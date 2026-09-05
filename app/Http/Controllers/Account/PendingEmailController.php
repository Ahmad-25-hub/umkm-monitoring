<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateEmailRequest;
use App\Models\User;
use App\Notifications\VerifyPendingEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;

class PendingEmailController extends Controller
{
    public function update(UpdateEmailRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->pending_email = $request->validated('new_email');
        $user->pending_email_requested_at = now();
        $user->save();

        $this->sendVerification($user);

        return back()->with('status', 'email-verification-sent');
    }

    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();

        if ($user->pending_email === null) {
            return back()->withErrors([
                'new_email' => 'Tidak ada perubahan email yang menunggu verifikasi.',
            ], 'emailUpdate');
        }

        $user->pending_email_requested_at = now();
        $user->save();

        $this->sendVerification($user);

        return back()->with('status', 'email-verification-sent');
    }

    private function sendVerification(User $user): void
    {
        Notification::route('mail', $user->pending_email)->notify(
            new VerifyPendingEmail(
                userId: $user->getKey(),
                pendingEmail: $user->pending_email,
                requestedAt: $user->pending_email_requested_at->getTimestamp(),
            ),
        );
    }
}
