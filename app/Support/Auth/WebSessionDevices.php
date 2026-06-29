<?php

declare(strict_types=1);

namespace App\Support\Auth;

use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class WebSessionDevices
{
    public const DEVICE_KIND = 'web-session';

    public function currentSessionId(Request $request): ?string
    {
        $sessionId = trim((string) $request->session()->getId());

        return $sessionId !== '' ? $sessionId : null;
    }

    public function composeDeviceKey(string $sessionId): string
    {
        return self::DEVICE_KIND.':'.$sessionId;
    }

    public function parseDeviceKey(string $deviceKey): ?string
    {
        $normalizedDeviceKey = trim($deviceKey);
        $prefix = self::DEVICE_KIND.':';

        if (! str_starts_with($normalizedDeviceKey, $prefix)) {
            return null;
        }

        $sessionId = trim(substr($normalizedDeviceKey, strlen($prefix)));

        return $sessionId !== '' ? $sessionId : null;
    }

    public function touch(Request $request, User $user): void
    {
        $sessionId = $this->currentSessionId($request);

        if ($sessionId === null) {
            return;
        }

        $this->clearRevocation($sessionId);

        DB::table('sessions')->updateOrInsert(
            ['id' => $sessionId],
            [
                'user_id' => $user->getKey(),
                'ip_address' => $request->ip(),
                'user_agent' => trim((string) $request->userAgent()),
                'payload' => '',
                'last_activity' => now()->getTimestamp(),
            ],
        );
    }

    public function remove(?string $sessionId): void
    {
        if ($sessionId === null || $sessionId === '') {
            return;
        }

        DB::table('sessions')->where('id', $sessionId)->delete();
        $this->clearRevocation($sessionId);
    }

    public function revoke(string $sessionId): bool
    {
        $deleted = DB::table('sessions')->where('id', $sessionId)->delete();

        Cache::put(
            $this->revocationCacheKey($sessionId),
            true,
            now()->addMinutes(max(1, (int) config('session.lifetime', 120))),
        );

        return $deleted > 0;
    }

    public function isRevoked(string $sessionId): bool
    {
        return Cache::get($this->revocationCacheKey($sessionId), false) === true;
    }

    /**
     * @return array<int, array{
     *     id: string,
     *     device_key: string,
     *     kind: string,
     *     name: string,
     *     last_used_at: string|null,
     *     created_at: string|null,
     *     user_agent: string|null
     * }>
     */
    public function listForUser(User $user, ?string $exceptSessionId = null): array
    {
        return DB::table('sessions')
            ->where('user_id', $user->getKey())
            ->when(
                $exceptSessionId !== null && $exceptSessionId !== '',
                fn ($query) => $query->where('id', '!=', $exceptSessionId),
            )
            ->orderByDesc('last_activity')
            ->get(['id', 'last_activity', 'user_agent'])
            ->map(function (object $session): array {
                $sessionId = trim((string) ($session->id ?? ''));
                $lastActivity = max(0, (int) ($session->last_activity ?? 0));

                return [
                    'id' => $sessionId,
                    'device_key' => $this->composeDeviceKey($sessionId),
                    'kind' => self::DEVICE_KIND,
                    'name' => arabic_text('متصفح الويب'),
                    'last_used_at' => $lastActivity > 0
                        ? CarbonImmutable::createFromTimestamp($lastActivity)->toISOString()
                        : null,
                    'created_at' => null,
                    'user_agent' => ($userAgent = trim((string) ($session->user_agent ?? ''))) !== ''
                        ? $userAgent
                        : null,
                ];
            })
            ->all();
    }

    private function clearRevocation(string $sessionId): void
    {
        Cache::forget($this->revocationCacheKey($sessionId));
    }

    private function revocationCacheKey(string $sessionId): string
    {
        return 'web-session-revoked:'.$sessionId;
    }
}
