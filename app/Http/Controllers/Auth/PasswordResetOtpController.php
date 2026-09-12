<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\SendPasswordResetOtpRequest;
use App\Support\PasswordResetOtp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordResetOtpController extends Controller
{
    public function create(Request $request): View
    {
        return view('auth.forgot-password', [
            'role' => $request->query('role') === 'employee' ? 'employee' : 'owner',
        ]);
    }

    public function store(SendPasswordResetOtpRequest $request, PasswordResetOtp $otp): RedirectResponse
    {
        $email = $request->validated('email');
        $otp->send($email);

        $request->session()->forget(['password_reset_grant', 'password_reset_verified_until']);
        $request->session()->put([
            'password_reset_email' => $email,
            'password_reset_role' => $request->validated('role') ?? 'owner',
        ]);

        return redirect()->route('password.otp')
            ->with('status', 'Jika email terdaftar, kode OTP akan dikirim ke alamat tersebut. Periksa juga folder spam.');
    }
}
