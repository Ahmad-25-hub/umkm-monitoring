<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\EmployeeLoginRequest;
use App\Models\BusinessMembership;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class EmployeeAuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.employee-login');
    }

    public function store(EmployeeLoginRequest $request): RedirectResponse
    {
        $request->authenticate();
        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();
        $activeMembership = $user->memberships()
            ->active()
            ->where('role', BusinessMembership::ROLE_EMPLOYEE)
            ->oldest('id')
            ->first();

        if ($activeMembership !== null) {
            $request->session()->put('active_employee_business_id', $activeMembership->business_id);

            return redirect()->intended(route('employee.dashboard', absolute: false));
        }

        return redirect()->route('employee.business.join.create');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('employee.login');
    }
}
