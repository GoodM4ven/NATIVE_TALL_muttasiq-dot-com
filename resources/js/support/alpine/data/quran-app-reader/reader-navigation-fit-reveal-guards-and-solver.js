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

        clearModalPreOpenPending() {
            if (this._modalPreOpenPendingTimer !== null) {
                clearTimeout(this._modalPreOpenPendingTimer);
                this._modalPreOpenPendingTimer = null;
            }

            this._modalPreOpenPending = false;
        },

        setModalPreOpenPending(timeoutMs = 1400) {
            this.clearModalPreOpenPending();
            this._modalPreOpenPending = true;

            this._modalPreOpenPendingTimer = window.setTimeout(
                () => {
                    this._modalPreOpenPendingTimer = null;

                    if (this.openModalCount() > 0 || this._isModalLifecycleSettling) {
                        return;
                    }

                    this._modalPreOpenPending = false;
                    this.syncReaderChromeDocumentClass();
                },
                Math.max(680, Math.trunc(Number(timeoutMs) || 1400)),
            );
        },

        hasBlockingModalLifecycleState({ recoverStaleState = false } = {}) {
            if (this._modalPreOpenPending) {
                return true;
            }

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
            this._modalLifecycleFadeOutPending = false;
            this.clearModalPreOpenPending();

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
            if (this.normalizeBooleanFlag(this.isQrDebugLoggingEnabled, false)) {
                return true;
            }

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

            const tracePayload = {
                pageNumber: this.pageNumber,
                isFittingPage: this.isFittingPage,
                isLoadingPage: this.isLoadingPage,
                isTransitioningOutPage: this.isTransitioningOutPage,
                modalPreOpenPending: this._modalPreOpenPending,
                pendingTargetPage: clampPage(
                    Number(this._pendingNavigationRequest?.targetPage ?? 0),
                    this.maxPage,
                ),
                navigationRevealLocked: this._navigationRevealLocked,
                modalLifecycleSettling: this._isModalLifecycleSettling,
                activeModalCount: this._activeModalIds.size,
                openModalCount: this.openModalCount(),
                ...payload,
            };

            console.log('[quran-reader][reveal]', normalizedEventName, tracePayload);

            const globalScope = window;
            const traceStore = Array.isArray(globalScope.__quranReaderLayoutTrace)
                ? globalScope.__quranReaderLayoutTrace
                : [];
            traceStore.push({
                channel: 'reveal',
                event: normalizedEventName,
                payload: tracePayload,
                at: Date.now(),
            });
            globalScope.__quranReaderLayoutTrace = traceStore.slice(-500);
        },

        currentPageLayoutDebugSnapshot() {
            const rootElement = this.$el?.firstElementChild;
            const pageContent = this.$refs?.pageContent;
            const pageLinesElement =
                pageContent instanceof Element
                    ? pageContent.classList.contains('quran-page-lines')
                        ? pageContent
                        : pageContent.querySelector('.quran-page-lines')
                    : null;
            const rootStyles =
                rootElement instanceof HTMLElement ? window.getComputedStyle(rootElement) : null;
            const pageLinesStyles =
                pageLinesElement instanceof HTMLElement
                    ? window.getComputedStyle(pageLinesElement)
                    : null;
            const firstSurahHeaderLine =
                pageContent instanceof Element
                    ? pageContent.querySelector(
                          '[data-quran-line][data-quran-line-type="surah_name"]',
                      )
                    : null;
            const firstBasmallahLine =
                pageContent instanceof Element
                    ? pageContent.querySelector(
                          '[data-quran-line][data-quran-line-type="basmallah"]',
                      )
                    : null;
            const firstSurahHeaderStyles =
                firstSurahHeaderLine instanceof HTMLElement
                    ? window.getComputedStyle(firstSurahHeaderLine)
                    : null;
            const firstBasmallahStyles =
                firstBasmallahLine instanceof HTMLElement
                    ? window.getComputedStyle(firstBasmallahLine)
                    : null;

            const readRootVar = (name, fallback = '') => {
                if (!rootStyles) {
                    return fallback;
                }

                const value = String(rootStyles.getPropertyValue(name) ?? '').trim();

                return value === '' ? fallback : value;
            };

            return {
                pageScale: this.pageScale,
                cssVars: {
                    pageScale: readRootVar('--quran-page-scale'),
                    pageTypeScale: readRootVar('--quran-page-type-scale'),
                    pageLeadingMultiplier: readRootVar('--quran-page-leading-multiplier'),
                    pageGapMultiplier: readRootVar('--quran-page-gap-multiplier'),
                    pageSurahHeaderScale: readRootVar('--quran-page-surah-header-scale'),
                    basmallahBottomGapScale: readRootVar('--quran-basmallah-bottom-gap-scale'),
                    surahHeaderBasmallahOverlap: readRootVar(
                        '--quran-surah-header-basmallah-overlap',
                    ),
                    surahHeaderBottomTrim: readRootVar('--quran-surah-header-bottom-trim'),
                    lineGap: readRootVar('--quran-line-gap'),
                    gapScale: readRootVar('--quran-gap-scale'),
                },
                lines: {
                    className: String(pageLinesElement?.className ?? ''),
                    gap: String(pageLinesStyles?.gap ?? ''),
                    transform: String(pageLinesStyles?.transform ?? ''),
                },
                firstSurahHeader: {
                    marginBlockStart: String(firstSurahHeaderStyles?.marginBlockStart ?? ''),
                    marginBlockEnd: String(firstSurahHeaderStyles?.marginBlockEnd ?? ''),
                    lineHeight: String(firstSurahHeaderStyles?.lineHeight ?? ''),
                    fontSize: String(firstSurahHeaderStyles?.fontSize ?? ''),
                },
                firstBasmallah: {
                    marginBlockStart: String(firstBasmallahStyles?.marginBlockStart ?? ''),
                    marginBlockEnd: String(firstBasmallahStyles?.marginBlockEnd ?? ''),
                    lineHeight: String(firstBasmallahStyles?.lineHeight ?? ''),
                    fontSize: String(firstBasmallahStyles?.fontSize ?? ''),
                },
            };
        },

        qrDebugLayoutSnapshot(eventName, details = {}) {
            if (!this.readerRevealDebugEnabled()) {
                return;
            }

            const normalizedEventName = String(eventName ?? '').trim() || 'event';
            const payload =
                details && typeof details === 'object' && !Array.isArray(details) ? details : {};

            const tracePayload = {
                pageNumber: this.pageNumber,
                hasRenderablePage: this.hasRenderablePage(),
                ...this.currentPageLayoutDebugSnapshot(),
                ...payload,
            };

            console.log('[quran-reader][layout]', normalizedEventName, tracePayload);

            const globalScope = window;
            const traceStore = Array.isArray(globalScope.__quranReaderLayoutTrace)
                ? globalScope.__quranReaderLayoutTrace
                : [];
            traceStore.push({
                channel: 'layout',
                event: normalizedEventName,
                payload: tracePayload,
                at: Date.now(),
            });
            globalScope.__quranReaderLayoutTrace = traceStore.slice(-500);
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

            if (this._navigationRevealLocked || this._pendingNavigationRequest !== null) {
                this.traceReaderReveal('force-reveal-skipped', {
                    reason,
                    blockedByNavigation: true,
                });

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
            this.clearModalPreOpenPending();
            this._isModalLifecycleSettling = false;
            this._activeModalIds.clear();
            this._modalLifecycleFadeOutPending = false;
            this.isTransitioningOutPage = false;
            this.isFittingPage = false;
            this._lastPageRevealAt = Date.now();
            this._revealBlockedSinceAt = 0;
            this._revealBlockedLayoutToken = 0;
            this.traceReaderReveal('force-reveal-current-page', { reason });

            return true;
        },

        async waitForNavigationRevealUnlock(
            pageNumber = this.pageNumber,
            { maxAttempts = 32, delayMs = 28 } = {},
        ) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);
            const attempts = Math.max(1, Math.trunc(Number(maxAttempts) || 32));
            const waitDelayMs = Math.max(12, Math.trunc(Number(delayMs) || 28));

            for (let attempt = 0; attempt < attempts; attempt += 1) {
                if (
                    this._pendingNavigationRequest !== null &&
                    !this._navigationRevealLocked &&
                    !this.isLoadingPage
                ) {
                    void this.commitPendingNavigation();
                }

                const hasOpenModal = this.openModalCount() > 0;
                const isReadyForPostModalFit =
                    this.pageNumber === normalizedPageNumber &&
                    !this._navigationRevealLocked &&
                    this._pendingNavigationRequest === null &&
                    !this.isLoadingPage &&
                    !this._isModalLifecycleSettling &&
                    this._activeModalIds.size === 0 &&
                    !hasOpenModal;

                if (isReadyForPostModalFit) {
                    this.traceReaderReveal('navigation-reveal-unlocked', {
                        targetPage: normalizedPageNumber,
                        attempt,
                    });

                    return true;
                }

                await wait(waitDelayMs);
            }

            this.traceReaderReveal('navigation-reveal-unlock-timeout', {
                targetPage: normalizedPageNumber,
            });

            return (
                this.pageNumber === normalizedPageNumber &&
                !this._navigationRevealLocked &&
                this._pendingNavigationRequest === null &&
                !this.isLoadingPage
            );
        },

        async waitForPageRevealCycle(
            pageNumber = this.pageNumber,
            { maxAttempts = 28, delayMs = 24 } = {},
        ) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);
            const attempts = Math.max(1, Math.trunc(Number(maxAttempts) || 28));
            const waitDelayMs = Math.max(12, Math.trunc(Number(delayMs) || 24));

            for (let attempt = 0; attempt < attempts; attempt += 1) {
                if (this.pageNumber !== normalizedPageNumber) {
                    return false;
                }

                const revealReady =
                    this._revealTimer === null &&
                    !this.isFittingPage &&
                    this.pageFitState() === 'ready' &&
                    (this.isCurrentPageVisiblyReady() || this.isCurrentPageContentVisible(0.12));

                if (revealReady) {
                    this.traceReaderReveal('page-reveal-cycle-ready', {
                        targetPage: normalizedPageNumber,
                        attempt,
                    });

                    return true;
                }

                await wait(waitDelayMs);
            }

            this.traceReaderReveal('page-reveal-cycle-timeout', {
                targetPage: normalizedPageNumber,
            });

            return (
                this.pageNumber === normalizedPageNumber &&
                this._revealTimer === null &&
                !this.isFittingPage &&
                (this.isCurrentPageVisiblyReady() || this.isCurrentPageContentVisible(0.12))
            );
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
                contentElement.querySelectorAll('[data-quran-line-text], [data-quran-word-button]'),
            ).filter(
                (lineElement) =>
                    String(lineElement.textContent ?? '')
                        .replace(/\s+/g, '')
                        .trim().length > 0,
            ).length;
            const fallbackRenderableText =
                String(contentElement.textContent ?? '')
                    .replace(/\s+/g, '')
                    .trim().length > 0;
            const hasRenderableText = visibleLineCount > 0 || fallbackRenderableText;
            const hasLayoutReadyState = this.pageFitState() === 'ready';
            const hasVisualReadyState =
                !this.isLoadingPage &&
                !this.isFittingPage &&
                !this.isTransitioningOutPage &&
                this._pendingNavigationRequest === null &&
                this.openModalCount() <= 0;

            return (
                (hasLayoutReadyState || hasVisualReadyState) &&
                styles.visibility !== 'hidden' &&
                opacity > 0.08 &&
                hasRenderableText
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

        resetFitSanityRecoveryState() {
            this._fitSanityContextKey = '';
            this._fitSanityContextAttemptCount = 0;
            this._fitSanityContextLastWidth = 0;
            this._fitSanityContextLastHeight = 0;
            this._fitSanityContextOutcome = '';
            this._fitSanitySuppressedUntil = 0;
            this._fitSanityDisabledContextKey = '';
        },

        async stabilizeModalDrivenLayout({
            revealDelayMs = 150,
            maxAttempts = 5,
            maxFrames = 18,
            requiredStableFrames = 3,
            tolerancePx = 0.8,
        } = {}) {
            if (!this.hasRenderablePage()) {
                return;
            }

            this._bypassNextFitCache = true;
            this.resetFitSanityRecoveryState();
            await this.nextTickAsync();
            await this.waitForStablePageFrame({
                maxFrames,
                requiredStableFrames,
                tolerancePx,
            });
            this._bypassNextFitCache = true;
            await this.layoutPageGuaranteed({
                revealDelayMs,
                maxAttempts,
                useIdleFit: false,
            });
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

        holdPageHiddenForModalLifecycle({
            waitForModalLifecycle = true,
            animateFadeOut = true,
        } = {}) {
            if (!this.hasRenderablePage()) {
                return;
            }

            if (this._modalLifecycleFadeOutTimer !== null) {
                if (this._modalLifecycleFadeOutPending) {
                    if (waitForModalLifecycle) {
                        this._isModalLifecycleSettling = true;
                        this.clearLayoutTimers();
                        this.beginLayoutCycle();
                    }

                    return;
                }

                clearTimeout(this._modalLifecycleFadeOutTimer);
                this._modalLifecycleFadeOutTimer = null;
            }

            const shouldAnimateFadeOut =
                animateFadeOut && !this.isTransitioningOutPage && !this.isFittingPage;

            if (shouldAnimateFadeOut) {
                this._modalLifecycleFadeOutPending = true;
                this.isTransitioningOutPage = true;

                this._modalLifecycleFadeOutTimer = window.setTimeout(() => {
                    this._modalLifecycleFadeOutTimer = null;

                    if (!this._modalLifecycleFadeOutPending) {
                        return;
                    }

                    this._modalLifecycleFadeOutPending = false;
                    this.isTransitioningOutPage = false;
                }, 130);
            }

            if (!waitForModalLifecycle) {
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
                    this._modalLifecycleFadeOutPending = false;
                    this.clearModalPreOpenPending();
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

            this._isModalLifecycleSettling = true;
        },

        cancelActiveSearchProcessing() {
            const activeRequestSerial = Math.max(
                0,
                Math.trunc(Number(this._searchRequestSerial ?? 0)),
            );

            if (activeRequestSerial > 0 && typeof this.$wire?.cancelSearch === 'function') {
                void this.$wire.cancelSearch(activeRequestSerial);
            }

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

            await this.waitForNavigationRevealUnlock(normalizedPageNumber, {
                maxAttempts: 36,
                delayMs: 24,
            });

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

            if (this.isCurrentPageVisiblyReady() && this.isCurrentFitQualityHealthy()) {
                return true;
            }

            this.pauseIdleWarmup(960);
            this._bypassNextFitCache = true;
            this.isFittingPage = true;
            this.clearLayoutTimers();

            await this.nextTickAsync();
            await this.waitForStablePageFrame({
                maxFrames: 22,
                requiredStableFrames: 3,
                tolerancePx: 0.75,
            });
            this._bypassNextFitCache = true;
            await this.layoutPageGuaranteed({
                revealDelayMs,
                maxAttempts,
                useIdleFit: false,
            });
            const initialRevealReady = await this.waitForPageRevealCycle(normalizedPageNumber, {
                maxAttempts: 20,
                delayMs: 24,
            });
            const initialFitQualityHealthy = this.isCurrentFitQualityHealthy();
            this.traceReaderReveal('post-modal-fit-pass', {
                targetPage: normalizedPageNumber,
                stage: 'initial',
                fittedPage: this._lastFittedPageNumber,
                isCurrentPageVisiblyReady: this.isCurrentPageVisiblyReady(),
                initialRevealReady,
                fitQualityHealthy: initialFitQualityHealthy,
            });

            const requiresRecoveryPass =
                this._lastFittedPageNumber !== normalizedPageNumber ||
                !initialRevealReady ||
                !initialFitQualityHealthy;

            if (requiresRecoveryPass) {
                this._bypassNextFitCache = true;
                await this.stabilizeModalDrivenLayout({
                    revealDelayMs: Math.max(170, revealDelayMs - 30),
                    maxAttempts: Math.max(4, maxAttempts - 1),
                    maxFrames: 20,
                    requiredStableFrames: 3,
                    tolerancePx: 0.7,
                });
                const recoveryRevealReady = await this.waitForPageRevealCycle(
                    normalizedPageNumber,
                    {
                        maxAttempts: 20,
                        delayMs: 24,
                    },
                );
                const recoveryFitQualityHealthy = this.isCurrentFitQualityHealthy();
                this.traceReaderReveal('post-modal-fit-pass', {
                    targetPage: normalizedPageNumber,
                    stage: 'recovery',
                    fittedPage: this._lastFittedPageNumber,
                    isCurrentPageVisiblyReady: this.isCurrentPageVisiblyReady(),
                    recoveryRevealReady,
                    fitQualityHealthy: recoveryFitQualityHealthy,
                });
            }

            if (
                this.pageNumber === normalizedPageNumber &&
                (!this.isCurrentPageVisiblyReady() ||
                    this._lastFittedPageNumber !== normalizedPageNumber ||
                    !this.isCurrentFitQualityHealthy())
            ) {
                await wait(120);
                await this.waitForStablePageFrame({
                    maxFrames: 14,
                    requiredStableFrames: 2,
                    tolerancePx: 0.6,
                });
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 150,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
                const finalRevealReady = await this.waitForPageRevealCycle(normalizedPageNumber, {
                    maxAttempts: 24,
                    delayMs: 24,
                });
                const finalFitQualityHealthy = this.isCurrentFitQualityHealthy();
                this.traceReaderReveal('post-modal-fit-pass', {
                    targetPage: normalizedPageNumber,
                    stage: 'final',
                    fittedPage: this._lastFittedPageNumber,
                    isCurrentPageVisiblyReady: this.isCurrentPageVisiblyReady(),
                    finalRevealReady,
                    fitQualityHealthy: finalFitQualityHealthy,
                });
            }

            return (
                this.pageNumber === normalizedPageNumber &&
                this._lastFittedPageNumber === normalizedPageNumber &&
                (this.isCurrentPageVisiblyReady() || this.isCurrentPageContentVisible(0.12)) &&
                this.isCurrentFitQualityHealthy()
            );
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
            const lifecycleEventKey = modalId !== '' ? `${kind}:${modalId}` : '';
            const shouldDedupeLifecycleEvent = kind === 'closing' || kind === 'closed';
            const isDuplicateLifecycleEvent =
                shouldDedupeLifecycleEvent &&
                lifecycleEventKey !== '' &&
                lifecycleEventKey === this._lastModalLifecycleEventKey &&
                now - this._lastModalLifecycleEventAt < 140;

            if (isDuplicateLifecycleEvent) {
                return;
            }

            if (lifecycleEventKey !== '') {
                this._lastModalLifecycleEventKey = lifecycleEventKey;
            }

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
                    this._modalLifecycleFadeOutPending = false;
                    const hasNavigationInFlight =
                        this._pendingNavigationRequest !== null || this.isLoadingPage;

                    if (!hasNavigationInFlight) {
                        this.scheduleLayoutAfterModalLifecycle(kind === 'closed' ? 120 : 180);
                    }
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

            this._lastModalLifecycleEventAt = now;

            if (kind === 'opening') {
                if (this.hasRenderablePage()) {
                    this.setModalPreOpenPending();
                    this.holdPageHiddenForModalLifecycle({ waitForModalLifecycle: false });
                    this.syncReaderChromeDocumentClass();
                }

                return;
            }

            if (kind === 'opened') {
                this.clearModalPreOpenPending();

                if (modalId !== '') {
                    this._activeModalIds.add(modalId);
                }

                this._bypassNextFitCache = true;
                this.holdPageHiddenForModalLifecycle({ animateFadeOut: false });
                this._managerModalVersion += 1;
                this.syncReaderChromeDocumentClass();

                return;
            }

            if (kind === 'closing') {
                this.clearModalPreOpenPending();

                if (modalId === '' || this._activeModalIds.has(modalId) || openModalCount > 0) {
                    const navigatedAway = this._lastFittedPageNumber !== this.pageNumber;

                    if (navigatedAway) {
                        this._bypassNextFitCache = true;
                        this.holdPageHiddenForModalLifecycle({ animateFadeOut: false });
                    }

                    this.resumeLayoutWhenNoOpenModals();
                }

                return;
            }

            if (kind === 'closed') {
                this.clearModalPreOpenPending();

                if (modalId !== '') {
                    this._activeModalIds.delete(modalId);
                }

                const navigatedAway = this._lastFittedPageNumber !== this.pageNumber;

                if (navigatedAway) {
                    this._bypassNextFitCache = true;
                    this.holdPageHiddenForModalLifecycle({ animateFadeOut: false });
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
                this.clearModalPreOpenPending();
                this._isModalLifecycleSettling = false;
                this._modalLifecycleFadeOutPending = false;
                this.isTransitioningOutPage = false;
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

                const contentElement = this.$refs.pageContent;
                const rootElement = this.$el?.firstElementChild;
                const frameElement = this.$refs?.pageFrame;

                if (
                    !(contentElement instanceof Element) ||
                    !(rootElement instanceof HTMLElement) ||
                    !(frameElement instanceof HTMLElement)
                ) {
                    return;
                }

                const currentTargetMetrics = this.currentFitTargetMetrics({
                    rootElement,
                    frameElement,
                });
                const sanityAvailableWidth = Math.max(
                    1,
                    Number(currentTargetMetrics?.targetWidth ?? normalizedAvailableWidth),
                );
                const sanityAvailableHeight = Math.max(
                    1,
                    Number(currentTargetMetrics?.targetHeight ?? normalizedAvailableHeight),
                );
                const widthOverflowThreshold =
                    sanityAvailableWidth * Number(strictWidthOverflowTolerance ?? 1.06);
                const heightOverflowThreshold =
                    sanityAvailableHeight * Number(strictHeightOverflowTolerance ?? 1.01);

                const measured = this.measureRenderedBounds(contentElement, {
                    useRobustWidth: false,
                });
                const hasOverflow =
                    measured.width > widthOverflowThreshold ||
                    measured.height > heightOverflowThreshold;
                const fillWidth = measured.width / sanityAvailableWidth;
                const fillHeight = measured.height / sanityAvailableHeight;
                const rootStyles = window.getComputedStyle(rootElement);
                const sanityMinScale = Math.max(
                    0.05,
                    Number.parseFloat(rootStyles.getPropertyValue('--quran-min-page-scale')) || 0.1,
                );
                const isPageScaleAtFloor = Number(this.pageScale ?? 1) <= sanityMinScale + 1e-3;
                // When the solver legitimately bottomed at --quran-min-page-scale (e.g. dense
                // pages on small viewports), under-fill is expected, not a fit failure — skip
                // the recovery refit to avoid storm. Overflow is still treated as a real issue.
                const hasSuspiciousUnderfill =
                    !isPageScaleAtFloor &&
                    (fillWidth < normalizedMinimumFillWidth ||
                        fillHeight < normalizedMinimumFillHeight);

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

        resolveFitWidthReferenceRect(frameElement = this.$refs.pageFrame) {
            const candidateElements = [
                this.$refs?.pageSurface,
                this.$refs?.pageViewport,
                frameElement?.parentElement,
                frameElement,
            ];

            for (const candidateElement of candidateElements) {
                if (!(candidateElement instanceof HTMLElement)) {
                    continue;
                }

                const candidateRect = candidateElement.getBoundingClientRect();

                if (Number(candidateRect?.width ?? 0) > 1) {
                    return candidateRect;
                }
            }

            return null;
        },

        currentFitTargetMetrics({
            rootElement = this.$el?.firstElementChild ?? null,
            frameElement = this.$refs?.pageFrame ?? null,
            computedRootStyles = null,
        } = {}) {
            if (!(rootElement instanceof HTMLElement) || !(frameElement instanceof HTMLElement)) {
                return null;
            }

            const frameRect = frameElement.getBoundingClientRect();
            const widthReferenceRect = this.resolveFitWidthReferenceRect(frameElement);
            const resolvedRootStyles =
                computedRootStyles instanceof CSSStyleDeclaration
                    ? computedRootStyles
                    : window.getComputedStyle(rootElement);
            const fitAreaPaddingX = Math.max(
                0,
                this.cssCustomLengthPixels(
                    resolvedRootStyles,
                    '--quran-fit-area-pad-x',
                    rootElement,
                    0,
                ),
            );
            const fitAreaPaddingY = Math.max(
                0,
                this.cssCustomLengthPixels(
                    resolvedRootStyles,
                    '--quran-fit-area-pad-y',
                    rootElement,
                    0,
                ),
            );
            const fitTopClearance = Math.max(
                0,
                this.cssCustomLengthPixels(
                    resolvedRootStyles,
                    '--quran-fit-top-clearance',
                    rootElement,
                    0,
                ),
            );
            const fitBottomClearance = Math.max(
                0,
                this.cssCustomLengthPixels(
                    resolvedRootStyles,
                    '--quran-fit-bottom-clearance',
                    rootElement,
                    8,
                ),
            );
            const immersiveFitTopPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          resolvedRootStyles,
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
                          resolvedRootStyles,
                          '--quran-immersive-page-pad-bottom',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const rawAvailableWidth = Math.max(
                1,
                Number(
                    widthReferenceRect?.width ??
                        frameRect?.width ??
                        this.$refs?.pageSurface?.clientWidth ??
                        this.$refs?.pageViewport?.clientWidth ??
                        frameElement.parentElement?.clientWidth ??
                        frameElement.clientWidth ??
                        1,
                ),
            );
            const fitHeightRatio = Math.min(
                1,
                Math.max(
                    0.7,
                    Number.parseFloat(
                        resolvedRootStyles.getPropertyValue('--quran-fit-height-ratio'),
                    ) || 1,
                ),
            );
            const fitTargetWidthRatio = Math.min(
                0.95,
                Math.max(
                    0.55,
                    Number.parseFloat(
                        resolvedRootStyles.getPropertyValue('--quran-fit-target-width-ratio'),
                    ) || 0.8,
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

            const availableWidth = Math.max(1, rawAvailableWidth - fitAreaPaddingX * 2);
            const rawAvailableHeight = Math.max(1, frameAreaBottom - frameAreaTop);
            const availableHeight = Math.max(1, rawAvailableHeight * fitHeightRatio);

            return {
                targetWidth: Math.max(1, availableWidth * fitTargetWidthRatio),
                targetHeight: Math.max(1, availableHeight),
            };
        },

        currentFitQualitySnapshot() {
            const surfaceElement = this.$refs?.pageSurface;
            const frameElement = this.$refs?.pageFrame;
            const contentElement = this.$refs?.pageContent;

            if (
                !(surfaceElement instanceof HTMLElement) ||
                !(frameElement instanceof HTMLElement) ||
                !(contentElement instanceof HTMLElement)
            ) {
                return null;
            }

            const surfaceRect = surfaceElement.getBoundingClientRect();
            const frameRect = frameElement.getBoundingClientRect();
            const contentRect = contentElement.getBoundingClientRect();

            if (
                surfaceRect.width <= 0 ||
                frameRect.width <= 0 ||
                frameRect.height <= 0 ||
                contentRect.width <= 0
            ) {
                return null;
            }

            return {
                frameSurfaceRatio: frameRect.width / surfaceRect.width,
                lineFrameRatio: contentRect.width / frameRect.width,
                lineHeightRatio: frameRect.height > 0 ? contentRect.height / frameRect.height : 0,
            };
        },

        isCurrentFitQualityHealthy({
            minimumFrameSurfaceRatio = 0.58,
            minimumLineFrameRatio = 0.76,
        } = {}) {
            const fitQualitySnapshot = this.currentFitQualitySnapshot();

            if (!fitQualitySnapshot) {
                return false;
            }

            return (
                Number(fitQualitySnapshot.frameSurfaceRatio ?? 0) >=
                    Math.max(0.1, Number(minimumFrameSurfaceRatio) || 0.58) &&
                Number(fitQualitySnapshot.lineFrameRatio ?? 0) >=
                    Math.max(0.1, Number(minimumLineFrameRatio) || 0.76)
            );
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
            const widthReferenceRect = this.resolveFitWidthReferenceRect(frameElement);
            const availableWidth = Math.max(
                1,
                Number(
                    widthReferenceRect?.width ??
                        frameRect?.width ??
                        this.$refs?.pageSurface?.clientWidth ??
                        this.$refs?.pageViewport?.clientWidth ??
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
            const widthReferenceRect = this.resolveFitWidthReferenceRect(frameElement);
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
                    widthReferenceRect?.width ??
                        frameRect?.width ??
                        this.$refs?.pageSurface?.clientWidth ??
                        this.$refs?.pageViewport?.clientWidth ??
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
                Number(widthReferenceRect?.left ?? frameRect?.left ?? 0) +
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

            const captureMaxLineWidth = () => {
                let widest = 0;

                for (let index = 0; index < measuredWidthTargets.length; index += 1) {
                    const rect = measuredWidthTargets[index].getBoundingClientRect();

                    if (rect.height > 0 && rect.width > widest) {
                        widest = rect.width;
                    }
                }

                return widest;
            };

            const measureVisualBounds = ({ refineWidthWithLines = false } = {}) => {
                const rect = contentElement.getBoundingClientRect();
                const width = Math.max(1, Number(rect.width ?? 0) || 1);
                const height = Math.max(1, Number(rect.height ?? 0) || 1);
                const refinedWidth =
                    refineWidthWithLines && measuredWidthTargets.length > 0
                        ? captureMaxLineWidth()
                        : 0;

                return {
                    width: refinedWidth > 0 ? Math.max(1, refinedWidth) : width,
                    height,
                    minLeft: Number(rect.left ?? 0),
                    minTop: Number(rect.top ?? 0),
                    maxRight: Number(rect.right ?? 0),
                    maxBottom: Number(rect.bottom ?? 0),
                };
            };

            const isWithinTargetArea = (bounds, tolerancePx = 0.5) => {
                const overflowLeft = bounds.minLeft < targetAreaLeft - tolerancePx;
                const overflowRight = bounds.maxRight > targetAreaRight + tolerancePx;
                const overflowTop = bounds.minTop < targetAreaTop - tolerancePx;
                const overflowBottom = bounds.maxBottom > targetAreaBottom + tolerancePx;

                return !(overflowLeft || overflowRight || overflowTop || overflowBottom);
            };

            const evaluateScale = (scale, { refineWidthWithLines = true } = {}) => {
                const normalizedScale = Math.max(
                    minScale,
                    Math.min(maxScale, Number(scale) || minScale),
                );
                this.setCurrentPageScale(normalizedScale, { forFitting: true });
                const bounds = measureVisualBounds({ refineWidthWithLines });
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
                // 1. Read natural (scale=1) size using actual line width rather than
                //    container width, so the geometric scale is height-limited when text
                //    is narrower than the frame (prevents profile candidates from winning
                //    at a smaller scale than the CSS baseline on post-modal fits).
                this.setCurrentPageScale(1, { forFitting: true });
                const naturalBounds = measureVisualBounds({ refineWidthWithLines: true });
                const naturalWidth = Math.max(1, naturalBounds.width);
                const naturalHeight = Math.max(1, naturalBounds.height);

                // 2. Geometric scale = the largest s where s * natural fits both axes.
                //    With --quran-page-scale appearing once in every font-size/gap calc(),
                //    the rendered W and H scale ~linearly with it for a given candidate.
                const geometricScale = Math.max(
                    minScale,
                    Math.min(
                        maxScale,
                        Math.min(targetWidth / naturalWidth, targetHeight / naturalHeight),
                    ),
                );

                // 3. One verification with line-width refinement for accurate fit detection.
                const verification = evaluateScale(geometricScale, { refineWidthWithLines: true });

                if (verification.fits || geometricScale <= minScale + 1e-4) {
                    return verification;
                }

                // 4. One refinement step if the linear projection over-shot due to wrap
                //    edge cases or content padding. Shrink by the largest observed overflow.
                const overflowFactor = Math.min(
                    targetWidth / Math.max(1, verification.bounds.width),
                    targetHeight / Math.max(1, verification.bounds.height),
                );

                if (overflowFactor >= 1 || overflowFactor < 0.5) {
                    return verification;
                }

                const refinedScale = Math.max(minScale, geometricScale * overflowFactor * 0.998);
                const refined = evaluateScale(refinedScale, { refineWidthWithLines: true });

                return refined.fits || !verification.fits ? refined : verification;
            };

            let bestLayout = baselineLayout;
            let finalEvaluation = null;
            let bestScore = Number.NEGATIVE_INFINITY;
            let relaxedFallbackLayout = baselineLayout;
            let relaxedFallbackEvaluation = null;
            let relaxedFallbackScore = Number.NEGATIVE_INFINITY;

            layoutCandidatesToEvaluate.forEach((candidateLayout) => {
                this.applyFitLayoutVariables(rootElement, candidateLayout);
                const evaluation = solveBestScaleForCurrentLayout();
                const relaxedScore =
                    Math.min(1.08, evaluation.fillWidth) * 1.2 +
                    Math.min(1.08, evaluation.fillHeight) * 0.8;

                if (relaxedScore > relaxedFallbackScore) {
                    relaxedFallbackScore = relaxedScore;
                    relaxedFallbackLayout = { ...candidateLayout };
                    relaxedFallbackEvaluation = evaluation;
                }

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
                    Math.min(1.04, evaluation.fillWidth) * 1.12 +
                    Math.min(1.04, evaluation.fillHeight) * 1.12 -
                    widthDeficitPenalty * 1.25 -
                    heightDeficitPenalty * 1.05 -
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
                bestLayout = relaxedFallbackLayout;
                finalEvaluation = relaxedFallbackEvaluation ?? evaluateScale(minScale);
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
