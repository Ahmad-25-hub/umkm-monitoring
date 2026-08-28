@props(['sales', 'loading' => false, 'class' => ''])

@php
    $hasSalesData = count($sales['periods']);
    $default = $hasSalesData ? $sales['periods'][$sales['defaultPeriod']] : null;
@endphp

<article
    {{ $attributes->merge(['class' => 'panel sales-panel p-6 sm:p-7 '.$class]) }}
    @if ($hasSalesData && ! $loading)
        data-sales-chart
        data-periods='@json($sales['periods'])'
        data-default-period="{{ $sales['defaultPeriod'] }}"
    @endif
>
    @if ($loading)
        <x-dashboard.loading-state variant="chart" />
    @else
        <div class="sales-header">
            <div>
                <p class="section-kicker">Prioritas #6 · Detailed analytics</p>
                <h2 class="panel-title mt-1.5">Sales Performance</h2>
                <p class="mt-2 text-xs leading-5 text-ink-muted">Gunakan grafik ketika Anda ingin memahami pola di balik ringkasan utama.</p>
            </div>

            @if ($hasSalesData)
                <div class="period-tabs" role="group" aria-label="Pilih periode grafik">
                    @foreach ($sales['periods'] as $key => $period)
                        <button
                            type="button"
                            data-chart-period="{{ $key }}"
                            @class(['is-active' => $key === $sales['defaultPeriod']])
                        >
                            {{ $period['label'] }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        @if ($hasSalesData)
            <div class="sales-summary">
                <div class="sales-stat">
                    <span class="legend-dot legend-dot-revenue"></span>
                    <div>
                        <p>Pendapatan</p>
                        <div><strong data-revenue-total>{{ $default['revenueTotal'] }}</strong><span data-revenue-change>{{ $default['revenueChange'] }}</span></div>
                    </div>
                </div>
                <div class="sales-stat">
                    <span class="legend-dot legend-dot-transactions"></span>
                    <div>
                        <p>Transaksi</p>
                        <div><strong data-transaction-total>{{ $default['transactionTotal'] }}</strong><span data-transaction-change>{{ $default['transactionChange'] }}</span></div>
                    </div>
                </div>
                <p class="sales-comparison"><i data-lucide="arrow-up-right" aria-hidden="true"></i> dibanding periode sebelumnya</p>
            </div>

            <button type="button" class="chart-mobile-toggle" data-chart-toggle aria-expanded="false">
                <span>Lihat grafik lengkap</span>
                <i data-lucide="chevron-down" aria-hidden="true"></i>
            </button>

            <div class="sales-canvas-wrap" data-chart-region>
                <canvas data-sales-canvas aria-label="Grafik pendapatan dan transaksi"></canvas>
                <div class="sales-tooltip" data-chart-tooltip hidden>
                    <strong data-tooltip-label></strong>
                    <span><i class="legend-dot legend-dot-revenue"></i><span data-tooltip-revenue></span></span>
                    <span><i class="legend-dot legend-dot-transactions"></i><span data-tooltip-transactions></span></span>
                </div>
            </div>
        @else
            <x-dashboard.empty-state
                icon="chart-no-axes-combined"
                title="Belum ada riwayat penjualan"
                description="Hubungkan POS atau unggah data transaksi untuk melihat tren pendapatan dari waktu ke waktu."
                action="Hubungkan data penjualan"
            />
        @endif
    @endif
</article>
