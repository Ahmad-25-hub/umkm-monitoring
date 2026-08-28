@props(['business'])

@php
    $plainInvitationCode = session('invitation_code');
@endphp

<section class="panel overflow-hidden" aria-labelledby="invitation-code-title">
    @if (session('success'))
        <div class="border-b border-brand-100 bg-brand-50 px-5 py-3 text-xs font-medium text-brand-700 sm:px-6" role="status">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid gap-6 p-5 sm:p-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-center">
        <div>
            <p class="section-kicker">Akses karyawan</p>
            <h2 id="invitation-code-title" class="section-heading">Kode undangan {{ $business->name }}</h2>
            <p class="mt-3 max-w-2xl text-sm leading-6 text-ink-muted">
                Karyawan akan menggunakan kode ini saat alur pendaftaran karyawan tersedia. Kode ini bukan password bersama.
            </p>

            @if ($plainInvitationCode)
                <div class="mt-5 flex flex-col gap-3 rounded-xl border border-brand-100 bg-brand-50 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <p class="text-[0.65rem] font-semibold uppercase tracking-[0.1em] text-brand-700">Salin sekarang — hanya ditampilkan kali ini</p>
                        <code class="mt-2 block text-xl font-semibold tracking-[0.14em] text-brand-900" data-invitation-code>{{ $plainInvitationCode }}</code>
                    </div>
                    <button type="button" class="button-secondary shrink-0" data-copy-invitation-code data-copy-label="Salin kode">
                        <i data-lucide="copy" aria-hidden="true"></i>
                        <span>Salin kode</span>
                    </button>
                </div>
            @else
                <div class="mt-5 inline-flex items-center gap-2 rounded-full border border-line-soft bg-canvas px-3 py-2 text-xs font-medium text-ink-muted">
                    <span class="status-dot" aria-hidden="true"></span>
                    Kode aktif tersimpan aman sebagai hash dan tidak dapat ditampilkan kembali.
                </div>
            @endif
        </div>

        @can('rotateInvitationCode', $business)
            <form method="POST" action="{{ route('businesses.invitation-code.store', $business) }}" class="lg:text-right">
                @csrf
                <button type="submit" class="inline-flex h-11 w-full items-center justify-center rounded-xl bg-brand-900 px-5 text-sm font-semibold text-white transition hover:bg-brand-700 focus:ring-4 focus:ring-brand-100 lg:w-auto">
                    Buat ulang kode
                </button>
                <p class="mt-2 text-xs leading-5 text-ink-faint">Kode lama langsung tidak berlaku.</p>
            </form>
        @endcan
    </div>
</section>
