<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        if ($request->user()) {
            return redirect()->route(
                $request->user()->hasActiveOwnedBusiness() ? 'overview' : 'employee.dashboard',
            );
        }

        return view('home');
    }
}
