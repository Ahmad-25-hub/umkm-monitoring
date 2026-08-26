@props(['alerts', 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel p-6 sm:p-7 '.$class]) }}>
    <div class="panel-heading-row">
        <div>
            <p class="section-kicker">Action required</p>
            <h2 class="panel-title mt-1.5">Needs Your Attention</h2>
            <p class="mt-2 text-xs text-ink-muted">Prioritas yang sebaiknya ditinjau hari ini.</p>
        </div>
        <span class="attention-count">{{ count($alerts) }}</span>
    </div>

    <div class="alert-list mt-6">
        @foreach ($alerts as $alert)
            <div class="alert-item alert-{{ $alert['severity'] }}">
                <span class="alert-icon"><i data-lucide="{{ $alert['icon'] }}" aria-hidden="true"></i></span>
                <div class="min-w-0 flex-1">
                    <p class="alert-category">{{ $alert['category'] }}</p>
                    <h3>{{ $alert['title'] }}</h3>
                    <p>{{ $alert['description'] }}</p>
                    <button type="button">Lihat detail <i data-lucide="arrow-right" aria-hidden="true"></i></button>
                </div>
            </div>
        @endforeach
    </div>
</article>
