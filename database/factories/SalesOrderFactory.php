<?php

namespace Database\Factories;

use App\Models\Business;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'business_id' => Business::factory(),
            'imported_by_user_id' => User::factory(),
            'platform' => 'tiktok',
            'platform_order_id' => fake()->unique()->numerify('##################'),
            'status' => 'Perlu dikirim',
            'substatus' => 'Menunggu pengambilan',
            'quantity' => 1,
            'item_subtotal_amount' => 50_000,
            'order_amount' => 50_000,
            'refund_amount' => 0,
            'net_sales_amount' => 50_000,
            'ordered_on' => today(),
            'ordered_at' => now(),
            'paid_at' => now(),
            'cancelled_at' => null,
            'is_cancelled' => false,
            'purchase_channel' => 'TikTok',
            'order_channel' => 'Product cards',
            'items' => [[
                'product_name' => fake()->words(3, true),
                'variation' => 'Default',
                'quantity' => 1,
                'subtotal' => 50_000,
            ]],
            'source_file_name' => 'penjualan-tiktok.csv',
            'last_imported_at' => now(),
        ];
    }
}
