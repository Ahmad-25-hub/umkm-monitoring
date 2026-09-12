<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PharData;
use RecursiveIteratorIterator;
use SimpleXMLElement;
use Throwable;
use XMLReader;

class SalesSpreadsheetReader
{
    private const MAX_SHARED_STRINGS_BYTES = 50 * 1024 * 1024;

    private const MAX_WORKSHEET_BYTES = 100 * 1024 * 1024;

    private const MAX_WORKSHEETS = 20;

    /**
     * @return array<int, array<string, string>>
     *
     * @throws ValidationException
     */
    public function readRows(UploadedFile $file, array $requiredHeaders, int $maxRows = 100000, bool $strict = false, ?string $formatError = null): array
    {
        $path = $file->getRealPath();

        if ($path === false || ! is_readable($path)) {
            throw ValidationException::withMessages([
                'sales_file' => 'File penjualan tidak dapat dibaca.',
            ]);
        }

        $temporaryPath = tempnam(sys_get_temp_dir(), 'sales-sheet-');

        if ($temporaryPath === false) {
            throw ValidationException::withMessages([
                'sales_file' => 'File Excel tidak dapat disiapkan untuk dibaca.',
            ]);
        }

        @unlink($temporaryPath);
        $archivePath = $temporaryPath.'.zip';
        $archive = null;

        try {
            if (! copy($path, $archivePath)) {
                throw ValidationException::withMessages([
                    'sales_file' => 'File Excel tidak dapat disiapkan untuk dibaca.',
                ]);
            }

            $archive = new PharData($archivePath);
            if ($strict && isset($archive['xl/workbook.xml']) && preg_match('/date1904=["\'](?:1|true)["\']/', $archive['xl/workbook.xml']->getContent())) {
                throw ValidationException::withMessages(['sales_file' => 'Gunakan sistem tanggal standar dari template NADI (bukan sistem tanggal 1904).']);
            }
            $sharedStrings = $this->readSharedStrings($archive);
            $worksheetPaths = $this->worksheetPaths($archive);

            foreach ($worksheetPaths as $worksheetPath) {
                $rows = $this->readWorksheet($worksheetPath, $sharedStrings, $requiredHeaders, $maxRows, $strict);

                if ($rows !== null) {
                    return $rows;
                }
            }

            throw ValidationException::withMessages([
                'sales_file' => $formatError ?? 'Kolom file tidak sesuai. Kolom wajib: '.implode(', ', $requiredHeaders).'.',
            ]);
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'sales_file' => 'File XLSX Excel rusak atau tidak dapat dibaca.',
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
                    'sales_file' => 'Worksheet pada file Excel terlalu besar untuk diproses.',
                ]);
            }

            $worksheetPaths[] = $entryPath;

            if (count($worksheetPaths) > self::MAX_WORKSHEETS) {
                throw ValidationException::withMessages([
                    'sales_file' => 'File Excel memiliki terlalu banyak worksheet.',
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
                'sales_file' => 'Tabel teks pada file Excel terlalu besar untuk diproses.',
            ]);
        }

        $reader = new XMLReader;

        if (! @$reader->open($entry->getPathName(), null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw ValidationException::withMessages([
                'sales_file' => 'Tabel teks pada file Excel tidak dapat dibaca.',
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
    private function readWorksheet(string $worksheetPath, array $sharedStrings, array $requiredHeaders, int $maxRows, bool $strict): ?array
    {
        $reader = new XMLReader;

        if (! @$reader->open($worksheetPath, null, LIBXML_NONET | LIBXML_COMPACT)) {
            throw ValidationException::withMessages([
                'sales_file' => 'Worksheet pada file Excel tidak dapat dibaca.',
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

                $rowNumber = (int) $reader->getAttribute('r');
                $rowXml = $reader->readOuterXml();
                $values = $this->valuesFromRowXml($rowXml, $sharedStrings, $strict);

                if ($this->isEmptyRow($values)) {
                    continue;
                }

                if ($headers === null) {
                    $candidateHeaders = array_map($this->cleanValue(...), $values);
                    $headerCandidates++;

                    if (array_diff($requiredHeaders, $candidateHeaders) === []) {
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
                    $row['_row'] = (string) $rowNumber;
                    $rows[] = $row;

                    if (count($rows) > $maxRows) {
                        throw ValidationException::withMessages(['sales_file' => "Maksimal {$maxRows} baris penjualan per file."]);
                    }
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
    private function valuesFromRowXml(string $rowXml, array $sharedStrings, bool $strict): array
    {
        $row = simplexml_load_string($rowXml, SimpleXMLElement::class, LIBXML_NONET | LIBXML_COMPACT);

        if ($row === false) {
            throw ValidationException::withMessages([
                'sales_file' => 'Salah satu baris pada file Excel tidak dapat dibaca.',
            ]);
        }

        $namespace = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';
        $values = [];

        foreach ($row->children($namespace)->c as $cell) {
            $reference = (string) $cell->attributes()->r;
            $columnIndex = $this->columnIndex($reference);
            $values[$columnIndex] = $this->cellValue($cell, $sharedStrings, $namespace, $strict);
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
    private function cellValue(SimpleXMLElement $cell, array $sharedStrings, string $namespace, bool $strict): string
    {
        $type = (string) $cell->attributes()->t;
        $children = $cell->children($namespace);

        if ($strict && isset($children->f)) {
            return '__NADI_FORMULA__';
        }

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
}
