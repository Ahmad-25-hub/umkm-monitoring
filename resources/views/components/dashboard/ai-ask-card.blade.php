@props(['suggestions', 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel ai-panel overflow-hidden '.$class]) }}>
    <div class="ai-content">
        <div class="ai-heading">
            <span class="ai-mark"><i data-lucide="sparkles" aria-hidden="true"></i></span>
            <div>
                <div class="flex items-center gap-2">
                    <p class="text-lg font-semibold tracking-[-0.025em] text-ink">Ask NADI</p>
                    <span class="ai-beta">Preview</span>
                </div>
                <p class="mt-1 text-sm text-ink-muted">Dapatkan jawaban tentang bisnis Anda dalam hitungan detik.</p>
            </div>
        </div>

        <div class="ai-input-shell">
            <i data-lucide="sparkles" aria-hidden="true"></i>
            <input
                type="text"
                placeholder="Tanyakan apa saja tentang bisnis Anda..."
                data-ai-input
                aria-describedby="ai-teaser-note"
            />
            <button type="button" class="ai-send" aria-label="Kirim pertanyaan" disabled>
                <i data-lucide="arrow-up" aria-hidden="true"></i>
            </button>
        </div>

        <div class="ai-suggestions" aria-label="Contoh pertanyaan">
            @foreach ($suggestions as $suggestion)
                <button type="button" data-ai-suggestion="{{ $suggestion }}">{{ $suggestion }}</button>
            @endforeach
        </div>

        <p id="ai-teaser-note" class="mt-5 text-[0.68rem] text-ink-faint">Ask NADI sedang dalam tahap preview. Integrasi AI akan tersedia pada fase berikutnya.</p>
    </div>
</article>
