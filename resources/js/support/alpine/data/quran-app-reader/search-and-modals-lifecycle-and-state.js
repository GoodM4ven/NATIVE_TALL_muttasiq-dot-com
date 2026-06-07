export const createSearchAndModalsLifecycleAndStateModule = (deps) => {
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
        traceSearchModalLifecycle(eventName, details = {}) {
            if (typeof this.traceReaderReveal !== 'function') {
                return;
            }

            const normalizedEventName = String(eventName ?? '').trim() || 'event';
            const payload =
                details && typeof details === 'object' && !Array.isArray(details) ? details : {};

            this.traceReaderReveal(`search-modal:${normalizedEventName}`, {
                searchModalOpen: Boolean(this.search?.modalOpen),
                searchNavigationInFlight: Boolean(this._searchNavigationInFlight),
                searchRequestInFlight: Boolean(this._searchRequestInFlight),
                searchResultsCount: Array.isArray(this.search?.results)
                    ? this.search.results.length
                    : 0,
                ...payload,
            });
        },

        syncSearchResultMetadata(results = []) {
            const activeResults = (Array.isArray(results) ? results : []).filter(
                (result) => !this.searchResultIsLeaving(result),
            );

            this.search.isOpen = (Array.isArray(results) ? results : []).length > 0;
            this.search.readyResult = activeResults.length === 1 ? activeResults[0] : null;
        },

        queueSearchLeaveCleanup() {
            if (this._searchResultsLeaveTimer !== null) {
                clearTimeout(this._searchResultsLeaveTimer);
                this._searchResultsLeaveTimer = null;
            }

            const hasLeavingResults = (
                Array.isArray(this.search.results) ? this.search.results : []
            ).some((result) => this.searchResultIsLeaving(result));

            if (!hasLeavingResults) {
                return;
            }

            this._searchResultsLeaveTimer = window.setTimeout(() => {
                this.search.results = (
                    Array.isArray(this.search.results) ? this.search.results : []
                ).filter((result) => !this.searchResultIsLeaving(result));
                this.syncSearchResultMetadata(this.search.results);
                if (typeof this.syncFilamentSearchSelectOptionsFromResults === 'function') {
                    this.syncFilamentSearchSelectOptionsFromResults();
                }
                this._searchResultsLeaveTimer = null;
            }, 260);
        },

        setSearchResults(nextResults, { immediate = false } = {}) {
            const normalizedNextResults = this.normalizeSearchResults(nextResults);

            if (immediate) {
                if (this._searchResultsLeaveTimer !== null) {
                    clearTimeout(this._searchResultsLeaveTimer);
                    this._searchResultsLeaveTimer = null;
                }

                this.search.results = normalizedNextResults;
                this.syncSearchResultMetadata(this.search.results);
                if (typeof this.syncFilamentSearchSelectOptionsFromResults === 'function') {
                    this.syncFilamentSearchSelectOptionsFromResults();
                }

                return;
            }

            const currentResults = Array.isArray(this.search.results) ? this.search.results : [];
            const nextByKey = new Map(
                normalizedNextResults.map((result) => [result.__key, result]),
            );
            const composedResults = [];
            const usedKeys = new Set();

            currentResults.forEach((result) => {
                const resultKey = this.searchResultKey(result);
                const nextMatch = nextByKey.get(resultKey);

                if (nextMatch) {
                    composedResults.push({
                        ...result,
                        ...nextMatch,
                        __key: resultKey,
                        __leaving: false,
                    });
                    usedKeys.add(resultKey);

                    return;
                }

                if (this.searchResultIsLeaving(result)) {
                    composedResults.push(result);

                    return;
                }

                composedResults.push({
                    ...result,
                    __key: resultKey,
                    __leaving: true,
                });
            });

            normalizedNextResults.forEach((result) => {
                if (usedKeys.has(result.__key)) {
                    return;
                }

                composedResults.push(result);
            });

            composedResults.sort((left, right) => {
                const leftLeaving = this.searchResultIsLeaving(left);
                const rightLeaving = this.searchResultIsLeaving(right);

                if (leftLeaving !== rightLeaving) {
                    return leftLeaving ? 1 : -1;
                }

                return this.searchResultSortWeight(left) - this.searchResultSortWeight(right);
            });

            this.search.results = composedResults;
            this.syncSearchResultMetadata(this.search.results);
            if (typeof this.syncFilamentSearchSelectOptionsFromResults === 'function') {
                this.syncFilamentSearchSelectOptionsFromResults();
            }
            this.queueSearchLeaveCleanup();
        },

        applySearchStreamPayload(payload) {
            const requestSerial = Math.max(0, Math.trunc(Number(payload?.request_serial ?? 0)));

            if (requestSerial < 1) {
                return;
            }

            if (requestSerial !== this._searchRequestSerial) {
                if (!this.search.modalOpen) {
                    return;
                }

                if (requestSerial > this._searchRequestSerial || !this._searchRequestInFlight) {
                    this._searchRequestSerial = requestSerial;
                } else {
                    return;
                }
            }

            const stage = String(payload?.stage ?? '').trim();
            const stageResults = Array.isArray(payload?.stage_items) ? payload.stage_items : [];
            const allResults = Array.isArray(payload?.items) ? payload.items : [];
            const hasStreamChunk = stageResults.length > 0;

            if (hasStreamChunk && stage !== 'complete') {
                this.search.streamHasUpdates = true;
                this.setSearchResults(
                    this.mergeSearchResults(this.activeSearchResults(), stageResults),
                );
            } else if (allResults.length > 0) {
                if (!this.search.streamHasUpdates || stage === 'start') {
                    this.setSearchResults(allResults);
                } else {
                    this.syncSearchResultMetadata(this.search.results);

                    if (typeof this.syncFilamentSearchSelectOptionsFromResults === 'function') {
                        this.syncFilamentSearchSelectOptionsFromResults();
                    }

                    this.queueSearchLeaveCleanup();
                }
            } else if (!this.search.streamHasUpdates) {
                this.setSearchResults([], { immediate: true });
            }

            if (typeof payload?.is_loading === 'boolean') {
                this.search.isLoading = payload.is_loading;
            }

            this.$nextTick(() => {
                this.ensureSearchResultAnimations();
                if (typeof this.syncFilamentSearchSelectOptionsFromResults === 'function') {
                    this.syncFilamentSearchSelectOptionsFromResults();
                }
            });
        },

        modalWindowElementById(modalId) {
            const normalizedModalId = String(modalId ?? '').trim();

            if (!normalizedModalId) {
                return null;
            }

            const resolveModalWindowFromElement = (element) => {
                if (!(element instanceof Element)) {
                    return null;
                }

                if (element.classList.contains('fi-modal-window')) {
                    return element;
                }

                const nestedModalWindow = element.querySelector('.fi-modal-window');

                return nestedModalWindow instanceof Element ? nestedModalWindow : null;
            };

            const directElement = document.getElementById(normalizedModalId);
            const directModalWindow = resolveModalWindowFromElement(directElement);

            if (directModalWindow) {
                return directModalWindow;
            }

            const escapedId = window.CSS?.escape
                ? window.CSS.escape(normalizedModalId)
                : normalizedModalId;
            const modalByDataId = document.querySelector(`[data-fi-modal-id="${escapedId}"]`);
            const modalWindowFromDataId = resolveModalWindowFromElement(modalByDataId);

            if (modalWindowFromDataId) {
                return modalWindowFromDataId;
            }

            return null;
        },

        isModalWindowVisibleById(modalId) {
            const modalWindowElement = this.modalWindowElementById(modalId);

            if (!(modalWindowElement instanceof Element)) {
                return false;
            }

            const modalElement = modalWindowElement.closest('.fi-modal');

            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }

            const styles = window.getComputedStyle(modalWindowElement);

            return styles.display !== 'none' && styles.visibility !== 'hidden';
        },

        isSearchModalWindowVisible() {
            const modalWindowElement = this.searchModalWindowElement();

            if (!(modalWindowElement instanceof Element)) {
                return false;
            }

            const modalElement = modalWindowElement.closest('.fi-modal');

            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }

            const styles = window.getComputedStyle(modalWindowElement);

            return styles.display !== 'none' && styles.visibility !== 'hidden';
        },

        isSearchModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.searchModalId, this.searchModalDomId, this.searchActionModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);
            const searchOpenRequestAgeMs =
                Date.now() - Number(this._searchModalOpenRequestedAt ?? 0);
            const hasRecentSearchOpenRequest =
                Number.isFinite(searchOpenRequestAgeMs) &&
                searchOpenRequestAgeMs >= 0 &&
                searchOpenRequestAgeMs <= 2600;
            const resolveModalIdFromWindowElement = (modalWindowElement) => {
                if (!(modalWindowElement instanceof Element)) {
                    return '';
                }

                const modalElement = modalWindowElement.closest('.fi-modal');
                const dataModalId = String(modalElement?.dataset?.fiModalId ?? '').trim();

                if (dataModalId !== '') {
                    return dataModalId;
                }

                return String(modalElement?.id ?? '').trim();
            };

            const modalElementContainsSearchFields = () => {
                if (modalId === '') {
                    return false;
                }

                const modalWindowElement = this.modalWindowElementById(modalId);

                if (!(modalWindowElement instanceof Element)) {
                    return false;
                }

                return Boolean(
                    modalWindowElement.querySelector('#quran-reader-search-input') ||
                    modalWindowElement.querySelector('[data-quran-search-stream-target]') ||
                    modalWindowElement.querySelector('[data-quran-surah-grid]'),
                );
            };
            const findAnySearchModalWindowElement = () => {
                const openModalSearchInput = document.querySelector(
                    '.fi-modal.fi-modal-open #quran-reader-search-input, .fi-modal.fi-modal-open [data-quran-search-stream-target]',
                );

                if (openModalSearchInput instanceof Element) {
                    return openModalSearchInput.closest('.fi-modal-window');
                }

                const anyModalSearchInput = document.querySelector(
                    '#quran-reader-search-input, [data-quran-search-stream-target]',
                );

                if (anyModalSearchInput instanceof Element) {
                    return anyModalSearchInput.closest('.fi-modal-window');
                }

                return null;
            };

            if (kind === 'opened') {
                if (matchedKnownId) {
                    this.searchActionModalId = modalId;

                    return true;
                }

                if (modalElementContainsSearchFields()) {
                    this.searchActionModalId = modalId;

                    return true;
                }

                const searchWindowElement = findAnySearchModalWindowElement();
                const searchWindowModalId = resolveModalIdFromWindowElement(searchWindowElement);

                if (searchWindowModalId !== '') {
                    this.searchActionModalId = searchWindowModalId;

                    return true;
                }

                if (hasRecentSearchOpenRequest && modalId !== '') {
                    this.searchActionModalId = modalId;

                    return true;
                }

                const isVisible = this.isSearchModalWindowVisible();

                if (isVisible && modalId !== '') {
                    this.searchActionModalId = modalId;
                }

                return isVisible;
            }

            if (matchedKnownId) {
                return true;
            }

            if (modalElementContainsSearchFields()) {
                this.searchActionModalId = modalId;

                return true;
            }

            const searchWindowElement = findAnySearchModalWindowElement();
            const searchWindowModalId = resolveModalIdFromWindowElement(searchWindowElement);

            if (searchWindowModalId !== '') {
                this.searchActionModalId = searchWindowModalId;

                return true;
            }

            if (
                hasRecentSearchOpenRequest &&
                modalId !== '' &&
                (kind === 'opening' || kind === 'opened')
            ) {
                this.searchActionModalId = modalId;

                return true;
            }

            if (modalId === '') {
                return this.search.modalOpen || this._lastKnownModalOpenState;
            }

            return this.search.modalOpen || this._lastKnownModalOpenState;
        },

        isHistoryModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.historyModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);
            const historyOpenRequestAgeMs =
                Date.now() - Number(this._historyModalOpenRequestedAt ?? 0);
            const hasRecentHistoryOpenRequest =
                Number.isFinite(historyOpenRequestAgeMs) &&
                historyOpenRequestAgeMs >= 0 &&
                historyOpenRequestAgeMs <= 2000;

            if (matchedKnownId) {
                return true;
            }

            if (kind === 'opened') {
                return this.isModalWindowVisibleById(this.historyModalId);
            }

            return this.historyModalOpen || hasRecentHistoryOpenRequest;
        },

        isBookmarksModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.bookmarksModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);
            const bookmarksOpenRequestAgeMs =
                Date.now() - Number(this._bookmarksModalOpenRequestedAt ?? 0);
            const hasRecentBookmarksOpenRequest =
                Number.isFinite(bookmarksOpenRequestAgeMs) &&
                bookmarksOpenRequestAgeMs >= 0 &&
                bookmarksOpenRequestAgeMs <= 2000;

            if (matchedKnownId) {
                return true;
            }

            if (kind === 'opened') {
                return this.isModalWindowVisibleById(this.bookmarksModalId);
            }

            return this.bookmarksModalOpen || hasRecentBookmarksOpenRequest;
        },

        queueSmPlusWebModalCloseRefit(modalId = '') {
            const canQueuePostCloseTargetFit =
                typeof this.schedulePendingModalCloseFit === 'function' &&
                typeof this.hasRenderablePage === 'function';

            if (canQueuePostCloseTargetFit && this.hasRenderablePage()) {
                const targetPage = clampPage(Number(this.pageNumber ?? 0), this.maxPage);

                if (targetPage > 0) {
                    this.schedulePendingModalCloseFit(targetPage, {
                        retries: this.nativeRuntime ? 44 : 30,
                        delayMs: this.nativeRuntime ? 108 : 90,
                        revealDelayMs: this.nativeRuntime ? 240 : 220,
                        maxAttempts: this.nativeRuntime ? 6 : 5,
                        forceRefit: true,
                    });
                }
            }

            const shouldRefitAfterModalClose =
                !this.nativeRuntime &&
                !this.shouldUseImmersiveReaderChrome() &&
                typeof this.queueReaderReentryRefit === 'function';

            if (!shouldRefitAfterModalClose) {
                return;
            }

            // Modal transitions can leave stale fit snapshots on sm+ web.
            this.queueReaderReentryRefit(72, 5);
            window.setTimeout(() => this.queueReaderReentryRefit(180, 3), 120);
            window.setTimeout(() => this.queueReaderReentryRefit(300, 2), 240);
            window.setTimeout(() => this.queueReaderReentryRefit(460, 2), 420);
            window.setTimeout(() => this.queueReaderReentryRefit(620, 1), 620);

            const canRunForcedLayoutRecovery =
                typeof this.scheduleLayout === 'function' &&
                typeof this.clearLayoutTimers === 'function' &&
                typeof this.hasRenderablePage === 'function';

            if (canRunForcedLayoutRecovery) {
                window.setTimeout(() => {
                    if (this.openModalCount() > 0 || this._modalNavigationCloseGuardActive) {
                        return;
                    }

                    if (!this.hasRenderablePage()) {
                        return;
                    }

                    this._bypassNextFitCache = true;
                    this.isFittingPage = true;
                    this.clearLayoutTimers();
                    this.scheduleLayout({ revealDelayMs: 170, maxAttempts: 5 });
                }, 280);

                window.setTimeout(() => {
                    if (this.openModalCount() > 0 || this._modalNavigationCloseGuardActive) {
                        return;
                    }

                    if (!this.hasRenderablePage()) {
                        return;
                    }

                    this._bypassNextFitCache = true;
                    this.isFittingPage = true;
                    this.clearLayoutTimers();
                    this.scheduleLayout({ revealDelayMs: 190, maxAttempts: 6 });
                }, 620);
            }

            const normalizedModalId = String(modalId ?? '').trim();
            const canRunSecondaryRecoveryPulse =
                normalizedModalId !== '' &&
                typeof this.runSecondaryModalExitRecoveryPulse === 'function';
            const canRunVisibilityRecovery =
                typeof this.ensureModalDrivenPageVisible === 'function' &&
                typeof this.hasRenderablePage === 'function';

            if (canRunSecondaryRecoveryPulse) {
                window.setTimeout(async () => {
                    if (this.openModalCount() > 0 || this._modalNavigationCloseGuardActive) {
                        return;
                    }

                    await this.runSecondaryModalExitRecoveryPulse(normalizedModalId);

                    if (!canRunVisibilityRecovery || !this.hasRenderablePage()) {
                        return;
                    }

                    const targetPage = clampPage(Number(this.pageNumber ?? 0), this.maxPage);

                    if (targetPage <= 0) {
                        return;
                    }

                    await this.ensureModalDrivenPageVisible(targetPage, {
                        revealDelayMs: 170,
                        maxAttempts: 5,
                        fallbackReason: `${normalizedModalId}-post-close-visibility-recovery`,
                    });
                }, 160);
            }
        },

        isJumpPageModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.jumpPageModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);

            if (matchedKnownId) {
                return true;
            }

            if (kind === 'opened') {
                return (
                    this.isModalWindowVisibleById(this.jumpPageModalId) ||
                    this.isJumpPageInputVisible()
                );
            }

            return this.jumpPageModalOpen;
        },

        syncManagerModalFlagsFromVisibility() {
            const searchVisible = this.isSearchModalWindowVisible();
            const historyVisible = this.isModalWindowVisibleById(this.historyModalId);
            const bookmarksVisible = this.isModalWindowVisibleById(this.bookmarksModalId);
            const jumpVisible =
                this.isModalWindowVisibleById(this.jumpPageModalId) ||
                this.isJumpPageInputVisible();

            this.historyModalOpen = historyVisible;
            this.bookmarksModalOpen = bookmarksVisible;
            this.jumpPageModalOpen = jumpVisible;

            if (searchVisible) {
                this.search.modalOpen = true;
                this._lastKnownModalOpenState = true;
            } else if (this.search.modalOpen) {
                this.handleSearchModalClosed();
            } else {
                this._lastKnownModalOpenState = false;
            }

            this._managerModalVersion += 1;
            this.dispatchManagerModalsVisibilityState();
        },

        queueSearchModalCloseSync({ delayMs = 0 } = {}) {
            const normalizedDelayMs = Math.max(0, Math.trunc(Number(delayMs) || 0));

            window.setTimeout(() => {
                this.syncManagerModalFlagsFromVisibility();

                const hasStaleSearchState =
                    this.search.modalOpen ||
                    String(this.search.query ?? '').trim() !== '' ||
                    Number(this.search.results?.length ?? 0) > 0;

                if (!hasStaleSearchState) {
                    return;
                }

                if (!this.isSearchModalWindowVisible()) {
                    this.traceSearchModalLifecycle('closed-by-sync', {
                        delayMs: normalizedDelayMs,
                    });
                    this.handleSearchModalClosed();
                }
            }, normalizedDelayMs);
        },

        suppressModalLifecycleEffects(
            modalIds = [],
            { durationMs = modalLifecycleSuppressionDurationMs } = {},
        ) {
            const normalizedModalIds = (Array.isArray(modalIds) ? modalIds : [modalIds])
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            if (normalizedModalIds.length < 1) {
                return;
            }

            const suppressionDurationMs = Math.max(120, Math.trunc(Number(durationMs) || 0));

            this._suppressModalLifecycleEffectsUntil = Math.max(
                this._suppressModalLifecycleEffectsUntil,
                Date.now() + suppressionDurationMs,
            );

            normalizedModalIds.forEach((modalId) => {
                this._suppressModalLifecycleModalIds.add(modalId);
            });
        },

        pruneModalLifecycleSuppression(now = Date.now()) {
            if (now < this._suppressModalLifecycleEffectsUntil) {
                return;
            }

            this._suppressModalLifecycleEffectsUntil = 0;
            this._suppressModalLifecycleModalIds.clear();
        },

        handleModalLifecycleEvent(kind, event) {
            if (!this.isAnyQuranReaderViewOpen()) {
                this.recoverStaleModalLifecycleState();
                this.pruneModalLifecycleSuppression();
                this.clearPendingPostModalTargetFit();

                return;
            }

            const nativeLifecycleEventName = String(event?.type ?? '').trim();
            const normalizedKind =
                kind === 'opened' && nativeLifecycleEventName === 'open-modal' ? 'opening' : kind;
            const eventModalId = String(event?.detail?.id ?? '').trim();

            this.trackModalLifecycle(normalizedKind, event);
            const isSearchModalEvent = this.isSearchModalEvent(normalizedKind, event);
            const isHistoryModalEvent = this.isHistoryModalEvent(normalizedKind, event);
            const isBookmarksModalEvent = this.isBookmarksModalEvent(normalizedKind, event);
            const isJumpPageModalEvent = this.isJumpPageModalEvent(normalizedKind, event);
            let shouldSyncManagerModalsVisibility = false;

            this.traceSearchModalLifecycle('lifecycle-event', {
                kind: normalizedKind,
                nativeLifecycleEventName,
                modalId: eventModalId,
                isSearchModalEvent,
                isHistoryModalEvent,
                isBookmarksModalEvent,
                isJumpPageModalEvent,
            });

            if (
                normalizedKind === 'opening' ||
                normalizedKind === 'opened' ||
                normalizedKind === 'closing' ||
                normalizedKind === 'closed'
            ) {
                this.$nextTick(() => {
                    this.syncSupportLockTargetsUi();
                });
            }

            if (
                (normalizedKind === 'opening' || normalizedKind === 'opened') &&
                this.searchDestinationCueActive &&
                !this._searchNavigationInFlight
            ) {
                this.deactivateSearchDestinationCue();
            }

            if (normalizedKind === 'opened') {
                this.$nextTick(() => {
                    this.queueJumpPageModalInputSync();
                });
            }

            if (
                (normalizedKind === 'closing' || normalizedKind === 'closed') &&
                !isSearchModalEvent
            ) {
                this.queueSearchModalCloseSync({
                    delayMs: normalizedKind === 'closed' ? 0 : 96,
                });

                window.setTimeout(
                    () => {
                        this.syncManagerModalFlagsFromVisibility();

                        if (this.openModalCount() > 0) {
                            return;
                        }

                        this.recoverStaleModalLifecycleState();
                        this.pruneModalLifecycleSuppression();
                        this.refreshMobileEdgeCaptions(false);
                        this.syncReaderChromeDocumentClass();
                    },
                    normalizedKind === 'closed' ? 0 : 120,
                );
            }

            if (isJumpPageModalEvent) {
                if (normalizedKind === 'opened') {
                    this.jumpPageModalOpen = true;
                    shouldSyncManagerModalsVisibility = true;
                }

                if (normalizedKind === 'closing' || normalizedKind === 'closed') {
                    window.setTimeout(
                        () => {
                            const isStillVisible = this.isJumpPageInputVisible();

                            if (isStillVisible) {
                                this.jumpPageModalOpen = true;

                                return;
                            }

                            this.jumpPageModalOpen = false;
                            this.dispatchManagerModalsVisibilityState();
                        },
                        normalizedKind === 'closed' ? 0 : 48,
                    );
                    shouldSyncManagerModalsVisibility = true;
                }
            }

            if (isHistoryModalEvent) {
                if (normalizedKind === 'opened') {
                    this.historyModalOpen = true;
                    this.$nextTick(() => {
                        this.ensureHistoryRowsAnimations();
                        this.queueHistoryManagerTableSync();
                    });
                }

                if (normalizedKind === 'closing' || normalizedKind === 'closed') {
                    this.clearHistoryManagerSyncQueue();
                }

                if (normalizedKind === 'closed') {
                    this.historyModalOpen = false;
                    this.teardownHistoryRowsAnimations();
                    this.queueSmPlusWebModalCloseRefit(this.historyModalId);
                }

                shouldSyncManagerModalsVisibility = true;
            }

            if (isBookmarksModalEvent) {
                if (normalizedKind === 'opened') {
                    this.bookmarksModalOpen = true;
                    this.$nextTick(() => {
                        this.ensureBookmarksRowsAnimations();
                        this.queueBookmarksManagerTableSync();
                    });
                }

                if (normalizedKind === 'closing' || normalizedKind === 'closed') {
                    this.clearBookmarksManagerSyncQueue();
                }

                if (normalizedKind === 'closed') {
                    this.bookmarksModalOpen = false;
                    this.teardownBookmarksRowsAnimations();
                    this.queueSmPlusWebModalCloseRefit(this.bookmarksModalId);
                }

                shouldSyncManagerModalsVisibility = true;
            }

            if (shouldSyncManagerModalsVisibility) {
                this.dispatchManagerModalsVisibilityState();
            }

            if (normalizedKind === 'closed' && eventModalId !== '' && !isSearchModalEvent) {
                this.queueSmPlusWebModalCloseRefit(eventModalId);
            }

            if (isSearchModalEvent) {
                if (normalizedKind === 'opened') {
                    this._searchModalOpenRequestedAt = 0;
                    void this.handleSearchModalOpened();
                    this.traceSearchModalLifecycle('opened', {
                        handler: 'handleSearchModalOpened',
                    });

                    return;
                }

                if (normalizedKind === 'closing') {
                    this.cancelActiveSearchProcessing();
                    this.queueSearchModalCloseSync({ delayMs: 96 });
                    this.traceSearchModalLifecycle('closing');

                    return;
                }

                if (normalizedKind === 'closed') {
                    this._searchModalOpenRequestedAt = 0;
                    this.cancelActiveSearchProcessing();
                    this.handleSearchModalClosed();
                    this.traceSearchModalLifecycle('closed', {
                        handler: 'handleSearchModalClosed',
                    });
                }
            } else if (
                (normalizedKind === 'closing' || normalizedKind === 'closed') &&
                this.search.modalOpen &&
                !this.isSearchModalWindowVisible()
            ) {
                this.handleSearchModalClosed();
            }

            if (normalizedKind === 'closed' && this.openModalCount() <= 0) {
                this.recoverStaleModalLifecycleState();
                this.pruneModalLifecycleSuppression();
                this.refreshMobileEdgeCaptions(false);
                this.syncReaderChromeDocumentClass();

                if (this._postModalTargetFitPage > 0) {
                    this.schedulePendingModalCloseFit(this._postModalTargetFitPage, {
                        retries: 42,
                        delayMs: 90,
                        revealDelayMs: 230,
                        maxAttempts: 6,
                    });
                }
            }
        },

        shouldDeferSearchModalClose() {
            return false;
        },

        shouldBlockSearchModalCloseEvent(event) {
            return false;
        },
    };
};
