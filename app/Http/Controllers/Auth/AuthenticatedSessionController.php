<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Support\ZoneAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    public function create(): View
    {
        return view('auth.login');
    }

    public function store(LoginRequest $request, ActivityLogService $activity): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = $request->user();

        if (! $user->is_active) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()->withErrors([
                'email' => 'Your account has been suspended.',
            ]);
        }

        $activity->log($user, 'auth.login', 'User logged in', $request);

        return ZoneAuth::finishLogin(
            $request,
            $user,
            $this->safeIntendedPath($request, $user),
            $request->boolean('remember'),
            'Logged in successfully.'
        );
    }

    public function destroy(Request $request, ActivityLogService $activity): RedirectResponse
    {
        if ($request->user()) {
            $activity->log($request->user(), 'auth.logout', 'User logged out', $request);
        }

        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/')->with('status', 'Logged out successfully');
    }

    /**
     * Honor url.intended only when it belongs to this user's role zone.
     */
    private function safeIntendedPath(Request $request, User $user): string
    {
        $home = route($user->homeRouteName(), absolute: false);
        $intended = $request->session()->pull('url.intended', $home);
        $path = parse_url($intended, PHP_URL_PATH) ?: '';

        $allowedPrefixes = match ($user->role) {
            'admin' => ['/admin', '/dashboard', '/profile', '/notifications', '/api/poll'],
            'teacher' => ['/teacher', '/dashboard', '/profile', '/notifications', '/api/poll'],
            default => ['/student', '/dashboard', '/profile', '/notifications', '/api/poll'],
        };

        foreach ($allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, rtrim($prefix, '/').'/')) {
                return $intended;
            }
        }

        return $home;
    }
}
