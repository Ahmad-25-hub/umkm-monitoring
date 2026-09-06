@php
    $selectedType = old('type', isset($task) ? $task->type->value : \App\TaskType::OneTime->value);
    $selectedPriority = old('priority', isset($task) ? $task->priority->value : \App\TaskPriority::Normal->value);
    $selectedAssigneeIds = collect(old('assignee_ids', isset($task) ? $task->assignees->pluck('id')->all() : []))
        ->map(fn ($id): string => (string) $id)
        ->all();
@endphp

<form method="POST" action="{{ $action }}" class="grid gap-6" data-task-form data-submit-once>
    @csrf
    @if ($method !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="rounded-2xl border border-red-200 bg-red-50 px-5 py-4 text-sm text-red-800" role="alert">
            <p class="font-semibold">Periksa kembali data tugas.</p>
            <ul class="mt-2 list-disc space-y-1 pl-5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <section class="panel p-6 sm:p-8" aria-labelledby="task-details-heading">
        <div class="border-b border-line-soft pb-5">
            <p class="section-kicker">Detail pekerjaan</p>
            <h2 id="task-details-heading" class="mt-2 text-xl font-semibold text-ink">Apa yang perlu dikerjakan?</h2>
        </div>

        <div class="mt-6 grid gap-5">
            <label class="grid gap-2 text-sm font-semibold text-ink">
                Judul tugas
                <input name="title" value="{{ old('title', $task->title ?? '') }}" required maxlength="255" class="rounded-xl border border-line-soft bg-white px-4 py-3 font-normal outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100" placeholder="Contoh: Periksa stok etalase">
            </label>

            <label class="grid gap-2 text-sm font-semibold text-ink">
                Deskripsi <span class="font-normal text-ink-faint">(opsional)</span>
                <textarea name="description" rows="4" maxlength="2000" class="rounded-xl border border-line-soft bg-white px-4 py-3 font-normal leading-6 outline-none transition focus:border-brand-500 focus:ring-4 focus:ring-brand-100" placeholder="Tambahkan langkah kerja atau hasil yang diharapkan.">{{ old('description', $task->description ?? '') }}</textarea>
            </label>

            <div class="grid gap-5 sm:grid-cols-2">
                <label class="grid gap-2 text-sm font-semibold text-ink">
                    Jenis tugas
                    <select name="type" required class="rounded-xl border border-line-soft bg-white px-4 py-3 font-normal outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100" data-task-type>
                        @foreach ($types as $type)
                            <option value="{{ $type->value }}" @selected($selectedType === $type->value)>{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="grid gap-2 text-sm font-semibold text-ink">
                    Prioritas
                    <select name="priority" required class="rounded-xl border border-line-soft bg-white px-4 py-3 font-normal outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
                        @foreach ($priorities as $priority)
                            <option value="{{ $priority->value }}" @selected($selectedPriority === $priority->value)>{{ $priority->label() }}</option>
                        @endforeach
                    </select>
                </label>
            </div>
        </div>
    </section>

    <section class="panel p-6 sm:p-8" aria-labelledby="task-schedule-heading">
        <div class="border-b border-line-soft pb-5">
            <p class="section-kicker">Jadwal</p>
            <h2 id="task-schedule-heading" class="mt-2 text-xl font-semibold text-ink">Kapan tugas berlaku?</h2>
            <p class="mt-2 text-sm leading-6 text-ink-muted">Tugas harian dimulai lagi dengan status Belum dikerjakan setiap pukul 00.00 WIB selama jadwal aktif. Progres dan catatan hari sebelumnya tetap tersimpan.</p>
        </div>

        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <label class="grid gap-2 text-sm font-semibold text-ink">
                Tanggal mulai
                <input type="date" name="starts_on" value="{{ old('starts_on', isset($task) ? $task->starts_on->format('Y-m-d') : today(\App\Models\Task::TIMEZONE)->format('Y-m-d')) }}" required class="rounded-xl border border-line-soft bg-white px-4 py-3 font-normal outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </label>

            <label class="grid gap-2 text-sm font-semibold text-ink" data-one-time-field>
                Tenggat tugas (WIB)
                <input type="datetime-local" name="due_at" value="{{ old('due_at', isset($task) && $task->due_at ? $task->due_at->format('Y-m-d\TH:i') : '') }}" class="rounded-xl border border-line-soft bg-white px-4 py-3 font-normal outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </label>

            <label class="grid gap-2 text-sm font-semibold text-ink" data-daily-field>
                Jam tenggat harian (WIB)
                <input type="time" name="daily_due_time" value="{{ old('daily_due_time', isset($task) ? substr((string) $task->daily_due_time, 0, 5) : '17:00') }}" class="rounded-xl border border-line-soft bg-white px-4 py-3 font-normal outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </label>

            <label class="grid gap-2 text-sm font-semibold text-ink" data-daily-field>
                Tanggal selesai <span class="font-normal text-ink-faint">(opsional)</span>
                <input type="date" name="ends_on" value="{{ old('ends_on', isset($task) && $task->ends_on ? $task->ends_on->format('Y-m-d') : '') }}" class="rounded-xl border border-line-soft bg-white px-4 py-3 font-normal outline-none focus:border-brand-500 focus:ring-4 focus:ring-brand-100">
            </label>
        </div>

        <label class="mt-5 flex items-start gap-3 rounded-2xl border border-line-soft bg-canvas px-4 py-4">
            <input type="hidden" name="is_active" value="0">
            <input type="checkbox" name="is_active" value="1" @checked((bool) old('is_active', $task->is_active ?? true)) class="mt-0.5 h-4 w-4 rounded border-line-soft text-brand-700 focus:ring-brand-500">
            <span>
                <span class="block text-sm font-semibold text-ink">Tugas aktif</span>
                <span class="mt-1 block text-xs leading-5 text-ink-muted">Jika dinonaktifkan, tugas tidak akan dibuat otomatis setiap hari.</span>
            </span>
        </label>
    </section>

    <section class="panel p-6 sm:p-8" aria-labelledby="task-assignees-heading">
        <div class="border-b border-line-soft pb-5">
            <p class="section-kicker">Penerima</p>
            <h2 id="task-assignees-heading" class="mt-2 text-xl font-semibold text-ink">Pilih karyawan</h2>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            @forelse ($employees as $membership)
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-line-soft bg-white px-4 py-4 transition hover:border-brand-200 hover:bg-brand-50/40">
                    <input type="checkbox" name="assignee_ids[]" value="{{ $membership->user_id }}" @checked(in_array((string) $membership->user_id, $selectedAssigneeIds, true)) class="mt-0.5 h-4 w-4 rounded border-line-soft text-brand-700 focus:ring-brand-500">
                    <span class="min-w-0">
                        <span class="block truncate text-sm font-semibold text-ink">{{ $membership->user->name }}</span>
                        <span class="mt-1 block truncate text-xs text-ink-muted">{{ $membership->user->email }}</span>
                    </span>
                </label>
            @empty
                <div class="sm:col-span-2 rounded-2xl border border-dashed border-line-soft bg-canvas px-5 py-8 text-center text-sm text-ink-muted">
                    Belum ada karyawan aktif pada usaha ini. Undang karyawan melalui bagian tim pada halaman ringkasan.
                </div>
            @endforelse
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('tasks.index') }}" class="inline-flex items-center justify-center rounded-xl border border-line-soft bg-white px-5 py-3 text-sm font-semibold text-ink transition hover:bg-canvas">Batal</a>
        <button type="submit" class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-700 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-800 disabled:cursor-not-allowed disabled:opacity-50" @disabled($employees->isEmpty())>
            <i data-lucide="check" class="h-4 w-4" aria-hidden="true"></i>
            {{ $submitLabel }}
        </button>
    </div>
</form>
