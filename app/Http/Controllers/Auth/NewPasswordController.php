<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Support\PasswordResetOtp;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NewPasswordController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        if (! $this->hasVerifiedSession($request)) {
            return $this->restart($request);
        }

        return view('auth.reset-password', [
            'role' => $request->session()->get('password_reset_role', 'owner'),
        ]);
    }

    public function store(ResetPasswordRequest $request, PasswordResetOtp $otp): RedirectResponse
    {
        if (! $this->hasVerifiedSession($request)
            || ! $otp->reset(
                $request->session()->get('password_reset_email'),
                $request->session()->get('password_reset_grant'),
                $request->validated('password'),
            )) {
            return $this->restart($request);
        }

        $loginRoute = $request->session()->get('password_reset_role') === 'employee' ? 'employee.login' : 'login';
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route($loginRoute)
            ->with('status', 'Password berhasil diperbarui. Silakan masuk dengan password baru Anda.');
    }

    private function hasVerifiedSession(Request $request): bool
    {
        return is_string($request->session()->get('password_reset_email'))
            && is_string($request->session()->get('password_reset_grant'))
            && $request->session()->get('password_reset_verified_until', 0) > now()->getTimestamp();
    }

    private function restart(Request $request): RedirectResponse
    {
        $role = $request->session()->get('password_reset_role', 'owner');
        $request->session()->forget([
            'password_reset_email', 'password_reset_grant', 'password_reset_verified_until',
        ]);

        return redirect()->route('password.request', ['role' => $role])
            ->withErrors(['email' => 'Sesi reset password tidak valid atau sudah kedaluwarsa. Silakan minta OTP baru.']);
    }
}
