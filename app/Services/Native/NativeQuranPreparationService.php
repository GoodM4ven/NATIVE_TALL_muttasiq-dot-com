<?php

declare(strict_types=1);

namespace App\Services\Native;

use App\Jobs\DownloadNativeQuranSnapshot;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Support\Facades\Cache;
use Throwable;

class NativeQuranPreparationService
{
    private const STATUS_CACHE_KEY = 'native-quran-preparation-status-v1';

    private const PENDING_TTL_SECONDS = 300;

    private const PENDING_STALE_AFTER_SECONDS = 240;

    private const READY_TTL_SECONDS = 86400;

    private const FAILURE_TTL_SECONDS = 900;

    /**
     * @return array{
     *     ready: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null,
     *     updatedAt: int
     * }
     */
    public function queueIfNeeded(QuranReaderDataService $readerDataService): array
    {
        $status = $this->currentStatus($readerDataService);

        if ($status['ready'] || in_array($status['state'], ['queued', 'running'], true)) {
            return $status;
        }

        $queuedStatus = $this->storeStatus([
            'ready' => false,
            'state' => 'queued',
            'message' => arabic_text('يجري تجهيز بيانات القرآن. يمكنك المتابعة وسيفتح القارئ فور اكتمال التنزيل.'),
            'progressPercent' => 0,
            'downloadedBytes' => 0,
            'totalBytes' => null,
            'updatedAt' => now()->getTimestamp(),
        ], self::PENDING_TTL_SECONDS);

        try {
            DownloadNativeQuranSnapshot::dispatch();
        } catch (Throwable $throwable) {
            return $this->markFailed($throwable);
        }

        return $queuedStatus;
    }

    /**
     * @return array{
     *     ready: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null,
     *     updatedAt: int
     * }
     */
    public function currentStatus(QuranReaderDataService $readerDataService): array
    {
        $readerDataService->forgetReadinessCaches();

        if ($readerDataService->isReady()) {
            return $this->markReady();
        }

        $status = $this->normalizeStatus(Cache::get(self::STATUS_CACHE_KEY));

        if ($status === null) {
            return $this->idleStatus();
        }

        if (
            in_array($status['state'], ['queued', 'running'], true)
            && (now()->getTimestamp() - $status['updatedAt']) >= self::PENDING_STALE_AFTER_SECONDS
        ) {
            $this->forgetStatus();

            return $this->idleStatus();
        }

        return $status;
    }

    public function markRunning(
        ?int $progressPercent = null,
        ?int $downloadedBytes = null,
        ?int $totalBytes = null,
        ?string $message = null,
    ): void {
        $this->storeStatus([
            'ready' => false,
            'state' => 'running',
            'message' => $message ?? arabic_text('يجري تنزيل بيانات القرآن الآن. انتظر قليلًا...'),
            'progressPercent' => $this->normalizeProgressPercent($progressPercent),
            'downloadedBytes' => $this->normalizeByteCount($downloadedBytes),
            'totalBytes' => $this->normalizeByteCount($totalBytes),
            'updatedAt' => now()->getTimestamp(),
        ], self::PENDING_TTL_SECONDS);
    }

    public function markDownloadProgress(
        int $downloadedBytes,
        ?int $totalBytes = null,
        ?int $progressPercent = null,
    ): void {
        $status = $this->normalizeStatus(Cache::get(self::STATUS_CACHE_KEY));

        if ($status === null || ! in_array($status['state'], ['queued', 'running'], true)) {
            $status = [
                ...$this->idleStatus(),
                'state' => 'running',
            ];
        }

        $normalizedTotalBytes = $this->normalizeByteCount($totalBytes);
        $normalizedDownloadedBytes = max(0, $downloadedBytes);
        $resolvedProgressPercent = $progressPercent;

        if ($resolvedProgressPercent === null && $normalizedTotalBytes !== null && $normalizedTotalBytes > 0) {
            $resolvedProgressPercent = (int) floor(($normalizedDownloadedBytes / $normalizedTotalBytes) * 100);
        }

        $this->storeStatus([
            ...$status,
            'ready' => false,
            'state' => 'running',
            'message' => arabic_text('يجري تنزيل بيانات القرآن الآن. انتظر قليلًا...'),
            'progressPercent' => $this->normalizeProgressPercent($resolvedProgressPercent),
            'downloadedBytes' => $normalizedDownloadedBytes,
            'totalBytes' => $normalizedTotalBytes,
            'updatedAt' => now()->getTimestamp(),
        ], self::PENDING_TTL_SECONDS);
    }

    /**
     * @return array{
     *     ready: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null,
     *     updatedAt: int
     * }
     */
    public function markReady(): array
    {
        return $this->storeStatus([
            'ready' => true,
            'state' => 'ready',
            'message' => null,
            'progressPercent' => 100,
            'downloadedBytes' => null,
            'totalBytes' => null,
            'updatedAt' => now()->getTimestamp(),
        ], self::READY_TTL_SECONDS);
    }

    /**
     * @return array{
     *     ready: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null,
     *     updatedAt: int
     * }
     */
    public function markFailed(Throwable|string|null $error = null): array
    {
        report($error);

        return $this->storeStatus([
            'ready' => false,
            'state' => 'failed',
            'message' => arabic_text('تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.'),
            'progressPercent' => null,
            'downloadedBytes' => null,
            'totalBytes' => null,
            'updatedAt' => now()->getTimestamp(),
        ], self::FAILURE_TTL_SECONDS);
    }

    public function forgetStatus(): void
    {
        Cache::forget(self::STATUS_CACHE_KEY);
        Cache::memo()->forget(self::STATUS_CACHE_KEY);
    }

    /**
     * @return array{
     *     ready: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null,
     *     updatedAt: int
     * }|null
     */
    private function normalizeStatus(mixed $status): ?array
    {
        if (! is_array($status)) {
            return null;
        }

        $state = (string) ($status['state'] ?? '');

        if (! in_array($state, ['idle', 'queued', 'running', 'ready', 'failed'], true)) {
            return null;
        }

        return [
            'ready' => (bool) ($status['ready'] ?? false),
            'state' => $state,
            'message' => isset($status['message']) ? (string) $status['message'] : null,
            'progressPercent' => $this->normalizeProgressPercent(
                isset($status['progressPercent']) ? (int) $status['progressPercent'] : null,
            ),
            'downloadedBytes' => $this->normalizeByteCount(
                isset($status['downloadedBytes']) ? (int) $status['downloadedBytes'] : null,
            ),
            'totalBytes' => $this->normalizeByteCount(
                isset($status['totalBytes']) ? (int) $status['totalBytes'] : null,
            ),
            'updatedAt' => max(0, (int) ($status['updatedAt'] ?? 0)),
        ];
    }

    /**
     * @param  array{
     *     ready: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null,
     *     updatedAt: int
     * }  $status
     * @return array{
     *     ready: bool,
     *     state: 'idle'|'queued'|'running'|'ready'|'failed',
     *     message: string|null,
     *     progressPercent: int|null,
     *     downloadedBytes: int|null,
     *     totalBytes: int|null,
     *     updatedAt: int
     * }
     */
    private function storeStatus(array $status, int $ttlSeconds): array
    {
        $expiration = now()->addSeconds($ttlSeconds);

        Cache::put(self::STATUS_CACHE_KEY, $status, $expiration);
        Cache::memo()->put(self::STATUS_CACHE_KEY, $status, $expiration);

        return $status;
    }

    /**
     * @return array{
     *     ready: false,
     *     state: 'idle',
     *     message: null,
     *     progressPercent: null,
     *     downloadedBytes: null,
     *     totalBytes: null,
     *     updatedAt: int
     * }
     */
    private function idleStatus(): array
    {
        return [
            'ready' => false,
            'state' => 'idle',
            'message' => null,
            'progressPercent' => null,
            'downloadedBytes' => null,
            'totalBytes' => null,
            'updatedAt' => now()->getTimestamp(),
        ];
    }

    private function normalizeProgressPercent(?int $progressPercent): ?int
    {
        if ($progressPercent === null) {
            return null;
        }

        return max(0, min(100, $progressPercent));
    }

    private function normalizeByteCount(?int $bytes): ?int
    {
        if ($bytes === null) {
            return null;
        }

        return max(0, $bytes);
    }
}
