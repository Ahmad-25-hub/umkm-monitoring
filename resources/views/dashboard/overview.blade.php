<x-dashboard.app-shell
    :owner="$owner"
    :businesses="$businesses"
    :active-business-id="$activeBusinessId"
>
    <div class="dashboard-sections">
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

        <x-dashboard.invitation-code-card :business="$activeBusiness" />

        <section aria-labelledby="metrics-heading">
            <div class="section-heading-row">
                <div>
                    <p class="section-kicker">Sinyal utama</p>
                    <h2 id="metrics-heading" class="section-heading">Ringkasan hari ini</h2>
                </div>
                <span class="section-meta">{{ $lastUpdatedLabel }}</span>
            </div>

            <div class="metrics-grid mt-5">
                @if ($isLoading)
                    @for ($card = 0; $card < 4; $card++)
                        <x-dashboard.loading-state variant="kpi" />
                    @endfor
                @elseif (count($metrics))
                    @foreach ($metrics as $metric)
                        <x-dashboard.metric-card :metric="$metric" />
                    @endforeach
                @else
                    <div class="panel metrics-empty">
                        <x-dashboard.empty-state
                            icon="chart-no-axes-combined"
                            title="Belum ada ringkasan bisnis"
                            description="Hubungkan data penjualan untuk mulai melihat metrik utama bisnis Anda."
                            action="Hubungkan data penjualan"
                            compact
                        />
                    </div>
                @endif
            </div>
        </section>

        <section aria-label="Hal yang perlu diperhatikan">
            <x-dashboard.alert-card :alerts="$alerts" :loading="$isLoading" />
        </section>

        <section aria-label="Insight NADI">
            <x-dashboard.insight-card :insights="$insights" :loading="$isLoading" />
        </section>

        <section aria-label="Performa tim">
            <x-dashboard.employee-performance :employees="$employees" :loading="$isLoading" />
        </section>

        <section aria-label="Analitik penjualan terperinci">
            <x-dashboard.sales-chart :sales="$sales" :loading="$isLoading" />
        </section>

        <section aria-label="Tanya NADI">
            <x-dashboard.ai-ask-card :suggestions="$aiSuggestions" />
        </section>
    </div>
</x-dashboard.app-shell>
