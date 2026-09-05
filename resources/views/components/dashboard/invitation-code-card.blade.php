@props(['business'])
@php
    $plainInvitationCode = session('invitation_code');
@endphp

<details class="panel invitation-disclosure" @if ($plainInvitationCode) open @endif>
    <summary class="flex items-center gap-4 p-5 sm:p-6">
        <span class="panel-icon shrink-0"><i data-lucide="users-round" aria-hidden="true"></i></span>
        <span class="min-w-0 flex-1"><span class="block text-base font-semibold text-ink">Undang karyawan</span><span class="mt-1 block text-sm leading-5 text-ink-muted">Kelola kode undangan {{ $business->name }}.</span></span>
        <i data-lucide="chevron-down" class="h-5 w-5 shrink-0 text-ink-muted" aria-hidden="true"></i>
    </summary>
    <div class="grid gap-5 border-t border-line-soft p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
        <div>
            <h2 class="text-base font-semibold text-ink">Kode undangan {{ $business->name }}</h2>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-ink-muted">Bagikan kode ini agar karyawan dapat bergabung menggunakan akun pribadi mereka.</p>
            @if ($plainInvitationCode)
                <div class="mt-4 flex flex-wrap items-center justify-between gap-4 rounded-xl border border-brand-100 bg-brand-50 p-4">
                    <div class="min-w-0"><p class="text-xs font-semibold text-brand-700">Salin sekarang — hanya ditampilkan kali ini</p><code class="mt-2 block break-all text-xl font-semibold tracking-widest text-brand-900" data-invitation-code>{{ $plainInvitationCode }}</code></div>
                    <button type="button" class="button-secondary" data-copy-invitation-code><i data-lucide="copy" aria-hidden="true"></i><span>Salin kode</span></button>
                </div>
                <p class="mt-2 text-xs text-ink-muted" data-copy-feedback role="status"></p>
            @else
                <p class="mt-4 rounded-xl bg-canvas px-4 py-3 text-sm leading-6 text-ink-muted">Kode yang sudah dibuat tidak dapat dilihat kembali. Jika belum menyimpannya, buat kode baru lalu bagikan kepada karyawan.</p>
            @endif
        </div>
        @can('rotateInvitationCode', $business)
            <form method="POST" action="{{ route('businesses.invitation-code.store', $business) }}" data-confirm="Buat kode undangan baru? Kode lama langsung tidak dapat digunakan lagi." data-submit-once>
                @csrf
                <button type="submit" class="button-primary w-full"><i data-lucide="key-round" aria-hidden="true"></i>Buat ulang kode</button>
                <p class="mt-2 text-xs leading-5 text-ink-muted">Kode lama langsung tidak berlaku.</p>
            </form>
        @endcan
    </div>
</details>
