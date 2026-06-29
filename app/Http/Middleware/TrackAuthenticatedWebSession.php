<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Auth\WebSessionDevices;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TrackAuthenticatedWebSession
{
    public function __construct(
        private readonly WebSessionDevices $webSessionDevices,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (is_platform('native')) {
            return $next($request);
        }

        $sessionId = $this->webSessionDevices->currentSessionId($request);

        if (
            $sessionId !== null &&
            $this->webSessionDevices->isRevoked($sessionId) &&
            Auth::check()
        ) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $response = $next($request);
        $user = Auth::user();

        if ($user instanceof User) {
            $this->webSessionDevices->touch($request, $user);
        } else {
            $this->webSessionDevices->remove($sessionId);
        }

        return $response;
    }
}
