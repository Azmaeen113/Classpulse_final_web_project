<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class ZoneClaimController extends Controller
{
    public function __invoke(Request $request, string $zone, string $token): RedirectResponse
    {
        abort_unless(in_array($zone, ['teacher', 'student', 'admin'], true), 404);

        $payload = Cache::pull('cp_zone_claim:'.$token);

        if (! is_array($payload)
            || ($payload['role'] ?? null) !== $zone
            || empty($payload['user_id'])
        ) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'That sign-in link expired. Please log in again.']);
        }

        $user = User::query()
            ->whereKey((int) $payload['user_id'])
            ->where('is_active', true)
            ->first();

        if (! $user || $user->role !== $zone) {
            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Unable to open that account session.']);
        }

        Auth::login($user, (bool) ($payload['remember'] ?? false));
        $request->session()->regenerate();

        return redirect()
            ->to($payload['destination'] ?? route($user->homeRouteName(), absolute: false))
            ->with('status', $payload['status'] ?? 'Logged in successfully.');
    }
}
