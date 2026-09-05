<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ImportTikTokSalesCsvAction
{
    private const PLATFORM = 'tiktok';

    private const REQUIRED_HEADERS = [
        'Order ID',
        'Order Status',
        'Order Substatus',
        'Product Name',
        'Variation',
        'Quantity',
        'SKU Subtotal After Discount',
        'Order Refund Amount',
        'Order Amount',
        'Created Time',
        'Paid Time',
        'Cancelled Time',
        'Purchase Channel',
        'Order Channel',
    ];

    public function __construct(private PersistImportedSalesOrdersAction $persistImportedSalesOrders) {}

    /**
     * @return array{row_count: int, order_count: int, new_count: int, updated_count: int}
     *
     * @throws ValidationException
     */
    public function execute(Business $business, User $employee, UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'sales_file' => 'File penjualan tidak dapat dibaca.',
            ]);
        }

        $handle = fopen($path, 'rb');

        if ($handle === false) {
            throw ValidationException::withMessages([
                'sales_file' => 'File penjualan tidak dapat dibuka.',
            ]);
        }

        try {
            $headerRow = fgetcsv($handle, escape: '');

            if ($headerRow === false) {
                throw ValidationException::withMessages([
                    'sales_file' => 'File penjualan kosong.',
                ]);
            }

            $headers = array_map($this->cleanValue(...), $headerRow);
            $missingHeaders = array_values(array_diff(self::REQUIRED_HEADERS, $headers));

            if ($missingHeaders !== []) {
                throw ValidationException::withMessages([
                    'sales_file' => 'Format file TikTok Seller tidak sesuai. Kolom yang tidak ditemukan: '.implode(', ', $missingHeaders).'.',
                ]);
            }

            $orders = [];
            $rowCount = 0;
            $recordNumber = 1;

            while (($row = fgetcsv($handle, escape: '')) !== false) {
                $recordNumber++;

                if ($this->isEmptyRow($row)) {
                    continue;
                }

                if (count($row) !== count($headers)) {
                    throw ValidationException::withMessages([
                        'sales_file' => "Baris data ke-{$recordNumber} memiliki jumlah kolom yang tidak sesuai.",
                    ]);
                }

                /** @var array<string, string|null> $rowByHeader */
                $rowByHeader = array_combine($headers, $row);
                $orderId = $this->cleanValue($rowByHeader['Order ID']);

                if ($orderId === '') {
                    throw ValidationException::withMessages([
                        'sales_file' => "Order ID pada baris data ke-{$recordNumber} tidak boleh kosong.",
                    ]);
                }

                $orderedAt = $this->parseRequiredDate(
                    $this->cleanValue($rowByHeader['Created Time']),
                    'Created Time',
                    $recordNumber,
                );
                $quantity = $this->parseAmount($rowByHeader['Quantity'], 'Quantity', $recordNumber);
                $itemSubtotal = $this->parseAmount(
                    $rowByHeader['SKU Subtotal After Discount'],
                    'SKU Subtotal After Discount',
                    $recordNumber,
                );
                $orderAmount = $this->parseAmount($rowByHeader['Order Amount'], 'Order Amount', $recordNumber);
                $refundAmount = $this->parseAmount(
                    $rowByHeader['Order Refund Amount'],
                    'Order Refund Amount',
                    $recordNumber,
                );
                $item = [
                    'product_name' => $this->cleanValue($rowByHeader['Product Name']),
                    'variation' => $this->cleanValue($rowByHeader['Variation']),
                    'quantity' => $quantity,
                    'subtotal' => $itemSubtotal,
                ];

                if (isset($orders[$orderId])) {
                    $orders[$orderId]['quantity'] += $quantity;
                    $orders[$orderId]['item_subtotal_amount'] += $itemSubtotal;
                    $orders[$orderId]['order_amount'] = max($orders[$orderId]['order_amount'], $orderAmount);
                    $orders[$orderId]['refund_amount'] = max($orders[$orderId]['refund_amount'], $refundAmount);
                    $orders[$orderId]['items'][] = $item;
                    $orders[$orderId]['status'] = $this->cleanValue($rowByHeader['Order Status']);
                    $orders[$orderId]['substatus'] = $this->nullableValue($rowByHeader['Order Substatus']);
                    $orders[$orderId]['paid_at'] ??= $this->parseOptionalDate(
                        $this->cleanValue($rowByHeader['Paid Time']),
                        'Paid Time',
                        $recordNumber,
                    );
                    $orders[$orderId]['cancelled_at'] ??= $this->parseOptionalDate(
                        $this->cleanValue($rowByHeader['Cancelled Time']),
                        'Cancelled Time',
                        $recordNumber,
                    );
                } else {
                    $orders[$orderId] = [
                        'status' => $this->cleanValue($rowByHeader['Order Status']),
                        'substatus' => $this->nullableValue($rowByHeader['Order Substatus']),
                        'quantity' => $quantity,
                        'item_subtotal_amount' => $itemSubtotal,
                        'order_amount' => $orderAmount,
                        'refund_amount' => $refundAmount,
                        'ordered_on' => $orderedAt->toDateString(),
                        'ordered_at' => $orderedAt->utc()->format('Y-m-d H:i:s'),
                        'paid_at' => $this->parseOptionalDate(
                            $this->cleanValue($rowByHeader['Paid Time']),
                            'Paid Time',
                            $recordNumber,
                        ),
                        'cancelled_at' => $this->parseOptionalDate(
                            $this->cleanValue($rowByHeader['Cancelled Time']),
                            'Cancelled Time',
                            $recordNumber,
                        ),
                        'purchase_channel' => $this->nullableValue($rowByHeader['Purchase Channel']),
                        'order_channel' => $this->nullableValue($rowByHeader['Order Channel']),
                        'items' => [$item],
                    ];
                }

                $rowCount++;
            }
        } finally {
            fclose($handle);
        }

        foreach ($orders as $orderId => $order) {
            $orders[$orderId]['is_cancelled'] = $order['cancelled_at'] !== null || Str::contains(
                Str::lower($order['status']),
                ['batal', 'cancel'],
            );
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

    private function cleanValue(?string $value): string
    {
        $cleanValue = trim((string) $value, " \t\n\r\0\x0B\"");

        return Str::replaceStart("\u{FEFF}", '', $cleanValue);
    }

    private function nullableValue(?string $value): ?string
    {
        $cleanValue = $this->cleanValue($value);

        return $cleanValue === '' ? null : $cleanValue;
    }

    /**
     * @param  array<int, string|null>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if ($this->cleanValue($value) !== '') {
                return false;
            }
        }

        return true;
    }

    private function parseAmount(?string $value, string $column, int $recordNumber): int
    {
        $cleanValue = $this->cleanValue($value);

        if ($cleanValue === '') {
            return 0;
        }

        if (! preg_match('/^\d+(?:\.\d+)?$/', $cleanValue)) {
            throw ValidationException::withMessages([
                'sales_file' => "Nilai {$column} pada baris data ke-{$recordNumber} tidak valid.",
            ]);
        }

        return (int) round((float) $cleanValue);
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
        if ($value === '') {
            return null;
        }

        return $this->parseRequiredDate($value, $column, $recordNumber);
    }

    private function parseDate(string $value): ?CarbonImmutable
    {
        try {
            $date = CarbonImmutable::createFromFormat(
                'd/m/Y H:i:s',
                $value,
                ImportSalesOrdersAction::SALES_TIMEZONE,
            );
        } catch (Throwable) {
            return null;
        }

        if ($date === false || $date->format('d/m/Y H:i:s') !== $value) {
            return null;
        }

        return $date;
    }
}
