@props(['business'])

@php
    $navigation = [
        ['label' => 'Tugas Saya', 'icon' => 'activity', 'section' => 'employee-tasks'],
        ['label' => 'Unggah Penjualan', 'icon' => 'upload', 'section' => 'sales-upload'],
        ['label' => 'Unit Terjual', 'icon' => 'shopping-basket', 'section' => 'sales-summary'],
    ];
@endphp
<aside id="dashboard-sidebar" class="sidebar" aria-label="Navigasi karyawan">
    <div class="flex h-full flex-col">
        <div class="brand-lockup">
            <a href="{{ route('employee.dashboard') }}" class="brand-mark" aria-label="Dashboard karyawan NADI">
                <span class="brand-symbol" aria-hidden="true"><span></span><span></span><span></span></span>
                <span><span class="block text-lg font-semibold tracking-[0.18em] text-white">NADI</span><span class="mt-1 block text-xs text-white/60">Ruang kerja tim</span></span>
            </a>
            <button type="button" class="sidebar-close" data-sidebar-close aria-label="Tutup navigasi"><i data-lucide="x" aria-hidden="true"></i></button>
            <button type="button" class="sidebar-collapse" data-sidebar-collapse aria-label="Ciutkan navigasi" aria-expanded="true"><i data-lucide="chevrons-left" aria-hidden="true"></i></button>
        </div>
        <nav class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 pb-5 pt-7">
            <p class="nav-label">Pekerjaan hari ini</p>
            <ul class="mt-3 grid gap-1.5">
                @foreach ($navigation as $item)
                    <li><a href="{{ route('employee.dashboard') }}#{{ $item['section'] }}" class="nav-item" data-section-link="{{ $item['section'] }}" title="{{ $item['label'] }}"><i data-lucide="{{ $item['icon'] }}" aria-hidden="true"></i><span>{{ $item['label'] }}</span></a></li>
                @endforeach
            </ul>
            <div class="mt-auto pt-8">
                <div class="sidebar-context">
                    <span class="sidebar-context-icon"><i data-lucide="shield-check" aria-hidden="true"></i></span>
                    <div class="min-w-0"><p class="truncate text-sm font-medium text-white/90">{{ $business->name }}</p><p class="mt-2 text-xs leading-5 text-white/60">Terhubung sebagai karyawan.</p></div>
                </div>
            </div>
        </nav>
    </div>
</aside>
