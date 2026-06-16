<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Must NOT send users straight to /teacher/* from the shared login session —
        // that empty zone cookie causes ERR_TOO_MANY_REDIRECTS.
        RedirectIfAuthenticated::redirectUsing(function () {
            return Auth::check()
                ? route('auth.continue')
                : route('login');
        });
    }
}
