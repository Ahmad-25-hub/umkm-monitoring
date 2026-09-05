<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_overview_displays_the_primary_business_signals(): void
    {
        $this->travelTo('2026-09-01 12:00:00');
        $business = $this->actingAsOwner();
        SalesOrder::factory()->for($business)->count(2)->sequence(
            ['platform_order_id' => 'overview-order-1', 'order_amount' => 25_000, 'net_sales_amount' => 25_000],
            ['platform_order_id' => 'overview-order-2', 'order_amount' => 50_000, 'net_sales_amount' => 50_000],
        )->create([
            'ordered_on' => '2026-09-01',
            'ordered_at' => '2026-09-01 05:00:00',
        ]);

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeText('Penjualan hari ini sudah tercatat.')
            ->assertSeeText('Status data penjualan')
            ->assertSeeText('Rp75.000')
            ->assertSeeText('Performa penjualan')
            ->assertSeeText('Catatan usaha')
            ->assertSeeText('Anggota tim')
            ->assertSeeText('Perlu perhatian')
            ->assertSeeText('Tanya NADI');
    }

    public function test_owner_overview_exposes_reusable_loading_states(): void
    {
        $this->actingAsOwner();

        $response = $this->get('/?loading=1');

        $response
            ->assertOk()
            ->assertSee('loading-kpi', false)
            ->assertSee('loading-chart', false)
            ->assertSee('loading-employees', false)
            ->assertSee('loading-insights', false)
            ->assertSeeText('Memuat data dashboard');
    }

    public function test_owner_overview_explains_sections_without_data(): void
    {
        $this->actingAsOwner();

        $response = $this->get('/?state=empty');

        $response
            ->assertOk()
            ->assertSeeText('Belum ada ringkasan bisnis')
            ->assertSeeText('Tidak ada pengingat dari data saat ini')
            ->assertSeeText('Belum ada catatan usaha')
            ->assertSeeText('Belum ada karyawan terdaftar')
            ->assertSeeText('Belum ada riwayat penjualan');
    }

    private function actingAsOwner(): Business
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()
            ->for($owner)
            ->for($business)
            ->create();

        $this->actingAs($owner);

        return $business;
    }
}
