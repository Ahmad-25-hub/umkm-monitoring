@props(['suggestions' => [], 'class' => ''])

<aside {{ $attributes->merge(['class' => 'flex items-start gap-3 rounded-2xl border border-dashed border-line px-5 py-4 '.$class]) }} aria-label="Pengembangan NADI">
    <i data-lucide="sparkles" class="mt-0.5 h-5 w-5 shrink-0 text-brand-700" aria-hidden="true"></i>
    <div>
        <p class="text-sm font-semibold text-ink">Tanya NADI <span class="ml-2 text-xs font-normal text-ink-muted">Dalam pengembangan</span></p>
        <p class="mt-1 text-xs leading-5 text-ink-muted">Asisten untuk membantu memahami usaha Anda. Fitur tanya jawab belum tersedia.</p>
    </div>
</aside>
