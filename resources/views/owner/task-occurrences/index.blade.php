<x-dashboard.app-shell :owner="$owner" :businesses="$businesses" :active-business-id="$activeBusinessId">
    <div class="dashboard-sections">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-kicker">Manajemen tugas</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-ink">Monitoring progres</h1>
                <p class="mt-2 text-sm leading-6 text-ink-muted">Lihat progres, tenggat, dan catatan pekerjaan setiap anggota tim.</p>
            </div>
            <a href="{{ route('tasks.index') }}" class="inline-flex items-center justify-center rounded-xl border border-line-soft bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-canvas">Kembali ke tugas</a>
        </header>

        <section class="grid grid-cols-2 gap-4 xl:grid-cols-4" aria-label="Ringkasan progres tugas">
            @foreach ([
                ['label' => 'Belum dikerjakan', 'value' => (int) ($summary->pending_count ?? 0), 'tone' => 'text-ink'],
                ['label' => 'Sedang dikerjakan', 'value' => (int) ($summary->in_progress_count ?? 0), 'tone' => 'text-blue-700'],
                ['label' => 'Selesai', 'value' => (int) ($summary->completed_count ?? 0), 'tone' => 'text-brand-700'],
                ['label' => 'Terlambat', 'value' => (int) ($summary->overdue_count ?? 0), 'tone' => 'text-red-700'],
            ] as $metric)
                <article class="panel p-5">
                    <p class="text-xs font-semibold uppercase tracking-[0.1em] text-ink-faint">{{ $metric['label'] }}</p>
                    <p class="mt-3 text-3xl font-semibold {{ $metric['tone'] }}">{{ $metric['value'] }}</p>
                </article>
            @endforeach
        </section>

        <section class="panel p-5 sm:p-6" aria-labelledby="task-filters-heading">
            <h2 id="task-filters-heading" class="text-base font-semibold text-ink">Filter pekerjaan</h2>
            <form method="GET" action="{{ route('task-occurrences.index') }}" class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                <label class="grid gap-2 text-xs font-medium text-ink-muted">Tanggal tugas<input type="date" name="date" value="{{ $filters['date'] ?? '' }}" class="rounded-xl border border-line-soft bg-white px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100"></label>
                <label class="grid gap-2 text-xs font-medium text-ink-muted">Karyawan<select name="employee_id" class="rounded-xl border border-line-soft bg-white px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">Semua karyawan</option>
                    @foreach ($employees as $membership)
                        <option value="{{ $membership->user_id }}" @selected((string) ($filters['employee_id'] ?? '') === (string) $membership->user_id)>{{ $membership->user->name }}</option>
                    @endforeach
                </select></label>
                <label class="grid gap-2 text-xs font-medium text-ink-muted">Status<select name="status" class="rounded-xl border border-line-soft bg-white px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">Semua status</option>
                    <option value="overdue" @selected(($filters['status'] ?? '') === 'overdue')>Terlambat</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected(($filters['status'] ?? '') === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select></label>
                <label class="grid gap-2 text-xs font-medium text-ink-muted">Prioritas<select name="priority" class="rounded-xl border border-line-soft bg-white px-3 py-2.5 text-sm outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                    <option value="">Semua prioritas</option>
                    @foreach ($priorities as $priority)
                        <option value="{{ $priority->value }}" @selected(($filters['priority'] ?? '') === $priority->value)>{{ $priority->label() }}</option>
                    @endforeach
                </select></label>
                <div class="flex items-center justify-end gap-2 sm:col-span-2 xl:col-span-4"><a href="{{ route('task-occurrences.index') }}" class="button-secondary">Reset</a><button type="submit" class="rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">Terapkan filter</button></div>
            </form>
        </section>

        <section class="panel overflow-hidden" aria-labelledby="occurrence-table-heading">
            <div class="flex items-center justify-between border-b border-line-soft px-5 py-4 sm:px-6">
                <h2 id="occurrence-table-heading" class="text-base font-semibold text-ink">Riwayat pekerjaan</h2>
                <span class="text-xs text-ink-faint">{{ $occurrences->total() }} pekerjaan</span>
            </div>
            <div class="overflow-x-auto">
                <table class="responsive-table w-full min-w-[820px] text-left text-sm">
                    <thead class="bg-canvas text-xs uppercase tracking-[0.08em] text-ink-faint">
                        <tr>
                            <th class="px-5 py-3 font-semibold sm:px-6">Tugas</th>
                            <th class="px-5 py-3 font-semibold">Karyawan</th>
                            <th class="px-5 py-3 font-semibold">Tanggal</th>
                            <th class="px-5 py-3 font-semibold">Tenggat</th>
                            <th class="px-5 py-3 font-semibold">Status</th>
                            <th class="px-5 py-3 font-semibold">Catatan</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-line-soft">
                        @forelse ($occurrences as $occurrence)
                            <tr class="align-top">
                                <td data-label="Tugas" class="px-5 py-4 sm:px-6">
                                    <p class="font-semibold text-ink">{{ $occurrence->title }}</p>
                                    <p class="mt-1 text-xs text-ink-faint">{{ $occurrence->priority->label() }} · {{ $occurrence->task->type->label() }}</p>
                                </td>
                                <td data-label="Karyawan" class="px-5 py-4">
                                    <p class="font-medium text-ink">{{ $occurrence->assignee->name }}</p>
                                    <p class="mt-1 text-xs text-ink-faint">{{ $occurrence->assignee->email }}</p>
                                </td>
                                <td data-label="Tanggal" class="px-5 py-4 text-ink-muted">{{ $occurrence->occurrence_date->format('d M Y') }}</td>
                                <td data-label="Tenggat" class="px-5 py-4 text-ink-muted">{{ $occurrence->due_at->format('d M Y, H:i') }}</td>
                                <td data-label="Status" class="px-5 py-4">
                                    <span @class([
                                        'inline-flex rounded-full px-2.5 py-1 text-xs font-semibold',
                                        'bg-red-50 text-red-700' => $occurrence->isOverdue(),
                                        'bg-brand-50 text-brand-700' => ! $occurrence->isOverdue() && $occurrence->status === \App\TaskOccurrenceStatus::Completed,
                                        'bg-blue-50 text-blue-700' => ! $occurrence->isOverdue() && $occurrence->status === \App\TaskOccurrenceStatus::InProgress,
                                        'bg-slate-100 text-slate-600' => ! $occurrence->isOverdue() && in_array($occurrence->status, [\App\TaskOccurrenceStatus::Pending, \App\TaskOccurrenceStatus::Cancelled], true),
                                    ])>{{ $occurrence->isOverdue() ? 'Terlambat' : $occurrence->status->label() }}</span>
                                </td>
                                <td data-label="Catatan" class="mobile-wide max-w-56 px-5 py-4 text-xs leading-5 text-ink-muted">{{ $occurrence->employee_note ?: '—' }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-sm text-ink-muted">Tidak ada pekerjaan yang cocok dengan filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($occurrences->hasPages())
                <div class="border-t border-line-soft px-5 py-4 sm:px-6">{{ $occurrences->links() }}</div>
            @endif
        </section>
    </div>
</x-dashboard.app-shell>
