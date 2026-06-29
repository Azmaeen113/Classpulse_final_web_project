<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardRedirectController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            abort(403, 'Account inactive.');
        }

        return redirect()->route($user->homeRouteName());
    }
}
