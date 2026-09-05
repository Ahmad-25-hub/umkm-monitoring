@props(['employee', 'business'])

<header class="topbar">
    <div class="topbar-inner">
        <button type="button" class="icon-button lg:hidden" data-sidebar-open aria-label="Buka navigasi" aria-controls="dashboard-sidebar">
            <i data-lucide="menu" aria-hidden="true"></i>
        </button>

        <div class="min-w-0 flex-1">
            <p class="truncate text-[0.95rem] font-semibold tracking-[-0.01em] text-ink">Selamat datang, {{ $employee['name'] }} <span aria-hidden="true">👋</span></p>
            <p class="mt-0.5 truncate text-xs text-ink-muted">Ruang kerja karyawan · {{ $business->name }}</p>
        </div>

        <div class="flex min-w-0 items-center gap-3">
            <span class="hidden rounded-full border border-brand-100 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700 sm:inline-flex">Karyawan aktif</span>
            <x-account.profile-dropdown role-label="Karyawan" logout-route="employee.logout" />
        </div>
    </div>
</header>
