<?php

use App\Http\Middleware\AdminMiddleware;
use App\Http\Middleware\ConfigureZoneSession;
use App\Http\Middleware\EnsureUserIsActive;
use App\Http\Middleware\StudentMiddleware;
use App\Http\Middleware\TeacherMiddleware;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Plain cookies readable by JS / zone detection
        $middleware->encryptCookies(except: [
            'cp_theme',
            'cp_active_zone',
        ]);

        // Separate session cookie per role zone (before StartSession)
        $middleware->prependToGroup('web', ConfigureZoneSession::class);

        $middleware->alias([
            'admin' => AdminMiddleware::class,
            'teacher' => TeacherMiddleware::class,
            'student' => StudentMiddleware::class,
            'active' => EnsureUserIsActive::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
