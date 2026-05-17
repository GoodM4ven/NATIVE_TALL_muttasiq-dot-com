<?php

declare(strict_types=1);

test('quran search input clears immediately when native search clear button is used', function () {
    $readerSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $searchModalSource = file_get_contents(resource_path('views/components/partials/quran-app/search-modal.blade.php'));

    expect($readerSource)->not->toBeFalse()
        ->and($readerSource)->toContain("'x-on:search' => 'search.query = String(\$event?.target?.value ?? \'\'); queueSearchResultsUpdate(0)'")
        ->and($readerSource)->toContain("'x-on:input' => 'if (String(\$event?.target?.value ?? \'\').trim() === \'\') { queueSearchResultsUpdate(0) }'")
        ->and($searchModalSource)->toContain('x-show="shouldShowSearchNoResults()"');
});
