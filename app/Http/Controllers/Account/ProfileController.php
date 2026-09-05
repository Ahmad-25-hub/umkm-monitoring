<?php

namespace App\Http\Controllers\Account;

use App\Actions\BuildAccountNavigationContextAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Account\UpdateProfileRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function show(Request $request, BuildAccountNavigationContextAction $buildNavigation): View
    {
        return view('account.profile', [
            'navigation' => $buildNavigation->execute($request),
            'user' => $request->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->name = $request->validated('name');
        $user->save();

        return back()->with('status', 'profile-updated');
    }
}
