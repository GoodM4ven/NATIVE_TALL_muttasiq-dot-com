<?php

declare(strict_types=1);

use App\Services\Quran\QuranReaderDataService;
use Illuminate\Support\Facades\Schema;

test('quran search endpoint returns the rewritten ayah and surah stage names', function () {
    if (! Schema::hasTable('quran_verses')) {
        $this->markTestSkipped('Quran verses table is unavailable.');
    }

    /** @var QuranReaderDataService $service */
    $service = app(QuranReaderDataService::class);

    if (! $service->isReady()) {
        $this->markTestSkipped('Quran reader search dependencies are unavailable.');
    }

    $items = $service->search('آل عمران', 20);
    $strategies = collect($items)->pluck('match_strategy')->filter()->values()->all();

    expect($strategies)->not->toBeEmpty()
        ->and(collect($strategies)->every(static fn (string $strategy): bool => in_array($strategy, [
            'surah_exact',
            'surah_close',
            'surah_sarf',
            'ayah_exact',
            'ayah_close',
            'ayah_sarf',
            'ayah_jathr',
        ], true)))->toBeTrue();
});
