<x-dashboard.app-shell :owner="$owner" :businesses="$businesses" :active-business-id="$activeBusinessId">
    <div class="dashboard-sections">
        <div class="overview-toolbar">
            <p class="eyebrow"><i data-lucide="calendar-days" class="h-4 w-4" aria-hidden="true"></i>{{ $overview['dateLabel'] }}</p>
            <a href="{{ route('sales-entry.create') }}" class="button-secondary">+ Tambah penjualan</a>
            <a href="{{ route('tasks.create') }}" class="button-primary"><i data-lucide="plus" aria-hidden="true"></i>Buat tugas</a>
        </div>

        @if (session('success'))
            <div class="feedback-success" role="status">{{ session('success') }}</div>
        @endif

        <section aria-labelledby="overview-title"><x-dashboard.business-health-card :owner="$owner" :overview="$overview" /></section>

        <section aria-labelledby="metrics-heading">
            <div class="section-heading-row">
                <div><p class="section-kicker">Angka utama</p><h2 id="metrics-heading" class="section-heading">Ringkasan hari ini</h2></div>
                <span class="section-meta">{{ $lastUpdatedLabel }}</span>
            </div>
            <div class="metrics-grid mt-5">
                @if ($isLoading)
                    @for ($card = 0; $card < 4; $card++)<x-dashboard.loading-state variant="kpi" />@endfor
                @elseif (count($metrics))
                    @foreach ($metrics as $metric)<x-dashboard.metric-card :metric="$metric" />@endforeach
                @else
                    <div class="panel metrics-empty">
                        <x-dashboard.empty-state icon="chart-no-axes-combined" title="Belum ada ringkasan bisnis" description="Minta karyawan mengunggah file pesanan TikTok Seller atau Shopee untuk melihat ringkasan penjualan." action="Lihat anggota tim" href="#team" compact />
                    </div>
                @endif
            </div>
        </section>

        <section aria-label="Hal yang perlu diperhatikan"><x-dashboard.alert-card :alerts="$alerts" :loading="$isLoading" /></section>
        <section aria-label="Tren penjualan"><x-dashboard.sales-chart :sales="$sales" :loading="$isLoading" /></section>
        <section aria-label="Catatan usaha"><x-dashboard.insight-card :insights="$insights" :loading="$isLoading" /></section>

        <section id="team" aria-label="Anggota tim">
            @error('status')
                <p class="mb-4 text-sm text-critical" role="alert">{{ $message }}</p>
            @enderror
            <x-dashboard.employee-performance :employees="$employees" :loading="$isLoading" />
        </section>
        <section id="team-access" aria-label="Undang karyawan">
            <x-dashboard.invitation-code-card :business="$activeBusiness" />
        </section>
        <x-dashboard.ai-ask-card :suggestions="$aiSuggestions" />
    </div>
</x-dashboard.app-shell>
