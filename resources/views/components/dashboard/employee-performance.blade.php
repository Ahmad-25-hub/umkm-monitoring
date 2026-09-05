@props(['employees', 'loading' => false, 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel p-6 sm:p-7 '.$class]) }}>
    @if ($loading)
        <x-dashboard.loading-state variant="employees" />
    @else
        <div class="panel-heading-row">
            <div>
                <p class="section-kicker">Bekerja bersama</p>
                <h2 class="panel-title mt-1.5">Anggota tim</h2>
                <p class="mt-2 text-xs text-ink-muted">Karyawan yang terdaftar pada usaha aktif saat ini.</p>
            </div>
            @if (count($employees))
                <a href="{{ route('task-occurrences.index') }}" class="text-link">Pantau tugas <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
            @endif
        </div>

        @if (count($employees))
            <div class="employee-list mt-7">
                @foreach ($employees as $employee)
                    <div class="employee-row">
                        <span class="employee-avatar avatar-{{ $employee['tone'] }}">
                            {{ $employee['initials'] }}
                            @if ($employee['isActive'])
                                <span class="online-dot" aria-label="Keanggotaan aktif"></span>
                            @endif
                        </span>
                        <div class="min-w-0 flex-1">
                            <h3 class="truncate text-sm font-semibold text-ink">{{ $employee['name'] }}</h3>
                            <p class="mt-1 truncate text-xs text-ink-faint">{{ $employee['email'] }}</p>
                        </div>
                        <div class="employee-score">
                            <p>{{ $employee['joinedAt'] }}</p>
                            <span>Tanggal bergabung</span>
                        </div>
                        <span @class(['employee-status', 'is-good' => ! $employee['isActive']])>{{ $employee['status'] }}</span>
                    </div>
                @endforeach
            </div>
        @else
            <x-dashboard.empty-state
                icon="users-round"
                title="Belum ada karyawan terdaftar"
                description="Bagikan Kode Usaha agar karyawan dapat bergabung dan tampil pada daftar tim ini."
                action="Undang karyawan" href="#team-access"
                compact
            />
        @endif
    @endif
</article>
