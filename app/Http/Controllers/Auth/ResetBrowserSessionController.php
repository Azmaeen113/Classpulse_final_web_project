<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\ConfigureZoneSession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Clears ClassPulse session cookies when the browser is stuck in a redirect loop.
 */
class ResetBrowserSessionController extends Controller
{
    public function __invoke(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $response = redirect()
            ->route('login')
            ->with('status', 'Browser session cleared. Please log in again.');

        foreach ([
            ConfigureZoneSession::cookieName('shared'),
            ConfigureZoneSession::cookieName('teacher'),
            ConfigureZoneSession::cookieName('student'),
            ConfigureZoneSession::cookieName('admin'),
            ConfigureZoneSession::ZONE_COOKIE,
            'cp_auth_handoff',
            config('session.cookie'),
        ] as $name) {
            if ($name) {
                $response->withCookie(cookie()->forget($name));
            }
        }

        return $response;
    }
}
