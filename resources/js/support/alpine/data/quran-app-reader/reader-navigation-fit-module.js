export const createReaderNavigationFitModule = (deps) => {
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
        closeSurahQuickNavigator({ resetSuppressClick = true } = {}) {
            this.surahQuickNavigator.visible = false;
            this.clearSurahQuickNavigatorPressState({ resetSuppressClick });
        },

        onSurahTriggerPointerDown(event) {
            if (this.wirdModeActive) {
                return;
            }

            this.clearSurahQuickNavigatorPressState();
            this.surahQuickNavigator.pointerId = Number(event?.pointerId ?? 0) || null;
            this.surahQuickNavigator.holdTriggered = false;
            this.surahQuickNavigator.suppressClick = false;
            this.surahQuickNavigator.timer = window.setTimeout(() => {
                this.surahQuickNavigator.timer = null;
                this.openSurahQuickNavigator();
            }, surahQuickNavigatorHoldDelayMs);
        },

        onSurahTriggerPointerUp(event) {
            const pointerId = Number(event?.pointerId ?? 0) || null;

            if (
                this.surahQuickNavigator.pointerId !== null &&
                pointerId !== null &&
                this.surahQuickNavigator.pointerId !== pointerId
            ) {
                return;
            }

            if (this.surahQuickNavigator.timer !== null) {
                clearTimeout(this.surahQuickNavigator.timer);
                this.surahQuickNavigator.timer = null;
            }

            if (this.surahQuickNavigator.holdTriggered) {
                this.surahQuickNavigator.suppressClick = true;
            }
        },

        onSurahTriggerPointerCancel() {
            const shouldKeepSuppression =
                this.surahQuickNavigator.holdTriggered || this.surahQuickNavigator.suppressClick;

            this.clearSurahQuickNavigatorPressState({
                resetSuppressClick: !shouldKeepSuppression,
            });
        },

        onSurahTriggerClick() {
            const shouldSuppressClick = this.surahQuickNavigator.suppressClick;

            if (shouldSuppressClick || this.wirdModeActive) {
                this.clearSurahQuickNavigatorPressState();

                return;
            }

            this.closeSurahQuickNavigator({ resetSuppressClick: false });
            this.warmSearchIndex();
            this.$wire.mountAction('searchQuran');
            this.queueSurahDirectoryAutoFocus();
            this.clearSurahQuickNavigatorPressState();
        },

        pageSurahHeaderNumbers() {
            const uniqueSurahNumbers = new Set();

            this.mushafLines.forEach((line) => {
                if (String(line?.line_type ?? '') !== 'surah_name') {
                    return;
                }

                const lineSurahNumber = Math.max(0, Math.trunc(Number(line?.surah_number ?? 0)));

                if (lineSurahNumber > 0) {
                    uniqueSurahNumbers.add(lineSurahNumber);
                }
            });

            return [...uniqueSurahNumbers].sort((firstNumber, secondNumber) => {
                return firstNumber - secondNumber;
            });
        },

        surahQuickNavigatorBaseSurahNumber(direction = 'next') {
            const surahHeaderNumbers = this.pageSurahHeaderNumbers();

            if (surahHeaderNumbers.length === 0) {
                return Math.max(1, Math.trunc(Number(this.currentSurahNumber() ?? 1)));
            }

            if (direction === 'prev') {
                return surahHeaderNumbers[0];
            }

            return surahHeaderNumbers[surahHeaderNumbers.length - 1];
        },

        surahQuickNavigatorTargetSurahNumber(direction = 'next') {
            const baseSurahNumber = this.surahQuickNavigatorBaseSurahNumber(direction);

            if (direction === 'prev') {
                if (baseSurahNumber <= 1) {
                    return null;
                }

                return baseSurahNumber - 1;
            }

            if (baseSurahNumber >= 114) {
                return null;
            }

            return baseSurahNumber + 1;
        },

        surahDirectoryEntryBySurahNumber(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            const surahDirectoryEntries = Array.isArray(this.search.surahDirectory)
                ? this.search.surahDirectory
                : [];

            return (
                surahDirectoryEntries.find((entry) => {
                    return (
                        Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1))) ===
                        normalizedSurahNumber
                    );
                }) ?? null
            );
        },

        firstAyahIndexForSurahInCurrentPage(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            let fallbackAyahIndex = 0;

            for (const line of this.mushafLines) {
                if (String(line?.line_type ?? '') !== 'ayah') {
                    continue;
                }

                const lineSurahNumber = Math.max(0, Math.trunc(Number(line?.surah_number ?? 0)));

                if (lineSurahNumber !== normalizedSurahNumber) {
                    continue;
                }

                const lineAyahIndex = Math.max(0, Math.trunc(Number(line?.ayah_index ?? 0)));

                if (lineAyahIndex > 0) {
                    return lineAyahIndex;
                }

                if (!Array.isArray(line?.words)) {
                    continue;
                }

                for (const word of line.words) {
                    const wordAyahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));
                    const wordSurahNumber = Math.max(
                        0,
                        Math.trunc(Number(word?.surah_number ?? 0)),
                    );

                    if (
                        wordSurahNumber === normalizedSurahNumber &&
                        wordAyahIndex > 0 &&
                        (fallbackAyahIndex === 0 || wordAyahIndex < fallbackAyahIndex)
                    ) {
                        fallbackAyahIndex = wordAyahIndex;
                    }
                }
            }

            return fallbackAyahIndex;
        },

        isSurahQuickNavigatorPreviousDisabled() {
            const currentPage = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const targetSurahNumber = this.surahQuickNavigatorTargetSurahNumber('prev');

            return currentPage <= 1 || targetSurahNumber === null;
        },

        isSurahQuickNavigatorNextDisabled() {
            const currentPage = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const targetSurahNumber = this.surahQuickNavigatorTargetSurahNumber('next');

            return currentPage >= surahQuickNavigatorLastPage || targetSurahNumber === null;
        },

        async navigateToAdjacentSurah(direction = 'next') {
            const normalizedDirection = direction === 'prev' ? 'prev' : 'next';
            const isDisabled =
                normalizedDirection === 'prev'
                    ? this.isSurahQuickNavigatorPreviousDisabled()
                    : this.isSurahQuickNavigatorNextDisabled();

            if (isDisabled) {
                return;
            }

            const targetSurahNumber =
                this.surahQuickNavigatorTargetSurahNumber(normalizedDirection);

            if (targetSurahNumber === null) {
                return;
            }

            const targetSurahEntry = this.surahDirectoryEntryBySurahNumber(targetSurahNumber);
            const targetPage = clampPage(Number(targetSurahEntry?.page_number ?? 1), this.maxPage);
            const currentPage = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const activeAyahIndex =
                targetPage === currentPage
                    ? this.firstAyahIndexForSurahInCurrentPage(targetSurahNumber)
                    : 0;

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.activeWordIndex = 0;
            this._bypassNextFitCache = true;

            await this.goToPageFromChevron(targetPage, {
                source: 'surah-quick-nav',
                activeAyahIndex,
                commitNow: true,
                settleDelayMs: 0,
            });

            if (this._lastFittedPageNumber !== this.pageNumber) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 160,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            }

            this.search.activeSurahNumber = targetSurahNumber;
            this.activeAyahIndex = activeAyahIndex;
            this.activeWordIndex = 0;
            this.recordNavigationHistory({
                source: 'surah-directory',
                pageNumber: targetPage,
                surahNumber: targetSurahNumber,
                ayahIndex: activeAyahIndex,
            });
        },

        openBookmarksManager() {
            this.$wire.mountAction('bookmarksManager');
        },

        readerRootStyle() {
            return '';
        },

        pageDataUrl(pageNumber) {
            return this.api.pageDataTemplate.replace('__PAGE__', String(pageNumber));
        },

        abortActivePageLoad() {
            if (
                typeof AbortController === 'undefined' ||
                !(this._activePageAbortController instanceof AbortController)
            ) {
                return;
            }

            this._activePageAbortController.abort();
            this._activePageAbortController = null;
        },

        beginActivePageLoadAbortController() {
            if (typeof AbortController === 'undefined') {
                this._activePageAbortController = null;

                return null;
            }

            this.abortActivePageLoad();
            this._activePageAbortController = new AbortController();

            return this._activePageAbortController;
        },

        async getPagePayload(
            pageNumber,
            { preferCache = true, forceNetwork = false, signal = null } = {},
        ) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);

            if (this._pagePayloadByPage.has(normalizedPage) && !forceNetwork) {
                return this._pagePayloadByPage.get(normalizedPage);
            }

            const pendingLoad = this._pendingPageLoads.get(normalizedPage);

            if (pendingLoad) {
                return await pendingLoad;
            }

            const url = this.pageDataUrl(normalizedPage);
            const loadPromise = (async () => {
                try {
                    const payload = normalizePayload(
                        await fetchJsonWithCache({
                            url,
                            cacheName: this.cacheNames.pages,
                            preferCache,
                            forceNetwork,
                            signal,
                        }),
                    );

                    this._pagePayloadByPage.set(normalizedPage, payload);
                    await this.prefetchFontAsset(payload);

                    return payload;
                } catch (error) {
                    if (error?.name === 'AbortError') {
                        return null;
                    }

                    throw error;
                }
            })();

            this._pendingPageLoads.set(normalizedPage, loadPromise);

            try {
                return await loadPromise;
            } finally {
                this._pendingPageLoads.delete(normalizedPage);
            }
        },

        async ensureCurrentPageLoaded() {
            const normalizedPage = clampPage(this.pageNumber, this.maxPage);
            const targetPage = clampPage(Number(this.pageInput ?? this.pageNumber), this.maxPage);
            const loadedPayloadPageNumber = Math.max(
                0,
                Math.trunc(Number(this._loadedPayloadPageNumber ?? 0)),
            );

            if (
                normalizedPage === targetPage &&
                this.hasRenderablePage() &&
                loadedPayloadPageNumber === targetPage
            ) {
                return;
            }

            await this.goToPage(targetPage, {
                animate: false,
                forceRefit: true,
                source: 'startup-ensure-current-page',
            });
        },

        buildDigitMorphSegments(previousValue, nextValue) {
            const previous = String(this.pageCounterNormalizeDisplayValue(previousValue, 1));
            const next = String(this.pageCounterNormalizeDisplayValue(nextValue, previousValue));
            const length = this.pageCounterDigitLength();
            const previousChars = previous.padStart(length, ' ').split('');
            const nextChars = next.padStart(length, ' ').split('');

            const segments = nextChars.map((nextChar, index) => {
                const previousChar = previousChars[index] ?? '';
                const prev = previousChar === ' ' ? '' : previousChar;
                const nextValueChar = nextChar === ' ' ? '' : nextChar;

                return {
                    key: `${index}:${prev}->${nextValueChar}`,
                    prev,
                    next: nextValueChar,
                    changed: prev !== nextValueChar,
                };
            });

            return {
                segments,
                hasChanges: segments.some((segment) => segment.changed),
            };
        },

        pageCounterDigitLength() {
            return Math.max(3, String(this.pageCounterMaxDisplayValue()).length);
        },

        pageCounterNormalizeDisplayValue(value, fallback = 1) {
            const maxDisplayValue = Math.max(
                1,
                Math.trunc(Number(this.pageCounterMaxDisplayValue()) || 1),
            );
            const normalizedFallback = Number.isFinite(Number(fallback))
                ? Math.trunc(Number(fallback))
                : 1;
            const normalizedValue = Number.isFinite(Number(value))
                ? Math.trunc(Number(value))
                : normalizedFallback;

            return Math.max(1, Math.min(maxDisplayValue, normalizedValue));
        },

        pageCounterDisplayDigits(value) {
            return String(this.pageCounterNormalizeDisplayValue(value))
                .padStart(this.pageCounterDigitLength(), ' ')
                .split('')
                .map((digit) => (digit === ' ' ? '' : digit));
        },

        pageCounterMaxDisplayValue() {
            if (!this.wirdModeActive) {
                return Math.max(1, this.maxPage);
            }

            const range = this.wirdRangeState();

            return Math.max(1, range.requiredPages);
        },

        pageCounterCurrentDisplayValue() {
            if (!this.wirdModeActive) {
                return clampPage(this.pageInput, this.maxPage);
            }

            const range = this.wirdRangeState();
            const currentStep = this.normalizeIntegerFlag(
                this.wirdSliderDisplayStep(range.record),
                this.wirdActiveStepForNavigation(range.record),
                {
                    min: 0,
                    max: range.maxStep,
                },
            );

            return currentStep + 1;
        },

        currentMushafPageDisplayValue() {
            return clampPage(this.pageInput ?? this.pageNumber, this.maxPage);
        },

        shouldShowMushafPageIndicator() {
            if (!this.wirdModeActive) {
                return false;
            }

            return this.pageCounterMaxDisplayValue() > Math.max(1, this.resolveReaderMaxPage());
        },

        clampPage(value, maxPage = this.maxPage) {
            return clampPage(value, maxPage);
        },

        triggerPageCounterPulse(previousValue, nextValue, { source = 'generic' } = {}) {
            if (this.pageCounterPulse.timer !== null) {
                clearTimeout(this.pageCounterPulse.timer);
                this.pageCounterPulse.timer = null;
            }

            const morph = this.buildDigitMorphSegments(previousValue, nextValue);

            this.pageCounterPulse.segments = morph.segments;
            this.pageCounterPulse.hasChanges = morph.hasChanges;

            if (!morph.hasChanges || this.shouldSuspendPageCounterMorph({ source })) {
                this.pageCounterPulse.isActive = false;

                return;
            }

            if (!this.pageCounterPulse.isActive) {
                requestAnimationFrame(() => {
                    this.pageCounterPulse.isActive = true;
                });
            }

            this.pageCounterPulse.timer = window.setTimeout(() => {
                this.pageCounterPulse.isActive = false;
                this.pageCounterPulse.timer = null;
            }, pageCounterPulseDurationMs);
        },

        async animatePageInputTo(
            targetPage,
            { source = 'generic', durationMs = wirdModeEntryPageInputTweenDurationMs } = {},
        ) {
            const startPage = clampPage(this.pageInput, this.maxPage);
            const nextPage = clampPage(targetPage, this.maxPage);

            if (nextPage === startPage) {
                this.pageInput = nextPage;
                this._lastPageInputVisualValue = nextPage;

                return;
            }

            if (this._pageInputTweenRaf !== null) {
                cancelAnimationFrame(this._pageInputTweenRaf);
                this._pageInputTweenRaf = null;
            }

            this.triggerPageCounterPulse(startPage, nextPage, { source });

            const resolvedDurationMs = Math.max(
                120,
                Math.trunc(Number(durationMs) || wirdModeEntryPageInputTweenDurationMs),
            );

            await new Promise((resolve) => {
                const startTimestamp = performance.now();
                const distance = nextPage - startPage;

                const step = (timestamp) => {
                    const elapsed = timestamp - startTimestamp;
                    const progress = Math.max(0, Math.min(1, elapsed / resolvedDurationMs));
                    const easedProgress =
                        progress < 0.5
                            ? 2 * progress * progress
                            : 1 - Math.pow(-2 * progress + 2, 2) / 2;
                    const interpolatedPage = clampPage(
                        Math.round(startPage + distance * easedProgress),
                        this.maxPage,
                    );

                    if (interpolatedPage !== this.pageInput) {
                        this.pageInput = interpolatedPage;
                        this._lastPageInputVisualValue = interpolatedPage;
                    }

                    if (progress >= 1) {
                        this._pageInputTweenRaf = null;
                        this.pageInput = nextPage;
                        this._lastPageInputVisualValue = nextPage;
                        resolve();

                        return;
                    }

                    this._pageInputTweenRaf = requestAnimationFrame(step);
                };

                this._pageInputTweenRaf = requestAnimationFrame(step);
            });
        },

        shouldSuspendPageCounterMorph({ source = 'generic' } = {}) {
            const normalizedSource = String(source ?? 'generic').trim();

            if (
                normalizedSource === 'keyboard' ||
                normalizedSource === 'swipe' ||
                normalizedSource === 'page-input' ||
                normalizedSource === 'page-slider' ||
                normalizedSource === 'page-slider-commit'
            ) {
                return false;
            }

            return this.isLoadingPage;
        },

        navigationBasePage() {
            const pendingTargetPage = Number(this._pendingNavigationRequest?.targetPage ?? 0);

            if (pendingTargetPage > 0) {
                return pendingTargetPage;
            }

            if (!this.isLoadingPage && this._pendingNavigationRequest === null) {
                return clampPage(this.pageNumber, this.maxPage);
            }

            const visualPage = clampPage(
                Number(this.pageInput ?? this._lastPageInputVisualValue ?? this.pageNumber),
                this.maxPage,
            );

            if (visualPage > 0) {
                return visualPage;
            }

            return this.pageNumber;
        },

        syncPageInputToCurrentPage() {
            if (!this.wirdModeActive && this._pageSliderInteractionActive) {
                return;
            }

            const normalizedCurrentPage = clampPage(this.pageNumber, this.maxPage);

            if (
                this.pageInput === normalizedCurrentPage &&
                this._lastPageInputVisualValue === normalizedCurrentPage
            ) {
                return;
            }

            this.pageInput = normalizedCurrentPage;
            this._lastPageInputVisualValue = normalizedCurrentPage;
        },

        resolveNavigationDirection(targetPage, direction = null) {
            if (direction === 'prev' || direction === 'next') {
                return direction;
            }

            const basePage = this.navigationBasePage();

            return targetPage >= basePage ? 'next' : 'prev';
        },

        isHighFrequencyNavigationSource(source = 'generic') {
            const normalizedSource = String(source ?? '').trim();

            return (
                normalizedSource === 'keyboard' ||
                normalizedSource === 'swipe' ||
                normalizedSource.endsWith('-keyboard') ||
                normalizedSource.endsWith('-swipe')
            );
        },

        isImmediateNavigationSource(source = 'generic') {
            const normalizedSource = String(source ?? '').trim();

            return (
                normalizedSource === 'chevron' ||
                this.isHighFrequencyNavigationSource(normalizedSource)
            );
        },

        isFastFitPrioritySource(source = 'generic') {
            const normalizedSource = String(source ?? '').trim();

            return (
                normalizedSource === 'surah-directory' ||
                normalizedSource === 'search-result' ||
                normalizedSource === 'page-jump' ||
                normalizedSource === 'page-slider-commit' ||
                normalizedSource.startsWith('wird-')
            );
        },

        resolveIdleWarmupPauseDuration(source = 'generic') {
            return this.isHighFrequencyNavigationSource(source)
                ? idleWarmupPauseOnHighFrequencyNavigationMs
                : idleWarmupPauseOnStandardNavigationMs;
        },

        clearNavigationBurstState() {
            this._navigationBurstLastInputAt = 0;
            this._navigationBurstCount = 0;
            this._navigationBurstFreezeUntil = 0;
        },

        isNavigationBurstActive() {
            return this._navigationBurstFreezeUntil > Date.now();
        },

        registerNavigationBurst(source = 'generic') {
            if (!this.isHighFrequencyNavigationSource(source)) {
                return;
            }

            if (this.isImmediateNavigationSource(source)) {
                this.clearNavigationBurstState();

                return;
            }

            const now = Date.now();
            const elapsedSinceLastInput = now - this._navigationBurstLastInputAt;
            const isBurstContinuation = elapsedSinceLastInput <= navigationBurstInputThresholdMs;

            this._navigationBurstCount = isBurstContinuation ? this._navigationBurstCount + 1 : 1;
            this._navigationBurstLastInputAt = now;
            this._navigationBurstFreezeUntil =
                now +
                (isBurstContinuation ? navigationBurstSettleDelayMs : navigationSettleDelayMs);
        },

        navigationBurstRemainingMsFor(source = 'generic') {
            if (!this.isHighFrequencyNavigationSource(source)) {
                return 0;
            }

            if (this.isImmediateNavigationSource(source)) {
                return 0;
            }

            return Math.max(0, this._navigationBurstFreezeUntil - Date.now());
        },

        resolveNavigationCommitDelay(source = 'generic', delayMs = navigationSettleDelayMs) {
            const normalizedDelay = Math.max(
                0,
                Math.trunc(Number(delayMs) || navigationSettleDelayMs),
            );

            return Math.max(normalizedDelay, this.navigationBurstRemainingMsFor(source));
        },

        schedulePendingNavigationCommit(delayMs = navigationSettleDelayMs) {
            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            const normalizedDelayMs = Math.max(
                0,
                Math.trunc(Number(delayMs) || navigationSettleDelayMs),
            );

            this.traceReaderReveal('schedule-pending-navigation-commit', {
                delayMs: normalizedDelayMs,
            });

            this._navigationDebounceTimer = window.setTimeout(() => {
                this._navigationDebounceTimer = null;
                void this.commitPendingNavigation();
            }, normalizedDelayMs);
        },

        setNavigationRevealLock(durationMs = navigationRevealLockDurationMs) {
            this._navigationRevealLocked = true;
            this.traceReaderReveal('set-navigation-reveal-lock', {
                durationMs: Math.max(
                    120,
                    Math.trunc(Number(durationMs) || navigationRevealLockDurationMs),
                ),
            });

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            this._navigationRevealUnlockTimer = window.setTimeout(
                () => {
                    this._navigationRevealUnlockTimer = null;
                    this._navigationRevealLocked = false;
                    this.traceReaderReveal('clear-navigation-reveal-lock');

                    if (this._pendingNavigationRequest !== null) {
                        this.schedulePendingNavigationCommit(
                            this.resolveNavigationCommitDelay(
                                this._pendingNavigationRequest?.source ?? 'generic',
                                0,
                            ),
                        );
                    }
                },
                Math.max(120, Math.trunc(Number(durationMs) || navigationRevealLockDurationMs)),
            );
        },

        async commitPendingNavigation() {
            if (this._pendingNavigationRequest === null) {
                return;
            }

            if (this._navigationRevealLocked || this.isLoadingPage) {
                this.traceReaderReveal('skip-commit-pending-navigation', {
                    reason: this._navigationRevealLocked ? 'reveal-locked' : 'loading-page',
                });
                this.schedulePendingNavigationCommit(
                    this.resolveNavigationCommitDelay(
                        this._pendingNavigationRequest?.source ?? 'generic',
                        navigationSettleDelayMs,
                    ),
                );

                return;
            }

            const request = this._pendingNavigationRequest;
            const burstRemainingMs = this.navigationBurstRemainingMsFor(request.source);

            if (burstRemainingMs > 0) {
                this.schedulePendingNavigationCommit(burstRemainingMs);

                return;
            }

            this._pendingNavigationRequest = null;
            const isSamePageNavigation =
                request.targetPage === this.pageNumber && this.mushafLines.length > 0;

            await this.goToPage(request.targetPage, {
                direction: request.direction,
                animate: request.animate,
                activeAyahIndex: request.activeAyahIndex,
                searchHighlightAyahIndex: request.searchHighlightAyahIndex,
                forceRefit: request.forceRefit,
                source: request.source,
            });

            if (request.animate && !isSamePageNavigation) {
                this.setNavigationRevealLock();
            }
        },

        async navigateToPage(
            targetPage,
            {
                direction = 'next',
                animate = true,
                activeAyahIndex = null,
                searchHighlightAyahIndex = null,
                source = 'generic',
                forceRefit = false,
                commitNow = false,
                settleDelayMs = navigationSettleDelayMs,
            } = {},
        ) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);
            const resolvedDirection = this.resolveNavigationDirection(
                normalizedTargetPage,
                direction,
            );
            const previousInputPage = clampPage(this.pageInput, this.maxPage);
            this.pauseIdleWarmup(this.resolveIdleWarmupPauseDuration(source), {
                preservePage: normalizedTargetPage,
            });

            if (previousInputPage !== normalizedTargetPage) {
                this.triggerPageCounterPulse(previousInputPage, normalizedTargetPage, {
                    source,
                });
            }

            this.pageInput = normalizedTargetPage;
            this._lastPageInputVisualValue = normalizedTargetPage;
            this.registerNavigationBurst(source);

            this._pendingNavigationRequest = {
                targetPage: normalizedTargetPage,
                direction: resolvedDirection,
                animate: Boolean(animate),
                activeAyahIndex,
                searchHighlightAyahIndex,
                source,
                forceRefit: Boolean(forceRefit),
            };

            if (this._navigationRevealLocked || this.isLoadingPage) {
                this.traceReaderReveal('defer-navigate-to-page', {
                    source,
                    targetPage: normalizedTargetPage,
                    reason: this._navigationRevealLocked ? 'reveal-locked' : 'loading-page',
                });
                this.schedulePendingNavigationCommit(
                    this.resolveNavigationCommitDelay(source, settleDelayMs),
                );

                return;
            }

            const resolvedCommitDelay = this.resolveNavigationCommitDelay(source, settleDelayMs);

            if (commitNow) {
                if (this.navigationBurstRemainingMsFor(source) <= 0) {
                    await this.commitPendingNavigation();

                    if (this._pendingNavigationRequest !== null) {
                        this.schedulePendingNavigationCommit(
                            this.resolveNavigationCommitDelay(source, settleDelayMs),
                        );
                    }

                    return;
                }

                this.schedulePendingNavigationCommit(
                    this.resolveNavigationCommitDelay(source, settleDelayMs),
                );

                return;
            }

            this.schedulePendingNavigationCommit(resolvedCommitDelay);
        },

        async nextPage(source = 'generic') {
            const sourceProfile = this.navigationSourceProfile(source);

            if (this.wirdModeActive) {
                await this.stepWird('next', sourceProfile);

                return;
            }

            const basePage = this.navigationBasePage();
            const shouldCommitImmediately = this.isImmediateNavigationSource(sourceProfile);

            await this.navigateToPage(basePage + 1, {
                direction: 'next',
                source: sourceProfile,
                commitNow: shouldCommitImmediately,
                settleDelayMs: shouldCommitImmediately ? 0 : navigationSettleDelayMs,
            });
        },

        async previousPage(source = 'generic') {
            const sourceProfile = this.navigationSourceProfile(source);

            if (this.wirdModeActive) {
                await this.stepWird('prev', sourceProfile);

                return;
            }

            const basePage = this.navigationBasePage();
            const shouldCommitImmediately = this.isImmediateNavigationSource(sourceProfile);

            if (basePage <= 1) {
                this.requestReaderGateNavigation(sourceProfile);

                return;
            }

            await this.navigateToPage(basePage - 1, {
                direction: 'prev',
                source: sourceProfile,
                commitNow: shouldCommitImmediately,
                settleDelayMs: shouldCommitImmediately ? 0 : navigationSettleDelayMs,
            });
        },

        isFirstNavigationPage() {
            if (this.wirdModeActive) {
                const record = this.ensureWirdDailyRecord();
                const pageStep = this.wirdStepForPage(this.pageNumber, record, {
                    preferredStep: this.wirdActiveStepForNavigation(record),
                });

                if (pageStep !== null) {
                    return pageStep <= 0;
                }

                if (record?.completed) {
                    return this.wirdBrowseStepValue(record) <= 0;
                }

                return this.wirdCurrentStep(record) <= 0;
            }

            return this.navigationBasePage() <= 1;
        },

        isLastNavigationPage() {
            if (this.wirdModeActive) {
                const record = this.ensureWirdDailyRecord();
                const requiredPages = Math.max(
                    1,
                    this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
                );
                const pageStep = this.wirdStepForPage(this.pageNumber, record, {
                    preferredStep: this.wirdActiveStepForNavigation(record),
                });

                if (pageStep !== null) {
                    return pageStep >= requiredPages - 1;
                }

                if (record?.completed) {
                    return this.wirdBrowseStepValue(record) >= requiredPages - 1;
                }

                return this.wirdCurrentStep(record) >= requiredPages - 1;
            }

            return this.maxPage > 0 && this.navigationBasePage() >= this.maxPage;
        },

        async goNextFromChevron(source = 'chevron') {
            const resolvedSource = this.consumePendingChevronSource(source);

            if (!this.wirdModeActive && this.isLastNavigationPage()) {
                return;
            }

            await this.nextPage(resolvedSource);
        },

        async goPreviousFromChevron(source = 'chevron') {
            await this.previousPage(this.consumePendingChevronSource(source));
        },

        async goToPageFromChevron(
            targetPage,
            {
                source = 'chevron-page',
                activeAyahIndex = null,
                searchHighlightAyahIndex = null,
                forceRefit = true,
                animate = true,
                commitNow = null,
                settleDelayMs = null,
            } = {},
        ) {
            const sourceProfile = this.navigationSourceProfile(source);
            const normalizedTargetPage = clampPage(targetPage ?? this.pageInput, this.maxPage);
            const shouldCommitImmediately =
                typeof commitNow === 'boolean'
                    ? commitNow
                    : this.isImmediateNavigationSource(sourceProfile);
            const resolvedSettleDelayMs = Number.isFinite(Number(settleDelayMs))
                ? Math.max(0, Math.trunc(Number(settleDelayMs)))
                : shouldCommitImmediately
                  ? 0
                  : navigationSettleDelayMs;

            await this.navigateToPage(normalizedTargetPage, {
                direction: this.resolveNavigationDirection(normalizedTargetPage),
                animate: Boolean(animate),
                activeAyahIndex,
                searchHighlightAyahIndex,
                source: sourceProfile,
                forceRefit: Boolean(forceRefit),
                commitNow: shouldCommitImmediately,
                settleDelayMs: resolvedSettleDelayMs,
            });

            return normalizedTargetPage;
        },

        consumePendingChevronSource(fallbackSource = 'chevron') {
            const pendingSource = String(this.pendingChevronSource ?? '').trim();

            this.pendingChevronSource = null;

            if (pendingSource !== '') {
                return pendingSource;
            }

            return String(fallbackSource ?? '').trim() || 'chevron';
        },

        resolveChevronButton(direction) {
            if (direction === 'next') {
                const nextButton = this.$refs?.nextChevronButton;

                return nextButton instanceof HTMLButtonElement ? nextButton : null;
            }

            if (direction === 'prev') {
                const previousButton = this.$refs?.prevChevronButton;

                return previousButton instanceof HTMLButtonElement ? previousButton : null;
            }

            return null;
        },

        triggerChevronButtonClick(direction, source = 'chevron') {
            const chevronButton = this.resolveChevronButton(direction);

            if (!(chevronButton instanceof HTMLButtonElement) || chevronButton.disabled) {
                this.pendingChevronSource = null;

                return false;
            }

            this.pendingChevronSource = String(source ?? '').trim() || 'chevron';
            chevronButton.click();

            return true;
        },

        async handleRequestedNavigation(kind, detail = {}) {
            this.resetSwipeState();
            const requestedSource = String(detail?.source ?? '').trim();

            if (this.wirdModeActive && kind === 'page') {
                return;
            }

            if (kind === 'next') {
                await this.goNextFromChevron();

                return;
            }

            if (kind === 'prev') {
                await this.goPreviousFromChevron();

                return;
            }

            if (kind === 'page') {
                const requestedPage = clampPage(detail?.page ?? this.pageInput, this.maxPage);
                const requestedActiveAyahIndex = Math.max(
                    0,
                    Math.trunc(Number(detail?.activeAyahIndex ?? 0)),
                );
                const requestedSearchHighlightAyahIndex = Math.max(
                    0,
                    Math.trunc(Number(detail?.searchHighlightAyahIndex ?? 0)),
                );
                const isModalDrivenPriorityRequest =
                    requestedSource === 'search-result' || requestedSource === 'surah-directory';
                const isPriorityPageRequest =
                    requestedSource === 'page-jump' ||
                    requestedSource === 'page-slider-commit' ||
                    isModalDrivenPriorityRequest;
                const isSliderCommitRequest = requestedSource === 'page-slider-commit';
                const shouldResetQueueForPriorityRequest =
                    requestedSource === 'page-jump' || isModalDrivenPriorityRequest;
                const shouldCommitImmediately = isPriorityPageRequest;

                if (shouldResetQueueForPriorityRequest) {
                    this.resetNavigationQueueForPriorityJump();
                    this.clearPendingPostModalTargetFit();
                }

                if (isPriorityPageRequest) {
                    this._bypassNextFitCache = true;
                }

                if (requestedSource === 'page-jump' || isModalDrivenPriorityRequest) {
                    const modalLifecycleIds =
                        requestedSource === 'page-jump'
                            ? [this.jumpPageModalId]
                            : [
                                  this.resolveSearchModalCloseTargetId(),
                                  this.searchActionModalId,
                                  this.searchModalId,
                                  this.searchModalDomId,
                              ];

                    this.suppressModalLifecycleEffects(modalLifecycleIds, {
                        durationMs: Math.max(
                            modalLifecycleSuppressionDurationMs,
                            postModalFitRevealSettleDelayMs + 640,
                        ),
                    });
                    await this.waitForModalLifecycleToSettle(28, 28);
                    await wait(modalCloseTransitionDelayMs);
                    this._bypassNextFitCache = true;
                }

                if (isSliderCommitRequest) {
                    if (this._navigationDebounceTimer !== null) {
                        clearTimeout(this._navigationDebounceTimer);
                        this._navigationDebounceTimer = null;
                    }

                    this._pendingNavigationRequest = null;
                    this._navigationRevealLocked = false;
                    this.clearSwipeRevealWatchdog();

                    await this.goToPage(requestedPage, {
                        direction: this.resolveNavigationDirection(requestedPage),
                        animate: false,
                        forceRefit: true,
                        source: requestedSource || 'page-event',
                    });
                } else {
                    await this.goToPageFromChevron(requestedPage, {
                        source: requestedSource || 'page-event',
                        activeAyahIndex: requestedActiveAyahIndex,
                        searchHighlightAyahIndex: requestedSearchHighlightAyahIndex,
                        commitNow: shouldCommitImmediately || undefined,
                    });
                }

                if (isPriorityPageRequest) {
                    if (isModalDrivenPriorityRequest) {
                        this._bypassNextFitCache = true;
                        this._fitSanityContextKey = '';
                        this._fitSanityContextAttemptCount = 0;
                        this._fitSanityContextLastWidth = 0;
                        this._fitSanityContextLastHeight = 0;
                        this._fitSanityContextOutcome = '';
                        this._fitSanitySuppressedUntil = 0;
                        this._fitSanityDisabledContextKey = '';
                        await this.waitForStablePageFrame({
                            maxFrames: 14,
                            requiredStableFrames: 2,
                            tolerancePx: 0.8,
                        });
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 150,
                            maxAttempts: 5,
                            useIdleFit: false,
                        });
                        this.refreshMobileEdgeCaptions(false);
                        this.syncReaderChromeDocumentClass();
                    }

                    if (isSliderCommitRequest) {
                        this._bypassNextFitCache = true;
                        this._fitSanityContextKey = '';
                        this._fitSanityContextAttemptCount = 0;
                        this._fitSanityContextLastWidth = 0;
                        this._fitSanityContextLastHeight = 0;
                        this._fitSanityContextOutcome = '';
                        this._fitSanitySuppressedUntil = 0;
                        this._fitSanityDisabledContextKey = '';
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 140,
                            maxAttempts: 5,
                            useIdleFit: false,
                        });
                    }

                    if (
                        !this.isCurrentPageVisiblyReady() &&
                        this._lastFittedPageNumber !== this.pageNumber
                    ) {
                        this._bypassNextFitCache = true;
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 170,
                            maxAttempts: 4,
                            useIdleFit: false,
                        });
                    }

                    if (!this.isCurrentPageVisiblyReady() && this.hasRenderablePage()) {
                        this.clearStaleRevealGuards();
                        this.forceRevealCurrentPage('priority-page-post-fit-fail-open');
                    }
                }

                if (
                    requestedSource === 'page-jump' ||
                    requestedSource === 'page-slider-commit' ||
                    isModalDrivenPriorityRequest
                ) {
                    this.recordNavigationHistory({
                        source: requestedSource,
                        pageNumber: requestedPage,
                        surahNumber: Math.max(
                            0,
                            Math.trunc(Number(detail?.surahNumber ?? this.currentSurahNumber())),
                        ),
                        ayahNumber: Math.max(0, Math.trunc(Number(detail?.ayahNumber ?? 0))),
                        ayahIndex: requestedActiveAyahIndex,
                        query: detail?.query ?? null,
                    });
                }
            }
        },

        resetNavigationQueueForPriorityJump() {
            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            this._pendingNavigationRequest = null;
            this._navigationRevealLocked = false;
            this.clearSwipeRevealWatchdog();
            this.clearNavigationBurstState();
        },

        async onGlobalArrowNavigate(direction, event = null) {
            if (event?.__quranReaderInputHandled) {
                return;
            }

            if (event && typeof event === 'object') {
                event.__quranReaderInputHandled = true;
            }

            if (this.search.modalOpen) {
                return;
            }

            if (event?.ctrlKey || event?.metaKey || event?.altKey || event?.shiftKey) {
                return;
            }

            if (
                event?.target?.closest?.(
                    'input:not([type="range"]), textarea, select, [contenteditable="true"], [role="textbox"]',
                )
            ) {
                return;
            }

            if (event?.cancelable) {
                event.preventDefault();
            }

            if (direction === 'left') {
                if (this.triggerChevronButtonClick('next', 'keyboard')) {
                    return;
                }

                await this.goNextFromChevron('keyboard');

                return;
            }

            if (direction === 'right') {
                if (this.triggerChevronButtonClick('prev', 'keyboard')) {
                    return;
                }

                await this.goPreviousFromChevron('keyboard');
            }
        },

        dispatchPageNavigationRequest(targetPage, source = 'generic') {
            window.dispatchEvent(
                new CustomEvent('quran-go-page', {
                    detail: {
                        page: clampPage(targetPage, this.maxPage),
                        source,
                    },
                }),
            );
        },

        commitPageSliderTargetPage(
            targetPage,
            { source = 'page-slider-commit', dedupeWindowMs = 340 } = {},
        ) {
            if (this.wirdModeActive) {
                return false;
            }

            const normalizedTargetPage = clampPage(targetPage ?? this.pageInput, this.maxPage);
            const now = Date.now();
            const normalizedDedupeWindowMs = Math.max(
                120,
                Math.trunc(Number(dedupeWindowMs) || 340),
            );

            if (
                normalizedTargetPage === this._lastPageSliderCommitPage &&
                now - this._lastPageSliderCommitAt < normalizedDedupeWindowMs
            ) {
                return false;
            }

            this._lastPageSliderCommitPage = normalizedTargetPage;
            this._lastPageSliderCommitAt = now;
            this.pageInput = normalizedTargetPage;
            this._lastPageInputVisualValue = normalizedTargetPage;
            this.dispatchPageNavigationRequest(
                normalizedTargetPage,
                String(source ?? '').trim() || 'page-slider-commit',
            );

            return true;
        },

        async goToPage(
            pageNumber,
            {
                direction = 'next',
                animate = true,
                activeAyahIndex = null,
                searchHighlightAyahIndex = null,
                forceRefit = false,
                source = 'generic',
            } = {},
        ) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);
            const normalizedSearchHighlightAyahIndex =
                Number.isFinite(Number(searchHighlightAyahIndex)) &&
                Number(searchHighlightAyahIndex) > 0
                    ? Math.trunc(Number(searchHighlightAyahIndex))
                    : 0;
            const nextSearchHighlightedAyahIndex =
                source === 'search-result' ? normalizedSearchHighlightAyahIndex : 0;
            this.clearWordPressState();
            this.hoveredAyahIndex = 0;
            this.hoveredWordIndex = 0;

            if (normalizedPage === this.pageNumber && this.mushafLines.length > 0) {
                if (this.pageInput !== normalizedPage) {
                    this.triggerPageCounterPulse(this.pageInput, normalizedPage, {
                        source,
                    });
                }

                this.pageInput = normalizedPage;
                this._lastPageInputVisualValue = normalizedPage;
                this.persistLastPageNumber(normalizedPage);
                this.searchHighlightedAyahIndex = nextSearchHighlightedAyahIndex;
                this.refreshSurahTriggerCaption(Boolean(animate));
                this.refreshMobileEdgeCaptions(Boolean(animate));
                this.syncSearchActiveSurahNumber();

                const hasOpenModals = this.hasBlockingModalLifecycleState({
                    recoverStaleState: true,
                });

                if (!hasOpenModals) {
                    this.recoverStaleModalLifecycleState();

                    if (forceRefit) {
                        await this.layoutPageGuaranteed({ revealDelayMs: 200 });
                    } else if (
                        this.isFittingPage ||
                        this._lastFittedPageNumber !== normalizedPage
                    ) {
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 140,
                            maxAttempts: 3,
                            useIdleFit: false,
                        });
                    }

                    if (this.hasRenderablePage()) {
                        this.isFittingPage = false;
                    }
                } else {
                    this.queuePendingPostModalTargetFit(normalizedPage);
                    this.schedulePendingModalCloseFit(normalizedPage, {
                        retries: 42,
                        delayMs: 84,
                        revealDelayMs: 220,
                        maxAttempts: 5,
                    });
                }

                return;
            }

            const isModalSourcedNavigation = [
                'search-result',
                'surah-directory',
                'bookmark',
                'history-navigation',
                'page-jump',
            ].includes(String(source ?? ''));

            if (isModalSourcedNavigation && this.shouldUseImmersiveReaderChrome()) {
                this.isReaderChromeVisible = false;
                this.syncReaderChromeDocumentClass();
            }

            this.isLoadingPage = true;
            let didCompletePageTransition = false;
            let didAbortPageTransition = false;
            const pageAbortController = this.beginActivePageLoadAbortController();

            try {
                const payloadPromise = this.getPagePayload(normalizedPage, {
                    signal: pageAbortController?.signal ?? null,
                });
                const transitionDelayMs = this.isHighFrequencyNavigationSource(source) ? 68 : 128;

                if (this.mushafLines.length > 0) {
                    this.isTransitioningOutPage = true;
                    this.isFittingPage = false;
                    await this.nextTickAsync();
                    await wait(transitionDelayMs);
                    this.isTransitioningOutPage = false;
                    this.isFittingPage = true;
                }

                const payload = await payloadPromise;

                if (payload === null) {
                    didAbortPageTransition = true;

                    return;
                }

                const pendingTargetPage = Math.max(
                    0,
                    Math.trunc(Number(this._pendingNavigationRequest?.targetPage ?? 0)),
                );

                if (pendingTargetPage > 0 && pendingTargetPage !== normalizedPage) {
                    return;
                }

                this.applyPayload(payload, { setPageNumber: true });
                this.persistLastPageNumber(this.pageNumber);
                this.refreshSurahTriggerCaption(animate);
                this.refreshMobileEdgeCaptions(animate);
                this.syncSearchActiveSurahNumber();
                this.activeAyahIndex =
                    this.shouldPersistActivationIndexes() &&
                    Number.isFinite(Number(activeAyahIndex)) &&
                    Number(activeAyahIndex) > 0
                        ? Math.trunc(Number(activeAyahIndex))
                        : 0;
                this.activeWordIndex = 0;
                this.searchHighlightedAyahIndex = nextSearchHighlightedAyahIndex;

                if (animate) {
                    this.playPageMotion(direction);
                }

                if (this.navigationBurstRemainingMsFor(source) <= 0) {
                    this.prefetchNeighborPages(normalizedPage);
                }

                const shouldUseFastFitPriority = this.isFastFitPrioritySource(source);
                await this.layoutPageGuaranteed({
                    revealDelayMs: shouldUseFastFitPriority
                        ? 170
                        : this.isHighFrequencyNavigationSource(source)
                          ? 180
                          : 220,
                    maxAttempts: shouldUseFastFitPriority
                        ? 3
                        : this.isHighFrequencyNavigationSource(source)
                          ? 3
                          : 4,
                    useIdleFit: !shouldUseFastFitPriority,
                });
                didCompletePageTransition = true;
            } catch (error) {
                if (error?.name === 'AbortError') {
                    didAbortPageTransition = true;

                    return;
                }

                if (this.hasRenderablePage()) {
                    this.isFittingPage = false;
                }
            } finally {
                if (this._activePageAbortController === pageAbortController) {
                    this._activePageAbortController = null;
                }

                this.isLoadingPage = false;
                this.isTransitioningOutPage = false;

                if (!didCompletePageTransition && this.hasRenderablePage()) {
                    this.scheduleLayout({ revealDelayMs: 150 });
                }

                if (
                    didAbortPageTransition &&
                    this._pendingNavigationRequest === null &&
                    this.hasRenderablePage() &&
                    !this._layoutActivePromise
                ) {
                    this.isFittingPage = false;
                }

                this.flushQueuedLayoutRequest();

                if (this._pendingNavigationRequest !== null) {
                    if (this._navigationRevealLocked) {
                        this.schedulePendingNavigationCommit(
                            this.resolveNavigationCommitDelay(
                                this._pendingNavigationRequest?.source ?? 'generic',
                                0,
                            ),
                        );
                    } else {
                        void this.commitPendingNavigation();
                    }
                }

                this.scheduleIdleWarmup();
            }
        },

        onPageInputInput() {
            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }

            const normalizedInputPage = clampPage(this.pageInput, this.maxPage);
            const previousVisualPage = clampPage(this._lastPageInputVisualValue, this.maxPage);

            if (normalizedInputPage !== previousVisualPage) {
                this.triggerPageCounterPulse(previousVisualPage, normalizedInputPage, {
                    source: 'page-input',
                });
            }

            this._lastPageInputVisualValue = normalizedInputPage;
        },

        queueWirdSliderCommit(step, { source = 'slider', delayMs = 0 } = {}) {
            if (!this.wirdModeActive) {
                return;
            }

            const range = this.wirdRangeState();
            const normalizedStep = this.normalizeIntegerFlag(step, this.sliderValue(), {
                min: 0,
                max: range.maxStep,
            });
            this._wirdSliderPendingCommitStep = normalizedStep;

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }

            this._wirdSliderInputCommitTimer = window.setTimeout(
                () => {
                    this._wirdSliderInputCommitTimer = null;

                    if (!this.wirdModeActive) {
                        this._wirdSliderPendingCommitStep = null;

                        return;
                    }

                    const currentRange = this.wirdRangeState();
                    const commitStep = this.normalizeIntegerFlag(
                        this._wirdSliderPendingCommitStep,
                        this.sliderValue(),
                        {
                            min: 0,
                            max: currentRange.maxStep,
                        },
                    );

                    this._wirdSliderPendingCommitStep = null;
                    this._wirdSliderLastInputStep = null;
                    this._wirdSliderLastInputAt = 0;
                    this.clearWirdSliderVisualTween();
                    this.wirdSliderVisualStep = commitStep;
                    const record = this.ensureWirdDailyRecord();
                    const targetPage = this.wirdTargetPageFromStep(commitStep, record);
                    const now = Date.now();

                    if (
                        targetPage === this._wirdLastCommittedTargetPage &&
                        commitStep === this._wirdLastCommittedStep &&
                        now - this._wirdLastCommittedAt < 320 &&
                        (this.isLoadingPage || this._pendingNavigationRequest !== null)
                    ) {
                        return;
                    }

                    this._wirdLastCommittedTargetPage = targetPage;
                    this._wirdLastCommittedStep = commitStep;
                    this._wirdLastCommittedAt = now;
                    void this.navigateWirdToStep(commitStep, source);
                },
                Math.max(0, Math.trunc(Number(delayMs) || 0)),
            );
        },

        onSliderPointerRelease(event = null) {
            if (typeof document === 'undefined') {
                return;
            }

            const releaseTarget = event?.target;
            const targetSlider =
                releaseTarget instanceof HTMLInputElement && releaseTarget.type === 'range'
                    ? releaseTarget
                    : null;
            const activeElement =
                document.activeElement instanceof HTMLInputElement &&
                document.activeElement.type === 'range'
                    ? document.activeElement
                    : null;
            const sliderElement = targetSlider ?? activeElement;

            if (!(sliderElement instanceof HTMLInputElement)) {
                return;
            }

            if (!sliderElement.classList.contains('quran-page-slider')) {
                return;
            }

            const sliderTargetPage = clampPage(
                sliderElement.value ?? this.pageInput ?? this.pageNumber,
                this.maxPage,
            );
            const shouldAttemptFallbackCommit =
                !this.wirdModeActive &&
                sliderTargetPage > 0 &&
                sliderTargetPage !== this.pageNumber;

            window.setTimeout(() => {
                if (document.activeElement === sliderElement) {
                    sliderElement.blur();
                }

                this._pageSliderInteractionActive = false;
            }, 0);

            if (shouldAttemptFallbackCommit) {
                this.commitPageSliderTargetPage(sliderTargetPage, {
                    source: 'page-slider-commit',
                });
            }
        },

        async onSliderInput(event = null) {
            if (!this.wirdModeActive) {
                const targetPage = clampPage(event?.target?.value ?? this.pageInput, this.maxPage);
                this._pageSliderInteractionActive = true;

                this.pageInput = targetPage;
                this.onPageInputInput();

                return;
            }

            const range = this.wirdRangeState();
            const step = this.normalizeIntegerFlag(event?.target?.value, this.sliderValue(), {
                min: 0,
                max: range.maxStep,
            });
            const targetPage = this.wirdTargetPageFromStep(step, range.record);
            const previousCounterValue = this.pageCounterCurrentDisplayValue();
            const nextCounterValue = step + 1;

            if (nextCounterValue !== previousCounterValue) {
                this.triggerPageCounterPulse(previousCounterValue, nextCounterValue, {
                    source: 'page-slider-logical',
                });
            }

            this.pageInput = targetPage;
            this._lastPageInputVisualValue = targetPage;

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }

            this.clearWirdSliderVisualTween();
            this.wirdSliderVisualStep = step;
            this._wirdSliderLastInputStep = step;
            this._wirdSliderLastInputAt = Date.now();
            this.queueWirdSliderCommit(step, {
                source: 'slider-input-idle',
                delayMs: 140,
            });
        },

        async onSliderCommit(event = null) {
            this.onSliderPointerRelease(event);

            if (!this.wirdModeActive) {
                const targetPage = clampPage(event?.target?.value ?? this.pageInput, this.maxPage);
                this._pageSliderInteractionActive = false;
                this.commitPageSliderTargetPage(targetPage, {
                    source: 'page-slider-commit',
                });

                return;
            }

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }

            const range = this.wirdRangeState();
            const directCommitStep = this.normalizeIntegerFlag(
                event?.target?.value,
                this.sliderValue(),
                {
                    min: 0,
                    max: range.maxStep,
                },
            );
            const hasRecentInputStep =
                Number.isFinite(Number(this._wirdSliderLastInputStep)) &&
                Date.now() - this._wirdSliderLastInputAt <= 520;
            const freshestInputStep = Number.isFinite(Number(this._wirdSliderPendingCommitStep))
                ? this._wirdSliderPendingCommitStep
                : this._wirdSliderLastInputStep;
            const commitStep = hasRecentInputStep
                ? this.normalizeIntegerFlag(freshestInputStep, directCommitStep, {
                      min: 0,
                      max: range.maxStep,
                  })
                : directCommitStep;

            this.queueWirdSliderCommit(commitStep, {
                source: 'slider',
                delayMs: 0,
            });
        },

        async onPageInputBlur() {
            if (this.wirdModeActive) {
                return;
            }

            const now = Date.now();
            const targetPage = clampPage(this.pageInput, this.maxPage);

            if (
                targetPage === this._lastPageInputCommitPage &&
                now - this._lastPageInputCommitAt < 420
            ) {
                return;
            }

            await this.onPageInputCommit({
                force: true,
                commitNow: true,
                source: 'page-input-blur',
            });
        },

        async onPageInputCommit({ force = false, commitNow = false, source = 'page-input' } = {}) {
            if (this.wirdModeActive) {
                return;
            }

            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }

            const targetPage = clampPage(this.pageInput, this.maxPage);
            const direction = this.resolveNavigationDirection(targetPage);
            this._lastPageInputCommitPage = targetPage;
            this._lastPageInputCommitAt = Date.now();

            this.pageInput = targetPage;
            this._lastPageInputVisualValue = targetPage;

            if (!force && targetPage === this.pageNumber) {
                return;
            }

            await this.navigateToPage(targetPage, {
                direction,
                animate: true,
                source,
                forceRefit: true,
                commitNow: Boolean(commitNow),
            });
        },

        applyPayload(payload, { setPageNumber = false, persistPageNumber = true } = {}) {
            const normalizedPayload = normalizePayload(payload);
            const previousPageNumber = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const payloadPageNumber = clampPage(
                normalizedPayload.pageNumber,
                normalizedPayload.maxPage,
            );

            this.ready = normalizedPayload.ready;
            this.maxPage = normalizedPayload.maxPage;
            this.mushafLines = normalizedPayload.mushafLines;
            this.lineWordGapAdjustments = {};
            this._lastWordGapRebalancedPageNumber = 0;
            this.rebuildWordSelectionIndex();
            this.useCenteredAyahLayout = normalizedPayload.useCenteredAyahLayout;
            this.qpcPageFontFamily = normalizedPayload.qpcPageFontFamily;
            this.qpcPageFontUrl = normalizedPayload.qpcPageFontUrl;
            this.qpcPageFontFormat = normalizedPayload.qpcPageFontFormat;
            this.basmallahFontFamily = normalizedPayload.basmallahFontFamily;
            this.basmallahFontUrl = normalizedPayload.basmallahFontUrl;
            this.basmallahFontFormat = normalizedPayload.basmallahFontFormat;
            this.basmallahText = normalizedPayload.basmallahText;
            this.surahHeaderFontFamily =
                normalizedPayload.surahHeaderFontFamily ?? this.surahHeaderFontFamily;
            this.surahHeaderFontUrl =
                normalizedPayload.surahHeaderFontUrl ?? this.surahHeaderFontUrl;
            this.surahHeaderFontFormat =
                normalizedPayload.surahHeaderFontFormat ?? this.surahHeaderFontFormat;
            this.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah =
                normalizedPayload.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah ??
                this.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah;

            if (
                normalizedPayload.surahNames &&
                Object.keys(normalizedPayload.surahNames).length > 0
            ) {
                this.search.surahNames = normalizedPayload.surahNames;
            }

            const hasIncomingSurahDirectory =
                Array.isArray(normalizedPayload.surahDirectory) &&
                normalizedPayload.surahDirectory.length > 0;

            this.buildSurahDirectory(
                hasIncomingSurahDirectory
                    ? normalizedPayload.surahDirectory
                    : this.search.surahDirectory,
            );
            this._loadedPayloadPageNumber = payloadPageNumber;

            if (setPageNumber) {
                this.pageNumber = payloadPageNumber;

                if (persistPageNumber) {
                    this.persistLastPageNumber(this.pageNumber);
                }
            }

            if (this.pageInput !== this.pageNumber) {
                this.triggerPageCounterPulse(this.pageInput, this.pageNumber, {
                    source: 'payload-sync',
                });
            }

            this.pageInput = this.pageNumber;
            this._lastPageInputVisualValue = this.pageNumber;
            this.syncPageFontFace();
            this.syncBasmallahFontFace();
            this.syncSurahHeaderFontFace();

            if (
                this.pageNumber !== previousPageNumber ||
                this._lastFittedPageNumber !== this.pageNumber
            ) {
                this.resetCurrentPageFitStyles();
                this._lastFittedPageNumber = 0;
                this._fitSanityContextKey = '';
                this._fitSanityContextAttemptCount = 0;
                this._fitSanityContextLastWidth = 0;
                this._fitSanityContextLastHeight = 0;
                this._fitSanityContextOutcome = '';
                this._fitSanitySuppressedUntil = 0;
                this._fitSanityDisabledContextKey = '';
                this._fontReadyRecoveryAttemptPage = 0;
                this._fontReadyRecoveryAttemptCount = 0;
                this._fontReadyRecoveryLastAt = 0;
            }
        },

        async nextTickAsync() {
            await new Promise((resolve) => this.$nextTick(resolve));
        },

        async resolveWithTimeout(
            promise,
            timeoutMs = pageFontLoadTimeoutMs,
            { timeoutValue = 'timeout', resolveOnReject = true } = {},
        ) {
            let timerId = null;
            const normalizedTimeoutMs = Math.max(
                120,
                Math.trunc(Number(timeoutMs) || pageFontLoadTimeoutMs),
            );
            const timeoutPromise = new Promise((resolve) => {
                timerId = window.setTimeout(() => {
                    resolve(timeoutValue);
                }, normalizedTimeoutMs);
            });

            try {
                if (resolveOnReject) {
                    return await Promise.race([
                        Promise.resolve(promise)
                            .then(() => 'resolved')
                            .catch(() => 'rejected'),
                        timeoutPromise,
                    ]);
                }

                return await Promise.race([Promise.resolve(promise), timeoutPromise]);
            } finally {
                if (timerId !== null) {
                    clearTimeout(timerId);
                }
            }
        },

        scheduleFontReadyRecoveryRefit(
            pageNumber = this.pageNumber,
            { delayMs = pageFontReadyRecoveryDelayMs } = {},
        ) {
            if (!document.fonts?.ready) {
                return;
            }

            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);

            if (normalizedPageNumber <= 0) {
                return;
            }

            const now = Date.now();
            const isSameRecoveryPage = this._fontReadyRecoveryAttemptPage === normalizedPageNumber;

            if (!isSameRecoveryPage) {
                this._fontReadyRecoveryAttemptPage = normalizedPageNumber;
                this._fontReadyRecoveryAttemptCount = 0;
                this._fontReadyRecoveryLastAt = 0;
            }

            if (
                this._fontReadyRecoveryTimer !== null &&
                this._fontReadyRecoveryPage === normalizedPageNumber
            ) {
                return;
            }

            if (
                this._fontReadyRecoveryAttemptCount >= 2 &&
                now - this._fontReadyRecoveryLastAt < 4_800
            ) {
                return;
            }

            if (now - this._fontReadyRecoveryLastAt < 2_200) {
                return;
            }

            if (this._fontReadyRecoveryTimer !== null) {
                clearTimeout(this._fontReadyRecoveryTimer);
                this._fontReadyRecoveryTimer = null;
            }

            this._fontReadyRecoveryAttemptCount += 1;
            this._fontReadyRecoveryLastAt = now;
            this._fontReadyRecoveryPage = normalizedPageNumber;
            this._fontReadyRecoveryTimer = window.setTimeout(
                () => {
                    this._fontReadyRecoveryTimer = null;
                    const recoveryTargetPage = Math.max(
                        0,
                        Math.trunc(Number(this._fontReadyRecoveryPage ?? 0)),
                    );

                    if (recoveryTargetPage <= 0) {
                        return;
                    }

                    Promise.resolve(document.fonts.ready)
                        .then(() => {
                            if (
                                !this.hasRenderablePage() ||
                                this.pageNumber !== recoveryTargetPage ||
                                this.isLoadingPage
                            ) {
                                return;
                            }

                            // Do not alter fit after reveal begins.
                            if (
                                this.isCurrentPageVisiblyReady() &&
                                this.pageFitState() === 'ready'
                            ) {
                                return;
                            }

                            this._bypassNextFitCache = true;
                            this.scheduleLayout({
                                revealDelayMs: 130,
                                maxAttempts: 5,
                            });
                        })
                        .catch(() => {
                            // Ignore delayed font readiness failures.
                        });
                },
                Math.max(120, Math.trunc(Number(delayMs) || pageFontReadyRecoveryDelayMs)),
            );
        },

        async waitForPageFontReady() {
            const family = String(this.qpcPageFontFamily ?? '').trim();
            const basmallahFamily = String(this.basmallahFontFamily ?? '').trim();
            const surahHeaderFamily = String(this.surahHeaderFontFamily ?? '').trim();
            const fallbackMushafFamily = 'MadinaQuran';
            const trackedFamilies = [
                family,
                basmallahFamily,
                surahHeaderFamily,
                fallbackMushafFamily,
            ]
                .map((entry) => String(entry ?? '').trim())
                .filter(Boolean);

            if (trackedFamilies.length === 0 || !document.fonts?.load) {
                return;
            }

            const fontLoadTasks = [];

            try {
                if (family) {
                    fontLoadTasks.push(
                        this.resolveWithTimeout(
                            document.fonts.load(`32px '${family}'`, this.preferredPageProbeText()),
                            pageFontLoadTimeoutMs,
                        ),
                    );
                }

                if (basmallahFamily) {
                    fontLoadTasks.push(
                        this.resolveWithTimeout(
                            document.fonts.load(
                                `32px '${basmallahFamily}'`,
                                this.preferredBasmallahText(),
                            ),
                            pageFontLoadTimeoutMs,
                        ),
                    );
                }

                if (surahHeaderFamily) {
                    fontLoadTasks.push(
                        this.resolveWithTimeout(
                            document.fonts.load(`32px '${surahHeaderFamily}'`, 'الفاتحة'),
                            pageFontLoadTimeoutMs,
                        ),
                    );
                }

                fontLoadTasks.push(
                    this.resolveWithTimeout(
                        document.fonts.load(
                            `32px '${fallbackMushafFamily}'`,
                            this.preferredPageProbeText(),
                        ),
                        pageFontLoadTimeoutMs,
                    ),
                );

                const loadOutcomes = await Promise.all(fontLoadTasks);
                const readyOutcome = await this.resolveWithTimeout(
                    document.fonts.ready,
                    pageFontReadyTimeoutMs,
                );
                const hasMissingTrackedFamily =
                    typeof document.fonts?.check === 'function' &&
                    trackedFamilies.some(
                        (fontFamily) =>
                            !document.fonts.check(
                                `32px '${fontFamily}'`,
                                fontFamily === fallbackMushafFamily
                                    ? this.preferredPageProbeText()
                                    : 'الحمد لله',
                            ),
                    );
                const didTimeout =
                    readyOutcome === 'timeout' ||
                    loadOutcomes.some((outcome) => outcome === 'timeout') ||
                    hasMissingTrackedFamily;

                if (didTimeout) {
                    this._bypassNextFitCache = true;
                    this.scheduleFontReadyRecoveryRefit(this.pageNumber);
                } else if (this._fontReadyRecoveryAttemptPage === this.pageNumber) {
                    const hadPendingTimerForCurrentPage =
                        this._fontReadyRecoveryTimer !== null &&
                        this._fontReadyRecoveryPage === this.pageNumber;
                    this._fontReadyRecoveryAttemptCount = 0;
                    this._fontReadyRecoveryLastAt = 0;
                    this._fontReadyRecoveryPage = 0;

                    if (hadPendingTimerForCurrentPage) {
                        clearTimeout(this._fontReadyRecoveryTimer);
                        this._fontReadyRecoveryTimer = null;
                    }
                }
            } catch (_) {
                // Ignore font loading failures and continue with fallback glyphs.
            }
        },

        normalizeFontFamilyToken(value) {
            return String(value ?? '')
                .trim()
                .replace(/^['"]+|['"]+$/g, '')
                .toLowerCase();
        },

        trackedPageFontFamilies() {
            const families = [
                this.qpcPageFontFamily,
                this.basmallahFontFamily,
                this.surahHeaderFontFamily,
                'MadinaQuran',
            ]
                .map((entry) => String(entry ?? '').trim())
                .filter(Boolean);

            return Array.from(new Set(families));
        },

        areTrackedPageFontsLoaded() {
            if (!document.fonts || typeof document.fonts !== 'object') {
                return true;
            }

            const trackedFamilies = this.trackedPageFontFamilies();

            if (trackedFamilies.length === 0) {
                return true;
            }

            const fontFaces = Array.from(document.fonts);

            return trackedFamilies.every((family) => {
                const normalizedFamily = this.normalizeFontFamilyToken(family);
                const matchingFaces = fontFaces.filter(
                    (fontFace) =>
                        this.normalizeFontFamilyToken(fontFace?.family ?? '') === normalizedFamily,
                );

                if (matchingFaces.length > 0) {
                    return matchingFaces.every(
                        (fontFace) => String(fontFace?.status ?? '') === 'loaded',
                    );
                }

                if (typeof document.fonts.check === 'function') {
                    return document.fonts.check(
                        `32px '${family}'`,
                        family === 'MadinaQuran' ? this.preferredPageProbeText() : 'الحمد لله',
                    );
                }

                return false;
            });
        },

        preferredPageProbeText() {
            if (!Array.isArray(this.mushafLines)) {
                return 'ﱁﱂﱃ';
            }

            for (const line of this.mushafLines) {
                const words = Array.isArray(line?.words) ? line.words : [];

                for (const word of words) {
                    const text = String(word?.text ?? '').trim();

                    if (text !== '') {
                        return text;
                    }
                }
            }

            return 'ﱁﱂﱃ';
        },

        async waitForFontReady(family) {
            const normalizedFamily = String(family ?? '').trim();

            if (!normalizedFamily || !document.fonts?.load) {
                return;
            }

            try {
                const loadOutcome = await this.resolveWithTimeout(
                    document.fonts.load(`32px '${normalizedFamily}'`, 'الحمد لله'),
                    pageFontLoadTimeoutMs,
                );
                const readyOutcome = await this.resolveWithTimeout(
                    document.fonts.ready,
                    pageFontReadyTimeoutMs,
                );

                if (loadOutcome === 'timeout' || readyOutcome === 'timeout') {
                    this.scheduleFontReadyRecoveryRefit(this.pageNumber);
                } else if (this._fontReadyRecoveryAttemptPage === this.pageNumber) {
                    this._fontReadyRecoveryAttemptCount = 0;
                    this._fontReadyRecoveryLastAt = 0;
                    this._fontReadyRecoveryPage = 0;
                }
            } catch (_) {
                // Ignore font loading failures and continue with fallback glyphs.
            }
        },

        syncDynamicFontFace({ styleId, family, url, format = 'woff2' }) {
            let styleTag = document.getElementById(styleId);
            const normalizedFamily = String(family ?? '').trim();
            const normalizedUrl = String(url ?? '').trim();
            const normalizedFormat = String(format ?? 'woff2').trim() || 'woff2';

            if (!normalizedFamily || !normalizedUrl) {
                if (styleTag) {
                    styleTag.remove();
                }

                return;
            }

            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = styleId;
                document.head.appendChild(styleTag);
            }

            styleTag.textContent = `@font-face { font-family: '${normalizedFamily}'; src: url('${normalizedUrl}') format('${normalizedFormat}'); font-display: block; }`;
        },

        syncPageFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-page-font',
                family: this.qpcPageFontFamily,
                url: this.qpcPageFontUrl,
                format: this.qpcPageFontFormat,
            });
        },

        syncBasmallahFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-basmallah-font',
                family: this.basmallahFontFamily,
                url: this.basmallahFontUrl,
                format: this.basmallahFontFormat,
            });
        },

        syncSurahHeaderFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-surah-header-font',
                family: this.surahHeaderFontFamily,
                url: this.surahHeaderFontUrl,
                format: this.surahHeaderFontFormat,
            });
        },

        clearLayoutTimers() {
            if (this._layoutRaf !== null) {
                cancelAnimationFrame(this._layoutRaf);
                this._layoutRaf = null;
            }

            if (this._revealTimer !== null) {
                clearTimeout(this._revealTimer);
                this._revealTimer = null;
            }

            this._revealBlockedSinceAt = 0;
            this._revealBlockedLayoutToken = 0;
        },

        scheduleReaderPanelLayoutRefresh() {
            if (this.$el instanceof Element && !this.$el.isConnected) {
                return;
            }

            if (this._readerPanelLayoutRaf !== null) {
                return;
            }

            this._readerPanelLayoutRaf = requestAnimationFrame(() => {
                this._readerPanelLayoutRaf = null;
                this._readerPanelLayoutSerial += 1;

                requestAnimationFrame(() => {
                    if (
                        !this.ready ||
                        this.isLoadingPage ||
                        !this.hasRenderablePage() ||
                        !this.isReaderElementVisible()
                    ) {
                        return;
                    }

                    this.scheduleLayout({ revealDelayMs: 150, maxAttempts: 5 });
                });
            });
        },

        clearFitResultCache({ persist = true } = {}) {
            this._fitResultByContext.clear();

            if (persist) {
                this.queuePersistedFitCacheWrite();
            }
        },

        normalizeLayoutRequest({ revealDelayMs = 180, maxAttempts = 4 } = {}) {
            return {
                revealDelayMs: Math.max(0, Math.trunc(Number(revealDelayMs) || 180)),
                maxAttempts: Math.max(2, Math.trunc(Number(maxAttempts) || 4)),
            };
        },

        queueLayoutRequest(request = {}) {
            const normalizedRequest = this.normalizeLayoutRequest(request);

            if (this._queuedLayoutRequest === null) {
                this._queuedLayoutRequest = normalizedRequest;

                return;
            }

            this._queuedLayoutRequest = {
                revealDelayMs: Math.min(
                    this._queuedLayoutRequest.revealDelayMs,
                    normalizedRequest.revealDelayMs,
                ),
                maxAttempts: Math.max(
                    this._queuedLayoutRequest.maxAttempts,
                    normalizedRequest.maxAttempts,
                ),
            };
        },

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

        async layoutPage({ revealDelayMs = 180, useIdleFit = true } = {}) {
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
            this.queuePageReveal(layoutToken, revealDelayMs);
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
        } = {}) {
            const layoutRequest = this.normalizeLayoutRequest({
                revealDelayMs,
                maxAttempts,
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

        setCurrentPageScale(baseScale, { forFitting = false } = {}) {
            const scaleElement = this.pageScaleElement();

            if (!(scaleElement instanceof HTMLElement)) {
                return;
            }

            const normalizedBaseScale = Math.max(0.05, Number(baseScale) || 1);
            const effectiveScale = Number(
                forFitting
                    ? normalizedBaseScale
                    : (normalizedBaseScale * this.pageScaleAdjustFactor()).toFixed(4),
            );
            const effectiveGapFactor = forFitting ? 1 : this.pageGapAdjustFactor();
            const effectiveYOffset = forFitting
                ? '0rem'
                : `${this.pageYOffsetAdjustRemValue().toFixed(3)}rem`;

            scaleElement.style.setProperty('--quran-page-scale', String(effectiveScale));
            scaleElement.style.setProperty(
                '--quran-page-gap-adjust-factor',
                String(effectiveGapFactor),
            );
            scaleElement.style.setProperty('--quran-page-y-offset-adjust', effectiveYOffset);
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

        handlePageScaleAdjustInput(event = null) {
            this.applyPageScaleAdjustValue(event?.target?.value ?? 0, {
                persist: false,
                refit: false,
            });
        },

        applyPageGapAdjustValue(value, { persist = true, refit = false } = {}) {
            this.quranPageGapAdjustValue = this.normalizePageGapAdjustValue(value, 0);

            if (persist) {
                this.persistPageGapAdjustValue(this.quranPageGapAdjustValue);
            }

            this.setCurrentPageScale(this.pageScale);

            if (refit) {
                this.schedulePageScaleAdjustRefit();
            }
        },

        handlePageGapAdjustInput(event = null) {
            this.applyPageGapAdjustValue(event?.target?.value ?? 0, {
                persist: false,
                refit: false,
            });
        },

        applyPageYOffsetAdjustValue(value, { persist = true, refit = false } = {}) {
            this.quranPageYOffsetAdjustValue = this.normalizePageYOffsetAdjustValue(value, 0);

            if (persist) {
                this.persistPageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue);
            }

            this.setCurrentPageScale(this.pageScale);

            if (refit) {
                this.schedulePageScaleAdjustRefit();
            }
        },

        handlePageYOffsetAdjustInput(event = null) {
            this.applyPageYOffsetAdjustValue(event?.target?.value ?? 0, {
                persist: false,
                refit: false,
            });
        },

        commitPageLayoutAdjustments() {
            this.persistPageScaleAdjustValue(this.quranPageScaleAdjustValue);
            this.persistPageGapAdjustValue(this.quranPageGapAdjustValue);
            this.persistPageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue);
            this.schedulePageScaleAdjustRefit();
        },

        toggleFontScaleOverlay() {
            if (!this.isAnyQuranReaderViewOpen()) {
                return;
            }

            this.isFontScaleOverlayVisible = !this.isFontScaleOverlayVisible;
            this.syncReaderChromeDocumentClass();
        },

        closeFontScaleOverlay() {
            this.isFontScaleOverlayVisible = false;
            this.syncReaderChromeDocumentClass();
        },

        shouldUseImmersiveReaderChrome() {
            return String(this.resolveCurrentBreakpointName() ?? '').trim() === 'base';
        },

        shouldShowImmersiveMobileEdgeCaptions() {
            void this._managerModalVersion;

            if (!this.shouldUseImmersiveReaderChrome()) {
                return false;
            }

            if (!this.doesShowImmersiveMobileEdgeCaptions) {
                return false;
            }

            if (!this.isAnyQuranReaderViewOpen()) {
                return false;
            }

            const hasManagerModalOpen =
                this.isSearchModalWindowVisible() ||
                this.isModalWindowVisibleById(this.historyModalId) ||
                this.isModalWindowVisibleById(this.bookmarksModalId) ||
                this.isModalWindowVisibleById(this.jumpPageModalId) ||
                this.search.modalOpen ||
                this.historyModalOpen ||
                this.bookmarksModalOpen ||
                this.jumpPageModalOpen;

            if (hasManagerModalOpen) {
                return false;
            }

            if (this.hasBlockingModalLifecycleState({ recoverStaleState: true })) {
                return false;
            }

            if (this.isReaderChromeVisible || this.isFontScaleOverlayVisible) {
                return false;
            }

            if (
                this.isCalibrating ||
                this._startupCalibrationPending ||
                !this.hasCompletedInitialMushafPreparation
            ) {
                return false;
            }

            return true;
        },

        mobileReaderSurahCaption() {
            const name = this.surahNameOnly(this.currentSurahNumber());
            const caption =
                name !== ''
                    ? name
                    : this.resolveCurrentSurahTriggerLabel()
                          .replace(/^\(\s*\d+\s*\)\s*-\s*/u, '')
                          .replace(/^\(\s*\d+\s*\)\s*/u, '')
                          .trim();

            if (this.wirdModeActive && caption !== '') {
                return `الورد اليومي - ${caption}`;
            }

            return caption;
        },

        mobileReaderPageCaption() {
            return this.formatReaderNumber(this.currentMushafPageDisplayValue());
        },

        canRevealReaderChrome() {
            if (!this.shouldUseImmersiveReaderChrome()) {
                return false;
            }

            if (!this.isAnyQuranReaderViewOpen()) {
                return false;
            }

            if (this.hasBlockingModalLifecycleState({ recoverStaleState: true })) {
                return false;
            }

            if (!this._immersiveEntryAwaitingFirstReveal) {
                return true;
            }

            if (
                this.isCalibrating ||
                this._startupCalibrationPending ||
                !this.hasCompletedInitialMushafPreparation ||
                this.isLoadingPage ||
                this.isFittingPage ||
                this._revealTimer !== null
            ) {
                return false;
            }

            return this.isCurrentPageVisiblyReady();
        },

        syncReaderChromeDocumentClass({ forceInactive = false } = {}) {
            if (typeof document === 'undefined' || !(document.body instanceof HTMLElement)) {
                return;
            }

            const isActive =
                !forceInactive &&
                this.shouldUseImmersiveReaderChrome() &&
                this.isAnyQuranReaderViewOpen();
            const canRevealChrome = isActive && this.canRevealReaderChrome();
            const isCalibrating =
                isActive &&
                (this.isCalibrating ||
                    this._startupCalibrationPending ||
                    !this.hasCompletedInitialMushafPreparation);

            if (!canRevealChrome && this.isReaderChromeVisible) {
                this.isReaderChromeVisible = false;
            }

            document.body.classList.toggle('quran-reader-immersive-active', isActive);
            document.body.classList.toggle(
                'quran-reader-immersive-chrome-visible',
                canRevealChrome && this.isReaderChromeVisible,
            );
            document.body.classList.toggle('quran-reader-calibrating', isCalibrating);
            document.body.classList.toggle(
                'quran-reader-font-scale-overlay-open',
                isActive && this.isFontScaleOverlayVisible,
            );

            const isFontScaleOverlayOpen = isActive && this.isFontScaleOverlayVisible;

            if (this._lastDispatchedFontScaleOverlayVisible !== isFontScaleOverlayOpen) {
                this._lastDispatchedFontScaleOverlayVisible = isFontScaleOverlayOpen;

                window.dispatchEvent(
                    new CustomEvent('quran-reader-font-scale-overlay-visibility', {
                        detail: {
                            open: isFontScaleOverlayOpen,
                        },
                    }),
                );
            }
        },

        isReaderChromeToggleTarget(event = null) {
            if (!this.shouldUseImmersiveReaderChrome()) {
                return false;
            }

            const target = event?.target instanceof Element ? event.target : null;

            if (!(target instanceof Element)) {
                return false;
            }

            if (target.closest('[data-quran-word-button], [data-quran-line-text]')) {
                return true;
            }

            return !target.closest(
                [
                    '[data-no-swipe]',
                    'button',
                    'a',
                    'input',
                    'textarea',
                    'select',
                    '[contenteditable="true"]',
                ].join(', '),
            );
        },

        showReaderChrome() {
            if (!this.shouldUseImmersiveReaderChrome()) {
                this.isReaderChromeVisible = false;
                this.syncReaderChromeDocumentClass();

                return;
            }

            if (!this.canRevealReaderChrome()) {
                this.isReaderChromeVisible = false;
                this.syncReaderChromeDocumentClass();

                return;
            }

            this.isReaderChromeVisible = true;
            this.syncReaderChromeDocumentClass();
        },

        hideReaderChrome() {
            this.isReaderChromeVisible = false;
            this.syncReaderChromeDocumentClass();
        },

        toggleReaderChrome() {
            if (!this.shouldUseImmersiveReaderChrome()) {
                this.hideReaderChrome();

                return;
            }

            if (!this.canRevealReaderChrome()) {
                this.isReaderChromeVisible = false;
                this.syncReaderChromeDocumentClass();

                return;
            }

            this.isReaderChromeVisible = !this.isReaderChromeVisible;
            this.syncReaderChromeDocumentClass();
        },

        handleReaderChromeToggleTap(event = null) {
            if (!this.isReaderChromeToggleTarget(event)) {
                return;
            }

            this.toggleReaderChrome();
        },

        readerPanelStyle() {
            void this._readerPanelLayoutSerial;

            const styleEntries = [
                `touch-action: ${this.shouldUseImmersiveReaderChrome() ? 'none' : 'pan-y'}`,
            ];
            const breakpointName = String(this.resolveCurrentBreakpointName() ?? '').trim();

            if (!['base', 'sm'].includes(breakpointName)) {
                return `${styleEntries.join('; ')};`;
            }

            const viewportHeight = Number(window.visualViewport?.height ?? window.innerHeight ?? 0);

            if (!Number.isFinite(viewportHeight) || viewportHeight <= 0) {
                return `${styleEntries.join('; ')};`;
            }

            const rootElement = this.$el?.firstElementChild;
            const rootStyles =
                rootElement instanceof Element ? window.getComputedStyle(rootElement) : null;

            if (this.shouldUseImmersiveReaderChrome()) {
                const edgePadding = Math.max(
                    0,
                    this.cssCustomLengthPixels(
                        rootStyles,
                        '--quran-immersive-panel-edge-padding',
                        rootElement,
                        10,
                    ),
                );
                const availablePanelHeight = Math.max(1, viewportHeight - edgePadding);

                styleEntries.push(
                    `height: ${Math.round(availablePanelHeight)}px`,
                    `width: min(calc(100vw - ${Math.round(edgePadding * 2)}px), 25rem)`,
                );

                return `${styleEntries.join('; ')};`;
            }

            const stackClearance = this.cssCustomLengthPixels(
                rootStyles,
                '--quran-fit-panel-stack-clearance',
                rootElement,
                10,
            );
            const fallbackStackBottom = this.cssCustomLengthPixels(
                rootStyles,
                '--quran-fit-panel-top-reserve',
                rootElement,
                breakpointName === 'base' ? 64 : 70,
            );
            const stageRect = this.$el
                ?.closest?.('.quran-app-reader-stage')
                ?.getBoundingClientRect?.();
            const stackElement = document.querySelector('.app-action-buttons-stack');
            const stackRects = Array.from(
                stackElement instanceof Element
                    ? [stackElement, ...stackElement.querySelectorAll('[data-stack-item]')]
                    : [],
            )
                .map((element) => element?.getBoundingClientRect?.() ?? null)
                .filter(
                    (rect) =>
                        rect &&
                        Number.isFinite(rect.top) &&
                        Number.isFinite(rect.bottom) &&
                        rect.width > 0 &&
                        rect.height > 0 &&
                        rect.bottom <= viewportHeight * 0.38,
                );
            const stackBottom = stackRects.reduce(
                (bottom, rect) => Math.max(bottom, Number(rect.bottom ?? 0)),
                0,
            );
            const effectiveStackBottom = Math.max(stackBottom, fallbackStackBottom);
            const hasUsableStageBottom =
                Number.isFinite(stageRect?.bottom) &&
                Number(stageRect.bottom) > effectiveStackBottom
                    ? true
                    : false;
            const stageBottom = hasUsableStageBottom
                ? Math.min(viewportHeight, Number(stageRect.bottom))
                : viewportHeight;
            const availableViewportHeight = viewportHeight - effectiveStackBottom - stackClearance;
            const availableStageHeight = stageBottom - effectiveStackBottom - stackClearance;
            const minimumPanelHeight = breakpointName === 'base' ? 320 : 420;
            const rawAvailablePanelHeight = hasUsableStageBottom
                ? Math.min(availableViewportHeight, availableStageHeight)
                : availableViewportHeight;
            const availablePanelHeight = Math.max(
                1,
                Math.max(
                    Math.min(minimumPanelHeight, rawAvailablePanelHeight),
                    rawAvailablePanelHeight,
                ),
            );

            if (breakpointName === 'base') {
                styleEntries.push(
                    `height: ${Math.round(availablePanelHeight)}px`,
                    'width: min(91vw, 25rem)',
                );
            } else {
                styleEntries.push(
                    `height: min(${Math.round(availablePanelHeight)}px, 82svh, 50rem)`,
                );
            }

            return `${styleEntries.join('; ')};`;
        },

        pageFitState() {
            if (this.isTransitioningOutPage) {
                return 'fading-out';
            }

            if (this.isFittingPage || this.hasBlockingModalLifecycleState()) {
                return 'fitting';
            }

            return 'ready';
        },
    };
};
