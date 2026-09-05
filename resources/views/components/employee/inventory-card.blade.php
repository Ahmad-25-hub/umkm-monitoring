@props(['business', 'salesStatistics' => null, 'class' => ''])

@php
    $totalUnitsSold = $salesStatistics['today']['units'] ?? 0;
    $hasSalesData = ($salesStatistics['last_import']['at'] ?? null) !== null;
@endphp

<article {{ $attributes->merge(['class' => 'panel overflow-hidden p-5 sm:p-6 '.$class]) }}>
    <div class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-center">
        <div>
            <div class="flex items-center gap-3">
                <span class="inline-grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700">
                    <i data-lucide="package" class="h-5 w-5" aria-hidden="true"></i>
                </span>
                <div>
                    <h3 class="text-base font-semibold text-ink">Ringkasan Persediaan & Stok</h3>
                    <p class="mt-0.5 text-xs text-ink-muted">Pantau status barang dan pergerakan persediaan di <strong class="font-medium text-ink">{{ $business->name }}</strong></p>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-3">
                <div class="rounded-xl border border-line-soft bg-canvas p-4">
                    <div class="flex items-center justify-between text-ink-muted">
                        <span class="text-xs font-medium">Barang Keluar Hari Ini</span>
                        <i data-lucide="shopping-basket" class="h-4 w-4" aria-hidden="true"></i>
                    </div>
                    <p class="mt-2 text-2xl font-semibold text-ink">{{ number_format($totalUnitsSold, 0, ',', '.') }}</p>
                    <p class="mt-1 text-[0.68rem] text-ink-faint">Tercatat dari transaksi penjualan</p>
                </div>

                <div class="rounded-xl border border-line-soft bg-canvas p-4">
                    <div class="flex items-center justify-between text-ink-muted">
                        <span class="text-xs font-medium">Status Pengisian Stok</span>
                        <i data-lucide="boxes" class="h-4 w-4 text-emerald-600" aria-hidden="true"></i>
                    </div>
                    <div class="mt-2 flex items-center gap-2">
                        <span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>
                        <p class="text-base font-semibold text-ink">Terhubung</p>
                    </div>
                    <p class="mt-1 text-[0.68rem] text-ink-faint">Terintegrasi file TikTok Seller & Shopee</p>
                </div>

                <div class="rounded-xl border border-line-soft bg-canvas p-4">
                    <div class="flex items-center justify-between text-ink-muted">
                        <span class="text-xs font-medium">Monitoring Persediaan</span>
                        <i data-lucide="shield-alert" class="h-4 w-4 text-brand-600" aria-hidden="true"></i>
                    </div>
                    <p class="mt-2 text-base font-semibold text-ink">Aman & Terkendali</p>
                    <p class="mt-1 text-[0.68rem] text-ink-faint">Tidak ada peringatan stok kritis</p>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
            <div class="flex items-center gap-2.5">
                <i data-lucide="sparkles" class="h-4 w-4 text-brand-700" aria-hidden="true"></i>
                <span class="text-xs font-semibold uppercase tracking-[0.1em] text-brand-700">Integrasi Inventory</span>
            </div>
            <p class="mt-3 text-sm font-semibold text-brand-900">Pencatatan Stok Otomatis</p>
            <p class="mt-1.5 text-xs leading-5 text-brand-800/80">Setiap pesanan yang diunggah karyawan akan memperbarui perhitungan jumlah unit keluar secara real-time.</p>
            <div class="mt-4 border-t border-brand-200/60 pt-3">
                <span class="inline-flex items-center gap-1.5 text-[0.68rem] font-medium text-brand-700">
                    <i data-lucide="check-circle-2" class="h-3.5 w-3.5" aria-hidden="true"></i>
                    Siap digunakan karyawan
                </span>
            </div>
        </div>
    </div>
</article>
