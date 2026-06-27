<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class NativeSyncController
{
    /**
     * Push a password change from the native runtime to the authoritative server
     * account (so web + other devices use the new credential). Authenticated by
     * the device's Sanctum Bearer token via the `auth:sanctum` guard.
     */
    public function password(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Push the user's synced settings bundle to the authoritative server account.
     */
    public function settings(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'data' => ['present', 'array'],
        ]);

        $bundle = array_filter($validated['data'], static fn ($value): bool => is_string($value));

        $user->forceFill(['synced_data' => $bundle])->save();

        return response()->json(['ok' => true]);
    }

    /**
     * Delete the authoritative server account (and its tokens) when the user
     * deletes their account from a native device.
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $user->tokens()->delete();
        $user->delete();

        return response()->json(['ok' => true]);
    }

    /**
     * Revoke just the device's own token on native logout, leaving the server
     * account (and other devices' tokens) intact.
     */
    public function revoke(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['ok' => true]);
    }
}
