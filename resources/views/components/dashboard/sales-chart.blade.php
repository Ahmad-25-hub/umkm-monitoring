@props(['sales', 'class' => ''])

@php($default = $sales['periods'][$sales['defaultPeriod']])

<article
    {{ $attributes->merge(['class' => 'panel sales-panel p-6 sm:p-7 '.$class]) }}
    data-sales-chart
    data-periods='@json($sales['periods'])'
    data-default-period="{{ $sales['defaultPeriod'] }}"
>
    <div class="sales-header">
        <div>
            <p class="section-kicker">Business performance</p>
            <h2 class="panel-title mt-1.5">Sales Performance</h2>
            <p class="mt-2 text-xs leading-5 text-ink-muted">Pantau perkembangan bisnis dan bandingkan dengan periode sebelumnya.</p>
        </div>

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
    </div>

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

    <div class="sales-canvas-wrap">
        <canvas data-sales-canvas aria-label="Grafik pendapatan dan transaksi"></canvas>
    </div>
</article>
