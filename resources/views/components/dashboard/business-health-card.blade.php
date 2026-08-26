@props(['owner', 'overview', 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel overview-hero relative overflow-hidden '.$class]) }}>
    <div class="overview-hero-glow" aria-hidden="true"></div>
    <div class="relative grid min-h-[22rem] items-center gap-10 p-6 sm:p-8 lg:grid-cols-[minmax(0,1.45fr)_minmax(18rem,0.55fr)] lg:p-10">
        <div>
            <p class="hero-greeting">Good morning, {{ $owner['name'] }} <span aria-hidden="true">👋</span></p>
            <h1 id="overview-title" class="hero-title">{{ $overview['headline'] }}</h1>
            <p class="hero-summary">{{ $overview['summary'] }}</p>

            <div class="mt-8 flex flex-wrap gap-3">
                <div class="hero-signal">
                    <span class="hero-signal-icon"><i data-lucide="trending-up" aria-hidden="true"></i></span>
                    <div>
                        <span class="block text-[0.66rem] font-medium uppercase tracking-[0.09em] text-ink-faint">Momentum</span>
                        <strong>Positif dan stabil</strong>
                    </div>
                </div>
                <div class="hero-signal">
                    <span class="hero-signal-icon"><i data-lucide="shield-check" aria-hidden="true"></i></span>
                    <div>
                        <span class="block text-[0.66rem] font-medium uppercase tracking-[0.09em] text-ink-faint">Prioritas</span>
                        <strong>1 tindakan penting</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="health-summary-card">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <p class="section-kicker text-brand-200">Business Health</p>
                    <p class="mt-1.5 text-sm font-medium text-white">Kondisi bisnis keseluruhan</p>
                </div>
                <i data-lucide="activity" class="h-5 w-5 text-brand-200" aria-hidden="true"></i>
            </div>

            <div class="mt-7 flex items-center gap-6">
                <div
                    class="health-score"
                    style="--score: {{ $overview['health']['score'] }}"
                    role="img"
                    aria-label="Business health score {{ $overview['health']['score'] }} dari 100"
                >
                    <div class="health-score-inner">
                        <strong>{{ $overview['health']['score'] }}</strong>
                        <span>/100</span>
                    </div>
                </div>
                <div class="min-w-0">
                    <span class="health-status"><span></span>{{ $overview['health']['status'] }}</span>
                    <p class="mt-3 text-xs leading-5 text-white/52">{{ $overview['health']['change'] }}</p>
                    <p class="mt-1 text-[0.66rem] text-white/32">Berdasarkan penjualan, tim, dan stok</p>
                </div>
            </div>
        </div>
    </div>
</article>
