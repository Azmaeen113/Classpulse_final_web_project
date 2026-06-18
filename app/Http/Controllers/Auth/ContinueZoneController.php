<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Support\ZoneAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Bridges the shared login session into the correct role-zone session.
 * Prevents /login ↔ /teacher/dashboard redirect loops.
 */
class ContinueZoneController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if (! $user) {
            return redirect()->route('login');
        }

        return ZoneAuth::finishLogin(
            $request,
            $user,
            route($user->homeRouteName(), absolute: false),
            false,
            null
        );
    }
}
