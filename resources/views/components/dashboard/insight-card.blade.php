@props(['insights', 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel insights-panel overflow-hidden '.$class]) }}>
    <div class="insights-intro">
        <div>
            <span class="dark-icon"><i data-lucide="sparkles" aria-hidden="true"></i></span>
            <p class="mt-8 text-[0.66rem] font-semibold uppercase tracking-[0.12em] text-brand-200">Data → Insight → Action</p>
            <h2 class="mt-3 text-2xl font-medium tracking-[-0.035em] text-white">NADI Insights</h2>
            <p class="mt-3 max-w-xs text-sm leading-6 text-white/48">Hal penting dari bisnis Anda, dijelaskan dengan singkat dan dapat ditindaklanjuti.</p>
        </div>
        <p class="mt-10 text-[0.68rem] font-medium text-white/32">4 insight terbaru · diperbarui hari ini</p>
    </div>

    <div class="insight-list">
        @foreach ($insights as $insight)
            <div class="insight-item insight-{{ $insight['tone'] }}">
                <span class="insight-icon"><i data-lucide="{{ $insight['icon'] }}" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <p class="insight-type">{{ $insight['type'] }}</p>
                    <h3>{{ $insight['title'] }}</h3>
                    <p class="insight-description">{{ $insight['description'] }}</p>
                    <button type="button" class="insight-action">
                        {{ $insight['action'] }}
                        <i data-lucide="arrow-right" aria-hidden="true"></i>
                    </button>
                </div>
            </div>
        @endforeach
    </div>
</article>
