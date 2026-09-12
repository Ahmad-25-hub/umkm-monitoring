<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use App\Support\SalesSpreadsheetReader;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportShopeeSalesXlsxAction
{
    private const PLATFORM = 'shopee';

    private const REQUIRED_HEADERS = [
        'No. Pesanan',
        'Status Pesanan',
        'Waktu Pesanan Dibuat',
        'Waktu Pembayaran Dilakukan',
        'Nama Produk',
        'Jumlah',
        'Subtotal Pesanan',
        'Total Pembayaran',
    ];

    public function __construct(
        private PersistImportedSalesOrdersAction $persistImportedSalesOrders,
        private SalesSpreadsheetReader $spreadsheetReader,
    ) {}

    /**
     * @return array{row_count: int, order_count: int, new_count: int, updated_count: int}
     *
     * @throws ValidationException
     */
    public function execute(Business $business, User $employee, UploadedFile $file): array
    {
        $rows = $this->spreadsheetReader->readRows($file, self::REQUIRED_HEADERS, formatError: 'Format file Shopee tidak sesuai. Gunakan file XLSX yang diunduh langsung dari menu Pesanan Shopee Seller.');
        $orders = [];
        $rowCount = 0;

        foreach ($rows as $index => $row) {
            $recordNumber = $index + 2;
            $orderId = $this->cleanValue($row['No. Pesanan'] ?? null);

            if ($orderId === '') {
                throw ValidationException::withMessages([
                    'sales_file' => "No. Pesanan pada baris data ke-{$recordNumber} tidak boleh kosong.",
                ]);
            }

            $status = $this->cleanValue($row['Status Pesanan'] ?? null);

            if ($status === '') {
                throw ValidationException::withMessages([
                    'sales_file' => "Status Pesanan pada baris data ke-{$recordNumber} tidak boleh kosong.",
                ]);
            }

            $orderedAt = $this->parseRequiredDate(
                $this->cleanValue($row['Waktu Pesanan Dibuat'] ?? null),
                'Waktu Pesanan Dibuat',
                $recordNumber,
            );
            $quantity = $this->parseQuantity($row['Jumlah'] ?? null, 'Jumlah', $recordNumber);
            $itemSubtotal = $this->parseAmount(
                $row['Subtotal Pesanan'] ?? null,
                'Subtotal Pesanan',
                $recordNumber,
            );
            $orderAmount = $this->parseAmount(
                $row['Total Pembayaran'] ?? null,
                'Total Pembayaran',
                $recordNumber,
            );
            $substatus = $this->nullableValue($row['Status Pembatalan/ Pengembalian'] ?? null);
            $paidAt = $this->parseOptionalDate(
                $this->cleanValue($row['Waktu Pembayaran Dilakukan'] ?? null),
                'Waktu Pembayaran Dilakukan',
                $recordNumber,
            );
            $item = [
                'product_name' => $this->cleanValue($row['Nama Produk'] ?? null),
                'variation' => $this->cleanValue($row['Nama Variasi'] ?? null),
                'quantity' => $quantity,
                'subtotal' => $itemSubtotal,
            ];
            $isCancelled = $this->isCancelled($status, $substatus);

            if (isset($orders[$orderId])) {
                $orders[$orderId]['quantity'] += $quantity;
                $orders[$orderId]['item_subtotal_amount'] += $itemSubtotal;
                $orders[$orderId]['order_amount'] = max($orders[$orderId]['order_amount'], $orderAmount);
                $orders[$orderId]['items'][] = $item;
                $orders[$orderId]['status'] = $status;
                $orders[$orderId]['substatus'] = $substatus ?? $orders[$orderId]['substatus'];
                $orders[$orderId]['paid_at'] ??= $paidAt;
                $orders[$orderId]['is_cancelled'] = $orders[$orderId]['is_cancelled'] || $isCancelled;
            } else {
                $orders[$orderId] = [
                    'status' => $status,
                    'substatus' => $substatus,
                    'quantity' => $quantity,
                    'item_subtotal_amount' => $itemSubtotal,
                    'order_amount' => $orderAmount,
                    'refund_amount' => 0,
                    'ordered_on' => $orderedAt->toDateString(),
                    'ordered_at' => $orderedAt->utc()->format('Y-m-d H:i:s'),
                    'paid_at' => $paidAt,
                    'cancelled_at' => null,
                    'is_cancelled' => $isCancelled,
                    'purchase_channel' => 'Shopee',
                    'order_channel' => 'Shopee',
                    'items' => [$item],
                ];
            }

            $rowCount++;
        }

        return $this->persistImportedSalesOrders->execute(
            $business,
            $employee,
            $file,
            self::PLATFORM,
            $rowCount,
            $orders,
        );
    }

    private function cleanValue(mixed $value): string
    {
        $cleanValue = trim((string) $value, " \t\n\r\0\x0B\"");

        return Str::replaceStart("\u{FEFF}", '', $cleanValue);
    }

    private function nullableValue(mixed $value): ?string
    {
        $cleanValue = $this->cleanValue($value);

        return $cleanValue === '' || $cleanValue === '-' ? null : Str::limit($cleanValue, 100, '');
    }

    /** @param array<int|string, mixed> $row */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->cleanValue($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseQuantity(mixed $value, string $column, int $recordNumber): int
    {
        $cleanValue = $this->cleanValue($value);

        if (! preg_match('/^\d+$/', $cleanValue)) {
            throw ValidationException::withMessages([
                'sales_file' => "Nilai {$column} pada baris data ke-{$recordNumber} tidak valid.",
            ]);
        }

        return (int) $cleanValue;
    }

    private function parseAmount(mixed $value, string $column, int $recordNumber): int
    {
        $cleanValue = Str::of($this->cleanValue($value))
            ->replace(['Rp', ' '], '')
            ->toString();

        if ($cleanValue === '' || $cleanValue === '-') {
            return 0;
        }

        if (preg_match('/^\d{1,3}(?:\.\d{3})+(?:,\d+)?$/', $cleanValue)) {
            $normalizedValue = str_replace(['.', ','], ['', '.'], $cleanValue);
        } elseif (preg_match('/^\d+(?:[.,]\d{1,2})?$/', $cleanValue)) {
            $normalizedValue = str_replace(',', '.', $cleanValue);
        } else {
            throw ValidationException::withMessages([
                'sales_file' => "Nilai {$column} pada baris data ke-{$recordNumber} tidak valid.",
            ]);
        }

        return (int) round((float) $normalizedValue);
    }

    private function parseRequiredDate(string $value, string $column, int $recordNumber): CarbonImmutable
    {
        $date = $this->parseDate($value);

        if ($date === null) {
            throw ValidationException::withMessages([
                'sales_file' => "Nilai {$column} pada baris data ke-{$recordNumber} tidak valid.",
            ]);
        }

        return $date;
    }

    private function parseOptionalDate(string $value, string $column, int $recordNumber): ?CarbonImmutable
    {
        if ($value === '' || $value === '-') {
            return null;
        }

        return $this->parseRequiredDate($value, $column, $recordNumber);
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        foreach (['Y-m-d H:i:s', 'Y-m-d H:i'] as $format) {
            try {
                $date = CarbonImmutable::createFromFormat(
                    $format,
                    $value,
                    ImportSalesOrdersAction::SALES_TIMEZONE,
                );
            } catch (Throwable) {
                continue;
            }

            if ($date !== false && $date->format($format) === $value) {
                return $date;
            }
        }

        return null;
    }

    private function isCancelled(string $status, ?string $substatus): bool
    {
        $normalizedStatus = Str::lower($status.' '.($substatus ?? ''));

        return Str::contains($normalizedStatus, [
            'batal',
            'cancel',
            'dikembalikan',
            'pengembalian selesai',
            'refund selesai',
        ]);
    }
}
