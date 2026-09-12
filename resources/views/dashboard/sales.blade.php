<x-dashboard.app-shell
    :owner="$owner"
    :businesses="$businesses"
    :active-business-id="$activeBusinessId"
    title="Penjualan — NADI"
>
    <div class="dashboard-sections">
        @if (session('success'))
            <div class="feedback-success" role="status">{{ session('success') }}</div>
        @endif
        <div class="flex justify-end"><a href="{{ route('sales-entry.create') }}" class="button-primary">+ Tambah penjualan</a></div>
        <section class="panel panel-feature p-6 sm:p-8" aria-labelledby="sales-title">
            <div class="flex flex-col justify-between gap-6 xl:flex-row xl:items-end">
                <div class="max-w-2xl">
                    <div class="eyebrow">
                        <span class="status-dot" aria-hidden="true"></span>
                        {{ $report['dateLabel'] }}
                    </div>
                    <h1 id="sales-title" class="display-heading">Penjualan {{ $activeBusiness->name }}</h1>
                    <p class="mt-3 max-w-xl text-sm leading-6 text-ink-muted">
                        Pantau omzet, pola transaksi, produk, dan kanal penjualan dari satu tampilan.
                    </p>
                </div>

                <form method="GET" action="{{ route('sales.index') }}" class="grid gap-3 sm:grid-cols-3 xl:w-full xl:max-w-xl" aria-label="Filter laporan penjualan">
                    <label class="grid gap-1.5">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-ink-faint">Periode</span>
                        <select name="period" class="h-10 rounded-xl border border-line bg-white px-3 text-xs font-medium text-ink focus:border-brand-200 focus:ring-3 focus:ring-brand-500/15">
                            @foreach ($report['filters']['periodOptions'] as $value => $option)
                                <option value="{{ $value }}" @selected($report['filters']['period'] === $value)>{{ $option['label'] }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-1.5">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-ink-faint">Kanal</span>
                        <select name="channel" class="h-10 rounded-xl border border-line bg-white px-3 text-xs font-medium text-ink focus:border-brand-200 focus:ring-3 focus:ring-brand-500/15">
                            <option value="">Semua kanal</option>
                            @foreach ($report['filters']['channelOptions'] as $channel)
                                <option value="{{ $channel }}" @selected($report['filters']['channel'] === $channel)>{{ $channel }}</option>
                            @endforeach
                        </select>
                    </label>

                    <label class="grid gap-1.5">
                        <span class="text-xs font-semibold uppercase tracking-[0.08em] text-ink-faint">Status</span>
                        <select name="status" class="h-10 rounded-xl border border-line bg-white px-3 text-xs font-medium text-ink focus:border-brand-200 focus:ring-3 focus:ring-brand-500/15">
                            @foreach ($report['filters']['statusOptions'] as $value => $label)
                                <option value="{{ $value }}" @selected($report['filters']['status'] === $value)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </label>

                    <div class="flex items-center justify-end gap-2 sm:col-span-3">
                        @if ($report['filters']['period'] !== '30d' || $report['filters']['channel'] || $report['filters']['status'] !== 'valid')
                            <a href="{{ route('sales.index') }}" class="button-secondary">Reset</a>
                        @endif
                        <button type="submit" class="inline-flex h-10 items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 text-xs font-semibold text-white shadow-sm transition hover:bg-brand-900">
                            <i data-lucide="list-filter" class="h-4 w-4" aria-hidden="true"></i>
                            Terapkan filter
                        </button>
                    </div>
                </form>
            </div>
        </section>

        <section aria-labelledby="sales-summary-heading">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Sinyal utama</p>
                    <h2 id="sales-summary-heading" class="section-heading">Ringkasan penjualan</h2>
                </div>
                <span class="section-meta">{{ $report['lastUpdatedLabel'] }}</span>
            </div>

            <div class="metrics-grid mt-5">
                @foreach ($report['metrics'] as $metric)
                    <x-dashboard.metric-card :metric="$metric" />
                @endforeach
            </div>
        </section>

        <section aria-label="Tren penjualan">
            <x-dashboard.sales-chart
                :sales="$report['sales']"
                kicker="Tren periode terpilih"
                title="Perkembangan Penjualan"
                description="Omzet bersih dan jumlah pesanan berdasarkan filter yang sedang digunakan."
            />
        </section>

        <section class="grid gap-5 xl:grid-cols-2" aria-label="Rincian performa penjualan">
            <article class="panel p-6 sm:p-7">
                <div class="panel-heading-row">
                    <div>
                        <p class="section-kicker">Kontribusi omzet</p>
                        <h2 class="panel-title mt-1.5">Performa Kanal</h2>
                    </div>
                    <span class="panel-icon"><i data-lucide="store" aria-hidden="true"></i></span>
                </div>

                @if (count($report['channelPerformance']))
                    <ol class="mt-6 grid gap-5">
                        @foreach ($report['channelPerformance'] as $channel)
                            <li class="grid gap-2">
                                <div class="flex items-start justify-between gap-4 text-xs">
                                    <div class="min-w-0">
                                        <p class="truncate font-semibold text-ink">{{ $channel['name'] }}</p>
                                        <p class="mt-1 text-xs text-ink-faint">{{ $channel['transactions'] }} pesanan</p>
                                    </div>
                                    <p class="shrink-0 font-semibold text-ink">{{ $channel['revenue'] }}</p>
                                </div>
                                <div class="h-1.5 overflow-hidden rounded-full bg-canvas-strong">
                                    <div class="h-full rounded-full bg-brand-500" style="width: {{ $channel['percentage'] }}%"></div>
                                </div>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <x-dashboard.empty-state
                        icon="store"
                        title="Belum ada data kanal"
                        description="Kanal penjualan akan muncul setelah transaksi berhasil diimpor."
                        compact
                    />
                @endif
            </article>

            <article class="panel p-6 sm:p-7">
                <div class="panel-heading-row">
                    <div>
                        <p class="section-kicker">Berdasarkan nilai produk</p>
                        <h2 class="panel-title mt-1.5">Produk Terlaris</h2>
                    </div>
                    <span class="panel-icon"><i data-lucide="package-search" aria-hidden="true"></i></span>
                </div>

                @if (count($report['topProducts']))
                    <ol class="mt-5 divide-y divide-line-soft">
                        @foreach ($report['topProducts'] as $index => $product)
                            <li class="flex items-center gap-3 py-3.5">
                                <span class="grid h-8 w-8 shrink-0 place-items-center rounded-lg bg-brand-50 text-xs font-semibold text-brand-700">{{ $index + 1 }}</span>
                                <div class="min-w-0 flex-1">
                                    <p class="truncate text-xs font-semibold text-ink">{{ $product['name'] }}</p>
                                    <p class="mt-1 text-xs text-ink-faint">{{ $product['units'] }} produk terjual</p>
                                </div>
                                <p class="shrink-0 text-xs font-semibold text-ink">{{ $product['revenue'] }}</p>
                            </li>
                        @endforeach
                    </ol>
                @else
                    <x-dashboard.empty-state
                        icon="package-search"
                        title="Belum ada produk terlaris"
                        description="Produk dari transaksi yang diimpor akan dirangking di sini."
                        compact
                    />
                @endif
            </article>
        </section>

        <section class="panel overflow-hidden" aria-labelledby="business-performance-heading">
            <div class="flex flex-col gap-2 border-b border-line-soft px-6 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-7">
                <div>
                    <p class="section-kicker">Seluruh usaha milik Anda</p>
                    <h2 id="business-performance-heading" class="panel-title mt-1.5">Performa UMKM</h2>
                </div>
                <p class="text-xs text-ink-faint">Mengikuti periode dan filter aktif</p>
            </div>

            <div class="overflow-x-auto">
                <table class="responsive-table w-full min-w-[36rem] text-left">
                    <thead class="bg-canvas/70 text-xs font-semibold uppercase tracking-[0.08em] text-ink-faint">
                        <tr>
                            <th class="px-6 py-3 sm:px-7">Peringkat</th>
                            <th class="px-4 py-3">Nama UMKM</th>
                            <th class="px-4 py-3 text-right">Pesanan</th>
                            <th class="px-6 py-3 text-right sm:px-7">Omzet Bersih</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line-soft text-xs">
                        @foreach ($report['businessPerformance'] as $business)
                            <tr>
                                <td data-label="Peringkat" class="px-6 py-4 sm:px-7">
                                    <span class="grid h-7 w-7 place-items-center rounded-lg bg-canvas text-xs font-semibold text-ink-muted">{{ $business['rank'] }}</span>
                                </td>
                                <td data-label="Nama UMKM" class="px-4 py-4 font-semibold text-ink">{{ $business['name'] }}</td>
                                <td data-label="Pesanan" class="px-4 py-4 text-right text-ink-muted">{{ $business['transactions'] }}</td>
                                <td data-label="Omzet Bersih" class="px-6 py-4 text-right font-semibold text-ink sm:px-7">{{ $business['revenue'] }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>

        <section class="panel overflow-hidden" aria-labelledby="recent-sales-heading">
            <div class="flex flex-col gap-2 border-b border-line-soft px-6 py-5 sm:flex-row sm:items-end sm:justify-between sm:px-7">
                <div>
                    <p class="section-kicker">Pesanan terbaru</p>
                    <h2 id="recent-sales-heading" class="panel-title mt-1.5">Transaksi Penjualan</h2>
                </div>
                <p class="text-xs text-ink-faint">{{ $report['recentOrders']->total() }} transaksi ditemukan</p>
            </div>

            @if ($report['recentOrders']->count())
                <div class="overflow-x-auto">
                    <table class="responsive-table w-full min-w-[52rem] text-left">
                        <thead class="bg-canvas/70 text-xs font-semibold uppercase tracking-[0.08em] text-ink-faint">
                            <tr>
                                <th class="px-6 py-3 sm:px-7">Pesanan</th>
                                <th class="px-4 py-3">Produk</th>
                                <th class="px-4 py-3">Waktu</th>
                                <th class="px-4 py-3">Kanal</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-6 py-3 text-right sm:px-7">Nilai Bersih</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-line-soft text-xs">
                            @foreach ($report['recentOrders'] as $order)
                                <tr class="transition hover:bg-canvas/55">
                                    <td data-label="Pesanan" class="px-6 py-4 font-medium text-ink-muted sm:px-7">#{{ $order['id'] }}</td>
                                    <td data-label="Produk" class="mobile-wide max-w-64 px-4 py-4">
                                        <p class="truncate font-semibold text-ink">{{ $order['product'] }}</p>
                                        @if ($order['additionalProducts'] > 0)
                                            <p class="mt-1 text-xs text-ink-faint">+{{ $order['additionalProducts'] }} produk lainnya</p>
                                        @endif
                                    </td>
                                    <td data-label="Waktu" class="whitespace-nowrap px-4 py-4 text-ink-muted">{{ $order['orderedAt'] }}</td>
                                    <td data-label="Kanal" class="px-4 py-4 text-ink-muted">{{ $order['channel'] }}</td>
                                    <td data-label="Status" class="px-4 py-4">
                                        <span @class([
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                            'bg-brand-50 text-brand-700' => $order['status']['tone'] === 'positive',
                                            'bg-amber-50 text-warning' => $order['status']['tone'] === 'warning',
                                            'bg-red-50 text-critical' => $order['status']['tone'] === 'negative',
                                        ])>
                                            {{ $order['status']['label'] }}
                                        </span>
                                    </td>
                                    <td data-label="Nilai Bersih" class="whitespace-nowrap px-6 py-4 text-right font-semibold text-ink sm:px-7">{{ $order['amount'] }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                @if ($report['recentOrders']->hasPages())
                    <div class="border-t border-line-soft px-6 py-4 sm:px-7">
                        {{ $report['recentOrders']->links() }}
                    </div>
                @endif
            @else
                <div class="px-6 py-4 sm:px-7">
                    <x-dashboard.empty-state
                        icon="receipt-text"
                        title="Tidak ada transaksi pada filter ini"
                        description="Ubah periode atau filter untuk melihat transaksi lainnya."
                    />
                </div>
            @endif
        </section>
    </div>
</x-dashboard.app-shell>
