<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PreferenceController extends Controller
{
    public function setTheme(Request $request): JsonResponse|RedirectResponse
    {
        $theme = $request->validate([
            'theme' => ['required', 'in:light,dark'],
        ])['theme'];

        // Plain (non-encrypted) cookie — listed in bootstrap/app.php encryptCookies except
        $cookie = cookie('cp_theme', $theme, 60 * 24 * 365, '/', null, false, false, false, 'Lax');
        $forgetLegacy = cookie()->forget('theme');

        if ($request->expectsJson() || $request->ajax() || $request->wantsJson()) {
            return response()->json([
                'ok' => true,
                'theme' => $theme,
            ])->withCookie($cookie)->withCookie($forgetLegacy);
        }

        return back()
            ->withCookie($cookie)
            ->withCookie($forgetLegacy)
            ->with('status', $theme === 'light' ? 'Light mode on.' : 'Dark mode on.');
    }
}
