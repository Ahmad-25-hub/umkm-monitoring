<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterEmployeeAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterEmployeeRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

class RegisteredEmployeeController extends Controller
{
    public function create(): View
    {
        return view('auth.employee-register');
    }

    public function store(
        RegisterEmployeeRequest $request,
        RegisterEmployeeAction $registerEmployee,
    ): RedirectResponse {
        $registration = $registerEmployee->execute($request->validated());

        Auth::login($registration['user']);
        $request->session()->regenerate();
        $request->session()->put(
            'active_employee_business_id',
            $registration['membership']->business_id,
        );

        return redirect()
            ->route('employee.dashboard')
            ->with('success', 'Akun karyawan berhasil dibuat dan terhubung ke usaha.');
    }
}
