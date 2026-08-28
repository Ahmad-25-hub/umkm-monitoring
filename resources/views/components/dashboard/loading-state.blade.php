@props(['variant' => 'kpi'])

<div class="loading-state loading-{{ $variant }}" role="status" aria-live="polite" aria-label="Memuat data">
    <span class="sr-only">Memuat data dashboard…</span>

    @if ($variant === 'kpi')
        <div class="skeleton-row">
            <span class="skeleton skeleton-label"></span>
            <span class="skeleton skeleton-icon"></span>
        </div>
        <span class="skeleton skeleton-value"></span>
        <div class="skeleton-row mt-auto">
            <span class="skeleton skeleton-meta"></span>
            <span class="skeleton skeleton-sparkline"></span>
        </div>
    @elseif ($variant === 'chart')
        <div class="skeleton-row">
            <div class="space-y-2">
                <span class="skeleton skeleton-label"></span>
                <span class="skeleton skeleton-heading"></span>
            </div>
            <span class="skeleton skeleton-filter"></span>
        </div>
        <div class="skeleton-chart">
            <span></span><span></span><span></span><span></span>
        </div>
    @elseif ($variant === 'employees')
        <span class="skeleton skeleton-heading"></span>
        @for ($row = 0; $row < 3; $row++)
            <div class="skeleton-person">
                <span class="skeleton skeleton-avatar"></span>
                <span class="flex-1 space-y-2"><span class="skeleton skeleton-name"></span><span class="skeleton skeleton-meta"></span></span>
                <span class="skeleton skeleton-badge"></span>
            </div>
        @endfor
    @elseif ($variant === 'insights')
        <span class="skeleton skeleton-heading"></span>
        <div class="mt-5 grid gap-3 sm:grid-cols-2">
            @for ($card = 0; $card < 4; $card++)
                <div class="skeleton-insight">
                    <span class="skeleton skeleton-icon"></span>
                    <span class="flex-1 space-y-2"><span class="skeleton skeleton-name"></span><span class="skeleton skeleton-copy"></span></span>
                </div>
            @endfor
        </div>
    @endif
</div>
