<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Models\Thikr;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

uses(TestCase::class);

function withUnavailableSqliteConnection(callable $callback): mixed
{
    $originalDatabase = config('database.connections.sqlite.database');

    config([
        'database.connections.sqlite.database' => database_path('missing/'.Str::uuid()->toString().'.sqlite'),
    ]);

    Cache::flush();
    DB::purge('sqlite');

    try {
        return $callback();
    } finally {
        config([
            'database.connections.sqlite.database' => $originalDatabase,
        ]);

        Cache::flush();
        DB::purge('sqlite');
    }
}

it('falls back to safe setting and athkar defaults when sqlite is unavailable', function (): void {
    withUnavailableSqliteConnection(function (): void {
        config([
            'app.custom.app_version' => '9.9.9',
        ]);

        expect(Setting::appVersion())->toBe('9.9.9');
        expect(Setting::youtubeVideoUrl())->toBe(Setting::DEFAULT_YOUTUBE_VIDEO_URL);
        expect(Setting::storedValues([Setting::APP_VERSION]))->toBe([]);

        Setting::setAppVersion('10.0.0');

        expect(Setting::appVersion())->toBe('9.9.9');
        expect(Thikr::cachedDefaults())->toBe([]);
    });
});

it('marks the quran reader as unavailable when schema checks cannot hit the database', function (): void {
    withUnavailableSqliteConnection(function (): void {
        expect(app(QuranReaderDataService::class)->isReady())->toBeFalse();
    });
});
