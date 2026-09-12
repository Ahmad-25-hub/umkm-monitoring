<?php

namespace Tests\Feature;

use App\Actions\PrepareOfflineSalesAction;
use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\SalesOrder;
use App\Models\User;
use App\Support\OfflineSalesTemplate;
use App\Support\SalesSpreadsheetReader;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use PharData;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OfflineSalesTest extends TestCase
{
    use LazilyRefreshDatabase;

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_both_roles_can_open_entry_download_template_and_record_multiple_products(bool $employee): void
    {
        $business = $this->signIn($employee);
        $prefix = $employee ? 'employee.sales-entry' : 'sales-entry';
        $this->get(route($prefix.'.create'))->assertOk()
            ->assertSeeText('Catat manual')->assertSeeText('Upload Excel offline')->assertSeeText('Impor TikTok Seller atau Shopee');
        $template = $this->get(route($prefix.'.template'))->assertOk()->assertDownload('template-penjualan-offline-nadi.xlsx');
        $this->assertSame([], app(SalesSpreadsheetReader::class)->readRows(
            UploadedFile::fake()->createWithContent('template.xlsx', $template->getContent()), PrepareOfflineSalesAction::HEADERS,
        ));

        $this->post(route($prefix.'.store'), [
            ...$this->manualData($business), 'save_again' => 1,
            'platform' => 'shopee', 'net_sales_amount' => 1, 'imported_by_user_id' => 999,
        ])->assertRedirectToRoute($prefix.'.create')->assertSessionHas('success', 'Penjualan offline berhasil dicatat.');

        $this->assertDatabaseCount('sales_orders', 1);
        $order = SalesOrder::query()->sole();
        $this->assertSame($business->id, $order->business_id);
        $this->assertSame(auth()->id(), $order->imported_by_user_id);
        $this->assertSame('offline', $order->platform);
        $this->assertSame('OFF-001', $order->platform_order_id);
        $this->assertSame(3, $order->quantity);
        $this->assertSame(51000, $order->net_sales_amount);
        $this->assertSame('2026-09-11 17:00:00', $order->ordered_at->format('Y-m-d H:i:s'));
        $this->assertCount(2, $order->items);
        $this->assertSame(36000, $order->items[0]['subtotal']);
        $this->get(route($employee ? 'employee.dashboard' : 'sales.index'))->assertOk()->assertSeeText('Rp51.000');
    }

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_excel_is_only_saved_after_confirmation_and_reupload_skips_existing_orders(bool $employee): void
    {
        $business = $this->signIn($employee);
        $prefix = $employee ? 'employee.sales-entry' : 'sales-entry';
        $other = Business::factory()->create();
        SalesOrder::factory()->for($other)->create(['platform' => 'offline', 'platform_order_id' => 'OFF-001']);
        $file = $this->spreadsheet([
            ['12/09/2026', 'off-001', 'Kopi susu', 2, 18000],
            ['12/09/2026', 'OFF-001', 'Roti bakar', 1, 15000],
            ['2026-09-11', 'OFF-002', 'Teh manis', 3, 5000],
        ]);
        $preview = $this->post(route($prefix.'.preview'), ['business_id' => $business->id, 'sales_file' => $file])
            ->assertOk()->assertSeeText('2 transaksi baru')->assertSeeText('Belum ada data yang disimpan.');
        $this->assertDatabaseCount('sales_orders', 1);

        $this->post(route($prefix.'.confirm'), ['token' => $preview->viewData('token'), 'orders' => [['net_sales_amount' => 1]]])
            ->assertRedirectToRoute($employee ? 'employee.dashboard' : 'sales.index');

        $this->assertDatabaseCount('sales_orders', 3);
        $this->assertDatabaseHas('sales_orders', ['business_id' => $business->id, 'platform_order_id' => 'OFF-001', 'quantity' => 3, 'net_sales_amount' => 51000]);
        $this->assertDatabaseHas('sales_orders', ['business_id' => $business->id, 'platform_order_id' => 'OFF-002', 'net_sales_amount' => 15000]);
        $repeat = $this->post(route($prefix.'.preview'), ['business_id' => $business->id, 'sales_file' => $file])
            ->assertOk()->assertSeeText('0 transaksi baru')->assertSeeText('2 transaksi sudah tercatat');
        $this->post(route($prefix.'.confirm'), ['token' => $repeat->viewData('token')])->assertRedirect();
        $this->assertDatabaseCount('sales_orders', 3);
    }

    public function test_manual_duplicate_does_not_overwrite_an_existing_offline_order(): void
    {
        $business = $this->signIn();
        SalesOrder::factory()->for($business)->create(['platform' => 'offline', 'platform_order_id' => 'OFF-001', 'net_sales_amount' => 9000]);

        $this->post(route('sales-entry.store'), $this->manualData($business))
            ->assertInvalid(['order_id' => 'Nomor transaksi ini sudah tercatat.']);

        $this->assertDatabaseCount('sales_orders', 1);
        $this->assertSame(9000, SalesOrder::query()->sole()->net_sales_amount);
    }

    public function test_invalid_excel_rows_show_real_row_numbers_and_escape_product_names_without_saving(): void
    {
        $business = $this->signIn();
        $file = $this->spreadsheet([
            ['12/09/2026', 'OFF-001', '<script>alert(1)</script>', 1, 1000],
            [],
            ['31/02/2026', 'OFF-002', 'Teh', -1, 'Rp5.000'],
        ]);

        $response = $this->post(route('sales-entry.preview'), ['business_id' => $business->id, 'sales_file' => $file])
            ->assertOk()->assertSeeText('Baris 4:')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false);

        $this->assertNull($response->viewData('token'));
        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_excel_rejects_conflicting_dates_for_the_same_order(): void
    {
        $business = $this->signIn();
        $file = $this->spreadsheet([
            ['12/09/2026', 'OFF-001', 'Kopi', 1, 1000],
            ['11/09/2026', 'OFF-001', 'Roti', 1, 1000],
        ]);

        $response = $this->post(route('sales-entry.preview'), ['business_id' => $business->id, 'sales_file' => $file])
            ->assertOk()->assertSeeText('Tanggal berbeda untuk nomor transaksi yang sama.');

        $this->assertNull($response->viewData('token'));
        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_native_excel_dates_and_zero_prices_are_supported(): void
    {
        $business = $this->signIn();
        $file = $this->spreadsheet([[46277, 'OFF-001', 'Sampel', 1, 0]]);
        $preview = $this->post(route('sales-entry.preview'), ['business_id' => $business->id, 'sales_file' => $file])->assertOk();

        $this->post(route('sales-entry.confirm'), ['token' => $preview->viewData('token')])->assertRedirectToRoute('sales.index');

        $this->assertSame('2026-09-12', SalesOrder::query()->sole()->ordered_on->toDateString());
        $this->assertSame(0, SalesOrder::query()->sole()->net_sales_amount);
    }

    #[TestWith(['ordered_on', '2026-09-13'])]
    #[TestWith(['ordered_on', '2026-02-31'])]
    #[TestWith(['order_id', '<script>'])]
    #[TestWith(['items.0.quantity', 0])]
    #[TestWith(['items.0.quantity', 1.5])]
    #[TestWith(['items.0.unit_price', -100])]
    #[TestWith(['items.0.unit_price', '18.000'])]
    #[TestWith(['items.0.product_name', ''])]
    public function test_invalid_manual_sales_cannot_be_saved(string $field, mixed $value): void
    {
        $business = $this->signIn();
        $data = $this->manualData($business);
        data_set($data, $field, $value);

        $this->post(route('sales-entry.store'), $data)->assertInvalid([$field]);

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_manual_and_upload_forms_refuse_a_different_business(): void
    {
        $business = $this->signIn();
        $other = Business::factory()->create();

        $this->post(route('sales-entry.store'), [...$this->manualData($business), 'business_id' => $other->id])->assertInvalid(['business_id']);
        $this->post(route('sales-entry.preview'), ['business_id' => $other->id, 'sales_file' => $this->spreadsheet([])])->assertInvalid(['business_id']);

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_confirmation_is_scoped_to_user_business_and_expiry(): void
    {
        $business = $this->signIn();
        $owner = auth()->user();
        $file = $this->spreadsheet([['12/09/2026', 'OFF-001', 'Kopi', 1, 1000]]);
        $preview = $this->post(route('sales-entry.preview'), ['business_id' => $business->id, 'sales_file' => $file])->assertOk();
        $token = $preview->viewData('token');
        $other = Business::factory()->create();
        BusinessMembership::factory()->for($owner)->for($other)->create();
        $this->withSession(['active_business_id' => $other->id])->post(route('sales-entry.confirm'), ['token' => $token])->assertSessionHasErrors('sales_file');
        $anotherUser = User::factory()->create();
        BusinessMembership::factory()->for($anotherUser)->for($business)->create();
        $this->actingAs($anotherUser)->withSession(['active_business_id' => $business->id])
            ->post(route('sales-entry.confirm'), ['token' => $token])->assertSessionHasErrors('sales_file');
        $this->actingAs($owner);
        $this->travel(31)->minutes();
        $this->post(route('sales-entry.confirm'), ['token' => $token])->assertSessionHasErrors('sales_file');

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_revoked_employee_cannot_confirm_a_previously_valid_preview(): void
    {
        $business = $this->signIn(true);
        $preview = $this->post(route('employee.sales-entry.preview'), [
            'business_id' => $business->id, 'sales_file' => $this->spreadsheet([['12/09/2026', 'OFF-001', 'Kopi', 1, 1000]]),
        ])->assertOk();
        BusinessMembership::query()->where('business_id', $business->id)->update(['status' => 'inactive']);

        $this->post(route('employee.sales-entry.confirm'), ['token' => $preview->viewData('token')])->assertForbidden();

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_employee_cannot_use_owner_entry_routes(): void
    {
        $business = $this->signIn(true);

        $this->post(route('sales-entry.store'), $this->manualData($business))->assertRedirectToRoute('login');

        $this->assertDatabaseCount('sales_orders', 0);
    }

    #[TestWith(['sales-entry', 'login'])]
    #[TestWith(['employee.sales-entry', 'employee.login'])]
    public function test_guests_cannot_access_entry_endpoints(string $prefix, string $login): void
    {
        $this->get(route($prefix.'.create'))->assertRedirectToRoute($login);
        $this->get(route($prefix.'.template'))->assertRedirectToRoute($login);
        $this->post(route($prefix.'.store'))->assertRedirectToRoute($login);
        $this->post(route($prefix.'.preview'))->assertRedirectToRoute($login);
        $this->post(route($prefix.'.confirm'))->assertRedirectToRoute($login);
    }

    public function test_empty_corrupt_or_oversized_spreadsheets_cannot_be_previewed(): void
    {
        $business = $this->signIn();
        $this->post(route('sales-entry.preview'), ['business_id' => $business->id, 'sales_file' => $this->spreadsheet([])])
            ->assertInvalid(['sales_file' => 'Template masih kosong.']);
        $this->post(route('sales-entry.preview'), ['business_id' => $business->id, 'sales_file' => UploadedFile::fake()->createWithContent('bad.xlsx', 'broken')])
            ->assertInvalid(['sales_file']);
        $this->post(route('sales-entry.preview'), ['business_id' => $business->id, 'sales_file' => $this->spreadsheet(array_fill(0, 2001, ['12/09/2026', 'OFF-001', 'Kopi', 1, 1000]))])
            ->assertInvalid(['sales_file' => 'Maksimal 2000 baris']);

        $this->assertDatabaseCount('sales_orders', 0);
    }

    public function test_owner_can_still_import_marketplace_exports(): void
    {
        $business = $this->signIn();
        $this->post(route('sales-imports.store'), [
            'business_id' => $business->id,
            'sales_file' => UploadedFile::fake()->createWithContent('tiktok.csv', file_get_contents(base_path('tests/Fixtures/tiktok-sales.csv'))),
        ])->assertRedirectToRoute('sales.index')->assertSessionHas('success');

        $this->assertDatabaseHas('sales_orders', ['business_id' => $business->id, 'platform' => 'tiktok', 'platform_order_id' => '1001']);
    }

    public function test_owner_can_import_shopee_and_keep_offline_transactions_separate(): void
    {
        $business = $this->signIn();
        SalesOrder::factory()->for($business)->create(['platform' => 'offline', 'platform_order_id' => 'OFF-001']);
        $file = $this->spreadsheet([
            ['OFF-001', 'Selesai', '2026-09-12 08:00', '2026-09-12 08:05', 'Kopi', 2, 36000, 36000],
        ], ['No. Pesanan', 'Status Pesanan', 'Waktu Pesanan Dibuat', 'Waktu Pembayaran Dilakukan', 'Nama Produk', 'Jumlah', 'Subtotal Pesanan', 'Total Pembayaran']);

        $this->post(route('sales-imports.store'), ['business_id' => $business->id, 'sales_file' => $file])
            ->assertRedirectToRoute('sales.index')->assertSessionHas('success');

        $this->assertDatabaseCount('sales_orders', 2);
        $this->assertDatabaseHas('sales_orders', ['business_id' => $business->id, 'platform' => 'shopee', 'platform_order_id' => 'OFF-001', 'net_sales_amount' => 36000]);
    }

    public function test_confirmation_rechecks_duplicates_and_cannot_be_replayed(): void
    {
        $business = $this->signIn();
        $preview = $this->post(route('sales-entry.preview'), [
            'business_id' => $business->id, 'sales_file' => $this->spreadsheet([['12/09/2026', 'OFF-001', 'Kopi', 1, 1000]]),
        ])->assertOk();
        SalesOrder::factory()->for($business)->create(['platform' => 'offline', 'platform_order_id' => 'OFF-001', 'net_sales_amount' => 5000]);

        $this->post(route('sales-entry.confirm'), ['token' => $preview->viewData('token')])
            ->assertRedirectToRoute('sales.index')->assertSessionHas('success', 'Penjualan offline tersimpan: 0 transaksi baru; 1 transaksi yang sudah tercatat dilewati.');
        $this->post(route('sales-entry.confirm'), ['token' => $preview->viewData('token')])->assertSessionHasErrors('sales_file');

        $this->assertDatabaseCount('sales_orders', 1);
        $this->assertSame(5000, SalesOrder::query()->sole()->net_sales_amount);
    }

    public function test_excel_formulas_are_flagged_instead_of_importing_cached_results(): void
    {
        $business = $this->signIn();
        $file = $this->spreadsheet([['12/09/2026', 'OFF-001', 'Kopi', 1, ['formula' => '1000*2', 'value' => 2000]]]);

        $preview = $this->post(route('sales-entry.preview'), ['business_id' => $business->id, 'sales_file' => $file])
            ->assertOk()->assertSeeText('Isi nilai penjualan langsung, bukan formula Excel.');

        $this->assertNull($preview->viewData('token'));
        $this->assertDatabaseCount('sales_orders', 0);
    }

    private function signIn(bool $employee = false): Business
    {
        $this->travelTo('2026-09-12 12:00:00');
        $user = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->for($user)->for($business)->create(['role' => $employee ? 'employee' : 'owner']);
        $this->actingAs($user)->withSession([$employee ? 'active_employee_business_id' : 'active_business_id' => $business->id]);

        return $business;
    }

    /** @return array<string, mixed> */
    private function manualData(Business $business): array
    {
        return [
            'business_id' => $business->id, 'order_id' => 'off-001', 'ordered_on' => '2026-09-12',
            'items' => [
                ['product_name' => 'Kopi susu', 'quantity' => 2, 'unit_price' => 18000],
                ['product_name' => 'Roti bakar', 'quantity' => 1, 'unit_price' => 15000],
            ],
        ];
    }

    /**
     * @param  array<int, array<int, string|int|array{formula: string, value: int}>>  $rows
     * @param  array<int, string>|null  $headers
     */
    private function spreadsheet(array $rows, ?array $headers = null): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'offline-test-');
        unlink($path);
        $path .= '.zip';
        file_put_contents($path, app(OfflineSalesTemplate::class)->contents());
        try {
            $archive = new PharData($path);
            $xml = '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
            foreach ([$headers ?? PrepareOfflineSalesAction::HEADERS, ...$rows] as $index => $row) {
                $number = $index + 1;
                $xml .= '<row r="'.$number.'">';
                foreach ($row as $column => $value) {
                    $reference = chr(65 + $column).$number;
                    if (is_array($value)) {
                        $xml .= '<c r="'.$reference.'"><f>'.$value['formula'].'</f><v>'.$value['value'].'</v></c>';

                        continue;
                    }
                    $xml .= is_int($value)
                        ? '<c r="'.$reference.'"><v>'.$value.'</v></c>'
                        : '<c r="'.$reference.'" t="inlineStr"><is><t>'.htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8').'</t></is></c>';
                }
                $xml .= '</row>';
            }
            $archive['xl/worksheets/sheet1.xml'] = $xml.'</sheetData></worksheet>';
            unset($archive);

            return UploadedFile::fake()->createWithContent('offline.xlsx', file_get_contents($path));
        } finally {
            unset($archive);
            @unlink($path);
        }
    }
}
