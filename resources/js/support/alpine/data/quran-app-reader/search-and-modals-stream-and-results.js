export const createSearchAndModalsStreamAndResultsModule = (deps) => {
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
        clearSearchResultsUpdateQueue() {
            if (this._searchInputSyncDebounceTimer !== null) {
                clearTimeout(this._searchInputSyncDebounceTimer);
                this._searchInputSyncDebounceTimer = null;
            }
        },

        prepareSearchUiForNextQuery(normalizedQuery = '') {
            const normalized = String(normalizedQuery ?? '').trim();

            if (this._searchRequestInFlight) {
                const activeRequestSerial = Math.max(
                    0,
                    Math.trunc(Number(this._searchRequestSerial ?? 0)),
                );

                if (activeRequestSerial > 0 && typeof this.$wire?.cancelSearch === 'function') {
                    void this.$wire.cancelSearch(activeRequestSerial);
                }

                this._searchRequestSerial += 1;
                this._searchRequestInFlight = false;
                this.clearSearchStreamTarget();
            }

            this.search.lastCompletedNormalizedQuery = '';
            this.search.streamHasUpdates = false;
            this.setSearchResults([], { immediate: true });

            if (normalized === '' || normalized.length < this.search.minQueryLength) {
                this.search.isLoading = false;

                return;
            }

            this.search.isLoading = true;
        },

        queueSearchResultsUpdate(delayMs = null) {
            if (this._searchNavigationInFlight) {
                this.clearSearchResultsUpdateQueue();
                this.search.isLoading = false;

                return;
            }

            const fallbackDelayMs = Math.max(
                120,
                Math.trunc(Number(this.search.inputDebounceMs ?? 600) || 600),
            );
            const normalizedDelayMs =
                delayMs === null
                    ? fallbackDelayMs
                    : Math.max(0, Math.trunc(Number(delayMs) || fallbackDelayMs));

            this.clearSearchResultsUpdateQueue();
            this.prepareSearchUiForNextQuery(this.normalizeSearchQuery(this.search.query));

            if (normalizedDelayMs === 0) {
                void this.updateSearchResults();

                return;
            }

            this._searchInputSyncDebounceTimer = window.setTimeout(() => {
                this._searchInputSyncDebounceTimer = null;
                void this.updateSearchResults();
            }, normalizedDelayMs);
        },

        bindSearchModalInputSyncListener() {
            const inputElement = this.searchModalInputElement();

            if (!(inputElement instanceof HTMLInputElement)) {
                return false;
            }

            if (
                this._searchModalInputSyncElement === inputElement &&
                typeof this._onSearchModalInputSync === 'function'
            ) {
                return true;
            }

            this.unbindSearchModalInputSyncListener();

            this._onSearchModalInputSync = (event) => {
                const targetInput =
                    event?.target instanceof HTMLInputElement ? event.target : inputElement;
                const nextQuery = String(targetInput?.value ?? '');

                if (nextQuery === this.search.query) {
                    return;
                }

                this.search.query = nextQuery;
                this.queueSearchResultsUpdate();
            };
            this._searchModalInputSyncElement = inputElement;
            this._searchModalInputSyncElement.addEventListener(
                'input',
                this._onSearchModalInputSync,
            );

            const nextQuery = String(inputElement.value ?? '');

            if (nextQuery !== this.search.query) {
                this.search.query = nextQuery;
            }

            return true;
        },

        jumpPageInputElement() {
            const inputElement = document.getElementById('quran-reader-page-counter-input');

            if (inputElement instanceof HTMLInputElement && inputElement.isConnected) {
                return inputElement;
            }

            return null;
        },

        isJumpPageInputVisible() {
            const inputElement = this.jumpPageInputElement();

            if (!(inputElement instanceof HTMLInputElement)) {
                return false;
            }

            const modalElement = inputElement.closest('.fi-modal');

            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }

            const styles = window.getComputedStyle(inputElement);

            return (
                inputElement.clientHeight > 8 &&
                inputElement.clientWidth > 8 &&
                styles.display !== 'none' &&
                styles.visibility !== 'hidden'
            );
        },

        queueJumpPageModalInputSync({ shouldSelect = true } = {}) {
            let didSyncOnce = false;

            [0, 30, 80, 170].forEach((delayMs) => {
                window.setTimeout(() => {
                    if (didSyncOnce) {
                        return;
                    }

                    const didSync = this.syncJumpPageModalInputValue({
                        shouldSelect,
                    });

                    if (!didSync) {
                        return;
                    }

                    didSyncOnce = true;

                    if (!this.jumpPageModalOpen) {
                        this.jumpPageModalOpen = true;
                        this.dispatchManagerModalsVisibilityState();
                    }
                }, delayMs);
            });
        },

        syncJumpPageModalInputValue({ shouldSelect = true } = {}) {
            const inputElement = this.jumpPageInputElement();

            if (!(inputElement instanceof HTMLInputElement) || !this.isJumpPageInputVisible()) {
                return false;
            }

            const normalizedPageNumber = this.clampPage(this.pageNumber, this.maxPage);
            const nextValue = String(normalizedPageNumber);

            if (inputElement.value !== nextValue) {
                inputElement.value = nextValue;
                inputElement.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (shouldSelect) {
                inputElement.focus();
                inputElement.select();
            }

            return true;
        },

        searchModalWindowElement() {
            const candidates = [];
            const isVisible = (element) => {
                if (!(element instanceof HTMLElement) || !element.isConnected) {
                    return false;
                }

                const styles = window.getComputedStyle(element);

                return (
                    element.clientHeight > 16 &&
                    element.clientWidth > 16 &&
                    styles.display !== 'none' &&
                    styles.visibility !== 'hidden'
                );
            };

            const modalWindowFromElement = (element) => {
                if (!(element instanceof Element)) {
                    return null;
                }

                if (element.classList.contains('fi-modal-window')) {
                    return element;
                }

                const nestedWindow = element.querySelector('.fi-modal-window');

                return nestedWindow instanceof Element ? nestedWindow : null;
            };

            const pushCandidate = (element) => {
                if (!(element instanceof Element)) {
                    return;
                }

                candidates.push(element);
            };

            const searchInput = this.searchModalInputElement();

            if (searchInput instanceof HTMLElement) {
                const fromInput = searchInput.closest('.fi-modal-window');
                pushCandidate(fromInput);
            }

            [this.searchActionModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '')
                .forEach((actionModalId) => {
                    const escapedId = window.CSS?.escape
                        ? window.CSS.escape(actionModalId)
                        : actionModalId;
                    const actionModalCandidates = [
                        ...document.querySelectorAll(`#${escapedId}`),
                        ...document.querySelectorAll(`[data-fi-modal-id="${escapedId}"]`),
                    ];

                    actionModalCandidates.forEach((modalElement) => {
                        pushCandidate(modalWindowFromElement(modalElement));
                    });
                });

            [this.searchModalDomId, this.searchModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '')
                .forEach((modalWindowId) => {
                    const escapedId = window.CSS?.escape
                        ? window.CSS.escape(modalWindowId)
                        : modalWindowId;

                    document.querySelectorAll(`#${escapedId}`).forEach((element) => {
                        pushCandidate(modalWindowFromElement(element));
                    });
                });

            const uniqueCandidates = Array.from(new Set(candidates)).filter(
                (element) => element instanceof HTMLElement && element.isConnected,
            );

            if (uniqueCandidates.length === 0) {
                return null;
            }

            const visibleCandidates = uniqueCandidates.filter((element) => isVisible(element));

            if (visibleCandidates.length > 0) {
                const rankedVisibleCandidates = visibleCandidates
                    .map((element) => {
                        const modalElement = element.closest('.fi-modal');
                        const modalStyles =
                            modalElement instanceof HTMLElement
                                ? window.getComputedStyle(modalElement)
                                : null;
                        const modalZIndex = Number(modalStyles?.zIndex ?? '0');
                        const isOpenModal =
                            modalElement instanceof HTMLElement
                                ? modalElement.classList.contains('fi-modal-open')
                                : false;

                        return {
                            element,
                            zIndex: Number.isFinite(modalZIndex) ? modalZIndex : 0,
                            isOpenModal,
                        };
                    })
                    .sort(
                        (left, right) =>
                            Number(right.isOpenModal) - Number(left.isOpenModal) ||
                            right.zIndex - left.zIndex,
                    );

                return rankedVisibleCandidates[0]?.element ?? null;
            }

            return uniqueCandidates[0] ?? null;
        },

        resolveSearchModalCloseTargetId() {
            const modalWindowElement = this.searchModalWindowElement();

            if (modalWindowElement instanceof Element) {
                const modalElement = modalWindowElement.closest('.fi-modal');

                if (modalElement instanceof HTMLElement) {
                    const dataModalId = String(modalElement.dataset?.fiModalId ?? '').trim();

                    if (dataModalId !== '') {
                        this.searchActionModalId = dataModalId;

                        return dataModalId;
                    }

                    const elementId = String(modalElement.id ?? '').trim();

                    if (elementId !== '') {
                        this.searchActionModalId = elementId;

                        return elementId;
                    }
                }
            }

            const knownModalId = [
                this.searchActionModalId,
                this.searchModalId,
                this.searchModalDomId,
            ]
                .map((value) => String(value ?? '').trim())
                .find((value) => value !== '');

            if (knownModalId) {
                return knownModalId;
            }

            return '';
        },

        searchStreamTargetElement() {
            if (this.$refs.searchResultsStream instanceof Element) {
                return this.$refs.searchResultsStream;
            }

            const modalWindow = this.searchModalWindowElement();

            if (!(modalWindow instanceof Element)) {
                return null;
            }

            return modalWindow.querySelector('[data-quran-search-stream-target]');
        },

        clearSearchStreamTarget() {
            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element)) {
                this._lastSearchStreamPayloadRaw = '';

                return;
            }

            target.textContent = '';
            this._lastSearchStreamPayloadRaw = '';
            this._lastSearchStreamPayloadOffset = 0;
            this._searchStreamFrameRemainder = '';
        },

        teardownSearchStreamObserver() {
            if (this._searchStreamObserver) {
                this._searchStreamObserver.disconnect();
                this._searchStreamObserver = null;
            }
        },

        setupSearchStreamObserver() {
            this.teardownSearchStreamObserver();

            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element) || typeof MutationObserver === 'undefined') {
                return;
            }

            this._searchStreamObserver = new MutationObserver(() => {
                this.consumeSearchStreamPayload();
            });

            this._searchStreamObserver.observe(target, {
                childList: true,
                subtree: true,
                characterData: true,
            });
        },

        consumeSearchStreamPayload() {
            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element)) {
                return;
            }

            const streamText = String(target.textContent ?? '');
            const unreadPayload = streamText.slice(this._lastSearchStreamPayloadOffset);

            if (unreadPayload === '') {
                return;
            }

            this._lastSearchStreamPayloadOffset = streamText.length;

            const framedPayload = `${this._searchStreamFrameRemainder}${unreadPayload}`;
            const frames = framedPayload.split(quranSearchStreamFrameDelimiter);
            this._searchStreamFrameRemainder = frames.pop() ?? '';

            frames.forEach((frame) => {
                const rawPayload = String(frame ?? '').trim();

                if (rawPayload === '' || rawPayload === this._lastSearchStreamPayloadRaw) {
                    return;
                }

                this._lastSearchStreamPayloadRaw = rawPayload;

                let payload = null;

                try {
                    payload = JSON.parse(rawPayload);
                } catch (_) {
                    try {
                        payload = JSON.parse(this.decodeSearchStreamPayload(rawPayload));
                    } catch (__) {
                        return;
                    }
                }

                this.applySearchStreamPayload(payload);
            });
        },

        decodeSearchStreamPayload(rawPayload) {
            if (typeof document === 'undefined') {
                return String(rawPayload ?? '');
            }

            const parser = document.createElement('textarea');
            parser.innerHTML = String(rawPayload ?? '');

            return parser.value;
        },

        searchResultKey(result) {
            const id = Math.max(0, Math.trunc(Number(result?.id ?? 0)));

            if (id > 0) {
                return `id:${id}`;
            }

            return `fallback:${Math.max(0, Math.trunc(Number(result?.surah_number ?? 0)))}:${Math.max(0, Math.trunc(Number(result?.ayah_number ?? 0)))}:${Math.max(0, Math.trunc(Number(result?.page_number ?? 0)))}:${Math.max(0, Math.trunc(Number(result?.match_rank ?? 0)))}`;
        },

        searchResultIsLeaving(result) {
            return Boolean(result?.__leaving) || this._searchNavigationInFlight;
        },

        activeSearchResults() {
            return (Array.isArray(this.search.results) ? this.search.results : []).filter(
                (result) => !this.searchResultIsLeaving(result),
            );
        },

        searchResultStrategy(result) {
            return String(result?.match_strategy ?? '')
                .trim()
                .toLowerCase();
        },

        isSurahNameSearchResult(result) {
            const strategy = this.searchResultStrategy(result);

            return strategy === 'surah_exact' || strategy === 'surah_stem';
        },

        searchResultSortWeight(result) {
            const strategy = this.searchResultStrategy(result);

            if (strategy === 'surah_exact') {
                return 0;
            }

            if (strategy === 'surah_stem') {
                return 1;
            }

            if (strategy === 'exact_phrase') {
                return 2;
            }

            if (strategy === 'exact_tokens') {
                return 3;
            }

            if (strategy === 'stem_tokens') {
                return 4;
            }

            if (strategy === 'root_tokens') {
                return 5;
            }

            if (strategy === 'word_prefix') {
                return 6;
            }

            return 7;
        },

        normalizeSearchResults(nextResults = []) {
            return (Array.isArray(nextResults) ? nextResults : [])
                .map((result) => {
                    if (!result || typeof result !== 'object') {
                        return null;
                    }

                    const key = this.searchResultKey(result);

                    return {
                        ...result,
                        __key: key,
                        __leaving: false,
                    };
                })
                .filter((result) => result !== null)
                .sort((left, right) => {
                    const weightDelta =
                        this.searchResultSortWeight(left) - this.searchResultSortWeight(right);

                    if (weightDelta !== 0) {
                        return weightDelta;
                    }

                    const rankDelta =
                        Math.max(0, Math.trunc(Number(left?.match_rank ?? 0))) -
                        Math.max(0, Math.trunc(Number(right?.match_rank ?? 0)));

                    if (rankDelta !== 0) {
                        return rankDelta;
                    }

                    const surahDelta =
                        Math.max(1, Math.trunc(Number(left?.surah_number ?? 1))) -
                        Math.max(1, Math.trunc(Number(right?.surah_number ?? 1)));

                    if (surahDelta !== 0) {
                        return surahDelta;
                    }

                    return (
                        Math.max(0, Math.trunc(Number(left?.ayah_number ?? 0))) -
                        Math.max(0, Math.trunc(Number(right?.ayah_number ?? 0)))
                    );
                })
                .slice(0, 24);
        },

        mergeSearchResults(existingResults, incomingResults) {
            const mergedByKey = new Map();

            this.normalizeSearchResults(existingResults).forEach((result) => {
                mergedByKey.set(result.__key, result);
            });

            this.normalizeSearchResults(incomingResults).forEach((result) => {
                const previous = mergedByKey.get(result.__key) ?? {};
                mergedByKey.set(result.__key, {
                    ...previous,
                    ...result,
                    __key: result.__key,
                    __leaving: false,
                });
            });

            return Array.from(mergedByKey.values()).slice(0, 24);
        },
    };
};
