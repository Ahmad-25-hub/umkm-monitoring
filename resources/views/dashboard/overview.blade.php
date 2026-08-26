<x-dashboard.app-shell
    :owner="$owner"
    :businesses="$businesses"
    :active-business-id="$activeBusinessId"
>
    <div class="space-y-8 lg:space-y-10">
        <section class="overview-toolbar" aria-label="Periode ringkasan">
            <div class="eyebrow">
                <span class="status-dot" aria-hidden="true"></span>
                {{ $overview['dateLabel'] }}
            </div>
            <button type="button" class="button-secondary">
                <i data-lucide="calendar-days" aria-hidden="true"></i>
                <span>Hari ini</span>
                <i data-lucide="chevron-down" aria-hidden="true"></i>
            </button>
        </section>

        <section aria-labelledby="overview-title">
            <x-dashboard.business-health-card
                :owner="$owner"
                :overview="$overview"
            />
        </section>

        <section aria-labelledby="metrics-heading">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Sinyal utama</p>
                    <h2 id="metrics-heading" class="section-heading">Ringkasan hari ini</h2>
                </div>
                <span class="section-meta">Diperbarui 10 menit lalu</span>
            </div>

            <div class="mt-5 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                @foreach ($metrics as $metric)
                    <x-dashboard.metric-card :metric="$metric" />
                @endforeach
            </div>
        </section>

        <section aria-label="Performa penjualan">
            <x-dashboard.sales-chart :sales="$sales" />
        </section>

        <section aria-label="Insight NADI">
            <x-dashboard.insight-card :insights="$insights" />
        </section>

        <section class="dashboard-grid" aria-label="Performa tim dan perhatian bisnis">
            <x-dashboard.employee-performance :employees="$employees" class="lg:col-span-7" />
            <x-dashboard.alert-card :alerts="$alerts" class="lg:col-span-5" />
        </section>

        <section aria-label="Tanya NADI">
            <x-dashboard.ai-ask-card :suggestions="$aiSuggestions" />
        </section>
    </div>
</x-dashboard.app-shell>
