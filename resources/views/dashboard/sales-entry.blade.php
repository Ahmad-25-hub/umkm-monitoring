<x-dynamic-component :component="$shell" :owner="$owner" :employee="$employee" :business="$business" :businesses="$businesses" :active-business-id="$activeBusinessId" title="Tambah penjualan — NADI">
    <div class="dashboard-sections">
        <section class="panel p-6 sm:p-8">
            <a href="{{ route($returnRoute) }}" class="text-xs font-semibold text-brand-700">← Kembali ke dashboard</a>
            <p class="section-kicker mt-5">{{ $business->name }}</p>
            <h1 class="display-heading mt-2">Tambah penjualan</h1>
            <p class="mt-3 text-sm leading-6 text-ink-muted">Catat penjualan offline, unggah banyak transaksi, atau impor pesanan marketplace.</p>
            <nav class="mt-5 flex flex-wrap gap-2" aria-label="Cara menambah penjualan">
                <a href="#manual-sales" class="button-secondary">Catat manual</a>
                <a href="#offline-upload" class="button-secondary">Upload Excel offline</a>
                <a href="#marketplace-upload" class="button-secondary">Impor marketplace</a>
            </nav>
        </section>

        @if (session('success'))
            <div class="feedback-success" role="status">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="rounded-xl border border-red-200 bg-red-50 p-5 text-sm text-red-800" role="alert">
                <p class="font-semibold">Periksa data berikut:</p>
                <ul class="mt-2 list-inside list-disc">
                    @foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach
                </ul>
            </div>
        @endif

        <section id="manual-sales" class="panel scroll-mt-6 p-6 sm:p-8" aria-labelledby="manual-title">
            <p class="section-kicker">Untuk pencatatan harian</p>
            <h2 id="manual-title" class="panel-title mt-2">Catat manual</h2>
            <p class="mt-2 text-sm leading-6 text-ink-muted">Satu formulir untuk satu transaksi. Tambahkan beberapa produk bila dibeli bersamaan. Kanal otomatis Offline, status lunas.</p>
            <form method="POST" action="{{ route($routePrefix.'.store') }}" class="mt-6 grid gap-5" data-offline-sales data-submit-once>
                @csrf
                <input type="hidden" name="business_id" value="{{ $business->id }}">
                <div class="grid gap-4 sm:grid-cols-2">
                    <label class="grid gap-2 text-xs font-semibold text-ink-muted">Tanggal penjualan (WIB)
                        <input type="date" name="ordered_on" value="{{ old('ordered_on', today('Asia/Jakarta')->toDateString()) }}" min="2000-01-01" max="{{ today('Asia/Jakarta')->toDateString() }}" required class="h-11 rounded-xl border border-line bg-white px-3 text-sm font-normal text-ink focus:ring-3 focus:ring-brand-100">
                    </label>
                    <label class="grid gap-2 text-xs font-semibold text-ink-muted">Nomor transaksi
                        <input type="text" name="order_id" value="{{ old('order_id', $defaultOrderId) }}" maxlength="80" pattern="[A-Za-z0-9_-]+" required class="h-11 w-full rounded-xl border border-line bg-white px-3 text-sm font-normal text-ink focus:ring-3 focus:ring-brand-100" aria-describedby="order-id-help">
                    </label>
                </div>
                <p id="order-id-help" class="text-xs leading-5 text-ink-muted">Nomor dibuat otomatis. Anda bisa menggantinya dengan nomor nota; gunakan nomor unik untuk setiap transaksi.</p>
                <div class="grid gap-3" data-sale-items>
                    @foreach (old('items', [[]]) as $index => $item)
                        <x-dashboard.offline-sale-item :index="$index" :item="$item" />
                    @endforeach
                </div>
                <template data-sale-item-template><x-dashboard.offline-sale-item index="__INDEX__" /></template>
                <button type="button" class="button-secondary justify-self-start" data-add-sale-item>+ Tambah produk</button>
                <div class="flex flex-wrap items-center justify-between gap-4 rounded-xl bg-brand-50 p-4">
                    <span class="text-sm font-semibold text-brand-800">Total penjualan</span>
                    <output class="text-xl font-semibold text-brand-900" data-sale-total aria-live="polite">Rp0</output>
                </div>
                <noscript><p class="text-xs text-ink-muted">Total dihitung saat disimpan. Aktifkan JavaScript untuk menambahkan beberapa produk sekaligus.</p></noscript>
                <div class="flex flex-wrap items-center justify-end gap-3">
                    <button type="submit" class="button-primary">Simpan penjualan</button>
                    <button type="submit" name="save_again" value="1" class="button-secondary">Simpan & tambah lagi</button>
                </div>
            </form>
        </section>

        <div class="grid items-start gap-5 xl:grid-cols-2">
            <section id="offline-upload" class="panel grid scroll-mt-6 gap-5 p-6 sm:p-7" aria-labelledby="offline-upload-title">
                <div>
                    <p class="section-kicker">Banyak transaksi sekaligus</p>
                    <h2 id="offline-upload-title" class="panel-title mt-2">Upload Excel offline</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-muted">Unduh template, isi penjualan pada sheet Penjualan, lalu unggah untuk diperiksa sebelum disimpan.</p>
                </div>
                <a href="{{ route($routePrefix.'.template') }}" class="button-secondary justify-self-start">Unduh template Excel</a>
                <p class="text-xs leading-5 text-ink-muted">Kolom: tanggal, no. transaksi, produk, jumlah, harga satuan. Satu baris untuk satu produk; nomor transaksi yang sama digabung menjadi satu pesanan. Maksimal 2.000 baris / 10 MB.</p>
                <form method="POST" action="{{ route($routePrefix.'.preview') }}" enctype="multipart/form-data" class="grid gap-4" data-submit-once>
                    @csrf
                    <input type="hidden" name="business_id" value="{{ $business->id }}">
                    <label class="grid gap-2 text-xs font-semibold text-ink-muted">Pilih Excel offline yang sudah diisi
                        <input type="file" name="sales_file" accept=".xlsx" required class="block w-full rounded-xl border border-line bg-white text-sm font-normal file:mr-3 file:border-0 file:bg-canvas file:px-3 file:py-3 file:text-brand-800">
                    </label>
                    <button type="submit" class="button-primary">Periksa & lihat pratinjau</button>
                </form>
                <p class="text-xs leading-5 text-ink-muted">Transaksi yang sudah tercatat akan dilewati. Jika ada kesalahan, perbaiki file dan unggah ulang.</p>
            </section>

            <section id="marketplace-upload" class="panel grid scroll-mt-6 gap-5 p-6 sm:p-7" aria-labelledby="marketplace-title">
                <div>
                    <p class="section-kicker">Dari toko online</p>
                    <h2 id="marketplace-title" class="panel-title mt-2">Impor TikTok Seller atau Shopee</h2>
                    <p class="mt-2 text-sm leading-6 text-ink-muted">Gunakan file ekspor asli: CSV dari TikTok Seller atau XLSX dari menu Pesanan Shopee Seller.</p>
                </div>
                <form method="POST" action="{{ route($marketplaceRoute) }}" enctype="multipart/form-data" class="grid gap-4" data-submit-once>
                    @csrf
                    <input type="hidden" name="business_id" value="{{ $business->id }}">
                    <label class="grid gap-2 text-xs font-semibold text-ink-muted">Pilih file marketplace
                        <input type="file" name="sales_file" accept=".csv,.xlsx" required class="block w-full rounded-xl border border-line bg-white text-sm font-normal file:mr-3 file:border-0 file:bg-canvas file:px-3 file:py-3 file:text-brand-800">
                    </label>
                    <button type="submit" class="button-primary">Unggah dan perbarui statistik</button>
                </form>
                <p class="text-xs leading-5 text-ink-muted">Maksimal 10 MB. Pesanan marketplace yang sama akan diperbarui sesuai file terbaru.</p>
            </section>
        </div>
    </div>
</x-dynamic-component>
