<x-dynamic-component :component="$shell" :owner="$owner" :employee="$employee" :business="$business" :businesses="$businesses" :active-business-id="$activeBusinessId" title="Pratinjau penjualan — NADI">
    <div class="dashboard-sections">
        <section class="panel p-6 sm:p-8">
            <p class="section-kicker">{{ $business->name }} · Offline</p>
            <h1 class="display-heading mt-2">Periksa penjualan</h1>
            <p class="mt-3 text-sm leading-6 text-ink-muted">{{ count($preview['rows']) }} baris diperiksa. Belum ada data yang disimpan.</p>
            @if ($preview['errors'])
                <div class="mt-5 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800" role="alert">
                    <p class="font-semibold">Ada {{ count($preview['errors']) }} kesalahan. Perbaiki file, lalu unggah kembali.</p>
                    <ul class="mt-3 max-h-60 list-inside list-disc overflow-y-auto">
                        @foreach ($preview['errors'] as $error)<li>{{ $error }}</li>@endforeach
                    </ul>
                </div>
            @else
                <div class="mt-5 flex flex-wrap gap-4 text-sm">
                    <span class="rounded-xl bg-brand-50 px-4 py-3 font-semibold text-brand-800">{{ count($preview['orders']) - count($preview['duplicates']) }} transaksi baru</span>
                    <span class="rounded-xl bg-canvas px-4 py-3 text-ink-muted">{{ count($preview['duplicates']) }} transaksi sudah tercatat, akan dilewati</span>
                </div>
                <p class="mt-3 text-xs text-ink-muted">Pratinjau berlaku selama 30 menit. Nomor transaksi diperiksa ulang saat disimpan.</p>
            @endif
            <div class="mt-6 flex flex-wrap gap-3">
                <a href="{{ route($routePrefix.'.create') }}#offline-upload" class="button-secondary">Kembali / unggah ulang</a>
                @if ($token && count($preview['orders']) > count($preview['duplicates']))
                    <form method="POST" action="{{ route($routePrefix.'.confirm') }}" data-submit-once>
                        @csrf
                        <input type="hidden" name="token" value="{{ $token }}">
                        <button type="submit" class="button-primary">Simpan penjualan</button>
                    </form>
                @endif
            </div>
        </section>
        <section class="panel overflow-hidden" aria-label="Pratinjau baris penjualan">
            <div class="max-h-[36rem] overflow-auto">
                <table class="w-full min-w-[48rem] text-left text-xs">
                    <thead class="sticky top-0 bg-canvas text-ink-muted"><tr>
                        @foreach (['Baris', 'Tanggal', 'No. transaksi', 'Produk', 'Jumlah', 'Harga satuan', 'Subtotal', 'Hasil'] as $heading)
                            <th class="px-4 py-4">{{ $heading }}</th>
                        @endforeach
                    </tr></thead>
                    <tbody class="divide-y divide-line-soft">
                        @foreach ($preview['rows'] as $row)
                            <tr @class(['bg-red-50' => count($row['errors']) > 0])>
                                <td class="px-4 py-3">{{ $row['number'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['date'] }}</td>
                                <td class="px-4 py-3">{{ $row['order_id'] }}</td>
                                <td class="max-w-64 break-words px-4 py-3 font-semibold">{{ $row['product_name'] }}</td>
                                <td class="px-4 py-3">{{ $row['quantity'] }}</td>
                                <td class="px-4 py-3">{{ $row['unit_price'] }}</td>
                                <td class="whitespace-nowrap px-4 py-3">{{ $row['subtotal'] !== null ? 'Rp'.number_format($row['subtotal'], 0, ',', '.') : '—' }}</td>
                                <td class="min-w-44 px-4 py-3">{{ $row['errors'] ? implode(' ', $row['errors']) : (in_array($row['order_id'], $preview['duplicates'], true) ? 'Sudah tercatat · dilewati' : 'Siap disimpan') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</x-dynamic-component>
