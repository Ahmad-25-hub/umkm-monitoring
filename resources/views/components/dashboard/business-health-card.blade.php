@props(['owner', 'overview', 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel overview-hero relative overflow-hidden '.$class]) }}>
    <div class="overview-hero-glow" aria-hidden="true"></div>
    <div class="relative grid gap-6 p-6 sm:p-7 xl:grid-cols-[minmax(0,1fr)_19rem] xl:items-center">
        <div>
            <p class="section-kicker text-brand-700">Selamat datang, {{ $owner['name'] }}</p>
            <h1 id="overview-title" class="mt-3 max-w-3xl text-2xl font-semibold leading-tight tracking-tight text-ink sm:text-3xl">{{ $overview['headline'] }}</h1>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-muted">{{ $overview['summary'] }}</p>
            <a href="{{ route('sales.index') }}" class="text-link mt-4">Lihat laporan penjualan <i data-lucide="arrow-right" aria-hidden="true"></i></a>
        </div>
        <div class="rounded-2xl border border-brand-100 bg-white/80 p-5">
            <div class="flex items-start gap-3">
                <span class="panel-icon shrink-0"><i data-lucide="{{ $overview['health']['hasData'] ? 'file-chart-column' : 'clock-3' }}" aria-hidden="true"></i></span>
                <div class="min-w-0">
                    <p class="text-xs font-medium text-ink-muted">Status data penjualan</p>
                    <p class="mt-1 text-sm font-semibold text-ink">{{ $overview['health']['status'] }}</p>
                    <p class="mt-2 text-xs leading-5 text-ink-muted">{{ $overview['health']['change'] }}</p>
                </div>
            </div>
        </div>
    </div>
</article>
