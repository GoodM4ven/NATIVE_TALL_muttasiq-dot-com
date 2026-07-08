<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NativeAuthClaimController
{
    /**
     * Claim a native Telegram login that completed in the OAuth browser, keyed by
     * a device-generated `state` the device registered before opening the browser.
     *
     * This is the recovery path for when the user returns to the app via the system
     * back button instead of tapping the "return to app" deeplink button: the device
     * polls here on resume and, if the login finished, gets the same one-time `code`
     * the deeplink would have carried. The `state` is a high-entropy secret known
     * only to that device, so it is the only credential required.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $state = trim((string) $request->input('state', ''));

        if (preg_match('/^[A-Za-z0-9]{32,128}$/', $state) !== 1) {
            return response()->json(['ready' => false], 422);
        }

        // Retryable for the short TTL: a resume claim can start local handoff and
        // still lose the WebView/navigation race. Keeping the state lets the next
        // focus or the manual deeplink button recover with the same code.
        $code = Cache::get('native-auth-claim:'.$state);

        if (! is_string($code) || $code === '') {
            return response()->json(['ready' => false]);
        }

        return response()->json([
            'ready' => true,
            'code' => $code,
        ]);
    }
}
