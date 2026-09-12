<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\SalesOrder;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class StoreOfflineSalesAction
{
    /**
     * @param  array<string, array{ordered_on: string, items: array<int, array{product_name: string, quantity: int, unit_price: int}>}>  $orders
     * @return array{new_count: int, skipped_count: int}
     */
    public function execute(Business $business, User $user, array $orders, string $source): array
    {
        return DB::transaction(function () use ($business, $user, $orders, $source): array {
            Business::query()->whereKey($business->id)->lockForUpdate()->firstOrFail();
            $newCount = 0;

            foreach ($orders as $id => $order) {
                $orderedAt = CarbonImmutable::parse($order['ordered_on'], ImportSalesOrdersAction::SALES_TIMEZONE);
                $items = array_map(fn (array $item): array => [
                    'product_name' => $item['product_name'],
                    'quantity' => (int) $item['quantity'],
                    'subtotal' => (int) $item['quantity'] * (int) $item['unit_price'],
                ], $order['items']);
                $total = array_sum(array_column($items, 'subtotal'));
                $record = SalesOrder::query()->firstOrCreate([
                    'business_id' => $business->id,
                    'platform' => 'offline',
                    'platform_order_id' => Str::upper((string) $id),
                ], [
                    'imported_by_user_id' => $user->id,
                    'status' => 'Selesai',
                    'quantity' => array_sum(array_column($items, 'quantity')),
                    'item_subtotal_amount' => $total,
                    'order_amount' => $total,
                    'refund_amount' => 0,
                    'net_sales_amount' => $total,
                    'ordered_on' => $order['ordered_on'],
                    'ordered_at' => $orderedAt->utc(),
                    'paid_at' => $orderedAt->utc(),
                    'is_cancelled' => false,
                    'purchase_channel' => 'Offline',
                    'order_channel' => 'Offline',
                    'items' => $items,
                    'source_file_name' => Str::limit(basename($source), 255, ''),
                    'last_imported_at' => now(),
                ]);
                $newCount += (int) $record->wasRecentlyCreated;
            }

            return ['new_count' => $newCount, 'skipped_count' => count($orders) - $newCount];
        });
    }
}
