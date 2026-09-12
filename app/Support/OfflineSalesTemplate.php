<?php

namespace App\Support;

use App\Actions\PrepareOfflineSalesAction;
use Phar;
use PharData;
use RuntimeException;

class OfflineSalesTemplate
{
    public function contents(): string
    {
        $temporary = tempnam(sys_get_temp_dir(), 'nadi-template-');
        if ($temporary === false) {
            throw new RuntimeException('Template tidak dapat disiapkan.');
        }
        unlink($temporary);
        $path = $temporary.'.zip';

        try {
            $archive = new PharData($path, 0, null, Phar::ZIP);
            $archive->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?>'
                .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
                .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
                .'<Default Extension="xml" ContentType="application/xml"/>'
                .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
                .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
                .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
            $archive->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
            $archive->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?>'
                .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
                .'<workbookPr date1904="0"/><sheets><sheet name="Penjualan" sheetId="1" r:id="rId1"/>'
                .'<sheet name="Panduan" sheetId="2" r:id="rId2"/></sheets></workbook>');
            $archive->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?>'
                .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
                .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
                .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet2.xml"/>'
                .'<Relationship Id="rId3" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/></Relationships>');
            $archive->addFromString('xl/styles.xml', $this->styles());
            $archive->addFromString('xl/worksheets/sheet1.xml', $this->salesSheet());
            $archive->addFromString('xl/worksheets/sheet2.xml', $this->guideSheet());
            unset($archive);
            $contents = file_get_contents($path);
            if ($contents === false) {
                throw new RuntimeException('Template tidak dapat dibaca.');
            }

            return $contents;
        } finally {
            unset($archive);
            @unlink($path);
        }
    }

    private function salesSheet(): string
    {
        $rows = '<row r="1" ht="30" customHeight="1">';
        foreach (PrepareOfflineSalesAction::HEADERS as $index => $header) {
            $rows .= $this->textCell(chr(65 + $index).'1', $header, 1);
        }
        $rows .= '</row>';
        for ($row = 2; $row <= 101; $row++) {
            $rows .= '<row r="'.$row.'" ht="24" customHeight="1">';
            foreach ([2, 3, 0, 4, 4] as $column => $style) {
                $rows .= '<c r="'.chr(65 + $column).$row.'" s="'.$style.'"/>';
            }
            $rows .= '</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            .'<cols><col min="1" max="1" width="19" customWidth="1" style="2"/><col min="2" max="2" width="25" customWidth="1" style="3"/>'
            .'<col min="3" max="3" width="38" customWidth="1"/><col min="4" max="5" width="20" customWidth="1" style="4"/></cols>'
            .'<sheetData>'.$rows.'</sheetData><autoFilter ref="A1:E2001"/>'
            .'<dataValidations count="2"><dataValidation type="whole" operator="between" allowBlank="1" showErrorMessage="1" errorTitle="Jumlah tidak valid" error="Isi angka bulat antara 1 dan 100000." sqref="D2:D2001"><formula1>1</formula1><formula2>100000</formula2></dataValidation>'
            .'<dataValidation type="whole" operator="between" allowBlank="1" showErrorMessage="1" errorTitle="Harga tidak valid" error="Isi rupiah bulat tanpa Rp atau pemisah ribuan." sqref="E2:E2001"><formula1>0</formula1><formula2>1000000000</formula2></dataValidation></dataValidations></worksheet>';
    }

    private function guideSheet(): string
    {
        $lines = [
            'NADI - Panduan penjualan offline',
            'Isi sheet Penjualan. Sheet Panduan ini tidak diimpor.',
            'Satu baris = satu produk. Maksimal 2.000 baris dan 10 MB per file.',
            'Tanggal: DD/MM/YYYY, antara 01/01/2000 dan hari ini. Contoh: 12/09/2026.',
            'No. transaksi: huruf, angka, - atau _. Maksimal 80 karakter; contoh OFF-001.',
            'Gunakan nomor yang sama untuk produk dalam satu transaksi, dengan tanggal yang sama.',
            'Gunakan nomor baru untuk transaksi lain, termasuk penjualan pada hari berikutnya.',
            'Produk: nama produk, maksimal 255 karakter. Jumlah: bilangan bulat positif.',
            'Harga satuan: rupiah bulat, tanpa Rp atau pemisah ribuan. Contoh: 18000.',
            'NADI menghitung total otomatis. Semua penjualan dicatat sebagai lunas, kanal Offline.',
            'Upload melalui Tambah penjualan > Upload Excel offline, periksa pratinjau, lalu simpan.',
            'Jika ada baris bermasalah, perbaiki file dan unggah kembali. Belum ada data yang disimpan.',
            'Nomor transaksi yang sudah tercatat akan dilewati, termasuk dari input manual.',
            'Jangan ubah judul kolom, gunakan formula, atau mengisi data di sheet lain.',
            'Contoh: OFF-001, Kopi susu, 2, 18000 dan OFF-001, Roti bakar, 1, 15000 = Rp51000.',
        ];
        $rows = '';
        foreach ($lines as $index => $line) {
            $row = $index + 1;
            $rows .= '<row r="'.$row.'" ht="32" customHeight="1">'.$this->textCell('A'.$row, $line, $index === 0 ? 1 : 0).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<cols><col min="1" max="1" width="125" customWidth="1"/></cols><sheetData>'.$rows.'</sheetData></worksheet>';
    }

    private function textCell(string $reference, string $text, int $style): string
    {
        $text = htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');

        return '<c r="'.$reference.'" s="'.$style.'" t="inlineStr"><is><t>'.$text.'</t></is></c>';
    }

    private function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?><styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<numFmts count="1"><numFmt numFmtId="164" formatCode="dd/mm/yyyy"/></numFmts>'
            .'<fonts count="2"><font><sz val="11"/><color rgb="FF20332B"/><name val="Calibri"/></font><font><b/><sz val="11"/><color rgb="FFFFFFFF"/><name val="Calibri"/></font></fonts>'
            .'<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill><fill><patternFill patternType="solid"><fgColor rgb="FF22664C"/><bgColor indexed="64"/></patternFill></fill></fills>'
            .'<borders count="1"><border/></borders><cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="5"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            .'<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFill="1" applyFont="1"><alignment vertical="center"/></xf>'
            .'<xf numFmtId="164" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="49" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            .'<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/></cellXfs>'
            .'<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles></styleSheet>';
    }
}
