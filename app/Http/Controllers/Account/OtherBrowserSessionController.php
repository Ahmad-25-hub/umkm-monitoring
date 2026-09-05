<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Http\Requests\Account\DestroyOtherBrowserSessionsRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class OtherBrowserSessionController extends Controller
{
    public function destroy(DestroyOtherBrowserSessionsRequest $request): RedirectResponse
    {
        if (config('session.driver') !== 'database') {
            return back()->withErrors([
                'current_password' => 'Pengelolaan sesi tidak tersedia pada konfigurasi saat ini.',
            ], 'sessionDestroy');
        }

        $connection = config('session.connection') ?: config('database.default');
        $table = (string) config('session.table', 'sessions');

        DB::connection($connection)
            ->table($table)
            ->where('user_id', $request->user()->getAuthIdentifier())
            ->where('id', '!=', $request->session()->getId())
            ->delete();

        /** @var User $user */
        $user = $request->user();
        $user->setRememberToken(Str::random(60));
        $user->save();
        $request->session()->regenerate();

        return back()->with('status', 'other-sessions-destroyed');
    }
}
