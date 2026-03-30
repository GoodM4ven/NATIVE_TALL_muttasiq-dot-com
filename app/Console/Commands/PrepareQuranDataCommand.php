<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Native\NativeMigrationBootstrapper;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class PrepareQuranDataCommand extends Command
{
    protected $signature = 'app:prepare-quran-data';

    protected $description = 'Run the deferred Quran reader data migrations for the native reader.';

    /**
     * Execute the console command.
     */
    public function handle(
        NativeMigrationBootstrapper $bootstrapper,
        QuranReaderDataService $readerDataService,
    ): int {
        if ($readerDataService->isReady()) {
            $this->info('Quran data is already prepared.');

            return self::SUCCESS;
        }

        $status = $bootstrapper->runDeferredQuranMigrations();
        $output = trim(Artisan::output());
        $isReady = $readerDataService->isReady();

        if ($output !== '') {
            $this->line($output);
        }

        if (! $isReady) {
            $this->error('Quran data is still not ready after running the deferred migrations.');

            return self::FAILURE;
        }

        return $status === 0 ? self::SUCCESS : self::FAILURE;
    }
}
