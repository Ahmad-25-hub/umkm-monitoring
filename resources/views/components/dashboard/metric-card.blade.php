@props(['metric', 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel metric-card p-5 '.$class]) }}>
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-medium text-ink-muted">{{ $metric['label'] }}</p>
            <p class="metric-value">{{ $metric['value'] }}</p>
        </div>
        <span class="panel-icon"><i data-lucide="{{ $metric['icon'] }}" aria-hidden="true"></i></span>
    </div>

    <div class="mt-4 flex h-10 items-end justify-between gap-4">
        <div class="min-w-0">
            <span @class([
                'metric-change',
                'is-neutral' => $metric['tone'] === 'neutral',
                'is-negative' => $metric['tone'] === 'negative',
            ])>
                @if ($metric['tone'] === 'positive')
                    <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                @endif
                {{ $metric['change'] }}
            </span>
            <p class="mt-1 text-xs text-ink-faint">{{ $metric['context'] }}</p>
        </div>
        <canvas
            class="metric-sparkline"
            width="92"
            height="36"
            data-sparkline="{{ implode(',', $metric['trend']) }}"
            data-tone="{{ $metric['tone'] }}"
            aria-label="Tren {{ $metric['label'] }}"
        ></canvas>
    </div>
</article>
