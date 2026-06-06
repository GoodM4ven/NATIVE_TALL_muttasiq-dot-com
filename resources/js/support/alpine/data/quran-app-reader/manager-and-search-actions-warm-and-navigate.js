export const createManagerAndSearchActionsWarmAndNavigateModule = (deps) => {
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
        stripDefiniteArticleToken(token) {
            const normalizedToken = String(token ?? '').trim();

            if (normalizedToken === '') {
                return '';
            }

            return normalizedToken.replace(/^([وفبكل]?)(?:ال)([\u0600-\u06FF]{2,})$/u, '$1$2');
        },

        stripDefiniteArticlePhrase(phrase) {
            return String(phrase ?? '')
                .trim()
                .split(/\s+/)
                .filter(Boolean)
                .map((token) => this.stripDefiniteArticleToken(token))
                .join(' ')
                .trim();
        },

        rowMatchesQueryToken(row, token) {
            const normalizedToken = String(token ?? '').trim();

            if (normalizedToken === '') {
                return false;
            }

            if (Boolean(row._tokens?.[normalizedToken])) {
                return true;
            }

            const strippedToken = this.stripDefiniteArticleToken(normalizedToken);

            if (strippedToken !== normalizedToken && strippedToken !== '') {
                return Boolean(row._tokens?.[strippedToken]);
            }

            return false;
        },

        localSearchContainsWholePhrase(text, phrase) {
            const normalizedText = String(text ?? '').trim();
            const normalizedPhrase = String(phrase ?? '').trim();

            if (normalizedText === '' || normalizedPhrase === '') {
                return false;
            }

            const containsWholePhraseBounded = (haystack, needle) => {
                let phraseOffset = haystack.indexOf(needle);

                while (phraseOffset !== -1) {
                    const beforeBoundary = phraseOffset === 0 || haystack[phraseOffset - 1] === ' ';
                    const afterIndex = phraseOffset + needle.length;
                    const afterBoundary =
                        afterIndex === haystack.length || haystack[afterIndex] === ' ';

                    if (beforeBoundary && afterBoundary) {
                        return true;
                    }

                    phraseOffset = haystack.indexOf(needle, phraseOffset + 1);
                }

                return false;
            };

            if (containsWholePhraseBounded(normalizedText, normalizedPhrase)) {
                return true;
            }

            const normalizedHamzatedText = normalizedText.replace(
                /(^|\s)ءا(?=[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF])/g,
                '$1ا',
            );
            const normalizedHamzatedPhrase = normalizedPhrase.replace(
                /(^|\s)ءا(?=[\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF])/g,
                '$1ا',
            );

            if (
                normalizedHamzatedText === normalizedText &&
                normalizedHamzatedPhrase === normalizedPhrase
            ) {
                return false;
            }

            return containsWholePhraseBounded(normalizedHamzatedText, normalizedHamzatedPhrase);
        },

        localSearchStrategyForRow(row, normalizedQuery, queryTokens) {
            if (!row || typeof row !== 'object') {
                return null;
            }

            const typedText = String(row._typed ?? '');
            const searchableText = String(row._searchable ?? '');
            const strippedDefiniteArticleQuery = this.stripDefiniteArticlePhrase(normalizedQuery);

            if (
                this.localSearchContainsWholePhrase(typedText, normalizedQuery) ||
                this.localSearchContainsWholePhrase(searchableText, normalizedQuery) ||
                (strippedDefiniteArticleQuery !== normalizedQuery &&
                    strippedDefiniteArticleQuery !== '' &&
                    (this.localSearchContainsWholePhrase(typedText, strippedDefiniteArticleQuery) ||
                        this.localSearchContainsWholePhrase(
                            searchableText,
                            strippedDefiniteArticleQuery,
                        )))
            ) {
                return 'ayah_exact';
            }

            if (
                queryTokens.length > 0 &&
                queryTokens.every((token) => this.rowMatchesQueryToken(row, token))
            ) {
                return 'ayah_close';
            }

            if (
                (typedText !== '' && typedText.includes(normalizedQuery)) ||
                (searchableText !== '' && searchableText.includes(normalizedQuery)) ||
                (strippedDefiniteArticleQuery !== normalizedQuery &&
                    strippedDefiniteArticleQuery !== '' &&
                    ((typedText !== '' && typedText.includes(strippedDefiniteArticleQuery)) ||
                        (searchableText !== '' &&
                            searchableText.includes(strippedDefiniteArticleQuery))))
            ) {
                return 'ayah_close';
            }

            return null;
        },

        buildLocalSearchResultFromRow(row, strategy) {
            const resolvedStrategy = String(strategy ?? '').trim() || 'ayah_close';
            const toneByStrategy = {
                ayah_exact: 'success',
                ayah_close: 'warning',
            };
            const shadeByStrategy = {
                ayah_exact: 50,
                ayah_close: 100,
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
                match_rank: resolvedStrategy === 'ayah_exact' ? 30 : 40,
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
            if (
                typeof this.usesFilamentNativeSearchSelect === 'function' &&
                this.usesFilamentNativeSearchSelect()
            ) {
                this.search.isLoading = false;
                return;
            }

            if (this._searchNavigationInFlight) {
                this._searchQueuedNormalizedQuery = null;
                this.search.isLoading = false;
                this.search.streamHasUpdates = false;
                this._searchRequestSerial += 1;
                this._searchRequestInFlight = false;
                this.clearSearchStreamTarget();

                return;
            }

            const rawQuery = String(this.search.query ?? '').trim();
            const normalizedQuery = this.normalizeSearchQuery(rawQuery);

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
                void this.warmSearchIndex();
            }

            if (!this.search.localIndexReady) {
                void this.warmSearchLocalIndex();
            }

            const hasSearchModalContext =
                this.search.modalOpen ||
                this.isSearchModalWindowVisible() ||
                this.searchModalWindowElement() instanceof HTMLElement ||
                this.searchModalInputElement() instanceof HTMLInputElement ||
                Boolean(this.searchResultsSelectInstance());

            if (!hasSearchModalContext) {
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

            const appendWorkerResults = (workerResults) => {
                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                const results = Array.isArray(workerResults) ? workerResults : [];

                if (results.length < 1) {
                    return;
                }

                this.search.streamHasUpdates = true;
                this.setSearchResults(this.mergeSearchResults(this.activeSearchResults(), results));
                this.$nextTick(() => {
                    this.ensureSearchResultAnimations();
                });
            };
            const runWorkerFallbackSearch = async () => {
                const workers = [
                    () => this.$wire.searchSurahExact(rawQuery, requestSerial, 24),
                    () => this.$wire.searchAyahExact(rawQuery, requestSerial, 24),
                    () => this.$wire.searchSurahClose(rawQuery, requestSerial, 24),
                    () => this.$wire.searchAyahClose(rawQuery, requestSerial, 24),
                    () => this.$wire.searchSurahSarf(rawQuery, requestSerial, 24),
                    () => this.$wire.searchAyahSarf(rawQuery, requestSerial, 24),
                    () => this.$wire.searchAyahJathr(rawQuery, requestSerial, 24),
                ];

                if (this.nativeRuntime) {
                    // Native WebView is more sensitive to overlapping heavy search requests.
                    for (const runWorker of workers) {
                        if (requestSerial !== this._searchRequestSerial) {
                            return;
                        }

                        try {
                            const results = await runWorker();

                            appendWorkerResults(results);
                        } catch (_) {
                            //
                        }
                    }

                    return;
                }

                await Promise.all(
                    workers.map(async (runWorker) => {
                        if (requestSerial !== this._searchRequestSerial) {
                            return;
                        }

                        try {
                            const results = await runWorker();

                            appendWorkerResults(results);
                        } catch (_) {
                            //
                        }
                    }),
                );
            };

            void this.applyLocalSearchPreview(normalizedQuery, requestSerial).catch(() => {
                //
            });

            const shouldUseStreamSearch =
                typeof this.shouldUseStreamSearchPipeline === 'function'
                    ? this.shouldUseStreamSearchPipeline()
                    : !this.nativeRuntime && typeof this.$wire?.streamSearch === 'function';

            try {
                if (shouldUseStreamSearch) {
                    const streamedResults = await this.$wire.streamSearch(
                        rawQuery,
                        requestSerial,
                        24,
                    );

                    if (requestSerial === this._searchRequestSerial) {
                        const resolvedResults = Array.isArray(streamedResults)
                            ? streamedResults.slice(0, 24)
                            : [];
                        const hadStreamUpdates = this.search.streamHasUpdates;

                        if (resolvedResults.length > 0) {
                            this.search.streamHasUpdates = true;
                            this.setSearchResults(
                                hadStreamUpdates
                                    ? this.mergeSearchResults(
                                          this.activeSearchResults(),
                                          resolvedResults,
                                      )
                                    : resolvedResults,
                            );
                        } else if (!this.search.streamHasUpdates) {
                            this.setSearchResults([], { immediate: true });
                        }
                    }
                } else {
                    await runWorkerFallbackSearch();
                }
            } catch (_) {
                if (requestSerial === this._searchRequestSerial) {
                    await runWorkerFallbackSearch();
                }
            } finally {
                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                this.search.isLoading = false;
                this.search.lastCompletedNormalizedQuery = normalizedQuery;
                this._searchRequestInFlight = false;
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
            const shouldUseStandardSmPlusNavigation =
                !this.nativeRuntime &&
                Boolean(this.$store?.bp?.is?.('sm+')) &&
                !Boolean(this.$store?.bp?.isTablet?.());
            const standardSmPlusSource = 'search-standard';
            if (shouldUseStandardSmPlusNavigation) {
                this.searchDestinationScaleBoostPageNumber = 0;
                this.searchDestinationScaleBoostSource = '';
                this.searchDestinationScaleBoostExpiresAt = 0;
            } else {
                this.searchDestinationScaleBoostPageNumber = targetPage;
                this.searchDestinationScaleBoostSource = isSurahNameResult
                    ? 'surah-directory'
                    : 'search-result';
            }
            let usedStandardSmPlusNavigation = false;
            let usedModalCloseGuard = false;

            try {
                this.resetNavigationQueueForPriorityJump();
                this.clearPendingPostModalTargetFit();
                this.cancelActiveSearchProcessing();
                if (!shouldUseStandardSmPlusNavigation) {
                    this.holdPageHiddenForModalLifecycle({ animateFadeOut: false });
                    this.beginModalNavigationCloseGuard(searchModalLifecycleIds);
                    usedModalCloseGuard = true;
                } else {
                    this.suppressModalLifecycleEffects(searchModalLifecycleIds, {
                        durationMs: Math.max(
                            modalLifecycleSuppressionDurationMs,
                            postModalFitRevealSettleDelayMs + 560,
                        ),
                    });
                }

                await this.requestSearchModalClose({
                    skipLayout: shouldUseStandardSmPlusNavigation,
                });
                await this.waitForModalLifecycleToSettle();
                await wait(modalCloseTransitionDelayMs);

                if (!this.isSearchModalWindowVisible() && this.search.modalOpen) {
                    this.handleSearchModalClosed();
                }

                if (shouldUseStandardSmPlusNavigation) {
                    usedStandardSmPlusNavigation = true;
                    await this.nextTickAsync();
                    await this.waitForStablePageFrame({
                        maxFrames: 18,
                        requiredStableFrames: 3,
                        tolerancePx: 0.8,
                    });
                    this._bypassNextFitCache = true;
                    this.dispatchPageNavigationRequest(targetPage, standardSmPlusSource, {
                        activeAyahIndex: highlightAyahIndex,
                        searchHighlightAyahIndex: highlightAyahIndex,
                    });
                    await wait(24);
                } else {
                    await this.navigateFromManagerModalRecord({
                        targetPage,
                        ayahIndex: highlightAyahIndex,
                        source: 'search-result',
                        modalId: this.resolveSearchModalCloseTargetId(),
                        ensureVisibleAfterModalClose: true,
                    });
                }

                if (highlightAyahIndex > 0) {
                    this.activeAyahIndex = highlightAyahIndex;
                    this.searchHighlightedAyahIndex = highlightAyahIndex;
                } else {
                    this.activeAyahIndex = 0;
                    this.searchHighlightedAyahIndex = 0;
                }
                this.activeWordIndex = 0;
                if (shouldUseStandardSmPlusNavigation) {
                    this.activateSearchDestinationCue({
                        source: standardSmPlusSource,
                        surahNumber,
                        pageNumber: targetPage,
                        ayahText: this.searchResultAyahText(result),
                    });
                } else if (isSurahNameResult) {
                    this.activateSearchDestinationCue({
                        source: 'surah-directory',
                        surahNumber,
                        pageNumber: targetPage,
                        ayahText: this.searchResultAyahText(result),
                    });
                } else {
                    this.activateSearchDestinationCue({
                        source: 'search-result',
                        surahNumber,
                        pageNumber: targetPage,
                        ayahText: this.searchResultAyahText(result),
                    });
                }
                this.recordNavigationHistory({
                    source: 'search-result',
                    pageNumber: targetPage,
                    surahNumber,
                    ayahNumber,
                    ayahIndex: highlightAyahIndex,
                    query: activeQuery,
                });
            } finally {
                if (usedModalCloseGuard) {
                    this.endModalNavigationCloseGuard();
                }

                if (
                    usedStandardSmPlusNavigation &&
                    this.hasRenderablePage() &&
                    this.openModalCount() <= 0
                ) {
                    this.clearStaleRevealGuards();
                    this.scheduleLayout({ revealDelayMs: 96, maxAttempts: 4 });
                }

                this._searchNavigationInFlight = false;
            }
        },
    };
};
