<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Support\Auth\WebSessionDevices;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
            $this->forceLogout($request);
        }

        // Front-end auth invariant: a rendered front-end page/asset may only show an
        // authenticated user if a real front-end login confirmed it (callback() +
        // AuthButton both set this flag alongside Auth::login). Anything else that
        // seeds `login_web` — a remember-me recaller, a native handoff/restore login
        // bleeding through the shared-DB/Redis dev host, a stale morphed session — is
        // rejected before that GET is ever rendered as the leaked account. Enforced
        // here (every web request) instead of only on the home route, which let asset
        // and other GETs slip a leaked session through. Scoped to plain front-end GET
        // renders: the Filament admin panel + the shared Livewire endpoint (login
        // POSTs, panel interactions) authenticate without this flag, so both are left
        // alone — Auth::logout() is global to the web guard, so gating them would
        // sign admins out of their own panel.
        if (
            $request->isMethod('GET') &&
            Auth::check() &&
            $request->session()->get('auth.web_login_confirmed') !== true &&
            ! $this->shouldSkipConfirmationGate($request)
        ) {
            Log::warning('Rejected an unconfirmed web session (no explicit front-end login).', [
                'session_id' => $sessionId,
                'user_id' => (int) Auth::id(),
                'via_remember' => Auth::viaRemember(),
                'method' => $request->method(),
                'path' => $request->path(),
            ]);

            $this->forceLogout($request);
        }

        $response = $next($request);
        $user = Auth::user();

        if ($user instanceof User) {
            // Canary for the reported cross-account leak: a web session's user must
            // never silently change to a different account. If it does, this records
            // exactly which request did it (path/method) so the switch can be traced.
            $previousUserId = $request->session()->get('__web_session_user_id');

            if ($previousUserId !== null && (int) $previousUserId !== (int) $user->getKey()) {
                Log::warning('Web session user switched without an explicit login.', [
                    'session_id' => $sessionId,
                    'from_user_id' => (int) $previousUserId,
                    'to_user_id' => (int) $user->getKey(),
                    'to_telegram_id' => $user->telegram_id,
                    'method' => $request->method(),
                    'path' => $request->path(),
                ]);
            }

            $request->session()->put('__web_session_user_id', $user->getKey());
            $this->webSessionDevices->touch($request, $user);
        } else {
            $request->session()->forget('__web_session_user_id');
            $this->webSessionDevices->remove($sessionId);
        }

        return $response;
    }

    private function forceLogout(Request $request): void
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    private function shouldSkipConfirmationGate(Request $request): bool
    {
        if ($request->routeIs('livewire.*')) {
            return true;
        }

        $adminPath = trim((string) config('app.custom.admin_path', 'admin'), '/');

        return $adminPath !== '' && $request->is($adminPath, $adminPath.'/*');
    }
}
