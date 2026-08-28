<?php

namespace App\Http\Controllers\Auth;

use App\Actions\RegisterOwnerAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterOwnerRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RegisteredOwnerController extends Controller
{
    public function create(): View
    {
        return view('auth.register');
    }

    public function store(RegisterOwnerRequest $request, RegisterOwnerAction $registerOwner): RedirectResponse
    {
        $result = DB::transaction(function () use ($request, $registerOwner): array {
            $registration = $registerOwner->execute($request->validated());

            Auth::login($registration['user']);
            $request->session()->regenerate();
            $request->session()->put('active_business_id', $registration['business']->id);

            return $registration;
        }, attempts: 3);

        $request->session()->flash('invitation_code', $result['invitation_code']);
        $request->session()->flash('success', 'Akun dan usaha berhasil dibuat.');

        return redirect()->route('overview');
    }
}
