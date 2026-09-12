@props(['index', 'item' => []])

<fieldset class="grid gap-3 rounded-xl border border-line-soft bg-canvas/50 p-4 sm:grid-cols-12" data-sale-item>
    <legend class="sr-only">Produk penjualan</legend>
    <label class="grid gap-2 text-xs font-semibold text-ink-muted sm:col-span-5">
        Nama produk
        <input name="items[{{ $index }}][product_name]" value="{{ $item['product_name'] ?? '' }}" type="text" maxlength="255" required class="h-11 w-full rounded-xl border border-line bg-white px-3 text-sm font-normal text-ink focus:ring-3 focus:ring-brand-100" placeholder="Contoh: Kopi susu">
    </label>
    <label class="grid gap-2 text-xs font-semibold text-ink-muted sm:col-span-2">
        Jumlah
        <input name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] ?? 1 }}" data-item-quantity type="number" inputmode="numeric" min="1" max="100000" step="1" required class="h-11 w-full rounded-xl border border-line bg-white px-3 text-sm font-normal text-ink focus:ring-3 focus:ring-brand-100">
    </label>
    <label class="grid gap-2 text-xs font-semibold text-ink-muted sm:col-span-4">
        Harga satuan (Rp)
        <input name="items[{{ $index }}][unit_price]" value="{{ $item['unit_price'] ?? '' }}" data-item-price type="number" inputmode="numeric" min="0" max="1000000000" step="1" required class="h-11 w-full rounded-xl border border-line bg-white px-3 text-sm font-normal text-ink focus:ring-3 focus:ring-brand-100" placeholder="18000">
    </label>
    <button type="button" data-remove-sale-item class="self-end rounded-lg px-1 py-3 text-xs font-semibold text-critical hover:bg-red-50 disabled:opacity-40 sm:col-span-1" aria-label="Hapus produk">Hapus</button>
</fieldset>
