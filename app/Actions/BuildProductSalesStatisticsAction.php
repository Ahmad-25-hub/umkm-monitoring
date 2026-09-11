<?php

namespace App\Actions;

use Illuminate\Database\Eloquent\Builder;

class BuildProductSalesStatisticsAction
{
    /**
     * Item subtotals are not order net sales: refunds are only recorded per order.
     *
     * @return array{products: array<string, array{name: string, variation: string, units: int, subtotal: int, orders: int}>, missing_items: int}
     */
    public function execute(Builder $query, string $groupBy = 'product', string $search = ''): array
    {
        $products = [];
        $missingItems = 0;

        foreach ((clone $query)->reorder()->select(['id', 'items'])->lazyById(500) as $order) {
            $seenProducts = [];

            if (! is_array($order->items) || $order->items === []) {
                $missingItems++;

                continue;
            }

            foreach ($order->items as $item) {
                if (! is_array($item) || ! is_string($item['product_name'] ?? null)
                    || trim($item['product_name']) === ''
                    || ! is_numeric($item['quantity'] ?? null) || ! is_numeric($item['subtotal'] ?? null)
                    || $item['quantity'] < 0 || $item['subtotal'] < 0) {
                    $missingItems++;

                    continue;
                }

                $name = trim($item['product_name']);
                $variation = $groupBy === 'variant' && is_string($item['variation'] ?? null) ? trim($item['variation']) : '';

                if ($search !== '' && ! str_contains(mb_strtolower($name.' '.$variation), mb_strtolower($search))) {
                    continue;
                }

                $key = json_encode([$name, $variation], JSON_THROW_ON_ERROR);
                $products[$key] ??= ['name' => $name, 'variation' => $variation, 'units' => 0, 'subtotal' => 0, 'orders' => 0];
                $products[$key]['units'] += (int) $item['quantity'];
                $products[$key]['subtotal'] += (int) $item['subtotal'];
                $products[$key]['orders'] += isset($seenProducts[$key]) ? 0 : 1;
                $seenProducts[$key] = true;
            }
        }

        return ['products' => $products, 'missing_items' => $missingItems];
    }
}
