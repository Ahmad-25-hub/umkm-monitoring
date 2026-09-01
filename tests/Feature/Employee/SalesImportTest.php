<?php

namespace Tests\Feature\Employee;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
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
            ->assertSeeText('Unggah dari TikTok Seller')
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

    public function test_import_rejects_non_csv_file(): void
    {
        $employee = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()->employee()->for($employee)->for($business)->create();
        $file = UploadedFile::fake()->createWithContent('penjualan.xlsx', 'bukan csv');

        $this->actingAs($employee)
            ->post(route('employee.sales-imports.store'), ['sales_file' => $file])
            ->assertInvalid(['sales_file' => 'File penjualan harus berformat CSV.']);

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
}
