<?php

declare(strict_types=1);

use App\Livewire\QuranApp\BookmarksManagerTable;
use App\Livewire\QuranApp\HistoryManagerTable;
use Filament\Actions\Testing\TestAction;

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
