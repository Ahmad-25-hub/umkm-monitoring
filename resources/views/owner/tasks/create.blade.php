<x-dashboard.app-shell :owner="$owner" :businesses="$businesses" :active-business-id="$activeBusinessId">
    <div class="dashboard-sections">
        <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-kicker">Manajemen tugas</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-[-0.04em] text-ink">Buat tugas baru</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Tetapkan pekerjaan satu kali atau rutin untuk satu maupun beberapa karyawan.</p>
            </div>
        </header>

        @include('owner.tasks._form', [
            'action' => route('tasks.store'),
            'method' => 'POST',
            'submitLabel' => 'Buat tugas',
        ])
    </div>
</x-dashboard.app-shell>
