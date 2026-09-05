<?php

namespace App\Http\Controllers\Account;

use App\Actions\BuildAccountNavigationContextAction;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class AccountSettingsController extends Controller
{
    public function show(Request $request, BuildAccountNavigationContextAction $buildNavigation): View
    {
        $sessionsSupported = config('session.driver') === 'database';
        $sessions = [];

        if ($sessionsSupported) {
            $connection = config('session.connection') ?: config('database.default');
            $table = (string) config('session.table', 'sessions');

            $sessions = DB::connection($connection)
                ->table($table)
                ->where('user_id', $request->user()->getAuthIdentifier())
                ->orderByDesc('last_activity')
                ->get()
                ->map(fn (object $session): array => [
                    'id' => $session->id,
                    'device' => $this->deviceLabel($session->user_agent),
                    'ipAddress' => $this->maskIpAddress($session->ip_address),
                    'lastActive' => now()->setTimestamp($session->last_activity)->diffForHumans(),
                    'isCurrent' => hash_equals($request->session()->getId(), $session->id),
                ])
                ->all();
        }

        return view('account.settings', [
            'navigation' => $buildNavigation->execute($request),
            'user' => $request->user(),
            'sessionsSupported' => $sessionsSupported,
            'sessions' => $sessions,
        ]);
    }

    private function deviceLabel(?string $userAgent): string
    {
        if ($userAgent === null || $userAgent === '') {
            return 'Perangkat tidak dikenal';
        }

        $browser = match (true) {
            Str::contains($userAgent, 'Edg/') => 'Microsoft Edge',
            Str::contains($userAgent, 'Chrome/') => 'Google Chrome',
            Str::contains($userAgent, 'Firefox/') => 'Mozilla Firefox',
            Str::contains($userAgent, ['Safari/', 'Version/']) => 'Safari',
            default => 'Browser lain',
        };
        $device = Str::contains($userAgent, ['Mobile', 'Android', 'iPhone', 'iPad'])
            ? 'perangkat seluler'
            : 'komputer';

        return $browser.' · '.$device;
    }

    private function maskIpAddress(?string $ipAddress): string
    {
        if ($ipAddress === null || $ipAddress === '') {
            return 'IP tidak tersedia';
        }

        if (str_contains($ipAddress, ':')) {
            return implode(':', array_slice(explode(':', $ipAddress), 0, 3)).'::';
        }

        $segments = explode('.', $ipAddress);

        if (count($segments) === 4) {
            $segments[3] = 'xxx';

            return implode('.', $segments);
        }

        return 'IP disamarkan';
    }
}
