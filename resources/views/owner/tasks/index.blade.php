<x-dashboard.app-shell :owner="$owner" :businesses="$businesses" :active-business-id="$activeBusinessId">
    <div class="dashboard-sections">
        @if (session('success'))
            <div class="rounded-2xl border border-brand-100 bg-brand-50 px-5 py-4 text-sm font-medium text-brand-900" role="status">{{ session('success') }}</div>
        @endif

        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-kicker">Tim · {{ $activeBusiness->name }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-ink">Manajemen tugas</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Atur definisi tugas, penerima, jadwal, dan status aktif dalam satu tempat.</p>
            </div>
            <div class="flex flex-wrap gap-3">
                <a href="{{ route('task-occurrences.index') }}" class="inline-flex items-center justify-center gap-2 rounded-xl border border-line-soft bg-white px-4 py-2.5 text-sm font-semibold text-ink transition hover:bg-canvas">
                    <i data-lucide="activity" class="h-4 w-4" aria-hidden="true"></i>
                    Pantau progres
                </a>
                <a href="{{ route('tasks.create') }}" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800">
                    <i data-lucide="plus" class="h-4 w-4" aria-hidden="true"></i>
                    Buat tugas
                </a>
            </div>
        </header>

        <section aria-labelledby="task-list-heading">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Definisi tugas</p>
                    <h2 id="task-list-heading" class="section-heading">Semua tugas</h2>
                </div>
                <span class="section-meta">{{ $tasks->total() }} tugas</span>
            </div>

            <div class="mt-5 grid gap-4 lg:grid-cols-2">
                @forelse ($tasks as $task)
                    <article class="panel flex flex-col p-6">
                        <div class="flex items-start justify-between gap-4">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[0.68rem] font-semibold',
                                        'bg-red-50 text-red-700' => $task->priority === \App\TaskPriority::High,
                                        'bg-amber-50 text-amber-700' => $task->priority === \App\TaskPriority::Normal,
                                        'bg-slate-100 text-slate-600' => $task->priority === \App\TaskPriority::Low,
                                    ])>{{ $task->priority->label() }}</span>
                                    <span class="rounded-full bg-canvas px-2.5 py-1 text-[0.68rem] font-semibold text-ink-muted">{{ $task->type->label() }}</span>
                                    <span @class([
                                        'rounded-full px-2.5 py-1 text-[0.68rem] font-semibold',
                                        'bg-brand-50 text-brand-700' => $task->is_active,
                                        'bg-slate-100 text-slate-500' => ! $task->is_active,
                                    ])>{{ $task->is_active ? 'Aktif' : 'Nonaktif' }}</span>
                                </div>
                                <h3 class="mt-4 text-lg font-semibold text-ink">{{ $task->title }}</h3>
                                @if ($task->description)
                                    <p class="mt-2 line-clamp-2 text-sm leading-6 text-ink-muted">{{ $task->description }}</p>
                                @endif
                            </div>
                            <a href="{{ route('tasks.edit', $task) }}" class="inline-grid h-9 w-9 shrink-0 place-items-center rounded-xl border border-line-soft text-ink-muted transition hover:bg-canvas hover:text-ink" aria-label="Edit {{ $task->title }}">
                                <i data-lucide="settings-2" class="h-4 w-4" aria-hidden="true"></i>
                            </a>
                        </div>

                        <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-line-soft pt-5 text-xs">
                            <div>
                                <dt class="text-ink-faint">Penerima</dt>
                                <dd class="mt-1 font-semibold text-ink">{{ $task->assignees->pluck('name')->join(', ') }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-faint">Jadwal</dt>
                                <dd class="mt-1 font-semibold text-ink">
                                    @if ($task->type === \App\TaskType::Daily)
                                        Setiap hari · {{ substr((string) $task->daily_due_time, 0, 5) }}
                                    @else
                                        {{ $task->due_at?->format('d M Y, H:i') }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="text-ink-faint">Belum selesai</dt>
                                <dd class="mt-1 text-base font-semibold text-ink">{{ $task->pending_occurrences_count }}</dd>
                            </div>
                            <div>
                                <dt class="text-ink-faint">Selesai</dt>
                                <dd class="mt-1 text-base font-semibold text-brand-700">{{ $task->completed_occurrences_count }}</dd>
                            </div>
                        </dl>
                    </article>
                @empty
                    <div class="panel p-10 text-center lg:col-span-2">
                        <span class="mx-auto inline-grid h-12 w-12 place-items-center rounded-2xl bg-brand-50 text-brand-700"><i data-lucide="activity" aria-hidden="true"></i></span>
                        <h3 class="mt-4 text-lg font-semibold text-ink">Belum ada tugas</h3>
                        <p class="mt-2 text-sm text-ink-muted">Buat tugas pertama untuk mulai mengatur pekerjaan tim.</p>
                    </div>
                @endforelse
            </div>

            @if ($tasks->hasPages())
                <div class="mt-6">{{ $tasks->links() }}</div>
            @endif
        </section>
    </div>
</x-dashboard.app-shell>
