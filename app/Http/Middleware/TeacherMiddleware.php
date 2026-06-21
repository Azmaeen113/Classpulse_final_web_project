<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TeacherMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->is_active) {
            if ($user) {
                auth()->logout();
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Your account has been suspended.']);
        }

        if (! $user->isTeacher()) {
            return redirect()->route($user->homeRouteName());
        }

        return $next($request);
    }
}
