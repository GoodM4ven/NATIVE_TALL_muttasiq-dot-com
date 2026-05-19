export const createReaderNavigationFitPageNavAndLayoutSchedulingModule = (deps) => {
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

                const wasOnDensePage = this.isDenseFullLinePage();
                this.applyPayload(payload, { setPageNumber: true });
                this.clearStaleFitInlineVariables();
                if (isModalSourcedNavigation && wasOnDensePage && !this.isDenseFullLinePage()) {
                    this.applyModalFromDenseTransitionGuard();
                }
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
    };
};
