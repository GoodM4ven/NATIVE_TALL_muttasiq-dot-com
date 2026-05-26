export const createReaderNavigationFitPageAdjustAndChromeModule = (deps) => {
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
        handlePageScaleAdjustInput(event = null) {
            this.applyPageScaleAdjustValue(event?.target?.value ?? 0, {
                persist: false,
                refit: false,
            });
        },

        applyPageTypeScaleAdjustValue(value, { persist = true, refit = false } = {}) {
            this.quranPageTypeScaleAdjustValue = this.normalizePageTypeScaleAdjustValue(value, 0);

            if (persist) {
                this.persistPageTypeScaleAdjustValue(this.quranPageTypeScaleAdjustValue);
            }

            this.setCurrentPageScale(this.pageScale);

            if (refit) {
                this.schedulePageScaleAdjustRefit();
            }
        },

        handlePageTypeScaleAdjustInput(event = null) {
            this.applyPageTypeScaleAdjustValue(event?.target?.value ?? 0, {
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
            this.persistPageTypeScaleAdjustValue(this.quranPageTypeScaleAdjustValue);
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

            return this.isCurrentPageVisiblyReady() || this.isCurrentPageContentVisible(0.12);
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

            if (!['base', 'sm', 'md', 'lg'].includes(breakpointName)) {
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
            } else if (breakpointName === 'sm') {
                styleEntries.push(
                    `height: min(${Math.round(availablePanelHeight)}px, 82svh, 50rem)`,
                );
            } else {
                styleEntries.push(
                    `height: min(${Math.round(availablePanelHeight)}px, 84svh, 54rem)`,
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
