<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Events\UserRealtimeEvent;
use App\Models\User;
use App\Support\Auth\WebSessionDevices;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
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
        /** @var User|null $user */
        $user = $request->user();
        $currentTokenId = $this->currentTokenId($request);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8'],
        ]);

        $user->forceFill(['password' => Hash::make($validated['password'])])->save();
        $this->deleteOtherTokens($user, $currentTokenId);
        $this->broadcast($user, 'passwordChanged', socketId: normalize_socket_id($request->headers->get('X-Socket-ID')));

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
            socketId: normalize_socket_id($request->headers->get('X-Socket-ID')),
        );

        return response()->json(['ok' => true]);
    }

    /**
     * Return the authoritative settings bundle for the current account.
     */
    public function snapshot(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        return response()->json([
            'ok' => true,
            'synced_data' => $user->synced_data ?? [],
            'synced_data_updated_at' => $user->synced_data_updated_at?->toISOString(),
        ]);
    }

    public function devices(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $currentTokenId = $this->currentTokenId($request);
        $webSessionDevices = app(WebSessionDevices::class);

        $devices = $user->tokens()
            ->latest('id')
            ->get()
            ->reject(fn (PersonalAccessToken $token): bool => $currentTokenId !== null && $token->getKey() === $currentTokenId)
            ->values()
            ->map(fn (PersonalAccessToken $token): array => [
                'id' => $token->getKey(),
                'device_key' => 'token:'.$token->getKey(),
                'kind' => 'native-token',
                'name' => $token->name,
                'last_used_at' => $token->last_used_at?->toISOString(),
                'created_at' => $token->created_at?->toISOString(),
                'user_agent' => null,
            ])
            ->all();

        $devices = collect([
            ...$devices,
            ...$webSessionDevices->listForUser($user),
        ])->sortByDesc(static fn (array $device): string => (string) ($device['last_used_at'] ?? $device['created_at'] ?? ''))
            ->values()
            ->all();

        return response()->json([
            'ok' => true,
            'devices' => $devices,
        ]);
    }

    public function revokeDevice(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $webSessionDevices = app(WebSessionDevices::class);

        $validated = $request->validate([
            'token_id' => ['nullable', 'integer'],
            'session_id' => ['nullable', 'string'],
        ]);

        $sessionId = trim((string) ($validated['session_id'] ?? ''));
        $tokenId = isset($validated['token_id']) ? (int) $validated['token_id'] : 0;
        $currentTokenId = $this->currentTokenId($request);

        if ($sessionId === '' && $tokenId < 1) {
            return response()->json(['ok' => false], 422);
        }

        if ($currentTokenId === $tokenId && $tokenId > 0) {
            return response()->json(['ok' => false], 422);
        }

        if ($sessionId !== '') {
            $deleted = $webSessionDevices->revoke($sessionId);

            if (! $deleted) {
                return response()->json(['ok' => false], 404);
            }

            $this->broadcast(
                $user,
                'deviceLoggedOut',
                socketId: normalize_socket_id($request->headers->get('X-Socket-ID')),
                targetSessionId: $sessionId,
            );

            return response()->json(['ok' => true]);
        }

        $deleted = $user->tokens()->whereKey($tokenId)->delete();

        if ($deleted < 1) {
            return response()->json(['ok' => false], 404);
        }

        $this->broadcast($user, 'deviceLoggedOut', $tokenId, normalize_socket_id($request->headers->get('X-Socket-ID')));

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
            $this->broadcastUserRealtimeEvent(new UserRealtimeEvent($telegramId, 'accountDeleted'));
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

        $this->broadcast($user, 'deviceLoggedOut', $tokenId, normalize_socket_id($request->headers->get('X-Socket-ID')));

        return response()->json(['ok' => true]);
    }

    /**
     * Pull the authoritative settings bundle down into the native device mirror.
     */
    public function pull(Request $request): JsonResponse
    {
        abort_unless(is_platform('native'), 404);

        /** @var User|null $user */
        $user = $request->user();
        $serverBase = native_server_base();

        if (! $user instanceof User || blank($user->native_api_token) || $serverBase === null) {
            return response()->json(['ok' => false], 422);
        }

        try {
            $response = Http::asJson()->acceptJson()
                ->connectTimeout(3)->timeout(6)
                ->withToken((string) $user->native_api_token)
                ->get($serverBase.'/api/native-sync/snapshot');
        } catch (\Throwable) {
            return response()->json(['ok' => false], 502);
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            return response()->json(['ok' => false], 502);
        }

        $syncedData = $response->json('synced_data');
        $syncedData = is_array($syncedData) ? array_filter($syncedData, static fn ($value): bool => is_string($value)) : [];
        $syncedDataUpdatedAt = $response->json('synced_data_updated_at');

        $user->forceFill([
            'synced_data' => $syncedData,
            'synced_data_updated_at' => is_string($syncedDataUpdatedAt) && $syncedDataUpdatedAt !== ''
                ? CarbonImmutable::parse($syncedDataUpdatedAt)
                : now()->toImmutable(),
        ])->save();

        return response()->json([
            'ok' => true,
            'synced_data' => $syncedData,
            'synced_data_updated_at' => $user->fresh()?->synced_data_updated_at?->toISOString(),
        ]);
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

    private function broadcast(
        User $user,
        string $type,
        ?int $targetTokenId = null,
        ?string $socketId = null,
        ?string $targetSessionId = null,
    ): void {
        if ($user->telegram_id === null) {
            return;
        }

        $this->broadcastUserRealtimeEvent(
            new UserRealtimeEvent(
                (int) $user->telegram_id,
                $type,
                $targetTokenId,
                $socketId,
                $targetSessionId,
            ),
        );
    }

    private function broadcastUserRealtimeEvent(UserRealtimeEvent $event): void
    {
        try {
            broadcast($event);
        } catch (\Throwable) {
            // ponytail: realtime is best-effort; API auth and sync should keep working if Reverb is down.
        }
    }
}
