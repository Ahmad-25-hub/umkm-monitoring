@props(['suggestions' => [], 'class' => ''])

<aside {{ $attributes->merge(['class' => 'flex flex-col gap-4 rounded-2xl border border-brand-100 bg-brand-50/50 px-5 py-4 sm:flex-row sm:items-center '.$class]) }} aria-label="Tanya Nadi">
    <i data-lucide="sparkles" class="h-5 w-5 shrink-0 text-brand-700" aria-hidden="true"></i>
    <div class="flex-1">
        <p class="text-sm font-semibold text-ink">Ada pertanyaan tentang usaha Anda?</p>
        <p class="mt-1 text-xs leading-5 text-ink-muted">Tanyakan penjualan hari ini atau progres tugas tim kepada Nadi.</p>
    </div>
    <a href="{{ route('ai-insight.index') }}" class="inline-flex w-fit items-center gap-2 rounded-xl bg-brand-700 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-900">Tanya NADI <i data-lucide="arrow-right" class="h-4 w-4" aria-hidden="true"></i></a>
</aside>
