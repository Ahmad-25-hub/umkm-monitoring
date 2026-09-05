@props(['owner', 'overview', 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel overview-hero relative overflow-hidden '.$class]) }}>
    <div class="overview-hero-glow" aria-hidden="true"></div>
    <div class="relative grid min-h-[22rem] items-center gap-10 p-6 sm:p-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(18rem,0.55fr)] lg:p-10">
        <div>
            <p class="hero-greeting">Selamat datang, {{ $owner['name'] }} <span aria-hidden="true">👋</span></p>
            <h1 id="overview-title" class="hero-title">{{ $overview['headline'] }}</h1>
            <p class="hero-summary">{{ $overview['summary'] }}</p>

            <div class="mt-8 flex flex-wrap gap-3">
                @foreach ($overview['signals'] as $signal)
                    <div class="hero-signal">
                        <span class="hero-signal-icon"><i data-lucide="{{ $signal['icon'] }}" aria-hidden="true"></i></span>
                        <div>
                            <span class="block text-[0.66rem] font-medium uppercase tracking-[0.09em] text-ink-faint">{{ $signal['label'] }}</span>
                            <strong>{{ $signal['value'] }}</strong>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="health-summary-card">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="section-kicker text-brand-200">Sinkronisasi Data</p>
                    <p class="mt-1.5 text-sm font-medium text-white">Status data penjualan</p>
                </div>
                <i data-lucide="activity" class="h-5 w-5 text-brand-200" aria-hidden="true"></i>
            </div>

            <div class="mt-7 flex items-center gap-6">
                <div class="grid h-[7.6rem] w-[7.6rem] shrink-0 place-items-center rounded-full border border-white/10 bg-white/5 text-center shadow-inner" role="img" aria-label="{{ $overview['health']['value'] }} {{ $overview['health']['valueLabel'] }}">
                    <div>
                        <strong class="block text-3xl font-medium tracking-[-0.045em] text-white">{{ $overview['health']['value'] }}</strong>
                        <span class="mt-1 block max-w-20 text-[0.62rem] leading-4 text-white/45">{{ $overview['health']['valueLabel'] }}</span>
                    </div>
                </div>
                <div class="min-w-0">
                    <span class="health-status"><span></span>{{ $overview['health']['status'] }}</span>
                    <p class="mt-3 text-xs leading-5 text-white/52">{{ $overview['health']['change'] }}</p>
                    <p class="mt-1 text-[0.66rem] text-white/32">Dihitung dari file yang sudah diproses</p>
                </div>
            </div>
        </div>
    </div>
</article>
