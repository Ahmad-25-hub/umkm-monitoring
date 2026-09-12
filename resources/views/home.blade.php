<!DOCTYPE html>
<html lang="id" class="nadi-landing-document">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="description" content="Kenali Nadi, ruang usaha untuk pelaku UMKM. Catat penjualan, pantau tugas tim, dan pahami perkembangan usaha dalam satu dashboard.">
        <meta name="theme-color" content="#f7f8f2">
        <meta property="og:type" content="website">
        <meta property="og:title" content="Nadi — Usaha terpantau. Pikiran lebih tenang.">
        <meta property="og:description" content="Penjualan, aktivitas tim, dan insight usaha. Semua terhubung dalam satu ruang yang mudah dipahami.">
        <meta property="og:url" content="{{ route('home') }}">
        <link rel="canonical" href="{{ route('home') }}">
        <title>Nadi — Usaha terpantau. Pikiran lebih tenang.</title>
        @fonts
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="nadi-landing bg-[#f7f8f2] font-sans text-ink antialiased" data-landing>
        <a href="#konten" class="landing-skip-link">Lewati ke konten utama</a>

        <header class="landing-header">
            <nav class="landing-container flex h-20 items-center justify-between gap-6" aria-label="Navigasi utama">
                <a href="{{ route('home') }}" class="landing-brand" aria-label="Nadi — Beranda">
                    <span class="brand-symbol" aria-hidden="true"><span></span><span></span><span></span></span>
                    <span>NADI<span class="landing-brand-dot">.</span></span>
                </a>
                <div class="hidden items-center gap-8 text-sm font-medium text-ink-muted md:flex">
                    <a href="#fitur" class="landing-nav-link">Fitur</a>
                    <a href="#cara-kerja" class="landing-nav-link">Cara kerja</a>
                    <a href="#mengapa-nadi" class="landing-nav-link">Mengapa Nadi?</a>
                </div>
                <div class="flex items-center gap-3 sm:gap-5">
                    <a href="{{ route('login') }}" class="landing-nav-link px-2 py-3 text-sm font-semibold">Masuk</a>
                    <a href="{{ route('login') }}" class="landing-button landing-button-dark hidden sm:inline-flex">
                        Mulai sekarang <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                    </a>
                    <button type="button" class="landing-menu-button md:hidden" data-landing-menu-toggle aria-label="Buka navigasi" aria-controls="landing-mobile-menu" aria-expanded="false" hidden>
                        <i data-lucide="menu" aria-hidden="true"></i>
                    </button>
                </div>
            </nav>
            <nav id="landing-mobile-menu" class="landing-mobile-menu md:hidden" aria-label="Navigasi seluler" hidden>
                <a href="#fitur">Fitur</a>
                <a href="#cara-kerja">Cara kerja</a>
                <a href="#mengapa-nadi">Mengapa Nadi?</a>
                <a href="{{ route('register') }}">Daftarkan usaha <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
                <a href="{{ route('employee.login') }}">Masuk sebagai karyawan</a>
            </nav>
        </header>

        <main id="konten">
            <section class="landing-hero landing-container" aria-labelledby="hero-title">
                <div class="landing-hero-copy">
                    <div class="landing-eyebrow"><span class="landing-status-dot"></span> RUANG TUMBUH UNTUK UMKM</div>
                    <h1 id="hero-title">Usaha terpantau.<br>Pikiran lebih<br><em>tenang.</em><span class="landing-headline-spark" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 4V44M4 24H44M10 10L38 38M38 10L10 38" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg></span></h1>
                    <p class="landing-hero-description">Anda fokus membangun usaha.<br class="hidden sm:block"> Nadi bantu merapikan penjualan, memantau tim, dan memahami langkah berikutnya.</p>
                    <div class="flex flex-col gap-3 min-[390px]:flex-row sm:gap-4">
                        <a href="{{ route('register') }}" class="landing-button landing-button-dark landing-button-large">Daftarkan usaha <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
                        <a href="#preview" class="landing-button landing-button-outline landing-button-large"><i data-lucide="layout-dashboard" aria-hidden="true"></i> Jelajahi Nadi</a>
                    </div>
                    <div class="mt-7 flex items-center gap-3 text-sm text-ink-muted">
                        <span class="flex size-8 shrink-0 items-center justify-center rounded-full border border-[#dfe5d5]"><i data-lucide="users-round" class="size-4" aria-hidden="true"></i></span>
                        <span>Bagian dari tim? <a href="{{ route('employee.register') }}" class="landing-inline-link">Gabung sebagai karyawan <span aria-hidden="true">&#8599;&#65038;</span></a></span>
                    </div>
                </div>

                <div id="preview" class="landing-hero-product" data-reveal>
                    <div class="landing-product-orbit landing-product-orbit-one" aria-hidden="true"></div>
                    <div class="landing-product-orbit landing-product-orbit-two" aria-hidden="true"></div>
                    <div class="landing-product-heading">
                        <span><span class="landing-status-dot"></span> SATU RUANG. LEBIH TERHUBUNG.</span>
                        <i data-lucide="arrow-up-right" aria-hidden="true"></i>
                    </div>
                    <x-landing-preview />
                    <div class="landing-product-note">
                        <span class="landing-product-note-icon"><i data-lucide="circle-check" aria-hidden="true"></i></span>
                        <div><strong>Lebih tahu. Lebih terarah.</strong><span>Mulai dari gambaran usaha Anda hari ini.</span></div>
                        <span class="landing-note-bars" aria-hidden="true"><span></span><span></span><span></span><span></span></span>
                    </div>
                    <p class="landing-preview-caption">Preview interaktif · Seluruh angka merupakan data contoh</p>
                </div>
            </section>

            <div class="landing-container">
                <div class="landing-audience">
                    <p>Berawal dari usaha Anda.<br><strong>Tumbuh bersama Nadi.</strong></p>
                    <div><i data-lucide="store" aria-hidden="true"></i> Toko & retail</div>
                    <div><i data-lucide="shopping-basket" aria-hidden="true"></i> Kuliner</div>
                    <div><i data-lucide="package" aria-hidden="true"></i> Usaha online</div>
                    <div><i data-lucide="users-round" aria-hidden="true"></i> Tim usaha</div>
                </div>
            </div>

            <section id="fitur" class="landing-section landing-container" aria-labelledby="features-title">
                <div class="landing-section-heading" data-reveal>
                    <div><p class="landing-eyebrow">LEBIH RAPI, LEBIH TERARAH</p><h2 id="features-title">Kenali denyut usaha Anda.<br><span class="text-ink-muted">Setiap hari.</span></h2></div>
                    <p>Tak perlu berpindah-pindah catatan.<br>Hal penting tentang usaha dan tim Anda<br class="hidden lg:block"> bertemu di satu tempat.</p>
                </div>

                <div class="landing-features-grid">
                    <article class="landing-feature-card landing-feature-sales" data-reveal>
                        <div class="landing-feature-icon"><i data-lucide="chart-no-axes-combined" aria-hidden="true"></i></div>
                        <h3>Angka yang bercerita.</h3>
                        <p>Catat penjualan, impor pesanan marketplace, dan lihat perubahannya lewat ringkasan yang mudah dibaca.</p>
                        <div class="landing-mini-chart">
                            <div class="flex items-center justify-between gap-3"><span>Ringkasan penjualan</span><span class="text-xs text-ink-faint">Contoh data</span></div>
                            <div class="mt-4 flex items-end justify-between gap-3"><strong>Rp8.450.000</strong><span class="landing-mini-chip"><i data-lucide="trending-up" class="size-3" aria-hidden="true"></i> 18,6%</span></div>
                            <div class="landing-bar-chart" aria-label="Ilustrasi tren penjualan selama tujuh hari">
                                @foreach ([38, 55, 44, 72, 61, 83, 100] as $height)
                                    <div><span style="--bar-height: {{ $height }}%"></span><small>{{ ['S', 'S', 'R', 'K', 'J', 'S', 'M'][$loop->index] }}</small></div>
                                @endforeach
                            </div>
                        </div>
                        <span class="landing-feature-footnote">Penjualan manual · Impor Shopee & TikTok</span>
                    </article>

                    <article class="landing-feature-card landing-feature-team" data-reveal>
                        <div class="landing-feature-icon"><i data-lucide="users-round" aria-hidden="true"></i></div>
                        <h3>Tim seirama.<br> Kerja lebih tertata.</h3>
                        <p>Bagikan tugas, tentukan jadwal, dan pantau progres. Setiap orang tahu apa yang perlu dikerjakan.</p>
                        <div class="landing-mini-tasks" aria-label="Contoh tugas tim">
                            <div><span class="landing-task-check"><i data-lucide="check" aria-hidden="true"></i></span><span>Siapkan pesanan hari ini<small>Selesai · Rani</small></span><span class="landing-avatar bg-[#e4ead8]">R</span></div>
                            <div><span class="landing-task-check"><i data-lucide="check" aria-hidden="true"></i></span><span>Catat penjualan toko<small>Selesai · Dimas</small></span><span class="landing-avatar bg-[#f1e4d6]">D</span></div>
                            <div><span class="landing-task-pending"></span><span>Rapikan area display<small>Dalam proses · Sari</small></span><span class="landing-avatar bg-[#e0e8eb]">S</span></div>
                        </div>
                        <span class="landing-feature-footnote">Pembagian tugas · Pemantauan progres</span>
                    </article>

                    <article class="landing-feature-card landing-feature-insight" data-reveal>
                        <div class="landing-feature-icon"><i data-lucide="sparkles" aria-hidden="true"></i></div>
                        <h3>Dari data,<br> jadi pemahaman.</h3>
                        <p>Tanyakan kondisi usaha dalam bahasa sehari-hari. AI insight membantu Anda membaca data yang sudah tercatat.</p>
                        <div class="landing-mini-insight">
                            <div class="landing-insight-question">Bagaimana penjualan minggu ini?</div>
                            <div class="landing-insight-answer"><span class="flex items-center gap-2 font-semibold"><i data-lucide="sparkles" class="size-4" aria-hidden="true"></i> Nadi AI <small>Contoh insight</small></span><p>Penjualan tertinggi ada di akhir pekan. Coba perhatikan kesiapan tim di hari tersebut.</p></div>
                        </div>
                        <span class="landing-feature-footnote">Insight sebagai pendamping keputusan Anda</span>
                    </article>
                </div>
            </section>

            <section id="mengapa-nadi" class="landing-belief" aria-labelledby="belief-title">
                <div class="landing-container landing-belief-grid">
                    <div data-reveal>
                        <p class="landing-eyebrow">USAHA ANDA, SEPENUH HATI</p>
                        <h2 id="belief-title">Di balik setiap usaha,<br>ada mimpi yang<br><em>layak bertumbuh.</em></h2>
                        <p>Kami percaya, memahami usaha seharusnya tidak terasa rumit. Nadi hadir untuk membantu Anda melihat lebih jelas, bekerja lebih teratur, dan melangkah dengan lebih yakin.</p>
                        <a href="{{ route('register') }}" class="landing-button landing-button-lime mt-8">Bangun langkah pertama <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
                    </div>
                    <div class="landing-principles" data-reveal>
                        <article><span>01</span><div><h3>Jelas sejak awal.</h3><p>Ringkasan yang mudah dibaca dan bahasa yang akrab. Mulai dari penjualan dan aktivitas tim sehari-hari.</p></div><i data-lucide="eye" aria-hidden="true"></i></article>
                        <article><span>02</span><div><h3>Setiap orang, perannya.</h3><p>Pemilik memantau usaha. Karyawan mengakses ruang kerjanya. Akses mengikuti keanggotaan pada usaha.</p></div><i data-lucide="shield-check" aria-hidden="true"></i></article>
                        <article><span>03</span><div><h3>Anda yang memegang kendali.</h3><p>Data dan insight membantu memberi gambaran. Keputusan usaha tetap di tangan Anda.</p></div><i data-lucide="contact-round" aria-hidden="true"></i></article>
                    </div>
                </div>
                <div class="landing-belief-watermark" aria-hidden="true">nadi.</div>
            </section>

            <section id="cara-kerja" class="landing-section landing-container" aria-labelledby="steps-title">
                <div class="text-center" data-reveal><p class="landing-eyebrow justify-center">LANGKAH KECIL, AWAL YANG BERARTI</p><h2 id="steps-title" class="landing-section-title mt-4">Mulai teratur, mulai dari sini.</h2><p class="mt-5 text-base leading-7 text-ink-muted">Bangun kebiasaan baru untuk usaha Anda, satu langkah setiap hari.</p></div>
                <div class="landing-steps">
                    <article data-reveal><div class="landing-step-top"><span>01</span><i data-lucide="store" aria-hidden="true"></i></div><h3>Kenalkan usaha Anda</h3><p>Buat akun pemilik dan daftarkan usaha sebagai awal ruang kerja Anda di Nadi.</p><a href="{{ route('register') }}" class="landing-inline-link">Daftarkan usaha <span aria-hidden="true">&#8599;&#65038;</span></a></article>
                    <article data-reveal><div class="landing-step-top"><span>02</span><i data-lucide="users-round" aria-hidden="true"></i></div><h3>Ajak tim bergabung</h3><p>Bagikan kode usaha kepada karyawan agar mereka bisa bergabung dan mulai bekerja bersama.</p><a href="{{ route('employee.register') }}" class="landing-inline-link">Saya punya kode usaha <span aria-hidden="true">&#8599;&#65038;</span></a></article>
                    <article data-reveal><div class="landing-step-top"><span>03</span><i data-lucide="trending-up" aria-hidden="true"></i></div><h3>Pantau dan pahami</h3><p>Mulai catat penjualan, atur tugas, lalu gunakan ringkasan dan insight untuk melihat perkembangan usaha.</p><a href="#preview" class="landing-inline-link">Lihat contoh dashboard <span aria-hidden="true">↑</span></a></article>
                </div>
            </section>

            <section class="landing-faq-section landing-container" aria-labelledby="faq-title">
                <div data-reveal><p class="landing-eyebrow">MARI KENAL LEBIH DEKAT</p><h2 id="faq-title" class="landing-section-title mt-4">Masih penasaran?</h2><p class="mt-5 max-w-xs text-base leading-7 text-ink-muted">Beberapa hal yang mungkin ingin Anda ketahui sebelum memulai.</p></div>
                <div class="landing-faq-list" data-reveal>
                    <details name="landing-faq"><summary>Apakah Nadi cocok untuk usaha saya?<i data-lucide="plus" aria-hidden="true"></i></summary><p>Nadi ditujukan bagi pelaku UMKM yang ingin merapikan catatan penjualan dan aktivitas tim. Anda bisa memulai dari pencatatan penjualan manual, lalu menggunakan pembagian tugas dan insight sesuai kebutuhan usaha.</p></details>
                    <details name="landing-faq"><summary>Bagaimana cara memasukkan data penjualan?<i data-lucide="plus" aria-hidden="true"></i></summary><p>Anda dapat mencatat penjualan secara manual atau mengimpor file pesanan dari Shopee dan TikTok Seller melalui fitur penjualan. Ringkasan usaha mengikuti data yang sudah Anda masukkan.</p></details>
                    <details name="landing-faq"><summary>Apakah karyawan memakai akun yang sama?<i data-lucide="plus" aria-hidden="true"></i></summary><p>Setiap karyawan membuat akun sendiri, lalu bergabung menggunakan kode usaha dari pemilik. Karyawan memiliki ruang kerja untuk tugas dan pencatatan penjualan, sementara pemilik memantau usaha serta mengelola akses tim.</p></details>
                    <details name="landing-faq"><summary>Apakah angka pada halaman ini data usaha asli?<i data-lucide="plus" aria-hidden="true"></i></summary><p>Tidak. Semua angka, nama, grafik, dan jawaban AI pada halaman pengenalan ini merupakan contoh untuk menunjukkan cara kerja Nadi. Dashboard Anda akan menampilkan data usaha yang Anda dan tim catat.</p></details>
                    <details name="landing-faq"><summary>Haruskah saya mengikuti semua saran AI?<i data-lucide="plus" aria-hidden="true"></i></summary><p>AI insight membantu membaca data yang tersedia dan bisa saja keliru. Gunakan sebagai bahan pertimbangan, periksa kembali data serta kondisi usaha, dan tentukan keputusan yang paling sesuai untuk Anda.</p></details>
                </div>
            </section>

            <section class="landing-container pb-8 sm:pb-12" aria-labelledby="cta-title">
                <div class="landing-final-cta" data-reveal>
                    <div class="landing-cta-orbit" aria-hidden="true"></div>
                    <span class="landing-cta-symbol" aria-hidden="true"><svg viewBox="0 0 48 48" fill="none" aria-hidden="true"><path d="M24 4V44M4 24H44M10 10L38 38M38 10L10 38" stroke="currentColor" stroke-width="5" stroke-linecap="round"/></svg></span>
                    <p class="landing-eyebrow justify-center">LANGKAH BERIKUTNYA MILIK ANDA</p>
                    <h2 id="cta-title">Usaha yang Anda bangun,<br>layak dikelola <em>lebih baik.</em></h2>
                    <p>Mulai ruang usaha Anda di Nadi. Tumbuh dengan lebih terarah.</p>
                    <a href="{{ route('register') }}" class="landing-button landing-button-dark landing-button-large">Daftarkan usaha Anda <i data-lucide="arrow-up-right" aria-hidden="true"></i></a>
                    <span class="mt-5 text-sm text-ink-muted">Sudah punya akun? <a href="{{ route('login') }}" class="landing-inline-link">Masuk ke Nadi</a></span>
                </div>
            </section>
        </main>

        <footer class="landing-container landing-footer">
            <div class="flex flex-col justify-between gap-8 sm:flex-row sm:items-center">
                <div><a href="{{ route('home') }}" class="landing-brand w-fit" aria-label="Nadi — Beranda"><span class="brand-symbol" aria-hidden="true"><span></span><span></span><span></span></span><span>NADI<span class="landing-brand-dot">.</span></span></a><p class="mt-3 text-sm text-ink-muted">Menemani denyut usaha Anda.</p></div>
                <nav class="flex flex-wrap gap-x-7 gap-y-4 text-sm text-ink-muted" aria-label="Navigasi footer"><a href="#fitur" class="landing-nav-link">Fitur</a><a href="#cara-kerja" class="landing-nav-link">Cara kerja</a><a href="{{ route('login') }}" class="landing-nav-link">Masuk pemilik</a><a href="{{ route('employee.login') }}" class="landing-nav-link">Masuk karyawan</a></nav>
            </div>
            <div class="mt-9 flex flex-col justify-between gap-3 border-t border-line py-6 text-xs text-ink-muted sm:flex-row"><p>© {{ date('Y') }} Nadi. Ruang tumbuh untuk UMKM.</p><p>Dibangun untuk langkah besar dari usaha kecil.</p></div>
        </footer>
    </body>
</html>
