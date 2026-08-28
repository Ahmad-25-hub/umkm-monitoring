@props(['employees', 'loading' => false, 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel p-6 sm:p-7 '.$class]) }}>
    @if ($loading)
        <x-dashboard.loading-state variant="employees" />
    @else
        <div class="panel-heading-row">
            <div>
                <p class="section-kicker">Prioritas #5 · Your team</p>
                <h2 class="panel-title mt-1.5">Team Performance</h2>
                <p class="mt-2 text-xs text-ink-muted">Karyawan dengan kontribusi penjualan terbaik bulan ini.</p>
            </div>
            @if (count($employees))
                <button type="button" class="text-link">Lihat semua <i data-lucide="arrow-up-right" aria-hidden="true"></i></button>
            @endif
        </div>

        @if (count($employees))
            <div class="employee-list mt-7">
                @foreach ($employees as $employee)
                    <div class="employee-row">
                        <span class="rank-badge rank-{{ $employee['rank'] }}">{{ $employee['rank'] }}</span>
                        <span class="employee-avatar avatar-{{ $employee['tone'] }}">
                            {{ $employee['initials'] }}
                            <span class="online-dot" aria-label="Aktif"></span>
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h3 class="truncate text-sm font-semibold text-ink">{{ $employee['name'] }}</h3>
                                @if ($employee['rank'] === 1)
                                    <i data-lucide="award" class="h-3.5 w-3.5 text-warning" aria-label="Peringkat teratas"></i>
                                @endif
                            </div>
                            <p class="mt-1 text-[0.68rem] text-ink-faint">{{ $employee['sales'] }} penjualan</p>
                        </div>
                        <div class="employee-score">
                            <p>{{ $employee['score'] }}</p>
                            <span>Performance score</span>
                        </div>
                        <span @class(['employee-status', 'is-good' => $employee['status'] === 'Good'])>{{ $employee['status'] }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <x-dashboard.empty-state
                icon="users-round"
                title="Belum ada data performa karyawan"
                description="Tambahkan karyawan dan hubungkan aktivitas penjualan untuk mulai melihat leaderboard tim."
                action="Kelola karyawan"
                compact
            />
        @endif
    @endif
</article>
