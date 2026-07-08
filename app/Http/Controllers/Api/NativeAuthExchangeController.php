<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Support\Auth\WebSessionDevices;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class NativeAuthExchangeController
{
    /**
     * Exchange a one-time native-auth code (issued by the Telegram callback and
     * delivered to the device via the deeplink) for the account payload, so the
     * device can mirror the user into its own local SQLite and log in. The code
     * is the only credential here: random, single-use, and short-lived.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $code = trim((string) $request->input('code', ''));
        $deviceName = trim((string) $request->input('device_name', ''));

        if ($code === '') {
            return response()->json(['ok' => false], 422);
        }

        // Read (not pull): on return the deeplink handoff AND the resume claim-poll
        // can both exchange the same code in a race. Single-use meant the loser got a
        // null exchange and fell back to a plain page reload with no auth. Reading
        // lets both succeed within the code's short TTL (the first restart wins); the
        // code still expires on its own and is only ever delivered to this device.
        $userId = Cache::get('native-auth-code:'.$code);

        if ($userId === null) {
            return response()->json(['ok' => false], 422);
        }

        $user = User::query()->find($userId);

        if (! $user instanceof User) {
            return response()->json(['ok' => false], 422);
        }

        app(WebSessionDevices::class)->revokeAllForUser($user);

        return response()->json([
            'ok' => true,
            'user' => [
                'telegram_id' => $user->telegram_id,
                'telegram_username' => $user->telegram_username,
                'name' => $user->name,
                'username' => $user->username,
                // Already a bcrypt hash; the device stores it verbatim so the
                // texted username/password keeps working there too.
                'password' => $user->password,
                // Server-authoritative state the device mirrors on login.
                'synced_data' => $user->synced_data,
                'synced_data_updated_at' => $user->synced_data_updated_at?->toISOString(),
                'two_factor_confirmed_at' => $user->two_factor_confirmed_at?->toISOString(),
                // Sanctum Bearer token the device presents to push changes back.
                'sync_token' => $user->createToken($deviceName !== '' ? $deviceName : 'Native device')->plainTextToken,
            ],
        ]);
    }
}
