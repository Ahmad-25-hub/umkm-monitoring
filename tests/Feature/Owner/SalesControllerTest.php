<?php

namespace Tests\Feature\Owner;

use App\Models\Business;
use App\Models\BusinessMembership;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SalesControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('sales.index'))
            ->assertRedirectToRoute('login');
    }

    public function test_owner_sees_active_business_sales_and_owned_business_ranking(): void
    {
        $this->travelTo('2026-09-04 12:00:00');
        [$owner, $activeBusiness] = $this->createOwner();
        $secondBusiness = Business::factory()->create(['name' => 'Kedai Kedua']);
        BusinessMembership::factory()->for($owner)->for($secondBusiness)->create();
        $otherBusiness = Business::factory()->create(['name' => 'Usaha Orang Lain']);
        BusinessMembership::factory()
            ->for(User::factory()->create())
            ->for($otherBusiness)
            ->create();

        SalesOrder::factory()->for($activeBusiness)->create([
            'platform_order_id' => 'ACTIVE-ORDER',
            'net_sales_amount' => 75_000,
            'order_amount' => 75_000,
            'ordered_on' => '2026-09-04',
            'ordered_at' => '2026-09-04 09:00:00',
            'items' => [[
                'product_name' => 'Kopi Susu NADI',
                'variation' => 'Botol',
                'quantity' => 1,
                'subtotal' => 75_000,
            ]],
        ]);
        SalesOrder::factory()->for($activeBusiness)->create([
            'platform_order_id' => 'DEFAULT-CANCELLED-ORDER',
            'net_sales_amount' => 0,
            'is_cancelled' => true,
            'cancelled_at' => '2026-09-04 10:00:00',
            'ordered_on' => '2026-09-04',
            'ordered_at' => '2026-09-04 10:00:00',
        ]);
        SalesOrder::factory()->for($secondBusiness)->create([
            'platform_order_id' => 'SECOND-ORDER',
            'net_sales_amount' => 25_000,
            'order_amount' => 25_000,
            'ordered_on' => '2026-09-04',
            'ordered_at' => '2026-09-04 08:00:00',
        ]);
        SalesOrder::factory()->for($otherBusiness)->create([
            'platform_order_id' => 'PRIVATE-ORDER',
            'net_sales_amount' => 900_000,
            'order_amount' => 900_000,
            'ordered_on' => '2026-09-04',
            'ordered_at' => '2026-09-04 07:00:00',
        ]);

        $response = $this->actingAs($owner)->get(route('sales.index'));

        $response
            ->assertSeeText('Ringkasan penjualan')
            ->assertSeeText('Perkembangan Penjualan')
            ->assertSeeText('Performa UMKM')
            ->assertSeeText('Kedai Kedua')
            ->assertSeeText('Rp75.000')
            ->assertSeeText('ACTIVE-ORDER')
            ->assertSeeText('Kopi Susu NADI')
            ->assertDontSeeText('DEFAULT-CANCELLED-ORDER')
            ->assertDontSeeText('SECOND-ORDER')
            ->assertDontSeeText('PRIVATE-ORDER')
            ->assertDontSeeText('Usaha Orang Lain')
            ->assertDontSeeText('Rp900.000');
    }

    public function test_owner_can_filter_sales_by_period_channel_and_status(): void
    {
        $this->travelTo('2026-09-04 12:00:00');
        [$owner, $business] = $this->createOwner();
        SalesOrder::factory()->for($business)->create([
            'platform_order_id' => 'MATCHING-ORDER',
            'purchase_channel' => 'TikTok',
            'net_sales_amount' => 40_000,
            'order_amount' => 40_000,
            'ordered_on' => '2026-09-03',
            'ordered_at' => '2026-09-03 10:00:00',
        ]);
        SalesOrder::factory()->for($business)->create([
            'platform_order_id' => 'OTHER-CHANNEL',
            'purchase_channel' => 'Shopee',
            'net_sales_amount' => 80_000,
            'order_amount' => 80_000,
            'ordered_on' => '2026-09-03',
            'ordered_at' => '2026-09-03 11:00:00',
        ]);
        SalesOrder::factory()->for($business)->create([
            'platform_order_id' => 'CANCELLED-ORDER',
            'purchase_channel' => 'TikTok',
            'net_sales_amount' => 0,
            'order_amount' => 60_000,
            'is_cancelled' => true,
            'cancelled_at' => '2026-09-03 12:00:00',
            'ordered_on' => '2026-09-03',
            'ordered_at' => '2026-09-03 09:00:00',
        ]);
        SalesOrder::factory()->for($business)->create([
            'platform_order_id' => 'OUTSIDE-PERIOD',
            'purchase_channel' => 'TikTok',
            'net_sales_amount' => 120_000,
            'order_amount' => 120_000,
            'ordered_on' => '2026-08-20',
            'ordered_at' => '2026-08-20 09:00:00',
        ]);

        $response = $this->actingAs($owner)->get(route('sales.index', [
            'period' => '7d',
            'channel' => 'TikTok',
            'status' => 'valid',
        ]));

        $response
            ->assertSeeText('Rp40.000')
            ->assertSeeText('MATCHING-ORDER')
            ->assertDontSeeText('OTHER-CHANNEL')
            ->assertDontSeeText('CANCELLED-ORDER')
            ->assertDontSeeText('OUTSIDE-PERIOD');
    }

    public function test_invalid_filter_is_rejected(): void
    {
        [$owner] = $this->createOwner();

        $this->actingAs($owner)
            ->from(route('sales.index'))
            ->get(route('sales.index', ['period' => 'selamanya']))
            ->assertRedirect(route('sales.index'))
            ->assertSessionHasErrors('period');
    }

    public function test_product_names_are_escaped(): void
    {
        $this->travelTo('2026-09-04 12:00:00');
        [$owner, $business] = $this->createOwner();
        SalesOrder::factory()->for($business)->create([
            'platform_order_id' => 'ESCAPED-ORDER',
            'ordered_on' => '2026-09-04',
            'ordered_at' => '2026-09-04 09:00:00',
            'items' => [[
                'product_name' => '<script>alert("xss")</script>',
                'variation' => 'Default',
                'quantity' => 1,
                'subtotal' => 50_000,
            ]],
        ]);

        $response = $this->actingAs($owner)->get(route('sales.index'));

        $response
            ->assertSee('&lt;script&gt;', false)
            ->assertDontSee('<script>alert', false);
    }

    /** @return array{User, Business} */
    private function createOwner(): array
    {
        $owner = User::factory()->create();
        $business = Business::factory()->create(['name' => 'Usaha Aktif']);
        BusinessMembership::factory()
            ->for($owner)
            ->for($business)
            ->create();

        return [$owner, $business];
    }
}
