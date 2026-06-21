<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Use a separate session cookie per role zone so teacher / student / admin
 * can stay logged in at the same time in one browser (different tabs).
 */
class ConfigureZoneSession
{
    public const ZONE_COOKIE = 'cp_active_zone';

    public function handle(Request $request, Closure $next): Response
    {
        $zone = $this->detectZone($request);

        config([
            'session.cookie' => $this->cookieName($zone),
            'classpulse.zone' => $zone,
        ]);

        /** @var Response $response */
        $response = $next($request);

        if (in_array($zone, ['teacher', 'student', 'admin'], true)) {
            $response->headers->setCookie(cookie(
                self::ZONE_COOKIE,
                $zone,
                60 * 24 * 30,
                '/',
                null,
                false,
                false,
                false,
                'Lax'
            ));
        }

        return $response;
    }

    public static function cookieName(string $zone): string
    {
        return match ($zone) {
            'teacher' => 'cp_session_teacher',
            'student' => 'cp_session_student',
            'admin' => 'cp_session_admin',
            default => 'cp_session_shared',
        };
    }

    private function detectZone(Request $request): string
    {
        // One-time claim URLs must open the matching role session cookie
        if ($request->is('zone-claim/teacher', 'zone-claim/teacher/*')) {
            return 'teacher';
        }
        if ($request->is('zone-claim/student', 'zone-claim/student/*')) {
            return 'student';
        }
        if ($request->is('zone-claim/admin', 'zone-claim/admin/*')) {
            return 'admin';
        }

        // Role zones — always win
        if ($request->is('teacher', 'teacher/*')) {
            return 'teacher';
        }

        if ($request->is('student', 'student/*')) {
            return 'student';
        }

        if ($request->is('admin', 'admin/*')) {
            return 'admin';
        }

        // Guest auth + marketing use the shared session so /login stays
        // available while other role tabs remain signed in.
        // NOTE: logout / profile / notifications / preferences MUST NOT be forced
        // shared — their CSRF tokens come from the role-zone session that rendered the form.
        if ($request->is(
            '/',
            'login',
            'register',
            'forgot-password',
            'reset-password',
            'reset-password/*',
            'confirm-password',
            'verify-email',
            'verify-email/*',
            'email/*',
            'auth/continue',
            'auth/reset-browser'
        )) {
            return 'shared';
        }

        $header = strtolower((string) $request->header('X-ClassPulse-Zone', ''));
        if (in_array($header, ['teacher', 'student', 'admin'], true)) {
            return $header;
        }

        $query = strtolower((string) $request->query('zone', ''));
        if (in_array($query, ['teacher', 'student', 'admin'], true)) {
            return $query;
        }

        $referer = (string) $request->headers->get('referer', '');
        if (str_contains($referer, '/teacher')) {
            return 'teacher';
        }
        if (str_contains($referer, '/student')) {
            return 'student';
        }
        if (str_contains($referer, '/admin')) {
            return 'admin';
        }

        $cookie = strtolower((string) $request->cookie(self::ZONE_COOKIE, ''));
        if (in_array($cookie, ['teacher', 'student', 'admin'], true)) {
            return $cookie;
        }

        return 'shared';
    }
}
