<div {{ $attributes->class(['landing-preview']) }} data-landing-preview>
    <div class="landing-preview-topbar">
        <div class="flex items-center gap-2"><span class="landing-preview-appmark" aria-hidden="true"><i data-lucide="activity"></i></span><strong>NADI</strong><span class="landing-preview-divider"></span><span class="text-ink-muted">Ruang usaha Anda</span></div>
        <span class="landing-preview-example">DATA CONTOH</span>
    </div>
    <div class="landing-preview-workspace">
        <div class="landing-preview-sidebar" aria-hidden="true">
            <span class="is-active"><i data-lucide="layout-dashboard"></i></span>
            <span><i data-lucide="chart-no-axes-combined"></i></span>
            <span><i data-lucide="users-round"></i></span>
            <span><i data-lucide="sparkles"></i></span>
            <span class="mt-auto"><i data-lucide="settings-2"></i></span>
        </div>
        <div class="landing-preview-main">
            <div class="landing-preview-greeting"><div><span>USAHA CONTOH</span><h2>Kedai Senja <span aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 4V44M4 24H44M10 10L38 38M38 10L10 38" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg></span></h2></div><span class="landing-avatar bg-[#e7eddc]">KS</span></div>
            <div class="landing-preview-tabs" role="tablist" aria-label="Jelajahi contoh fitur Nadi" data-preview-tabs hidden>
                <button type="button" id="preview-tab-overview" role="tab" aria-selected="true" aria-controls="preview-overview" data-preview-tab="overview">Ringkasan</button>
                <button type="button" id="preview-tab-team" role="tab" aria-selected="false" aria-controls="preview-team" tabindex="-1" data-preview-tab="team">Aktivitas tim</button>
                <button type="button" id="preview-tab-insight" role="tab" aria-selected="false" aria-controls="preview-insight" tabindex="-1" data-preview-tab="insight"><i data-lucide="sparkles" aria-hidden="true"></i> AI insight</button>
            </div>

            <div class="landing-preview-panels">
                <section id="preview-overview" class="landing-preview-panel" aria-label="Contoh ringkasan usaha" data-preview-panel="overview">
                    <div class="landing-preview-welcome"><span class="landing-preview-welcome-icon"><i data-lucide="trending-up" aria-hidden="true"></i></span><div><strong>Hari baru, peluang baru.</strong><p>Yuk, lihat kabar usaha Anda hari ini.</p></div><i data-lucide="arrow-up-right" class="ml-auto size-4" aria-hidden="true"></i></div>
                    <div class="landing-preview-metrics">
                        <div><span>Penjualan hari ini</span><strong>Rp1.250.000</strong><small><i data-lucide="trending-up" aria-hidden="true"></i> 12,5% dari kemarin</small></div>
                        <div><span>Pesanan tercatat</span><strong>38 <em>pesanan</em></strong><small>8 pesanan dari toko</small></div>
                    </div>
                    <div class="landing-preview-chart">
                        <div class="flex items-center justify-between gap-3"><strong>Performa penjualan</strong><span>7 hari terakhir</span></div>
                        <svg viewBox="0 0 420 145" class="landing-preview-chart-svg" role="img" aria-label="Grafik contoh: penjualan berfluktuasi dan meningkat menjelang akhir minggu">
                            <defs><linearGradient id="landing-chart-fill" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="#76b794" stop-opacity=".3"/><stop offset="100%" stop-color="#76b794" stop-opacity=".01"/></linearGradient></defs>
                            <g stroke="#e9eee8" stroke-dasharray="3 5"><path d="M0 25H420"/><path d="M0 65H420"/><path d="M0 105H420"/></g>
                            <path d="M4 114C30 114 40 73 68 78S110 116 138 85S185 74 208 60S250 87 278 56S317 70 348 34S389 49 416 17V140H4Z" fill="url(#landing-chart-fill)"/>
                            <path class="landing-chart-stroke" d="M4 114C30 114 40 73 68 78S110 116 138 85S185 74 208 60S250 87 278 56S317 70 348 34S389 49 416 17" fill="none" stroke="#347b5b" stroke-width="3" stroke-linecap="round"/>
                            <circle cx="348" cy="34" r="6" fill="#347b5b" stroke="white" stroke-width="3"/>
                            <g><rect x="291" y="0" width="97" height="22" rx="6" fill="#183e30"/><text x="339.5" y="14.5" text-anchor="middle" fill="white" font-size="9" font-family="sans-serif">Rp1.450.000</text></g>
                        </svg>
                        <div class="landing-preview-chart-days"><span>Sen</span><span>Sel</span><span>Rab</span><span>Kam</span><span>Jum</span><span>Sab</span><span>Min</span></div>
                    </div>
                    <div class="landing-preview-bottom"><span><i data-lucide="circle-check" aria-hidden="true"></i> Tugas tim hari ini</span><strong>8 dari 10 selesai</strong></div>
                    <div class="landing-preview-progress"><span></span></div>
                </section>
    
                <section id="preview-team" class="landing-preview-panel" role="tabpanel" aria-labelledby="preview-tab-team" tabindex="0" data-preview-panel="team" hidden>
                    <div class="landing-preview-welcome"><span class="landing-preview-welcome-icon"><i data-lucide="users-round" aria-hidden="true"></i></span><div><strong>Tim terarah, usaha bergerak.</strong><p>Setiap tugas punya penanggung jawab.</p></div></div>
                    <div class="landing-preview-metrics"><div><span>Tugas selesai</span><strong>8 <em>dari 10</em></strong><small>Progres hari ini</small></div><div><span>Anggota tim</span><strong>3 <em>karyawan</em></strong><small>Dalam usaha ini</small></div></div>
                    <div class="landing-preview-team-list">
                        <div><span class="landing-avatar bg-[#e4ead8]">R</span><span><strong>Siapkan pesanan hari ini</strong><small>Rani · 09.00 WIB</small></span><span class="landing-preview-task-status">Selesai</span></div>
                        <div><span class="landing-avatar bg-[#f1e4d6]">D</span><span><strong>Catat penjualan toko</strong><small>Dimas · 12.00 WIB</small></span><span class="landing-preview-task-status">Selesai</span></div>
                        <div><span class="landing-avatar bg-[#e0e8eb]">S</span><span><strong>Rapikan area display</strong><small>Sari · 15.00 WIB</small></span><span class="landing-preview-task-status is-pending">Proses</span></div>
                    </div>
                    <p class="landing-preview-panel-note"><i data-lucide="shield-check" aria-hidden="true"></i> Ruang kerja sesuai peran anggota tim.</p>
                </section>
    
                <section id="preview-insight" class="landing-preview-panel" role="tabpanel" aria-labelledby="preview-tab-insight" tabindex="0" data-preview-panel="insight" hidden>
                    <div class="landing-preview-ai-heading"><span><i data-lucide="sparkles" aria-hidden="true"></i></span><strong>Tanya Nadi.<small>Lebih dekat dengan data usaha Anda.</small></strong></div>
                    <div class="landing-preview-ai-question">Apa yang perlu saya perhatikan minggu ini?</div>
                    <div class="landing-preview-ai-answer"><strong><i data-lucide="sparkles" aria-hidden="true"></i> Nadi AI <span>Contoh jawaban</span></strong><p>Dari data contoh Kedai Senja, penjualan paling tinggi terjadi pada akhir pekan.</p><p>Pertimbangkan menyiapkan tim sebelum jam ramai dan periksa kembali tugas yang belum selesai.</p><span class="landing-preview-ai-source"><i data-lucide="file-chart-column" aria-hidden="true"></i> Berdasarkan contoh penjualan & tugas</span></div>
                    <p class="landing-preview-panel-note">Insight membantu pertimbangan. Anda tetap menentukan.</p>
                </section>
            </div>
        </div>
    </div>
</div>
