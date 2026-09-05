@props(['business', 'salesStatistics' => null, 'class' => ''])

@php
    $hasSalesData = ($salesStatistics['last_import']['at'] ?? null) !== null;
    $totalUnitsSold = $salesStatistics['today']['units'] ?? 0;
@endphp

<article {{ $attributes->merge(['class' => 'panel grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_minmax(0,1fr)] '.$class]) }}>
    <div class="flex items-start gap-4">
        <span class="panel-icon shrink-0"><i data-lucide="shopping-basket" aria-hidden="true"></i></span>
        <div>
            <h3 class="text-sm font-semibold text-ink">Produk terjual hari ini</h3>
            <p class="mt-3 text-3xl font-semibold tracking-tight text-ink">{{ $hasSalesData ? number_format($totalUnitsSold, 0, ',', '.') : '—' }} <span class="text-sm font-normal text-ink-muted">{{ $hasSalesData ? 'unit' : '' }}</span></p>
            <p class="mt-2 text-xs leading-5 text-ink-muted">{{ $hasSalesData ? 'Tercatat dari pesanan yang diunggah untuk '.$business->name.'.' : 'Belum ada file penjualan yang diunggah.' }}</p>
        </div>
    </div>
    <div class="rounded-xl border border-line-soft bg-canvas p-4">
        <p class="flex items-center gap-2 text-sm font-semibold text-ink"><i data-lucide="package" class="h-4 w-4 text-ink-muted" aria-hidden="true"></i>Data stok belum tersedia</p>
        <p class="mt-2 text-sm leading-6 text-ink-muted">Jumlah produk terjual belum menunjukkan sisa persediaan. Periksa stok fisik atau catatan persediaan usaha untuk memastikan ketersediaan barang.</p>
    </div>
</article>
