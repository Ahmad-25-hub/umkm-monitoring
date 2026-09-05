@props(['insight'])
<div class="insight-item insight-{{ $insight['tone'] }}">
    <span class="insight-icon"><i data-lucide="{{ $insight['icon'] }}" aria-hidden="true"></i></span>
    <div class="min-w-0 flex-1">
        <p class="insight-type">{{ $insight['type'] }}</p><h3>{{ $insight['title'] }}</h3>
        <p class="insight-description">{{ $insight['description'] }}</p>
        <a href="{{ $insight['type'] === 'Tim' ? '#team' : route('sales.index') }}" class="insight-action">{{ $insight['type'] === 'Tim' ? 'Lihat anggota tim' : 'Lihat laporan penjualan' }}<i data-lucide="arrow-right" aria-hidden="true"></i></a>
    </div>
</div>
