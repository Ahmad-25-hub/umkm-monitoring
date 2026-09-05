@props([
    'roleLabel' => 'Pengguna',
    'logoutRoute' => 'logout',
])

@php($accountUser = auth()->user())

<div class="relative" data-profile-menu>
    <button
        type="button"
        class="group flex min-h-11 items-center gap-2 rounded-xl px-1.5 py-1 transition hover:bg-white/70 focus-visible:ring-4 focus-visible:ring-brand-100"
        aria-label="Buka menu profil {{ $accountUser->name }}"
        aria-haspopup="menu"
        aria-controls="profile-menu-panel"
        aria-expanded="false"
        data-profile-menu-toggle
    >
        <x-account.avatar :user="$accountUser" class="h-9 w-9 text-xs" />
        <span class="hidden max-w-32 truncate text-sm font-semibold text-ink xl:block">{{ $accountUser->name }}</span>
        <i data-lucide="chevron-down" class="h-4 w-4 text-ink-faint transition group-aria-expanded:rotate-180" aria-hidden="true"></i>
    </button>

    <div
        id="profile-menu-panel"
        class="fixed left-3 right-3 top-[calc(var(--topbar-height)-.25rem)] z-60 overflow-hidden rounded-2xl border border-line bg-white shadow-menu sm:absolute sm:left-auto sm:right-0 sm:top-[calc(100%+.65rem)] sm:w-80"
        role="menu"
        aria-label="Menu akun"
        data-profile-menu-panel
        hidden
    >
        <div class="flex items-center gap-3 border-b border-line-soft px-4 py-4">
            <x-account.avatar :user="$accountUser" class="h-11 w-11 text-sm" />
            <div class="min-w-0 flex-1">
                <p class="truncate text-sm font-semibold text-ink">{{ $accountUser->name }}</p>
                <p class="mt-0.5 truncate text-xs text-ink-muted">{{ $accountUser->email }}</p>
                <span class="mt-2 inline-flex rounded-full bg-brand-50 px-2 py-1 text-[0.65rem] font-semibold text-brand-700">{{ $roleLabel }}</span>
            </div>
        </div>

        <div class="p-2">
            <a href="{{ route('profile.show') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink transition hover:bg-canvas focus-visible:ring-4 focus-visible:ring-brand-100" role="menuitem" data-profile-menu-item>
                <i data-lucide="user-round" class="h-4 w-4 text-ink-muted" aria-hidden="true"></i>
                <span>Profil Saya</span>
            </a>
            <a href="{{ route('account.settings') }}" class="flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-medium text-ink transition hover:bg-canvas focus-visible:ring-4 focus-visible:ring-brand-100" role="menuitem" data-profile-menu-item>
                <i data-lucide="settings-2" class="h-4 w-4 text-ink-muted" aria-hidden="true"></i>
                <span>Pengaturan Akun</span>
            </a>
        </div>

        <form method="POST" action="{{ route($logoutRoute) }}" class="border-t border-line-soft p-2">
            @csrf
            <button type="submit" class="flex w-full items-center gap-3 rounded-xl px-3 py-2.5 text-left text-sm font-semibold text-critical transition hover:bg-red-50 focus-visible:ring-4 focus-visible:ring-red-100" role="menuitem" data-profile-menu-item>
                <i data-lucide="log-out" class="h-4 w-4" aria-hidden="true"></i>
                <span>Keluar</span>
            </button>
        </form>
    </div>
</div>
