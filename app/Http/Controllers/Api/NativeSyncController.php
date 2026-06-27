<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\UserRealtimeEvent;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Laravel\Fortify\Contracts\TwoFactorAuthenticationProvider;
use Laravel\Sanctum\PersonalAccessToken;

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
        $currentTokenId = $this->currentTokenId($request);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $this->deleteOtherTokens($user, $currentTokenId);
        $this->broadcast($user, 'passwordChanged', socketId: $request->headers->get('X-Socket-ID'));

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
            'synced_at' => ['nullable', 'date'],
            'realtime_type' => ['nullable', 'string', 'in:dataSynced,dataOverridden'],
        ]);

        $incomingSyncedAt = isset($validated['synced_at'])
            ? CarbonImmutable::parse((string) $validated['synced_at'])
            : now()->toImmutable();

        if (
            $user->synced_data_updated_at !== null
            && $incomingSyncedAt->lessThanOrEqualTo($user->synced_data_updated_at->toImmutable())
        ) {
            return response()->json(['ok' => true, 'stale' => true]);
        }

        $bundle = array_filter($validated['data'], static fn ($value): bool => is_string($value));

        $user->forceFill([
            'synced_data' => $bundle,
            'synced_data_updated_at' => $incomingSyncedAt,
        ])->save();

        $this->broadcast(
            $user,
            (string) ($validated['realtime_type'] ?? 'dataSynced'),
            socketId: $request->headers->get('X-Socket-ID'),
        );

        return response()->json(['ok' => true]);
    }

    public function devices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $currentTokenId = $this->currentTokenId($request);

        return response()->json([
            'ok' => true,
            'devices' => $user->tokens()
                ->latest('id')
                ->get()
                ->reject(fn (PersonalAccessToken $token): bool => $currentTokenId !== null && $token->getKey() === $currentTokenId)
                ->values()
                ->map(fn (PersonalAccessToken $token): array => [
                    'id' => $token->getKey(),
                    'name' => $token->name,
                    'last_used_at' => $token->last_used_at?->toISOString(),
                    'created_at' => $token->created_at?->toISOString(),
                ]),
        ]);
    }

    public function revokeDevice(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'token_id' => ['required', 'integer'],
        ]);

        $tokenId = (int) $validated['token_id'];
        $currentTokenId = $this->currentTokenId($request);

        if ($currentTokenId === $tokenId) {
            return response()->json(['ok' => false], 422);
        }

        $deleted = $user->tokens()->whereKey($tokenId)->delete();

        if ($deleted < 1) {
            return response()->json(['ok' => false], 404);
        }

        $this->broadcast($user, 'deviceLoggedOut', $tokenId, $request->headers->get('X-Socket-ID'));

        return response()->json(['ok' => true]);
    }

    public function twoFactor(Request $request, TwoFactorAuthenticationProvider $provider): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $validated = $request->validate([
            'code' => ['required', 'string'],
        ]);

        if ($user->two_factor_confirmed_at === null) {
            return response()->json(['ok' => true]);
        }

        if ($user->two_factor_secret === null) {
            return response()->json(['ok' => false], 422);
        }

        $isValid = false;

        try {
            $isValid = $provider->verify(decrypt($user->two_factor_secret), (string) $validated['code']);
        } catch (\Throwable) {
            $isValid = false;
        }

        return $isValid
            ? response()->json(['ok' => true])
            : response()->json(['ok' => false], 422);
    }

    /**
     * Delete the authoritative server account (and its tokens) when the user
     * deletes their account from a native device.
     */
    public function destroy(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $telegramId = $user->telegram_id !== null ? (int) $user->telegram_id : null;

        $user->tokens()->delete();
        $user->delete();

        if ($telegramId !== null) {
            broadcast(new UserRealtimeEvent($telegramId, 'accountDeleted'));
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Revoke just the device's own token on native logout, leaving the server
     * account (and other devices' tokens) intact.
     */
    public function revoke(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $tokenId = $this->currentTokenId($request);

        $request->user()?->currentAccessToken()?->delete();

        $this->broadcast($user, 'deviceLoggedOut', $tokenId, $request->headers->get('X-Socket-ID'));

        return response()->json(['ok' => true]);
    }

    private function currentTokenId(Request $request): ?int
    {
        $token = $request->user()?->currentAccessToken();

        return $token instanceof PersonalAccessToken ? (int) $token->getKey() : null;
    }

    private function deleteOtherTokens(User $user, ?int $currentTokenId): void
    {
        $query = $user->tokens();

        if ($currentTokenId !== null) {
            $query->where($query->getModel()->getKeyName(), '!=', $currentTokenId);
        }

        $query->delete();
    }

    private function broadcast(User $user, string $type, ?int $targetTokenId = null, ?string $socketId = null): void
    {
        if ($user->telegram_id === null) {
            return;
        }

        broadcast(new UserRealtimeEvent((int) $user->telegram_id, $type, $targetTokenId, $socketId));
    }
}
