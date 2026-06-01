export const createManagerAndSearchActionsUiAndLocalIndexModule = (deps) => {
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
        ensureHistoryRowsAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._historyRowsAutoAnimateStop === 'function') {
                return;
            }

            const historyRowsContainer = this.$refs.historyRowsList;

            if (!(historyRowsContainer instanceof Element)) {
                return;
            }

            this._historyRowsAutoAnimateStop = window.autoAnimate(historyRowsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        ensureBookmarksRowsAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._bookmarksRowsAutoAnimateStop === 'function') {
                return;
            }

            const bookmarksRowsContainer = this.$refs.bookmarksRowsList;

            if (!(bookmarksRowsContainer instanceof Element)) {
                return;
            }

            this._bookmarksRowsAutoAnimateStop = window.autoAnimate(bookmarksRowsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        teardownHistoryRowsAnimations() {
            if (typeof this._historyRowsAutoAnimateStop !== 'function') {
                return;
            }

            this._historyRowsAutoAnimateStop();
            this._historyRowsAutoAnimateStop = null;
        },

        teardownBookmarksRowsAnimations() {
            if (typeof this._bookmarksRowsAutoAnimateStop !== 'function') {
                return;
            }

            this._bookmarksRowsAutoAnimateStop();
            this._bookmarksRowsAutoAnimateStop = null;
        },

        teardownSearchResultAnimations() {
            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                this._searchResultsAutoAnimateStop();
                this._searchResultsAutoAnimateStop = null;
            }
        },

        beginModalNavigationCloseGuard(modalIds = [], { durationMs = null } = {}) {
            this._modalNavigationCloseGuardActive = true;
            this.holdPageHiddenForModalLifecycle({ animateFadeOut: false });

            const normalizedModalIds = (Array.isArray(modalIds) ? modalIds : [modalIds])
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            if (normalizedModalIds.length < 1) {
                return;
            }

            const resolvedDurationMs = Math.max(
                modalLifecycleSuppressionDurationMs,
                postModalFitRevealSettleDelayMs + 560,
                Math.trunc(Number(durationMs) || 0),
            );

            this.suppressModalLifecycleEffects(normalizedModalIds, {
                durationMs: resolvedDurationMs,
            });
        },

        endModalNavigationCloseGuard() {
            this._modalNavigationCloseGuardActive = false;
        },

        ensureSearchResultAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                return;
            }

            const searchResultsContainer = this.$refs.searchResultsList;

            if (!(searchResultsContainer instanceof Element)) {
                return;
            }

            this._searchResultsAutoAnimateStop = window.autoAnimate(searchResultsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        async handleSearchModalOpened() {
            const waitForSearchModalVisibility = async (
                expectedLifecycleToken,
                { maxAttempts = 18, delayMs = 24 } = {},
            ) => {
                const attempts = Math.max(1, Math.trunc(Number(maxAttempts) || 18));
                const waitDelayMs = Math.max(12, Math.trunc(Number(delayMs) || 24));

                for (let attempt = 0; attempt < attempts; attempt += 1) {
                    if (this._searchModalLifecycleToken !== expectedLifecycleToken) {
                        return false;
                    }

                    if (!this.search.modalOpen) {
                        return false;
                    }

                    if (this.isSearchModalWindowVisible()) {
                        return true;
                    }

                    await wait(waitDelayMs);
                }

                return this.isSearchModalWindowVisible();
            };

            const lifecycleToken =
                Math.max(0, Math.trunc(Number(this._searchModalLifecycleToken ?? 0))) + 1;
            this._searchModalLifecycleToken = lifecycleToken;
            this.search.modalOpen = true;
            this._searchNavigationInFlight = false;
            this._lastKnownModalOpenState = true;
            this._skipNextSearchModalCloseLayout = false;
            this.refreshSurahTriggerCaption(false);
            this.dispatchManagerModalsVisibilityState();

            await this.warmSearchIndex();

            if (this._searchModalLifecycleToken !== lifecycleToken || !this.search.modalOpen) {
                this.traceSearchModalLifecycle('opened-aborted', {
                    reason: 'stale-open',
                    lifecycleToken,
                    activeLifecycleToken: this._searchModalLifecycleToken,
                    modalOpen: this.search.modalOpen,
                });

                return;
            }

            if (!(await waitForSearchModalVisibility(lifecycleToken))) {
                this.traceSearchModalLifecycle('opened-aborted', {
                    reason: 'stale-open-not-visible',
                    lifecycleToken,
                    activeLifecycleToken: this._searchModalLifecycleToken,
                    modalOpen: this.search.modalOpen,
                });

                return;
            }

            if (this.search.preserveActiveSurahOnNextOpen) {
                this.search.preserveActiveSurahOnNextOpen = false;
            } else {
                this.syncSearchActiveSurahNumber();
            }

            this.search.query = '';
            this.setSearchResults([], { immediate: true });
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;

            await this.nextTickAsync();

            if (this._searchModalLifecycleToken !== lifecycleToken || !this.search.modalOpen) {
                this.traceSearchModalLifecycle('opened-aborted', {
                    reason: 'stale-open-next-tick',
                    lifecycleToken,
                    activeLifecycleToken: this._searchModalLifecycleToken,
                    modalOpen: this.search.modalOpen,
                });

                return;
            }

            if (!(await waitForSearchModalVisibility(lifecycleToken, { maxAttempts: 10 }))) {
                this.traceSearchModalLifecycle('opened-aborted', {
                    reason: 'stale-open-next-tick-not-visible',
                    lifecycleToken,
                    activeLifecycleToken: this._searchModalLifecycleToken,
                    modalOpen: this.search.modalOpen,
                });

                return;
            }

            this.setupSearchStreamObserver();
            this.clearSearchStreamTarget();
            this.ensureSearchResultAnimations();
            this.queueSearchModalInputSyncBinding();
            this.queueSurahDirectoryAutoFocus();
            this.searchModalInputElement()?.focus?.();
            window.setTimeout(() => {
                if (this._searchModalLifecycleToken !== lifecycleToken || !this.search.modalOpen) {
                    return;
                }

                const input = this.searchModalInputElement();

                if (!(input instanceof HTMLInputElement)) {
                    return;
                }

                try {
                    input.focus({ preventScroll: true });
                } catch (_) {
                    input.focus();
                }
            }, 48);
        },

        handleSearchModalClosed() {
            this._searchModalLifecycleToken =
                Math.max(0, Math.trunc(Number(this._searchModalLifecycleToken ?? 0))) + 1;
            this.cancelSurahDirectoryAutoFocus();
            this.clearSearchResultsUpdateQueue();
            this.unbindSearchModalInputSyncListener();
            this.teardownSearchStreamObserver();
            this.clearSearchStreamTarget();
            this.teardownSearchResultAnimations();
            this._searchRequestSerial += 1;
            this._searchRequestInFlight = false;
            this._searchNavigationInFlight = false;
            this._searchQueuedNormalizedQuery = null;
            this.search.modalOpen = false;
            this._lastKnownModalOpenState = false;
            this.searchActionModalId = '';
            this.search.query = '';
            this.setSearchResults([], { immediate: true });
            this.search.isLoading = false;
            this.dispatchManagerModalsVisibilityState();

            if (this._searchModalCloseDebounceTimer !== null) {
                clearTimeout(this._searchModalCloseDebounceTimer);
                this._searchModalCloseDebounceTimer = null;
            }

            if (this._skipNextSearchModalCloseLayout) {
                this._skipNextSearchModalCloseLayout = false;

                return;
            }
        },

        async requestModalCloseByKnownIds(
            knownModalIds = [],
            {
                onFallback = null,
                isModalStillVisible = null,
                quietly = false,
                allowLivewireUnmount = true,
                forceLivewireUnmount = false,
            } = {},
        ) {
            const normalizedModalIds = Array.from(
                new Set(
                    (Array.isArray(knownModalIds) ? knownModalIds : [knownModalIds])
                        .map((value) => String(value ?? '').trim())
                        .filter((value) => value !== ''),
                ),
            );
            const resolveModalVisibleState = (modalId = '') => {
                if (typeof isModalStillVisible === 'function') {
                    return Boolean(isModalStillVisible());
                }

                const normalizedModalId = String(modalId ?? '').trim();

                if (normalizedModalId === '') {
                    return false;
                }

                return this.isModalWindowVisibleById(normalizedModalId);
            };
            const closeEventName = quietly ? 'close-modal-quietly' : 'close-modal';

            for (const modalId of normalizedModalIds) {
                window.dispatchEvent(
                    new CustomEvent(closeEventName, {
                        detail: {
                            id: modalId,
                        },
                    }),
                );
                await wait(16);

                if (!resolveModalVisibleState(modalId)) {
                    return true;
                }
            }

            if (allowLivewireUnmount && typeof this.$wire?.unmountAction === 'function') {
                try {
                    await this.$wire.unmountAction(false);
                    await wait(16);

                    if (!resolveModalVisibleState(normalizedModalIds[0] ?? '')) {
                        return true;
                    }
                } catch (_) {
                    //
                }
            }

            if (typeof onFallback === 'function') {
                onFallback();
                await wait(16);
            }

            return !resolveModalVisibleState(normalizedModalIds[0] ?? '');
        },

        async requestSearchModalClose({ skipLayout = false } = {}) {
            if (skipLayout) {
                this._skipNextSearchModalCloseLayout = true;
            }

            const shouldAttemptLivewireUnmount = !Boolean(this._searchNavigationInFlight);
            const searchModalCloseTargetId = this.resolveSearchModalCloseTargetId();
            let didCloseSearchModal = await this.requestModalCloseByKnownIds(
                [
                    searchModalCloseTargetId,
                    this.searchActionModalId,
                    this.searchModalId,
                    this.searchModalDomId,
                ],
                {
                    onFallback: () => {},
                    isModalStillVisible: () => this.isSearchModalWindowVisible(),
                    quietly: false,
                    allowLivewireUnmount: shouldAttemptLivewireUnmount,
                    forceLivewireUnmount: shouldAttemptLivewireUnmount,
                },
            );

            if (!didCloseSearchModal && this.isSearchModalWindowVisible()) {
                const fallbackModalId = this.resolveSearchModalCloseTargetId();

                if (fallbackModalId !== '') {
                    window.dispatchEvent(
                        new CustomEvent('close-modal', {
                            detail: {
                                id: fallbackModalId,
                            },
                        }),
                    );
                }

                didCloseSearchModal = await this.waitForSearchModalToClose();
            }

            this.syncManagerModalFlagsFromVisibility();
            const modalStillVisible = this.isSearchModalWindowVisible();

            if (!modalStillVisible) {
                this.searchActionModalId = '';
            }

            if (this.search.modalOpen && modalStillVisible) {
                this.queueSearchModalCloseSync({ delayMs: 120 });

                return false;
            }

            if (this.search.modalOpen && !modalStillVisible) {
                this.handleSearchModalClosed();

                return true;
            }

            return !modalStillVisible || didCloseSearchModal;
        },

        async waitForSearchModalToClose(maxAttempts = 18, delayMs = 24) {
            const attempts = Math.max(1, Math.trunc(Number(maxAttempts) || 18));
            const normalizedDelayMs = Math.max(12, Math.trunc(Number(delayMs) || 24));

            for (let attempt = 0; attempt < attempts; attempt += 1) {
                if (!this.isSearchModalWindowVisible()) {
                    if (this.openModalCount() <= 0) {
                        this.recoverStaleModalLifecycleState();
                    }

                    return true;
                }

                await wait(normalizedDelayMs);
            }

            return !this.isSearchModalWindowVisible();
        },

        async waitForModalLifecycleToSettle(maxAttempts = 14, delayMs = 24) {
            const attempts = Math.max(1, Math.trunc(Number(maxAttempts) || 14));
            const waitDelayMs = Math.max(12, Math.trunc(Number(delayMs) || 24));

            for (let attempt = 0; attempt < attempts; attempt += 1) {
                if (this.openModalCount() <= 0) {
                    this.recoverStaleModalLifecycleState();
                }

                if (
                    this.openModalCount() <= 0 &&
                    !this._isModalLifecycleSettling &&
                    this._activeModalIds.size === 0
                ) {
                    return true;
                }

                await wait(waitDelayMs);
            }

            if (this.openModalCount() <= 0) {
                this.recoverStaleModalLifecycleState();
            }

            return (
                this.openModalCount() <= 0 &&
                !this._isModalLifecycleSettling &&
                this._activeModalIds.size === 0
            );
        },

        async requestHistoryModalClose() {
            await this.requestModalCloseByKnownIds([this.historyModalId], {
                onFallback: () => {
                    this.historyModalOpen = false;
                    this.teardownHistoryRowsAnimations();
                    this.dispatchManagerModalsVisibilityState();
                },
            });
        },

        async requestBookmarksModalClose() {
            await this.requestModalCloseByKnownIds([this.bookmarksModalId], {
                onFallback: () => {
                    this.bookmarksModalOpen = false;
                    this.teardownBookmarksRowsAnimations();
                    this.dispatchManagerModalsVisibilityState();
                },
            });
        },

        requestReaderGateNavigation(_source = 'generic') {
            this.resetSwipeState();
            this.clearWordPressState();
            void this.requestSearchModalClose({ skipLayout: true });
            window.dispatchEvent(new CustomEvent('quran-go-gate'));
        },

        shouldCloseSearchModalBeforeManagerModalOpen() {
            return this.search.modalOpen || this.isSearchModalWindowVisible();
        },

        shouldUseSmPlusWebFastModalRecovery() {
            if (this.nativeRuntime || this.shouldUseImmersiveReaderChrome()) {
                return false;
            }

            return Boolean(this.$store?.bp?.is?.('sm'));
        },

        prepareManagerModalOpenLifecycle(modalIds = []) {
            const normalizedModalIds = (Array.isArray(modalIds) ? modalIds : [modalIds])
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            if (this._modalLayoutResumeTimer !== null) {
                clearTimeout(this._modalLayoutResumeTimer);
                this._modalLayoutResumeTimer = null;
            }

            this.clearPendingPostModalTargetFit();
            this._bypassNextFitCache = true;

            if (this.hasRenderablePage()) {
                this.holdPageHiddenForModalLifecycle();
            }

            if (normalizedModalIds.length > 0) {
                this.suppressModalLifecycleEffects(normalizedModalIds, {
                    durationMs: Math.max(
                        modalLifecycleSuppressionDurationMs,
                        postModalFitRevealSettleDelayMs + 640,
                    ),
                });
            }
        },

        async openManagerModalAction(actionName, modalIds = []) {
            const normalizedActionName = String(actionName ?? '').trim();

            if (normalizedActionName === '' || this.wirdModeActive) {
                return false;
            }

            this._searchModalOpenRequestedAt = 0;

            this.resetSwipeState();
            this.clearWordPressState();
            this.prepareManagerModalOpenLifecycle(modalIds);

            if (this.shouldCloseSearchModalBeforeManagerModalOpen()) {
                await this.requestSearchModalClose({ skipLayout: true });
                await this.waitForModalLifecycleToSettle();
                await wait(modalCloseTransitionDelayMs);
            }

            if (typeof this.mountReaderAction === 'function') {
                const mountedViaReaderAction = await this.mountReaderAction(normalizedActionName);

                if (mountedViaReaderAction) {
                    return true;
                }
            }

            if (typeof this.$wire?.mountAction === 'function') {
                try {
                    await this.$wire.mountAction(normalizedActionName);

                    return true;
                } catch (_) {
                    return false;
                }
            }

            return false;
        },

        async openHistoryModal() {
            this._historyModalOpenRequestedAt = Date.now();
            return await this.openManagerModalAction('navigationHistory', [this.historyModalId]);
        },

        scheduleSearchModalBootstrapFallback() {
            if (
                typeof this.usesFilamentNativeSearchSelect === 'function' &&
                this.usesFilamentNativeSearchSelect()
            ) {
                return;
            }

            const bootstrapDelaysMs = [0, 60, 150, 320, 640];
            let didBootstrap = false;

            bootstrapDelaysMs.forEach((delayMs) => {
                window.setTimeout(() => {
                    if (didBootstrap) {
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

                    if (!this.search.modalOpen) {
                        this.search.modalOpen = true;
                        this._lastKnownModalOpenState = true;
                        this.dispatchManagerModalsVisibilityState();
                    }

                    if (typeof this.setupSearchStreamObserver === 'function') {
                        this.setupSearchStreamObserver();
                    }

                    if (typeof this.queueSearchModalInputSyncBinding === 'function') {
                        this.queueSearchModalInputSyncBinding();
                    }

                    didBootstrap = true;
                }, delayMs);
            });
        },

        async openSearchModal() {
            if (this.wirdModeActive) {
                return false;
            }

            this._searchModalOpenRequestedAt = Date.now();

            if (typeof this.warmSearchIndex === 'function') {
                void this.warmSearchIndex();
            }

            return await this.openManagerModalAction('searchQuran', [this.searchModalId]);
        },

        async openJumpPageModal() {
            return await this.openManagerModalAction('jumpToPage', [this.jumpPageModalId]);
        },

        async openBookmarksModal() {
            this._bookmarksModalOpenRequestedAt = Date.now();
            return await this.openManagerModalAction('bookmarksManager', [this.bookmarksModalId]);
        },

        async ensureModalDrivenPageVisible(
            pageNumber,
            {
                revealDelayMs = 180,
                maxAttempts = 5,
                fallbackReason = 'modal-driven-post-close-visibility-recovery',
            } = {},
        ) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);

            if (
                normalizedPageNumber <= 0 ||
                this.pageNumber !== normalizedPageNumber ||
                !this.hasRenderablePage()
            ) {
                return false;
            }

            const isVisibleAndHealthy = () =>
                this.isCurrentPageVisiblyReady() && this.isCurrentFitQualityHealthy();

            const recovered = await this.fitSpecificPageAfterModalClose(normalizedPageNumber, {
                revealDelayMs,
                maxAttempts,
            });

            if (recovered && isVisibleAndHealthy()) {
                return true;
            }

            this.clearStaleRevealGuards();
            this._bypassNextFitCache = true;
            await this.stabilizeModalDrivenLayout({
                revealDelayMs: Math.max(160, revealDelayMs - 20),
                maxAttempts: Math.max(4, maxAttempts - 1),
                maxFrames: 18,
                requiredStableFrames: 3,
                tolerancePx: 0.8,
            });

            if (!this.isCurrentFitQualityHealthy()) {
                const appliedSafetyScale =
                    typeof this.applySafetyScaleForCurrentPageOverflow === 'function' &&
                    this.applySafetyScaleForCurrentPageOverflow();

                if (appliedSafetyScale) {
                    await this.nextTickAsync();
                    await this.waitForStablePageFrame({
                        maxFrames: 12,
                        requiredStableFrames: 2,
                        tolerancePx: 0.65,
                    });
                }
            }

            if (!this.isCurrentPageVisiblyReady() && this.hasRenderablePage()) {
                this.clearStaleRevealGuards();
                this.forceRevealCurrentPage(fallbackReason);
            }

            return (
                isVisibleAndHealthy() ||
                (this.isCurrentPageContentVisible(0.12) && this.isCurrentFitQualityHealthy())
            );
        },

        async runSecondaryModalExitRecoveryPulse(modalId = '') {
            if (!this.hasRenderablePage()) {
                return;
            }

            const normalizedModalId = String(modalId ?? '').trim();

            if (normalizedModalId !== '') {
                this._activeModalIds.add(normalizedModalId);
            }

            this._bypassNextFitCache = true;
            this.holdPageHiddenForModalLifecycle();
            this.resumeLayoutWhenNoOpenModals();

            await wait(Math.max(180, modalCloseTransitionDelayMs + 90));
            await this.waitForModalLifecycleToSettle();
            await this.nextTickAsync();

            this._bypassNextFitCache = true;
            await this.stabilizeModalDrivenLayout({
                revealDelayMs: 140,
                maxAttempts: 4,
                maxFrames: 16,
                requiredStableFrames: 3,
                tolerancePx: 0.8,
            });
        },

        async navigateFromManagerModalRecord({
            targetPage,
            ayahIndex = 0,
            source = 'history-entry',
            modalId = '',
            suppressionDurationMs = historyNavigationModalLifecycleSuppressionDurationMs,
            ensureVisibleAfterModalClose = false,
        } = {}) {
            const normalizedModalId = String(modalId ?? '').trim();
            const normalizedSource = String(source ?? '').trim() || 'history-entry';
            const normalizedTargetPage = clampPage(Number(targetPage ?? 1), this.maxPage);
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.holdPageHiddenForModalLifecycle({ animateFadeOut: false });

            if (normalizedModalId !== '') {
                this.suppressModalLifecycleEffects([normalizedModalId], {
                    durationMs: Math.max(120, Math.trunc(Number(suppressionDurationMs) || 0)),
                });
            }

            this._bypassNextFitCache = true;
            await this.goToPageFromChevron(normalizedTargetPage, {
                activeAyahIndex: normalizedAyahIndex,
                source: normalizedSource,
                commitNow: true,
                settleDelayMs: 0,
            });

            await this.stabilizeModalDrivenLayout({
                revealDelayMs: 160,
                maxAttempts: 4,
                maxFrames: 18,
                requiredStableFrames: 3,
                tolerancePx: 0.8,
            });

            await this.runSecondaryModalExitRecoveryPulse(normalizedModalId);

            if (ensureVisibleAfterModalClose) {
                await this.ensureModalDrivenPageVisible(normalizedTargetPage, {
                    revealDelayMs: 190,
                    maxAttempts: 5,
                    fallbackReason: `${normalizedSource}-post-close-visibility-recovery`,
                });
            }
        },

        async goToHistoryEntry(entry) {
            const targetPage = clampPage(Number(entry?.page_number ?? 1), this.maxPage);
            const ayahIndex = Math.max(0, Math.trunc(Number(entry?.ayah_index ?? 0)));

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.beginModalNavigationCloseGuard([this.historyModalId], {
                durationMs: historyNavigationModalLifecycleSuppressionDurationMs,
            });

            try {
                await this.requestHistoryModalClose();
                await this.waitForModalLifecycleToSettle();
                await wait(modalCloseTransitionDelayMs);
                await this.navigateFromManagerModalRecord({
                    targetPage,
                    ayahIndex,
                    source: 'history-entry',
                    modalId: this.historyModalId,
                    suppressionDurationMs: historyNavigationModalLifecycleSuppressionDurationMs,
                });
            } finally {
                this.endModalNavigationCloseGuard();
            }

            this.activeWordIndex = 0;
        },

        async goToBookmark(bookmark) {
            const targetPage = clampPage(Number(bookmark?.page_number ?? 1), this.maxPage);

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.beginModalNavigationCloseGuard([this.bookmarksModalId], {
                durationMs: historyNavigationModalLifecycleSuppressionDurationMs,
            });

            try {
                await this.requestBookmarksModalClose();
                await this.waitForModalLifecycleToSettle();
                await wait(modalCloseTransitionDelayMs);
                await this.navigateFromManagerModalRecord({
                    targetPage,
                    ayahIndex: 0,
                    source: 'bookmark',
                    modalId: this.bookmarksModalId,
                    suppressionDurationMs: historyNavigationModalLifecycleSuppressionDurationMs,
                });
            } finally {
                this.endModalNavigationCloseGuard();
            }
            this.activeAyahIndex = 0;
            this.activeWordIndex = 0;
            this.recordNavigationHistory({
                source: 'bookmark-navigation',
                pageNumber: targetPage,
                surahNumber: this.currentSurahNumber(),
            });
        },

        async confirmSearchSelection() {
            if (!this.search.readyResult) {
                return;
            }

            await this.goToSearchResult(this.search.readyResult);
        },

        async goToSurahFromDirectory(entry) {
            if (this._searchNavigationInFlight) {
                return;
            }

            this._searchNavigationInFlight = true;
            const pageNumber = clampPage(Number(entry?.page_number ?? 1), this.maxPage);
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));
            const searchModalLifecycleIds = [
                this.resolveSearchModalCloseTargetId(),
                this.searchActionModalId,
                this.searchModalId,
                this.searchModalDomId,
            ]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            this.search.activeSurahNumber = surahNumber;
            this.search.preserveActiveSurahOnNextOpen = true;
            const shouldUseStandardSmPlusNavigation =
                !this.nativeRuntime && Boolean(this.$store?.bp?.is?.('sm+'));
            const standardSmPlusSource = 'search-standard';
            if (shouldUseStandardSmPlusNavigation) {
                this.searchDestinationScaleBoostPageNumber = 0;
                this.searchDestinationScaleBoostSource = '';
                this.searchDestinationScaleBoostExpiresAt = 0;
            } else {
                this.searchDestinationScaleBoostPageNumber = pageNumber;
                this.searchDestinationScaleBoostSource = 'surah-directory';
            }
            let usedStandardSmPlusNavigation = false;
            let usedModalCloseGuard = false;

            try {
                this.cancelActiveSearchProcessing();
                this.resetNavigationQueueForPriorityJump();
                this.clearPendingPostModalTargetFit();
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
                    this.dispatchPageNavigationRequest(pageNumber, standardSmPlusSource, {
                        activeAyahIndex: 0,
                        searchHighlightAyahIndex: 0,
                    });
                    await wait(24);
                } else {
                    this._bypassNextFitCache = true;
                    await this.goToPageFromChevron(pageNumber, {
                        activeAyahIndex: 0,
                        source: 'surah-directory',
                        commitNow: true,
                        settleDelayMs: 0,
                    });

                    await this.stabilizeModalDrivenLayout({
                        revealDelayMs: 160,
                        maxAttempts: 4,
                        maxFrames: 18,
                        requiredStableFrames: 3,
                        tolerancePx: 0.8,
                    });

                    await this.ensureModalDrivenPageVisible(pageNumber, {
                        revealDelayMs: 190,
                        maxAttempts: 5,
                        fallbackReason: 'surah-directory-post-close-visibility-recovery',
                    });
                }

                this.search.activeSurahNumber = surahNumber;
                this.activeAyahIndex = 0;
                this.activeWordIndex = 0;
                this.searchHighlightedAyahIndex = 0;
                this.activateSearchDestinationCue({
                    source: shouldUseStandardSmPlusNavigation
                        ? standardSmPlusSource
                        : 'surah-directory',
                    surahNumber,
                    pageNumber,
                });
                this.recordNavigationHistory({
                    source: 'surah-directory',
                    pageNumber,
                    surahNumber,
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

        scheduleManagerModalsPrewarm(delayMs = 220) {
            const normalizedDelay = Math.max(0, Math.trunc(Number(delayMs) || 0));

            window.setTimeout(() => {
                if (
                    this._managerModalsPrewarmed ||
                    this._managerModalsPrewarmPromise !== null ||
                    this.historyModalOpen ||
                    this.bookmarksModalOpen ||
                    this.search.modalOpen ||
                    this.jumpPageModalOpen
                ) {
                    return;
                }

                const runPrewarm = () => {
                    void this.prewarmManagerModals();
                };

                if (typeof window.requestIdleCallback === 'function') {
                    window.requestIdleCallback(runPrewarm, { timeout: 640 });

                    return;
                }

                runPrewarm();
            }, normalizedDelay);
        },

        async prewarmManagerModals() {
            if (this._managerModalsPrewarmed) {
                return;
            }

            if (this._managerModalsPrewarmPromise !== null) {
                await this._managerModalsPrewarmPromise;

                return;
            }

            if (typeof this.$wire?.prewarmManagerModals !== 'function') {
                return;
            }

            this._managerModalsPrewarmPromise = (async () => {
                try {
                    await this.$wire.prewarmManagerModals();
                    this._managerModalsPrewarmed = true;
                } catch (_) {
                    // Ignore background prewarm failures.
                } finally {
                    this._managerModalsPrewarmPromise = null;
                }
            })();

            await this._managerModalsPrewarmPromise;
        },

        searchRequestUrl(query = '') {
            const baseUrl = String(this.api.searchIndexUrl ?? '').trim();

            if (!baseUrl) {
                return '';
            }

            if (!query) {
                return baseUrl;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', query);

            return url.toString();
        },

        searchLocalIndexRequestUrl() {
            const baseUrl = this.searchRequestUrl();

            if (!baseUrl) {
                return '';
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('local', '1');

            return url.toString();
        },

        normalizeLocalSearchIndexRows(items = []) {
            return (Array.isArray(items) ? items : [])
                .map((item) => {
                    if (!item || typeof item !== 'object') {
                        return null;
                    }

                    const typedNormalized = this.normalizeSearchQuery(
                        item.text_searchable_typed ?? '',
                    );
                    const searchableNormalized = this.normalizeSearchQuery(
                        item.text_searchable ?? '',
                    );
                    const tokenLookup = {};

                    `${typedNormalized} ${searchableNormalized}`
                        .trim()
                        .split(/\s+/)
                        .filter(Boolean)
                        .forEach((token) => {
                            tokenLookup[token] = true;
                        });

                    return {
                        id: Math.max(0, Math.trunc(Number(item.id ?? 0))),
                        ayah_index: Math.max(0, Math.trunc(Number(item.ayah_index ?? 0))),
                        surah_number: Math.max(1, Math.trunc(Number(item.surah_number ?? 1))),
                        ayah_number: Math.max(1, Math.trunc(Number(item.ayah_number ?? 1))),
                        page_number: Math.max(1, Math.trunc(Number(item.page_number ?? 1))),
                        text_uthmani: String(item.text_uthmani ?? '').trim(),
                        text_searchable_typed: String(item.text_searchable_typed ?? '').trim(),
                        _typed: typedNormalized,
                        _searchable: searchableNormalized,
                        _tokens: tokenLookup,
                    };
                })
                .filter((item) => item !== null && (item._typed !== '' || item._searchable !== ''));
        },
    };
};
