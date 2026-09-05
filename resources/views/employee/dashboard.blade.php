<x-employee.app-shell :employee="$employee" :business="$business">
    <div class="dashboard-sections">
        @if (session('success'))
            <div class="rounded-2xl border border-brand-100 bg-brand-50 px-5 py-4 text-sm font-medium text-brand-900" role="status">
                {{ session('success') }}
            </div>
        @endif

        <section class="panel overflow-hidden p-6 sm:p-8" aria-labelledby="employee-dashboard-title">
            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-center">
                <div>
                    <p class="section-kicker">Dashboard Karyawan</p>
                    <h1 id="employee-dashboard-title" class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-ink sm:text-4xl">Tugas Anda hari ini.</h1>
                    <p class="mt-4 max-w-2xl text-sm leading-7 text-ink-muted">Lihat pekerjaan dari <strong class="font-semibold text-ink">{{ $business->name }}</strong>, mulai ketika siap, lalu simpan catatan saat menyelesaikannya.</p>
                </div>

                <div class="rounded-2xl border border-brand-100 bg-brand-50 p-5">
                    <span class="inline-grid h-11 w-11 place-items-center rounded-xl bg-white text-brand-700 shadow-sm">
                        <i data-lucide="shield-check" class="h-5 w-5" aria-hidden="true"></i>
                    </span>
                    <p class="mt-4 text-xs font-semibold uppercase tracking-[0.12em] text-brand-700">Membership aktif</p>
                    <p class="mt-2 text-lg font-semibold text-brand-900">{{ $business->name }}</p>
                    <p class="mt-1 truncate text-xs text-brand-700/75">{{ $employee['email'] }}</p>
                </div>
            </div>
        </section>

        <section aria-labelledby="sales-import-title">
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
                            accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            required
                            class="block w-full rounded-xl border border-line-soft bg-white text-sm font-normal text-ink file:mr-4 file:border-0 file:border-r file:border-line-soft file:bg-canvas file:px-4 file:py-3 file:text-xs file:font-semibold file:text-brand-800 hover:file:bg-brand-50 focus:outline-none focus:ring-4 focus:ring-brand-100"
                        >
                    </label>

                    @error('sales_file')
                        <p class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-xs leading-5 text-red-800" role="alert">{{ $message }}</p>
                    @enderror

                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-3 text-sm font-semibold text-white transition hover:bg-brand-800 focus:outline-none focus:ring-4 focus:ring-brand-200">
                        <i data-lucide="database-zap" class="h-4 w-4" aria-hidden="true"></i>
                        Unggah dan perbarui statistik
                    </button>

                    <p class="text-[0.68rem] leading-5 text-ink-faint">Pesanan dengan nomor yang sama pada platform yang sama akan diperbarui, bukan diduplikasi.</p>
                </form>

                <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-2">
                    @foreach ($salesStatistics['metrics'] as $metric)
                        <x-dashboard.metric-card :metric="$metric" />
                    @endforeach
                </div>
            </div>
        </section>

        <section aria-labelledby="employee-inventory-title">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Stok & Persediaan</p>
                    <h2 id="employee-inventory-title" class="section-heading">Monitoring Inventory</h2>
                </div>
                <span class="section-meta">Manajemen stok produk</span>
            </div>

            <div class="mt-5">
                <x-employee.inventory-card :business="$business" :sales-statistics="$salesStatistics" />
            </div>
        </section>

        <section aria-labelledby="employee-tasks-title">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Pekerjaan saya</p>
                    <h2 id="employee-tasks-title" class="section-heading">Daftar tugas</h2>
                </div>
                <span class="section-meta">{{ collect($taskGroups)->sum(fn ($tasks) => $tasks->count()) }} tugas aktif & terbaru</span>
            </div>

            @if ($errors->has('status') || $errors->has('employee_note'))
                <div class="mt-5 rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800" role="alert">{{ $errors->first() }}</div>
            @endif

            <div class="mt-5 grid gap-6">
                @php
                    $groupPresentation = [
                        'new' => ['label' => 'Tugas baru', 'description' => 'Belum pernah Anda buka.', 'tone' => 'text-violet-700 bg-violet-50'],
                        'today' => ['label' => 'Tugas hari ini', 'description' => 'Siap untuk mulai dikerjakan.', 'tone' => 'text-ink bg-canvas'],
                        'in_progress' => ['label' => 'Sedang dikerjakan', 'description' => 'Tugas yang sudah Anda mulai.', 'tone' => 'text-blue-700 bg-blue-50'],
                        'overdue' => ['label' => 'Terlambat', 'description' => 'Lewat tenggat dan perlu diprioritaskan.', 'tone' => 'text-red-700 bg-red-50'],
                        'completed' => ['label' => 'Selesai', 'description' => 'Diselesaikan dalam tujuh hari terakhir.', 'tone' => 'text-brand-700 bg-brand-50'],
                    ];
                @endphp

                @foreach ($groupPresentation as $groupKey => $presentation)
                    <section class="panel overflow-hidden" aria-labelledby="task-group-{{ $groupKey }}">
                        <div class="flex items-center justify-between gap-4 border-b border-line-soft px-5 py-4 sm:px-6">
                            <div>
                                <h3 id="task-group-{{ $groupKey }}" class="text-base font-semibold text-ink">{{ $presentation['label'] }}</h3>
                                <p class="mt-1 text-xs text-ink-muted">{{ $presentation['description'] }}</p>
                            </div>
                            <span class="inline-flex min-w-8 justify-center rounded-full px-2.5 py-1 text-xs font-semibold {{ $presentation['tone'] }}">{{ $taskGroups[$groupKey]->count() }}</span>
                        </div>

                        <div class="divide-y divide-line-soft">
                            @forelse ($taskGroups[$groupKey] as $occurrence)
                                <article class="grid gap-5 px-5 py-5 sm:px-6 lg:grid-cols-[minmax(0,1fr)_18rem] lg:items-start">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span @class([
                                                'rounded-full px-2.5 py-1 text-[0.68rem] font-semibold',
                                                'bg-red-50 text-red-700' => $occurrence->priority === \App\TaskPriority::High,
                                                'bg-amber-50 text-amber-700' => $occurrence->priority === \App\TaskPriority::Normal,
                                                'bg-slate-100 text-slate-600' => $occurrence->priority === \App\TaskPriority::Low,
                                            ])>{{ $occurrence->priority->label() }}</span>
                                            <span class="rounded-full bg-canvas px-2.5 py-1 text-[0.68rem] font-semibold text-ink-muted">{{ $occurrence->task->type->label() }}</span>
                                            <span class="text-xs font-medium {{ $occurrence->isOverdue() ? 'text-red-700' : 'text-ink-faint' }}">
                                                Tenggat {{ $occurrence->due_at->format('d M Y, H:i') }}
                                            </span>
                                        </div>
                                        <h4 class="mt-3 text-base font-semibold text-ink">{{ $occurrence->title }}</h4>
                                        @if ($occurrence->description)
                                            <p class="mt-2 text-sm leading-6 text-ink-muted">{{ $occurrence->description }}</p>
                                        @endif
                                        <p class="mt-3 text-xs text-ink-faint">Diberikan oleh {{ $occurrence->task->creator->name }}</p>

                                        @if ($occurrence->employee_note)
                                            <div class="mt-4 rounded-xl bg-canvas px-4 py-3 text-xs leading-5 text-ink-muted">
                                                <span class="font-semibold text-ink">Catatan Anda:</span> {{ $occurrence->employee_note }}
                                            </div>
                                        @endif
                                    </div>

                                    @if (! in_array($occurrence->status, [\App\TaskOccurrenceStatus::Completed, \App\TaskOccurrenceStatus::Cancelled], true))
                                        <div class="grid gap-2">
                                            @if ($occurrence->status === \App\TaskOccurrenceStatus::Pending)
                                                <form method="POST" action="{{ route('employee.tasks.update', $occurrence) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <input type="hidden" name="status" value="in_progress">
                                                    <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-brand-200 bg-brand-50 px-4 py-2.5 text-sm font-semibold text-brand-800 transition hover:bg-brand-100">
                                                        <i data-lucide="activity" class="h-4 w-4" aria-hidden="true"></i>
                                                        Mulai Kerjakan
                                                    </button>
                                                </form>
                                            @endif

                                            <form method="POST" action="{{ route('employee.tasks.update', $occurrence) }}" class="grid gap-2">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="completed">
                                                <label class="text-xs font-semibold text-ink-muted">
                                                    Catatan <span class="font-normal">(opsional)</span>
                                                    <textarea name="employee_note" rows="2" maxlength="2000" class="mt-1.5 w-full rounded-xl border border-line-soft bg-white px-3 py-2 text-sm font-normal text-ink outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100" placeholder="Hasil atau kendala pekerjaan">{{ old('employee_note') }}</textarea>
                                                </label>
                                                <button type="submit" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-800">
                                                    <i data-lucide="check" class="h-4 w-4" aria-hidden="true"></i>
                                                    Selesaikan
                                                </button>
                                            </form>
                                        </div>
                                    @else
                                        <div class="rounded-xl bg-brand-50 px-4 py-3 text-center text-sm font-semibold text-brand-700">Selesai {{ $occurrence->completed_at?->format('d M, H:i') }}</div>
                                    @endif
                                </article>
                            @empty
                                <p class="px-5 py-8 text-center text-sm text-ink-muted sm:px-6">Tidak ada tugas pada kelompok ini.</p>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>
        </section>
    </div>
</x-employee.app-shell>
