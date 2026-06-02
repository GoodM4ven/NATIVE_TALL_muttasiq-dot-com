<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Native\NativeMigrationBootstrapper;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class NativeBootstrapCommand extends Command
{
    protected $signature = 'app:native-bootstrap';

    protected $description = 'Run native bootstrap migrations while deferring heavy Quran data imports.';

    /**
     * Execute the console command.
     */
    public function handle(NativeMigrationBootstrapper $bootstrapper): int
    {
        $status = $bootstrapper->runBootstrapMigrations();
        $output = trim(Artisan::output());

        if ($output !== '') {
            $this->line($output);
        }

        return $status === 0 ? self::SUCCESS : self::FAILURE;
    }
}
