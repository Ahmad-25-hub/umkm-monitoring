@extends('auth.layout')

@section('title', 'Masuk Karyawan')
@section('homeUrl', route('employee.login'))
@section('metaDescription', 'Masuk ke ruang kerja karyawan NADI.')
@section('contextKicker', 'Ruang kerja karyawan')
@section('contextTitle', 'Terhubung dengan usaha tempat Anda bekerja.')
@section('contextDescription', 'Masuk menggunakan akun pribadi, lalu gunakan Kode Usaha dari pemilik untuk bergabung ke tim yang tepat.')
@section('contextFooter', 'Akses aman untuk karyawan NADI.')

@section('content')
    <div>
        <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Portal karyawan</p>
        <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-ink">Masuk ke akun karyawan</h2>
        <p class="mt-3 text-sm leading-6 text-ink-muted">Gunakan email dan password akun Anda. Setelah masuk, Anda akan diminta memasukkan Kode Usaha bila belum menjadi anggota.</p>
    </div>

    <form method="POST" action="{{ route('employee.login.store') }}" class="mt-8 flex flex-col gap-5" novalidate>
        @csrf

        <div class="flex flex-col gap-2">
            <label for="email" class="text-sm font-semibold text-ink">Email</label>
            <input
                id="email"
                name="email"
                type="email"
                value="{{ old('email') }}"
                autocomplete="email"
                required
                autofocus
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

        <div class="flex flex-col gap-2">
            <label for="password" class="text-sm font-semibold text-ink">Password</label>
            <input
                id="password"
                name="password"
                type="password"
                autocomplete="current-password"
                required
                @class([
                    'h-12 rounded-xl border bg-white px-4 text-sm text-ink shadow-sm transition placeholder:text-ink-faint focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60',
                    'border-critical' => $errors->has('password'),
                    'border-line' => ! $errors->has('password'),
                ])
                placeholder="Masukkan password"
            >
            @error('password')
                <p class="text-xs font-medium text-critical">{{ $message }}</p>
            @enderror
        </div>

        <label class="inline-flex w-fit items-center gap-3 text-sm text-ink-muted">
            <input name="remember" type="checkbox" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-line text-brand-600 focus:ring-brand-200">
            Ingat saya
        </label>

        <button type="submit" class="inline-flex h-12 items-center justify-center rounded-xl bg-brand-900 px-5 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-4 focus:ring-brand-100">
            Masuk sebagai karyawan
        </button>
    </form>

    <div class="mt-7 grid gap-3 text-center text-sm text-ink-muted">
        <p>
            Belum memiliki akun?
            <a href="{{ route('employee.register') }}" class="font-semibold text-brand-700 hover:text-brand-900">Daftar sebagai karyawan</a>
        </p>
        <p>
            Anda pemilik usaha?
            <a href="{{ route('login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Masuk ke ruang pemilik</a>
        </p>
    </div>
@endsection
