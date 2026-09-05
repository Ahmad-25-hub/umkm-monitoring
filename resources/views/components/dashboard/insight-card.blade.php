@props(['insights', 'loading' => false, 'class' => ''])

<article {{ $attributes->merge(['class' => 'panel overflow-hidden '.$class]) }}>
    @if ($loading)
        <div class="p-6"><x-dashboard.loading-state variant="insights" /></div>
    @else
        <div class="border-b border-line-soft p-5 sm:p-6">
            <p class="section-kicker">Dari data usaha Anda</p><h2 class="panel-title mt-2">Catatan usaha</h2>
            <p class="mt-2 text-sm text-ink-muted">Penjelasan singkat untuk membantu menentukan langkah berikutnya.</p>
        </div>
        <div class="grid divide-y divide-line-soft md:grid-cols-2 md:divide-y-0">
            @forelse ($insights as $insight)
                <x-dashboard.insight-item :insight="$insight" />
            @empty
                <div class="md:col-span-2"><x-dashboard.empty-state icon="lightbulb" title="Belum ada catatan usaha" description="Catatan akan muncul setelah data penjualan dan anggota tim tersedia." compact /></div>
            @endforelse
        </div>
    @endif
</article>
