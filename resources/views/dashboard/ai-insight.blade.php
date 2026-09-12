<x-dashboard.app-shell :owner="$owner" :businesses="$businesses" :active-business-id="$activeBusinessId" title="AI Insight — NADI">
    <div class="dashboard-sections" data-ai-chat data-business-id="{{ $activeBusinessId }}" data-clear-url="{{ route('ai-insight.destroy') }}">
        <header class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="section-kicker">Kenali usaha Anda · {{ $activeBusiness->name }}</p>
                <h1 class="mt-2 text-3xl font-semibold tracking-tight text-ink">Tanya Nadi</h1>
                <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Bahas kondisi usaha, tentukan prioritas, dan lanjutkan dengan pertanyaan atau ide Anda.</p>
            </div>
            <span class="inline-flex w-fit items-center gap-2 rounded-full border border-brand-100 bg-brand-50 px-3 py-1.5 text-xs font-semibold text-brand-700">
                <i data-lucide="sparkles" class="h-3.5 w-3.5" aria-hidden="true"></i> AI Insight · Analisis usaha
            </span>
        </header>

        @unless ($isConfigured)
            <div class="rounded-2xl border border-amber-200 bg-amber-50 px-5 py-4 text-sm leading-6 text-amber-900" role="status">
                AI Insight belum diaktifkan. Hubungi pengelola aplikasi untuk mengaktifkan asisten Nadi.
            </div>
        @endunless

        <div class="grid items-start gap-5 xl:grid-cols-[minmax(0,1fr)_18rem]">
            <section class="panel min-w-0 overflow-hidden" aria-label="Percakapan dengan Nadi">
                <div class="flex items-center justify-between gap-3 border-b border-line-soft px-5 py-4">
                    <div class="flex items-center gap-3">
                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-brand-50 text-brand-700"><i data-lucide="sparkles" class="h-5 w-5" aria-hidden="true"></i></span>
                        <div><h2 class="text-sm font-semibold">Asisten usaha Anda</h2><p class="mt-1 text-xs text-ink-muted">Penjualan, produk & kinerja tugas</p></div>
                    </div>
                    <button type="button" data-ai-clear class="rounded-lg px-3 py-2 text-xs font-semibold text-ink-muted hover:bg-canvas disabled:opacity-50" @disabled(count($messages) === 0)>Chat baru</button>
                </div>

                <div data-ai-messages role="log" aria-label="Pesan percakapan" aria-live="polite" aria-relevant="additions" class="flex max-h-[34rem] min-h-80 flex-col gap-5 overflow-y-auto bg-canvas/50 p-4 sm:p-6">
                    <div data-ai-empty @if (count($messages) > 0) hidden @endif class="my-auto py-10 text-center">
                        <span class="mx-auto mb-4 grid h-14 w-14 place-items-center rounded-2xl border border-brand-100 bg-brand-50 text-brand-700"><i data-lucide="sparkles" class="h-6 w-6" aria-hidden="true"></i></span>
                        <h3 class="text-lg font-semibold tracking-tight">Apa yang ingin Anda ketahui?</h3>
                        <p class="mx-auto mt-2 max-w-sm text-sm leading-6 text-ink-muted">Temukan produk terlaris, pahami tren penjualan, atau periksa kinerja tugas tim pada usaha aktif Anda.</p>
                    </div>
                    @foreach ($messages as $entry)
                        <x-dashboard.ai-message :entry="$entry" />
                    @endforeach
                </div>

                <div class="border-t border-line-soft p-4 sm:p-5">
                    <p data-ai-progress hidden class="mb-3 text-xs text-brand-700" role="status">Nadi sedang memahami pertanyaan Anda…</p>
                    <p data-ai-error hidden class="mb-3 rounded-xl bg-red-50 px-4 py-3 text-sm text-red-700" role="alert"></p>
                    <form method="POST" action="{{ route('ai-insight.store') }}" data-ai-form>
                        @csrf
                        <label for="ai-question" class="sr-only">Pertanyaan tentang usaha Anda</label>
                        <textarea id="ai-question" name="message" data-ai-input rows="2" maxlength="1000" required aria-describedby="ai-input-help" placeholder="Berapa penjualan hari ini?" class="block w-full resize-y rounded-xl border border-line bg-canvas/50 px-4 py-3 text-sm leading-6 placeholder:text-ink-faint focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-100 disabled:opacity-50" @disabled(! $isConfigured)></textarea>
                        <div class="mt-3 flex items-center justify-between gap-3">
                            <p id="ai-input-help" class="text-xs text-ink-faint"><span data-ai-count>0</span>/1.000 · Enter untuk kirim, Shift+Enter untuk baris baru</p>
                            <button type="submit" class="inline-flex shrink-0 items-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-900 disabled:opacity-50" @disabled(! $isConfigured)><span>Kirim</span><i data-lucide="send" class="h-4 w-4" aria-hidden="true"></i></button>
                        </div>
                    </form>
                    <noscript><p class="mt-3 text-sm text-critical">Aktifkan JavaScript untuk menggunakan percakapan Nadi.</p></noscript>
                </div>
            </section>

            <aside class="grid gap-5" aria-label="Bantuan AI Insight">
                <section class="panel p-5">
                    <p class="section-kicker">Mulai dari sini</p>
                    <h2 class="mt-2 text-base font-semibold">Coba tanyakan</h2>
                    <div class="mt-4 grid gap-2">
                        @foreach (['Produk apa yang sebaiknya saya fokuskan bulan ini?', 'Belakangan ini penjualan naik atau turun?', 'Tampilkan produk terlaris dan kinerja karyawan bulan ini.', 'Ada saran dari hasil yang tadi?', 'Bagaimana cara memperbaiki penyelesaian tugas tim?', 'Bandingkan penjualan TikTok dan Shopee bulan ini.'] as $suggestion)
                            <button type="button" data-ai-suggestion="{{ $suggestion }}" class="rounded-xl border border-line-soft px-3 py-3 text-left text-sm leading-5 text-ink-muted transition hover:border-brand-200 hover:bg-brand-50 hover:text-brand-700 disabled:opacity-50" @disabled(! $isConfigured)>{{ $suggestion }}</button>
                        @endforeach
                    </div>
                </section>
                <section class="rounded-2xl border border-dashed border-line p-5">
                    <div class="flex items-center gap-2 text-brand-700"><i data-lucide="shield-check" class="h-4 w-4" aria-hidden="true"></i><h2 class="text-sm font-semibold">Tentang jawaban Nadi</h2></div>
                    <ul class="mt-3 grid gap-3 text-xs leading-5 text-ink-muted">
                        <li>Nadi membaca ringkasan data usaha aktif dan konteks percakapan untuk memberi penjelasan serta saran.</li>
                        <li>Penjualan mengikuti impor terakhir. Data yang belum diunggah belum terhitung.</li>
                        <li>Kinerja karyawan berdasarkan tugas tercatat; absensi, kualitas, dan kesulitan kerja belum dinilai.</li>
                        <li>Stok, laba, dan prediksi memerlukan pencatatan tambahan. Subtotal produk bukan laba.</li>
                        <li>Saran adalah bahan pertimbangan. Buka dasar analisis untuk memeriksa angka dan batasan datanya.</li>
                        <li>Lima pertanyaan terakhir tersimpan selama sesi ini. Gunakan “Chat baru” untuk menghapusnya.</li>
                    </ul>
                </section>
            </aside>
        </div>

        <template data-ai-message-template>
            <x-dashboard.ai-message :entry="['role' => 'assistant', 'message' => '', 'source' => null, 'time' => '']" />
        </template>
    </div>
</x-dashboard.app-shell>
