<?php

declare(strict_types=1);

test('quran search input clears immediately when native search clear button is used', function () {
    $readerSource = file_get_contents(app_path('Livewire/QuranApp/Reader.php'));
    $searchModalSource = file_get_contents(resource_path('views/components/partials/quran-app/search-modal.blade.php'));
    $readerScriptSource = file_get_contents(resource_path('js/support/alpine/data/quran-app-reader.js'));

    expect($readerSource)->not->toBeFalse()
        ->and($readerSource)->toContain("'x-on:search' => 'search.query = String(\$event?.target?.value ?? \'\'); queueSearchResultsUpdate(0)'")
        ->and($readerSource)->toContain("'x-on:input' => 'if (String(\$event?.target?.value ?? \'\').trim() === \'\') { queueSearchResultsUpdate(0) }'")
        ->and($searchModalSource)->toContain('x-show="shouldShowSearchNoResults()"')
        ->and($readerScriptSource)->toContain('this._searchRequestSerial += 1;')
        ->and($readerScriptSource)->toContain('this._searchRequestInFlight = false;')
        ->and($readerScriptSource)->toContain('cancelActiveSearchProcessing()')
        ->and($readerScriptSource)->toContain('this.cancelActiveSearchProcessing();')
        ->and($readerScriptSource)->toContain('async waitForSearchModalToClose(maxAttempts = 18, delayMs = 24)')
        ->and($readerScriptSource)->toContain("const closeEventName = quietly ? 'close-modal-quietly' : 'close-modal';")
        ->and($readerScriptSource)->not->toContain(
            "if (this._searchRequestInFlight) {\n                this._searchQueuedNormalizedQuery = normalizedQuery;\n\n                return;\n            }",
        );
});

test('quran modal navigation keeps immersive captions and same-page refit paths ready', function () {
    $readerScriptSource = file_get_contents(resource_path('js/support/alpine/data/quran-app-reader.js'));

    expect($readerScriptSource)->not->toBeFalse()
        ->and($readerScriptSource)->toContain('resolveSearchModalCloseTargetId()')
        ->and($readerScriptSource)->toContain('const searchModalCloseTargetId = this.resolveSearchModalCloseTargetId();')
        ->and($readerScriptSource)->toContain('onFallback: () => {},')
        ->and($readerScriptSource)->toContain('this.refreshMobileEdgeCaptions(Boolean(animate));')
        ->and($readerScriptSource)->toContain('this.syncSearchActiveSurahNumber();')
        ->and($readerScriptSource)->toContain('this.queuePendingPostModalTargetFit(pageNumber);')
        ->and($readerScriptSource)->toContain('this.queuePendingPostModalTargetFit(targetPage);')
        ->and($readerScriptSource)->toContain('const didCloseSearchModal = await this.requestSearchModalClose();')
        ->and($readerScriptSource)->toContain('if (!didCloseSearchModal) {')
        ->and($readerScriptSource)->toContain('this.schedulePendingModalCloseFit(pageNumber, {')
        ->and($readerScriptSource)->toContain('this.schedulePendingModalCloseFit(targetPage, {')
        ->and($readerScriptSource)->toContain('const isModalDrivenPriorityRequest =')
        ->and($readerScriptSource)->toContain("requestedSource === 'search-result' || requestedSource === 'surah-directory';")
        ->and($readerScriptSource)->toContain("await this.handleRequestedNavigation('page', {")
        ->and($readerScriptSource)->toContain('searchHighlightAyahIndex: highlightAyahIndex,')
        ->and($readerScriptSource)->toContain('this.searchHighlightedAyahIndex = highlightAyahIndex;')
        ->and($readerScriptSource)->toContain('this.suppressModalLifecycleEffects(searchModalLifecycleIds, {')
        ->and($readerScriptSource)->toContain('quietly: true,')
        ->and($readerScriptSource)->toContain('allowLivewireUnmount: false,')
        ->and($readerScriptSource)->toContain("new CustomEvent('close-modal-quietly', {")
        ->and($readerScriptSource)->toContain('await this.waitForModalLifecycleToSettle(28, 28);')
        ->and($readerScriptSource)->toContain("if (kind === 'closing') {\n                return;\n            }")
        ->and($readerScriptSource)->toContain("if (kind === 'closed') {\n                this.handleSearchModalClosed();")
        ->and($readerScriptSource)->toContain('this.refreshMobileEdgeCaptions(false);')
        ->and($readerScriptSource)->toContain('this.syncReaderChromeDocumentClass();')
        ->and($readerScriptSource)->toContain('return !modalStillVisible || didCloseSearchModal;')
        ->and($readerScriptSource)->toContain('this.hasBlockingModalLifecycleState({')
        ->and($readerScriptSource)->toContain('void this._managerModalVersion;')
        ->and($readerScriptSource)->not->toContain('ensureReaderChromeVisible')
        ->and($readerScriptSource)->not->toContain('scheduleModalDrivenNavigationRecovery');
});
