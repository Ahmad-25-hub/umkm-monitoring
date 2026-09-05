@props(['alerts', 'loading' => false, 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel attention-panel p-6 sm:p-7 '.$class]) }}>
    @if ($loading)
        <x-dashboard.loading-state variant="insights" />
    @elseif (! count($alerts))
        <div class="flex items-start gap-4">
            <span class="panel-icon shrink-0"><i data-lucide="circle-check" aria-hidden="true"></i></span>
            <div><h2 class="text-sm font-semibold text-ink">Perlu perhatian</h2><p class="mt-1 text-sm text-ink-muted">Tidak ada pengingat dari data saat ini</p></div>
        </div>
    @else
        <div class="panel-heading-row">
            <div>
                <p class="section-kicker">Langkah berikutnya</p>
                <h2 class="panel-title mt-1.5">Perlu perhatian</h2>
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
        @endif
    @endif
</article>
