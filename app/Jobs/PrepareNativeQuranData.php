<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\Native\NativeMigrationBootstrapper;
use App\Services\Native\NativeQuranPreparationService;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use RuntimeException;
use Throwable;

class PrepareNativeQuranData implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;

    public int $tries = 1;

    public int $timeout = 180;

    public bool $failOnTimeout = true;

    public function handle(
        NativeMigrationBootstrapper $bootstrapper,
        NativeQuranPreparationService $preparationService,
        QuranReaderDataService $readerDataService,
    ): void {
        $preparationService->markRunning();

        try {
            $readerDataService->forgetReadinessCaches();

            if ($readerDataService->isReady()) {
                $preparationService->markReady();

                return;
            }

            $status = $bootstrapper->runDeferredQuranMigrations();
            $readerDataService->forgetReadinessCaches();

            if ($status !== 0 || ! $readerDataService->isReady()) {
                throw new RuntimeException('Quran data is still not ready after the native background preparation job.');
            }

            $preparationService->markReady();
        } catch (Throwable $throwable) {
            $preparationService->markFailed($throwable);

            throw $throwable;
        }
    }

    public function failed(?Throwable $exception): void
    {
        app(NativeQuranPreparationService::class)->markFailed($exception);
    }
}
