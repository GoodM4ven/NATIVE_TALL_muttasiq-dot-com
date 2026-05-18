export const createManagerAndSearchActionsModule = (deps) => {
    const {
        arabicHarakatPattern,
        arabicPresentationFormsPattern,
        athkarSettingsUserOverridesStorageKey,
        bookmarkHoldDelayMs,
        bookmarksStorageKey,
        cacheAssetResponse,
        clampPage,
        controlPanelSettingKeys,
        copiedHighlightVisibleDurationMs,
        copyPopoverVisibleDurationMs,
        currentDateKey,
        defaultArabicNumerals,
        defaultBasmallahBottomGapScale,
        defaultPagePayload,
        defaultWesternNumerals,
        ensureSupportLockLivewireMorphBridge,
        fetchJsonWithCache,
        fitCacheStorageKey,
        fitCacheStorageVersion,
        fitCacheViewportBucketSizePx,
        fitCalibrationReferencePage,
        fitDefaultProfile,
        fitResultCacheLimit,
        fitRobustWidthOutlierThreshold,
        fitRobustWidthQuantile,
        hasArabicPresentationForms,
        historyEntryHasPersistenceMeta,
        historyNavigationModalLifecycleSuppressionDurationMs,
        idleWarmupPauseOnHighFrequencyNavigationMs,
        idleWarmupPauseOnStandardNavigationMs,
        idleWarmupResumeDelayMs,
        lastPageStorageKey,
        managerRowRemoveAnimationDurationMs,
        managerRowReplaceAnimationDurationMs,
        managerRowUpdateAnimationDurationMs,
        mobileDoubleTapCopyWindowMs,
        mobileDoubleTapHoldDelayMs,
        modalCloseTransitionDelayMs,
        modalLifecycleSuppressionDurationMs,
        navigationBurstInputThresholdMs,
        navigationBurstSettleDelayMs,
        navigationHistoryLimit,
        navigationHistoryStorageKey,
        navigationRevealLockDurationMs,
        navigationSettleDelayMs,
        nextAnimationFrame,
        normalizeBookmarkEntry,
        normalizeBookmarks,
        normalizeDayOffsetDays,
        normalizeHistoryEntry,
        normalizeNavigationHistory,
        normalizeNumerals,
        normalizePayload,
        normalizeSupportUnlockState,
        normalizeTags,
        normalizeTextValue,
        openCacheSafely,
        openingSpreadFinalScaleMultiplier,
        pageCounterPulseDurationMs,
        pageFontLoadTimeoutMs,
        pageFontReadyRecoveryDelayMs,
        pageFontReadyTimeoutMs,
        postModalFitRevealSettleDelayMs,
        pruneNavigationHistory,
        quranPageGapAdjustMax,
        quranPageGapAdjustMin,
        quranPageGapAdjustMultiplierStep,
        quranPageGapAdjustStorageKey,
        quranPageScaleAdjustMax,
        quranPageScaleAdjustMin,
        quranPageScaleAdjustMultiplierStep,
        quranPageScaleAdjustStorageKey,
        quranPageYOffsetAdjustMax,
        quranPageYOffsetAdjustMin,
        quranPageYOffsetAdjustRemStep,
        quranPageYOffsetAdjustStorageKey,
        quranReaderDebugLogsEnabledByEnv,
        quranReaderDebugLogsToggleEventName,
        quranSearchStreamFrameDelimiter,
        readBookmarks,
        readLastPageNumber,
        readLocalStorage,
        readLocalStorageRaw,
        readNavigationHistory,
        readSupportUnlockState,
        readWirdDayOffsetDays,
        readerRevealDebugStorageKey,
        revealBlockedFailOpenDelayMs,
        shouldPersistFitCacheAcrossReloads,
        stripArabicHarakat,
        supportLockClosedOutlineIconSvg,
        supportLockLivewireMorphedEventName,
        supportUnlockModePermanent,
        supportUnlockModeWeekly,
        supportUnlockStorageKey,
        supportUnlockStorageVersion,
        supportUnlockWeeklyDurationMs,
        supportedHistorySources,
        surahQuickNavigatorHoldDelayMs,
        surahQuickNavigatorLastPage,
        swipeActivationThresholdPx,
        swipeRevealWatchdogDelayMs,
        uniqueLocalId,
        wait,
        wirdCompletionVisibleDurationMs,
        wirdDailyKhatmatTargetMax,
        wirdDayOffsetStorageKey,
        wirdFrequencyModeDaily,
        wirdFrequencyModeMonthly,
        wirdHoverShimmerDurationMs,
        wirdKhatmatTargetMin,
        wirdModeEntryPageInputTweenDurationMs,
        wirdMonthlyKhatmatTargetMax,
        wirdProgressStorageKey,
        wirdProgressStorageVersion,
        wirdRecordRetentionDays,
        wordClickSuppressionResetMs,
        wordPressDragThresholdPx,
        wordPressHoldDelayMs,
        writeBookmarks,
        writeLastPageNumber,
        writeLocalStorage,
        writeNavigationHistory,
        writeSupportUnlockState,
        writeWirdDayOffsetDays,
    } = deps;

    return {
        ensureHistoryRowsAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._historyRowsAutoAnimateStop === 'function') {
                return;
            }

            const historyRowsContainer = this.$refs.historyRowsList;

            if (!(historyRowsContainer instanceof Element)) {
                return;
            }

            this._historyRowsAutoAnimateStop = window.autoAnimate(historyRowsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        ensureBookmarksRowsAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._bookmarksRowsAutoAnimateStop === 'function') {
                return;
            }

            const bookmarksRowsContainer = this.$refs.bookmarksRowsList;

            if (!(bookmarksRowsContainer instanceof Element)) {
                return;
            }

            this._bookmarksRowsAutoAnimateStop = window.autoAnimate(bookmarksRowsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        teardownHistoryRowsAnimations() {
            if (typeof this._historyRowsAutoAnimateStop !== 'function') {
                return;
            }

            this._historyRowsAutoAnimateStop();
            this._historyRowsAutoAnimateStop = null;
        },

        teardownBookmarksRowsAnimations() {
            if (typeof this._bookmarksRowsAutoAnimateStop !== 'function') {
                return;
            }

            this._bookmarksRowsAutoAnimateStop();
            this._bookmarksRowsAutoAnimateStop = null;
        },

        teardownSearchResultAnimations() {
            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                this._searchResultsAutoAnimateStop();
                this._searchResultsAutoAnimateStop = null;
            }
        },

        ensureSearchResultAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                return;
            }

            const searchResultsContainer = this.$refs.searchResultsList;

            if (!(searchResultsContainer instanceof Element)) {
                return;
            }

            this._searchResultsAutoAnimateStop = window.autoAnimate(searchResultsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        async handleSearchModalOpened() {
            await this.warmSearchIndex();
            this.search.modalOpen = true;
            this._searchNavigationInFlight = false;
            this._lastKnownModalOpenState = true;
            this._skipNextSearchModalCloseLayout = false;
            this.refreshSurahTriggerCaption(false);
            this.dispatchManagerModalsVisibilityState();

            if (this.search.preserveActiveSurahOnNextOpen) {
                this.search.preserveActiveSurahOnNextOpen = false;
            } else {
                this.syncSearchActiveSurahNumber();
            }

            this.search.query = '';
            this.setSearchResults([], { immediate: true });
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;

            await this.nextTickAsync();
            this.setupSearchStreamObserver();
            this.clearSearchStreamTarget();
            this.ensureSearchResultAnimations();
            this.bindSearchModalInputSyncListener();
            this.searchModalInputElement()?.focus?.();
            this.queueSurahDirectoryAutoFocus();
            this._surahDirectoryPostOpenTimers = [260, 560, 920].map((delayMs) =>
                window.setTimeout(() => {
                    if (!this.search.modalOpen) {
                        return;
                    }

                    this.scrollSurahDirectoryToActive({ behavior: 'auto' });
                }, delayMs),
            );
        },

        handleSearchModalClosed() {
            this.cancelSurahDirectoryAutoFocus();
            this.clearSearchResultsUpdateQueue();
            this.unbindSearchModalInputSyncListener();
            this.teardownSearchStreamObserver();
            this.clearSearchStreamTarget();
            this.teardownSearchResultAnimations();
            this._searchRequestSerial += 1;
            this._searchRequestInFlight = false;
            this._searchNavigationInFlight = false;
            this._searchQueuedNormalizedQuery = null;
            this.search.modalOpen = false;
            this._lastKnownModalOpenState = false;
            this.search.query = '';
            this.setSearchResults([], { immediate: true });
            this.search.isLoading = false;
            this.dispatchManagerModalsVisibilityState();

            if (this._searchModalCloseDebounceTimer !== null) {
                clearTimeout(this._searchModalCloseDebounceTimer);
                this._searchModalCloseDebounceTimer = null;
            }

            if (this._skipNextSearchModalCloseLayout) {
                this._skipNextSearchModalCloseLayout = false;

                return;
            }
        },

        async requestModalCloseByKnownIds(
            knownModalIds = [],
            {
                onFallback = null,
                isModalStillVisible = null,
                quietly = false,
                allowLivewireUnmount = true,
            } = {},
        ) {
            const normalizedModalIds = Array.from(
                new Set(
                    (Array.isArray(knownModalIds) ? knownModalIds : [knownModalIds])
                        .map((value) => String(value ?? '').trim())
                        .filter((value) => value !== ''),
                ),
            );
            const resolveModalVisibleState = (modalId = '') => {
                if (typeof isModalStillVisible === 'function') {
                    return Boolean(isModalStillVisible());
                }

                const normalizedModalId = String(modalId ?? '').trim();

                if (normalizedModalId === '') {
                    return false;
                }

                return this.isModalWindowVisibleById(normalizedModalId);
            };
            const closeEventName = quietly ? 'close-modal-quietly' : 'close-modal';

            for (const modalId of normalizedModalIds) {
                window.dispatchEvent(
                    new CustomEvent(closeEventName, {
                        detail: {
                            id: modalId,
                        },
                    }),
                );
                await wait(16);

                if (!resolveModalVisibleState(modalId)) {
                    return true;
                }
            }

            if (allowLivewireUnmount && typeof this.$wire?.unmountAction === 'function') {
                try {
                    await this.$wire.unmountAction(false);
                    await wait(16);

                    if (!resolveModalVisibleState(normalizedModalIds[0] ?? '')) {
                        return true;
                    }
                } catch (_) {
                    //
                }
            }

            if (typeof onFallback === 'function') {
                onFallback();
                await wait(16);
            }

            return !resolveModalVisibleState(normalizedModalIds[0] ?? '');
        },

        async requestSearchModalClose({ skipLayout = false } = {}) {
            if (skipLayout) {
                this._skipNextSearchModalCloseLayout = true;
            }

            const searchModalCloseTargetId = this.resolveSearchModalCloseTargetId();
            let didCloseSearchModal = await this.requestModalCloseByKnownIds(
                [
                    searchModalCloseTargetId,
                    this.searchActionModalId,
                    this.searchModalId,
                    this.searchModalDomId,
                ],
                {
                    onFallback: () => {},
                    isModalStillVisible: () => this.isSearchModalWindowVisible(),
                    quietly: true,
                    allowLivewireUnmount: false,
                },
            );

            if (!didCloseSearchModal && this.isSearchModalWindowVisible()) {
                const fallbackModalId = this.resolveSearchModalCloseTargetId();

                if (fallbackModalId !== '') {
                    window.dispatchEvent(
                        new CustomEvent('close-modal-quietly', {
                            detail: {
                                id: fallbackModalId,
                            },
                        }),
                    );
                }

                didCloseSearchModal = await this.waitForSearchModalToClose();
            }

            this.syncManagerModalFlagsFromVisibility();
            const modalStillVisible = this.isSearchModalWindowVisible();

            if (this.search.modalOpen && modalStillVisible) {
                this.queueSearchModalCloseSync({ delayMs: 120 });

                return false;
            }

            if (this.search.modalOpen && !modalStillVisible) {
                this.handleSearchModalClosed();

                return true;
            }

            return !modalStillVisible || didCloseSearchModal;
        },

        async waitForSearchModalToClose(maxAttempts = 18, delayMs = 24) {
            const attempts = Math.max(1, Math.trunc(Number(maxAttempts) || 18));
            const normalizedDelayMs = Math.max(12, Math.trunc(Number(delayMs) || 24));

            for (let attempt = 0; attempt < attempts; attempt += 1) {
                if (!this.isSearchModalWindowVisible()) {
                    if (this.openModalCount() <= 0) {
                        this.recoverStaleModalLifecycleState();
                    }

                    return true;
                }

                await wait(normalizedDelayMs);
            }

            return !this.isSearchModalWindowVisible();
        },

        async waitForModalLifecycleToSettle(maxAttempts = 14, delayMs = 24) {
            const attempts = Math.max(1, Math.trunc(Number(maxAttempts) || 14));
            const waitDelayMs = Math.max(12, Math.trunc(Number(delayMs) || 24));

            for (let attempt = 0; attempt < attempts; attempt += 1) {
                if (this.openModalCount() <= 0) {
                    this.recoverStaleModalLifecycleState();
                }

                if (
                    this.openModalCount() <= 0 &&
                    !this._isModalLifecycleSettling &&
                    this._activeModalIds.size === 0
                ) {
                    return true;
                }

                await wait(waitDelayMs);
            }

            if (this.openModalCount() <= 0) {
                this.recoverStaleModalLifecycleState();
            }

            return (
                this.openModalCount() <= 0 &&
                !this._isModalLifecycleSettling &&
                this._activeModalIds.size === 0
            );
        },

        async requestHistoryModalClose() {
            await this.requestModalCloseByKnownIds([this.historyModalId], {
                onFallback: () => {
                    this.historyModalOpen = false;
                    this.teardownHistoryRowsAnimations();
                    this.dispatchManagerModalsVisibilityState();
                },
            });
        },

        async requestBookmarksModalClose() {
            await this.requestModalCloseByKnownIds([this.bookmarksModalId], {
                onFallback: () => {
                    this.bookmarksModalOpen = false;
                    this.teardownBookmarksRowsAnimations();
                    this.dispatchManagerModalsVisibilityState();
                },
            });
        },

        requestReaderGateNavigation(_source = 'generic') {
            this.resetSwipeState();
            this.clearWordPressState();
            void this.requestSearchModalClose({ skipLayout: true });
            window.dispatchEvent(new CustomEvent('quran-go-gate'));
        },

        async goToHistoryEntry(entry) {
            const targetPage = clampPage(Number(entry?.page_number ?? 1), this.maxPage);
            const ayahIndex = Math.max(0, Math.trunc(Number(entry?.ayah_index ?? 0)));

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.suppressModalLifecycleEffects([this.historyModalId], {
                durationMs: historyNavigationModalLifecycleSuppressionDurationMs,
            });
            await this.requestHistoryModalClose();
            await this.waitForModalLifecycleToSettle();
            await wait(modalCloseTransitionDelayMs);
            this.suppressModalLifecycleEffects([this.historyModalId], {
                durationMs: historyNavigationModalLifecycleSuppressionDurationMs,
            });
            this._bypassNextFitCache = true;
            await this.goToPageFromChevron(targetPage, {
                activeAyahIndex: ayahIndex,
                source: 'history-entry',
                commitNow: true,
                settleDelayMs: 0,
            });

            const shouldQueuePostModalFit =
                !this.isCurrentPageVisiblyReady() || this._lastFittedPageNumber !== this.pageNumber;

            if (shouldQueuePostModalFit) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 160,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            } else if (this.hasRenderablePage()) {
                this._bypassNextFitCache = true;
                this.fitPageToViewport();
                this.applySafetyScaleForCurrentPageOverflow();
                this._lastPageRevealAt = Date.now();
            }

            this.activeWordIndex = 0;
        },

        async goToBookmark(bookmark) {
            const targetPage = clampPage(Number(bookmark?.page_number ?? 1), this.maxPage);

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.suppressModalLifecycleEffects([this.bookmarksModalId]);
            await this.requestBookmarksModalClose();
            await this.waitForModalLifecycleToSettle();
            await wait(modalCloseTransitionDelayMs);
            this._bypassNextFitCache = true;
            await this.goToPageFromChevron(targetPage, {
                activeAyahIndex: 0,
                source: 'bookmark',
                commitNow: true,
                settleDelayMs: 0,
            });

            const shouldQueuePostModalFit =
                !this.isCurrentPageVisiblyReady() || this._lastFittedPageNumber !== this.pageNumber;

            if (shouldQueuePostModalFit) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 160,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            } else if (this.hasRenderablePage()) {
                this._bypassNextFitCache = true;
                this.fitPageToViewport();
                this.applySafetyScaleForCurrentPageOverflow();
                this._lastPageRevealAt = Date.now();
            }
            this.activeAyahIndex = 0;
            this.activeWordIndex = 0;
            this.recordNavigationHistory({
                source: 'bookmark-navigation',
                pageNumber: targetPage,
                surahNumber: this.currentSurahNumber(),
            });
        },

        async confirmSearchSelection() {
            if (!this.search.readyResult) {
                return;
            }

            await this.goToSearchResult(this.search.readyResult);
        },

        async goToSurahFromDirectory(entry) {
            if (this._searchNavigationInFlight) {
                return;
            }

            this._searchNavigationInFlight = true;
            const pageNumber = clampPage(Number(entry?.page_number ?? 1), this.maxPage);
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));
            const searchModalLifecycleIds = [
                this.resolveSearchModalCloseTargetId(),
                this.searchActionModalId,
                this.searchModalId,
                this.searchModalDomId,
            ]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            this.search.activeSurahNumber = surahNumber;
            this.search.preserveActiveSurahOnNextOpen = true;

            try {
                this.resetNavigationQueueForPriorityJump();
                this.clearPendingPostModalTargetFit();
                this.cancelActiveSearchProcessing();
                this.suppressModalLifecycleEffects(searchModalLifecycleIds);
                await this.requestSearchModalClose();
                await this.waitForModalLifecycleToSettle();
                await wait(modalCloseTransitionDelayMs);

                if (!this.isSearchModalWindowVisible() && this.search.modalOpen) {
                    this.handleSearchModalClosed();
                }

                this._bypassNextFitCache = true;
                await this.goToPageFromChevron(pageNumber, {
                    activeAyahIndex: 0,
                    source: 'surah-directory',
                    commitNow: true,
                    settleDelayMs: 0,
                });

                const shouldQueuePostModalFit =
                    !this.isCurrentPageVisiblyReady() ||
                    this._lastFittedPageNumber !== this.pageNumber;

                if (shouldQueuePostModalFit) {
                    this._bypassNextFitCache = true;
                    await this.layoutPageGuaranteed({
                        revealDelayMs: 160,
                        maxAttempts: 3,
                        useIdleFit: false,
                    });
                } else if (this.hasRenderablePage()) {
                    this._bypassNextFitCache = true;
                    this.fitPageToViewport();
                    this.applySafetyScaleForCurrentPageOverflow();
                    this._lastPageRevealAt = Date.now();
                }

                this.search.activeSurahNumber = surahNumber;
                this.activeAyahIndex = 0;
                this.activeWordIndex = 0;
                this.searchHighlightedAyahIndex = 0;
                this.recordNavigationHistory({
                    source: 'surah-directory',
                    pageNumber,
                    surahNumber,
                });
            } finally {
                this._searchNavigationInFlight = false;
            }
        },

        scheduleManagerModalsPrewarm(delayMs = 220) {
            const normalizedDelay = Math.max(0, Math.trunc(Number(delayMs) || 0));

            window.setTimeout(() => {
                if (
                    this._managerModalsPrewarmed ||
                    this._managerModalsPrewarmPromise !== null ||
                    this.historyModalOpen ||
                    this.bookmarksModalOpen ||
                    this.search.modalOpen ||
                    this.jumpPageModalOpen
                ) {
                    return;
                }

                const runPrewarm = () => {
                    void this.prewarmManagerModals();
                };

                if (typeof window.requestIdleCallback === 'function') {
                    window.requestIdleCallback(runPrewarm, { timeout: 640 });

                    return;
                }

                runPrewarm();
            }, normalizedDelay);
        },

        async prewarmManagerModals() {
            if (this._managerModalsPrewarmed) {
                return;
            }

            if (this._managerModalsPrewarmPromise !== null) {
                await this._managerModalsPrewarmPromise;

                return;
            }

            if (typeof this.$wire?.prewarmManagerModals !== 'function') {
                return;
            }

            this._managerModalsPrewarmPromise = (async () => {
                try {
                    await this.$wire.prewarmManagerModals();
                    this._managerModalsPrewarmed = true;
                } catch (_) {
                    // Ignore background prewarm failures.
                } finally {
                    this._managerModalsPrewarmPromise = null;
                }
            })();

            await this._managerModalsPrewarmPromise;
        },

        searchRequestUrl(query = '') {
            const baseUrl = String(this.api.searchIndexUrl ?? '').trim();

            if (!baseUrl) {
                return '';
            }

            if (!query) {
                return baseUrl;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', query);

            return url.toString();
        },

        searchLocalIndexRequestUrl() {
            const baseUrl = this.searchRequestUrl();

            if (!baseUrl) {
                return '';
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('local', '1');

            return url.toString();
        },

        normalizeLocalSearchIndexRows(items = []) {
            return (Array.isArray(items) ? items : [])
                .map((item) => {
                    if (!item || typeof item !== 'object') {
                        return null;
                    }

                    const typedNormalized = this.normalizeSearchQuery(
                        item.text_searchable_typed ?? '',
                    );
                    const searchableNormalized = this.normalizeSearchQuery(
                        item.text_searchable ?? '',
                    );
                    const tokenLookup = {};

                    `${typedNormalized} ${searchableNormalized}`
                        .trim()
                        .split(/\s+/)
                        .filter(Boolean)
                        .forEach((token) => {
                            tokenLookup[token] = true;
                        });

                    return {
                        id: Math.max(0, Math.trunc(Number(item.id ?? 0))),
                        ayah_index: Math.max(0, Math.trunc(Number(item.ayah_index ?? 0))),
                        surah_number: Math.max(1, Math.trunc(Number(item.surah_number ?? 1))),
                        ayah_number: Math.max(1, Math.trunc(Number(item.ayah_number ?? 1))),
                        page_number: Math.max(1, Math.trunc(Number(item.page_number ?? 1))),
                        text_uthmani: String(item.text_uthmani ?? '').trim(),
                        text_searchable_typed: String(item.text_searchable_typed ?? '').trim(),
                        _typed: typedNormalized,
                        _searchable: searchableNormalized,
                        _tokens: tokenLookup,
                    };
                })
                .filter((item) => item !== null && (item._typed !== '' || item._searchable !== ''));
        },

        localSearchContainsWholePhrase(text, phrase) {
            const normalizedText = String(text ?? '').trim();
            const normalizedPhrase = String(phrase ?? '').trim();

            if (normalizedText === '' || normalizedPhrase === '') {
                return false;
            }

            let phraseOffset = normalizedText.indexOf(normalizedPhrase);

            while (phraseOffset !== -1) {
                const beforeBoundary =
                    phraseOffset === 0 || normalizedText[phraseOffset - 1] === ' ';
                const afterIndex = phraseOffset + normalizedPhrase.length;
                const afterBoundary =
                    afterIndex === normalizedText.length || normalizedText[afterIndex] === ' ';

                if (beforeBoundary && afterBoundary) {
                    return true;
                }

                phraseOffset = normalizedText.indexOf(normalizedPhrase, phraseOffset + 1);
            }

            return false;
        },

        localSearchStrategyForRow(row, normalizedQuery, queryTokens) {
            if (!row || typeof row !== 'object') {
                return null;
            }

            const typedText = String(row._typed ?? '');
            const searchableText = String(row._searchable ?? '');

            if (
                this.localSearchContainsWholePhrase(typedText, normalizedQuery) ||
                this.localSearchContainsWholePhrase(searchableText, normalizedQuery)
            ) {
                return 'exact_phrase';
            }

            if (
                queryTokens.length > 0 &&
                queryTokens.every((token) => Boolean(row._tokens?.[token]))
            ) {
                return 'exact_tokens';
            }

            if (
                (typedText !== '' && typedText.includes(normalizedQuery)) ||
                (searchableText !== '' && searchableText.includes(normalizedQuery))
            ) {
                return 'word_prefix';
            }

            return null;
        },

        buildLocalSearchResultFromRow(row, strategy) {
            const resolvedStrategy = String(strategy ?? '').trim() || 'exact_tokens';
            const toneByStrategy = {
                exact_phrase: 'success',
                exact_tokens: 'success',
                word_prefix: 'warning',
            };
            const shadeByStrategy = {
                exact_phrase: 50,
                exact_tokens: 50,
                word_prefix: 100,
            };

            return {
                id: Math.max(0, Math.trunc(Number(row?.id ?? 0))),
                ayah_index: Math.max(0, Math.trunc(Number(row?.ayah_index ?? 0))),
                surah_number: Math.max(1, Math.trunc(Number(row?.surah_number ?? 1))),
                ayah_number: Math.max(1, Math.trunc(Number(row?.ayah_number ?? 1))),
                page_number: Math.max(1, Math.trunc(Number(row?.page_number ?? 1))),
                text_uthmani: String(row?.text_uthmani ?? '').trim(),
                text_searchable_typed: String(row?.text_searchable_typed ?? '').trim(),
                search_snippet: String(row?.text_searchable_typed ?? '').trim(),
                match_strategy: resolvedStrategy,
                match_tone: toneByStrategy[resolvedStrategy] ?? 'warning',
                match_shade: shadeByStrategy[resolvedStrategy] ?? 100,
                match_label: '',
                match_rank: resolvedStrategy === 'exact_phrase' ? 30 : 40,
            };
        },

        async warmSearchLocalIndex() {
            if (this.search.localIndexReady || !this.api.searchIndexUrl) {
                return;
            }

            if (this._searchLocalIndexPromise) {
                await this._searchLocalIndexPromise;

                return;
            }

            this._searchLocalIndexPromise = (async () => {
                try {
                    const payload = await fetchJsonWithCache({
                        url: this.searchLocalIndexRequestUrl(),
                        cacheName: this.cacheNames.searchLocalIndex,
                        preferCache: true,
                    });
                    const localRows = this.normalizeLocalSearchIndexRows(payload?.items ?? []);
                    this._searchLocalRows = localRows;
                    this.search.localIndexReady = localRows.length > 0;
                } catch (_) {
                    this._searchLocalRows = [];
                    this.search.localIndexReady = false;
                } finally {
                    this._searchLocalIndexPromise = null;
                }
            })();

            await this._searchLocalIndexPromise;
        },

        async applyLocalSearchPreview(normalizedQuery, requestSerial) {
            if (!this.search.localIndexReady || !Array.isArray(this._searchLocalRows)) {
                return;
            }

            const queryTokens = normalizedQuery.split(/\s+/).filter(Boolean);

            if (queryTokens.length === 0) {
                return;
            }

            const localResults = [];
            let lastRenderedCount = 0;

            for (let rowIndex = 0; rowIndex < this._searchLocalRows.length; rowIndex += 1) {
                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                const row = this._searchLocalRows[rowIndex];
                const matchStrategy = this.localSearchStrategyForRow(
                    row,
                    normalizedQuery,
                    queryTokens,
                );

                if (!matchStrategy) {
                    continue;
                }

                localResults.push(this.buildLocalSearchResultFromRow(row, matchStrategy));

                if (localResults.length >= 24) {
                    break;
                }

                if (
                    localResults.length > lastRenderedCount &&
                    (localResults.length % 2 === 0 || rowIndex % 160 === 0)
                ) {
                    this.search.streamHasUpdates = true;
                    this.setSearchResults(
                        this.mergeSearchResults(this.activeSearchResults(), localResults),
                    );
                    lastRenderedCount = localResults.length;
                    await this.nextTickAsync();
                }
            }

            if (requestSerial !== this._searchRequestSerial || localResults.length === 0) {
                return;
            }

            this.search.streamHasUpdates = true;
            this.setSearchResults(
                this.mergeSearchResults(this.activeSearchResults(), localResults),
            );
        },

        async warmSearchIndex() {
            if (this.search.isReady || this.search.isLoading || !this.api.searchIndexUrl) {
                return;
            }

            if (this._searchIndexPromise) {
                await this._searchIndexPromise;

                return;
            }

            this.search.isLoading = true;

            this._searchIndexPromise = (async () => {
                try {
                    const payload = await fetchJsonWithCache({
                        url: this.searchRequestUrl(),
                        cacheName: this.cacheNames.search,
                        preferCache: true,
                    });

                    if (
                        payload &&
                        typeof payload === 'object' &&
                        payload.surah_names &&
                        Object.keys(payload.surah_names).length > 0
                    ) {
                        this.search.surahNames = payload.surah_names;
                    }

                    let surahDirectory = Array.isArray(payload?.surah_directory)
                        ? payload.surah_directory
                        : [];

                    if (
                        surahDirectory.length === 0 &&
                        Array.isArray(payload?.items) &&
                        payload.items.length > 0
                    ) {
                        surahDirectory = this.deriveSurahDirectoryFromItems(payload.items);
                    }

                    this.buildSurahDirectory(surahDirectory);
                    this.refreshSurahTriggerCaption(false);
                    this.search.isReady = true;
                    void this.warmSearchLocalIndex();
                } catch (_) {
                    if (
                        !Array.isArray(this.search.surahDirectory) ||
                        this.search.surahDirectory.length === 0
                    ) {
                        this.buildSurahDirectory([]);
                    }

                    this.search.isReady = false;
                } finally {
                    this.search.isLoading = false;
                    this._searchIndexPromise = null;
                }
            })();

            await this._searchIndexPromise;
        },

        async updateSearchResults() {
            if (this._searchNavigationInFlight) {
                this._searchQueuedNormalizedQuery = null;
                this.search.isLoading = false;
                this.search.streamHasUpdates = false;
                this._searchRequestSerial += 1;
                this._searchRequestInFlight = false;
                this.clearSearchStreamTarget();

                return;
            }

            const normalizedQuery = this.normalizeSearchQuery(this.search.query);

            if (!normalizedQuery) {
                this._searchQueuedNormalizedQuery = null;
                this.setSearchResults([], { immediate: true });
                this.search.isLoading = false;
                this.search.streamHasUpdates = false;
                this.search.lastCompletedNormalizedQuery = '';
                this._searchRequestSerial += 1;
                this._searchRequestInFlight = false;
                this.clearSearchStreamTarget();

                return;
            }

            if (normalizedQuery.length < this.search.minQueryLength) {
                this._searchQueuedNormalizedQuery = null;
                this.setSearchResults([], { immediate: true });
                this.search.isLoading = false;
                this.search.streamHasUpdates = false;
                this.search.lastCompletedNormalizedQuery = '';
                this._searchRequestSerial += 1;
                this._searchRequestInFlight = false;
                this.clearSearchStreamTarget();

                return;
            }

            if (!this.search.isReady) {
                await this.warmSearchIndex();
            }

            if (!this.search.isReady) {
                this._searchQueuedNormalizedQuery = null;
                this.setSearchResults([], { immediate: true });
                this.search.isLoading = false;
                this.search.streamHasUpdates = false;
                this.search.lastCompletedNormalizedQuery = '';
                this._searchRequestSerial += 1;
                this._searchRequestInFlight = false;
                this.clearSearchStreamTarget();

                return;
            }

            if (!this.search.localIndexReady) {
                void this.warmSearchLocalIndex();
            }

            const isSearchModalVisible = this.search.modalOpen || this.isSearchModalWindowVisible();

            if (!isSearchModalVisible) {
                return;
            }

            if (!this.search.modalOpen) {
                this.search.modalOpen = true;
                this._lastKnownModalOpenState = true;
            }

            const requestSerial = ++this._searchRequestSerial;
            this._searchRequestInFlight = true;
            this._searchQueuedNormalizedQuery = null;
            this.setSearchResults([], { immediate: true });
            this.search.isLoading = true;
            this.search.streamHasUpdates = false;
            this.clearSearchStreamTarget();
            await this.applyLocalSearchPreview(normalizedQuery, requestSerial);

            try {
                const livewireResults = await this.$wire.streamSearch(
                    normalizedQuery,
                    requestSerial,
                );
                const results = Array.isArray(livewireResults) ? livewireResults.slice(0, 24) : [];

                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                this.setSearchResults(
                    this.search.streamHasUpdates
                        ? this.mergeSearchResults(this.activeSearchResults(), results)
                        : results,
                );
                this.$nextTick(() => {
                    this.ensureSearchResultAnimations();
                });
            } catch (error) {
                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                this.setSearchResults([], { immediate: true });
            } finally {
                if (requestSerial === this._searchRequestSerial) {
                    this.search.isLoading = false;
                    this.search.lastCompletedNormalizedQuery = normalizedQuery;
                    this._searchRequestInFlight = false;
                }
                this._searchQueuedNormalizedQuery = null;
            }
        },

        async goToSearchResult(result) {
            if (this._searchNavigationInFlight) {
                return;
            }

            this._searchNavigationInFlight = true;
            const targetPage = clampPage(Number(result?.page_number ?? 1), this.maxPage);
            const ayahIndex = Math.max(0, Math.trunc(Number(result?.ayah_index ?? 0)));
            const activeQuery = this.search.query;
            const surahNumber = Math.max(1, Math.trunc(Number(result?.surah_number ?? 1)));
            const ayahNumber = Math.max(0, Math.trunc(Number(result?.ayah_number ?? 0)));
            const isSurahNameResult = this.isSurahNameSearchResult(result);
            const highlightAyahIndex = isSurahNameResult
                ? 0
                : ayahIndex > 0
                  ? ayahIndex
                  : ayahNumber;
            const searchModalLifecycleIds = [
                this.resolveSearchModalCloseTargetId(),
                this.searchActionModalId,
                this.searchModalId,
                this.searchModalDomId,
            ]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            try {
                this.resetNavigationQueueForPriorityJump();
                this.clearPendingPostModalTargetFit();
                this.cancelActiveSearchProcessing();
                this.suppressModalLifecycleEffects(searchModalLifecycleIds);
                await this.requestSearchModalClose();
                await this.waitForModalLifecycleToSettle();
                await wait(modalCloseTransitionDelayMs);

                if (!this.isSearchModalWindowVisible() && this.search.modalOpen) {
                    this.handleSearchModalClosed();
                }

                this._bypassNextFitCache = true;
                await this.goToPageFromChevron(targetPage, {
                    activeAyahIndex: highlightAyahIndex,
                    searchHighlightAyahIndex: highlightAyahIndex,
                    source: 'search-result',
                    commitNow: true,
                    settleDelayMs: 0,
                });

                const shouldQueuePostModalFit =
                    !this.isCurrentPageVisiblyReady() ||
                    this._lastFittedPageNumber !== this.pageNumber;

                if (shouldQueuePostModalFit) {
                    this._bypassNextFitCache = true;
                    await this.layoutPageGuaranteed({
                        revealDelayMs: 160,
                        maxAttempts: 3,
                        useIdleFit: false,
                    });
                } else if (this.hasRenderablePage()) {
                    this._bypassNextFitCache = true;
                    this.fitPageToViewport();
                    this.applySafetyScaleForCurrentPageOverflow();
                    this._lastPageRevealAt = Date.now();
                }

                if (highlightAyahIndex > 0) {
                    this.activeAyahIndex = highlightAyahIndex;
                    this.searchHighlightedAyahIndex = highlightAyahIndex;
                } else {
                    this.activeAyahIndex = 0;
                    this.searchHighlightedAyahIndex = 0;
                }
                this.activeWordIndex = 0;
                this.recordNavigationHistory({
                    source: 'search-result',
                    pageNumber: targetPage,
                    surahNumber,
                    ayahNumber,
                    ayahIndex: highlightAyahIndex,
                    query: activeQuery,
                });
            } finally {
                this._searchNavigationInFlight = false;
            }
        },
    };
};
