<?php

declare(strict_types=1);

use App\Livewire\QuranApp\BookmarksManagerTable;
use App\Livewire\QuranApp\HistoryManagerTable;
use Filament\Actions\Testing\TestAction;
use Filament\Support\ArrayRecord;

use function Pest\Livewire\livewire;

it('handles bookmarks manager remove action for custom table records', function () {
    $record = [
        'id' => 'bookmark-1',
        'sort_order' => 1,
        'page_number' => 2,
        'tags' => [],
        'note' => '',
    ];

    livewire(BookmarksManagerTable::class)
        ->call('syncFromClient', records: [$record])
        ->callAction(TestAction::make('remove')->table($record))
        ->assertDispatched('quran-bookmarks-manager-removed');
});

it('resolves bookmarks manager action record from scalar record key', function () {
    $record = [
        'id' => 'bookmark-2',
        'sort_order' => 1,
        'page_number' => 3,
        'tags' => [],
        'note' => '',
    ];

    $component = livewire(BookmarksManagerTable::class)
        ->call('syncFromClient', records: [$record])
        ->instance();

    $resolveTableRecordMethod = new ReflectionMethod($component, 'resolveTableRecord');
    $resolveTableRecordMethod->setAccessible(true);
    $resolvedRecord = $resolveTableRecordMethod->invoke($component, 'bookmark-2');

    expect($resolvedRecord)->toBeArray()
        ->and((string) ($resolvedRecord['id'] ?? ''))->toBe('bookmark-2');
});

it('resolves bookmarks manager action record from filament array key', function () {
    $record = [
        'id' => 'bookmark-3',
        'sort_order' => 1,
        'page_number' => 7,
        'tags' => [],
        'note' => '',
    ];

    $component = livewire(BookmarksManagerTable::class)
        ->call('syncFromClient', records: [$record])
        ->instance();

    $resolveTableRecordMethod = new ReflectionMethod($component, 'resolveTableRecord');
    $resolveTableRecordMethod->setAccessible(true);
    $resolvedRecord = $resolveTableRecordMethod->invoke($component, [
        ArrayRecord::getKeyName() => 'bookmark-3',
    ]);

    expect($resolvedRecord)->toBeArray()
        ->and((string) ($resolvedRecord['id'] ?? ''))->toBe('bookmark-3')
        ->and((int) ($resolvedRecord['page_number'] ?? 0))->toBe(7);
});

it('dispatches bookmarks manager go action payload from scalar key', function () {
    $record = [
        'id' => 'bookmark-4',
        'sort_order' => 1,
        'page_number' => 19,
        'tags' => [],
        'note' => '',
    ];

    livewire(BookmarksManagerTable::class)
        ->call('syncFromClient', records: [$record])
        ->callAction(TestAction::make('go')->table('bookmark-4'))
        ->assertDispatched(
            'quran-bookmarks-manager-go',
            fn (string $name, array $params): bool => $name === 'quran-bookmarks-manager-go'
                && (string) ($params['id'] ?? '') === 'bookmark-4'
                && (int) ($params['page_number'] ?? 0) === 19,
        );
});

it('handles history manager remove action for custom table records', function () {
    $record = [
        'id' => 'history-1',
        'sort_order' => 1,
        'page_number' => 2,
        'surah_number' => 1,
        'source' => 'search-result',
        'tags' => [],
        'note' => '',
    ];

    livewire(HistoryManagerTable::class)
        ->call('syncFromClient', records: [$record])
        ->callAction(TestAction::make('remove')->table($record))
        ->assertDispatched('quran-history-manager-removed');
});

it('resolves history manager action record from scalar record key', function () {
    $record = [
        'id' => 'history-2',
        'sort_order' => 1,
        'page_number' => 3,
        'surah_number' => 1,
        'source' => 'search-result',
        'tags' => [],
        'note' => '',
    ];

    $component = livewire(HistoryManagerTable::class)
        ->call('syncFromClient', records: [$record])
        ->instance();

    $resolveTableRecordMethod = new ReflectionMethod($component, 'resolveTableRecord');
    $resolveTableRecordMethod->setAccessible(true);
    $resolvedRecord = $resolveTableRecordMethod->invoke($component, 'history-2');

    expect($resolvedRecord)->toBeArray()
        ->and((string) ($resolvedRecord['id'] ?? ''))->toBe('history-2');
});

it('resolves history manager action record from filament array key', function () {
    $record = [
        'id' => 'history-3',
        'sort_order' => 1,
        'page_number' => 8,
        'surah_number' => 1,
        'source' => 'search-result',
        'tags' => [],
        'note' => '',
    ];

    $component = livewire(HistoryManagerTable::class)
        ->call('syncFromClient', records: [$record])
        ->instance();

    $resolveTableRecordMethod = new ReflectionMethod($component, 'resolveTableRecord');
    $resolveTableRecordMethod->setAccessible(true);
    $resolvedRecord = $resolveTableRecordMethod->invoke($component, [
        ArrayRecord::getKeyName() => 'history-3',
    ]);

    expect($resolvedRecord)->toBeArray()
        ->and((string) ($resolvedRecord['id'] ?? ''))->toBe('history-3')
        ->and((int) ($resolvedRecord['page_number'] ?? 0))->toBe(8);
});
