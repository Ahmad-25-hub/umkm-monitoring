@props(['insights', 'loading' => false, 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel insights-panel overflow-hidden '.$class]) }}>
    @if ($loading)
        <div class="bg-white p-6 sm:p-7 lg:col-span-2">
            <x-dashboard.loading-state variant="insights" />
        </div>
    @else
        <div class="insights-intro">
            <div>
                <span class="dark-icon"><i data-lucide="sparkles" aria-hidden="true"></i></span>
                <p class="mt-8 text-[0.66rem] font-semibold uppercase tracking-[0.12em] text-brand-200">Data → Insight → Action</p>
                <h2 class="mt-3 text-2xl font-medium tracking-[-0.035em] text-white">NADI Insights</h2>
                <p class="mt-3 max-w-xs text-sm leading-6 text-white/48">Penjelasan singkat tentang apa yang terjadi dan langkah yang dapat Anda ambil.</p>
            </div>
            @if (count($insights))
                <p class="mt-10 text-[0.68rem] font-medium text-white/32">{{ count($insights) }} insight · diperbarui hari ini</p>
            @endif
        </div>

        <div class="insight-list">
            @if (count($insights))
                @foreach (array_slice($insights, 0, 2) as $insight)
                    <x-dashboard.insight-item :insight="$insight" />
                @endforeach

                @if (count($insights) > 2)
                    <details class="disclosure insight-disclosure">
                        <summary class="disclosure-trigger">
                            <span>Lihat {{ count($insights) - 2 }} insight lainnya</span>
                            <i data-lucide="chevron-down" aria-hidden="true"></i>
                        </summary>
                        <div class="disclosure-content insight-extra-grid">
                            @foreach (array_slice($insights, 2) as $insight)
                                <x-dashboard.insight-item :insight="$insight" />
                            @endforeach
                        </div>
                    </details>
                @endif
            @else
                <div class="bg-white p-6 sm:p-8">
                    <x-dashboard.empty-state
                        icon="lightbulb"
                        title="Belum ada insight bisnis"
                        description="Hubungkan data penjualan dan inventory untuk mulai menerima rekomendasi dari NADI."
                        action="Hubungkan sumber data"
                        compact
                    />
                </div>
            @endif
        </div>
    @endif
</article>
