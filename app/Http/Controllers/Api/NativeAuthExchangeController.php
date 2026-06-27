<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
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

        if ($code === '') {
            return response()->json(['ok' => false], 422);
        }

        $userId = Cache::pull('native-auth-code:'.$code);

        if ($userId === null) {
            return response()->json(['ok' => false], 422);
        }

        $user = User::query()->find($userId);

        if (! $user instanceof User) {
            return response()->json(['ok' => false], 422);
        }

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
                // Sanctum Bearer token the device presents to push changes back.
                'sync_token' => $user->createToken('native-device')->plainTextToken,
            ],
        ]);
    }
}
