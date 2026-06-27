<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\User;
use Illuminate\Contracts\Queue\ShouldBeUniqueUntilProcessing;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Pushes the device's mirrored settings bundle to the authoritative server.
 *
 * Offline-first: queued (database driver) so it flushes when connectivity
 * returns. Deduplicated via ShouldBeUniqueUntilProcessing keyed by the user —
 * the lock releases when the job starts, so rapid changes coalesce to at most
 * one queued + one running, and the handler reads the LATEST bundle at run time,
 * so whoever runs last writes the newest state.
 */
class SyncUserSettings implements ShouldBeUniqueUntilProcessing, ShouldQueue
{
    use Queueable;

    public int $tries = 8;

    public int $uniqueFor = 120;

    public function __construct(
        public int $userId,
        public ?string $socketId = null,
        public string $realtimeType = 'dataSynced',
    ) {}

    public function uniqueId(): string
    {
        return (string) $this->userId;
    }

    /**
     * @return array<int, int>
     */
    public function backoff(): array
    {
        // Grows so a long offline stretch keeps retrying without hammering.
        return [10, 30, 60, 120, 300];
    }

    public function handle(): void
    {
        $user = User::query()->find($this->userId);

        if (! $user instanceof User || blank($user->native_api_token)) {
            return;
        }

        $serverBase = native_server_base();

        if ($serverBase === null) {
            return;
        }

        $response = Http::asJson()->acceptJson()
            ->connectTimeout(5)->timeout(8)
            ->withToken((string) $user->native_api_token)
            ->withHeaders(array_filter([
                'X-Socket-ID' => $this->socketId,
            ]))
            ->post($serverBase.'/api/native-sync/settings', [
                // Read fresh at run time so the latest state always wins.
                'data' => $user->synced_data ?? [],
                'synced_at' => $user->synced_data_updated_at?->toISOString() ?? now()->toISOString(),
                'realtime_type' => $this->realtimeType,
            ]);

        if (! $response->successful() || $response->json('ok') !== true) {
            // Throw so the queue retries (covers transient offline / server errors).
            throw new RuntimeException('Native settings sync failed: '.$response->status());
        }
    }
}
