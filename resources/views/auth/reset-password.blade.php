@extends('auth.layout')

@section('title', 'Buat Password Baru')
@section('homeUrl', route($role === 'employee' ? 'employee.login' : 'login'))
@section('contextKicker', 'Pemulihan akun')
@section('contextTitle', 'Awal baru untuk akses yang lebih aman.')
@section('contextDescription', 'Gunakan password yang kuat dan berbeda dari password sebelumnya untuk melindungi akun Anda.')
@section('contextFooter', 'Pemulihan akun yang aman untuk pengguna NADI.')

@section('content')
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Langkah 3 dari 3</p>
    <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-ink">Buat password baru</h2>
    <p class="mt-3 text-sm leading-6 text-ink-muted">Email berhasil diverifikasi. Simpan password baru Anda dalam 5 menit untuk menyelesaikan pemulihan akun.</p>

    @include('auth.password-reset-errors')

    <form method="POST" action="{{ route('password.update') }}" class="mt-6 flex flex-col gap-5">
        @csrf

        <div class="flex flex-col gap-2">
            <label for="password" class="text-sm font-semibold text-ink">Password baru</label>
            <input id="password" name="password" type="password" autocomplete="new-password" required autofocus minlength="8" maxlength="255" aria-describedby="password-help" aria-invalid="{{ $errors->has('password') ? 'true' : 'false' }}" class="h-12 rounded-xl border border-line bg-white px-4 text-sm text-ink shadow-sm focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60">
            <p id="password-help" class="text-xs leading-5 text-ink-muted">Minimal 8 karakter dengan huruf besar, huruf kecil, dan angka.</p>
        </div>

        <div class="flex flex-col gap-2">
            <label for="password_confirmation" class="text-sm font-semibold text-ink">Konfirmasi password baru</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" required minlength="8" maxlength="255" class="h-12 rounded-xl border border-line bg-white px-4 text-sm text-ink shadow-sm focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60">
        </div>

        <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-brand-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-4 focus:ring-brand-100">
            Simpan password baru
            <i data-lucide="arrow-right" class="size-4 shrink-0" aria-hidden="true"></i>
        </button>
    </form>

    <p class="mt-7 text-center text-sm text-ink-muted">
        <a href="{{ route($role === 'employee' ? 'employee.login' : 'login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Kembali ke halaman masuk</a>
    </p>
@endsection
