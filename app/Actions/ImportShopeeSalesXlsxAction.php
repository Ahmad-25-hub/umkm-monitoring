<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PharData;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use Throwable;
use XMLReader;

class ImportShopeeSalesXlsxAction
{
    private const PLATFORM = 'shopee';

    private const MAX_SHARED_STRINGS_BYTES = 50 * 1024 * 1024;

    private const MAX_WORKSHEET_BYTES = 100 * 1024 * 1024;

    private const MAX_WORKSHEETS = 20;

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

    public function __construct(private PersistImportedSalesOrdersAction $persistImportedSalesOrders) {}

    /**
     * @return array{row_count: int, order_count: int, new_count: int, updated_count: int}
     *
     * @throws ValidationException
     */
    public function execute(Business $business, User $employee, UploadedFile $file): array
    {
        $rows = $this->readRows($file);
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

    /**
     * @return array<int, array<string, string>>
     *
     * @throws ValidationException
     */
    private function readRows(UploadedFile $file): array
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'sales_file' => 'File penjualan tidak dapat dibaca.',
            ]);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'shopee-sales-');

        if ($temporaryPath === false) {
            throw ValidationException::withMessages([
                'sales_file' => 'File Shopee tidak dapat disiapkan untuk dibaca.',
            ]);
        }

        @unlink($temporaryPath);
        $archivePath = $temporaryPath.'.zip';
        $archive = null;

        try {
            if (! copy($path, $archivePath)) {
                throw ValidationException::withMessages([
                    'sales_file' => 'File Shopee tidak dapat disiapkan untuk dibaca.',
                ]);
            }

            $archive = new PharData($archivePath);
            $sharedStrings = $this->readSharedStrings($archive);
            $worksheetPaths = $this->worksheetPaths($archive);

            foreach ($worksheetPaths as $worksheetPath) {
                $rows = $this->readWorksheet($worksheetPath, $sharedStrings);

                if ($rows !== null) {
                    return $rows;
                }
            }

            throw ValidationException::withMessages([
                'sales_file' => 'Format file Shopee tidak sesuai. Gunakan file XLSX yang diunduh langsung dari menu Pesanan Shopee Seller.',
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'sales_file' => 'File XLSX Shopee rusak atau tidak dapat dibaca.',
            ]);
        } finally {
            unset($archive);
            @unlink($archivePath);
        }
    }

    /**
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    private function worksheetPaths(PharData $archive): array
    {
        $worksheetPaths = [];

        foreach (new RecursiveIteratorIterator($archive) as $entry) {
            if ($entry->isDir()) {
                continue;
            }

            $entryPath = str_replace('\\', '/', $entry->getPathName());

            if (! preg_match('~/xl/worksheets/[^/]+\.xml$~i', $entryPath)) {
                continue;
            }

            if ($entry->getSize() > self::MAX_WORKSHEET_BYTES) {
                throw ValidationException::withMessages([
                    'sales_file' => 'Worksheet pada file Shopee terlalu besar untuk diproses.',
                ]);
            }

            $worksheetPaths[] = $entryPath;

            if (count($worksheetPaths) > self::MAX_WORKSHEETS) {
                throw ValidationException::withMessages([
                    'sales_file' => 'File Shopee memiliki terlalu banyak worksheet.',
                ]);
            }
        }

        natsort($worksheetPaths);

        return array_values($worksheetPaths);
    }

    /**
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    private function readSharedStrings(PharData $archive): array
    {
        if (! isset($archive['xl/sharedStrings.xml'])) {
            return [];
        }

        $entry = $archive['xl/sharedStrings.xml'];

        if ($entry->getSize() > self::MAX_SHARED_STRINGS_BYTES) {
            throw ValidationException::withMessages([
                'sales_file' => 'Tabel teks pada file Shopee terlalu besar untuk diproses.',
            ]);
        }

        $reader = new XMLReader;

        if (! @$reader->open($entry->getPathName(), null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw ValidationException::withMessages([
                'sales_file' => 'Tabel teks pada file Shopee tidak dapat dibaca.',
            ]);
        }

        $strings = [];

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'si') {
                    continue;
                }

                $strings[] = $this->textFromXml($reader->readOuterXml());
            }
        } finally {
            $reader->close();
        }

        return $strings;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, array<string, string>>|null
     *
     * @throws ValidationException
     */
    private function readWorksheet(string $worksheetPath, array $sharedStrings): ?array
    {
        $reader = new XMLReader;

        if (! @$reader->open($worksheetPath, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw ValidationException::withMessages([
                'sales_file' => 'Worksheet pada file Shopee tidak dapat dibaca.',
            ]);
        }

        $headers = null;
        $rows = [];
        $headerCandidates = 0;

        try {
            while ($reader->read()) {
                if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                    continue;
                }

                $rowXml = $reader->readOuterXml();
                $values = $this->valuesFromRowXml($rowXml, $sharedStrings);

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                if ($headers === null) {
                    $candidateHeaders = array_map($this->cleanValue(...), $values);
                    $headerCandidates++;

                    if (array_diff(self::REQUIRED_HEADERS, $candidateHeaders) === []) {
                        $headers = $candidateHeaders;
                    } elseif ($headerCandidates >= 20) {
                        return null;
                    }

                    continue;
                }

                $row = [];

                foreach ($headers as $columnIndex => $header) {
                    if ($header !== '') {
                        $row[$header] = $values[$columnIndex] ?? '';
                    }
                }

                if (! $this->isEmptyRow($row)) {
                    $rows[] = $row;
                }
            }
        } finally {
            $reader->close();
        }

        return $headers === null ? null : $rows;
    }

    /**
     * @param  array<int, string>  $sharedStrings
     * @return array<int, string>
     *
     * @throws ValidationException
     */
    private function valuesFromRowXml(string $rowXml, array $sharedStrings): array
    {
        $row = simplexml_load_string($rowXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);

        if ($row === false) {
            throw ValidationException::withMessages([
                'sales_file' => 'Salah satu baris pada file Shopee tidak dapat dibaca.',
            ]);
        }

        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $values = [];

        foreach ($row->children($namespace)->c as $cell) {
            $reference = (string) $cell->attributes()->r;
            $columnIndex = $this->columnIndex($reference);
            $values[$columnIndex] = $this->cellValue($cell, $sharedStrings, $namespace);
        }

        if ($values === []) {
            return [];
        }

        $maximumColumnIndex = max(array_keys($values));

        for ($columnIndex = 0; $columnIndex <= $maximumColumnIndex; $columnIndex++) {
            $values[$columnIndex] ??= '';
        }

        ksort($values);

        return array_values($values);
    }

    /** @param array<int, string> $sharedStrings */
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings, string $namespace): string
    {
        $type = (string) $cell->attributes()->t;
        $children = $cell->children($namespace);

        if ($type === 'inlineStr') {
            return $this->textFromElement($cell);
        }

        $value = (string) $children->v;

        if ($type === 's') {
            return $sharedStrings[(int) $value] ?? '';
        }

        if ($type === 'b') {
            return $value === '1' ? '1' : '0';
        }

        return $value;
    }

    private function textFromXml(string $xml): string
    {
        $element = simplexml_load_string($xml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);

        if ($element === false) {
            return '';
        }

        return $this->textFromElement($element);
    }

    private function textFromElement(SimpleXMLElement $element): string
    {
        $textNodes = $element->xpath('.//*[local-name() = "t"]');

        if ($textNodes === false) {
            return '';
        }

        return implode('', array_map(
            fn (SimpleXMLElement $textNode): string => (string) $textNode,
            $textNodes,
        ));
    }

    private function columnIndex(string $reference): int
    {
        if (! preg_match('/^([A-Z]+)/i', $reference, $matches)) {
            return 0;
        }

        $index = 0;

        foreach (str_split(Str::upper($matches[1])) as $character) {
            $index = ($index * 26) + ord($character) - 64;
        }

        return $index - 1;
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
