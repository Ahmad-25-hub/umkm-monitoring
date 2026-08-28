<?php

namespace App\Http\Controllers;

use App\Actions\RotateBusinessInvitationCodeAction;
use App\Models\Business;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BusinessInvitationCodeController extends Controller
{
    public function store(
        Request $request,
        Business $business,
        RotateBusinessInvitationCodeAction $rotateInvitationCode,
    ): RedirectResponse {
        Gate::authorize('rotateInvitationCode', $business);

        /** @var User $user */
        $user = $request->user();
        $invitationCode = $rotateInvitationCode->execute($business, $user);

        $request->session()->flash('invitation_code', $invitationCode);
        $request->session()->flash('success', 'Kode undangan baru berhasil dibuat. Kode lama tidak berlaku lagi.');

        return redirect()->route('overview');
    }
}
