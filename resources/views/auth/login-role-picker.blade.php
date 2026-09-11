<nav aria-label="Pilih peran untuk masuk" class="mt-7 flex flex-col gap-3">
    <p class="text-sm font-semibold text-ink">Masuk sebagai</p>

    <div class="grid grid-cols-2 gap-3">
        @foreach ([
            'owner' => ['label' => 'Pemilik Usaha', 'description' => 'Kelola usaha dan pantau perkembangannya.', 'icon' => 'store', 'route' => 'login'],
            'employee' => ['label' => 'Karyawan', 'description' => 'Akses tugas dan aktivitas kerja harian.', 'icon' => 'users-round', 'route' => 'employee.login'],
        ] as $role => $option)
            <a
                href="{{ route($option['route']) }}"
                @if ($activeRole === $role) aria-current="page" @endif
                @class([
                    'group flex min-w-0 flex-col gap-3 rounded-2xl border p-3.5 transition-colors focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-brand-600 sm:p-4',
                    'border-brand-600 bg-brand-50 ring-1 ring-brand-600' => $activeRole === $role,
                    'border-line bg-white hover:border-brand-200 hover:bg-brand-50/50' => $activeRole !== $role,
                ])
            >
                <span class="flex items-center justify-between gap-2">
                    <span @class([
                        'grid size-10 shrink-0 place-items-center rounded-xl',
                        'bg-brand-700 text-white' => $activeRole === $role,
                        'bg-canvas text-ink-muted group-hover:text-brand-700' => $activeRole !== $role,
                    ])>
                        <i data-lucide="{{ $option['icon'] }}" class="size-5" aria-hidden="true"></i>
                    </span>
                    @if ($activeRole === $role)
                        <span class="grid size-5 shrink-0 place-items-center rounded-full bg-brand-700 text-white">
                            <i data-lucide="check" class="size-3.5" aria-hidden="true"></i>
                            <span class="sr-only">Dipilih</span>
                        </span>
                    @else
                        <span class="size-5 shrink-0 rounded-full border border-line" aria-hidden="true"></span>
                    @endif
                </span>
                <span class="flex flex-col gap-1.5">
                    <span class="text-sm font-semibold text-ink">{{ $option['label'] }}</span>
                    <span class="text-xs leading-5 text-ink-muted">{{ $option['description'] }}</span>
                </span>
            </a>
        @endforeach
    </div>
</nav>
