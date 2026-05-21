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
        usesFilamentNativeSearchSelect() {
            const selectElement = this.searchResultsSelectElement();
            const attribute = String(selectElement?.dataset?.quranSearchNative ?? '').trim();

            return attribute === 'true';
        },

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
            const selectInstance = this.searchResultsSelectInstance();

            if (!(inputElement instanceof HTMLInputElement) && !selectInstance) {
                return false;
            }

            if (
                this._searchModalInputSyncElement === inputElement &&
                this._searchModalTypeSyncInstance === selectInstance &&
                typeof this._onSearchModalInputSync === 'function' &&
                typeof this._onSearchModalTypeSync === 'function'
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
            if (inputElement instanceof HTMLInputElement) {
                this._searchModalInputSyncElement = inputElement;
                this._searchModalInputSyncElement.addEventListener(
                    'input',
                    this._onSearchModalInputSync,
                );
            }

            this._onSearchModalTypeSync = (value) => {
                const nextQuery = String(value ?? '');

                if (nextQuery === this.search.query) {
                    return;
                }

                this.search.query = nextQuery;
                this.queueSearchResultsUpdate();
            };

            if (
                selectInstance &&
                typeof selectInstance.on === 'function' &&
                typeof this._onSearchModalTypeSync === 'function'
            ) {
                selectInstance.on('type', this._onSearchModalTypeSync);
                this._searchModalTypeSyncInstance = selectInstance;
            }

            const nextQuery = String(
                inputElement instanceof HTMLInputElement ? (inputElement.value ?? '') : '',
            );

            if (nextQuery !== this.search.query) {
                this.search.query = nextQuery;
            }

            return true;
        },

        queueSearchModalInputSyncBinding() {
            if (this.usesFilamentNativeSearchSelect()) {
                return;
            }

            if (typeof this.clearSearchModalInputSyncBindingQueue === 'function') {
                this.clearSearchModalInputSyncBindingQueue();
            }

            const bindingDelaysMs = [0, 40, 120, 260, 520, 900];
            let didBind = false;

            this._searchModalInputSyncBindTimers = bindingDelaysMs.map((delayMs) =>
                window.setTimeout(() => {
                    if (didBind) {
                        return;
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

                    didBind = this.bindSearchModalInputSyncListener();

                    if (!didBind) {
                        return;
                    }

                    this.queueSearchResultsUpdate(0);
                }, delayMs),
            );
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
            if (this.usesFilamentNativeSearchSelect()) {
                return;
            }

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

            return strategy === 'surah_exact' || strategy === 'surah_close';
        },

        searchResultSortWeight(result) {
            const strategy = this.searchResultStrategy(result);

            if (strategy === 'surah_exact') {
                return 0;
            }

            if (strategy === 'surah_close') {
                return 1;
            }

            if (strategy === 'ayah_exact') {
                return 2;
            }

            if (strategy === 'ayah_close') {
                return 3;
            }

            if (strategy === 'ayah_sarf') {
                return 4;
            }

            if (strategy === 'ayah_jathr') {
                return 5;
            }

            return 6;
        },

        searchResultChunkKey(resultOrStrategy = '') {
            const strategy =
                typeof resultOrStrategy === 'string'
                    ? String(resultOrStrategy ?? '')
                          .trim()
                          .toLowerCase()
                    : this.searchResultStrategy(resultOrStrategy);

            if (strategy === 'surah_exact') {
                return 'surah_exact';
            }

            if (strategy === 'surah_close') {
                return 'surah_close';
            }

            if (strategy === 'ayah_exact') {
                return 'ayah_exact';
            }

            if (strategy === 'ayah_close') {
                return 'ayah_close';
            }

            if (strategy === 'ayah_sarf') {
                return 'ayah_sarf';
            }

            if (strategy === 'ayah_jathr') {
                return 'ayah_jathr';
            }

            return 'other';
        },

        searchResultChunkLabel(chunkKey = '') {
            const normalizedChunkKey = String(chunkKey ?? '')
                .trim()
                .toLowerCase();

            if (normalizedChunkKey === 'surah_exact') {
                return 'السور المطابقة';
            }

            if (normalizedChunkKey === 'surah_close') {
                return 'السور القريبة';
            }

            if (normalizedChunkKey === 'ayah_exact') {
                return 'الآيات المطابقة';
            }

            if (normalizedChunkKey === 'ayah_close') {
                return 'الآيات القريبة';
            }

            if (normalizedChunkKey === 'ayah_sarf') {
                return 'الآيات الصرفية';
            }

            if (normalizedChunkKey === 'ayah_jathr') {
                return 'الآيات الجذرية';
            }

            return 'نتائج أخرى';
        },

        searchResultChunks() {
            const chunkOrder = [
                'surah_exact',
                'surah_close',
                'ayah_exact',
                'ayah_close',
                'ayah_sarf',
                'ayah_jathr',
                'other',
            ];
            const groupedResults = new Map(
                chunkOrder.map((chunkKey) => [
                    chunkKey,
                    {
                        key: chunkKey,
                        label: this.searchResultChunkLabel(chunkKey),
                        results: [],
                    },
                ]),
            );

            this.activeSearchResults().forEach((result) => {
                const chunkKey = this.searchResultChunkKey(result);
                const chunk = groupedResults.get(chunkKey) ?? groupedResults.get('other');

                if (!chunk) {
                    return;
                }

                chunk.results.push(result);
            });

            return chunkOrder
                .map((chunkKey) => groupedResults.get(chunkKey))
                .filter((chunk) => chunk && chunk.results.length > 0);
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

        searchResultsSelectElement() {
            const directElement = document.getElementById('quran-reader-search-select');

            if (directElement instanceof HTMLSelectElement && directElement.isConnected) {
                return directElement;
            }

            const fallbackElement = document.querySelector(
                '#quran-reader-search-modal select[name$=\"search_result_key\"]',
            );

            return fallbackElement instanceof HTMLSelectElement ? fallbackElement : null;
        },

        searchResultsSelectInstance() {
            const selectElement = this.searchResultsSelectElement();

            if (!selectElement) {
                return null;
            }

            const instance = selectElement?.tomselect ?? null;

            if (!instance || typeof instance.addOption !== 'function') {
                return null;
            }

            return instance;
        },

        searchResultGroupLabel(strategy = '') {
            return this.searchResultChunkLabel(this.searchResultChunkKey(strategy));
        },

        encodeFilamentSearchSelectionPayload(result, query = '') {
            const payload = {
                verse_id: Math.max(0, Math.trunc(Number(result?.id ?? 0))),
                page_number: Math.max(1, Math.trunc(Number(result?.page_number ?? 1))),
                surah_number: Math.max(1, Math.trunc(Number(result?.surah_number ?? 1))),
                ayah_number: Math.max(0, Math.trunc(Number(result?.ayah_number ?? 0))),
                highlight_ayah_index: Math.max(0, Math.trunc(Number(result?.ayah_index ?? 0))),
                query: String(query ?? '').trim() || null,
            };

            const json = JSON.stringify(payload);
            const base64 = btoa(unescape(encodeURIComponent(json)));

            return base64.replace(/\+/g, '-').replace(/\//g, '_').replace(/=+$/g, '');
        },

        searchResultOptionHtml(result) {
            const escapeHtml = (value) =>
                String(value ?? '').replace(
                    /[&<>"']/g,
                    (character) =>
                        ({
                            '&': '&amp;',
                            '<': '&lt;',
                            '>': '&gt;',
                            '"': '&quot;',
                            "'": '&#39;',
                        })[character] ?? character,
                );
            const surahNumber = Math.max(1, Math.trunc(Number(result?.surah_number ?? 1)));
            const ayahNumber = Math.max(0, Math.trunc(Number(result?.ayah_number ?? 0)));
            const pageNumber = Math.max(1, Math.trunc(Number(result?.page_number ?? 1)));
            const strategy = String(result?.match_strategy ?? '')
                .trim()
                .toLowerCase();
            const matchTone = escapeHtml(this.searchMatchTone(result));
            const label = escapeHtml(this.searchMatchLabel(result));
            const meta = strategy.startsWith('surah_')
                ? `سورة ${surahNumber} · صفحة ${pageNumber}`
                : `سورة ${surahNumber} · آية ${Math.max(1, ayahNumber)}`;
            const ayahText = escapeHtml(this.searchResultAyahText(result));

            return `<div class="quran-search-option-card" data-match-tone="${matchTone}"><span class="quran-search-option-card__meta">${escapeHtml(meta)}</span><span class="quran-search-option-card__ayah font-quran">${ayahText}</span><span class="quran-search-option-card__badge" data-match-tone="${matchTone}">${label}</span></div>`;
        },

        syncFilamentSearchSelectOptionsFromResults() {
            const instance = this.searchResultsSelectInstance();

            if (!instance) {
                return;
            }

            const activeResults = this.activeSearchResults();
            const query = this.normalizeSearchQuery(this.search.query);
            const previousValue = instance.getValue?.() ?? '';
            const optionRecords = [];
            const groupLabels = new Map();

            activeResults.forEach((result) => {
                const value = this.encodeFilamentSearchSelectionPayload(result, query);
                const groupLabel = this.searchResultGroupLabel(result?.match_strategy);
                const groupKey = groupLabel;
                groupLabels.set(groupKey, groupLabel);
                optionRecords.push({
                    value,
                    text: this.searchResultOptionHtml(result),
                    optgroup: groupKey,
                });
            });

            instance.clearOptions();

            if (typeof instance.clearOptionGroups === 'function') {
                instance.clearOptionGroups();
            }

            groupLabels.forEach((label, value) => {
                if (typeof instance.addOptionGroup === 'function') {
                    instance.addOptionGroup(value, { label });
                }
            });

            optionRecords.forEach((record) => {
                instance.addOption(record);
            });

            const hasPreviousOption = optionRecords.some(
                (record) => record.value === previousValue,
            );

            if (hasPreviousOption && previousValue) {
                instance.setValue(previousValue, true);
            } else {
                instance.clear(true);
            }

            instance.refreshOptions(false);
        },
    };
};
