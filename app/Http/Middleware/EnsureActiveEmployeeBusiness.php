<?php

namespace App\Http\Middleware;

use App\Models\BusinessMembership;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureActiveEmployeeBusiness
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $activeMemberships = $request->user()->memberships()
            ->active()
            ->where('role', BusinessMembership::ROLE_EMPLOYEE)
            ->with('business')
            ->oldest('id')
            ->get();

        if ($activeMemberships->isEmpty()) {
            $request->session()->forget('active_employee_business_id');

            return redirect()->route('employee.business.join.create');
        }

        $preferredBusinessId = (int) $request->session()->get('active_employee_business_id');
        $activeMembership = $activeMemberships->first(
            fn (BusinessMembership $membership): bool => $membership->business_id === $preferredBusinessId,
        ) ?? $activeMemberships->first();

        $request->session()->put('active_employee_business_id', $activeMembership->business_id);
        $request->attributes->set('activeEmployeeBusiness', $activeMembership->business);
        $request->attributes->set('activeEmployeeBusinessMemberships', $activeMemberships);

        return $next($request);
    }
}
