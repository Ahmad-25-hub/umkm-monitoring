@php
    $primaryNavigation = [
        ['label' => 'Ringkasan', 'icon' => 'layout-dashboard', 'route' => 'overview', 'active' => request()->routeIs('overview')],
        ['label' => 'Penjualan', 'icon' => 'chart-no-axes-combined', 'route' => 'sales.index', 'active' => request()->routeIs('sales.*')],
        ['label' => 'Tugas', 'icon' => 'activity', 'route' => 'tasks.index', 'active' => request()->routeIs('tasks.*')],
        ['label' => 'Monitoring Tugas', 'icon' => 'user-round-check', 'route' => 'task-occurrences.index', 'active' => request()->routeIs('task-occurrences.*')],
    ];
@endphp

<aside id="dashboard-sidebar" class="sidebar" aria-label="Navigasi pemilik">
    <div class="flex h-full flex-col">
        <div class="brand-lockup">
            <a href="{{ route('overview') }}" class="brand-mark" aria-label="Ringkasan NADI">
                <span class="brand-symbol" aria-hidden="true"><span></span><span></span><span></span></span>
                <span>
                    <span class="block text-lg font-semibold tracking-[0.18em] text-white">NADI</span>
                    <span class="mt-1 block text-xs text-white/60">Ruang usaha Anda</span>
                </span>
            </a>
            <button type="button" class="sidebar-close" data-sidebar-close aria-label="Tutup navigasi"><i data-lucide="x" aria-hidden="true"></i></button>
            <button type="button" class="sidebar-collapse" data-sidebar-collapse aria-label="Ciutkan navigasi" aria-expanded="true"><i data-lucide="chevrons-left" aria-hidden="true"></i></button>
        </div>
        <nav class="flex min-h-0 flex-1 flex-col overflow-y-auto px-4 pb-5 pt-7">
            <p class="nav-label">Kelola usaha</p>
            <ul class="mt-3 grid gap-1.5">
                @foreach ($primaryNavigation as $item)
                    <li>
                        <a href="{{ route($item['route']) }}" title="{{ $item['label'] }}" @class(['nav-item', 'is-active' => $item['active']]) @if ($item['active']) aria-current="page" @endif>
                            <i data-lucide="{{ $item['icon'] }}" aria-hidden="true"></i>
                            <span>{{ $item['label'] }}</span>
                        </a>
                    </li>
                @endforeach
            </ul>
            <div class="mt-8 border-t border-white/10 pt-6">
                <p class="nav-label">Tim Anda</p>
                <ul class="mt-3 grid gap-1.5">
                    <li><a href="{{ route('overview') }}#team" class="nav-item" title="Anggota tim"><i data-lucide="users-round" aria-hidden="true"></i><span>Anggota tim</span></a></li>
                    <li><a href="{{ route('overview') }}#team-access" class="nav-item" title="Undang karyawan"><i data-lucide="plus" aria-hidden="true"></i><span>Undang karyawan</span></a></li>
                </ul>
            </div>
            <div class="mt-auto pt-8">
                <div class="sidebar-context">
                    <span class="sidebar-context-icon"><i data-lucide="lightbulb" aria-hidden="true"></i></span>
                    <div><p class="text-sm font-medium text-white/90">Mulai dari yang penting</p><p class="mt-2 text-xs leading-5 text-white/60">Cek penjualan, pantau tugas, dan bantu tim tetap terarah.</p></div>
                </div>
            </div>
        </nav>
    </div>
</aside>
