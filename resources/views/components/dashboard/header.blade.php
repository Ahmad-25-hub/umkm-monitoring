@props([
    'owner',
    'businesses' => [],
    'activeBusinessId' => null,
])

@php
    $activeBusiness = collect($businesses)->firstWhere('id', $activeBusinessId) ?? collect($businesses)->first();
@endphp

<header class="topbar">
    <div class="topbar-inner">
        <button type="button" class="icon-button lg:hidden" data-sidebar-open aria-label="Open navigation" aria-controls="dashboard-sidebar">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        <div class="hidden min-w-0 sm:block">
            <p class="truncate text-[0.95rem] font-semibold tracking-[-0.01em] text-ink">Selamat datang, {{ $owner['name'] }} <span aria-hidden="true">👋</span></p>
            <p class="mt-0.5 truncate text-xs text-ink-muted">Berikut ringkasan usaha Anda hari ini.</p>
        </div>

        <div class="flex min-w-0 flex-1 items-center justify-end gap-2 sm:gap-3">
            <label class="search-control" aria-label="Search NADI">
                <i data-lucide="search" aria-hidden="true"></i>
                <input type="search" placeholder="Search anything..." data-global-search />
                <kbd>⌘ K</kbd>
            </label>

            <div class="notification-center">
                <button
                    type="button"
                    class="icon-button relative"
                    aria-label="Buka notifikasi, 3 belum dibaca"
                    aria-expanded="false"
                    data-notification-toggle
                >
                    <i data-lucide="bell" aria-hidden="true"></i>
                    <span class="notification-dot" data-notification-dot aria-hidden="true"></span>
                </button>

                <div class="notification-menu" data-notification-menu hidden>
                    <div class="notification-menu-header">
                        <div>
                            <p>Notifikasi</p>
                            <span data-notification-count>3 belum dibaca</span>
                        </div>
                        <button type="button" data-mark-notifications-read>Tandai sudah dibaca</button>
                    </div>
                    <div class="notification-list">
                        <button type="button" class="notification-item is-unread">
                            <span class="notification-icon is-warning"><i data-lucide="package-x" aria-hidden="true"></i></span>
                            <span><strong>Stok perlu diperiksa</strong><small>Minyak Goreng 2L diperkirakan habis dalam 2 hari.</small><em>5 menit lalu</em></span>
                        </button>
                        <button type="button" class="notification-item is-unread">
                            <span class="notification-icon is-positive"><i data-lucide="trending-up" aria-hidden="true"></i></span>
                            <span><strong>Target harian tercapai</strong><small>Pendapatan hari ini sudah mencapai 104% dari target.</small><em>28 menit lalu</em></span>
                        </button>
                        <button type="button" class="notification-item is-unread">
                            <span class="notification-icon is-info"><i data-lucide="clock-3" aria-hidden="true"></i></span>
                            <span><strong>Ringkasan shift tersedia</strong><small>Performa shift pagi siap untuk ditinjau.</small><em>1 jam lalu</em></span>
                        </button>
                    </div>
                    <button type="button" class="notification-footer">Lihat semua notifikasi <i data-lucide="arrow-right" aria-hidden="true"></i></button>
                </div>
            </div>

            <details class="business-selector">
                <summary>
                    <span class="business-avatar">{{ strtoupper(substr($activeBusiness['name'] ?? 'NADI', 0, 1)) }}</span>
                    <span class="hidden min-w-0 text-left md:block">
                        <span class="block text-[0.66rem] font-medium uppercase tracking-[0.08em] text-ink-faint">Usaha aktif</span>
                        <span class="block max-w-32 truncate text-xs font-semibold text-ink">{{ $activeBusiness['name'] ?? 'Pilih usaha' }}</span>
                    </span>
                    <i data-lucide="chevrons-up-down" aria-hidden="true"></i>
                </summary>

                <div class="business-menu">
                    <p class="px-3 pb-2 pt-1 text-[0.66rem] font-semibold uppercase tracking-[0.1em] text-ink-faint">Usaha Anda</p>
                    @foreach ($businesses as $business)
                        <form method="POST" action="{{ route('businesses.activate', $business['id']) }}">
                            @csrf
                            <button type="submit" @class(['business-option', 'is-active' => $business['id'] === $activeBusinessId])>
                                <span class="business-option-mark">{{ strtoupper(substr($business['name'], 0, 1)) }}</span>
                                <span class="min-w-0 flex-1">
                                    <span class="block truncate font-medium">{{ $business['name'] }}</span>
                                    <span class="block truncate text-[0.68rem] text-ink-faint">{{ $business['location'] }}</span>
                                </span>
                                @if ($business['id'] === $activeBusinessId)
                                    <i data-lucide="check" aria-hidden="true"></i>
                                @endif
                            </button>
                        </form>
                    @endforeach
                    <button type="button" class="business-option border-t border-line-soft text-brand-700">
                        <span class="business-option-mark bg-brand-50 text-brand-700"><i data-lucide="plus" aria-hidden="true"></i></span>
                        <span class="font-medium">Tambah usaha</span>
                    </button>
                </div>
            </details>

            <button type="button" class="profile-avatar" aria-label="Open profile for {{ $owner['name'] }}">
                {{ $owner['initials'] }}
            </button>
        </div>
    </div>
</header>
