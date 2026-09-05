<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\EmailAddressChanged;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;

class PendingEmailVerificationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, int $user): RedirectResponse
    {
        abort_unless($request->user()->getAuthIdentifier() === $user, 403);
        $requestedAt = $request->integer('requested');
        $emailHash = $request->string('hash')->toString();

        $oldEmail = DB::transaction(function () use ($request, $requestedAt, $emailHash): string {
            /** @var User $account */
            $account = User::query()->lockForUpdate()->findOrFail($request->user()->getAuthIdentifier());

            abort_unless(
                $account->pending_email !== null
                && $account->pending_email_requested_at?->getTimestamp() === $requestedAt
                && hash_equals(hash('sha256', $account->pending_email), $emailHash),
                403,
            );

            $hasConflict = User::query()
                ->whereKeyNot($account->getKey())
                ->where(function (Builder $query) use ($account): void {
                    $query
                        ->where('email', $account->pending_email)
                        ->orWhere('pending_email', $account->pending_email);
                })
                ->exists();

            if ($hasConflict) {
                abort(409, 'Alamat email tersebut tidak dapat digunakan.');
            }

            $oldEmail = $account->email;
            $account->email = $account->pending_email;
            $account->email_verified_at = now();
            $account->pending_email = null;
            $account->pending_email_requested_at = null;
            $account->save();

            return $oldEmail;
        });

        Notification::route('mail', $oldEmail)->notify(
            new EmailAddressChanged($request->user()->fresh()->email),
        );

        return redirect()->route('account.settings')->with('status', 'email-updated');
    }
}
