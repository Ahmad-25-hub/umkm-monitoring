<?php

namespace App\Http\Controllers;

use App\Models\Business;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ActiveBusinessController extends Controller
{
    public function store(Request $request, Business $business): RedirectResponse
    {
        Gate::authorize('activateForOwner', $business);

        $request->session()->put('active_business_id', $business->id);

        return redirect()->route('overview');
    }
}
