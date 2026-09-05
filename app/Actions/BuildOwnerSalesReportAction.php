<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\SalesOrder;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Number;

class BuildOwnerSalesReportAction
{
    public const PERIODS = [
        '7d' => ['label' => '7 hari', 'days' => 7],
        '30d' => ['label' => '30 hari', 'days' => 30],
        '90d' => ['label' => '90 hari', 'days' => 90],
    ];

    public const STATUSES = [
        'valid' => 'Tidak dibatalkan',
        'all' => 'Semua status',
        'cancelled' => 'Dibatalkan',
        'refunded' => 'Memiliki refund',
    ];

    /**
     * @param  Collection<int, Business>  $availableBusinesses
     * @return array<string, mixed>
     */
    public function execute(
        Business $business,
        Collection $availableBusinesses,
        string $period,
        ?string $channel,
        string $status,
    ): array {
        $today = CarbonImmutable::now(ImportSalesOrdersAction::SALES_TIMEZONE)->startOfDay();
        $days = self::PERIODS[$period]['days'];
        $start = $today->subDays($days - 1);
        $previousStart = $start->subDays($days);
        $previousEnd = $start->subDay();

        $currentQuery = $this->filteredOrdersQuery($business, $start, $today, $channel, $status);
        $previousQuery = $this->filteredOrdersQuery($business, $previousStart, $previousEnd, $channel, $status);
        $currentStatistics = $this->statistics(clone $currentQuery);
        $previousStatistics = $this->statistics($previousQuery);
        $dailyStatistics = (clone $currentQuery)
            ->toBase()
            ->selectRaw('ordered_on, SUM(net_sales_amount) as revenue, COUNT(*) as transactions, SUM(quantity) as units')
            ->groupBy('ordered_on')
            ->orderBy('ordered_on')
            ->get()
            ->mapWithKeys(fn (object $order): array => [
                substr((string) $order->ordered_on, 0, 10) => [
                    'revenue' => (int) $order->revenue,
                    'transactions' => (int) $order->transactions,
                    'units' => (int) $order->units,
                ],
            ]);
        $dates = $this->dateRange($start, $today);
        $periodContext = 'dibanding '.$days.' hari sebelumnya';
        $channels = SalesOrder::query()
            ->whereBelongsTo($business)
            ->whereNotNull('purchase_channel')
            ->where('purchase_channel', '!=', '')
            ->distinct()
            ->orderBy('purchase_channel')
            ->pluck('purchase_channel')
            ->all();

        return [
            'filters' => [
                'period' => $period,
                'channel' => $channel,
                'status' => $status,
                'periodOptions' => self::PERIODS,
                'channelOptions' => $channels,
                'statusOptions' => self::STATUSES,
            ],
            'dateLabel' => $this->dateLabel($start, $today),
            'lastUpdatedLabel' => $this->lastUpdatedLabel($business),
            'metrics' => [
                $this->metric('Omzet Bersih', $this->formatRupiah($currentStatistics['revenue']), $currentStatistics['revenue'], $previousStatistics['revenue'], 'banknote', $dailyStatistics, 'revenue', $periodContext),
                $this->metric('Jumlah Pesanan', Number::format($currentStatistics['transactions'], locale: 'id'), $currentStatistics['transactions'], $previousStatistics['transactions'], 'receipt-text', $dailyStatistics, 'transactions', $periodContext),
                $this->metric('Produk Terjual', Number::format($currentStatistics['units'], locale: 'id'), $currentStatistics['units'], $previousStatistics['units'], 'shopping-basket', $dailyStatistics, 'units', $periodContext),
                $this->metric('Rata-rata Pesanan', $this->formatRupiah($currentStatistics['averageOrder']), $currentStatistics['averageOrder'], $previousStatistics['averageOrder'], 'chart-no-axes-combined', $dailyStatistics, 'averageOrder', $periodContext),
            ],
            'sales' => [
                'defaultPeriod' => 'selected',
                'periods' => $dailyStatistics->isEmpty() ? [] : [
                    'selected' => [
                        'label' => self::PERIODS[$period]['label'],
                        'labels' => array_map(
                            fn (string $date): string => CarbonImmutable::parse($date)->locale('id')->translatedFormat('d M'),
                            $dates,
                        ),
                        'revenue' => array_map(
                            fn (string $date): float => round(($dailyStatistics->get($date)['revenue'] ?? 0) / 1_000_000, 3),
                            $dates,
                        ),
                        'transactions' => array_map(
                            fn (string $date): int => $dailyStatistics->get($date)['transactions'] ?? 0,
                            $dates,
                        ),
                        'revenueTotal' => $this->formatRupiah($currentStatistics['revenue']),
                        'revenueChange' => $this->changeLabel($currentStatistics['revenue'], $previousStatistics['revenue']),
                        'transactionTotal' => Number::format($currentStatistics['transactions'], locale: 'id'),
                        'transactionChange' => $this->changeLabel($currentStatistics['transactions'], $previousStatistics['transactions']),
                    ],
                ],
            ],
            'channelPerformance' => $this->channelPerformance(clone $currentQuery),
            'topProducts' => $this->topProducts(clone $currentQuery),
            'businessPerformance' => $this->businessPerformance(
                $availableBusinesses,
                $start,
                $today,
                $channel,
                $status,
            ),
            'recentOrders' => (clone $currentQuery)
                ->orderByDesc('ordered_at')
                ->orderByDesc('id')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (SalesOrder $order): array => $this->presentOrder($order)),
        ];
    }

    private function filteredOrdersQuery(
        Business $business,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?string $channel,
        string $status,
    ): Builder {
        $query = SalesOrder::query()
            ->whereBelongsTo($business)
            ->whereBetween('ordered_on', [
                $start->format('Y-m-d 00:00:00'),
                $end->format('Y-m-d 23:59:59'),
            ]);

        return $this->applyOptionalFilters($query, $channel, $status);
    }

    private function applyOptionalFilters(Builder $query, ?string $channel, string $status): Builder
    {
        $query->when(
            $channel,
            fn (Builder $query, string $channel): Builder => $query->where('purchase_channel', $channel),
        );

        return match ($status) {
            'valid' => $query->where('is_cancelled', false),
            'cancelled' => $query->where('is_cancelled', true),
            'refunded' => $query->where('refund_amount', '>', 0),
            default => $query,
        };
    }

    /**
     * @return array{revenue: int, transactions: int, units: int, refunds: int, averageOrder: int}
     */
    private function statistics(Builder $query): array
    {
        $statistics = $query
            ->toBase()
            ->selectRaw('COALESCE(SUM(net_sales_amount), 0) as revenue')
            ->selectRaw('COUNT(*) as transactions')
            ->selectRaw('COALESCE(SUM(quantity), 0) as units')
            ->selectRaw('COALESCE(SUM(refund_amount), 0) as refunds')
            ->first();
        $transactions = (int) $statistics->transactions;
        $revenue = (int) $statistics->revenue;

        return [
            'revenue' => $revenue,
            'transactions' => $transactions,
            'units' => (int) $statistics->units,
            'refunds' => (int) $statistics->refunds,
            'averageOrder' => $transactions > 0 ? (int) round($revenue / $transactions) : 0,
        ];
    }

    /**
     * @param  Collection<string, array{revenue: int, transactions: int, units: int}>  $dailyStatistics
     * @return array<string, mixed>
     */
    private function metric(
        string $label,
        string $value,
        int $current,
        int $previous,
        string $icon,
        Collection $dailyStatistics,
        string $trendKey,
        string $context,
    ): array {
        $trend = $dailyStatistics
            ->map(function (array $statistics) use ($trendKey): int {
                if ($trendKey === 'averageOrder') {
                    return $statistics['transactions'] > 0
                        ? (int) round($statistics['revenue'] / $statistics['transactions'])
                        : 0;
                }

                return $statistics[$trendKey];
            })
            ->values()
            ->all();

        return [
            'label' => $label,
            'value' => $value,
            'change' => $this->changeLabel($current, $previous),
            'context' => $context,
            'icon' => $icon,
            'tone' => $current > $previous ? 'positive' : ($current < $previous ? 'negative' : 'neutral'),
            'trend' => $trend === [] ? [0] : $trend,
        ];
    }

    /** @return array<int, array<string, mixed>> */
    private function channelPerformance(Builder $query): array
    {
        $channels = $query
            ->toBase()
            ->selectRaw("COALESCE(NULLIF(purchase_channel, ''), 'Tidak diketahui') as channel")
            ->selectRaw('SUM(net_sales_amount) as revenue, COUNT(*) as transactions')
            ->groupBy('purchase_channel')
            ->orderByDesc('revenue')
            ->limit(5)
            ->get();
        $highestRevenue = max((int) ($channels->first()?->revenue ?? 0), 1);

        return $channels
            ->map(fn (object $channel): array => [
                'name' => $channel->channel,
                'revenue' => $this->formatRupiah((int) $channel->revenue),
                'transactions' => Number::format((int) $channel->transactions, locale: 'id'),
                'percentage' => round(((int) $channel->revenue / $highestRevenue) * 100, 1),
            ])
            ->all();
    }

    /** @return array<int, array<string, mixed>> */
    private function topProducts(Builder $query): array
    {
        $products = [];

        foreach ($query->select('items')->cursor() as $order) {
            foreach ($order->items as $item) {
                $name = trim((string) ($item['product_name'] ?? '')) ?: 'Produk tidak diketahui';
                $products[$name] ??= ['name' => $name, 'units' => 0, 'revenueValue' => 0];
                $products[$name]['units'] += (int) ($item['quantity'] ?? 0);
                $products[$name]['revenueValue'] += (int) ($item['subtotal'] ?? 0);
            }
        }

        usort(
            $products,
            fn (array $left, array $right): int => [$right['revenueValue'], $right['units']] <=> [$left['revenueValue'], $left['units']],
        );

        return collect(array_slice($products, 0, 5))
            ->map(fn (array $product): array => [
                'name' => $product['name'],
                'units' => Number::format($product['units'], locale: 'id'),
                'revenue' => $this->formatRupiah($product['revenueValue']),
            ])
            ->all();
    }

    /**
     * @param  Collection<int, Business>  $businesses
     * @return array<int, array<string, mixed>>
     */
    private function businessPerformance(
        Collection $businesses,
        CarbonImmutable $start,
        CarbonImmutable $end,
        ?string $channel,
        string $status,
    ): array {
        $statistics = $this->applyOptionalFilters(
            SalesOrder::query()
                ->whereIn('business_id', $businesses->pluck('id'))
                ->whereBetween('ordered_on', [
                    $start->format('Y-m-d 00:00:00'),
                    $end->format('Y-m-d 23:59:59'),
                ]),
            $channel,
            $status,
        )
            ->toBase()
            ->selectRaw('business_id, SUM(net_sales_amount) as revenue, COUNT(*) as transactions')
            ->groupBy('business_id')
            ->get()
            ->keyBy('business_id');

        return $businesses
            ->map(function (Business $business) use ($statistics): array {
                $businessStatistics = $statistics->get($business->id);

                return [
                    'name' => $business->name,
                    'revenueValue' => (int) ($businessStatistics?->revenue ?? 0),
                    'revenue' => $this->formatRupiah((int) ($businessStatistics?->revenue ?? 0)),
                    'transactions' => Number::format((int) ($businessStatistics?->transactions ?? 0), locale: 'id'),
                ];
            })
            ->sortByDesc('revenueValue')
            ->values()
            ->map(function (array $business, int $index): array {
                unset($business['revenueValue']);

                return ['rank' => $index + 1, ...$business];
            })
            ->all();
    }

    /** @return array<string, mixed> */
    private function presentOrder(SalesOrder $order): array
    {
        $items = $order->items;
        $primaryProduct = $items[0]['product_name'] ?? 'Produk tidak diketahui';

        if ($order->is_cancelled) {
            $status = ['label' => 'Dibatalkan', 'tone' => 'negative'];
        } elseif ($order->refund_amount > 0) {
            $status = ['label' => 'Refund', 'tone' => 'warning'];
        } else {
            $status = ['label' => $order->status, 'tone' => 'positive'];
        }

        return [
            'id' => $order->platform_order_id,
            'product' => $primaryProduct,
            'additionalProducts' => max(count($items) - 1, 0),
            'channel' => $order->purchase_channel ?: 'Tidak diketahui',
            'orderedAt' => $order->ordered_at
                ->setTimezone(ImportSalesOrdersAction::SALES_TIMEZONE)
                ->locale('id')
                ->translatedFormat('d M Y, H.i'),
            'amount' => $this->formatRupiah($order->net_sales_amount),
            'status' => $status,
        ];
    }

    /** @return array<int, string> */
    private function dateRange(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dates = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    private function dateLabel(CarbonImmutable $start, CarbonImmutable $end): string
    {
        return $start->locale('id')->translatedFormat('d M Y').' – '.$end->locale('id')->translatedFormat('d M Y');
    }

    private function lastUpdatedLabel(Business $business): string
    {
        $lastImportedAt = SalesOrder::query()
            ->whereBelongsTo($business)
            ->max('last_imported_at');

        if ($lastImportedAt === null) {
            return 'Belum ada data penjualan';
        }

        return 'Diperbarui '.CarbonImmutable::parse($lastImportedAt)
            ->locale('id')
            ->diffForHumans();
    }

    private function changeLabel(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current === 0 ? '0%' : 'Data baru';
        }

        $change = (($current - $previous) / $previous) * 100;

        return ($change > 0 ? '+' : '').Number::format($change, 1, locale: 'id').'%';
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.Number::format($amount, locale: 'id');
    }
}
