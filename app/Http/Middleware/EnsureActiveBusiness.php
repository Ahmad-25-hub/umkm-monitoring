<?php

namespace App\Http\Middleware;

use App\Models\BusinessMembership;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveBusiness
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $activeMemberships = $user->memberships()
            ->active()
            ->where('role', BusinessMembership::ROLE_OWNER)
            ->with('business')
            ->orderBy('id')
            ->get();

        if ($activeMemberships->isEmpty()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Akun Anda tidak memiliki keanggotaan usaha aktif.',
            ]);
        }

        $preferredBusinessId = (int) $request->session()->get('active_business_id');
        $activeMembership = $activeMemberships->first(
            fn (BusinessMembership $membership): bool => $membership->business_id === $preferredBusinessId,
        ) ?? $activeMemberships->first();

        $request->session()->put('active_business_id', $activeMembership->business_id);
        $request->attributes->set('activeBusiness', $activeMembership->business);
        $request->attributes->set('activeBusinessMemberships', $activeMemberships);

        return $next($request);
    }
}
