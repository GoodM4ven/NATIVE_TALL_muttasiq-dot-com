export const createReaderNavigationFitSurahQuickNavAndBurstModule = (deps) => {
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
        async mountReaderAction(actionName) {
            const normalizedActionName = String(actionName ?? '').trim();

            if (normalizedActionName === '' || typeof this.$wire?.mountAction !== 'function') {
                return false;
            }

            if (typeof this.$wire?.unmountAction === 'function') {
                try {
                    await this.$wire.unmountAction(false);
                } catch (_) {
                    //
                }
            }

            try {
                await this.$wire.mountAction(normalizedActionName);

                return true;
            } catch (_) {
                return false;
            }
        },

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
            void this.mountReaderAction('searchQuran');
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
            if (typeof this.openBookmarksModal === 'function') {
                void this.openBookmarksModal();

                return;
            }

            void this.mountReaderAction('bookmarksManager');
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
    };
};
