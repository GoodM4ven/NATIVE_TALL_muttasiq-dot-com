export const createReaderNavigationFitIdleWarmupAndScaleControlsModule = (deps) => {
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
        async prefetchFontAsset(payload) {
            const fontUrl = String(payload?.qpcPageFontUrl ?? '').trim();
            const surahHeaderFontUrl = String(payload?.surahHeaderFontUrl ?? '').trim();

            if (fontUrl) {
                await cacheAssetResponse({
                    url: fontUrl,
                    cacheName: this.cacheNames.fonts,
                });
            }

            if (surahHeaderFontUrl) {
                await cacheAssetResponse({
                    url: surahHeaderFontUrl,
                    cacheName: this.cacheNames.fonts,
                });
            }
        },

        cancelIdleWarmupHandle() {
            if (this._idleWarmupHandle === null) {
                return;
            }

            if (
                this._idleWarmupHandleKind === 'idle' &&
                typeof window.cancelIdleCallback === 'function'
            ) {
                window.cancelIdleCallback(this._idleWarmupHandle);
            } else {
                clearTimeout(this._idleWarmupHandle);
            }

            this._idleWarmupHandle = null;
            this._idleWarmupHandleKind = null;
        },

        abortIdleWarmupFetch() {
            if (
                typeof AbortController === 'undefined' ||
                !(this._idleWarmupAbortController instanceof AbortController)
            ) {
                return;
            }

            this._idleWarmupAbortController.abort();
            this._idleWarmupAbortController = null;
        },

        beginIdleWarmupAbortController() {
            if (typeof AbortController === 'undefined') {
                this._idleWarmupAbortController = null;

                return null;
            }

            this.abortIdleWarmupFetch();
            this._idleWarmupAbortController = new AbortController();

            return this._idleWarmupAbortController;
        },

        pauseIdleWarmup(
            durationMs = idleWarmupPauseOnHighFrequencyNavigationMs,
            { preservePage = 0 } = {},
        ) {
            const normalizedPreservePage = Math.max(0, Math.trunc(Number(preservePage) || 0));
            const shouldPreserveInFlightPage =
                normalizedPreservePage > 0 &&
                this._idleWarmupInFlightPage === normalizedPreservePage;

            this._idleWarmupPausedUntil = Math.max(
                this._idleWarmupPausedUntil,
                Date.now() +
                    Math.max(
                        80,
                        Math.trunc(
                            Number(durationMs) || idleWarmupPauseOnHighFrequencyNavigationMs,
                        ),
                    ),
            );

            if (!shouldPreserveInFlightPage) {
                this.abortIdleWarmupFetch();
            }

            this.cancelIdleWarmupHandle();
        },

        canRunIdleWarmup() {
            return (
                Date.now() >= this._idleWarmupPausedUntil &&
                !this.isLoadingPage &&
                !this.isNavigationBurstActive() &&
                this._pendingNavigationRequest === null
            );
        },

        enqueueIdleWarmupPages(pages = [], { prepend = false } = {}) {
            const normalizedPages = (Array.isArray(pages) ? pages : [])
                .map((page) => clampPage(page, this.maxPage))
                .filter((page) => page >= 1 && (this.maxPage < 1 || page <= this.maxPage));

            if (normalizedPages.length === 0) {
                return;
            }

            if (prepend) {
                const uniquePriorityPages = Array.from(new Set(normalizedPages));
                const priorityPageSet = new Set(uniquePriorityPages);

                this._idleWarmupQueue = this._idleWarmupQueue.filter(
                    (queuedPage) => !priorityPageSet.has(queuedPage),
                );
                this._idleWarmupQueue = [...uniquePriorityPages, ...this._idleWarmupQueue];
                uniquePriorityPages.forEach((page) => {
                    this._idleWarmupQueuedPages.add(page);
                });

                return;
            }

            const appendPages = [];
            normalizedPages.forEach((page) => {
                if (this._idleWarmupQueuedPages.has(page)) {
                    return;
                }

                this._idleWarmupQueuedPages.add(page);
                appendPages.push(page);
            });

            if (appendPages.length === 0) {
                return;
            }

            this._idleWarmupQueue.push(...appendPages);
        },

        buildBackgroundWarmupSweep(centerPage = this.pageNumber) {
            const maxPage = Math.max(1, Math.trunc(Number(this.maxPage || 0)));
            const startPage = clampPage(centerPage, maxPage);
            const sweep = [];

            for (let offset = 0; offset < maxPage; offset += 1) {
                const nextPage = startPage + offset;
                const previousPage = startPage - offset;

                if (offset === 0) {
                    sweep.push(startPage);

                    continue;
                }

                if (nextPage >= 1 && nextPage <= maxPage) {
                    sweep.push(nextPage);
                }

                if (previousPage >= 1 && previousPage <= maxPage) {
                    sweep.push(previousPage);
                }
            }

            return sweep;
        },

        scheduleIdleWarmup(delayMs = 0) {
            if (this._idleWarmupQueue.length === 0 || this._idleWarmupHandle !== null) {
                return;
            }

            const normalizedDelay = Math.max(0, Math.trunc(Number(delayMs) || 0));

            if (typeof window.requestIdleCallback === 'function') {
                this._idleWarmupHandleKind = 'idle';
                this._idleWarmupHandle = window.requestIdleCallback(
                    (deadline) => {
                        this._idleWarmupHandle = null;
                        this._idleWarmupHandleKind = null;
                        void this.processIdleWarmupQueue(deadline);
                    },
                    {
                        timeout: Math.max(120, normalizedDelay || idleWarmupResumeDelayMs),
                    },
                );

                return;
            }

            this._idleWarmupHandleKind = 'timeout';
            this._idleWarmupHandle = window.setTimeout(
                () => {
                    this._idleWarmupHandle = null;
                    this._idleWarmupHandleKind = null;
                    void this.processIdleWarmupQueue(null);
                },
                Math.max(60, normalizedDelay || idleWarmupResumeDelayMs),
            );
        },

        async processIdleWarmupQueue(deadline = null) {
            if (this._idleWarmupInFlight) {
                this.scheduleIdleWarmup(idleWarmupResumeDelayMs);

                return;
            }

            if (!this.canRunIdleWarmup()) {
                this.scheduleIdleWarmup(idleWarmupResumeDelayMs);

                return;
            }

            if (
                deadline &&
                typeof deadline.timeRemaining === 'function' &&
                deadline.timeRemaining() < 6
            ) {
                this.scheduleIdleWarmup(80);

                return;
            }

            const nextPage = this._idleWarmupQueue.shift();

            if (!Number.isFinite(Number(nextPage)) || Number(nextPage) < 1) {
                this.scheduleIdleWarmup();

                return;
            }

            this._idleWarmupQueuedPages.delete(nextPage);
            this._idleWarmupInFlight = true;
            this._idleWarmupInFlightPage = Math.max(0, Math.trunc(Number(nextPage) || 0));
            const idleAbortController = this.beginIdleWarmupAbortController();

            try {
                await this.prefetchPage(nextPage, {
                    signal: idleAbortController?.signal ?? null,
                });
            } catch (_) {
                // Ignore idle warmup failures and continue queue progress.
            } finally {
                if (this._idleWarmupAbortController === idleAbortController) {
                    this._idleWarmupAbortController = null;
                }

                this._idleWarmupInFlight = false;
                this._idleWarmupInFlightPage = 0;
            }

            if (this._idleWarmupQueue.length > 0) {
                this.scheduleIdleWarmup(40);
            }
        },

        queueStartupPreload() {
            const pages = [];

            for (let page = 1; page <= this.prewarmPages; page += 1) {
                pages.push(page);
            }

            for (let offset = 1; offset <= this.prefetchRadius; offset += 1) {
                pages.push(this.pageNumber + offset, this.pageNumber - offset);
            }

            const uniquePages = Array.from(
                new Set(
                    pages
                        .map((page) => clampPage(page, this.maxPage))
                        .filter((page) => page >= 1 && (this.maxPage < 1 || page <= this.maxPage)),
                ),
            );

            window.setTimeout(() => {
                this.enqueueIdleWarmupPages(uniquePages, { prepend: true });

                if (!this._idleWarmupHasBackgroundSweepQueued && this.maxPage > 0) {
                    this._idleWarmupHasBackgroundSweepQueued = true;
                    this.enqueueIdleWarmupPages(this.buildBackgroundWarmupSweep(this.pageNumber));
                }

                this.scheduleIdleWarmup();
            }, 40);
        },

        prefetchNeighborPages(pageNumber) {
            const pages = [];

            for (let offset = 1; offset <= this.prefetchRadius; offset += 1) {
                pages.push(pageNumber + offset, pageNumber - offset);
            }

            this.enqueueIdleWarmupPages(pages, { prepend: true });
            this.scheduleIdleWarmup();
        },

        async prefetchPage(pageNumber, { signal = null } = {}) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);

            if (normalizedPage < 1 || (this.maxPage > 0 && normalizedPage > this.maxPage)) {
                return;
            }

            try {
                await this.getPagePayload(normalizedPage, {
                    signal,
                });
            } catch (_) {
                // Ignore background prefetch failures.
            }
        },

        playPageMotion(direction) {
            const nextClass =
                direction === 'prev' ? 'quran-page-motion-prev' : 'quran-page-motion-next';

            if (this.pageMotionTimer !== null) {
                clearTimeout(this.pageMotionTimer);
            }

            this.pageMotionClass = nextClass;
            this.pageMotionTimer = window.setTimeout(() => {
                this.pageMotionClass = '';
                this.pageMotionTimer = null;
            }, 260);
        },

        swipePoint(event) {
            if (event?.touches?.length) {
                const touch = event.touches[0];

                return {
                    x: touch.clientX,
                    y: touch.clientY,
                    pointerType: 'touch',
                    pointerId: null,
                };
            }

            if (event?.changedTouches?.length) {
                const touch = event.changedTouches[0];

                return {
                    x: touch.clientX,
                    y: touch.clientY,
                    pointerType: 'touch',
                    pointerId: null,
                };
            }

            if (Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)) {
                return {
                    x: event.clientX,
                    y: event.clientY,
                    pointerType: event.pointerType ?? 'mouse',
                    pointerId: event.pointerId ?? null,
                };
            }

            return null;
        },

        swipeEventSource(event) {
            return event?.type?.startsWith('touch') ? 'touch' : 'pointer';
        },

        swipeNavigationDirection(deltaX, deltaY) {
            if (Date.now() - this._lastWordHoldAt < 360) {
                return null;
            }

            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);

            if (absX < swipeActivationThresholdPx || absX < absY) {
                return null;
            }

            return deltaX > 0 ? 'next' : 'prev';
        },

        async dispatchSwipeNavigation(direction) {
            this.resetSwipeState();
            this.clearSwipeRevealWatchdog();

            if (direction === 'next') {
                this.traceReaderReveal('dispatch-swipe-navigation', { direction: 'next' });

                if (this.triggerChevronButtonClick('next', 'swipe')) {
                    return true;
                }

                await this.goNextFromChevron('swipe');

                return true;
            }

            if (direction === 'prev') {
                this.traceReaderReveal('dispatch-swipe-navigation', { direction: 'prev' });

                if (this.triggerChevronButtonClick('prev', 'swipe')) {
                    return true;
                }

                await this.goPreviousFromChevron('swipe');

                return true;
            }

            return false;
        },

        activationAnchorFromEvent(event = null) {
            const targetElement =
                event?.currentTarget instanceof Element
                    ? event.currentTarget
                    : event?.target instanceof Element
                      ? event.target
                      : null;
            const point = this.swipePoint(event);

            if (point && Number.isFinite(point.x) && Number.isFinite(point.y)) {
                return {
                    x: point.x,
                    y: point.y,
                    target: targetElement,
                };
            }

            if (targetElement instanceof Element) {
                return {
                    target: targetElement,
                };
            }

            return null;
        },

        onSwipeStart(event) {
            if (event?.__quranReaderInputHandled) {
                return;
            }

            if (event && typeof event === 'object') {
                event.__quranReaderInputHandled = true;
            }

            if (event.target?.closest?.('[data-no-swipe]')) {
                this.resetSwipeState();

                return;
            }

            const source = this.swipeEventSource(event);

            const isTouchPointer =
                String(event?.pointerType ?? '').toLowerCase() === 'touch' ||
                String(event?.pointerType ?? '').toLowerCase() === 'pen';

            if (
                event.target?.closest?.('[data-quran-word-button], [data-quran-line-text]') &&
                source !== 'touch' &&
                !isTouchPointer &&
                !this.usesMobileDoubleTapCopyMode()
            ) {
                this.resetSwipeState();

                return;
            }

            if (event.target?.closest?.('input, textarea, select, [contenteditable="true"]')) {
                this.resetSwipeState();

                return;
            }

            if (this.swipe.source && this.swipe.source !== source) {
                return;
            }

            if (
                event.pointerType === 'mouse' &&
                Number.isFinite(Number(event.button)) &&
                Number(event.button) > 0
            ) {
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            this.swipe.active = true;
            this.swipe.source = source;
            this.swipe.startX = point.x;
            this.swipe.startY = point.y;
            this.swipe.pointerId = point.pointerId;
            this.swipe.pointerType = point.pointerType;
            this.hoveredAyahIndex = 0;
            this.hoveredWordIndex = 0;
        },

        async onSwipeMove(event) {
            if (event?.__quranReaderInputHandled) {
                return;
            }

            if (event && typeof event === 'object') {
                event.__quranReaderInputHandled = true;
            }

            if (!this.swipe.active) {
                return;
            }

            const source = this.swipeEventSource(event);
            const sourceMismatched = this.swipe.source && this.swipe.source !== source;

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            if (sourceMismatched) {
                if (!this.usesMobileDoubleTapCopyMode()) {
                    return;
                }

                this.swipe.source = source;
                this.swipe.pointerId = point.pointerId;
                this.swipe.pointerType = point.pointerType;
            }

            if (this.wordPress?.holdTriggered || this.wordPress?.dragActive) {
                return;
            }

            if (
                this.wordPress?.active &&
                String(point.pointerType ?? this.swipe.pointerType ?? '').toLowerCase() ===
                    'mouse' &&
                (!this.usesMobileDoubleTapCopyMode() || this.wordPress?.isSecondTap)
            ) {
                return;
            }

            if (this.swipe.pointerId !== null && point.pointerId !== this.swipe.pointerId) {
                this.resetSwipeState();

                return;
            }

            const direction = this.swipeNavigationDirection(
                point.x - this.swipe.startX,
                point.y - this.swipe.startY,
            );

            if (!direction) {
                return;
            }

            await this.dispatchSwipeNavigation(direction);
        },

        resetSwipeState() {
            this.swipe.active = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;
        },

        async onSwipeEnd(event) {
            if (event?.__quranReaderInputHandled) {
                return;
            }

            if (event && typeof event === 'object') {
                event.__quranReaderInputHandled = true;
            }

            if (!this.swipe.active) {
                return;
            }

            const source = this.swipeEventSource(event);
            const sourceMismatched = this.swipe.source && this.swipe.source !== source;

            const point = this.swipePoint(event);

            if (!point) {
                this.resetSwipeState();

                return;
            }

            if (sourceMismatched) {
                if (!this.usesMobileDoubleTapCopyMode()) {
                    this.resetSwipeState();

                    return;
                }

                this.swipe.source = source;
                this.swipe.pointerId = point.pointerId;
                this.swipe.pointerType = point.pointerType;
            }

            if (this.wordPress?.holdTriggered || this.wordPress?.dragActive) {
                this.resetSwipeState();

                return;
            }

            if (
                this.wordPress?.active &&
                String(point.pointerType ?? this.swipe.pointerType ?? '').toLowerCase() ===
                    'mouse' &&
                (!this.usesMobileDoubleTapCopyMode() || this.wordPress?.isSecondTap)
            ) {
                this.resetSwipeState();

                return;
            }

            if (this.swipe.pointerId !== null && point.pointerId !== this.swipe.pointerId) {
                this.resetSwipeState();

                return;
            }

            const direction = this.swipeNavigationDirection(
                point.x - this.swipe.startX,
                point.y - this.swipe.startY,
            );

            if (!direction) {
                this.resetSwipeState();

                return;
            }

            await this.dispatchSwipeNavigation(direction);
        },

        onSwipeCancel() {
            this.resetSwipeState();
            this.clearWordPressState();
        },

        pageContentStyle() {
            return 'width: max-content;';
        },

        hasManualPageLayoutAdjustments() {
            return (
                this.normalizePageScaleAdjustValue(this.quranPageScaleAdjustValue, 0) !== 0 ||
                this.normalizePageGapAdjustValue(this.quranPageGapAdjustValue, 0) !== 0 ||
                this.normalizePageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue, 0) !== 0
            );
        },

        normalizePageScaleAdjustValue(value, fallback = 0) {
            const parsedValue = Math.trunc(Number(value));
            const normalizedFallback = Math.trunc(Number(fallback) || 0);
            const candidate = Number.isFinite(parsedValue) ? parsedValue : normalizedFallback;

            return Math.max(quranPageScaleAdjustMin, Math.min(quranPageScaleAdjustMax, candidate));
        },

        normalizePageGapAdjustValue(value, fallback = 0) {
            const parsedValue = Math.trunc(Number(value));
            const normalizedFallback = Math.trunc(Number(fallback) || 0);
            const candidate = Number.isFinite(parsedValue) ? parsedValue : normalizedFallback;

            return Math.max(quranPageGapAdjustMin, Math.min(quranPageGapAdjustMax, candidate));
        },

        normalizePageYOffsetAdjustValue(value, fallback = 0) {
            const parsedValue = Math.trunc(Number(value));
            const normalizedFallback = Math.trunc(Number(fallback) || 0);
            const candidate = Number.isFinite(parsedValue) ? parsedValue : normalizedFallback;

            return Math.max(
                quranPageYOffsetAdjustMin,
                Math.min(quranPageYOffsetAdjustMax, candidate),
            );
        },

        readPersistedPageScaleAdjustValue() {
            return this.normalizePageScaleAdjustValue(
                readLocalStorage(quranPageScaleAdjustStorageKey, 0),
                0,
            );
        },

        persistPageScaleAdjustValue(value = this.quranPageScaleAdjustValue) {
            writeLocalStorage(
                quranPageScaleAdjustStorageKey,
                this.normalizePageScaleAdjustValue(value, 0),
            );
        },

        readPersistedPageGapAdjustValue() {
            return this.normalizePageGapAdjustValue(
                readLocalStorage(quranPageGapAdjustStorageKey, 0),
                0,
            );
        },

        persistPageGapAdjustValue(value = this.quranPageGapAdjustValue) {
            writeLocalStorage(
                quranPageGapAdjustStorageKey,
                this.normalizePageGapAdjustValue(value, 0),
            );
        },

        readPersistedPageYOffsetAdjustValue() {
            return this.normalizePageYOffsetAdjustValue(
                readLocalStorage(quranPageYOffsetAdjustStorageKey, 0),
                0,
            );
        },

        persistPageYOffsetAdjustValue(value = this.quranPageYOffsetAdjustValue) {
            writeLocalStorage(
                quranPageYOffsetAdjustStorageKey,
                this.normalizePageYOffsetAdjustValue(value, 0),
            );
        },

        pageScaleAdjustFactor() {
            return Math.max(
                0.2,
                1 +
                    this.normalizePageScaleAdjustValue(this.quranPageScaleAdjustValue, 0) *
                        quranPageScaleAdjustMultiplierStep,
            );
        },

        pageScaleAdjustDisplayValue() {
            const value = this.normalizePageScaleAdjustValue(this.quranPageScaleAdjustValue, 0);

            return value > 0 ? `+${value}` : String(value);
        },

        pageGapAdjustFactor() {
            return Math.max(
                0.2,
                1 +
                    this.normalizePageGapAdjustValue(this.quranPageGapAdjustValue, 0) *
                        quranPageGapAdjustMultiplierStep,
            );
        },

        pageGapAdjustDisplayValue() {
            const value = this.normalizePageGapAdjustValue(this.quranPageGapAdjustValue, 0);

            return value > 0 ? `+${value}` : String(value);
        },

        pageYOffsetAdjustRemValue() {
            return (
                this.normalizePageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue, 0) *
                quranPageYOffsetAdjustRemStep
            );
        },

        pageYOffsetAdjustDisplayValue() {
            const value = this.normalizePageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue, 0);

            return value > 0 ? `+${value}` : String(value);
        },

        pageScaleElement() {
            if (this.$refs?.pageContent instanceof HTMLElement) {
                return this.$refs.pageContent;
            }

            if (this.$el?.firstElementChild instanceof HTMLElement) {
                return this.$el.firstElementChild;
            }

            return null;
        },

        searchDestinationScaleBoostAmount() {
            const boostPageNumber = Math.max(
                0,
                Math.trunc(Number(this.searchDestinationScaleBoostPageNumber ?? 0)),
            );
            const boostSource = String(this.searchDestinationScaleBoostSource ?? '').trim();
            const isSearchDestinationSource =
                boostSource === 'search-result' || boostSource === 'surah-directory';

            if (!isSearchDestinationSource || boostPageNumber <= 0) {
                return 0;
            }

            if (this.pageNumber !== boostPageNumber) {
                return 0;
            }

            return 0.05;
        },

        searchDestinationTypeScaleBoostAmount() {
            const boostPageNumber = Math.max(
                0,
                Math.trunc(Number(this.searchDestinationScaleBoostPageNumber ?? 0)),
            );
            const boostSource = String(this.searchDestinationScaleBoostSource ?? '').trim();
            const isSearchDestinationSource =
                boostSource === 'search-result' || boostSource === 'surah-directory';

            if (!isSearchDestinationSource || boostPageNumber <= 0) {
                return 0;
            }

            if (this.pageNumber !== boostPageNumber) {
                return 0;
            }

            return 0.3;
        },

        setCurrentPageScale(baseScale, { forFitting = false } = {}) {
            const contentElement = this.$refs?.pageContent;
            const frameElement = this.$refs?.pageFrame;
            const fallbackScaleElement = this.pageScaleElement();
            const pageLinesTargets = [
                contentElement?.classList?.contains('quran-page-lines')
                    ? contentElement
                    : contentElement?.querySelector?.('.quran-page-lines'),
                frameElement?.querySelector?.('.quran-page-lines'),
            ];
            const scaleTargets = [
                contentElement,
                frameElement,
                fallbackScaleElement,
                ...pageLinesTargets,
            ].filter(
                (element, index, array) =>
                    element instanceof HTMLElement && array.indexOf(element) === index,
            );

            if (scaleTargets.length === 0) {
                return;
            }

            const scaleElement = scaleTargets[0];

            const normalizedBaseScale = Math.max(0.05, Number(baseScale) || 1);
            const scaledBaseScale = normalizedBaseScale + this.searchDestinationScaleBoostAmount();
            const effectiveScale = Number(
                forFitting
                    ? scaledBaseScale
                    : (scaledBaseScale * this.pageScaleAdjustFactor()).toFixed(4),
            );
            const effectiveGapFactor = forFitting ? 1 : this.pageGapAdjustFactor();
            const effectiveYOffset = forFitting
                ? '0rem'
                : `${this.pageYOffsetAdjustRemValue().toFixed(3)}rem`;
            const readTypeScaleValue = Number(
                getComputedStyle(scaleElement).getPropertyValue('--quran-page-type-scale').trim(),
            );
            const baseTypeScale = Number.isFinite(readTypeScaleValue) ? readTypeScaleValue : 1;
            const boostedTypeScale = Math.max(
                0.2,
                baseTypeScale + this.searchDestinationTypeScaleBoostAmount(),
            );

            scaleTargets.forEach((targetElement) => {
                targetElement.style.setProperty('--quran-page-scale', String(effectiveScale));
                targetElement.style.setProperty(
                    '--quran-page-type-scale-effective',
                    String(boostedTypeScale),
                );
                targetElement.style.setProperty(
                    '--quran-page-gap-adjust-factor',
                    String(effectiveGapFactor),
                );
                targetElement.style.setProperty('--quran-page-y-offset-adjust', effectiveYOffset);
            });
        },

        schedulePageScaleAdjustRefit() {
            if (this._pageScaleAdjustRefitRaf !== null) {
                return;
            }

            this._pageScaleAdjustRefitRaf = requestAnimationFrame(() => {
                this._pageScaleAdjustRefitRaf = null;

                if (
                    !this.hasRenderablePage() ||
                    this.isLoadingPage ||
                    this._layoutActivePromise !== null ||
                    this._pendingNavigationRequest !== null
                ) {
                    return;
                }

                this._bypassNextFitCache = true;
                this.fitPageToViewport();
            });
        },

        applyPageScaleAdjustValue(
            value,
            { persist = true, closeOverlay = false, refit = false } = {},
        ) {
            this.quranPageScaleAdjustValue = this.normalizePageScaleAdjustValue(value, 0);

            if (persist) {
                this.persistPageScaleAdjustValue(this.quranPageScaleAdjustValue);
            }

            this.setCurrentPageScale(this.pageScale);

            if (refit) {
                this.schedulePageScaleAdjustRefit();
            }

            if (closeOverlay) {
                this.isFontScaleOverlayVisible = false;
            }
        },
    };
};
