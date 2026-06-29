<?php

use App\Http\Middleware\TrackAuthenticatedWebSession;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

$application = Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // health: '/up',
    );

if (! is_native_bootstrap_runtime()) {
    $application = $application->withBroadcasting(
        __DIR__.'/../routes/channels.php',
        ['middleware' => ['web', 'auth', 'throttle:60,1']],
    );
}

return $application
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: TrackAuthenticatedWebSession::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
