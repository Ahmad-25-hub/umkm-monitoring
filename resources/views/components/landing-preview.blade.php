<div {{ $attributes->class(['landing-preview']) }} data-landing-preview>
    <div class="landing-preview-topbar">
        <div class="flex items-center gap-2">
            <span class="landing-preview-appmark" aria-hidden="true"><i data-lucide="activity"></i></span>
            <strong>NADI</strong>
            <span class="landing-preview-divider"></span>
            <span class="text-ink-muted">Ruang usaha Anda</span>
        </div>
        <div class="flex items-center gap-2">
            <span class="landing-preview-live-badge"><span class="landing-live-dot"></span> SIMULASI LIVE</span>
            <span class="landing-preview-example">DATA CONTOH</span>
        </div>
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
            <div class="landing-preview-greeting">
                <div>
                    <span>USAHA CONTOH</span>
                    <h2>Kedai Senja <span aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 4V44M4 24H44M10 10L38 38M38 10L10 38" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg></span></h2>
                </div>
                <div class="flex items-center gap-2">
                    <span class="landing-preview-mode-tag hidden sm:inline-flex"><i data-lucide="sparkles" class="size-3"></i> Coba interaktif</span>
                    <span class="landing-avatar bg-[#e7eddc]">KS</span>
                </div>
            </div>
            <div class="landing-preview-tabs" role="tablist" aria-label="Jelajahi contoh fitur Nadi" data-preview-tabs hidden>
                <button type="button" id="preview-tab-overview" role="tab" aria-selected="true" aria-controls="preview-overview" data-preview-tab="overview">
                    <i data-lucide="layout-dashboard" class="size-3"></i> Ringkasan
                </button>
                <button type="button" id="preview-tab-team" role="tab" aria-selected="false" aria-controls="preview-team" tabindex="-1" data-preview-tab="team">
                    <i data-lucide="users-round" class="size-3"></i> Aktivitas tim
                    <span class="landing-tab-counter" data-tab-task-count>8/10</span>
                </button>
                <button type="button" id="preview-tab-insight" role="tab" aria-selected="false" aria-controls="preview-insight" tabindex="-1" data-preview-tab="insight">
                    <i data-lucide="sparkles" class="size-3"></i> AI insight
                </button>
            </div>

            <div class="landing-preview-panels">
                {{-- Panel 1: Ringkasan --}}
                <section id="preview-overview" class="landing-preview-panel" aria-label="Contoh ringkasan usaha" data-preview-panel="overview">
                    <div class="landing-preview-welcome">
                        <span class="landing-preview-welcome-icon"><i data-lucide="trending-up" aria-hidden="true"></i></span>
                        <div>
                            <strong>Hari baru, peluang baru.</strong>
                            <p>Yuk, lihat kabar usaha Anda hari ini.</p>
                        </div>
                        <span class="landing-preview-badge-status ml-auto flex items-center gap-1.5"><span class="landing-live-dot"></span> Aktif</span>
                    </div>
                    <div class="landing-preview-metrics">
                        <div class="landing-metric-card" data-metric-card="sales">
                            <span>Penjualan hari ini</span>
                            <strong data-overview-sales-val>Rp1.250.000</strong>
                            <small><i data-lucide="trending-up" aria-hidden="true"></i> 12,5% dari kemarin</small>
                        </div>
                        <div class="landing-metric-card" data-metric-card="orders">
                            <span>Pesanan tercatat</span>
                            <strong>38 <em>pesanan</em></strong>
                            <small>8 pesanan dari toko</small>
                        </div>
                    </div>
                    <div class="landing-preview-chart" data-landing-chart-container>
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex items-center gap-2">
                                <strong>Performa penjualan</strong>
                                <span class="landing-chart-hint">Sentuh titik untuk detail</span>
                            </div>
                            <span class="landing-chart-sub">7 hari terakhir</span>
                        </div>
                        <div class="landing-preview-chart-wrapper">
                            <svg viewBox="0 0 420 145" class="landing-preview-chart-svg" role="img" aria-label="Grafik contoh: penjualan berfluktuasi dan meningkat menjelang akhir minggu">
                                <defs>
                                    <linearGradient id="landing-chart-fill" x1="0" y1="0" x2="0" y2="1">
                                        <stop offset="0%" stop-color="#76b794" stop-opacity=".35"/>
                                        <stop offset="100%" stop-color="#76b794" stop-opacity=".01"/>
                                    </linearGradient>
                                </defs>
                                <g stroke="#e9eee8" stroke-dasharray="3 5">
                                    <path d="M0 25H420"/>
                                    <path d="M0 65H420"/>
                                    <path d="M0 105H420"/>
                                </g>
                                <path d="M4 114C30 114 40 73 68 78S110 116 138 85S185 74 208 60S250 87 278 56S317 70 348 34S389 49 416 17V140H4Z" fill="url(#landing-chart-fill)"/>
                                <path class="landing-chart-stroke" d="M4 114C30 114 40 73 68 78S110 116 138 85S185 74 208 60S250 87 278 56S317 70 348 34S389 49 416 17" fill="none" stroke="#347b5b" stroke-width="3" stroke-linecap="round"/>
                                
                                {{-- Interactive Data Dots --}}
                                @php
                                    $chartPoints = [
                                        ['x' => 14, 'y' => 114, 'day' => 'Senin', 'val' => 'Rp620.000', 'note' => 'Awal pekan'],
                                        ['x' => 75, 'y' => 78, 'day' => 'Selasa', 'val' => 'Rp890.000', 'note' => 'Stabil'],
                                        ['x' => 142, 'y' => 86, 'day' => 'Rabu', 'val' => 'Rp810.000', 'note' => 'Stabil'],
                                        ['x' => 208, 'y' => 60, 'day' => 'Kamis', 'val' => 'Rp1.150.000', 'note' => 'Mulai naik'],
                                        ['x' => 278, 'y' => 56, 'day' => 'Jumat', 'val' => 'Rp1.220.000', 'note' => 'Ramai sore'],
                                        ['x' => 348, 'y' => 34, 'day' => 'Sabtu', 'val' => 'Rp1.450.000', 'note' => 'Puncak toko'],
                                        ['x' => 412, 'y' => 17, 'day' => 'Minggu', 'val' => 'Rp1.680.000', 'note' => 'Tertinggi'],
                                    ];
                                @endphp

                                @foreach($chartPoints as $idx => $pt)
                                    <circle class="landing-chart-dot @if($idx === 5) is-active @endif" cx="{{ $pt['x'] }}" cy="{{ $pt['y'] }}" r="6" data-chart-dot="{{ $idx }}" data-day="{{ $pt['day'] }}" data-val="{{ $pt['val'] }}" data-note="{{ $pt['note'] }}" data-cx="{{ $pt['x'] }}" data-cy="{{ $pt['y'] }}" tabindex="0" role="button" aria-label="{{ $pt['day'] }}: {{ $pt['val'] }}"/>
                                @endforeach

                                {{-- Dynamic Interactive Tooltip Box in SVG --}}
                                <g class="landing-chart-tooltip-group" data-chart-tooltip transform="translate(291, 0)">
                                    <rect x="0" y="0" width="112" height="24" rx="6" fill="#183e30" class="landing-chart-tooltip-bg"/>
                                    <text x="56" y="15.5" text-anchor="middle" fill="white" font-size="9.5" font-weight="600" font-family="sans-serif" data-chart-tooltip-text>Sab: Rp1.450.000</text>
                                </g>
                            </svg>
                        </div>
                        <div class="landing-preview-chart-days">
                            @foreach(['Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab', 'Min'] as $dIdx => $dLabel)
                                <button type="button" class="landing-day-btn @if($dIdx === 5) is-active @endif" data-day-btn="{{ $dIdx }}" aria-label="Lihat data {{ $dLabel }}">
                                    {{ $dLabel }}
                                </button>
                            @endforeach
                        </div>
                    </div>
                    <div class="landing-preview-bottom">
                        <span><i data-lucide="circle-check" aria-hidden="true"></i> Tugas tim hari ini</span>
                        <strong data-overview-task-label>8 dari 10 selesai</strong>
                    </div>
                    <div class="landing-preview-progress" aria-label="Progres tugas tim">
                        <span data-overview-progress-bar style="width: 80%"></span>
                    </div>
                </section>
    
                {{-- Panel 2: Aktivitas Tim (Interactive checklist) --}}
                <section id="preview-team" class="landing-preview-panel" role="tabpanel" aria-labelledby="preview-tab-team" tabindex="0" data-preview-panel="team" hidden>
                    <div class="landing-preview-welcome">
                        <span class="landing-preview-welcome-icon"><i data-lucide="users-round" aria-hidden="true"></i></span>
                        <div>
                            <strong>Tim terarah, usaha bergerak.</strong>
                            <p>Klik tugas di bawah untuk coba tandai selesai!</p>
                        </div>
                        <button type="button" class="landing-preview-reset-btn" data-reset-tasks title="Reset simulasi tugas">
                            <i data-lucide="clock-3" class="size-3"></i> Reset
                        </button>
                    </div>
                    <div class="landing-preview-metrics">
                        <div class="landing-metric-card">
                            <span>Tugas selesai</span>
                            <strong data-team-done-count>8 <em>dari 10</em></strong>
                            <small class="text-brand-600"><i data-lucide="circle-check" class="size-3"></i> <span data-team-percent-label>80% selesai</span></small>
                        </div>
                        <div class="landing-metric-card">
                            <span>Anggota tim</span>
                            <strong>3 <em>karyawan</em></strong>
                            <small>Aktif bertugas</small>
                        </div>
                    </div>
                    <div class="landing-preview-team-list" data-interactive-task-list>
                        <div class="landing-interactive-task is-done" data-task-item="1" role="button" tabindex="0" aria-label="Tandai status tugas Rani">
                            <button type="button" class="landing-task-checkbox" aria-label="Toggle status tugas"><i data-lucide="check"></i></button>
                            <span class="landing-avatar bg-[#e4ead8]">R</span>
                            <div class="landing-task-info">
                                <strong>Siapkan pesanan hari ini</strong>
                                <small>Rani · 09.00 WIB</small>
                            </div>
                            <span class="landing-preview-task-status" data-task-status-badge>Selesai</span>
                        </div>
                        <div class="landing-interactive-task is-done" data-task-item="2" role="button" tabindex="0" aria-label="Tandai status tugas Dimas">
                            <button type="button" class="landing-task-checkbox" aria-label="Toggle status tugas"><i data-lucide="check"></i></button>
                            <span class="landing-avatar bg-[#f1e4d6]">D</span>
                            <div class="landing-task-info">
                                <strong>Catat penjualan toko</strong>
                                <small>Dimas · 12.00 WIB</small>
                            </div>
                            <span class="landing-preview-task-status" data-task-status-badge>Selesai</span>
                        </div>
                        <div class="landing-interactive-task is-pending" data-task-item="3" role="button" tabindex="0" aria-label="Tandai status tugas Sari">
                            <button type="button" class="landing-task-checkbox" aria-label="Toggle status tugas"><i data-lucide="check"></i></button>
                            <span class="landing-avatar bg-[#e0e8eb]">S</span>
                            <div class="landing-task-info">
                                <strong>Rapikan area display</strong>
                                <small>Sari · 15.00 WIB</small>
                            </div>
                            <span class="landing-preview-task-status is-pending" data-task-status-badge>Proses</span>
                        </div>
                    </div>
                    <p class="landing-preview-panel-note"><i data-lucide="shield-check" aria-hidden="true"></i> Ruang kerja sesuai peran anggota tim. Klik tugas untuk mengubah status.</p>
                </section>
    
                {{-- Panel 3: AI Insight (Interactive prompts) --}}
                <section id="preview-insight" class="landing-preview-panel" role="tabpanel" aria-labelledby="preview-tab-insight" tabindex="0" data-preview-panel="insight" hidden>
                    <div class="landing-preview-ai-heading">
                        <span><i data-lucide="sparkles" aria-hidden="true"></i></span>
                        <div>
                            <strong>Tanya Nadi AI.<small>Pilih pertanyaan di bawah untuk melihat analisis langsung:</small></strong>
                        </div>
                    </div>
                    
                    {{-- Interactive prompt chips --}}
                    <div class="landing-preview-ai-chips" role="group" aria-label="Pilih pertanyaan AI contoh">
                        <button type="button" class="landing-ai-chip is-active" data-ai-chip="trend">
                            <i data-lucide="trending-up" class="size-3"></i> Tren minggu ini?
                        </button>
                        <button type="button" class="landing-ai-chip" data-ai-chip="products">
                            <i data-lucide="package" class="size-3"></i> Produk terlaris?
                        </button>
                        <button type="button" class="landing-ai-chip" data-ai-chip="team">
                            <i data-lucide="users-round" class="size-3"></i> Evaluasi tim?
                        </button>
                    </div>

                    <div class="landing-preview-ai-dialogue" data-ai-dialogue>
                        <div class="landing-preview-ai-question" data-ai-question-bubble>
                            Apa yang perlu saya perhatikan minggu ini?
                        </div>
                        <div class="landing-preview-ai-answer" data-ai-answer-bubble>
                            <strong>
                                <i data-lucide="sparkles" aria-hidden="true"></i> Nadi AI
                                <span class="landing-ai-time-tag">Baru saja</span>
                            </strong>
                            <div class="landing-ai-text" data-ai-text>
                                <p>Dari data contoh Kedai Senja, penjualan paling tinggi terjadi pada akhir pekan (Sabtu & Minggu mencapai 42% omzet mingguan).</p>
                                <p class="mt-2">Pertimbangkan menyiapkan tim sebelum jam ramai dan periksa kembali tugas yang belum selesai agar pelayanan tetap optimal.</p>
                            </div>
                            <span class="landing-preview-ai-source" data-ai-source>
                                <i data-lucide="file-chart-column" aria-hidden="true"></i> Berdasarkan contoh penjualan 7 hari & laporan tugas
                            </span>
                        </div>
                    </div>
                    <p class="landing-preview-panel-note"><i data-lucide="sparkles" aria-hidden="true"></i> AI mendampingi data tercatat untuk membantu pertimbangan Anda.</p>
                </section>
            </div>
        </div>
    </div>
</div>
