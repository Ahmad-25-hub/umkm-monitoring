@extends('auth.layout')

@section('title', 'Lupa Password')
@section('homeUrl', route($role === 'employee' ? 'employee.login' : 'login'))
@section('contextKicker', 'Pemulihan akun')
@section('contextTitle', 'Kembali terhubung dengan ruang kerja Anda.')
@section('contextDescription', 'Verifikasi email Anda untuk membuat password baru dan melanjutkan aktivitas di NADI.')
@section('contextFooter', 'Pemulihan akun yang aman untuk pengguna NADI.')

@section('content')
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Langkah 1 dari 3</p>
    <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-ink">Lupa password?</h2>
    <p class="mt-3 text-sm leading-6 text-ink-muted">Masukkan email akun Anda. Kami akan mengirimkan kode OTP untuk memverifikasi permintaan reset password.</p>

    @include('auth.password-reset-errors')

    <form method="POST" action="{{ route('password.email') }}" class="mt-6 flex flex-col gap-5">
        @csrf
        <input type="hidden" name="role" value="{{ $role }}">

        <div class="flex flex-col gap-2">
            <label for="email" class="text-sm font-semibold text-ink">Email akun</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" required autofocus maxlength="255" aria-invalid="{{ $errors->has('email') ? 'true' : 'false' }}" placeholder="nama@email.com" class="h-12 rounded-xl border border-line bg-white px-4 text-sm text-ink shadow-sm placeholder:text-ink-faint focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60">
        </div>

        <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-brand-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-4 focus:ring-brand-100">
            Kirim kode OTP
            <i data-lucide="mail" class="size-4 shrink-0" aria-hidden="true"></i>
        </button>
    </form>

    <p class="mt-7 text-center text-sm text-ink-muted">
        <a href="{{ route($role === 'employee' ? 'employee.login' : 'login') }}" class="font-semibold text-brand-700 hover:text-brand-900">Kembali ke halaman masuk</a>
    </p>
@endsection
