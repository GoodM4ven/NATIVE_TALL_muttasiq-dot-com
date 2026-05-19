export const createReaderNavigationFitRevealGuardsAndSolverModule = (deps) => {
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
        flushQueuedLayoutRequest() {
            if (this._queuedLayoutRequest === null) {
                return;
            }

            if (
                this.hasBlockingModalLifecycleState() ||
                this.isLoadingPage ||
                !this.hasRenderablePage()
            ) {
                return;
            }

            const queuedRequest = this._queuedLayoutRequest;
            this._queuedLayoutRequest = null;
            this.scheduleLayout(queuedRequest);
        },

        openModalCount() {
            return Array.from(document.querySelectorAll('.fi-modal')).filter((modalElement) =>
                modalElement.classList.contains('fi-modal-open'),
            ).length;
        },

        hasBlockingModalLifecycleState({ recoverStaleState = false } = {}) {
            const openModalCount = this.openModalCount();

            if (openModalCount > 0) {
                return true;
            }

            if (!this._isModalLifecycleSettling && this._activeModalIds.size === 0) {
                return false;
            }

            if (!recoverStaleState) {
                return true;
            }

            return !this.recoverStaleModalLifecycleState();
        },

        recoverStaleModalLifecycleState() {
            const openModalCount = this.openModalCount();

            if (openModalCount > 0) {
                return false;
            }

            if (!this._isModalLifecycleSettling && this._activeModalIds.size === 0) {
                return false;
            }

            this._activeModalIds.clear();
            this._isModalLifecycleSettling = false;

            return true;
        },

        clearStaleRevealGuards({ allowUnlock = true } = {}) {
            let didClearState = false;

            if (
                (this._isModalLifecycleSettling || this._activeModalIds.size > 0) &&
                this.openModalCount() <= 0
            ) {
                this._activeModalIds.clear();
                this._isModalLifecycleSettling = false;
                didClearState = true;
            }

            if (!this.isLoadingPage) {
                const pendingTargetPage = clampPage(
                    Number(this._pendingNavigationRequest?.targetPage ?? 0),
                    this.maxPage,
                );

                if (
                    pendingTargetPage === this.pageNumber &&
                    this._pendingNavigationRequest !== null
                ) {
                    this._pendingNavigationRequest = null;
                    didClearState = true;
                }
            }

            if (
                allowUnlock &&
                this._navigationRevealLocked &&
                !this.isLoadingPage &&
                this._pendingNavigationRequest === null &&
                this._navigationRevealUnlockTimer === null
            ) {
                this._navigationRevealLocked = false;
                didClearState = true;
            }

            return didClearState;
        },

        readerRevealDebugEnabled() {
            try {
                return this.normalizeBooleanFlag(
                    window.localStorage?.getItem?.(readerRevealDebugStorageKey),
                    false,
                );
            } catch (_) {
                return false;
            }
        },

        traceReaderReveal(eventName, details = {}) {
            if (!this.readerRevealDebugEnabled()) {
                return;
            }

            const normalizedEventName = String(eventName ?? '').trim() || 'event';
            const payload =
                details && typeof details === 'object' && !Array.isArray(details) ? details : {};

            console.debug('[quran-reader][reveal]', normalizedEventName, {
                pageNumber: this.pageNumber,
                isFittingPage: this.isFittingPage,
                isLoadingPage: this.isLoadingPage,
                pendingTargetPage: clampPage(
                    Number(this._pendingNavigationRequest?.targetPage ?? 0),
                    this.maxPage,
                ),
                navigationRevealLocked: this._navigationRevealLocked,
                modalLifecycleSettling: this._isModalLifecycleSettling,
                activeModalCount: this._activeModalIds.size,
                openModalCount: this.openModalCount(),
                ...payload,
            });
        },

        clearSwipeRevealWatchdog() {
            if (this._swipeRevealWatchdogTimer === null) {
                return;
            }

            clearTimeout(this._swipeRevealWatchdogTimer);
            this._swipeRevealWatchdogTimer = null;
        },

        forceRevealCurrentPage(reason = 'generic') {
            if (!this.hasRenderablePage()) {
                return false;
            }

            if (this.hasBlockingModalLifecycleState()) {
                this.traceReaderReveal('force-reveal-skipped', {
                    reason,
                    blockedByModalLifecycle: true,
                });

                return false;
            }

            this.clearSwipeRevealWatchdog();
            this.syncPageInputToCurrentPage();
            this.isFittingPage = false;
            this._lastPageRevealAt = Date.now();
            this._revealBlockedSinceAt = 0;
            this._revealBlockedLayoutToken = 0;
            this.traceReaderReveal('force-reveal-current-page', { reason });

            return true;
        },

        scheduleSwipeRevealWatchdog(
            source = 'swipe',
            { delayMs = swipeRevealWatchdogDelayMs, startedAtMs = null } = {},
        ) {
            if (String(source ?? '').trim() !== 'swipe') {
                return;
            }

            this.clearSwipeRevealWatchdog();
            const normalizedStartedAtMs = Number.isFinite(Number(startedAtMs))
                ? Math.max(0, Math.trunc(Number(startedAtMs)))
                : Date.now();
            const normalizedDelayMs = Math.max(
                200,
                Math.trunc(Number(delayMs) || swipeRevealWatchdogDelayMs),
            );
            this.traceReaderReveal('schedule-swipe-reveal-watchdog', {
                delayMs: normalizedDelayMs,
                startedAtMs: normalizedStartedAtMs,
            });

            this._swipeRevealWatchdogTimer = window.setTimeout(() => {
                this._swipeRevealWatchdogTimer = null;
                const hasRenderablePage = this.hasRenderablePage();

                if (!hasRenderablePage) {
                    return;
                }

                if (this.hasBlockingModalLifecycleState()) {
                    return;
                }

                const blockedByNavigationOrLayout =
                    this.isLoadingPage ||
                    this._layoutActivePromise !== null ||
                    this._pendingNavigationRequest !== null ||
                    this._navigationRevealLocked;

                if (blockedByNavigationOrLayout) {
                    const blockedForMs = Date.now() - normalizedStartedAtMs;
                    this.traceReaderReveal('swipe-watchdog-blocked', {
                        blockedForMs,
                        isLoadingPage: this.isLoadingPage,
                        hasLayoutPromise: this._layoutActivePromise !== null,
                        hasPendingNavigation: this._pendingNavigationRequest !== null,
                        navigationRevealLocked: this._navigationRevealLocked,
                    });

                    if (blockedForMs < revealBlockedFailOpenDelayMs * 2) {
                        this.scheduleSwipeRevealWatchdog('swipe', {
                            delayMs: Math.min(420, Math.max(220, normalizedDelayMs)),
                            startedAtMs: normalizedStartedAtMs,
                        });

                        return;
                    }

                    this.clearStaleRevealGuards();

                    if (this.isLoadingPage) {
                        this.abortActivePageLoad();
                        this._activePageAbortController = null;
                        this.isLoadingPage = false;
                    }

                    if (this.hasRenderablePage()) {
                        this.forceRevealCurrentPage('swipe-watchdog-blocked-fail-open');
                    }

                    if (
                        this._pendingNavigationRequest !== null &&
                        !this._navigationRevealLocked &&
                        !this.isLoadingPage
                    ) {
                        void this.commitPendingNavigation();
                    }

                    return;
                }

                if (this.isCurrentPageVisiblyReady()) {
                    this.clearStaleRevealGuards();
                    this.forceRevealCurrentPage('swipe-watchdog-already-visible');

                    return;
                }

                if (!this.isFittingPage) {
                    return;
                }

                this.traceReaderReveal('swipe-watchdog-layout-recovery');
                this.clearStaleRevealGuards();
                this.clearLayoutTimers();
                this.isFittingPage = true;
                this._bypassNextFitCache = true;
                void this.layoutPageGuaranteed({
                    revealDelayMs: 120,
                    maxAttempts: 4,
                    useIdleFit: false,
                }).finally(() => {
                    if (
                        !this.isLoadingPage &&
                        this._pendingNavigationRequest === null &&
                        !this._navigationRevealLocked &&
                        this.openModalCount() <= 0 &&
                        !this._isModalLifecycleSettling
                    ) {
                        this.forceRevealCurrentPage('swipe-watchdog-fail-open');
                    }
                });
            }, normalizedDelayMs);
        },

        beginLayoutCycle() {
            this._layoutToken += 1;
            this.isFittingPage = true;
            this._revealBlockedSinceAt = 0;
            this._revealBlockedLayoutToken = 0;

            return this._layoutToken;
        },

        hasRenderablePage() {
            return this.ready && this.mushafLines.length > 0;
        },

        isCurrentPageVisiblyReady() {
            if (!this.hasRenderablePage()) {
                return false;
            }

            const contentElement = this.$refs.pageContent;

            if (!(contentElement instanceof HTMLElement)) {
                return false;
            }

            const styles = window.getComputedStyle(contentElement);
            const opacity = Number.parseFloat(styles.opacity || '0');
            const visibleLineCount = Array.from(
                contentElement.querySelectorAll('[data-quran-line-text]'),
            ).filter(
                (lineElement) =>
                    String(lineElement.textContent ?? '')
                        .replace(/\s+/g, '')
                        .trim().length > 0,
            ).length;

            return (
                this.pageFitState() === 'ready' &&
                styles.visibility !== 'hidden' &&
                opacity > 0.35 &&
                visibleLineCount > 0
            );
        },

        isCurrentPageContentVisible(minOpacity = 0.35) {
            if (!this.hasRenderablePage()) {
                return false;
            }

            const contentElement = this.$refs.pageContent;

            if (!(contentElement instanceof HTMLElement)) {
                return false;
            }

            const styles = window.getComputedStyle(contentElement);
            const opacity = Number.parseFloat(styles.opacity || '0');
            const normalizedMinOpacity = Math.max(0, Number(minOpacity) || 0);
            const hasRenderableText = Array.from(
                contentElement.querySelectorAll('[data-quran-line-text], [data-quran-word-button]'),
            ).some(
                (lineElement) =>
                    String(lineElement.textContent ?? '')
                        .replace(/\s+/g, '')
                        .trim().length > 0,
            );
            const fallbackRenderableText =
                String(contentElement.textContent ?? '')
                    .replace(/\s+/g, '')
                    .trim().length > 0;

            return (
                styles.visibility !== 'hidden' &&
                opacity > normalizedMinOpacity &&
                (hasRenderableText || fallbackRenderableText)
            );
        },

        async waitForStablePageFrame({
            maxFrames = 18,
            requiredStableFrames = 3,
            tolerancePx = 0.75,
        } = {}) {
            const frameElement = this.$refs.pageFrame;

            if (!(frameElement instanceof Element)) {
                await nextAnimationFrame();

                return;
            }

            let previousWidth = 0;
            let previousHeight = 0;
            let stableFrameCount = 0;
            const normalizedMaxFrames = Math.max(4, Math.trunc(Number(maxFrames) || 18));
            const normalizedRequiredStableFrames = Math.max(
                2,
                Math.trunc(Number(requiredStableFrames) || 3),
            );
            const normalizedTolerancePx = Math.max(0.25, Number(tolerancePx) || 0.75);

            for (let frame = 0; frame < normalizedMaxFrames; frame += 1) {
                await nextAnimationFrame();

                const frameRect = frameElement.getBoundingClientRect();
                const frameParentRect =
                    frameElement.parentElement?.getBoundingClientRect?.() ?? null;
                const width = Math.max(
                    0,
                    Number(
                        frameParentRect?.width ??
                            frameRect?.width ??
                            frameElement.parentElement?.clientWidth ??
                            frameElement.clientWidth ??
                            0,
                    ),
                );
                const height = Math.max(
                    0,
                    Number(frameRect?.height ?? frameElement.clientHeight ?? 0),
                );

                if (width <= 1 || height <= 1) {
                    stableFrameCount = 0;
                    previousWidth = width;
                    previousHeight = height;

                    continue;
                }

                const widthDelta = Math.abs(width - previousWidth);
                const heightDelta = Math.abs(height - previousHeight);

                if (widthDelta <= normalizedTolerancePx && heightDelta <= normalizedTolerancePx) {
                    stableFrameCount += 1;
                } else {
                    stableFrameCount = 0;
                }

                previousWidth = width;
                previousHeight = height;

                if (stableFrameCount >= normalizedRequiredStableFrames) {
                    return;
                }
            }
        },

        async runStartupFinalFitPass() {
            if (!this.hasRenderablePage()) {
                return;
            }

            await this.nextTickAsync();
            await this.waitForPageFontReady();
            await this.waitForStablePageFrame({
                maxFrames: 16,
                requiredStableFrames: 3,
                tolerancePx: 0.8,
            });

            this._bypassNextFitCache = true;
            await this.layoutPageGuaranteed({
                revealDelayMs: 130,
                maxAttempts: 4,
                useIdleFit: false,
            });

            await this.waitForStablePageFrame({
                maxFrames: 12,
                requiredStableFrames: 2,
                tolerancePx: 0.6,
            });

            if (
                !this.isCurrentPageVisiblyReady() ||
                this._lastFittedPageNumber !== this.pageNumber
            ) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 110,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            }

            if (
                this.isFittingPage &&
                this.hasRenderablePage() &&
                !this.isLoadingPage &&
                this._pendingNavigationRequest === null &&
                !this._navigationRevealLocked &&
                !(this.shouldUseImmersiveReaderChrome() && this._immersiveEntryAwaitingFirstReveal)
            ) {
                this.forceRevealCurrentPage('startup-final-fit-pass-fail-open');
            }
        },

        holdPageHiddenForModalLifecycle() {
            if (!this.hasRenderablePage()) {
                return;
            }

            this._isModalLifecycleSettling = true;
            this.clearLayoutTimers();
            this.beginLayoutCycle();
        },

        scheduleLayoutAfterModalLifecycle(delayMs = 220) {
            if (!this.hasRenderablePage()) {
                return;
            }

            if (this._modalLayoutResumeTimer !== null) {
                clearTimeout(this._modalLayoutResumeTimer);
                this._modalLayoutResumeTimer = null;
            }

            this._modalLayoutResumeTimer = window.setTimeout(
                () => {
                    this._modalLayoutResumeTimer = null;
                    this._isModalLifecycleSettling = false;
                    this.clearLayoutTimers();
                    this.isFittingPage = true;
                    this._bypassNextFitCache = true;

                    void this.layoutPageGuaranteed({
                        revealDelayMs: 180,
                        maxAttempts: 5,
                        useIdleFit: false,
                    });
                },
                Math.max(0, Math.trunc(Number(delayMs) || 220)),
            );
        },

        cancelActiveSearchProcessing() {
            this.clearSearchResultsUpdateQueue();
            this._searchRequestSerial += 1;
            this._searchRequestInFlight = false;
            this._searchQueuedNormalizedQuery = null;
            this.search.isLoading = false;
            this.search.streamHasUpdates = false;
            this.search.lastCompletedNormalizedQuery = '';
            this.clearSearchStreamTarget();
        },

        clearPendingPostModalTargetFit() {
            if (this._postModalTargetFitTimer !== null) {
                clearTimeout(this._postModalTargetFitTimer);
                this._postModalTargetFitTimer = null;
            }

            this._postModalTargetFitPage = 0;
            this._postModalTargetFitRetries = 0;
        },

        queuePendingPostModalTargetFit(pageNumber) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);

            if (normalizedPageNumber <= 0) {
                this.clearPendingPostModalTargetFit();

                return;
            }

            this._postModalTargetFitPage = normalizedPageNumber;
            this._postModalTargetFitRetries = 0;
        },

        async fitSpecificPageAfterModalClose(
            pageNumber,
            { revealDelayMs = 240, maxAttempts = 6 } = {},
        ) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);

            if (
                normalizedPageNumber <= 0 ||
                this.pageNumber !== normalizedPageNumber ||
                !this.hasRenderablePage() ||
                this.isLoadingPage ||
                this._navigationRevealLocked ||
                this._isModalLifecycleSettling ||
                this._activeModalIds.size > 0 ||
                this.openModalCount() > 0
            ) {
                return false;
            }

            if (this.isCurrentPageVisiblyReady()) {
                return true;
            }

            this.pauseIdleWarmup(960);
            this._bypassNextFitCache = true;
            this.isFittingPage = true;
            this.clearLayoutTimers();

            await this.nextTickAsync();
            await this.layoutPageGuaranteed({
                revealDelayMs,
                maxAttempts,
            });

            if (this._lastFittedPageNumber !== normalizedPageNumber) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 180,
                    maxAttempts: Math.max(4, maxAttempts - 1),
                });
            }

            return this._lastFittedPageNumber === normalizedPageNumber;
        },

        schedulePendingModalCloseFit(
            pageNumber,
            { retries = 36, delayMs = 90, revealDelayMs = 240, maxAttempts = 6 } = {},
        ) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);

            if (normalizedPageNumber <= 0) {
                this.clearPendingPostModalTargetFit();

                return;
            }

            if (this._postModalTargetFitPage !== normalizedPageNumber) {
                this._postModalTargetFitRetries = Math.max(0, Math.trunc(Number(retries) || 36));
            } else {
                this._postModalTargetFitRetries = Math.max(
                    this._postModalTargetFitRetries,
                    Math.max(0, Math.trunc(Number(retries) || 36)),
                );
            }

            this._postModalTargetFitPage = normalizedPageNumber;

            if (this._postModalTargetFitTimer !== null) {
                clearTimeout(this._postModalTargetFitTimer);
                this._postModalTargetFitTimer = null;
            }

            const attemptFit = () => {
                if (this._postModalTargetFitPage !== normalizedPageNumber) {
                    return;
                }

                const canFitNow =
                    this.pageNumber === normalizedPageNumber &&
                    this.hasRenderablePage() &&
                    !this.isLoadingPage &&
                    !this._navigationRevealLocked &&
                    !this._isModalLifecycleSettling &&
                    this._activeModalIds.size === 0 &&
                    this.openModalCount() <= 0 &&
                    !(
                        this._layoutActivePromise !== null ||
                        this._revealTimer !== null ||
                        this.isFittingPage ||
                        (this._lastPageRevealAt > 0 &&
                            Date.now() - this._lastPageRevealAt < postModalFitRevealSettleDelayMs)
                    );

                if (canFitNow) {
                    if (this.isCurrentPageVisiblyReady()) {
                        this.clearPendingPostModalTargetFit();

                        return;
                    }

                    void this.fitSpecificPageAfterModalClose(normalizedPageNumber, {
                        revealDelayMs,
                        maxAttempts,
                    }).finally(() => {
                        if (this._postModalTargetFitPage === normalizedPageNumber) {
                            this.clearPendingPostModalTargetFit();
                        }
                    });

                    return;
                }

                if (this._postModalTargetFitRetries <= 0) {
                    this.clearPendingPostModalTargetFit();

                    return;
                }

                this._postModalTargetFitRetries -= 1;
                this._postModalTargetFitTimer = window.setTimeout(
                    () => {
                        this._postModalTargetFitTimer = null;
                        attemptFit();
                    },
                    Math.max(36, Math.trunc(Number(delayMs) || 90)),
                );
            };

            attemptFit();
        },

        shouldDeferPostModalTargetFit(pageNumber = this.pageNumber, source = 'generic') {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);
            const normalizedSource = String(source ?? 'generic').trim();

            if (
                normalizedPageNumber <= 0 ||
                this._postModalTargetFitPage !== normalizedPageNumber
            ) {
                return false;
            }

            return normalizedSource === 'surah-directory' || normalizedSource === 'search-result';
        },

        resumeLayoutWhenNoOpenModals(attempt = 0) {
            if (!this.hasRenderablePage()) {
                this._isModalLifecycleSettling = false;

                return;
            }

            const normalizedAttempt = Math.max(0, Math.trunc(Number(attempt) || 0));
            const remainingModalCount = this.openModalCount();

            if (remainingModalCount <= 0) {
                this.recoverStaleModalLifecycleState();
                this.scheduleLayoutAfterModalLifecycle(220);

                return;
            }

            this._isModalLifecycleSettling = true;

            if (normalizedAttempt >= 24) {
                this._activeModalIds.clear();
                this._isModalLifecycleSettling = false;
                this.scheduleLayout({ revealDelayMs: 240 });

                return;
            }

            window.setTimeout(() => {
                this.resumeLayoutWhenNoOpenModals(normalizedAttempt + 1);
            }, 40);
        },

        trackModalLifecycle(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const openModalCount = this.openModalCount();
            const hasTrackedModalId = modalId !== '' && this._activeModalIds.has(modalId);
            const hasTrackedModalState =
                this._activeModalIds.size > 0 || this._isModalLifecycleSettling;
            const isLateCloseLikeEvent = kind === 'closing' || kind === 'closed';
            const now = Date.now();

            this.pruneModalLifecycleSuppression(now);

            const isSuppressedCloseEvent =
                isLateCloseLikeEvent &&
                now < this._suppressModalLifecycleEffectsUntil &&
                (modalId !== ''
                    ? this._suppressModalLifecycleModalIds.has(modalId)
                    : this._suppressModalLifecycleModalIds.size > 0);

            if (isSuppressedCloseEvent) {
                if (modalId !== '') {
                    this._activeModalIds.delete(modalId);

                    if (kind === 'closed') {
                        this._suppressModalLifecycleModalIds.delete(modalId);
                    }
                }

                if (openModalCount <= 0) {
                    this._isModalLifecycleSettling = false;
                }

                if (
                    this._postModalTargetFitPage === this.pageNumber &&
                    this.isCurrentPageVisiblyReady()
                ) {
                    this.clearPendingPostModalTargetFit();
                }

                return;
            }

            if (
                isLateCloseLikeEvent &&
                openModalCount <= 0 &&
                this.hasRenderablePage() &&
                this.isCurrentPageVisiblyReady()
            ) {
                if (modalId !== '') {
                    this._activeModalIds.delete(modalId);
                }

                this._isModalLifecycleSettling = false;

                if (this._postModalTargetFitPage === this.pageNumber) {
                    this.clearPendingPostModalTargetFit();
                }

                return;
            }

            if (
                (kind === 'closing' || kind === 'closed') &&
                modalId === '' &&
                openModalCount <= 0 &&
                this._activeModalIds.size === 0
            ) {
                if (this._isModalLifecycleSettling) {
                    this._isModalLifecycleSettling = false;
                    this.scheduleLayoutAfterModalLifecycle(kind === 'closed' ? 120 : 180);
                }

                return;
            }

            if (kind === 'closing') {
                if (modalId === '' && openModalCount <= 0 && !hasTrackedModalState) {
                    return;
                }

                if (modalId !== '' && !hasTrackedModalId && openModalCount <= 0) {
                    return;
                }
            }

            if (kind === 'closed') {
                if (modalId === '' && openModalCount <= 0 && !hasTrackedModalState) {
                    return;
                }

                if (modalId !== '' && !hasTrackedModalId && openModalCount <= 0) {
                    return;
                }
            }

            this._lastModalLifecycleEventAt = Date.now();

            if (kind === 'opened') {
                if (modalId !== '') {
                    this._activeModalIds.add(modalId);
                }

                this._bypassNextFitCache = true;
                this.holdPageHiddenForModalLifecycle();
                this._managerModalVersion += 1;
                this.syncReaderChromeDocumentClass();

                return;
            }

            if (kind === 'closing') {
                if (modalId === '' || this._activeModalIds.has(modalId) || openModalCount > 0) {
                    const navigatedAway = this._lastFittedPageNumber !== this.pageNumber;

                    if (navigatedAway) {
                        this._bypassNextFitCache = true;
                        this.holdPageHiddenForModalLifecycle();
                    }

                    this.resumeLayoutWhenNoOpenModals();
                }

                return;
            }

            if (kind === 'closed') {
                if (modalId !== '') {
                    this._activeModalIds.delete(modalId);
                }

                const navigatedAway = this._lastFittedPageNumber !== this.pageNumber;

                if (navigatedAway) {
                    this._bypassNextFitCache = true;
                    this.holdPageHiddenForModalLifecycle();
                }

                this._managerModalVersion += 1;

                window.setTimeout(() => {
                    this.resumeLayoutWhenNoOpenModals();
                }, 24);

                window.setTimeout(() => {
                    this._managerModalVersion += 1;
                    this.recoverStaleModalLifecycleState();
                    this.syncReaderChromeDocumentClass();
                }, 320);
            }
        },

        queuePageReveal(layoutToken, delayMs = 180) {
            this._revealTimer = window.setTimeout(() => {
                this._revealTimer = null;

                if (layoutToken !== this._layoutToken) {
                    if (this.hasRenderablePage()) {
                        this.queuePageReveal(this._layoutToken, 120);
                    }

                    return;
                }

                this.clearStaleRevealGuards({ allowUnlock: false });
                this.traceReaderReveal('queue-page-reveal-tick', {
                    layoutToken,
                });

                if (this._startupCalibrationPending) {
                    this.isFittingPage = true;
                    this.queuePageReveal(layoutToken, 90);

                    return;
                }

                if (this.hasBlockingModalLifecycleState({ recoverStaleState: true })) {
                    this.traceReaderReveal('queue-page-reveal-blocked', {
                        reason: 'modal-lifecycle',
                    });
                    this.isFittingPage = true;
                    this.queuePageReveal(layoutToken, 120);

                    return;
                }

                if (
                    this._pendingNavigationRequest !== null &&
                    !this._navigationRevealLocked &&
                    !this.isLoadingPage
                ) {
                    const stalePendingTargetPage = clampPage(
                        Number(this._pendingNavigationRequest?.targetPage ?? 0),
                        this.maxPage,
                    );

                    if (stalePendingTargetPage === this.pageNumber) {
                        this._pendingNavigationRequest = null;
                    } else {
                        this.traceReaderReveal('queue-page-reveal-commit-pending-navigation', {
                            stalePendingTargetPage,
                        });
                        void this.commitPendingNavigation();
                    }
                }

                if (
                    this._navigationRevealLocked ||
                    this._pendingNavigationRequest !== null ||
                    this.isLoadingPage
                ) {
                    if (this._revealBlockedLayoutToken !== layoutToken) {
                        this._revealBlockedLayoutToken = layoutToken;
                        this._revealBlockedSinceAt = Date.now();
                    }

                    const revealBlockedForMs = Date.now() - this._revealBlockedSinceAt;
                    const pendingTargetPage = clampPage(
                        Number(this._pendingNavigationRequest?.targetPage ?? 0),
                        this.maxPage,
                    );
                    const hasOpenModal = this.openModalCount() > 0;
                    const mayFailOpenReveal =
                        this.hasRenderablePage() &&
                        !hasOpenModal &&
                        !this._isModalLifecycleSettling &&
                        this._activeModalIds.size === 0 &&
                        (pendingTargetPage <= 0 || pendingTargetPage === this.pageNumber);

                    if (
                        revealBlockedForMs >= revealBlockedFailOpenDelayMs &&
                        this.hasRenderablePage()
                    ) {
                        const clearedStaleGuards = this.clearStaleRevealGuards();
                        this.traceReaderReveal('queue-page-reveal-stale-guards', {
                            revealBlockedForMs,
                            clearedStaleGuards,
                            pendingTargetPage,
                            mayFailOpenReveal,
                        });

                        if (
                            mayFailOpenReveal &&
                            this.forceRevealCurrentPage('queue-page-reveal-stale-guards')
                        ) {
                            this.clearSwipeRevealWatchdog();
                            return;
                        }
                    }

                    this.traceReaderReveal('queue-page-reveal-blocked', {
                        reason: this._navigationRevealLocked
                            ? 'navigation-lock'
                            : this._pendingNavigationRequest !== null
                              ? 'pending-navigation'
                              : 'loading-page',
                        revealBlockedForMs,
                    });
                    this.isFittingPage = true;
                    this.queuePageReveal(layoutToken, 90);

                    return;
                }

                this._revealBlockedSinceAt = 0;
                this._revealBlockedLayoutToken = 0;

                this.syncPageInputToCurrentPage();
                this._lastPageRevealAt = Date.now();
                this.clearSwipeRevealWatchdog();

                if (
                    this.shouldUseImmersiveReaderChrome() &&
                    this.isAnyQuranReaderViewOpen() &&
                    this._immersiveEntryAwaitingFirstReveal
                ) {
                    this._immersiveEntryAwaitingFirstReveal = false;
                    this.isReaderChromeVisible = true;
                    this.syncReaderChromeDocumentClass();
                    this.isFittingPage = true;
                    this._bypassNextFitCache = true;
                    void this.layoutPageGuaranteed({
                        revealDelayMs: 80,
                        maxAttempts: 3,
                        useIdleFit: false,
                    });
                    this.traceReaderReveal('queue-page-reveal-deferred-for-chrome');

                    return;
                }

                this.isFittingPage = false;
                this.traceReaderReveal('queue-page-reveal-ready');
            }, delayMs);
        },

        handleViewportChange() {
            this.syncFitCacheBreakpoint();
            this.syncCalibrationHudPosition();
            this.scheduleReaderPanelLayoutRefresh();

            if (this._viewportChangeDebounceTimer !== null) {
                clearTimeout(this._viewportChangeDebounceTimer);
            }

            this._viewportChangeDebounceTimer = window.setTimeout(() => {
                this._viewportChangeDebounceTimer = null;
                this.scheduleLayout({ revealDelayMs: 150 });
            }, 90);
        },

        scheduleLayout({ revealDelayMs = 180, maxAttempts = 4 } = {}) {
            if (
                this.wirdModeActive &&
                this.isWirdEntryLayoutSchedulingSuppressed() &&
                this.hasRenderablePage() &&
                !this.isFittingPage
            ) {
                return;
            }

            const hadTrackedModalLifecycleState =
                this._isModalLifecycleSettling || this._activeModalIds.size > 0;

            if (this.hasBlockingModalLifecycleState({ recoverStaleState: true })) {
                this.holdPageHiddenForModalLifecycle();

                return;
            }

            if (
                hadTrackedModalLifecycleState &&
                !this._isModalLifecycleSettling &&
                this._activeModalIds.size === 0
            ) {
                this._bypassNextFitCache = true;
            }

            const isWaitingOnlyForReveal =
                this._revealTimer !== null &&
                this.isFittingPage &&
                !this.isLoadingPage &&
                !this._layoutActivePromise &&
                this._pendingNavigationRequest === null &&
                !this._navigationRevealLocked &&
                !this._isModalLifecycleSettling &&
                this._activeModalIds.size === 0 &&
                Date.now() - this._lastModalLifecycleEventAt > 420 &&
                this.hasRenderablePage();

            if (isWaitingOnlyForReveal) {
                return;
            }

            const layoutRequest = this.normalizeLayoutRequest({
                revealDelayMs,
                maxAttempts,
            });

            if (this.isLoadingPage || this._layoutActivePromise) {
                this.queueLayoutRequest(layoutRequest);

                return;
            }

            this.clearLayoutTimers();

            this._layoutRaf = requestAnimationFrame(() => {
                this._layoutRaf = null;
                void this.layoutPageGuaranteed(layoutRequest);
            });
        },

        async waitForStableRenderedText(maxFrames = 8) {
            const contentElement = this.$refs.pageContent;

            if (!(contentElement instanceof Element)) {
                await nextAnimationFrame();

                return;
            }

            let previousWidth = 0;
            let previousHeight = 0;
            let stableFrames = 0;
            const frames = Math.max(2, Math.trunc(Number(maxFrames) || 8));

            for (let frame = 0; frame < frames; frame += 1) {
                await nextAnimationFrame();

                const { width, height } = this.measureRenderedBounds(contentElement);

                if (width <= 1 || height <= 1) {
                    stableFrames = 0;
                    previousWidth = width;
                    previousHeight = height;

                    continue;
                }

                const widthDelta = Math.abs(width - previousWidth);
                const heightDelta = Math.abs(height - previousHeight);

                if (widthDelta < 0.6 && heightDelta < 0.6) {
                    stableFrames += 1;
                } else {
                    stableFrames = 0;
                }

                previousWidth = width;
                previousHeight = height;

                if (stableFrames >= 2) {
                    return;
                }
            }
        },

        async layoutPage({ revealDelayMs = 180, useIdleFit = true, deferReveal = false } = {}) {
            const layoutToken = this.beginLayoutCycle();
            const shouldUseImmersiveChrome = this.shouldUseImmersiveReaderChrome();
            const stableTextFrames = shouldUseImmersiveChrome ? 6 : 10;
            const shouldRebalanceWordSpacing =
                !shouldUseImmersiveChrome &&
                !this._startupCalibrationPending &&
                !this.isCalibrating;

            await this.nextTickAsync();
            await this.waitForPageFontReady();
            await nextAnimationFrame();
            await this.waitForStableRenderedText(stableTextFrames);

            if (shouldRebalanceWordSpacing) {
                try {
                    this.rebalanceRectangularAyahLineWordSpacing();
                } catch (_) {
                    this.lineWordGapAdjustments = {};
                }
            }

            await this.nextTickAsync();
            await nextAnimationFrame();

            if (useIdleFit) {
                await this.runFitPageToViewportLazily();
            } else {
                this.fitPageToViewport();
            }

            if (!deferReveal) {
                this.queuePageReveal(layoutToken, revealDelayMs);
            }
        },

        async runFitPageToViewportLazily() {
            if (typeof window.requestIdleCallback === 'function') {
                await new Promise((resolve) => {
                    window.requestIdleCallback(
                        () => {
                            this.fitPageToViewport();
                            resolve();
                        },
                        {
                            timeout: 80,
                        },
                    );
                });

                return;
            }

            await wait(0);
            this.fitPageToViewport();
        },

        async layoutPageGuaranteed({
            revealDelayMs = 180,
            maxAttempts = 4,
            useIdleFit = true,
            deferReveal = false,
        } = {}) {
            const layoutRequest = this.normalizeLayoutRequest({
                revealDelayMs,
                maxAttempts,
                deferReveal,
            });

            if (this._layoutActivePromise) {
                this.queueLayoutRequest(layoutRequest);
                await this._layoutActivePromise;

                return;
            }

            const runLayoutPromise = (async () => {
                const shouldUseIdleFit = Boolean(useIdleFit || this.isCalibrating);

                for (let attempt = 0; attempt < layoutRequest.maxAttempts; attempt += 1) {
                    const fitRunsBeforeAttempt = this._fitRunCounter;
                    await this.layoutPage({
                        revealDelayMs: attempt === 0 ? layoutRequest.revealDelayMs : 160,
                        useIdleFit: shouldUseIdleFit,
                        deferReveal: Boolean(layoutRequest.deferReveal),
                    });

                    if (
                        this._fitRunCounter > fitRunsBeforeAttempt &&
                        this._lastFittedPageNumber === this.pageNumber
                    ) {
                        return;
                    }

                    await wait(55);
                }
            })();

            this._layoutActivePromise = runLayoutPromise;

            try {
                await runLayoutPromise;
            } finally {
                if (this._layoutActivePromise === runLayoutPromise) {
                    this._layoutActivePromise = null;
                }

                this.flushQueuedLayoutRequest();
            }
        },

        measureRenderedBounds(contentElement, { useRobustWidth = true } = {}) {
            const lineTargets = Array.from(
                contentElement.querySelectorAll('[data-quran-line-text]'),
            );
            const ayahLineTargets = Array.from(
                contentElement.querySelectorAll(
                    "[data-quran-line][data-quran-line-type='ayah'] [data-quran-line-text]",
                ),
            );
            const boundsTargets = lineTargets.length > 0 ? lineTargets : [contentElement];
            const widthTargets =
                ayahLineTargets.length >= 3
                    ? ayahLineTargets
                    : lineTargets.length > 0
                      ? lineTargets
                      : [contentElement];
            const widths = [];

            let minLeft = Number.POSITIVE_INFINITY;
            let minTop = Number.POSITIVE_INFINITY;
            let maxRight = Number.NEGATIVE_INFINITY;
            let maxBottom = Number.NEGATIVE_INFINITY;

            boundsTargets.forEach((target) => {
                const rect = target.getBoundingClientRect();

                if (rect.width <= 0 || rect.height <= 0) {
                    return;
                }

                minLeft = Math.min(minLeft, rect.left);
                minTop = Math.min(minTop, rect.top);
                maxRight = Math.max(maxRight, rect.right);
                maxBottom = Math.max(maxBottom, rect.bottom);
            });

            widthTargets.forEach((target) => {
                const rect = target.getBoundingClientRect();

                if (rect.width <= 0 || rect.height <= 0) {
                    return;
                }

                widths.push(rect.width);
            });

            if (!Number.isFinite(minLeft) || !Number.isFinite(maxRight)) {
                const fallbackRect = contentElement.getBoundingClientRect();

                return {
                    width: Math.max(1, Number(fallbackRect.width ?? 1)),
                    height: Math.max(1, Number(fallbackRect.height ?? 1)),
                    strictWidth: Math.max(1, Number(fallbackRect.width ?? 1)),
                    robustWidth: Math.max(1, Number(fallbackRect.width ?? 1)),
                };
            }

            const strictWidth = Math.max(1, maxRight - minLeft);
            let robustWidth = strictWidth;
            const shouldUseRobustWidth = Boolean(useRobustWidth) && widths.length >= 7;

            if (shouldUseRobustWidth) {
                const sortedWidths = widths.slice().sort((first, second) => first - second);
                const medianIndex = Math.floor((sortedWidths.length - 1) * 0.5);
                const quantileIndex = Math.floor(
                    (sortedWidths.length - 1) * fitRobustWidthQuantile,
                );
                const medianWidth = sortedWidths[medianIndex] ?? strictWidth;
                const quantileWidth = sortedWidths[quantileIndex] ?? strictWidth;
                const candidateRobustWidth = Math.max(quantileWidth, medianWidth * 1.02);

                if (strictWidth > candidateRobustWidth * fitRobustWidthOutlierThreshold) {
                    robustWidth = candidateRobustWidth;
                }
            }

            return {
                width: Math.max(1, robustWidth),
                height: Math.max(1, maxBottom - minTop),
                strictWidth,
                robustWidth: Math.max(1, robustWidth),
            };
        },

        resetFitLayoutVariables(rootElement) {
            this.applyFitLayoutVariables(rootElement, {
                pageTypeScale: 1,
                pageLeadingMultiplier: 1,
                pageGapMultiplier: 1,
                pageSurahHeaderScale: 1,
                basmallahBottomGapScale: defaultBasmallahBottomGapScale,
            });
        },

        resetCurrentPageFitStyles() {
            const rootElement = this.$el.firstElementChild;

            if (!(rootElement instanceof HTMLElement)) {
                this.pageScale = 1;

                return;
            }

            this.resetFitLayoutVariables(rootElement);
            this.pageScale = 1;
            this.setCurrentPageScale(1, { forFitting: true });
        },

        applyFitLayoutVariables(rootElement, layout = {}) {
            if (!(rootElement instanceof HTMLElement)) {
                return;
            }

            rootElement.style.setProperty(
                '--quran-page-type-scale',
                String(layout.pageTypeScale ?? 1),
            );
            rootElement.style.setProperty(
                '--quran-page-leading-multiplier',
                String(layout.pageLeadingMultiplier ?? 1),
            );
            rootElement.style.setProperty(
                '--quran-page-gap-multiplier',
                String(layout.pageGapMultiplier ?? 1),
            );
            rootElement.style.setProperty(
                '--quran-page-surah-header-scale',
                String(layout.pageSurahHeaderScale ?? 1),
            );
            rootElement.style.setProperty(
                '--quran-basmallah-bottom-gap-scale',
                String(layout.basmallahBottomGapScale ?? defaultBasmallahBottomGapScale),
            );
        },

        fitLayoutFromCompressionLevel(level) {
            const normalizedLevel = Math.max(0, Math.min(1, Number(level) || 0));
            const profile = this.resolveFitProfile();
            const normalizedBaseLeadingMultiplier = Math.max(
                0.2,
                Number(profile.baseLeadingMultiplier ?? 1),
            );
            const normalizedBaseGapMultiplier = Math.max(0, Number(profile.baseGapMultiplier ?? 1));
            const pageTypeScaleRaw =
                Number(profile.layoutTypeScaleBase ?? 1) +
                normalizedLevel * Number(profile.layoutTypeScaleGain ?? 0);
            const pageLeadingRaw =
                Number(profile.layoutLeadingBase ?? 1) -
                normalizedLevel * Number(profile.layoutLeadingDrop ?? 0);
            const pageGapRaw =
                Number(profile.layoutGapBase ?? 1) -
                normalizedLevel * Number(profile.layoutGapDrop ?? 0);
            const pageSurahHeaderRaw =
                Number(profile.layoutSurahHeaderBase ?? 1) -
                normalizedLevel * Number(profile.layoutSurahHeaderDrop ?? 0);
            const basmallahBottomGapRaw =
                Number(profile.layoutBasmallahBase ?? defaultBasmallahBottomGapScale) -
                normalizedLevel * Number(profile.layoutBasmallahDrop ?? 0);

            return {
                pageTypeScale: Math.min(
                    profile.compressionTypeScaleCeiling,
                    Math.max(0.2, pageTypeScaleRaw),
                ),
                pageLeadingMultiplier: Math.max(
                    0.2,
                    Math.max(profile.compressionLeadingFloor, pageLeadingRaw) *
                        normalizedBaseLeadingMultiplier,
                ),
                pageGapMultiplier: Math.max(
                    0,
                    Math.max(profile.compressionGapFloor, pageGapRaw) * normalizedBaseGapMultiplier,
                ),
                pageSurahHeaderScale: Math.max(
                    profile.compressionSurahHeaderFloor,
                    pageSurahHeaderRaw,
                ),
                basmallahBottomGapScale: basmallahBottomGapRaw,
            };
        },

        fitScore({ fillWidth, fillHeight, compressionLevel }) {
            const profile = this.resolveFitProfile();
            const normalizedFillWidth = Math.max(0, Math.min(1, Number(fillWidth) || 0));
            const normalizedFillHeight = Math.max(0, Math.min(1, Number(fillHeight) || 0));
            const normalizedCompression = Math.max(0, Math.min(1, Number(compressionLevel) || 0));
            const minimumFill = Math.min(normalizedFillWidth, normalizedFillHeight);
            const areaFill = normalizedFillWidth * normalizedFillHeight;
            const balancePenalty = Math.abs(normalizedFillWidth - normalizedFillHeight);
            const widthDeficit = Math.max(0, profile.targetWidthRatio - normalizedFillWidth);
            const heightDeficit = Math.max(0, profile.targetHeightRatio - normalizedFillHeight);

            return (
                minimumFill * 0.48 +
                Math.sqrt(Math.max(0, areaFill)) * 0.16 -
                balancePenalty * 0.03 -
                widthDeficit * profile.widthDeficitWeight -
                heightDeficit * profile.heightDeficitWeight -
                normalizedCompression * profile.compressionPenaltyWeight
            );
        },

        resolveFitProfile() {
            const lines = Array.isArray(this.mushafLines) ? this.mushafLines : [];
            const pageNumber = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const breakpointName = String(this.resolveCurrentBreakpointName() ?? '').trim();
            const isTabletBreakpoint = ['sm', 'md', 'lg'].includes(breakpointName);
            const ayahLines = lines.filter((line) => String(line?.line_type ?? '') === 'ayah');
            const ayahLineCount = ayahLines.length;
            const centeredAyahLineCount = ayahLines.filter((line) =>
                Boolean(line?.is_centered),
            ).length;
            const surahHeaderCount = lines.filter(
                (line) => String(line?.line_type ?? '') === 'surah_name',
            ).length;
            const basmallahCount = lines.filter(
                (line) => String(line?.line_type ?? '') === 'basmallah',
            ).length;
            const centeredAyahRatio = ayahLineCount > 0 ? centeredAyahLineCount / ayahLineCount : 0;
            const hasOpeningSpreadSignature =
                ayahLineCount > 0 &&
                ayahLineCount <= 9 &&
                lines.length <= 12 &&
                surahHeaderCount <= 1 &&
                basmallahCount <= 1 &&
                centeredAyahRatio >= 0.45;
            const isOpeningSpread = pageNumber <= 2 && hasOpeningSpreadSignature;
            const isLineHeavyCenteredPage =
                !isOpeningSpread &&
                surahHeaderCount === 0 &&
                basmallahCount === 0 &&
                ayahLineCount >= 14 &&
                centeredAyahRatio >= 0.85;
            const isMultiSurahSegmentedPage =
                !isOpeningSpread && surahHeaderCount >= 2 && basmallahCount >= 2;
            const isSingleHeaderLongContentPage =
                !isOpeningSpread &&
                surahHeaderCount === 1 &&
                basmallahCount >= 1 &&
                ayahLineCount >= 10;

            if (isOpeningSpread) {
                const openingSpreadProfile = {
                    ...fitDefaultProfile,
                    compressionLeadingFloor: 0.92,
                    compressionGapFloor: 0.34,
                    compressionSurahHeaderFloor: 0.9,
                    compressionTypeScaleCeiling: 0.6,
                    layoutTypeScaleBase: 0.56,
                    layoutTypeScaleGain: 0.03,
                    layoutLeadingBase: 1,
                    layoutLeadingDrop: 0.04,
                    layoutGapBase: 0.5,
                    layoutGapDrop: 0.1,
                    layoutSurahHeaderBase: 1,
                    layoutSurahHeaderDrop: 0.03,
                    layoutBasmallahBase: -0.18,
                    layoutBasmallahDrop: 0.04,
                    baseLeadingMultiplier: 1,
                    baseGapMultiplier: 1,
                    minimumCompressionLevel: 0,
                    targetWidthRatio: 0.68,
                    targetHeightRatio: 0.76,
                    widthDeficitWeight: 0.34,
                    heightDeficitWeight: 0.14,
                    compressionPenaltyWeight: 0.01,
                    strictWidthOverflowTolerance: 1.03,
                    strictHeightOverflowTolerance: 1.0,
                    candidateSteps: 24,
                    maxScaleMultiplier: 0.56,
                };

                if (!isTabletBreakpoint) {
                    return openingSpreadProfile;
                }

                return {
                    ...openingSpreadProfile,
                    compressionTypeScaleCeiling: 0.53,
                    layoutTypeScaleBase: 0.5,
                    layoutTypeScaleGain: 0.02,
                    layoutLeadingBase: 1.08,
                    layoutLeadingDrop: 0.03,
                    layoutGapBase: 0.62,
                    layoutGapDrop: 0.08,
                    minimumCompressionLevel: 0,
                    targetWidthRatio: 0.62,
                    targetHeightRatio: 0.72,
                    maxScaleMultiplier: 0.44,
                };
            }

            if (isLineHeavyCenteredPage) {
                const lineHeavyProfile = {
                    ...fitDefaultProfile,
                    compressionLeadingFloor: 0.52,
                    compressionGapFloor: 0.1,
                    compressionSurahHeaderFloor: 0.58,
                    compressionTypeScaleCeiling: 1.92,
                    layoutTypeScaleBase: 1.774286,
                    layoutTypeScaleGain: 0.16,
                    layoutLeadingBase: 0.88,
                    layoutLeadingDrop: 0.22,
                    layoutGapBase: 0.428571,
                    layoutGapDrop: 0.26,
                    layoutSurahHeaderBase: 0.92,
                    layoutSurahHeaderDrop: 0.1,
                    layoutBasmallahBase: -0.27714285714285714,
                    layoutBasmallahDrop: 0.12,
                    baseLeadingMultiplier: 1,
                    baseGapMultiplier: 1,
                    minimumCompressionLevel: 0.48,
                    targetWidthRatio: 0.95,
                    targetHeightRatio: 0.9,
                    widthDeficitWeight: 0.86,
                    heightDeficitWeight: 0.05,
                    compressionPenaltyWeight: 0,
                    strictWidthOverflowTolerance: 1.04,
                    strictHeightOverflowTolerance: 1.0,
                    candidateSteps: 36,
                    maxScaleMultiplier: 1.08,
                };

                if (!isTabletBreakpoint) {
                    return lineHeavyProfile;
                }

                return {
                    ...lineHeavyProfile,
                    compressionTypeScaleCeiling: 1.56,
                    layoutTypeScaleBase: 1.42,
                    layoutTypeScaleGain: 0.09,
                    layoutLeadingBase: 1.02,
                    layoutLeadingDrop: 0.12,
                    layoutGapBase: 0.66,
                    layoutGapDrop: 0.18,
                    minimumCompressionLevel: 0.06,
                    targetWidthRatio: 0.88,
                    targetHeightRatio: 0.86,
                    widthDeficitWeight: 0.72,
                    heightDeficitWeight: 0.07,
                    compressionPenaltyWeight: 0,
                    maxScaleMultiplier: 0.9,
                };
            }

            if (isMultiSurahSegmentedPage) {
                const segmentedProfile = {
                    ...fitDefaultProfile,
                    compressionLeadingFloor: 0.66,
                    compressionGapFloor: 0.34,
                    compressionSurahHeaderFloor: 0.72,
                    compressionTypeScaleCeiling: 1.42,
                    layoutTypeScaleBase: 1.1207142857142858,
                    layoutTypeScaleGain: 0.14,
                    layoutLeadingBase: 0.8049999999999999,
                    layoutLeadingDrop: 0.1,
                    layoutGapBase: 0.5589285714285714,
                    layoutGapDrop: 0.18,
                    layoutSurahHeaderBase: 0.87,
                    layoutSurahHeaderDrop: 0.08,
                    layoutBasmallahBase: -0.33785714285714286,
                    layoutBasmallahDrop: 0.1,
                    minimumCompressionLevel: 0.18,
                    targetWidthRatio: 0.88,
                    targetHeightRatio: 0.94,
                    widthDeficitWeight: 0.42,
                    heightDeficitWeight: 0.14,
                    compressionPenaltyWeight: 0.008,
                    candidateSteps: 30,
                    maxScaleMultiplier: 1,
                };

                if (!isTabletBreakpoint) {
                    return segmentedProfile;
                }

                return {
                    ...segmentedProfile,
                    compressionTypeScaleCeiling: 1.12,
                    layoutTypeScaleBase: 0.98,
                    layoutTypeScaleGain: 0.08,
                    layoutLeadingBase: 0.96,
                    layoutLeadingDrop: 0.06,
                    layoutGapBase: 0.74,
                    layoutGapDrop: 0.1,
                    layoutSurahHeaderBase: 0.92,
                    layoutSurahHeaderDrop: 0.05,
                    layoutBasmallahBase: -0.24,
                    layoutBasmallahDrop: 0.07,
                    minimumCompressionLevel: 0,
                    targetWidthRatio: 0.84,
                    targetHeightRatio: 0.9,
                    widthDeficitWeight: 0.36,
                    heightDeficitWeight: 0.16,
                    compressionPenaltyWeight: 0.014,
                    maxScaleMultiplier: 0.84,
                };
            }

            if (isSingleHeaderLongContentPage && isTabletBreakpoint) {
                return {
                    ...fitDefaultProfile,
                    compressionLeadingFloor: 0.7,
                    compressionGapFloor: 0.34,
                    compressionSurahHeaderFloor: 0.78,
                    compressionTypeScaleCeiling: 1.12,
                    layoutTypeScaleBase: 0.98,
                    layoutTypeScaleGain: 0.08,
                    layoutLeadingBase: 0.97,
                    layoutLeadingDrop: 0.07,
                    layoutGapBase: 0.72,
                    layoutGapDrop: 0.11,
                    layoutSurahHeaderBase: 0.9,
                    layoutSurahHeaderDrop: 0.05,
                    layoutBasmallahBase: -0.24,
                    layoutBasmallahDrop: 0.07,
                    minimumCompressionLevel: 0.02,
                    targetWidthRatio: 0.84,
                    targetHeightRatio: 0.9,
                    widthDeficitWeight: 0.38,
                    heightDeficitWeight: 0.14,
                    compressionPenaltyWeight: 0.01,
                    candidateSteps: 30,
                    maxScaleMultiplier: 0.86,
                };
            }

            return { ...fitDefaultProfile };
        },

        fitSanityContextKey({
            pageNumber = this.pageNumber,
            availableWidth = 0,
            availableHeight = 0,
            minimumFillWidth = 0,
            minimumFillHeight = 0,
            strictWidthOverflowTolerance = 1.06,
            strictHeightOverflowTolerance = 1.01,
        } = {}) {
            const normalizedPageNumber = Math.max(
                1,
                Math.trunc(Number(pageNumber ?? this.pageNumber)),
            );
            const normalizedAvailableWidth = Math.max(1, Number(availableWidth) || 1);
            const normalizedAvailableHeight = Math.max(1, Number(availableHeight) || 1);
            const breakpointName = this.resolveCurrentBreakpointName();

            return [
                normalizedPageNumber,
                breakpointName || 'bp-unknown',
                this.viewportBucketValue(normalizedAvailableWidth),
                this.viewportBucketValue(normalizedAvailableHeight),
                Number(minimumFillWidth ?? 0).toFixed(3),
                Number(minimumFillHeight ?? 0).toFixed(3),
                Number(strictWidthOverflowTolerance ?? 1.06).toFixed(3),
                Number(strictHeightOverflowTolerance ?? 1.01).toFixed(3),
                this.useCenteredAyahLayout ? 'centered' : 'rect',
                this.mushafLines.length,
                String(this.qpcPageFontFamily ?? ''),
            ].join('|');
        },

        recordFitSanityRecoveryAttempt({
            contextKey = '',
            measuredWidth = 0,
            measuredHeight = 0,
            hasOverflow = false,
            hasSuspiciousUnderfill = false,
        } = {}) {
            const normalizedContextKey = String(contextKey ?? '').trim();
            const normalizedMeasuredWidth = Math.max(0, Number(measuredWidth) || 0);
            const normalizedMeasuredHeight = Math.max(0, Number(measuredHeight) || 0);
            const normalizedOutcome = `${hasOverflow ? 'overflow' : 'no-overflow'}|${
                hasSuspiciousUnderfill ? 'underfill' : 'no-underfill'
            }`;
            const now = Date.now();
            const sameContext =
                normalizedContextKey !== '' && this._fitSanityContextKey === normalizedContextKey;

            if (sameContext && this._fitSanityDisabledContextKey === normalizedContextKey) {
                return {
                    shouldSuppressRecovery: true,
                    reason: 'disabled-context',
                    attemptCount: this._fitSanityContextAttemptCount,
                };
            }

            if (sameContext && now < this._fitSanitySuppressedUntil) {
                return {
                    shouldSuppressRecovery: true,
                    reason: 'cooldown',
                    attemptCount: this._fitSanityContextAttemptCount,
                };
            }

            if (!sameContext) {
                this._fitSanityContextKey = normalizedContextKey;
                this._fitSanityContextAttemptCount = 1;
                this._fitSanityDisabledContextKey = '';
            } else {
                const widthDelta = Math.abs(
                    normalizedMeasuredWidth - this._fitSanityContextLastWidth,
                );
                const heightDelta = Math.abs(
                    normalizedMeasuredHeight - this._fitSanityContextLastHeight,
                );
                const hasStableMeasurements =
                    widthDelta < 6 &&
                    heightDelta < 6 &&
                    this._fitSanityContextOutcome === normalizedOutcome;

                this._fitSanityContextAttemptCount = hasStableMeasurements
                    ? this._fitSanityContextAttemptCount + 1
                    : 1;
            }

            this._fitSanityContextLastWidth = normalizedMeasuredWidth;
            this._fitSanityContextLastHeight = normalizedMeasuredHeight;
            this._fitSanityContextOutcome = normalizedOutcome;

            const attemptLimit = hasOverflow ? 4 : hasSuspiciousUnderfill ? 3 : 2;

            if (this._fitSanityContextAttemptCount > attemptLimit) {
                this._fitSanitySuppressedUntil = now + 2600;
                this._fitSanityDisabledContextKey = normalizedContextKey;

                return {
                    shouldSuppressRecovery: true,
                    reason: 'stable-loop',
                    attemptCount: this._fitSanityContextAttemptCount,
                };
            }

            return {
                shouldSuppressRecovery: false,
                reason: '',
                attemptCount: this._fitSanityContextAttemptCount,
            };
        },

        scheduleFitSanityCheck({
            cacheKey = '',
            pageNumber = this.pageNumber,
            availableWidth = 0,
            availableHeight = 0,
            strictWidthOverflowTolerance = 1.06,
            strictHeightOverflowTolerance = 1.01,
            minimumFillWidth = 0.32,
            minimumFillHeight = 0.22,
        } = {}) {
            const normalizedCacheKey = String(cacheKey ?? '').trim();
            const normalizedPageNumber = Math.max(
                1,
                Math.trunc(Number(pageNumber ?? this.pageNumber)),
            );
            const normalizedAvailableWidth = Math.max(1, Number(availableWidth) || 1);
            const normalizedAvailableHeight = Math.max(1, Number(availableHeight) || 1);
            const widthOverflowThreshold =
                normalizedAvailableWidth * Number(strictWidthOverflowTolerance ?? 1.06);
            const heightOverflowThreshold =
                normalizedAvailableHeight * Number(strictHeightOverflowTolerance ?? 1.01);
            const normalizedMinimumFillWidth = Math.max(
                0.1,
                Math.min(1, Number(minimumFillWidth ?? 0.32)),
            );
            const normalizedMinimumFillHeight = Math.max(
                0.1,
                Math.min(1, Number(minimumFillHeight ?? 0.22)),
            );

            if (this.hasManualPageLayoutAdjustments()) {
                return;
            }

            const fitSanityContextKey = this.fitSanityContextKey({
                pageNumber: normalizedPageNumber,
                availableWidth: normalizedAvailableWidth,
                availableHeight: normalizedAvailableHeight,
                minimumFillWidth: normalizedMinimumFillWidth,
                minimumFillHeight: normalizedMinimumFillHeight,
                strictWidthOverflowTolerance: Number(strictWidthOverflowTolerance ?? 1.06),
                strictHeightOverflowTolerance: Number(strictHeightOverflowTolerance ?? 1.01),
            });

            if (this._fitSanityCheckTimer !== null) {
                clearTimeout(this._fitSanityCheckTimer);
                this._fitSanityCheckTimer = null;
            }

            this._fitSanityCheckTimer = window.setTimeout(() => {
                this._fitSanityCheckTimer = null;

                if (
                    Math.max(1, Math.trunc(Number(this.pageNumber ?? 1))) !== normalizedPageNumber
                ) {
                    return;
                }

                // Do not run post-reveal fit adjustments.
                if (this.isCurrentPageVisiblyReady() && this.pageFitState() === 'ready') {
                    return;
                }

                const contentElement = this.$refs.pageContent;

                if (!(contentElement instanceof Element)) {
                    return;
                }

                const measured = this.measureRenderedBounds(contentElement, {
                    useRobustWidth: false,
                });
                const hasOverflow =
                    measured.width > widthOverflowThreshold ||
                    measured.height > heightOverflowThreshold;
                const fillWidth = measured.width / normalizedAvailableWidth;
                const fillHeight = measured.height / normalizedAvailableHeight;
                const hasSuspiciousUnderfill =
                    fillWidth < normalizedMinimumFillWidth ||
                    fillHeight < normalizedMinimumFillHeight;

                if (!hasOverflow && !hasSuspiciousUnderfill) {
                    this._fitSanityContextKey = '';
                    this._fitSanityContextAttemptCount = 0;
                    this._fitSanityContextLastWidth = 0;
                    this._fitSanityContextLastHeight = 0;
                    this._fitSanityContextOutcome = '';
                    this._fitSanitySuppressedUntil = 0;
                    this._fitSanityDisabledContextKey = '';

                    return;
                }

                if (
                    this._fitSanityContextKey === fitSanityContextKey &&
                    Date.now() < this._fitSanitySuppressedUntil
                ) {
                    return;
                }

                const isLayoutPipelineBusy =
                    this.isLoadingPage ||
                    this.isFittingPage ||
                    this._layoutActivePromise !== null ||
                    this._revealTimer !== null ||
                    this._pendingNavigationRequest !== null ||
                    this._navigationRevealLocked ||
                    this._isModalLifecycleSettling ||
                    this._activeModalIds.size > 0 ||
                    this.openModalCount() > 0;

                if (isLayoutPipelineBusy) {
                    this._fitSanityCheckTimer = window.setTimeout(() => {
                        this._fitSanityCheckTimer = null;
                        this.scheduleFitSanityCheck({
                            cacheKey: normalizedCacheKey,
                            pageNumber: normalizedPageNumber,
                            availableWidth: normalizedAvailableWidth,
                            availableHeight: normalizedAvailableHeight,
                            strictWidthOverflowTolerance: Number(
                                strictWidthOverflowTolerance ?? 1.06,
                            ),
                            strictHeightOverflowTolerance: Number(
                                strictHeightOverflowTolerance ?? 1.01,
                            ),
                            minimumFillWidth: normalizedMinimumFillWidth,
                            minimumFillHeight: normalizedMinimumFillHeight,
                        });
                    }, 180);

                    return;
                }

                const fitRecoveryDecision = this.recordFitSanityRecoveryAttempt({
                    contextKey: fitSanityContextKey,
                    measuredWidth: measured.width,
                    measuredHeight: measured.height,
                    hasOverflow,
                    hasSuspiciousUnderfill,
                });

                if (fitRecoveryDecision.shouldSuppressRecovery) {
                    if (this.hasRenderablePage() && !this.isLoadingPage) {
                        this.forceRevealCurrentPage('fit-sanity-churn-circuit-breaker');
                    }

                    this.traceReaderReveal('fit-sanity-recovery-suppressed', {
                        reason: fitRecoveryDecision.reason,
                        attemptCount: fitRecoveryDecision.attemptCount,
                        fillWidth,
                        fillHeight,
                        hasOverflow,
                        hasSuspiciousUnderfill,
                    });

                    return;
                }

                if (normalizedCacheKey !== '') {
                    this.forgetFitResult(normalizedCacheKey);
                }

                if (this.isCurrentPageVisiblyReady()) {
                    this._bypassNextFitCache = true;
                    this.fitPageToViewport();
                    this._lastPageRevealAt = Date.now();

                    return;
                }

                this.scheduleLayout({
                    revealDelayMs: 120,
                    maxAttempts: 5,
                });
            }, 160);
        },

        applySafetyScaleForCurrentPageOverflow() {
            const rootElement = this.$el.firstElementChild;
            const frameElement = this.$refs.pageFrame;
            const contentElement = this.$refs.pageContent;

            if (!rootElement || !frameElement || !contentElement) {
                return false;
            }

            if (this.hasManualPageLayoutAdjustments()) {
                return false;
            }

            const frameRect = frameElement.getBoundingClientRect();
            const frameParentRect = frameElement.parentElement?.getBoundingClientRect?.() ?? null;
            const availableWidth = Math.max(
                1,
                Number(
                    frameParentRect?.width ??
                        frameRect?.width ??
                        frameElement.parentElement?.clientWidth ??
                        frameElement.clientWidth ??
                        1,
                ),
            );
            const computedRootStyles = window.getComputedStyle(rootElement);
            const fitAreaPaddingX = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-area-pad-x',
                    rootElement,
                    0,
                ),
            );
            const fitAreaPaddingY = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-area-pad-y',
                    rootElement,
                    0,
                ),
            );
            const immersiveFitTopPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          computedRootStyles,
                          '--quran-immersive-page-pad-top',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const immersiveFitBottomPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          computedRootStyles,
                          '--quran-immersive-page-pad-bottom',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const fitHeightRatio = Math.min(
                1,
                Math.max(
                    0.5,
                    Number.parseFloat(
                        computedRootStyles.getPropertyValue('--quran-fit-height-ratio'),
                    ) || 1,
                ),
            );
            const availableHeight = Math.max(
                1,
                (Number(frameRect?.height ?? frameElement.clientHeight ?? 1) -
                    fitAreaPaddingY * 2 -
                    immersiveFitTopPadding -
                    immersiveFitBottomPadding) *
                    fitHeightRatio,
            );
            const adjustedAvailableWidth = Math.max(1, availableWidth - fitAreaPaddingX * 2);
            const minScale = Math.max(
                0.05,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-min-page-scale')) ||
                    0.1,
            );
            const maxScale = Math.max(
                minScale,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-max-page-scale')) ||
                    1,
            );
            const measured = this.measureRenderedBounds(contentElement, {
                useRobustWidth: false,
            });

            if (measured.width <= 1 || measured.height <= 1) {
                return false;
            }

            const adjustScale = Math.min(
                adjustedAvailableWidth / Math.max(1, measured.width),
                availableHeight / Math.max(1, measured.height),
            );

            if (!Number.isFinite(adjustScale) || adjustScale >= 0.995) {
                return false;
            }

            const currentScale = Math.max(
                minScale,
                Math.min(
                    maxScale,
                    Number.parseFloat(
                        contentElement.style.getPropertyValue('--quran-page-scale'),
                    ) ||
                        Number(this.pageScale) ||
                        1,
                ),
            );
            const nextScale = Math.max(
                minScale,
                Math.min(maxScale, Number((currentScale * adjustScale).toFixed(4))),
            );

            this.pageScale = nextScale;
            this.setCurrentPageScale(nextScale, { forFitting: true });

            return true;
        },

        fitPageToViewport() {
            const rootElement = this.$el.firstElementChild;
            const frameElement = this.$refs.pageFrame;
            const contentElement = this.$refs.pageContent;

            if (!rootElement || !frameElement || !contentElement) {
                this.qrDebugLog('[QR:fitPageToViewport] missing element refs');
                return;
            }

            // Always start from CSS-declared defaults to avoid cross-page drift from
            // previously applied inline fit values.
            rootElement.style.removeProperty('--quran-page-type-scale');
            rootElement.style.removeProperty('--quran-page-leading-multiplier');
            rootElement.style.removeProperty('--quran-page-gap-multiplier');
            rootElement.style.removeProperty('--quran-page-surah-header-scale');
            rootElement.style.removeProperty('--quran-basmallah-bottom-gap-scale');

            const frameRect = frameElement.getBoundingClientRect();
            const frameParentRect = frameElement.parentElement?.getBoundingClientRect?.() ?? null;
            const computedRootStyles = window.getComputedStyle(rootElement);
            const computedContentStyles = window.getComputedStyle(contentElement);
            const readCssNumber = (propertyName, fallback) => {
                const contentRawValue = Number.parseFloat(
                    computedContentStyles.getPropertyValue(propertyName),
                );

                if (Number.isFinite(contentRawValue)) {
                    return contentRawValue;
                }

                const rootRawValue = Number.parseFloat(
                    computedRootStyles.getPropertyValue(propertyName),
                );

                return Number.isFinite(rootRawValue) ? rootRawValue : fallback;
            };
            const breakpointName = this.resolveCurrentBreakpointName();
            const isBaseBreakpoint = breakpointName === 'base';
            const isTabletBreakpoint = ['sm', 'md', 'lg'].includes(breakpointName);
            const canUseGlobalFitCalibration = ['xl', '2xl', '3xl', '4xl'].includes(
                String(breakpointName ?? '').trim(),
            );
            const cssBaselineLayout = {
                pageTypeScale: Math.max(0.2, readCssNumber('--quran-page-type-scale', 1)),
                pageLeadingMultiplier: Math.max(
                    0.25,
                    readCssNumber('--quran-page-leading-multiplier', 1),
                ),
                pageGapMultiplier: Math.max(0, readCssNumber('--quran-page-gap-multiplier', 1)),
                pageSurahHeaderScale: Math.max(
                    0.5,
                    readCssNumber('--quran-page-surah-header-scale', 1),
                ),
                basmallahBottomGapScale: readCssNumber(
                    '--quran-basmallah-bottom-gap-scale',
                    defaultBasmallahBottomGapScale,
                ),
            };
            const calibrationLayout =
                canUseGlobalFitCalibration &&
                this._globalFitCalibrationLayout &&
                typeof this._globalFitCalibrationLayout === 'object'
                    ? this._globalFitCalibrationLayout
                    : null;
            const baselineLayout = calibrationLayout
                ? {
                      pageTypeScale: Math.max(
                          0.2,
                          Number(
                              calibrationLayout.pageTypeScale ?? cssBaselineLayout.pageTypeScale,
                          ),
                      ),
                      pageLeadingMultiplier: Math.max(
                          0.25,
                          Number(
                              calibrationLayout.pageLeadingMultiplier ??
                                  cssBaselineLayout.pageLeadingMultiplier,
                          ),
                      ),
                      pageGapMultiplier: Math.max(
                          0,
                          Number(
                              calibrationLayout.pageGapMultiplier ??
                                  cssBaselineLayout.pageGapMultiplier,
                          ),
                      ),
                      pageSurahHeaderScale: Math.max(
                          0.5,
                          Number(
                              calibrationLayout.pageSurahHeaderScale ??
                                  cssBaselineLayout.pageSurahHeaderScale,
                          ),
                      ),
                      basmallahBottomGapScale: Number(
                          calibrationLayout.basmallahBottomGapScale ??
                              cssBaselineLayout.basmallahBottomGapScale,
                      ),
                  }
                : { ...cssBaselineLayout };

            this.applyFitLayoutVariables(rootElement, baselineLayout);
            this.setCurrentPageScale(1, { forFitting: true });
            const rawAvailableWidth = Math.max(
                1,
                Number(
                    frameParentRect?.width ??
                        frameRect?.width ??
                        frameElement.parentElement?.clientWidth ??
                        frameElement.clientWidth ??
                        1,
                ),
            );
            const fitTargetWidthRatio = Math.min(
                0.95,
                Math.max(
                    0.55,
                    Number.parseFloat(
                        computedRootStyles.getPropertyValue('--quran-fit-target-width-ratio'),
                    ) || 0.8,
                ),
            );
            const fitAreaPaddingX = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-area-pad-x',
                    rootElement,
                    0,
                ),
            );
            const fitAreaPaddingY = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-area-pad-y',
                    rootElement,
                    0,
                ),
            );
            const fitTopClearance = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-top-clearance',
                    rootElement,
                    0,
                ),
            );
            const fitBottomClearance = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-bottom-clearance',
                    rootElement,
                    8,
                ),
            );
            const immersiveFitTopPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          computedRootStyles,
                          '--quran-immersive-page-pad-top',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const immersiveFitBottomPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          computedRootStyles,
                          '--quran-immersive-page-pad-bottom',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const availableWidth = Math.max(1, rawAvailableWidth - fitAreaPaddingX * 2);
            const fitHeightRatio = Math.min(
                1,
                Math.max(
                    0.7,
                    Number.parseFloat(
                        computedRootStyles.getPropertyValue('--quran-fit-height-ratio'),
                    ) || 1,
                ),
            );
            const frameAreaTop =
                Number(frameRect?.top ?? 0) +
                fitAreaPaddingY +
                fitTopClearance +
                immersiveFitTopPadding;
            let frameAreaBottom =
                Number(frameRect?.bottom ?? 0) > 0
                    ? Number(frameRect.bottom) - fitAreaPaddingY - immersiveFitBottomPadding
                    : Number(frameRect?.top ?? 0) +
                      Number(frameRect?.height ?? frameElement.clientHeight ?? 1) -
                      fitAreaPaddingY -
                      immersiveFitBottomPadding;
            const protectedBottomElements = this.shouldUseImmersiveReaderChrome()
                ? []
                : [
                      rootElement.querySelector('.quran-page-slider-chip'),
                      rootElement.querySelector('.quran-page-slider'),
                  ];

            protectedBottomElements.forEach((element) => {
                if (!(element instanceof Element)) {
                    return;
                }

                const elementRect = element.getBoundingClientRect();

                if (
                    elementRect.width <= 0 ||
                    elementRect.height <= 0 ||
                    elementRect.top <= frameAreaTop ||
                    elementRect.top > frameAreaBottom + fitBottomClearance * 1.5
                ) {
                    return;
                }

                frameAreaBottom = Math.min(frameAreaBottom, elementRect.top - fitBottomClearance);
            });

            const rawAvailableHeight = Math.max(1, frameAreaBottom - frameAreaTop);
            const availableHeight = Math.max(1, rawAvailableHeight * fitHeightRatio);
            const targetWidth = Math.max(1, availableWidth * fitTargetWidthRatio);
            const targetHeight = Math.max(1, availableHeight);
            const targetAreaLeft =
                Number(frameRect?.left ?? 0) +
                fitAreaPaddingX +
                (availableWidth - targetWidth) * 0.5;
            const targetAreaRight = targetAreaLeft + targetWidth;
            const targetAreaTop = frameAreaTop;
            const targetAreaBottom = targetAreaTop + targetHeight;
            const minScale = Math.max(
                0.05,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-min-page-scale')) ||
                    0.1,
            );
            let maxScale = Math.max(
                minScale,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-max-page-scale')) ||
                    1,
            );
            const activeFitProfile = this.resolveFitProfile();
            const profileMaxScaleMultiplier = Math.max(
                0.1,
                Number(activeFitProfile?.maxScaleMultiplier ?? 1),
            );
            maxScale = Math.max(minScale, maxScale * profileMaxScaleMultiplier);
            const hasGlobalCalibrationProfile =
                canUseGlobalFitCalibration &&
                this._globalFitCalibrationLayout &&
                typeof this._globalFitCalibrationLayout === 'object' &&
                Number.isFinite(Number(this._globalFitCalibrationScale)) &&
                Number(this._globalFitCalibrationScale) > 0;

            this.qrDebugLog(
                '[QR:fitPageToViewport] hasGlobalCalibration:',
                hasGlobalCalibrationProfile,
                'bypass:',
                this._bypassNextFitCache,
                'frameW:',
                frameRect?.width,
                'frameH:',
                frameRect?.height,
                'bp:',
                breakpointName,
                'page:',
                this.pageNumber,
                'visible:',
                this.isReaderElementVisible(),
            );

            if (hasGlobalCalibrationProfile) {
                const globalLayout = {
                    pageTypeScale: Math.max(
                        0.2,
                        Number(this._globalFitCalibrationLayout.pageTypeScale ?? 1),
                    ),
                    pageLeadingMultiplier: Math.max(
                        0.25,
                        Number(this._globalFitCalibrationLayout.pageLeadingMultiplier ?? 1),
                    ),
                    pageGapMultiplier: Math.max(
                        0,
                        Number(this._globalFitCalibrationLayout.pageGapMultiplier ?? 1),
                    ),
                    pageSurahHeaderScale: Math.max(
                        0.5,
                        Number(this._globalFitCalibrationLayout.pageSurahHeaderScale ?? 1),
                    ),
                    basmallahBottomGapScale: Number(
                        this._globalFitCalibrationLayout.basmallahBottomGapScale ??
                            defaultBasmallahBottomGapScale,
                    ),
                };
                const globalScale = Math.max(
                    minScale,
                    Math.min(maxScale, Number(this._globalFitCalibrationScale)),
                );

                this.applyFitLayoutVariables(rootElement, globalLayout);
                this.pageScale = globalScale;
                this.setCurrentPageScale(globalScale);
                this._bypassNextFitCache = false;
                this._fitRunCounter += 1;
                this._lastFittedPageNumber = this.pageNumber;

                return;
            }
            const minimumLeadingMultiplier = Math.max(
                0.35,
                Math.min(
                    baselineLayout.pageLeadingMultiplier,
                    readCssNumber(
                        '--quran-min-page-leading-multiplier',
                        baselineLayout.pageLeadingMultiplier,
                    ),
                ),
            );
            const minimumGapMultiplier = Math.max(
                0,
                Math.min(
                    baselineLayout.pageGapMultiplier,
                    readCssNumber(
                        '--quran-min-page-gap-multiplier',
                        baselineLayout.pageGapMultiplier,
                    ),
                ),
            );
            const minimumSurahHeaderScale = Math.max(
                0.5,
                Math.min(
                    baselineLayout.pageSurahHeaderScale,
                    readCssNumber(
                        '--quran-min-page-surah-header-scale',
                        baselineLayout.pageSurahHeaderScale,
                    ),
                ),
            );
            const minimumBasmallahBottomGapScale = Math.min(
                baselineLayout.basmallahBottomGapScale,
                readCssNumber(
                    '--quran-min-basmallah-bottom-gap-scale',
                    baselineLayout.basmallahBottomGapScale - 0.12,
                ),
            );
            const normalizedPageNumber = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const isModalLayoutContext =
                this._bypassNextFitCache ||
                this._isModalLifecycleSettling ||
                this._activeModalIds.size > 0 ||
                this.openModalCount() > 0;
            const isFontLayoutPending = !this.areTrackedPageFontsLoaded();
            const shouldBypassFitCache = isModalLayoutContext || isFontLayoutPending;
            const shouldSuppressPersistedCacheWrite = shouldBypassFitCache;
            const ayahLineCount = Array.isArray(this.mushafLines)
                ? this.mushafLines.filter((line) => String(line?.line_type ?? '') === 'ayah').length
                : 0;
            const targetMinimumFillWidth =
                ayahLineCount >= 10 ? 0.9 : ayahLineCount >= 6 ? 0.86 : 0.8;
            const targetMinimumFillHeight = Math.max(
                0.64,
                Math.min(0.9, Number(activeFitProfile?.targetHeightRatio ?? 0.84) - 0.08),
            );
            const fitCacheKey = [
                normalizedPageNumber,
                breakpointName || 'bp-unknown',
                this.viewportBucketValue(availableWidth),
                this.viewportBucketValue(availableHeight),
                isModalLayoutContext ? 'modal-open' : 'modal-closed',
                isFontLayoutPending ? 'fonts-pending' : 'fonts-loaded',
                this.useCenteredAyahLayout ? 'centered' : 'rect',
                'scale-only-v4',
                Number(fitTargetWidthRatio).toFixed(3),
                Number(fitAreaPaddingX).toFixed(2),
                Number(fitAreaPaddingY).toFixed(2),
                Number(fitTopClearance).toFixed(2),
                Number(fitBottomClearance).toFixed(2),
                Number(immersiveFitTopPadding).toFixed(2),
                Number(immersiveFitBottomPadding).toFixed(2),
                Number(minimumLeadingMultiplier).toFixed(3),
                Number(minimumGapMultiplier).toFixed(3),
                Number(minimumSurahHeaderScale).toFixed(3),
                Number(minimumBasmallahBottomGapScale).toFixed(3),
                this._globalFitCalibrationPageNumber > 0
                    ? `cal-${this._globalFitCalibrationPageNumber}`
                    : 'cal-none',
                String(this.qpcPageFontFamily ?? ''),
                String(this.basmallahFontFamily ?? ''),
                String(this.surahHeaderFontFamily ?? ''),
                Number(minScale).toFixed(3),
                Number(maxScale).toFixed(3),
            ].join('|');

            const strictWidthOverflowTolerance = 1.0025;
            const strictHeightOverflowTolerance = 1.0025;
            const strictWidthOverflowThreshold = targetWidth * strictWidthOverflowTolerance;
            const strictHeightOverflowThreshold = targetHeight * strictHeightOverflowTolerance;
            const minimumHealthyFillWidth = Math.max(0.5, targetMinimumFillWidth - 0.1);
            const minimumHealthyFillHeight = Math.max(0.56, targetMinimumFillHeight - 0.08);
            const measuredLineTargets = Array.from(
                contentElement.querySelectorAll('[data-quran-line-text]'),
            );
            const measuredAyahLineTargets = Array.from(
                contentElement.querySelectorAll(
                    "[data-quran-line][data-quran-line-type='ayah'] [data-quran-line-text]",
                ),
            );
            const measuredBoundsTargets =
                measuredLineTargets.length > 0 ? measuredLineTargets : [contentElement];
            const measuredWidthTargets =
                measuredAyahLineTargets.length >= 3
                    ? measuredAyahLineTargets
                    : measuredBoundsTargets;

            const measureVisualBounds = () => {
                const widths = [];
                let minLeft = Number.POSITIVE_INFINITY;
                let minTop = Number.POSITIVE_INFINITY;
                let maxRight = Number.NEGATIVE_INFINITY;
                let maxBottom = Number.NEGATIVE_INFINITY;

                measuredBoundsTargets.forEach((target) => {
                    const rect = target.getBoundingClientRect();

                    if (rect.width <= 0 || rect.height <= 0) {
                        return;
                    }

                    minLeft = Math.min(minLeft, rect.left);
                    minTop = Math.min(minTop, rect.top);
                    maxRight = Math.max(maxRight, rect.right);
                    maxBottom = Math.max(maxBottom, rect.bottom);
                });

                measuredWidthTargets.forEach((target) => {
                    const rect = target.getBoundingClientRect();

                    if (rect.width <= 0 || rect.height <= 0) {
                        return;
                    }

                    widths.push(rect.width);
                });

                if (!Number.isFinite(minLeft) || !Number.isFinite(maxRight)) {
                    const fallbackRect = contentElement.getBoundingClientRect();

                    return {
                        width: Math.max(1, Number(fallbackRect.width ?? 0)),
                        height: Math.max(1, Number(fallbackRect.height ?? 0)),
                        minLeft: Number(fallbackRect.left ?? 0),
                        minTop: Number(fallbackRect.top ?? 0),
                        maxRight: Number(fallbackRect.right ?? 0),
                        maxBottom: Number(fallbackRect.bottom ?? 0),
                    };
                }

                const measuredWidth =
                    widths.length > 0 ? Math.max(...widths) : Math.max(1, maxRight - minLeft);

                return {
                    width: Math.max(1, measuredWidth),
                    height: Math.max(1, maxBottom - minTop),
                    minLeft,
                    minTop,
                    maxRight,
                    maxBottom,
                };
            };

            const isWithinTargetArea = (bounds, tolerancePx = 0.5) => {
                const overflowLeft = bounds.minLeft < targetAreaLeft - tolerancePx;
                const overflowRight = bounds.maxRight > targetAreaRight + tolerancePx;
                const overflowTop = bounds.minTop < targetAreaTop - tolerancePx;
                const overflowBottom = bounds.maxBottom > targetAreaBottom + tolerancePx;

                return !(overflowLeft || overflowRight || overflowTop || overflowBottom);
            };

            const evaluateScale = (scale) => {
                const normalizedScale = Math.max(
                    minScale,
                    Math.min(maxScale, Number(scale) || minScale),
                );
                this.setCurrentPageScale(normalizedScale, { forFitting: true });
                const bounds = measureVisualBounds();
                const fillWidth = bounds.width / targetWidth;
                const fillHeight = bounds.height / targetHeight;
                const fitsBox =
                    bounds.width <= strictWidthOverflowThreshold &&
                    bounds.height <= strictHeightOverflowThreshold;
                const isInsideTargetArea = isWithinTargetArea(bounds, 1.5);

                return {
                    scale: normalizedScale,
                    bounds,
                    fillWidth,
                    fillHeight,
                    isInsideTargetArea,
                    fits: fitsBox && isInsideTargetArea,
                };
            };

            const cachedFitResult = isModalLayoutContext
                ? null
                : isFontLayoutPending
                  ? null
                  : this._fitResultByContext.get(fitCacheKey);

            if (
                cachedFitResult &&
                cachedFitResult.layout &&
                Number.isFinite(Number(cachedFitResult.scale))
            ) {
                this.applyFitLayoutVariables(rootElement, cachedFitResult.layout);
                const cached = evaluateScale(Number(cachedFitResult.scale));
                const cacheHasHealthyFill =
                    cached.fillWidth >= minimumHealthyFillWidth &&
                    cached.fillHeight >= minimumHealthyFillHeight;

                if (cached.fits && cacheHasHealthyFill) {
                    this.pageScale = cached.scale;
                    this.setCurrentPageScale(cached.scale);
                    this._fitRunCounter += 1;
                    this._lastFittedPageNumber = this.pageNumber;
                    this.scheduleFitSanityCheck({
                        cacheKey: fitCacheKey,
                        pageNumber: normalizedPageNumber,
                        availableWidth: targetWidth,
                        availableHeight: targetHeight,
                        strictWidthOverflowTolerance,
                        strictHeightOverflowTolerance,
                        minimumFillWidth: minimumHealthyFillWidth,
                        minimumFillHeight: minimumHealthyFillHeight,
                    });

                    return;
                }

                this.forgetFitResult(fitCacheKey);
            }

            const midpointLayout = {
                ...baselineLayout,
                pageLeadingMultiplier:
                    baselineLayout.pageLeadingMultiplier -
                    (baselineLayout.pageLeadingMultiplier - minimumLeadingMultiplier) * 0.5,
                pageGapMultiplier:
                    baselineLayout.pageGapMultiplier -
                    (baselineLayout.pageGapMultiplier - minimumGapMultiplier) * 0.5,
                pageSurahHeaderScale:
                    baselineLayout.pageSurahHeaderScale -
                    (baselineLayout.pageSurahHeaderScale - minimumSurahHeaderScale) * 0.5,
                basmallahBottomGapScale:
                    baselineLayout.basmallahBottomGapScale -
                    (baselineLayout.basmallahBottomGapScale - minimumBasmallahBottomGapScale) * 0.5,
            };
            const tightLayout = {
                ...baselineLayout,
                pageLeadingMultiplier: minimumLeadingMultiplier,
                pageGapMultiplier: minimumGapMultiplier,
                pageSurahHeaderScale: minimumSurahHeaderScale,
                basmallahBottomGapScale: minimumBasmallahBottomGapScale,
            };
            const expandedLayout = {
                ...baselineLayout,
                pageLeadingMultiplier: Math.min(
                    baselineLayout.pageLeadingMultiplier * 1.18,
                    baselineLayout.pageLeadingMultiplier + 0.28,
                ),
                pageGapMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageGapMultiplier * 1.65,
                        baselineLayout.pageGapMultiplier + 0.32,
                    ),
                    2.45,
                ),
                pageSurahHeaderScale: Math.min(baselineLayout.pageSurahHeaderScale * 1.06, 1.32),
            };
            const expandedVerticalLayout = {
                ...baselineLayout,
                pageLeadingMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageLeadingMultiplier * 1.44,
                        baselineLayout.pageLeadingMultiplier + 0.42,
                    ),
                    2.1,
                ),
                pageGapMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageGapMultiplier * 2.35,
                        baselineLayout.pageGapMultiplier + 0.7,
                    ),
                    3.35,
                ),
                pageSurahHeaderScale: Math.min(baselineLayout.pageSurahHeaderScale * 1.08, 1.36),
            };
            const expandedTallVerticalLayout = {
                ...baselineLayout,
                pageLeadingMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageLeadingMultiplier * 1.78,
                        baselineLayout.pageLeadingMultiplier + 0.72,
                    ),
                    2.65,
                ),
                pageGapMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageGapMultiplier * 3.4,
                        baselineLayout.pageGapMultiplier + 1.05,
                    ),
                    4.4,
                ),
                pageSurahHeaderScale: Math.min(baselineLayout.pageSurahHeaderScale * 1.1, 1.42),
            };
            const shouldUseFastFitPath =
                isBaseBreakpoint || this._startupCalibrationPending || this.isCalibrating;
            const profileCandidateCount = Math.max(
                shouldUseFastFitPath ? 4 : isBaseBreakpoint ? 6 : 8,
                Math.min(
                    shouldUseFastFitPath ? 7 : isBaseBreakpoint ? 12 : 18,
                    Math.trunc(Number(activeFitProfile?.candidateSteps ?? 24)),
                ),
            );
            const profileMinimumCompression = Math.max(
                0,
                Math.min(1, Number(activeFitProfile?.minimumCompressionLevel ?? 0)),
            );
            const profileLayoutCandidates = Array.from(
                { length: profileCandidateCount },
                (_, index) => {
                    const denominator = Math.max(1, profileCandidateCount - 1);
                    const level =
                        profileMinimumCompression +
                        ((1 - profileMinimumCompression) * index) / denominator;

                    return this.fitLayoutFromCompressionLevel(level);
                },
            );
            const layoutCandidates = [
                baselineLayout,
                expandedLayout,
                expandedVerticalLayout,
                expandedTallVerticalLayout,
                midpointLayout,
                tightLayout,
                ...profileLayoutCandidates,
            ].filter((candidateLayout, index, candidates) => {
                const candidateKey = [
                    Number(candidateLayout.pageTypeScale ?? 1).toFixed(4),
                    Number(candidateLayout.pageLeadingMultiplier ?? 1).toFixed(4),
                    Number(candidateLayout.pageGapMultiplier ?? 1).toFixed(4),
                    Number(candidateLayout.pageSurahHeaderScale ?? 1).toFixed(4),
                    Number(candidateLayout.basmallahBottomGapScale ?? 0).toFixed(4),
                ].join('|');

                return (
                    candidates.findIndex((candidate) => {
                        const existingKey = [
                            Number(candidate.pageTypeScale ?? 1).toFixed(4),
                            Number(candidate.pageLeadingMultiplier ?? 1).toFixed(4),
                            Number(candidate.pageGapMultiplier ?? 1).toFixed(4),
                            Number(candidate.pageSurahHeaderScale ?? 1).toFixed(4),
                            Number(candidate.basmallahBottomGapScale ?? 0).toFixed(4),
                        ].join('|');

                        return existingKey === candidateKey;
                    }) === index
                );
            });
            const layoutCandidatesToEvaluate = shouldUseFastFitPath
                ? layoutCandidates.filter((candidateLayout, index) => {
                      if (index < 4) {
                          return true;
                      }

                      return index % 2 === 0;
                  })
                : layoutCandidates;
            const solveBestScaleForCurrentLayout = () => {
                let lower = minScale;
                let upper = maxScale;
                let best = evaluateScale(minScale);

                if (!best.fits) {
                    return best;
                }

                const binarySearchSteps = shouldUseFastFitPath ? 7 : isBaseBreakpoint ? 12 : 16;

                for (let step = 0; step < binarySearchSteps; step += 1) {
                    const mid = (lower + upper) * 0.5;
                    const evaluation = evaluateScale(mid);

                    if (evaluation.fits) {
                        best = evaluation;
                        lower = mid;
                    } else {
                        upper = mid;
                    }
                }

                return best;
            };

            let bestLayout = baselineLayout;
            let finalEvaluation = evaluateScale(minScale);
            let bestScore = Number.NEGATIVE_INFINITY;

            layoutCandidatesToEvaluate.forEach((candidateLayout) => {
                this.applyFitLayoutVariables(rootElement, candidateLayout);
                this.setCurrentPageScale(1, { forFitting: true });
                const evaluation = solveBestScaleForCurrentLayout();

                if (!evaluation.fits) {
                    return;
                }

                const widthDeficitPenalty = Math.max(
                    0,
                    targetMinimumFillWidth - evaluation.fillWidth,
                );
                const areaOverflowPenalty = evaluation.isInsideTargetArea ? 0 : 0.08;
                const heightDeficitPenalty = Math.max(
                    0,
                    targetMinimumFillHeight - evaluation.fillHeight,
                );
                const compressionPenalty =
                    Math.max(
                        0,
                        baselineLayout.pageLeadingMultiplier -
                            Number(candidateLayout.pageLeadingMultiplier ?? 1),
                    ) *
                        0.08 +
                    Math.max(
                        0,
                        baselineLayout.pageGapMultiplier -
                            Number(candidateLayout.pageGapMultiplier ?? 1),
                    ) *
                        0.04;
                const expansionPenalty =
                    Math.max(
                        0,
                        Number(candidateLayout.pageGapMultiplier ?? 1) -
                            baselineLayout.pageGapMultiplier,
                    ) * 0.018;
                const score =
                    Math.min(1.04, evaluation.fillWidth) * 1.24 +
                    Math.min(1.04, evaluation.fillHeight) * 0.9 -
                    widthDeficitPenalty * 1.6 -
                    heightDeficitPenalty * 0.72 -
                    compressionPenalty -
                    expansionPenalty -
                    areaOverflowPenalty;

                if (score > bestScore) {
                    bestScore = score;
                    bestLayout = { ...candidateLayout };
                    finalEvaluation = evaluation;
                }
            });

            if (bestScore === Number.NEGATIVE_INFINITY) {
                let relaxedBestLayout = { ...baselineLayout };
                let relaxedBestEvaluation = evaluateScale(minScale);
                let relaxedBestScore = Number.NEGATIVE_INFINITY;

                layoutCandidatesToEvaluate.forEach((candidateLayout) => {
                    this.applyFitLayoutVariables(rootElement, candidateLayout);
                    this.setCurrentPageScale(1, { forFitting: true });
                    const natural = measureVisualBounds();

                    if (natural.width <= 1 || natural.height <= 1) {
                        return;
                    }

                    const geometricScale = Math.max(
                        minScale,
                        Math.min(
                            maxScale,
                            Math.min(targetWidth / natural.width, targetHeight / natural.height),
                        ),
                    );
                    const evaluation = evaluateScale(geometricScale);
                    const relaxedScore =
                        Math.min(1.08, evaluation.fillWidth) * 1.2 +
                        Math.min(1.08, evaluation.fillHeight) * 0.8;

                    if (relaxedScore > relaxedBestScore) {
                        relaxedBestScore = relaxedScore;
                        relaxedBestLayout = { ...candidateLayout };
                        relaxedBestEvaluation = evaluation;
                    }
                });

                bestLayout = relaxedBestLayout;
                finalEvaluation = relaxedBestEvaluation;
            }

            this.applyFitLayoutVariables(rootElement, bestLayout);
            this.pageScale = finalEvaluation.scale;
            this.setCurrentPageScale(finalEvaluation.scale);
            const safetyAdjustedScale = this.applySafetyScaleForCurrentPageOverflow()
                ? this.pageScale
                : finalEvaluation.scale;

            this.rememberFitResult(
                fitCacheKey,
                {
                    layout: { ...bestLayout },
                    scale: safetyAdjustedScale,
                },
                {
                    persist: !shouldSuppressPersistedCacheWrite,
                },
            );
            this.scheduleFitSanityCheck({
                cacheKey: fitCacheKey,
                pageNumber: normalizedPageNumber,
                availableWidth: targetWidth,
                availableHeight: targetHeight,
                strictWidthOverflowTolerance,
                strictHeightOverflowTolerance,
                minimumFillWidth: minimumHealthyFillWidth,
                minimumFillHeight: minimumHealthyFillHeight,
            });

            if (isFontLayoutPending) {
                this.scheduleFontReadyRecoveryRefit(normalizedPageNumber, {
                    delayMs: 110,
                });
            }

            this._bypassNextFitCache = false;
            this._fitRunCounter += 1;
            this._lastFittedPageNumber = this.pageNumber;
        },
    };
};
