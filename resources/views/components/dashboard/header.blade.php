@props(['owner', 'businesses' => [], 'activeBusinessId' => null])

@php
    $activeBusiness = collect($businesses)->firstWhere('id', $activeBusinessId) ?? collect($businesses)->first();
    $pageTitle = match (true) {
        request()->routeIs('profile.*') => 'Profil Saya',
        request()->routeIs('account.*') => 'Pengaturan Akun',
        request()->routeIs('ai-insight.*') => 'AI Insight',
        request()->routeIs('sales.*') => 'Penjualan',
        request()->routeIs('tasks.*') => 'Manajemen tugas',
        request()->routeIs('task-occurrences.*') => 'Monitoring tugas',
        default => 'Ringkasan usaha',
    };
@endphp

<header class="topbar">
    <div class="topbar-inner">
        <button type="button" class="icon-button lg:hidden" data-sidebar-open aria-label="Buka navigasi" aria-controls="dashboard-sidebar"><i data-lucide="menu" aria-hidden="true"></i></button>
        <div class="hidden min-w-0 flex-1 sm:block">
            <p class="text-sm font-semibold text-ink">{{ $pageTitle }}</p>
            <p class="mt-1 truncate text-xs text-ink-muted">Ruang pemilik · {{ $owner['name'] }}</p>
        </div>
        <div class="flex min-w-0 flex-1 items-center justify-end gap-3 sm:flex-none sm:gap-5">
            <details class="business-selector min-w-0">
                <summary aria-label="Pilih usaha aktif: {{ $activeBusiness['name'] ?? 'Pilih usaha' }}">
                    <span class="business-avatar shrink-0">{{ strtoupper(substr($activeBusiness['name'] ?? 'NADI', 0, 1)) }}</span>
                    <span class="min-w-0 text-left">
                        <span class="block text-xs text-ink-muted">Usaha aktif</span>
                        <span class="block max-w-[8rem] truncate text-sm font-semibold text-ink sm:max-w-48">{{ $activeBusiness['name'] ?? 'Pilih usaha' }}</span>
                    </span>
                    <i data-lucide="chevrons-up-down" class="shrink-0" aria-hidden="true"></i>
                </summary>
                <div class="business-menu">
                    <p class="px-3 pb-2 pt-1 text-xs font-semibold text-ink-muted">Pilih usaha Anda</p>
                    @foreach ($businesses as $business)
                        <form method="POST" action="{{ route('businesses.activate', $business['id']) }}" data-submit-once>
                            @csrf
                            <button type="submit" @class(['business-option', 'is-active' => $business['id'] === $activeBusinessId])>
                                <span class="business-option-mark">{{ strtoupper(substr($business['name'], 0, 1)) }}</span>
                                <span class="min-w-0 flex-1 text-left">
                                    <span class="block truncate font-medium">{{ $business['name'] }}</span>
                                    <span class="mt-1 block text-xs text-ink-muted">{{ $business['location'] }}</span>
                                </span>
                                @if ($business['id'] === $activeBusinessId)<i data-lucide="check" aria-hidden="true"></i>@endif
                            </button>
                        </form>
                    @endforeach
                </div>
            </details>
            <x-account.profile-dropdown role-label="Pemilik" logout-route="logout" />
        </div>
    </div>
</header>
