<?php

namespace Tests\Feature\Employee;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Phar;
use PharData;
use RuntimeException;
use Tests\TestCase;

class SalesImportTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_employee_login_when_importing_sales(): void
    {
        $this->post(route('employee.sales-imports.store'))
            ->assertRedirectToRoute('employee.login');
    }

    public function test_owner_without_employee_membership_cannot_import_sales(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->for($owner)->for($business)->create();

        $this->actingAs($owner)
            ->post(route('employee.sales-imports.store'), ['sales_file' => $this->salesFile()])
            ->assertRedirectToRoute('employee.business.join.create');

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_employee_imports_tiktok_csv_into_active_business_and_updates_dashboards(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        $employee = User::factory()->create();
        $business = Business::factory()->create(['name' => 'Usaha Penjualan Nyata']);
        $otherBusiness = Business::factory()->create(['name' => 'Usaha Tenant Lain']);
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();

        $this->actingAs($employee)
            ->withSession(['active_employee_business_id' => $otherBusiness->id])
            ->post(route('employee.sales-imports.store'), ['sales_file' => $this->salesFile()])
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('success', 'File berhasil diproses: 3 pesanan baru dan 0 pesanan diperbarui.');

        $this->assertDatabaseCount('sales_orders', 3);
        $this->assertDatabaseHas('sales_orders', [
            'business_id' => $business->id,
            'platform_order_id' => '1001',
            'quantity' => 3,
            'item_subtotal_amount' => 16_999,
            'order_amount' => 13_937,
            'net_sales_amount' => 13_937,
            'ordered_on' => '2026-09-01',
        ]);
        $this->assertDatabaseMissing('sales_orders', ['business_id' => $otherBusiness->id]);

        $this->get(route('employee.dashboard'))
            ->assertOk()
            ->assertSeeText('Unggah dari TikTok Seller atau Shopee')
            ->assertSeeText('Rp33.937')
            ->assertSeeText('Produk Terjual');

        $owner = User::factory()->create();
        BusinessMembership::factory()->for($owner)->for($business)->create();

        $this->actingAs($owner)
            ->withSession(['active_business_id' => $business->id])
            ->get(route('overview'))
            ->assertOk()
            ->assertSeeText('Penjualan hari ini sudah tercatat.')
            ->assertSeeText('Rp33.937')
            ->assertSeeText('2 transaksi sudah tercatat')
            ->assertDontSeeText('Rp24,8 jt');
    }

    public function test_employee_imports_shopee_xlsx_without_persisting_buyer_data(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        $employee = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();
        SalesOrder::factory()->for($business)->create([
            'platform' => 'tiktok',
            'platform_order_id' => '260901ABCDEFG',
            'ordered_on' => '2026-08-31',
        ]);

        $this->actingAs($employee)
            ->post(route('employee.sales-imports.store'), ['sales_file' => $this->shopeeSalesFile()])
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('success', 'File berhasil diproses: 2 pesanan baru dan 0 pesanan diperbarui.');

        $this->assertDatabaseCount('sales_orders', 3);
        $this->assertDatabaseHas('sales_orders', [
            'business_id' => $business->id,
            'platform' => 'shopee',
            'platform_order_id' => '260901ABCDEFG',
            'status' => 'Selesai',
            'quantity' => 3,
            'item_subtotal_amount' => 95_000,
            'order_amount' => 92_000,
            'net_sales_amount' => 92_000,
            'ordered_on' => '2026-09-01',
            'purchase_channel' => 'Shopee',
        ]);
        $this->assertDatabaseHas('sales_orders', [
            'business_id' => $business->id,
            'platform' => 'shopee',
            'platform_order_id' => '260901HIJKLMN',
            'is_cancelled' => true,
            'net_sales_amount' => 0,
            'paid_at' => null,
        ]);

        $shopeeOrder = SalesOrder::query()
            ->whereBelongsTo($business)
            ->where('platform', 'shopee')
            ->where('platform_order_id', '260901ABCDEFG')
            ->firstOrFail();

        $this->assertCount(2, $shopeeOrder->items);
        $this->assertSame(
            ['product_name', 'variation', 'quantity', 'subtotal'],
            array_keys($shopeeOrder->items[0]),
        );
        $this->assertStringNotContainsString('buyer-rahasia', json_encode($shopeeOrder->items, JSON_THROW_ON_ERROR));
        $this->assertStringNotContainsString('alamat-rahasia', json_encode($shopeeOrder->items, JSON_THROW_ON_ERROR));

        $this->actingAs($employee)
            ->post(route('employee.sales-imports.store'), ['sales_file' => $this->shopeeSalesFile()])
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('success', 'File berhasil diproses: 0 pesanan baru dan 2 pesanan diperbarui.');

        $this->assertDatabaseCount('sales_orders', 3);
    }

    public function test_reimport_updates_existing_order_instead_of_duplicating_it(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        $employee = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();
        SalesOrder::factory()->for($business)->create([
            'platform_order_id' => '1001',
            'quantity' => 1,
            'order_amount' => 1_000,
            'net_sales_amount' => 1_000,
        ]);

        $this->actingAs($employee)
            ->post(route('employee.sales-imports.store'), ['sales_file' => $this->salesFile()])
            ->assertRedirectToRoute('employee.dashboard')
            ->assertSessionHas('success', 'File berhasil diproses: 2 pesanan baru dan 1 pesanan diperbarui.');

        $this->assertDatabaseCount('sales_orders', 3);
        $this->assertDatabaseHas('sales_orders', [
            'business_id' => $business->id,
            'platform_order_id' => '1001',
            'quantity' => 3,
            'order_amount' => 13_937,
        ]);
    }

    public function test_import_rejects_csv_without_tiktok_seller_columns(): void
    {
        $employee = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();
        $file = UploadedFile::fake()->createWithContent('penjualan.csv', "kolom_salah,nilai\nfoo,bar");

        $this->actingAs($employee)
            ->post(route('employee.sales-imports.store'), ['sales_file' => $file])
            ->assertInvalid([
                'sales_file' => 'Format file TikTok Seller tidak sesuai. Kolom yang tidak ditemukan:',
            ]);

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_import_rejects_xlsx_without_shopee_columns(): void
    {
        $employee = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();
        $file = $this->shopeeSalesFile(['Kolom Salah'], [['nilai']]);

        $this->actingAs($employee)
            ->post(route('employee.sales-imports.store'), ['sales_file' => $file])
            ->assertInvalid([
                'sales_file' => 'Format file Shopee tidak sesuai.',
            ]);

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_import_rejects_unsupported_file(): void
    {
        $employee = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();
        $file = UploadedFile::fake()->createWithContent('penjualan.pdf', 'bukan file yang didukung');

        $this->actingAs($employee)
            ->post(route('employee.sales-imports.store'), ['sales_file' => $file])
            ->assertInvalid([
                'sales_file' => 'File pesanan harus berformat CSV TikTok Seller atau XLSX Shopee.',
            ]);

        $this->assertDatabaseCount('sales_orders', 0);
    }

    private function salesFile(): UploadedFile
    {
        $contents = file_get_contents(base_path('tests/Fixtures/tiktok-sales.csv'));

        $this->assertNotFalse($contents);

        return UploadedFile::fake()->createWithContent(
            'Untuk Dikirim pesanan-2026-09-01.csv',
            "\xEF\xBB\xBF".$contents,
        );
    }

    /**
     * @param  array<int, string>|null  $headers
     * @param  array<int, array<int, string>>|null  $rows
     */
    private function shopeeSalesFile(?array $headers = null, ?array $rows = null): UploadedFile
    {
        $headers ??= [
            'No. Pesanan',
            'Status Pesanan',
            'Status Pembatalan/ Pengembalian',
            'Waktu Pesanan Dibuat',
            'Waktu Pembayaran Dilakukan',
            'Nama Produk',
            'Nama Variasi',
            'Jumlah',
            'Returned quantity',
            'Subtotal Pesanan',
            'Total Pembayaran',
            'Username (Pembeli)',
            'Alamat Pengiriman',
        ];
        $rows ??= [
            [
                '260901ABCDEFG',
                'Selesai',
                '',
                '2026-09-01 08:15',
                '2026-09-01 08:16',
                'Produk Shopee A',
                'Merah',
                '2',
                '0',
                '70.000',
                '92.000',
                'buyer-rahasia',
                'alamat-rahasia',
            ],
            [
                '260901ABCDEFG',
                'Selesai',
                '',
                '2026-09-01 08:15',
                '2026-09-01 08:16',
                'Produk Shopee B',
                '',
                '1',
                '0',
                '25.000',
                '92.000',
                'buyer-rahasia',
                'alamat-rahasia',
            ],
            [
                '260901HIJKLMN',
                'Batal',
                '',
                '2026-09-01 09:00',
                '-',
                'Produk Dibatalkan',
                '',
                '1',
                '0',
                '35.000',
                '0',
                'buyer-lain',
                'alamat-lain',
            ],
        ];

        $temporaryPath = tempnam(sys_get_temp_dir(), 'shopee-fixture-');

        if ($temporaryPath === false) {
            throw new RuntimeException('Tidak dapat membuat file Shopee sementara.');
        }

        @unlink($temporaryPath);
        $archivePath = $temporaryPath.'.zip';
        $archive = null;

        try {
            $archive = new PharData($archivePath, 0, null, Phar::ZIP);
            $archive->addFromString('[Content_Types].xml', $this->contentTypesXml());
            $archive->addFromString('_rels/.rels', $this->rootRelationshipsXml());
            $archive->addFromString('xl/workbook.xml', $this->workbookXml());
            $archive->addFromString('xl/_rels/workbook.xml.rels', $this->workbookRelationshipsXml());
            $archive->addFromString('xl/worksheets/sheet2.xml', $this->worksheetXml($headers, $rows));
            unset($archive);

            $contents = file_get_contents($archivePath);

            if ($contents === false) {
                throw new RuntimeException('Tidak dapat membaca file Shopee sementara.');
            }
        } finally {
            unset($archive);
            @unlink($archivePath);
        }

        return UploadedFile::fake()->createWithContent('Order.all.20260805_20260904.xlsx', $contents);
    }

    private function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet2.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'</Types>';
    }

    private function rootRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="orders" sheetId="2" r:id="rId1"/></sheets>'
            .'</workbook>';
    }

    private function workbookRelationshipsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="/xl/worksheets/sheet2.xml"/>'
            .'</Relationships>';
    }

    /**
     * @param  array<int, string>  $headers
     * @param  array<int, array<int, string>>  $rows
     */
    private function worksheetXml(array $headers, array $rows): string
    {
        $xmlRows = '';

        foreach ([$headers, ...$rows] as $rowIndex => $values) {
            $rowNumber = $rowIndex + 1;
            $cells = '';

            foreach ($values as $columnIndex => $value) {
                $reference = $this->spreadsheetColumnName($columnIndex).$rowNumber;
                $escapedValue = htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $cells .= "<c r=\"{$reference}\" t=\"inlineStr\"><is><t xml:space=\"preserve\">{$escapedValue}</t></is></c>";
            }

            $xmlRows .= "<row r=\"{$rowNumber}\">{$cells}</row>";
        }

        return '<?xml version="1.0" encoding="UTF-8"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            ."<sheetData>{$xmlRows}</sheetData>"
            .'</worksheet>';
    }

    private function spreadsheetColumnName(int $columnIndex): string
    {
        $columnName = '';
        $columnNumber = $columnIndex + 1;

        while ($columnNumber > 0) {
            $remainder = ($columnNumber - 1) % 26;
            $columnName = chr(65 + $remainder).$columnName;
            $columnNumber = intdiv($columnNumber - 1, 26);
        }

        return $columnName;
    }
}
