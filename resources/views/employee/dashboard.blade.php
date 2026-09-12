<x-employee.app-shell :employee="$employee" :business="$business">
    @php
        $activeTaskCount = collect($taskGroups)->except('completed')->sum(fn ($tasks) => $tasks->count());
        $inProgressCount = collect($taskGroups)->flatten()->filter(fn ($task) => $task->status === \App\TaskOccurrenceStatus::InProgress)->count();
        $groupPresentation = [
            'overdue' => ['label' => 'Terlambat', 'description' => 'Prioritaskan tugas yang sudah melewati tenggat.', 'tone' => 'is-overdue'],
            'in_progress' => ['label' => 'Sedang dikerjakan', 'description' => 'Lanjutkan pekerjaan yang sudah Anda mulai.', 'tone' => 'is-progress'],
            'new' => ['label' => 'Tugas baru', 'description' => 'Pekerjaan yang baru masuk untuk Anda.', 'tone' => ''],
            'today' => ['label' => 'Tugas hari ini', 'description' => 'Siap untuk mulai dikerjakan.', 'tone' => ''],
            'completed' => ['label' => 'Selesai', 'description' => 'Riwayat penyelesaian dalam tujuh hari terakhir.', 'tone' => 'is-completed'],
        ];
    @endphp
    <div class="dashboard-sections" data-daily-reset-at="{{ $nextDailyResetAt }}">
        <div class="feedback-success" role="status" data-daily-reset-notice hidden>Hari sudah berganti. Simpan pekerjaan Anda, lalu muat ulang halaman untuk melihat tugas hari ini.</div>
        @if (session('success'))
            <div class="feedback-success" role="status"><i data-lucide="circle-check" aria-hidden="true"></i>{{ session('success') }}</div>
        @endif
        @if ($errors->has('status') || $errors->has('employee_note'))
            <div class="feedback-error" role="alert">{{ $errors->first('status') ?: $errors->first('employee_note') }}</div>
        @endif

        <section class="workday-intro" aria-labelledby="employee-dashboard-title">
            <div>
                <p class="section-kicker text-brand-700">Dashboard Karyawan</p>
                <h1 id="employee-dashboard-title" class="mt-3 text-3xl font-semibold tracking-tight text-ink sm:text-4xl">Tugas Anda hari ini.</h1>
                <p class="mt-3 text-sm leading-6 text-ink-muted">Mulai dari yang paling penting. Setiap progres membantu tim <strong class="font-semibold text-ink">{{ $business->name }}</strong> tetap terarah.</p>
            </div>
            <a href="{{ route('employee.sales-entry.create') }}" class="button-secondary shrink-0"><i data-lucide="upload" aria-hidden="true"></i>Tambah penjualan</a>
        </section>

        <div class="workday-stats" aria-label="Ringkasan pekerjaan yang ditampilkan">
            <a href="#employee-tasks" class="workday-stat">
                <span class="stat-symbol"><i data-lucide="activity" aria-hidden="true"></i></span>
                <span><span class="stat-value">{{ $activeTaskCount }}</span><span class="stat-label">Belum selesai</span></span>
                <i data-lucide="arrow-up-right" class="stat-arrow" aria-hidden="true"></i>
            </a>
            <a href="#employee-tasks" class="workday-stat">
                <span class="stat-symbol is-blue"><i data-lucide="clock-3" aria-hidden="true"></i></span>
                <span><span class="stat-value">{{ $inProgressCount }}</span><span class="stat-label">Sedang dikerjakan</span></span>
                <i data-lucide="arrow-up-right" class="stat-arrow" aria-hidden="true"></i>
            </a>
            <a href="#employee-tasks" @class(['workday-stat', 'has-overdue' => $taskGroups['overdue']->isNotEmpty()])>
                <span class="stat-symbol is-amber"><i data-lucide="calendar-days" aria-hidden="true"></i></span>
                <span><span class="stat-value">{{ $taskGroups['overdue']->count() }}</span><span class="stat-label">Terlambat</span></span>
                <i data-lucide="arrow-up-right" class="stat-arrow" aria-hidden="true"></i>
            </a>
        </div>

        <section id="employee-tasks" aria-labelledby="employee-tasks-title" data-task-workspace>
            <div class="section-heading-row">
                <div><p class="section-kicker">Pekerjaan saya</p><h2 id="employee-tasks-title" class="section-heading">Daftar tugas</h2></div>
                <span class="section-meta">{{ collect($taskGroups)->sum(fn ($tasks) => $tasks->count()) }} tugas ditampilkan</span>
            </div>
            <p class="mt-3 text-xs leading-5 text-ink-muted">Tugas harian kembali <strong>Belum dikerjakan</strong> setiap pukul 00.00 WIB. Riwayat dan tugas kemarin yang belum selesai tetap tersimpan.</p>
            <div class="task-toolbar" data-task-filters hidden>
                <label class="task-search">
                    <i data-lucide="search" aria-hidden="true"></i>
                    <span class="sr-only">Cari tugas berdasarkan judul</span>
                    <input type="search" placeholder="Cari judul tugas..." data-task-search autocomplete="off">
                </label>
                <label class="flex items-center gap-3 text-sm text-ink-muted">
                    <span class="shrink-0">Tampilkan</span>
                    <select class="field-control" data-task-filter aria-label="Filter kelompok tugas">
                        <option value="all">Semua tugas</option>
                        @foreach ($groupPresentation as $key => $presentation)
                            <option value="{{ $key }}">{{ $presentation['label'] }} ({{ $taskGroups[$key]->count() }})</option>
                        @endforeach
                    </select>
                </label>
            </div>
            <p class="sr-only" role="status" data-task-result-count></p>
            <div class="mt-5 grid gap-4">
                @foreach ($groupPresentation as $groupKey => $presentation)
                    @if ($taskGroups[$groupKey]->isNotEmpty())
                        <details class="task-group {{ $presentation['tone'] }}" data-task-group="{{ $groupKey }}" @if ($groupKey !== 'completed') open @endif>
                            <summary class="task-group-heading">
                                <span class="min-w-0">
                                    <span class="flex items-center gap-3"><span class="font-semibold text-ink">{{ $presentation['label'] }}</span><span class="task-count">{{ $taskGroups[$groupKey]->count() }}</span></span>
                                    <span class="mt-1 block text-xs leading-5 text-ink-muted">{{ $presentation['description'] }}</span>
                                </span>
                                <i data-lucide="chevron-down" class="h-4 w-4 shrink-0 text-ink-muted" aria-hidden="true"></i>
                            </summary>
                            <div class="divide-y divide-line-soft">
                                @foreach ($taskGroups[$groupKey] as $occurrence)
                                    @php
                                        $isCompleted = $occurrence->status === \App\TaskOccurrenceStatus::Completed;
                                        $isRestoredTask = (string) old('task_occurrence_id') === (string) $occurrence->id;
                                    @endphp
                                    <article id="task-{{ $occurrence->id }}" class="task-entry" data-task-entry data-task-title="{{ $occurrence->title }}">
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-2">
                                                <span @class(['task-priority', 'is-high' => $occurrence->priority === \App\TaskPriority::High, 'is-normal' => $occurrence->priority === \App\TaskPriority::Normal])>{{ $occurrence->priority->label() }}</span>
                                                <span class="text-xs text-ink-muted">{{ $occurrence->task->type->label() }} · {{ $occurrence->occurrence_date->format('d M Y') }}</span>
                                                @if ($occurrence->status === \App\TaskOccurrenceStatus::Pending)
                                                    <span class="text-xs font-medium text-ink-muted">Belum dikerjakan</span>
                                                @endif
                                                @if ($occurrence->status === \App\TaskOccurrenceStatus::InProgress && $occurrence->isOverdue())
                                                    <span class="text-xs font-medium text-blue-700">Sedang dikerjakan</span>
                                                @endif
                                            </div>
                                            <h3 class="mt-3 text-base font-semibold leading-6 text-ink">{{ $occurrence->title }}</h3>
                                            @if ($occurrence->description)
                                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-ink-muted">{{ $occurrence->description }}</p>
                                            @endif
                                            <div class="mt-4 flex flex-wrap gap-x-5 gap-y-2 text-xs text-ink-muted">
                                                <span @class(['inline-flex items-center gap-1.5', 'font-semibold text-red-700' => $occurrence->isOverdue()])><i data-lucide="clock-3" class="h-3.5 w-3.5" aria-hidden="true"></i>Tenggat {{ $occurrence->due_at->format('d M Y, H:i') }} WIB</span>
                                                <span>Dari {{ $occurrence->task->creator->name }}</span>
                                            </div>
                                            @if ($occurrence->employee_note)
                                                <p class="mt-4 rounded-xl bg-canvas px-4 py-3 text-sm leading-6 text-ink-muted"><span class="font-semibold text-ink">Catatan Anda:</span> {{ $occurrence->employee_note }}</p>
                                            @endif
                                        </div>
                                        @if (! $isCompleted)
                                            <div class="task-entry-actions">
                                                @if ($occurrence->status === \App\TaskOccurrenceStatus::Pending)
                                                    <form method="POST" action="{{ route('employee.tasks.update', $occurrence) }}" data-submit-once>
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="in_progress">
                                                        <input type="hidden" name="task_occurrence_id" value="{{ $occurrence->id }}">
                                                        <button type="submit" class="button-primary w-full" aria-label="Mulai kerjakan: {{ $occurrence->title }}"><i data-lucide="activity" aria-hidden="true"></i>Mulai Kerjakan</button>
                                                    </form>
                                                @endif
                                                <details class="task-completion" @if ($isRestoredTask && $errors->has('employee_note')) open @endif>
                                                    <summary @class(['button-primary w-full' => $occurrence->status === \App\TaskOccurrenceStatus::InProgress, 'task-completion-shortcut' => $occurrence->status === \App\TaskOccurrenceStatus::Pending])>
                                                        <i data-lucide="check" class="h-4 w-4" aria-hidden="true"></i>
                                                        {{ $occurrence->status === \App\TaskOccurrenceStatus::Pending ? 'Sudah selesai?' : 'Selesaikan tugas' }}
                                                    </summary>
                                                    <form method="POST" action="{{ route('employee.tasks.update', $occurrence) }}" class="mt-3 grid gap-3" data-submit-once>
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="status" value="completed">
                                                        <input type="hidden" name="task_occurrence_id" value="{{ $occurrence->id }}">
                                                        <label class="grid gap-2 text-xs font-semibold text-ink-muted">
                                                            Catatan hasil <span class="font-normal">(opsional)</span>
                                                            <textarea name="employee_note" rows="3" maxlength="2000" class="field-control text-sm font-normal" placeholder="Hasil pekerjaan yang perlu diketahui pemilik">{{ $isRestoredTask ? old('employee_note') : '' }}</textarea>
                                                        </label>
                                                        @if ($isRestoredTask)
                                                            @error('employee_note')<p class="text-xs text-red-700" role="alert">{{ $message }}</p>@enderror
                                                        @endif
                                                        <button type="submit" class="button-primary w-full">Simpan & selesaikan</button>
                                                    </form>
                                                </details>
                                            </div>
                                        @else
                                            <span class="inline-flex items-center gap-2 text-xs font-medium text-brand-700"><i data-lucide="circle-check" class="h-4 w-4" aria-hidden="true"></i>Selesai {{ $occurrence->completed_at?->copy()->timezone(\App\Models\Task::TIMEZONE)->format('d M, H:i') }} WIB</span>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        </details>
                    @endif
                @endforeach
                @if (collect($taskGroups)->every(fn ($tasks) => $tasks->isEmpty()))
                    <div class="panel"><x-dashboard.empty-state icon="circle-check" title="Belum ada tugas untuk Anda" description="Tugas dari pemilik usaha akan muncul di sini. Anda tetap dapat mengunggah data penjualan melalui bagian di bawah." /></div>
                @endif
                <div class="panel p-8 text-center" data-task-no-results hidden>
                    <p class="font-semibold text-ink">Tidak ada tugas yang cocok</p><p class="mt-2 text-sm text-ink-muted">Coba judul lain atau tampilkan semua kelompok tugas.</p>
                    <button type="button" class="button-secondary mt-4" data-task-reset>Hapus pencarian & filter</button>
                </div>
            </div>
        </section>

        <section id="sales-upload" aria-labelledby="sales-import-title">
            <div class="panel mb-5 flex flex-wrap items-center justify-between gap-4 p-5">
                <div><h2 class="panel-title">Penjualan offline</h2><p class="mt-2 text-sm text-ink-muted">Catat manual atau unggah banyak transaksi melalui template Excel NADI.</p></div>
                <a href="{{ route('employee.sales-entry.create') }}" class="button-primary">+ Tambah penjualan</a>
            </div>
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Data penjualan</p>
                    <h2 id="sales-import-title" class="section-heading">Unggah dari TikTok Seller atau Shopee</h2>
                </div>
                <span class="section-meta">{{ $salesStatistics['last_import']['label'] }}</span>
            </div>

            <div class="mt-5 grid gap-5 xl:grid-cols-[22rem_minmax(0,1fr)]">
                <form
                    method="POST"
                    action="{{ route('employee.sales-imports.store') }}"
                    enctype="multipart/form-data"
                    class="panel grid content-start gap-5 p-5 sm:p-6"
                    data-submit-once
                >
                    @csrf
                    <div class="flex items-start gap-4">
                        <span class="inline-grid h-11 w-11 shrink-0 place-items-center rounded-xl bg-brand-50 text-brand-700">
                            <i data-lucide="file-chart-column" class="h-5 w-5" aria-hidden="true"></i>
                        </span>
                        <div>
                            <h3 class="text-base font-semibold text-ink">File pesanan CSV atau XLSX</h3>
                            <p class="mt-1 text-xs leading-5 text-ink-muted">Gunakan CSV dari TikTok Seller atau XLSX dari menu Pesanan Shopee Seller.</p>
                        </div>
                    </div>

                    <label class="grid gap-2 text-xs font-semibold text-ink-muted">
                        Pilih file penjualan
                        <input
                            type="file"
                            name="sales_file"
                            data-sales-file
                            accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required
                            class="block w-full rounded-xl border border-line-soft bg-white text-sm font-normal text-ink file:mr-4 file:border-0 file:border-r file:border-line-soft file:bg-canvas file:px-4 file:py-3 file:text-xs file:font-semibold file:text-brand-800 hover:file:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                    </label>

                    <p class="text-xs leading-5 text-ink-muted" data-file-selection role="status">Belum ada file dipilih.</p>
                    @error('sales_file')
                        <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs leading-5 text-red-800" role="alert">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-200">
                        <i data-lucide="database-zap" class="h-4 w-4" aria-hidden="true"></i>
                        Unggah dan perbarui statistik
                    </button>

                    <details class="upload-help"><summary>Cara menyiapkan file penjualan</summary><p class="mt-2 text-xs leading-5 text-ink-muted">Ekspor pesanan dari TikTok Seller dalam format CSV, atau dari menu Pesanan Shopee Seller dalam format XLSX. Pilih file hasil ekspor tersebut, lalu unggah di sini.</p></details>
                    <p class="text-xs leading-5 text-ink-faint">Pesanan dengan nomor yang sama pada platform yang sama akan diperbarui, bukan diduplikasi.</p>
                </form>

                <div class="grid grid-cols-2 gap-4">
                    @foreach ($salesStatistics['metrics'] as $metric)
                        <x-dashboard.metric-card :metric="$metric" />
                    @endforeach
                </div>
            </div>
        </section>

        <section id="sales-summary" aria-labelledby="employee-inventory-title">
            <div class="section-heading-row"><div><p class="section-kicker">Ringkasan unggahan</p><h2 id="employee-inventory-title" class="section-heading">Unit terjual</h2></div><span class="section-meta">Berdasarkan file penjualan</span></div>
            <div class="mt-5"><x-employee.inventory-card :business="$business" :sales-statistics="$salesStatistics" /></div>
        </section>
    </div>
</x-employee.app-shell>
