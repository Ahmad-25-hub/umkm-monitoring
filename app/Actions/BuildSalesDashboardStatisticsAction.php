<?php

namespace App\Actions;

use App\Models\Business;
use App\Models\SalesOrder;
use Carbon\CarbonImmutable;

class BuildSalesDashboardStatisticsAction
{
    /**
     * @return array{
     *     date_label: string,
     *     today: array{revenue: int, transactions: int, units: int, average_order: int},
     *     metrics: array<int, array<string, mixed>>,
     *     sales: array{defaultPeriod: string, periods: array<string, array<string, mixed>>},
     *     last_import: array{at: mixed, file_name: string|null, label: string}
     * }
     */
    public function execute(Business $business): array
    {
        $today = CarbonImmutable::now(ImportSalesOrdersAction::SALES_TIMEZONE)->startOfDay();
        $historyStart = $today->startOfMonth()->subMonths(23);
        $dailyStatistics = SalesOrder::query()
            ->whereBelongsTo($business)
            ->where('is_cancelled', false)
            ->whereBetween('ordered_on', [
                $historyStart->format('Y-m-d 00:00:00'),
                $today->format('Y-m-d 23:59:59'),
            ])
            ->selectRaw('ordered_on, SUM(net_sales_amount) as revenue, COUNT(*) as transactions, SUM(quantity) as units')
            ->groupBy('ordered_on')
            ->orderBy('ordered_on')
            ->get()
            ->mapWithKeys(fn (SalesOrder $order): array => [
                $order->ordered_on->toDateString() => [
                    'revenue' => (int) $order->getAttribute('revenue'),
                    'transactions' => (int) $order->getAttribute('transactions'),
                    'units' => (int) $order->getAttribute('units'),
                ],
            ])
            ->all();

        $todayStatistics = $this->statisticsForRange($dailyStatistics, $today, $today);
        $yesterdayStatistics = $this->statisticsForRange(
            $dailyStatistics,
            $today->subDay(),
            $today->subDay(),
        );
        $lastSevenDates = $this->dateRange($today->subDays(6), $today);
        $lastImport = SalesOrder::query()
            ->whereBelongsTo($business)
            ->latest('last_imported_at')
            ->first(['source_file_name', 'last_imported_at']);

        $periods = $dailyStatistics === []
            ? []
            : [
                '7d' => $this->dailyPeriod($dailyStatistics, $today, 7, '7 Hari'),
                '30d' => $this->dailyPeriod($dailyStatistics, $today, 30, '30 Hari'),
                '3m' => $this->monthlyPeriod($dailyStatistics, $today, 3, '3 Bulan'),
                '1y' => $this->monthlyPeriod($dailyStatistics, $today, 12, '1 Tahun'),
            ];

        return [
            'date_label' => ucfirst($today->locale('id')->translatedFormat('l, d F Y')),
            'today' => $todayStatistics,
            'metrics' => [
                $this->metric(
                    'Nilai Penjualan',
                    $this->formatRupiah($todayStatistics['revenue']),
                    $todayStatistics['revenue'],
                    $yesterdayStatistics['revenue'],
                    'banknote',
                    array_map(fn (string $date): int => $dailyStatistics[$date]['revenue'] ?? 0, $lastSevenDates),
                ),
                $this->metric(
                    'Transaksi',
                    number_format($todayStatistics['transactions'], 0, ',', '.'),
                    $todayStatistics['transactions'],
                    $yesterdayStatistics['transactions'],
                    'receipt-text',
                    array_map(fn (string $date): int => $dailyStatistics[$date]['transactions'] ?? 0, $lastSevenDates),
                ),
                $this->metric(
                    'Produk Terjual',
                    number_format($todayStatistics['units'], 0, ',', '.'),
                    $todayStatistics['units'],
                    $yesterdayStatistics['units'],
                    'shopping-basket',
                    array_map(fn (string $date): int => $dailyStatistics[$date]['units'] ?? 0, $lastSevenDates),
                ),
                $this->metric(
                    'Rata-rata Pesanan',
                    $this->formatRupiah($todayStatistics['average_order']),
                    $todayStatistics['average_order'],
                    $yesterdayStatistics['average_order'],
                    'chart-no-axes-combined',
                    array_map(function (string $date) use ($dailyStatistics): int {
                        $statistics = $dailyStatistics[$date] ?? ['revenue' => 0, 'transactions' => 0];

                        return $statistics['transactions'] > 0
                            ? (int) round($statistics['revenue'] / $statistics['transactions'])
                            : 0;
                    }, $lastSevenDates),
                ),
            ],
            'sales' => [
                'defaultPeriod' => '7d',
                'periods' => $periods,
            ],
            'last_import' => [
                'at' => $lastImport?->last_imported_at,
                'file_name' => $lastImport?->source_file_name,
                'label' => $lastImport === null
                    ? 'Belum ada file penjualan'
                    : 'Diperbarui '.$lastImport->last_imported_at
                        ->locale('id')
                        ->diffForHumans(),
            ],
        ];
    }

    /**
     * @param  array<string, array{revenue: int, transactions: int, units: int}>  $dailyStatistics
     * @return array<string, mixed>
     */
    private function dailyPeriod(array $dailyStatistics, CarbonImmutable $today, int $days, string $label): array
    {
        $start = $today->subDays($days - 1);
        $dates = $this->dateRange($start, $today);
        $current = $this->statisticsForRange($dailyStatistics, $start, $today);
        $previous = $this->statisticsForRange(
            $dailyStatistics,
            $start->subDays($days),
            $start->subDay(),
        );

        return [
            'label' => $label,
            'labels' => array_map(
                fn (string $date): string => CarbonImmutable::parse($date)
                    ->locale('id')
                    ->translatedFormat('d M'),
                $dates,
            ),
            'revenue' => array_map(
                fn (string $date): float => round(($dailyStatistics[$date]['revenue'] ?? 0) / 1_000_000, 3),
                $dates,
            ),
            'transactions' => array_map(
                fn (string $date): int => $dailyStatistics[$date]['transactions'] ?? 0,
                $dates,
            ),
            'revenueTotal' => $this->formatRupiah($current['revenue']),
            'revenueChange' => $this->changeLabel($current['revenue'], $previous['revenue']),
            'transactionTotal' => number_format($current['transactions'], 0, ',', '.'),
            'transactionChange' => $this->changeLabel($current['transactions'], $previous['transactions']),
        ];
    }

    /**
     * @param  array<string, array{revenue: int, transactions: int, units: int}>  $dailyStatistics
     * @return array<string, mixed>
     */
    private function monthlyPeriod(array $dailyStatistics, CarbonImmutable $today, int $months, string $label): array
    {
        $currentStart = $today->startOfMonth()->subMonths($months - 1);
        $monthStarts = [];

        for ($index = 0; $index < $months; $index++) {
            $monthStarts[] = $currentStart->addMonths($index);
        }

        $current = $this->statisticsForRange($dailyStatistics, $currentStart, $today);
        $previousStart = $currentStart->subMonths($months);
        $previous = $this->statisticsForRange($dailyStatistics, $previousStart, $currentStart->subDay());

        return [
            'label' => $label,
            'labels' => array_map(
                fn (CarbonImmutable $month): string => $month->locale('id')->translatedFormat('M Y'),
                $monthStarts,
            ),
            'revenue' => array_map(function (CarbonImmutable $month) use ($dailyStatistics, $today): float {
                $statistics = $this->statisticsForRange(
                    $dailyStatistics,
                    $month,
                    $month->isSameMonth($today) ? $today : $month->endOfMonth(),
                );

                return round($statistics['revenue'] / 1_000_000, 3);
            }, $monthStarts),
            'transactions' => array_map(function (CarbonImmutable $month) use ($dailyStatistics, $today): int {
                return $this->statisticsForRange(
                    $dailyStatistics,
                    $month,
                    $month->isSameMonth($today) ? $today : $month->endOfMonth(),
                )['transactions'];
            }, $monthStarts),
            'revenueTotal' => $this->formatRupiah($current['revenue']),
            'revenueChange' => $this->changeLabel($current['revenue'], $previous['revenue']),
            'transactionTotal' => number_format($current['transactions'], 0, ',', '.'),
            'transactionChange' => $this->changeLabel($current['transactions'], $previous['transactions']),
        ];
    }

    /**
     * @param  array<string, array{revenue: int, transactions: int, units: int}>  $dailyStatistics
     * @return array{revenue: int, transactions: int, units: int, average_order: int}
     */
    private function statisticsForRange(
        array $dailyStatistics,
        CarbonImmutable $start,
        CarbonImmutable $end,
    ): array {
        $statistics = ['revenue' => 0, 'transactions' => 0, 'units' => 0];

        foreach ($dailyStatistics as $date => $daily) {
            if ($date < $start->toDateString() || $date > $end->toDateString()) {
                continue;
            }

            $statistics['revenue'] += $daily['revenue'];
            $statistics['transactions'] += $daily['transactions'];
            $statistics['units'] += $daily['units'];
        }

        return [
            ...$statistics,
            'average_order' => $statistics['transactions'] > 0
                ? (int) round($statistics['revenue'] / $statistics['transactions'])
                : 0,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function dateRange(CarbonImmutable $start, CarbonImmutable $end): array
    {
        $dates = [];

        for ($date = $start; $date->lte($end); $date = $date->addDay()) {
            $dates[] = $date->toDateString();
        }

        return $dates;
    }

    /**
     * @param  array<int, int>  $trend
     * @return array<string, mixed>
     */
    private function metric(
        string $label,
        string $value,
        int $current,
        int $previous,
        string $icon,
        array $trend,
    ): array {
        return [
            'label' => $label,
            'value' => $value,
            'change' => $this->changeLabel($current, $previous),
            'context' => 'dibanding kemarin',
            'icon' => $icon,
            'tone' => $current > $previous ? 'positive' : ($current < $previous ? 'negative' : 'neutral'),
            'trend' => $trend,
        ];
    }

    private function changeLabel(int $current, int $previous): string
    {
        if ($previous === 0) {
            return $current === 0 ? '0%' : 'Data baru';
        }

        $change = (($current - $previous) / $previous) * 100;
        $prefix = $change > 0 ? '+' : '';

        return $prefix.number_format($change, 1, ',', '.').'%';
    }

    private function formatRupiah(int $amount): string
    {
        return 'Rp'.number_format($amount, 0, ',', '.');
    }
}
