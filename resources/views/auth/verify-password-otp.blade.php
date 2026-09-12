@extends('auth.layout')

@section('title', 'Verifikasi OTP')
@section('homeUrl', route($role === 'employee' ? 'employee.login' : 'login'))
@section('contextKicker', 'Pemulihan akun')
@section('contextTitle', 'Satu langkah untuk memastikan ini adalah Anda.')
@section('contextDescription', 'Kode verifikasi membantu melindungi akses ke akun Anda. Jangan bagikan kode kepada siapa pun.')
@section('contextFooter', 'Pemulihan akun yang aman untuk pengguna NADI.')

@section('content')
    <p class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-700">Langkah 2 dari 3</p>
    <h2 class="mt-3 text-3xl font-semibold tracking-[-0.04em] text-ink">Periksa email Anda</h2>
    <p class="mt-3 text-sm leading-6 text-ink-muted">Masukkan kode OTP 6 digit untuk <span class="break-all font-semibold text-ink">{{ $email }}</span>.</p>

    @include('auth.password-reset-errors')

    <form method="POST" action="{{ route('password.otp.verify') }}" class="mt-6 flex flex-col gap-5">
        @csrf

        <div class="flex flex-col gap-2">
            <label for="otp" class="text-sm font-semibold text-ink">Kode OTP</label>
            <input id="otp" name="otp" type="text" inputmode="numeric" autocomplete="one-time-code" pattern="[0-9]{6}" minlength="6" maxlength="6" required autofocus aria-describedby="otp-help" aria-invalid="{{ $errors->has('otp') ? 'true' : 'false' }}" placeholder="000000" class="h-14 rounded-xl border border-line bg-white px-4 text-center text-2xl font-semibold tracking-[0.35em] text-ink shadow-sm placeholder:text-ink-faint focus:border-brand-200 focus:ring-4 focus:ring-brand-100/60">
            <p id="otp-help" class="text-xs leading-5 text-ink-muted">Kode berlaku 5 menit. Maksimal 5 percobaan sebelum Anda perlu meminta kode baru.</p>
        </div>

        <button type="submit" class="inline-flex min-h-12 items-center justify-center gap-3 rounded-xl bg-brand-900 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-brand-700 focus:ring-4 focus:ring-brand-100">
            Verifikasi OTP
            <i data-lucide="shield-check" class="size-4 shrink-0" aria-hidden="true"></i>
        </button>
    </form>

    <div class="mt-6 flex flex-col gap-3 border-t border-line pt-5 text-center">
        <p class="text-xs leading-5 text-ink-muted">Belum menerima email? Periksa folder spam. Anda dapat meminta kode baru setelah 60 detik. Kode sebelumnya akan diganti.</p>
        <form method="POST" action="{{ route('password.email') }}">
            @csrf
            <input type="hidden" name="email" value="{{ $email }}">
            <input type="hidden" name="role" value="{{ $role }}">
            <button type="submit" class="min-h-11 rounded-lg px-3 text-sm font-semibold text-brand-700 hover:text-brand-900 focus:ring-4 focus:ring-brand-100">Kirim ulang OTP</button>
        </form>
        <a href="{{ route('password.request', ['role' => $role]) }}" class="text-sm font-semibold text-brand-700 hover:text-brand-900">Gunakan email lain</a>
        <a href="{{ route($role === 'employee' ? 'employee.login' : 'login') }}" class="text-sm text-ink-muted hover:text-ink">Kembali ke halaman masuk</a>
    </div>
@endsection
