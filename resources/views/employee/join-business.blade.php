@extends('auth.layout')

@section('title', 'Gabung ke Usaha')
@section('homeUrl', route('employee.business.join.create'))
@section('metaDescription', 'Masukkan Kode Usaha untuk bergabung sebagai karyawan.')
@section('contextKicker', 'Langkah terakhir')
@section('contextTitle', 'Hubungkan akun Anda dengan usaha yang tepat.')
@section('contextDescription', 'Gunakan kode dari pemilik agar akun Anda terhubung dengan usaha yang tepat.')
@section('contextFooter', 'Kode dapat diperoleh dari pemilik usaha.')

@section('content')
    @if (session('access_notice'))
        <p class="mb-5 rounded-xl border border-line bg-white p-4 text-sm text-ink-muted" role="status">{{ session('access_notice') }}</p>
    @endif
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Halo, {{ $employee->name }}</p>
        <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-ink">Masukkan Kode Usaha</h2>
        <p class="mt-3 text-sm leading-6 text-ink-muted">Kode ini diberikan oleh pemilik usaha. Setelah terverifikasi, akun Anda otomatis bergabung sebagai karyawan.</p>
    </div>

    <form method="POST" action="{{ route('employee.business.join.store') }}" class="mt-8 flex flex-col gap-5" novalidate>
        @csrf

        <div class="flex flex-col gap-2">
            <label for="business_code" class="text-sm font-semibold text-ink">Kode Usaha</label>
            <input
                id="business_code"
                name="business_code"
                type="text"
                value="{{ old('business_code') }}"
                autocomplete="off"
                required
                autofocus
                spellcheck="false"
                @class([
                    'h-14 rounded-xl border bg-white px-4 font-mono text-base font-semibold uppercase tracking-[0.12em] text-ink shadow-sm transition placeholder:font-sans placeholder:text-sm placeholder:font-normal placeholder:normal-case placeholder:tracking-normal placeholder:text-ink-faint focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
                    'border-critical' => $errors->has('business_code'),
                    'border-line' => ! $errors->has('business_code'),
                ])
                placeholder="Contoh: ABCD-EFGH-2345"
            >
            @error('business_code')
                <p class="text-xs font-medium text-critical">{{ $message }}</p>
            @enderror
        </div>

        <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-brand-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-4 focus:ring-brand-100">
            Verifikasi dan gabung
        </button>
    </form>

    <form method="POST" action="{{ route('employee.logout') }}" class="mt-5 text-center">
        @csrf
        <button type="submit" class="text-sm font-semibold text-ink-muted hover:text-ink">Keluar dan gunakan akun lain</button>
    </form>
@endsection
