<?php

namespace Tests\Feature;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_owner_overview_displays_the_primary_business_signals(): void
    {
        $this->actingAsOwner();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSeeText('Bisnis Anda tumbuh dengan baik hari ini.')
            ->assertSeeText('Business Health')
            ->assertSeeText('Rp24,8 jt')
            ->assertSeeText('Sales Performance')
            ->assertSeeText('NADI Insights')
            ->assertSeeText('Team Performance')
            ->assertSeeText('Needs Your Attention')
            ->assertSeeText('Ask NADI');
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
            ->assertSeeText('Tidak ada masalah yang membutuhkan perhatian')
            ->assertSeeText('Belum ada insight bisnis')
            ->assertSeeText('Belum ada data performa karyawan')
            ->assertSeeText('Belum ada riwayat penjualan');
    }

    private function actingAsOwner(): void
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create();
        BusinessMembership::factory()
            ->for($owner)
            ->for($business)
            ->create();

        $this->actingAs($owner);
    }
}
