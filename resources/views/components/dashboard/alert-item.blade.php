@props(['alert'])

<div class="alert-item alert-{{ $alert['severity'] }}">
    <span class="alert-icon"><i data-lucide="{{ $alert['icon'] }}" aria-hidden="true"></i></span>
    <div class="min-w-0 flex-1">
        <p class="alert-category">{{ $alert['category'] }}</p>
        <h3>{{ $alert['title'] }}</h3>
        <p>{{ $alert['description'] }}</p>
        <button type="button">Lihat detail <i data-lucide="arrow-right" aria-hidden="true"></i></button>
    </div>
</div>
