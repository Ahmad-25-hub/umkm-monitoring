@props(['alerts', 'loading' => false, 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel attention-panel p-6 sm:p-7 '.$class]) }}>
    @if ($loading)
        <x-dashboard.loading-state variant="insights" />
    @else
        <div class="panel-heading-row">
            <div>
                <p class="section-kicker">Prioritas #3 · Action required</p>
                <h2 class="panel-title mt-1.5">Needs Your Attention</h2>
                <p class="mt-2 text-xs text-ink-muted">Mulai dari masalah yang paling berpengaruh pada operasional hari ini.</p>
            </div>
            @if (count($alerts))
                <span class="attention-count">{{ count($alerts) }}</span>
            @endif
        </div>

        @if (count($alerts))
            <div class="alert-primary-list mt-6">
                @foreach (array_slice($alerts, 0, 2) as $alert)
                    <x-dashboard.alert-item :alert="$alert" />
                @endforeach
            </div>

            @if (count($alerts) > 2)
                <details class="disclosure mt-3">
                    <summary class="disclosure-trigger">
                        <span>Lihat {{ count($alerts) - 2 }} perhatian lainnya</span>
                        <i data-lucide="chevron-down" aria-hidden="true"></i>
                    </summary>
                    <div class="alert-primary-list disclosure-content mt-3">
                        @foreach (array_slice($alerts, 2) as $alert)
                            <x-dashboard.alert-item :alert="$alert" />
                        @endforeach
                    </div>
                </details>
            @endif
        @else
            <x-dashboard.empty-state
                icon="shield-check"
                title="Tidak ada masalah yang membutuhkan perhatian"
                description="Semua sinyal bisnis berada dalam batas normal. NADI akan menampilkan prioritas baru di sini."
                compact
            />
        @endif
    @endif
</article>
