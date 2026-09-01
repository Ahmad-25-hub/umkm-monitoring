<x-dashboard.app-shell :owner="$owner" :businesses="$businesses" :active-business-id="$activeBusinessId">
    <div class="dashboard-sections">
        <header>
            <p class="section-kicker">Manajemen tugas</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-ink">Edit tugas</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Occurrence yang sudah mulai atau selesai tetap memakai snapshot sebelumnya.</p>
        </header>

        @include('owner.tasks._form', [
            'action' => route('tasks.update', $task),
            'method' => 'PUT',
            'submitLabel' => 'Simpan perubahan',
        ])
    </div>
</x-dashboard.app-shell>
