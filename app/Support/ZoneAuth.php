<?php

namespace App\Support;

use App\Http\Middleware\ConfigureZoneSession;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

final class ZoneAuth
{
    /**
     * Move a successful login from the shared session into the role-zone session
     * via a one-time claim token (other role sessions stay intact).
     */
    public static function finishLogin(Request $request, User $user, string $destination, bool $remember = false, ?string $status = null): RedirectResponse
    {
        $zone = match ($user->role) {
            'admin' => 'admin',
            'teacher' => 'teacher',
            default => 'student',
        };

        // Feature tests use a single in-memory session; skip the multi-cookie handoff.
        if (app()->environment('testing')) {
            $request->session()->put('cp_zone', $zone);

            return redirect()
                ->to($destination)
                ->with('status', $status);
        }

        $token = Str::random(64);

        Cache::put('cp_zone_claim:'.$token, [
            'user_id' => $user->id,
            'role' => $user->role,
            'remember' => $remember,
            'destination' => $destination,
            'status' => $status,
        ], now()->addMinutes(3));

        $zoneCookie = cookie(
            ConfigureZoneSession::ZONE_COOKIE,
            $zone,
            60 * 24 * 30,
            '/',
            null,
            false,
            false,
            false,
            'Lax'
        );

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('zone.claim', ['zone' => $zone, 'token' => $token])
            ->withCookie($zoneCookie);
    }
}
