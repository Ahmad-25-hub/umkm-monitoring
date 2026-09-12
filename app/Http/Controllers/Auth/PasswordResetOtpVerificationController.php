<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\VerifyPasswordResetOtpRequest;
use App\Support\PasswordResetOtp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PasswordResetOtpVerificationController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $request->session()->has('password_reset_email')) {
            return redirect()->route('password.request');
        }

        return view('auth.verify-password-otp', [
            'email' => $request->session()->get('password_reset_email'),
            'role' => $request->session()->get('password_reset_role', 'owner'),
        ]);
    }

    public function store(VerifyPasswordResetOtpRequest $request, PasswordResetOtp $otp): RedirectResponse
    {
        $email = $request->session()->get('password_reset_email');

        if (! is_string($email)) {
            return redirect()->route('password.request');
        }

        $grant = $otp->verify($email, $request->validated('otp'));
        $request->session()->regenerate();
        $request->session()->put([
            'password_reset_grant' => $grant,
            'password_reset_verified_until' => now()->getTimestamp() + PasswordResetOtp::EXPIRES_IN_SECONDS,
        ]);

        return redirect()->route('password.reset');
    }
}
