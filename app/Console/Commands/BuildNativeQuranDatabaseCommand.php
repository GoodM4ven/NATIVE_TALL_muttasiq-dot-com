<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Native\NativeQuranDatabaseSnapshotBuilder;
use Illuminate\Console\Command;

class BuildNativeQuranDatabaseCommand extends Command
{
    protected $signature = 'app:build-native-quran-database
        {--force : Rebuild the bundled native Quran snapshot even if the signature matches.}';

    protected $description = 'Build the bundled native SQLite snapshot that preloads the Quran reader database.';

    public function handle(NativeQuranDatabaseSnapshotBuilder $snapshotBuilder): int
    {
        $result = $snapshotBuilder->build((bool) $this->option('force'));

        if ($result['built']) {
            $this->info('Built bundled native Quran database snapshot: '.$result['path']);

            return self::SUCCESS;
        }

        $this->info('Bundled native Quran database snapshot is already up to date: '.$result['path']);

        return self::SUCCESS;
    }
}
