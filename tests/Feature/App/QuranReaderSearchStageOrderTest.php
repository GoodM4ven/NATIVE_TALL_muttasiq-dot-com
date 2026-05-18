<?php

declare(strict_types=1);

test('quran search stages run in exact then near then stem then root order', function () {
    $serviceSource = file_get_contents(app_path('Services/Quran/QuranReaderDataService.php'));

    expect($serviceSource)->not->toBeFalse();

    $wordPrefixPos = strpos($serviceSource, "'word_prefix'");
    $stemStagePos = strpos($serviceSource, 'appendStemTokenMatchesFromQuranWords');
    $rootStagePos = strpos($serviceSource, 'appendRootTokenMatchesFromQuranWords');

    expect($wordPrefixPos)->not->toBeFalse()
        ->and($stemStagePos)->not->toBeFalse()
        ->and($rootStagePos)->not->toBeFalse()
        ->and($wordPrefixPos)->toBeLessThan($stemStagePos)
        ->and($stemStagePos)->toBeLessThan($rootStagePos);
});

test('semantic token stage scans prefiltered quran words instead of full table', function () {
    $serviceSource = file_get_contents(app_path('Services/Quran/QuranReaderDataService.php'));

    expect($serviceSource)->not->toBeFalse()
        ->and($serviceSource)->toContain('allSemanticCandidates')
        ->and($serviceSource)->toContain('allTextCandidates')
        ->and($serviceSource)->toContain('whereIn($semanticColumn, $semanticCandidates)')
        ->and($serviceSource)->toContain('whereIn($searchColumn, $textCandidates)')
        ->and($serviceSource)->toContain('if (! $canFilterBySemantic && ! $canFilterByText) {');
});
