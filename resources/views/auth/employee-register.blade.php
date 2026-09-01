@extends('auth.layout')

@section('title', 'Daftar Karyawan')
@section('homeUrl', route('employee.register'))
@section('metaDescription', 'Daftar sebagai karyawan NADI menggunakan Kode Usaha.')
@section('contextKicker', 'Registrasi karyawan')
@section('contextTitle', 'Buat akun dan langsung bergabung ke tim Anda.')
@section('contextDescription', 'Kode Usaha memastikan akun baru hanya terhubung dengan usaha yang benar dan terdaftar sebagai karyawan aktif.')
@section('contextFooter', 'Minta Kode Usaha kepada pemilik sebelum mendaftar.')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Mulai sebagai karyawan</p>
        <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-ink">Daftar akun karyawan</h2>
        <p class="mt-3 text-sm leading-6 text-ink-muted">Lengkapi akun pribadi Anda dan masukkan Kode Usaha dari pemilik. Setelah berhasil, Anda langsung masuk ke Dashboard Karyawan.</p>
    </div>

    <form method="POST" action="{{ route('employee.register.store') }}" class="mt-8 flex flex-col gap-5" novalidate>
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <label for="name" class="text-sm font-semibold text-ink">Nama lengkap</label>
                <input
                    id="name"
                    name="name"
                    type="text"
                    value="{{ old('name') }}"
                    autocomplete="name"
                    required
                    autofocus
                    @class([
                        'h-12 rounded-xl border bg-white px-4 text-sm text-ink shadow-sm transition placeholder:text-ink-faint focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
                        'border-critical' => $errors->has('name'),
                        'border-line' => ! $errors->has('name'),
                    ])
                    placeholder="Nama lengkap"
                >
                @error('name')
                    <p class="text-xs font-medium text-critical">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-2">
                <label for="email" class="text-sm font-semibold text-ink">Email</label>
                <input
                    id="email"
                    name="email"
                    type="email"
                    value="{{ old('email') }}"
                    autocomplete="email"
                    required
                    @class([
                        'h-12 rounded-xl border bg-white px-4 text-sm text-ink shadow-sm transition placeholder:text-ink-faint focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
                        'border-critical' => $errors->has('email'),
                        'border-line' => ! $errors->has('email'),
                    ])
                    placeholder="nama@email.com"
                >
                @error('email')
                    <p class="text-xs font-medium text-critical">{{ $message }}</p>
                @enderror
            </div>
        </div>

        <div class="flex flex-col gap-2">
            <label for="business_code" class="text-sm font-semibold text-ink">Kode Usaha</label>
            <input
                id="business_code"
                name="business_code"
                type="text"
                value="{{ old('business_code') }}"
                autocomplete="off"
                required
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
            <p class="text-xs leading-5 text-ink-faint">Kode ini dapat diperoleh dari pemilik usaha.</p>
        </div>

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <label for="password" class="text-sm font-semibold text-ink">Password</label>
                <input
                    id="password"
                    name="password"
                    type="password"
                    autocomplete="new-password"
                    required
                    @class([
                        'h-12 rounded-xl border bg-white px-4 text-sm text-ink shadow-sm transition placeholder:text-ink-faint focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
                        'border-critical' => $errors->has('password'),
                        'border-line' => ! $errors->has('password'),
                    ])
                    placeholder="Minimal 8 karakter"
                >
                @error('password')
                    <p class="text-xs font-medium text-critical">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-2">
                <label for="password_confirmation" class="text-sm font-semibold text-ink">Konfirmasi password</label>
                <input
                    id="password_confirmation"
                    name="password_confirmation"
                    type="password"
                    autocomplete="new-password"
                    required
                    class="h-12 rounded-xl border border-line bg-white px-4 text-sm text-ink shadow-sm transition placeholder:text-ink-faint focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60"
                    placeholder="Ulangi password"
                >
            </div>
        </div>

        <p class="text-xs leading-5 text-ink-faint">Gunakan minimal 8 karakter dengan huruf besar, huruf kecil, dan angka.</p>

        <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-brand-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-4 focus:ring-brand-100">
            Daftar dan bergabung
        </button>
    </form>

    <div class="mt-7 grid gap-3 text-center text-sm text-ink-muted">
        <p>
            Sudah memiliki akun?
            <a href="{{ route('employee.login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Masuk sebagai karyawan</a>
        </p>
        <p>
            Anda pemilik usaha?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Masuk ke ruang pemilik</a>
        </p>
    </div>
@endsection
