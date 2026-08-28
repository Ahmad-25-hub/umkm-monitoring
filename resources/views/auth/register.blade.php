@extends('auth.layout')

@section('title', 'Daftar Pemilik Usaha')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Mulai bersama NADI</p>
        <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-ink">Daftarkan usaha Anda</h2>
        <p class="mt-3 text-sm leading-6 text-ink-muted">Akun ini akan menjadi pemilik pertama. Setelah selesai, Anda akan menerima kode undangan khusus untuk karyawan.</p>
    </div>

    <form method="POST" action="{{ route('register.store') }}" class="mt-8 flex flex-col gap-5" novalidate>
        @csrf

        <div class="grid gap-5 sm:grid-cols-2">
            <div class="flex flex-col gap-2">
                <label for="owner_name" class="text-sm font-semibold text-ink">Nama pemilik</label>
                <input
                    id="owner_name"
                    name="owner_name"
                    type="text"
                    value="{{ old('owner_name') }}"
                    autocomplete="name"
                    required
                    autofocus
                    @class([
                        'h-12 rounded-xl border bg-white px-4 text-sm shadow-sm transition focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
                        'border-critical' => $errors->has('owner_name'),
                        'border-line' => ! $errors->has('owner_name'),
                    ])
                    placeholder="Nama lengkap"
                >
                @error('owner_name')
                    <p class="text-xs font-medium text-critical">{{ $message }}</p>
                @enderror
            </div>

            <div class="flex flex-col gap-2">
                <label for="business_name" class="text-sm font-semibold text-ink">Nama usaha</label>
                <input
                    id="business_name"
                    name="business_name"
                    type="text"
                    value="{{ old('business_name') }}"
                    autocomplete="organization"
                    required
                    @class([
                        'h-12 rounded-xl border bg-white px-4 text-sm shadow-sm transition focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
                        'border-critical' => $errors->has('business_name'),
                        'border-line' => ! $errors->has('business_name'),
                    ])
                    placeholder="Contoh: Toko Maju"
                >
                @error('business_name')
                    <p class="text-xs font-medium text-critical">{{ $message }}</p>
                @enderror
            </div>
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
                    'h-12 rounded-xl border bg-white px-4 text-sm shadow-sm transition focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
                    'border-critical' => $errors->has('email'),
                    'border-line' => ! $errors->has('email'),
                ])
                placeholder="nama@email.com"
            >
            @error('email')
                <p class="text-xs font-medium text-critical">{{ $message }}</p>
            @enderror
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
                        'h-12 rounded-xl border bg-white px-4 text-sm shadow-sm transition focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
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
                    class="h-12 rounded-xl border border-line bg-white px-4 text-sm shadow-sm transition focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60"
                    placeholder="Ulangi password"
                >
            </div>
        </div>

        <p class="text-xs leading-5 text-ink-faint">Gunakan minimal 8 karakter dengan huruf besar, huruf kecil, dan angka.</p>

        <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-brand-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-4 focus:ring-brand-100">
            Buat akun dan usaha
        </button>
    </form>

    <p class="mt-7 text-center text-sm text-ink-muted">
        Sudah memiliki akun?
        <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Masuk</a>
    </p>
@endsection
