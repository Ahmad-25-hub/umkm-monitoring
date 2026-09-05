@props(['business'])

@php
    $navigation = [
        ['label' => 'Tugas Saya', 'icon' => 'layout-dashboard', 'route' => 'employee.dashboard', 'active' => request()->routeIs('employee.dashboard')],
        ['label' => 'Inventory', 'icon' => 'package', 'route' => null],
        ['label' => 'Jadwal', 'icon' => 'calendar-days', 'route' => null],
    ];
@endphp

<aside id="dashboard-sidebar" class="sidebar" aria-label="Navigasi karyawan">
    <div class="flex h-full flex-col">
        <div class="brand-lockup">
            <a href="{{ route('employee.dashboard') }}" class="brand-mark" aria-label="Dashboard karyawan NADI">
                <span class="brand-symbol" aria-hidden="true">
                    <span></span><span></span><span></span>
                </span>
                <span>
                    <span class="block text-[1.1rem] font-semibold tracking-[0.18em] text-white">NADI</span>
                    <span class="mt-0.5 block text-[0.65rem] font-medium tracking-[0.08em] text-white/45">Employee Workspace</span>
                </span>
            </a>

            <button type="button" class="sidebar-close" data-sidebar-close aria-label="Tutup navigasi">
                <i data-lucide="x" aria-hidden="true"></i>
            </button>

            <button type="button" class="sidebar-collapse" data-sidebar-collapse aria-label="Ciutkan navigasi" aria-expanded="true">
                <i data-lucide="chevrons-left" aria-hidden="true"></i>
            </button>
        </div>

        <nav class="flex min-h-0 flex-1 flex-col px-4 pb-5 pt-7">
            <p class="nav-label">Ruang kerja</p>
            <ul class="mt-3 space-y-1">
                @foreach ($navigation as $item)
                    <li>
                        <a
                            href="{{ $item['route'] ? route($item['route']) : '#' }}"
                            title="{{ $item['label'] }}"
                            @class(['nav-item', 'is-active' => $item['active'] ?? false])
                            @if (! $item['route']) aria-disabled="true" @endif
                        >
                            <i data-lucide="{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                            @if (! $item['route'])
                                <span class="nav-badge">Soon</span>
                            @endif
                        </a>
                    </li>
                @endforeach
            </ul>

            <div class="mt-auto pt-8">
                <div class="sidebar-context">
                    <span class="sidebar-context-icon"><i data-lucide="shield-check" aria-hidden="true"></i></span>
                    <div class="min-w-0">
                        <p class="truncate text-xs font-medium text-white/90">{{ $business->name }}</p>
                        <p class="mt-1 text-[0.68rem] leading-4 text-white/42">Terhubung sebagai karyawan.</p>
                    </div>
                </div>

            </div>
        </nav>
    </div>
</aside>
