<?php

namespace App\Http\Controllers\Employee;

use App\Actions\JoinBusinessByInvitationCodeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Employee\JoinBusinessRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BusinessMembershipController extends Controller
{
    public function create(Request $request): View
    {
        return view('employee.join-business', [
            'employee' => $request->user(),
        ]);
    }

    public function store(
        JoinBusinessRequest $request,
        JoinBusinessByInvitationCodeAction $joinBusiness,
    ): RedirectResponse {
        /** @var User $user */
        $user = $request->user();
        $membership = $joinBusiness->execute(
            $user,
            $request->string('business_code')->toString(),
        );

        $request->session()->put('active_employee_business_id', $membership->business_id);

        return redirect()
            ->route('employee.dashboard')
            ->with('success', 'Anda berhasil bergabung ke usaha.');
    }
}
