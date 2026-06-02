<?php

declare(strict_types=1);

use App\Jobs\DownloadNativeQuranSnapshot;
use App\Livewire\QuranApp\Reader;
use App\Services\Native\NativeQuranPreparationService;
use App\Services\Quran\QuranReaderDataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;

afterEach(function (): void {
    Cache::flush();
});

function fakeQuranReaderDataService(bool $ready, ?array $payload = null): QuranReaderDataService
{
    return new class($ready, $payload) extends QuranReaderDataService
    {
        private int $isReadyCallCount = 0;

        public function __construct(
            private bool $readyState,
            private ?array $resolvedPayload,
        ) {}

        public function isReady(): bool
        {
            $this->isReadyCallCount++;

            return $this->readyState;
        }

        public function forgetReadinessCaches(): void {}

        public function resolvePage(int $pageNumber, int $activeAyahIndex = 0): array
        {
            return $this->resolvedPayload ?? [
                'ready' => true,
                'pageNumber' => $pageNumber,
                'maxPage' => 604,
                'activeAyahIndex' => $activeAyahIndex,
                'mushafLines' => [],
                'qpcPageFontFamily' => null,
                'qpcPageFontUrl' => null,
                'qpcPageFontFormat' => null,
                'basmallahFontFamily' => null,
                'basmallahFontUrl' => null,
                'basmallahFontFormat' => null,
                'basmallahText' => null,
                'surahHeaderFontFamily' => null,
                'surahHeaderFontUrl' => null,
                'surahHeaderFontFormat' => null,
                'useCenteredAyahLayout' => true,
            ];
        }
    };
}

function fakeNativeQuranPreparationService(
    ?array $queuedStatus = null,
    ?array $currentStatus = null,
): NativeQuranPreparationService {
    return new class($queuedStatus, $currentStatus) extends NativeQuranPreparationService
    {
        public function __construct(
            private ?array $queuedStatus,
            private ?array $currentStatus,
        ) {}

        public function queueIfNeeded(QuranReaderDataService $readerDataService): array
        {
            return $this->queuedStatus ?? parent::queueIfNeeded($readerDataService);
        }

        public function currentStatus(QuranReaderDataService $readerDataService): array
        {
            return $this->currentStatus ?? parent::currentStatus($readerDataService);
        }
    };
}

it('queues native quran preparation when reader data is not ready', function () {
    Queue::fake();

    $status = app(NativeQuranPreparationService::class)->queueIfNeeded(
        fakeQuranReaderDataService(false),
    );

    expect($status)->toMatchArray([
        'ready' => false,
        'state' => 'queued',
    ]);

    Queue::assertPushed(DownloadNativeQuranSnapshot::class);
});

it('does not enqueue native quran preparation again while it is already running', function () {
    Queue::fake();

    $service = app(NativeQuranPreparationService::class);
    $service->markRunning();

    $status = $service->queueIfNeeded(fakeQuranReaderDataService(false));

    expect($status)->toMatchArray([
        'ready' => false,
        'state' => 'running',
    ]);

    Queue::assertNothingPushed();
});

it('reports native quran preparation as ready without queueing when reader data exists', function () {
    Queue::fake();

    $status = app(NativeQuranPreparationService::class)->queueIfNeeded(
        fakeQuranReaderDataService(true),
    );

    expect($status)->toMatchArray([
        'ready' => true,
        'state' => 'ready',
    ]);

    Queue::assertNothingPushed();
});

it('reader returns queued native quran preparation status without blocking', function () {
    $reader = app(Reader::class);

    $result = $reader->prepareQuranData(
        fakeNativeQuranPreparationService([
            'ready' => false,
            'state' => 'queued',
            'message' => 'queued',
            'progressPercent' => 37,
            'downloadedBytes' => 370,
            'totalBytes' => 1000,
            'updatedAt' => now()->getTimestamp(),
        ]),
        fakeQuranReaderDataService(false),
    );

    expect($result)->toMatchArray([
        'ready' => false,
        'prepared' => false,
        'state' => 'queued',
        'payload' => null,
        'message' => 'queued',
        'progressPercent' => 37,
        'downloadedBytes' => 370,
        'totalBytes' => 1000,
    ]);
});

it('reader returns quran payload once native preparation status is ready', function () {
    $reader = app(Reader::class);
    $reader->pageNumber = 12;
    $reader->activeAyahIndex = 34;

    $payload = [
        'ready' => true,
        'pageNumber' => 12,
        'maxPage' => 604,
        'activeAyahIndex' => 34,
        'mushafLines' => [],
        'qpcPageFontFamily' => null,
        'qpcPageFontUrl' => null,
        'qpcPageFontFormat' => null,
        'basmallahFontFamily' => null,
        'basmallahFontUrl' => null,
        'basmallahFontFormat' => null,
        'basmallahText' => null,
        'surahHeaderFontFamily' => null,
        'surahHeaderFontUrl' => null,
        'surahHeaderFontFormat' => null,
        'useCenteredAyahLayout' => true,
    ];

    $result = $reader->quranPreparationStatus(
        fakeNativeQuranPreparationService(
            currentStatus: [
                'ready' => true,
                'state' => 'ready',
                'message' => null,
                'progressPercent' => 100,
                'downloadedBytes' => null,
                'totalBytes' => null,
                'updatedAt' => now()->getTimestamp(),
            ],
        ),
        fakeQuranReaderDataService(false, $payload),
    );

    expect($result)->toMatchArray([
        'ready' => true,
        'prepared' => false,
        'state' => 'ready',
        'payload' => $payload,
        'message' => null,
        'progressPercent' => 100,
        'downloadedBytes' => null,
        'totalBytes' => null,
    ]);
});

it('shows native quran bootstrap progress through home and reader events', function () {
    $homeView = file_get_contents(resource_path('views/home.blade.php'));
    $readerView = file_get_contents(resource_path('views/livewire/quran-app/reader.blade.php'));
    $readerScript = file_get_contents(
        resource_path('js/support/alpine/data/quran-app-reader/lifecycle-bootstrap-environment-and-cache.js'),
    );

    expect($homeView)->toContain('quran-bootstrap-progress');
    expect($homeView)->toContain('quranBootstrap.progressPercent');
    expect($homeView)->toContain('quranBootstrap.statusMessage');
    expect($readerView)->not()->toContain('quran-background-prepare-request');
    expect($readerScript)->toContain('emitNativeQuranPreparationProgress');
    expect($readerScript)->toContain('quran-bootstrap-progress');
});

it('keeps native quran bootstrap modal in restart-required mode after successful download flow', function () {
    $homeView = file_get_contents(resource_path('views/home.blade.php'));

    expect($homeView)->toContain('didStartDownloadFlow')
        ->and($homeView)->toContain('requiresRestart')
        ->and($homeView)->toContain('restartNativeAppAfterQuranBootstrap')
        ->and($homeView)->toContain('window.AndroidBridge.restartApplication()')
        ->and($homeView)->toContain('handleQuranBootstrapOverlayClick()')
        ->and($homeView)->toContain('x-show="quranBootstrap.requiresRestart"')
        ->and($homeView)->toContain('x-bind:disabled="quranBootstrap.isRestarting"');
});
