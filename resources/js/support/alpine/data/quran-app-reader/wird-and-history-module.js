export const createWirdAndHistoryModule = (deps) => {
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
        ensureWirdDailyRecord({ forceRebuild = false } = {}) {
            this.syncWirdStorageState({
                clearDailyRecord: true,
            });

            if (!this.wirdState || typeof this.wirdState !== 'object') {
                this.hydrateWirdState();
            }

            const previousNextAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(this.wirdState?.nextAbsolutePage, 1, { min: 1 }),
            );
            const dateKey = currentDateKey();
            const signature = this.resolveWirdRecordSignature();
            const dayRecords =
                this.wirdState?.dayRecords &&
                typeof this.wirdState.dayRecords === 'object' &&
                !Array.isArray(this.wirdState.dayRecords)
                    ? this.wirdState.dayRecords
                    : {};
            let record = dayRecords[dateKey] ?? null;
            const shouldRebuild =
                forceRebuild ||
                !record ||
                typeof record !== 'object' ||
                String(record?.signature ?? '') !== signature;

            this.wirdTodayKey = dateKey;

            if (shouldRebuild) {
                const requiredPages = this.resolveWirdRequiredPages({
                    dateKey,
                });
                const maxStep = Math.max(0, requiredPages - 1);
                const fallbackStartAbsolutePage = Math.max(
                    1,
                    this.normalizeIntegerFlag(this.wirdState?.nextAbsolutePage, 1, { min: 1 }),
                );
                const canCarryExistingProgress =
                    !forceRebuild && record && typeof record === 'object';
                const startAbsolutePage = canCarryExistingProgress
                    ? Math.max(
                          1,
                          this.normalizeIntegerFlag(
                              record?.startAbsolutePage,
                              fallbackStartAbsolutePage,
                              {
                                  min: 1,
                              },
                          ),
                      )
                    : fallbackStartAbsolutePage;
                const currentStep = canCarryExistingProgress
                    ? this.normalizeIntegerFlag(record?.currentStep, 0, {
                          min: 0,
                          max: maxStep,
                      })
                    : 0;
                const carriedProgressStep = canCarryExistingProgress
                    ? this.normalizeIntegerFlag(record?.progressStep, currentStep, {
                          min: 0,
                          max: maxStep,
                      })
                    : currentStep;
                const completed = canCarryExistingProgress
                    ? Boolean(record?.completed) || carriedProgressStep >= maxStep
                    : false;
                const progressStep = completed
                    ? maxStep
                    : Math.max(currentStep, carriedProgressStep);

                record = {
                    startAbsolutePage,
                    requiredPages,
                    currentStep,
                    progressStep,
                    completed,
                    signature,
                    createdAt: canCarryExistingProgress
                        ? this.normalizeIntegerFlag(record?.createdAt, Date.now(), {
                              min: 0,
                          })
                        : Date.now(),
                    updatedAt: Date.now(),
                };

                this.wirdState.dayRecords[dateKey] = record;
            } else {
                const maxStep = Math.max(0, Number(record.requiredPages ?? 1) - 1);

                record.currentStep = this.normalizeIntegerFlag(record.currentStep, 0, {
                    min: 0,
                    max: maxStep,
                });
                record.progressStep = this.normalizeIntegerFlag(
                    record.progressStep,
                    record.currentStep,
                    {
                        min: 0,
                        max: maxStep,
                    },
                );
                record.completed = Boolean(record.completed);

                if (record.completed) {
                    record.progressStep = maxStep;
                } else {
                    record.progressStep = Math.max(record.progressStep, record.currentStep);
                }

                record.signature = signature;
            }

            this.wirdDailyRecord = record;
            this.reconcileWirdNextAbsolutePage(record);
            const didNextAbsolutePageChange =
                Math.max(
                    1,
                    this.normalizeIntegerFlag(this.wirdState?.nextAbsolutePage, 1, { min: 1 }),
                ) !== previousNextAbsolutePage;

            if (shouldRebuild || didNextAbsolutePageChange) {
                this.persistWirdState();
            }

            return this.wirdDailyRecord;
        },

        wirdCompletedPages(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.normalizeIntegerFlag(normalizedRecord?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });
            const progressStep = this.normalizeIntegerFlag(
                normalizedRecord?.progressStep,
                currentStep,
                {
                    min: 0,
                    max: maxStep,
                },
            );

            if (normalizedRecord?.completed) {
                return requiredPages;
            }

            return progressStep;
        },

        wirdRemainingPages(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );

            return Math.max(0, requiredPages - this.wirdCompletedPages(normalizedRecord));
        },

        wirdProgressPercent(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const completedPages = this.wirdCompletedPages(normalizedRecord);

            return Math.max(0, Math.min(100, Math.round((completedPages / requiredPages) * 100)));
        },

        wirdCurrentAbsolutePage(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const startAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.startAbsolutePage, 1, { min: 1 }),
            );
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.normalizeIntegerFlag(normalizedRecord?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });

            return startAbsolutePage + currentStep;
        },

        wirdCurrentStep(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);

            return this.normalizeIntegerFlag(normalizedRecord?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });
        },

        wirdProgressStep(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.wirdCurrentStep(normalizedRecord);
            const progressStep = this.normalizeIntegerFlag(
                normalizedRecord?.progressStep,
                currentStep,
                {
                    min: 0,
                    max: maxStep,
                },
            );

            if (normalizedRecord?.completed) {
                return maxStep;
            }

            return Math.max(currentStep, progressStep);
        },

        wirdBrowseStepForProgress(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();

            if (normalizedRecord?.completed) {
                return this.wirdBrowseStepValue(normalizedRecord);
            }

            return this.wirdCurrentStep(normalizedRecord);
        },

        wirdBrowsePercent(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const browseStep = this.wirdBrowseStepForProgress(normalizedRecord);

            return Math.max(0, Math.min(100, Math.round((browseStep / requiredPages) * 100)));
        },

        wirdRangeState(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const startAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.startAbsolutePage, 1, { min: 1 }),
            );
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );

            return {
                record: normalizedRecord,
                startAbsolutePage,
                requiredPages,
                maxStep: Math.max(0, requiredPages - 1),
            };
        },

        wirdBrowseStepValue(record = this.wirdDailyRecord) {
            const range = this.wirdRangeState(record);
            const fallbackStep = this.wirdCurrentStep(range.record);

            return this.normalizeIntegerFlag(this.wirdBrowseStep, fallbackStep, {
                min: 0,
                max: range.maxStep,
            });
        },

        wirdActiveStepForNavigation(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();

            if (normalizedRecord?.completed) {
                return this.wirdBrowseStepValue(normalizedRecord);
            }

            return this.wirdCurrentStep(normalizedRecord);
        },

        wirdTargetPageFromStep(step, record = this.wirdDailyRecord) {
            const range = this.wirdRangeState(record);
            const normalizedStep = this.normalizeIntegerFlag(
                step,
                this.wirdActiveStepForNavigation(record),
                {
                    min: 0,
                    max: range.maxStep,
                },
            );

            return this.absolutePageToPageNumber(range.startAbsolutePage + normalizedStep);
        },

        wirdStepForPage(pageNumber, record = this.wirdDailyRecord, { preferredStep = null } = {}) {
            const range = this.wirdRangeState(record);
            const targetPage = clampPage(pageNumber, this.maxPage);
            const maxReaderPage = Math.max(1, this.resolveReaderMaxPage());
            const startPage = this.absolutePageToPageNumber(range.startAbsolutePage);
            const initialStep =
                (((targetPage - startPage) % maxReaderPage) + maxReaderPage) % maxReaderPage;

            if (initialStep > range.maxStep) {
                return null;
            }

            if (!Number.isFinite(Number(preferredStep))) {
                return initialStep;
            }

            const normalizedPreferredStep = this.normalizeIntegerFlag(preferredStep, initialStep, {
                min: 0,
                max: range.maxStep,
            });
            let resolvedStep = initialStep;
            let smallestDistance = Math.abs(initialStep - normalizedPreferredStep);

            for (
                let candidateStep = initialStep + maxReaderPage;
                candidateStep <= range.maxStep;
                candidateStep += maxReaderPage
            ) {
                const distance = Math.abs(candidateStep - normalizedPreferredStep);

                if (distance > smallestDistance) {
                    continue;
                }

                if (distance === smallestDistance && candidateStep < resolvedStep) {
                    continue;
                }

                resolvedStep = candidateStep;
                smallestDistance = distance;
            }

            return resolvedStep;
        },

        clearWirdSliderVisualTween() {
            if (this._wirdSliderVisualTweenRaf === null) {
                return;
            }

            cancelAnimationFrame(this._wirdSliderVisualTweenRaf);
            this._wirdSliderVisualTweenRaf = null;
        },

        syncWirdSliderVisualStep(record = this.wirdDailyRecord) {
            if (!this.wirdModeActive) {
                this.clearWirdSliderVisualTween();
                this.wirdSliderVisualStep = null;

                return;
            }

            const range = this.wirdRangeState(record);
            const activeStep = this.wirdActiveStepForNavigation(range.record);
            this.clearWirdSliderVisualTween();
            this.wirdSliderVisualStep = activeStep;
        },

        wirdSliderDisplayStep(record = this.wirdDailyRecord) {
            if (!this.wirdModeActive) {
                return null;
            }

            const range = this.wirdRangeState(record);
            const activeStep = this.wirdActiveStepForNavigation(range.record);
            const visualStep = Number(this.wirdSliderVisualStep);

            if (!Number.isFinite(visualStep)) {
                return activeStep;
            }

            return Math.max(0, Math.min(range.maxStep, visualStep));
        },

        animateWirdSliderVisualStepTo(
            targetStep,
            { durationMs = 220, record = this.wirdDailyRecord } = {},
        ) {
            if (!this.wirdModeActive) {
                this.syncWirdSliderVisualStep(record);

                return;
            }

            const range = this.wirdRangeState(record);
            const normalizedTargetStep = Math.max(
                0,
                Math.min(range.maxStep, Number(targetStep ?? 0)),
            );
            const startingStep = Number(this.wirdSliderDisplayStep(range.record));

            if (!Number.isFinite(startingStep)) {
                this.wirdSliderVisualStep = normalizedTargetStep;

                return;
            }

            const normalizedDurationMs = Math.max(120, Math.trunc(Number(durationMs) || 220));
            const delta = normalizedTargetStep - startingStep;

            if (Math.abs(delta) < 0.001) {
                this.wirdSliderVisualStep = normalizedTargetStep;

                return;
            }

            this.clearWirdSliderVisualTween();
            this.wirdSliderVisualStep = startingStep;

            const startedAt = performance.now();

            const tick = (timestamp) => {
                if (!this.wirdModeActive) {
                    this._wirdSliderVisualTweenRaf = null;

                    return;
                }

                const elapsed = Math.max(0, timestamp - startedAt);
                const progress = Math.max(0, Math.min(1, elapsed / normalizedDurationMs));
                const easedProgress =
                    progress < 0.5
                        ? 2 * progress * progress
                        : 1 - Math.pow(-2 * progress + 2, 2) / 2;

                this.wirdSliderVisualStep = startingStep + delta * easedProgress;

                if (progress >= 1) {
                    this._wirdSliderVisualTweenRaf = null;
                    this.wirdSliderVisualStep = normalizedTargetStep;

                    return;
                }

                this._wirdSliderVisualTweenRaf = requestAnimationFrame(tick);
            };

            this._wirdSliderVisualTweenRaf = requestAnimationFrame(tick);
        },

        applyWirdNavigationVisualState(
            targetPage,
            targetStep,
            { source = 'generic', sliderDurationMs = 220, previousStep = null } = {},
        ) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);
            const range = this.wirdRangeState();
            const normalizedTargetStep = this.normalizeIntegerFlag(targetStep, 0, {
                min: 0,
                max: range.maxStep,
            });
            const normalizedPreviousStep = this.normalizeIntegerFlag(
                previousStep,
                normalizedTargetStep,
                {
                    min: 0,
                    max: range.maxStep,
                },
            );
            const previousCounterValue = normalizedPreviousStep + 1;
            const nextCounterValue = normalizedTargetStep + 1;

            if (nextCounterValue !== previousCounterValue) {
                this.triggerPageCounterPulse(previousCounterValue, nextCounterValue, {
                    source: `wird-${String(source ?? 'generic').trim() || 'generic'}-counter`,
                });
            }

            this.pageInput = normalizedTargetPage;
            this._lastPageInputVisualValue = normalizedTargetPage;
            this.animateWirdSliderVisualStepTo(normalizedTargetStep, {
                durationMs: sliderDurationMs,
            });
        },

        sliderMin() {
            return this.wirdModeActive ? 0 : 1;
        },

        sliderMax() {
            if (!this.wirdModeActive) {
                return Math.max(1, this.maxPage);
            }

            const range = this.wirdRangeState();

            return Math.max(0, range.maxStep);
        },

        sliderValue() {
            if (!this.wirdModeActive) {
                return clampPage(this.pageInput, this.maxPage);
            }

            return this.wirdSliderDisplayStep();
        },

        formatReaderNumber(value, fallback = '0') {
            const normalizedNumber = Number(value);

            if (!Number.isFinite(normalizedNumber)) {
                return fallback;
            }

            const westernText = String(Math.max(0, Math.trunc(normalizedNumber)));

            if (this.doesUseWesternNumerals) {
                return westernText;
            }

            return westernText.replace(/\d/g, (digit) => {
                const digitIndex = Number(digit);

                if (!Number.isInteger(digitIndex)) {
                    return digit;
                }

                return this.arabicNumeralCharacters[digitIndex] ?? digit;
            });
        },

        wirdProgressPercentLabel() {
            const record = this.ensureWirdDailyRecord();
            const percent = this.wirdProgressPercent(record);

            if (record?.completed) {
                return 'مكتمل';
            }

            return `${this.formatReaderNumber(percent)}%`;
        },

        wirdProgressCounterLabel() {
            const record = this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
            );
            const activeIndex = this.wirdProgressStep(record) + 1;
            const current = record?.completed
                ? requiredPages
                : Math.min(requiredPages, Math.max(1, activeIndex));

            return `${this.formatReaderNumber(requiredPages)} / ${this.formatReaderNumber(current)}`;
        },

        wirdProgressBarStyle() {
            return `--quran-wird-progress-percent: ${this.wirdProgressPercent()}%; --quran-wird-progress-browse-percent: ${this.wirdBrowsePercent()}%;`;
        },

        clearWirdEntryRevealTimers() {
            this._wirdEntryRevealTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._wirdEntryRevealTimers = [];

            this._historyManagerSyncTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._historyManagerSyncTimers = [];

            this._bookmarksManagerSyncTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._bookmarksManagerSyncTimers = [];
        },

        clearWirdEntryRecovery({ resetSuppression = true } = {}) {
            this.clearWirdEntryRevealTimers();

            if (resetSuppression) {
                this._wirdEntryLayoutSuppressedUntil = 0;
            }
        },

        suppressWirdEntryLayoutScheduling(durationMs = 900) {
            const normalizedDurationMs = Math.max(120, Math.trunc(Number(durationMs) || 900));

            this._wirdEntryLayoutSuppressedUntil = Math.max(
                this._wirdEntryLayoutSuppressedUntil,
                Date.now() + normalizedDurationMs,
            );
        },

        isWirdEntryLayoutSchedulingSuppressed() {
            return Date.now() < this._wirdEntryLayoutSuppressedUntil;
        },

        queueWirdEntryRevealRecovery(
            targetPage,
            navigationRequestSerial = this._wirdNavigationRequestSerial,
        ) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);

            if (normalizedTargetPage < 1) {
                return;
            }

            this.clearWirdEntryRevealTimers();

            const timerId = window.setTimeout(() => {
                this._wirdEntryRevealTimers = this._wirdEntryRevealTimers.filter(
                    (activeTimerId) => activeTimerId !== timerId,
                );

                if (
                    navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                    !this.wirdModeActive ||
                    this.pageNumber !== normalizedTargetPage ||
                    !this.hasRenderablePage() ||
                    this.isLoadingPage
                ) {
                    return;
                }

                void (async () => {
                    if (navigationRequestSerial !== this._wirdNavigationRequestSerial) {
                        return;
                    }

                    const shouldRunRecoveryLayout =
                        this.isFittingPage ||
                        (this._lastFittedPageNumber !== normalizedTargetPage &&
                            this._revealTimer === null);

                    if (shouldRunRecoveryLayout) {
                        this.pauseIdleWarmup(640, {
                            preservePage: normalizedTargetPage,
                        });
                        this._bypassNextFitCache = true;
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 130,
                            maxAttempts: 3,
                            useIdleFit: false,
                        });

                        if (navigationRequestSerial !== this._wirdNavigationRequestSerial) {
                            return;
                        }
                    }

                    await this.ensureWirdEntryPageVisible(normalizedTargetPage);
                })();
            }, 560);

            this._wirdEntryRevealTimers.push(timerId);
        },

        async ensureWirdEntryPageVisible(targetPage, { forceRecover = false } = {}) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);

            if (normalizedTargetPage < 1) {
                return;
            }

            await this.nextTickAsync();

            if (this.pageNumber !== normalizedTargetPage) {
                return;
            }

            if (!this.hasRenderablePage()) {
                if (this.isLoadingPage || this._pendingNavigationRequest !== null) {
                    return;
                }

                await this.goToPage(normalizedTargetPage, {
                    direction: this.resolveNavigationDirection(normalizedTargetPage),
                    animate: false,
                    forceRefit: true,
                    source: 'wird-recover',
                });
            }

            if (!this.hasRenderablePage()) {
                return;
            }

            const clearedStaleGuards = this.clearStaleRevealGuards();
            const shouldRunRecoveryLayout =
                forceRecover ||
                clearedStaleGuards ||
                this._lastFittedPageNumber !== normalizedTargetPage ||
                !this.isCurrentPageVisiblyReady() ||
                (this.isFittingPage && this._revealTimer === null);

            if (shouldRunRecoveryLayout) {
                this.pauseIdleWarmup(560, {
                    preservePage: normalizedTargetPage,
                });
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 130,
                    maxAttempts: 5,
                    useIdleFit: false,
                });
            }

            this.clearStaleRevealGuards();

            if (
                this.pageNumber === normalizedTargetPage &&
                !this.isLoadingPage &&
                this._pendingNavigationRequest === null
            ) {
                this.isFittingPage = false;
            }
        },

        startWirdHoverEffects() {
            if (this.isSupportLockActive()) {
                this.wirdHoverShimmerRunning = false;

                return;
            }

            if (this._wirdHoverShimmerTimer !== null) {
                clearTimeout(this._wirdHoverShimmerTimer);
                this._wirdHoverShimmerTimer = null;
            }

            this.wirdHoverShimmerRunning = false;

            requestAnimationFrame(() => {
                this.wirdHoverShimmerRunning = true;
            });

            this._wirdHoverShimmerTimer = window.setTimeout(() => {
                this._wirdHoverShimmerTimer = null;
                this.wirdHoverShimmerRunning = false;
            }, wirdHoverShimmerDurationMs);
        },

        endWirdHoverEffects({ immediate = false } = {}) {
            if (!immediate) {
                return;
            }

            if (this._wirdHoverShimmerTimer !== null) {
                clearTimeout(this._wirdHoverShimmerTimer);
                this._wirdHoverShimmerTimer = null;
            }

            this.wirdHoverShimmerRunning = false;
        },

        async toggleWirdMode() {
            this.$el.blur();

            if (this.isSupportLockActive()) {
                this.openSupportUnlockModal();

                return;
            }

            if (this.wirdModeActive) {
                await this.exitWirdMode({
                    restoreNormalPage: true,
                    reason: 'manual-toggle',
                });

                return;
            }

            await this.enterWirdMode();
        },

        async enterWirdMode() {
            if (this.isLoadingPage || !this.ready) {
                return;
            }

            this.closeSurahQuickNavigator();
            const record = this.ensureWirdDailyRecord();

            if (!record || typeof record !== 'object') {
                return;
            }

            this.resetNavigationQueueForPriorityJump();
            this._wirdNavigationRequestSerial += 1;
            this.wirdNormalPageBeforeMode = clampPage(this.pageNumber, this.maxPage);
            this.wirdModeActive = true;
            this.suppressWirdEntryLayoutScheduling();
            this.clearWirdEntryRevealTimers();

            const wirdRange = this.wirdRangeState(record);
            this.wirdBrowseStep = record?.completed ? wirdRange.maxStep : null;
            this.syncWirdSliderVisualStep(record);

            const targetAbsolutePage = record?.completed
                ? wirdRange.startAbsolutePage + this.wirdBrowseStep
                : this.wirdCurrentAbsolutePage(record);
            const targetPage = this.absolutePageToPageNumber(targetAbsolutePage);
            const direction = this.resolveNavigationDirection(targetPage);

            await this.animatePageInputTo(targetPage, {
                source: 'wird-enter',
            });

            if (targetPage === this.pageNumber && this.hasRenderablePage()) {
                this.pageInput = targetPage;
                this._lastPageInputVisualValue = targetPage;
                await this.ensureWirdEntryPageVisible(targetPage);
                this.queueWirdEntryRevealRecovery(targetPage);

                return;
            }

            await this.goToPage(targetPage, {
                direction,
                animate: true,
                forceRefit: true,
                source: 'wird-enter',
            });

            await this.ensureWirdEntryPageVisible(targetPage);
            this.queueWirdEntryRevealRecovery(targetPage);
        },

        async exitWirdMode({ restoreNormalPage = true, reason = 'manual' } = {}) {
            if (!this.wirdModeActive) {
                return;
            }

            this.resetNavigationQueueForPriorityJump();
            this._wirdNavigationRequestSerial += 1;
            this.abortActivePageLoad();

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }
            this._wirdSliderPendingCommitStep = null;
            this._wirdSliderLastInputStep = null;
            this._wirdSliderLastInputAt = 0;
            this._wirdLastCommittedTargetPage = 0;
            this._wirdLastCommittedStep = null;
            this._wirdLastCommittedAt = 0;

            this.clearWirdSliderVisualTween();

            this.wirdModeActive = false;
            this.wirdBrowseStep = null;
            this.clearWirdEntryRevealTimers();
            this._wirdEntryLayoutSuppressedUntil = 0;
            this.syncWirdSliderVisualStep();
            this.endWirdHoverEffects({ immediate: true });

            if (!restoreNormalPage) {
                return;
            }

            const fallbackPage = readLastPageNumber() ?? this.pageNumber;
            const targetPage = clampPage(
                this.wirdNormalPageBeforeMode || fallbackPage,
                this.maxPage,
            );

            if (targetPage === this.pageNumber && this.hasRenderablePage()) {
                this.pageInput = targetPage;
                this._lastPageInputVisualValue = targetPage;
                this.persistLastPageNumber(targetPage, { force: true });
                await this.ensureWirdEntryPageVisible(targetPage, { forceRecover: true });

                return;
            }
            const direction = this.resolveNavigationDirection(targetPage);

            await this.animatePageInputTo(targetPage, {
                source: 'wird-exit',
            });

            await this.goToPage(targetPage, {
                direction,
                animate: reason !== 'auto-complete',
                forceRefit: true,
                source: 'wird-exit',
            });

            await this.ensureWirdEntryPageVisible(targetPage, { forceRecover: true });
        },

        markWirdAsCompleted(record = this.wirdDailyRecord) {
            if (!record || typeof record !== 'object') {
                return;
            }

            const wasCompleted = Boolean(record.completed);

            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
            );

            record.currentStep = Math.max(0, requiredPages - 1);
            record.progressStep = Math.max(0, requiredPages - 1);
            record.completed = true;
            record.updatedAt = Date.now();
            this.wirdDailyRecord = record;
            this.wirdBrowseStep = Math.max(0, requiredPages - 1);
            this.syncWirdSliderVisualStep(record);
            this.reconcileWirdNextAbsolutePage(record);
            this.persistWirdState();

            if (!wasCompleted) {
                this.showWirdCompletionFeedback();
            }
        },

        navigationSourceProfile(source = 'generic') {
            const normalizedSource = String(source ?? 'generic').trim();

            if (!normalizedSource) {
                return 'generic';
            }

            return normalizedSource;
        },

        wirdNavigationSourceProfile(source = 'generic') {
            return this.navigationSourceProfile(source);
        },

        shouldScheduleWirdRevealRecovery(source = 'generic') {
            const normalizedSource = this.wirdNavigationSourceProfile(source);

            return !normalizedSource.startsWith('slider');
        },

        async stepWird(direction = 'next', source = 'generic') {
            if (!this.wirdModeActive) {
                return;
            }

            const navigationRequestSerial = this._wirdNavigationRequestSerial + 1;
            this._wirdNavigationRequestSerial = navigationRequestSerial;
            this.clearWirdEntryRecovery();

            const record = this.ensureWirdDailyRecord();

            if (!record || typeof record !== 'object') {
                return;
            }

            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.normalizeIntegerFlag(record?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });
            const isNextDirection = direction === 'next';
            const sourceProfile = this.wirdNavigationSourceProfile(source);
            const pageStep = this.wirdStepForPage(this.pageNumber, record, {
                preferredStep: this.wirdActiveStepForNavigation(record),
            });

            if (record?.completed) {
                let browseStep = this.wirdBrowseStepValue(record);

                if (pageStep !== null) {
                    browseStep = pageStep;
                }

                if (isNextDirection && browseStep >= maxStep) {
                    await this.exitWirdMode({
                        restoreNormalPage: true,
                        reason: 'boundary-next',
                    });

                    return;
                }

                if (!isNextDirection && browseStep <= 0) {
                    await this.exitWirdMode({
                        restoreNormalPage: true,
                        reason: 'boundary-prev',
                    });

                    return;
                }

                const previousBrowseStep = browseStep;
                browseStep = isNextDirection ? browseStep + 1 : browseStep - 1;
                this.wirdBrowseStep = browseStep;
                const startAbsolutePage = Math.max(
                    1,
                    this.normalizeIntegerFlag(record?.startAbsolutePage, 1, { min: 1 }),
                );
                const targetPage = this.absolutePageToPageNumber(startAbsolutePage + browseStep);
                this.applyWirdNavigationVisualState(targetPage, browseStep, {
                    source: sourceProfile,
                    previousStep: previousBrowseStep,
                });

                await this.goToPage(targetPage, {
                    direction: isNextDirection ? 'next' : 'prev',
                    animate: true,
                    forceRefit: true,
                    source: `wird-${sourceProfile}`,
                });

                if (
                    navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                    !this.wirdModeActive
                ) {
                    return;
                }

                await this.ensureWirdEntryPageVisible(targetPage);

                if (
                    navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                    !this.wirdModeActive
                ) {
                    return;
                }

                if (this.shouldScheduleWirdRevealRecovery(source)) {
                    this.queueWirdEntryRevealRecovery(targetPage, navigationRequestSerial);
                }

                return;
            }

            this.wirdBrowseStep = null;
            const effectiveCurrentStep = pageStep === null ? currentStep : pageStep;

            if (isNextDirection && effectiveCurrentStep >= maxStep) {
                this.markWirdAsCompleted(record);
                await this.exitWirdMode({
                    restoreNormalPage: true,
                    reason: 'auto-complete',
                });

                return;
            }

            if (!isNextDirection && effectiveCurrentStep <= 0) {
                await this.exitWirdMode({
                    restoreNormalPage: true,
                    reason: 'boundary-prev',
                });

                return;
            }

            record.currentStep = isNextDirection
                ? effectiveCurrentStep + 1
                : effectiveCurrentStep - 1;
            record.progressStep = this.normalizeIntegerFlag(
                record?.progressStep,
                effectiveCurrentStep,
                {
                    min: 0,
                    max: maxStep,
                },
            );
            record.progressStep = Math.max(record.progressStep, record.currentStep);
            record.completed = Boolean(record.completed);
            record.updatedAt = Date.now();
            this.wirdDailyRecord = record;
            this.reconcileWirdNextAbsolutePage(record);
            this.persistWirdState();

            const targetPage = this.absolutePageToPageNumber(this.wirdCurrentAbsolutePage(record));
            this.applyWirdNavigationVisualState(targetPage, record.currentStep, {
                source: sourceProfile,
                previousStep: effectiveCurrentStep,
            });

            await this.goToPage(targetPage, {
                direction: isNextDirection ? 'next' : 'prev',
                animate: true,
                forceRefit: true,
                source: `wird-${sourceProfile}`,
            });

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            await this.ensureWirdEntryPageVisible(targetPage);

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            if (this.shouldScheduleWirdRevealRecovery(source)) {
                this.queueWirdEntryRevealRecovery(targetPage, navigationRequestSerial);
            }
        },

        async navigateWirdToStep(step, source = 'slider') {
            if (!this.wirdModeActive) {
                return;
            }

            const navigationRequestSerial = this._wirdNavigationRequestSerial + 1;
            this._wirdNavigationRequestSerial = navigationRequestSerial;
            this.clearWirdEntryRecovery();

            const record = this.ensureWirdDailyRecord();

            if (!record || typeof record !== 'object') {
                return;
            }

            const range = this.wirdRangeState(record);
            const normalizedStep = this.normalizeIntegerFlag(
                step,
                this.wirdActiveStepForNavigation(record),
                {
                    min: 0,
                    max: range.maxStep,
                },
            );
            const targetPage = this.wirdTargetPageFromStep(normalizedStep, record);
            const direction = this.resolveNavigationDirection(targetPage);

            if (record?.completed) {
                this.wirdBrowseStep = normalizedStep;
            } else {
                const maxStep = Math.max(0, range.maxStep);
                record.currentStep = normalizedStep;
                record.progressStep = this.normalizeIntegerFlag(
                    record?.progressStep,
                    normalizedStep,
                    {
                        min: 0,
                        max: maxStep,
                    },
                );
                record.progressStep = Math.max(record.progressStep, normalizedStep);
                record.completed = Boolean(record.completed);
                record.updatedAt = Date.now();
                this.wirdDailyRecord = record;
                this.wirdBrowseStep = null;
                this.reconcileWirdNextAbsolutePage(record);
                this.persistWirdState();
            }

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            if (targetPage === this.pageNumber && this.hasRenderablePage()) {
                this.pageInput = targetPage;
                this._lastPageInputVisualValue = targetPage;
                this.clearWirdSliderVisualTween();
                this.wirdSliderVisualStep = normalizedStep;
                await this.ensureWirdEntryPageVisible(targetPage);

                if (
                    navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                    !this.wirdModeActive
                ) {
                    return;
                }

                if (this.shouldScheduleWirdRevealRecovery(source)) {
                    this.queueWirdEntryRevealRecovery(targetPage, navigationRequestSerial);
                }

                return;
            }

            await this.goToPage(targetPage, {
                direction,
                animate: true,
                forceRefit: true,
                source: `wird-${source}`,
            });

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            await this.ensureWirdEntryPageVisible(targetPage);

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            if (this.shouldScheduleWirdRevealRecovery(source)) {
                this.queueWirdEntryRevealRecovery(targetPage, navigationRequestSerial);
            }
        },

        persistNavigationHistory() {
            this.navigationHistory = writeNavigationHistory(this.navigationHistory);
            this.syncHistoryManagerTableRecords();
        },

        persistBookmarks() {
            this.bookmarks = writeBookmarks(this.bookmarks);
            this.syncBookmarksManagerTableRecords();
        },

        normalizeHistoryEntryId(entryId) {
            return String(entryId ?? '').trim();
        },

        historyTagsMatch(currentTags = [], nextTags = []) {
            if (currentTags.length !== nextTags.length) {
                return false;
            }

            return currentTags.every((tag, index) => tag === nextTags[index]);
        },

        historyEntryById(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return null;
            }

            return (
                this.navigationHistory.find(
                    (entry) => this.normalizeHistoryEntryId(entry?.id) === normalizedEntryId,
                ) ?? null
            );
        },

        syncHistoryTagDraftForEntry(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const existingEntry = this.historyEntryById(normalizedEntryId);

            if (!existingEntry) {
                if (
                    this.historyTagDraftById &&
                    Object.prototype.hasOwnProperty.call(
                        this.historyTagDraftById,
                        normalizedEntryId,
                    )
                ) {
                    const nextDrafts = { ...this.historyTagDraftById };
                    delete nextDrafts[normalizedEntryId];
                    this.historyTagDraftById = nextDrafts;
                }

                return;
            }

            const currentDraft = String(this.historyTagDraftById?.[normalizedEntryId] ?? '').trim();

            if (currentDraft !== '') {
                return;
            }

            this.historyTagDraftById = {
                ...this.historyTagDraftById,
                [normalizedEntryId]: '',
            };
        },

        syncHistoryTagDrafts() {
            const nextDrafts = {};

            this.navigationHistory.forEach((entry) => {
                const normalizedEntryId = this.normalizeHistoryEntryId(entry?.id);

                if (!normalizedEntryId) {
                    return;
                }

                nextDrafts[normalizedEntryId] = String(
                    this.historyTagDraftById?.[normalizedEntryId] ?? '',
                );
            });

            this.historyTagDraftById = nextDrafts;
        },

        historyTagDraft(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return '';
            }

            return String(this.historyTagDraftById?.[normalizedEntryId] ?? '');
        },

        setHistoryTagDraft(entryId, value) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            this.historyTagDraftById = {
                ...this.historyTagDraftById,
                [normalizedEntryId]: String(value ?? ''),
            };
        },

        collectSharedManagerTags({ excludeTags = [], draftValue = '' } = {}) {
            const excluded = new Set(
                normalizeTags(excludeTags).map((tag) => String(tag ?? '').toLocaleLowerCase()),
            );
            const normalizedDraftValue = String(draftValue ?? '')
                .toLocaleLowerCase()
                .trim();
            const suggestions = [];
            const usedSuggestions = new Set();
            const suggestionSources = [...this.navigationHistory, ...this.bookmarks];

            suggestionSources.forEach((entry) => {
                (Array.isArray(entry?.tags) ? entry.tags : []).forEach((rawTag) => {
                    const normalizedTag = String(rawTag ?? '').trim();

                    if (normalizedTag === '') {
                        return;
                    }

                    const normalizedKey = normalizedTag.toLocaleLowerCase();

                    if (excluded.has(normalizedKey) || usedSuggestions.has(normalizedKey)) {
                        return;
                    }

                    if (
                        normalizedDraftValue !== '' &&
                        !normalizedKey.includes(normalizedDraftValue)
                    ) {
                        return;
                    }

                    usedSuggestions.add(normalizedKey);
                    suggestions.push(normalizedTag);
                });
            });

            return suggestions.slice(0, 18);
        },

        historyTagSuggestions(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);
            const entry = this.historyEntryById(normalizedEntryId);
            const existingTags = Array.isArray(entry?.tags) ? entry.tags : [];
            const draftValue = this.historyTagDraft(normalizedEntryId);

            return this.collectSharedManagerTags({
                excludeTags: existingTags,
                draftValue,
            });
        },

        commitHistoryTagDraft(entryId, { clearInput = true } = {}) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const draftValue = this.historyTagDraft(normalizedEntryId);
            const parsedDraftTags = normalizeTags(draftValue);

            if (parsedDraftTags.length < 1) {
                if (clearInput) {
                    this.setHistoryTagDraft(normalizedEntryId, '');
                }

                return;
            }

            const entry = this.historyEntryById(normalizedEntryId);
            const existingTags = Array.isArray(entry?.tags) ? entry.tags : [];
            const nextTags = normalizeTags([...existingTags, ...parsedDraftTags]);

            this.updateHistoryEntryTags(normalizedEntryId, nextTags, {
                markUpdated: true,
            });

            if (clearInput) {
                this.setHistoryTagDraft(normalizedEntryId, '');
            }
        },

        removeHistoryEntryTag(entryId, tagValue) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);
            const normalizedTagValue = String(tagValue ?? '').trim();

            if (!normalizedEntryId || normalizedTagValue === '') {
                return;
            }

            const entry = this.historyEntryById(normalizedEntryId);

            if (!entry || !Array.isArray(entry?.tags)) {
                return;
            }

            const nextTags = entry.tags.filter(
                (tag) =>
                    String(tag ?? '').toLocaleLowerCase() !==
                    normalizedTagValue.toLocaleLowerCase(),
            );

            this.updateHistoryEntryTags(normalizedEntryId, nextTags, {
                markUpdated: true,
            });
        },

        historyEntryTagsAsText(entry) {
            if (!Array.isArray(entry?.tags)) {
                return '';
            }

            return entry.tags.join(', ');
        },

        historyEntrySourceLabel(entry) {
            const source = String(entry?.source ?? '');

            if (source === 'surah-directory') {
                return 'تنقّل سريع';
            }

            if (source === 'bookmark-navigation') {
                return 'إشارة مرجعية';
            }

            if (source === 'page-jump') {
                return 'قفزة صفحة';
            }

            if (source === 'page-slider-commit') {
                return 'شريط الصفحات';
            }

            return 'بحث';
        },

        historyEntryContextLabel(entry) {
            const surahNumber = Math.max(0, Math.trunc(Number(entry?.surah_number ?? 0)));
            const ayahNumber = Math.max(0, Math.trunc(Number(entry?.ayah_number ?? 0)));

            if (
                String(entry?.source ?? '') === 'surah-directory' ||
                String(entry?.source ?? '') === 'bookmark-navigation' ||
                String(entry?.source ?? '') === 'page-jump' ||
                String(entry?.source ?? '') === 'page-slider-commit'
            ) {
                return this.surahLabel(surahNumber > 0 ? surahNumber : this.currentSurahNumber());
            }

            if (surahNumber > 0 && ayahNumber > 0) {
                return `${this.surahLabel(surahNumber)} · آية ${ayahNumber}`;
            }

            if (surahNumber > 0) {
                return this.surahLabel(surahNumber);
            }

            const query = normalizeTextValue(entry?.query);

            if (query) {
                return `بحث: ${query}`;
            }

            return 'انتقال عبر البحث';
        },

        historyEntrySurahName(entry) {
            const surahNumber = Math.max(0, Math.trunc(Number(entry?.surah_number ?? 0)));
            const resolvedName = this.surahNameOnly(surahNumber);

            if (resolvedName !== '') {
                return resolvedName;
            }

            return '-';
        },

        nextHistorySortOrder() {
            return (
                this.navigationHistory
                    .filter((entry) => historyEntryHasPersistenceMeta(entry))
                    .reduce((maxValue, entry) => {
                        const sortOrder = Number(entry?.sort_order ?? 0);

                        return sortOrder > maxValue ? sortOrder : maxValue;
                    }, 0) + 1
            );
        },

        normalizePersistedHistorySortOrder() {
            let nextSortOrder = 1;

            this.navigationHistory = this.navigationHistory.map((entry) => {
                if (!historyEntryHasPersistenceMeta(entry)) {
                    return {
                        ...entry,
                        sort_order: 0,
                    };
                }

                const normalizedEntry = {
                    ...entry,
                    sort_order: nextSortOrder,
                };

                nextSortOrder += 1;

                return normalizedEntry;
            });
        },

        reorderNavigationHistoryByIds(orderIds = []) {
            const normalizedOrderIds = (Array.isArray(orderIds) ? orderIds : [])
                .map((entryId) => this.normalizeHistoryEntryId(entryId))
                .filter((entryId) => entryId !== '');

            if (normalizedOrderIds.length < 1) {
                return;
            }

            const historyById = new Map(
                this.navigationHistory.map((entry) => [
                    this.normalizeHistoryEntryId(entry?.id),
                    entry,
                ]),
            );
            const orderedEntries = [];
            const usedIds = new Set();

            normalizedOrderIds.forEach((entryId) => {
                const entry = historyById.get(entryId);

                if (!entry || usedIds.has(entryId)) {
                    return;
                }

                usedIds.add(entryId);
                orderedEntries.push(entry);
            });

            this.navigationHistory.forEach((entry) => {
                const entryId = this.normalizeHistoryEntryId(entry?.id);

                if (entryId === '' || usedIds.has(entryId)) {
                    return;
                }

                orderedEntries.push(entry);
            });

            this.navigationHistory = orderedEntries;
            this.normalizePersistedHistorySortOrder();
            this.persistNavigationHistory();
        },

        normalizeBookmarksSortOrder() {
            let nextSortOrder = 1;

            this.bookmarks = this.bookmarks.map((bookmark) => {
                const normalizedBookmark = {
                    ...bookmark,
                    sort_order: nextSortOrder,
                };

                nextSortOrder += 1;

                return normalizedBookmark;
            });
        },

        reorderBookmarksByIds(orderIds = []) {
            const normalizedOrderIds = (Array.isArray(orderIds) ? orderIds : [])
                .map((entryId) => this.normalizeBookmarkEntryId(entryId))
                .filter((entryId) => entryId !== '');

            if (normalizedOrderIds.length < 1) {
                return;
            }

            const bookmarksById = new Map(
                this.bookmarks.map((bookmark) => [
                    this.normalizeBookmarkEntryId(bookmark?.id),
                    bookmark,
                ]),
            );
            const orderedBookmarks = [];
            const usedIds = new Set();

            normalizedOrderIds.forEach((bookmarkId) => {
                const bookmark = bookmarksById.get(bookmarkId);

                if (!bookmark || usedIds.has(bookmarkId)) {
                    return;
                }

                usedIds.add(bookmarkId);
                orderedBookmarks.push(bookmark);
            });

            this.bookmarks.forEach((bookmark) => {
                const bookmarkId = this.normalizeBookmarkEntryId(bookmark?.id);

                if (bookmarkId === '' || usedIds.has(bookmarkId)) {
                    return;
                }

                orderedBookmarks.push(bookmark);
            });

            this.bookmarks = orderedBookmarks;
            this.normalizeBookmarksSortOrder();
            this.persistBookmarks();
        },

        emitLivewireManagerEvent(eventName, detail = {}) {
            const normalizedEventName = String(eventName ?? '').trim();

            if (normalizedEventName === '') {
                return;
            }

            if (typeof window?.Livewire?.dispatch === 'function') {
                window.Livewire.dispatch(normalizedEventName, detail);
            }
        },

        clearHistoryManagerSyncQueue() {
            this._historyManagerSyncTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._historyManagerSyncTimers = [];
        },

        clearBookmarksManagerSyncQueue() {
            this._bookmarksManagerSyncTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._bookmarksManagerSyncTimers = [];
        },

        queueHistoryManagerTableSync() {
            this.clearHistoryManagerSyncQueue();

            [0, 72, 180, 360].forEach((delayMs) => {
                const timerId = window.setTimeout(() => {
                    this._historyManagerSyncTimers = this._historyManagerSyncTimers.filter(
                        (activeTimerId) => activeTimerId !== timerId,
                    );
                    this.syncHistoryManagerTableRecords();
                }, delayMs);

                this._historyManagerSyncTimers.push(timerId);
            });
        },

        queueBookmarksManagerTableSync() {
            this.clearBookmarksManagerSyncQueue();

            [0, 72, 180, 360].forEach((delayMs) => {
                const timerId = window.setTimeout(() => {
                    this._bookmarksManagerSyncTimers = this._bookmarksManagerSyncTimers.filter(
                        (activeTimerId) => activeTimerId !== timerId,
                    );
                    this.syncBookmarksManagerTableRecords();
                }, delayMs);

                this._bookmarksManagerSyncTimers.push(timerId);
            });
        },

        syncHistoryManagerTableRecords() {
            const payload = {
                records: this.navigationHistory,
                surahNames: this.search?.surahNames ?? {},
            };

            this.emitLivewireManagerEvent('quran-history-manager-sync', payload);
        },

        syncBookmarksManagerTableRecords() {
            const payload = {
                records: this.bookmarks,
            };

            this.emitLivewireManagerEvent('quran-bookmarks-manager-sync', payload);
        },

        extractReorderIdsFromPayload(payload = null) {
            if (Array.isArray(payload)) {
                return payload
                    .map((value) => String(value ?? '').trim())
                    .filter((value) => value !== '');
            }

            if (!payload || typeof payload !== 'object') {
                return [];
            }

            return Object.entries(payload)
                .map(([recordId, order]) => ({
                    recordId: String(recordId ?? '').trim(),
                    order: Number(order ?? 0),
                }))
                .filter((entry) => entry.recordId !== '')
                .sort((left, right) => left.order - right.order)
                .map((entry) => entry.recordId);
        },

        async handleHistoryManagerGoEvent(detail = {}) {
            const entry = this.historyEntryById(detail?.id);

            if (!entry) {
                return;
            }

            await this.goToHistoryEntry(entry);
        },

        applyHistoryManagerRecordUpdate(detail = {}) {
            const entryId = this.normalizeHistoryEntryId(detail?.id);

            if (!entryId) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(detail ?? {}, 'note')) {
                this.updateHistoryEntryNote(entryId, detail?.note);
            }

            if (Object.prototype.hasOwnProperty.call(detail ?? {}, 'tags')) {
                this.updateHistoryEntryTags(entryId, detail?.tags ?? [], {
                    markUpdated: false,
                });
            }

            this.markManagerRowUpdated('history', entryId);
        },

        applyHistoryManagerReorder(detail = {}) {
            const orderIds = this.extractReorderIdsFromPayload(detail?.order ?? detail);

            if (orderIds.length < 1) {
                return;
            }

            this.reorderNavigationHistoryByIds(orderIds);
        },

        async handleBookmarksManagerGoEvent(detail = {}) {
            const bookmark = this.bookmarkEntryById(detail?.id);

            if (!bookmark) {
                return;
            }

            await this.goToBookmark(bookmark);
        },

        applyBookmarkManagerRecordUpdate(detail = {}) {
            const bookmarkId = this.normalizeBookmarkEntryId(detail?.id);

            if (!bookmarkId) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(detail ?? {}, 'note')) {
                this.updateBookmarkNote(bookmarkId, detail?.note);
            }

            if (Object.prototype.hasOwnProperty.call(detail ?? {}, 'tags')) {
                this.updateBookmarkTags(bookmarkId, detail?.tags ?? [], {
                    markUpdated: false,
                });
            }

            this.markManagerRowUpdated('bookmarks', bookmarkId);
        },

        applyBookmarksManagerReorder(detail = {}) {
            const orderIds = this.extractReorderIdsFromPayload(detail?.order ?? detail);

            if (orderIds.length < 1) {
                return;
            }

            this.reorderBookmarksByIds(orderIds);
        },

        dispatchManagerModalsVisibilityState() {
            const hasVisibleManagerModal =
                this.isSearchModalWindowVisible() ||
                this.isModalWindowVisibleById(this.historyModalId) ||
                this.isModalWindowVisibleById(this.bookmarksModalId) ||
                this.isModalWindowVisibleById(this.jumpPageModalId);

            window.dispatchEvent(
                new CustomEvent('quran-manager-modals-visibility', {
                    detail: {
                        open:
                            hasVisibleManagerModal ||
                            this.search.modalOpen ||
                            this.historyModalOpen ||
                            this.bookmarksModalOpen ||
                            this.jumpPageModalOpen,
                    },
                }),
            );
        },

        managerRowEffectClass(collection, itemId) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';
            const normalizedItemId = String(itemId ?? '').trim();

            if (normalizedItemId === '') {
                return '';
            }

            const effect = this.managerRowEffects?.[normalizedCollection]?.[normalizedItemId] ?? '';

            if (effect === 'updated') {
                return 'quran-manager-row--updated';
            }

            if (effect === 'replacing') {
                return 'quran-manager-row--replacing';
            }

            if (effect === 'removing') {
                return 'quran-manager-row--removing';
            }

            return '';
        },

        historyRowEffectClass(entry) {
            return this.managerRowEffectClass('history', entry?.id);
        },

        bookmarkRowEffectClass(bookmark) {
            return this.managerRowEffectClass('bookmarks', bookmark?.id);
        },

        managerRowEffectTimerKey(collection, itemId) {
            return `${collection}:${itemId}`;
        },

        clearManagerRowEffectTimer(collection, itemId) {
            const timerKey = this.managerRowEffectTimerKey(collection, itemId);
            const timerId = this._managerRowEffectTimers.get(timerKey);

            if (timerId !== undefined) {
                clearTimeout(timerId);
                this._managerRowEffectTimers.delete(timerKey);
            }
        },

        setManagerRowEffect(collection, itemId, effect) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';
            const normalizedItemId = String(itemId ?? '').trim();

            if (normalizedItemId === '') {
                return;
            }

            const nextCollectionEffects = {
                ...(this.managerRowEffects?.[normalizedCollection] ?? {}),
            };

            if (String(effect ?? '').trim() === '') {
                delete nextCollectionEffects[normalizedItemId];
            } else {
                nextCollectionEffects[normalizedItemId] = String(effect ?? '');
            }

            this.managerRowEffects = {
                ...this.managerRowEffects,
                [normalizedCollection]: nextCollectionEffects,
            };
        },

        markManagerRowUpdated(collection, itemId) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';
            const normalizedItemId = String(itemId ?? '').trim();

            if (normalizedItemId === '') {
                return;
            }

            this.clearManagerRowEffectTimer(normalizedCollection, normalizedItemId);
            this.setManagerRowEffect(normalizedCollection, normalizedItemId, 'updated');

            const timerKey = this.managerRowEffectTimerKey(normalizedCollection, normalizedItemId);
            const timerId = window.setTimeout(() => {
                this.setManagerRowEffect(normalizedCollection, normalizedItemId, '');
                this._managerRowEffectTimers.delete(timerKey);
            }, managerRowUpdateAnimationDurationMs);

            this._managerRowEffectTimers.set(timerKey, timerId);
        },

        markManagerRowReplaced(collection, itemId) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';
            const normalizedItemId = String(itemId ?? '').trim();

            if (normalizedItemId === '') {
                return;
            }

            this.clearManagerRowEffectTimer(normalizedCollection, normalizedItemId);
            this.setManagerRowEffect(normalizedCollection, normalizedItemId, 'replacing');

            const timerKey = this.managerRowEffectTimerKey(normalizedCollection, normalizedItemId);
            const timerId = window.setTimeout(() => {
                this.setManagerRowEffect(normalizedCollection, normalizedItemId, '');
                this._managerRowEffectTimers.delete(timerKey);
            }, managerRowReplaceAnimationDurationMs);

            this._managerRowEffectTimers.set(timerKey, timerId);
        },

        markManagerRowsRemoving(collection, itemIds = []) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';

            itemIds
                .map((itemId) => String(itemId ?? '').trim())
                .filter((itemId) => itemId !== '')
                .forEach((itemId) => {
                    this.clearManagerRowEffectTimer(normalizedCollection, itemId);
                    this.setManagerRowEffect(normalizedCollection, itemId, 'removing');
                });
        },

        updateHistoryEntryTags(entryId, rawTags, { markUpdated = true } = {}) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const parsedTags = normalizeTags(rawTags);
            let didUpdateEntry = false;

            this.navigationHistory = this.navigationHistory.map((entry) => {
                if (this.normalizeHistoryEntryId(entry?.id) !== normalizedEntryId) {
                    return entry;
                }

                const currentTags = normalizeTags(entry?.tags ?? []);

                if (this.historyTagsMatch(currentTags, parsedTags)) {
                    return entry;
                }

                didUpdateEntry = true;
                const nextNote = normalizeTextValue(entry?.note);
                const nextSortOrder =
                    parsedTags.length > 0 || Boolean(nextNote)
                        ? Math.max(1, Number(entry?.sort_order ?? this.nextHistorySortOrder()))
                        : 0;

                return {
                    ...entry,
                    tags: parsedTags,
                    created_at: Number(entry?.created_at ?? Date.now()),
                    sort_order: nextSortOrder,
                };
            });

            if (!didUpdateEntry) {
                this.syncHistoryTagDraftForEntry(normalizedEntryId);

                return;
            }

            this.normalizePersistedHistorySortOrder();
            this.persistNavigationHistory();

            if (markUpdated) {
                this.markManagerRowUpdated('history', normalizedEntryId);
            }

            this.syncHistoryTagDraftForEntry(normalizedEntryId);
        },

        updateHistoryEntryNote(entryId, note) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const normalizedNote = normalizeTextValue(note);
            let didUpdateEntry = false;

            this.navigationHistory = this.navigationHistory.map((entry) => {
                if (this.normalizeHistoryEntryId(entry?.id) !== normalizedEntryId) {
                    return entry;
                }

                if (normalizeTextValue(entry?.note) === normalizedNote) {
                    return entry;
                }

                didUpdateEntry = true;
                const existingTags = normalizeTags(entry?.tags ?? []);
                const nextSortOrder =
                    existingTags.length > 0 || Boolean(normalizedNote)
                        ? Math.max(1, Number(entry?.sort_order ?? this.nextHistorySortOrder()))
                        : 0;

                return {
                    ...entry,
                    note: normalizedNote,
                    created_at: Number(entry?.created_at ?? Date.now()),
                    sort_order: nextSortOrder,
                };
            });

            if (!didUpdateEntry) {
                return;
            }

            this.normalizePersistedHistorySortOrder();
            this.persistNavigationHistory();
            this.markManagerRowUpdated('history', normalizedEntryId);
        },

        removeHistoryEntry(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const hasEntry = this.navigationHistory.some(
                (entry) => this.normalizeHistoryEntryId(entry?.id) === normalizedEntryId,
            );

            if (!hasEntry) {
                return;
            }

            this.markManagerRowsRemoving('history', [normalizedEntryId]);

            window.setTimeout(() => {
                this.navigationHistory = this.navigationHistory.filter((entry) => {
                    return this.normalizeHistoryEntryId(entry?.id) !== normalizedEntryId;
                });
                this.normalizePersistedHistorySortOrder();
                this.persistNavigationHistory();
                this.syncHistoryTagDrafts();
                this.setManagerRowEffect('history', normalizedEntryId, '');
            }, managerRowRemoveAnimationDurationMs);
        },

        clearNavigationHistory() {
            const removableIds = this.navigationHistory
                .filter((entry) => !historyEntryHasPersistenceMeta(entry))
                .map((entry) => String(entry?.id ?? '').trim())
                .filter((entryId) => entryId !== '');

            if (removableIds.length === 0) {
                return;
            }

            this.markManagerRowsRemoving('history', removableIds);

            window.setTimeout(() => {
                this.navigationHistory = this.navigationHistory.filter((entry) => {
                    const normalizedEntryId = String(entry?.id ?? '').trim();

                    return !removableIds.includes(normalizedEntryId);
                });
                this.normalizePersistedHistorySortOrder();
                this.persistNavigationHistory();
                this.syncHistoryTagDrafts();
                removableIds.forEach((entryId) => {
                    this.setManagerRowEffect('history', entryId, '');
                });
            }, managerRowRemoveAnimationDurationMs);
        },

        recordNavigationHistory({
            source = 'search-result',
            pageNumber = this.pageNumber,
            surahNumber = 0,
            ayahNumber = 0,
            ayahIndex = 0,
            note = null,
            query = null,
            tags = [],
        } = {}) {
            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);
            const normalizedSurahNumber = Math.max(0, Math.trunc(Number(surahNumber ?? 0)));
            const normalizedAyahNumber = Math.max(0, Math.trunc(Number(ayahNumber ?? 0)));
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));
            const sourceValue = String(source ?? '');
            const normalizedSource = supportedHistorySources.includes(sourceValue)
                ? sourceValue
                : 'search-result';
            const normalizedNote = normalizeTextValue(note);
            const normalizedQuery = normalizeTextValue(query);
            const normalizedTags = normalizeTags(tags);

            this.navigationHistory = [
                normalizeHistoryEntry({
                    id: uniqueLocalId(),
                    source: normalizedSource,
                    page_number: normalizedPageNumber,
                    surah_number: normalizedSurahNumber,
                    ayah_number: normalizedAyahNumber,
                    ayah_index: normalizedAyahIndex,
                    note: normalizedNote,
                    query: normalizedQuery,
                    tags: normalizedTags,
                    created_at: Date.now(),
                    sort_order:
                        normalizedTags.length > 0 || Boolean(normalizedNote)
                            ? this.nextHistorySortOrder()
                            : 0,
                }),
                ...this.navigationHistory,
            ];
            this.normalizePersistedHistorySortOrder();
            this.persistNavigationHistory();
            this.syncHistoryTagDrafts();
        },

        normalizeBookmarkEntryId(bookmarkId) {
            return String(bookmarkId ?? '').trim();
        },

        bookmarkEntryById(bookmarkId) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return null;
            }

            return (
                this.bookmarks.find(
                    (bookmark) =>
                        this.normalizeBookmarkEntryId(bookmark?.id) === normalizedBookmarkId,
                ) ?? null
            );
        },

        syncBookmarkTagDraftForEntry(bookmarkId) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return;
            }

            const existingBookmark = this.bookmarkEntryById(normalizedBookmarkId);

            if (!existingBookmark) {
                if (
                    this.bookmarkTagDraftById &&
                    Object.prototype.hasOwnProperty.call(
                        this.bookmarkTagDraftById,
                        normalizedBookmarkId,
                    )
                ) {
                    const nextDrafts = { ...this.bookmarkTagDraftById };
                    delete nextDrafts[normalizedBookmarkId];
                    this.bookmarkTagDraftById = nextDrafts;
                }

                return;
            }

            const currentDraft = String(
                this.bookmarkTagDraftById?.[normalizedBookmarkId] ?? '',
            ).trim();

            if (currentDraft !== '') {
                return;
            }

            this.bookmarkTagDraftById = {
                ...this.bookmarkTagDraftById,
                [normalizedBookmarkId]: '',
            };
        },

        syncBookmarkTagDrafts() {
            const nextDrafts = {};

            this.bookmarks.forEach((bookmark) => {
                const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmark?.id);

                if (!normalizedBookmarkId) {
                    return;
                }

                nextDrafts[normalizedBookmarkId] = String(
                    this.bookmarkTagDraftById?.[normalizedBookmarkId] ?? '',
                );
            });

            this.bookmarkTagDraftById = nextDrafts;
        },

        bookmarkTagDraft(bookmarkId) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return '';
            }

            return String(this.bookmarkTagDraftById?.[normalizedBookmarkId] ?? '');
        },

        setBookmarkTagDraft(bookmarkId, value) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return;
            }

            this.bookmarkTagDraftById = {
                ...this.bookmarkTagDraftById,
                [normalizedBookmarkId]: String(value ?? ''),
            };
        },

        bookmarkTagSuggestions(bookmarkId) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);
            const bookmark = this.bookmarkEntryById(normalizedBookmarkId);
            const existingTags = Array.isArray(bookmark?.tags) ? bookmark.tags : [];
            const draftValue = this.bookmarkTagDraft(normalizedBookmarkId);

            return this.collectSharedManagerTags({
                excludeTags: existingTags,
                draftValue,
            });
        },

        commitBookmarkTagDraft(bookmarkId, { clearInput = true } = {}) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return;
            }

            const draftValue = this.bookmarkTagDraft(normalizedBookmarkId);
            const parsedDraftTags = normalizeTags(draftValue);

            if (parsedDraftTags.length < 1) {
                if (clearInput) {
                    this.setBookmarkTagDraft(normalizedBookmarkId, '');
                }

                return;
            }

            const bookmark = this.bookmarkEntryById(normalizedBookmarkId);
            const existingTags = Array.isArray(bookmark?.tags) ? bookmark.tags : [];
            const nextTags = normalizeTags([...existingTags, ...parsedDraftTags]);

            this.updateBookmarkTags(normalizedBookmarkId, nextTags, {
                markUpdated: true,
            });

            if (clearInput) {
                this.setBookmarkTagDraft(normalizedBookmarkId, '');
            }
        },

        removeBookmarkTag(bookmarkId, tagValue) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);
            const normalizedTagValue = String(tagValue ?? '').trim();

            if (!normalizedBookmarkId || normalizedTagValue === '') {
                return;
            }

            const bookmark = this.bookmarkEntryById(normalizedBookmarkId);

            if (!bookmark || !Array.isArray(bookmark?.tags)) {
                return;
            }

            const nextTags = bookmark.tags.filter(
                (tag) =>
                    String(tag ?? '').toLocaleLowerCase() !==
                    normalizedTagValue.toLocaleLowerCase(),
            );

            this.updateBookmarkTags(normalizedBookmarkId, nextTags, {
                markUpdated: true,
            });
        },

        bookmarkedPageEntry(pageNumber = this.pageNumber) {
            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);

            return (
                this.bookmarks.find(
                    (bookmark) =>
                        clampPage(bookmark?.page_number ?? 1, this.maxPage) ===
                        normalizedPageNumber,
                ) ?? null
            );
        },

        isCurrentPageBookmarked() {
            return this.bookmarkedPageEntry(this.pageNumber) !== null;
        },

        defaultBookmarkNote(pageNumber = this.pageNumber) {
            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);
            const surahTitle = this.currentSurahTitle();

            return `${surahTitle} · صفحة ${normalizedPageNumber}`;
        },

        addBookmark({
            pageNumber = this.pageNumber,
            note = null,
            tags = [],
            preserveCreatedAt = null,
            id = null,
        } = {}) {
            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);
            const timestamp = Date.now();
            const existingEntry = this.bookmarkedPageEntry(normalizedPageNumber);
            const nextId = String(id ?? existingEntry?.id ?? uniqueLocalId());
            const normalizedNote = normalizeTextValue(note ?? existingEntry?.note);
            const normalizedTags = normalizeTags(tags ?? existingEntry?.tags ?? []);

            this.bookmarks = this.bookmarks.filter(
                (bookmark) => String(bookmark?.id ?? '') !== String(existingEntry?.id ?? ''),
            );
            this.normalizeBookmarksSortOrder();
            this.bookmarks.unshift(
                normalizeBookmarkEntry({
                    id: nextId,
                    page_number: normalizedPageNumber,
                    note: normalizedNote,
                    tags: normalizedTags,
                    created_at:
                        preserveCreatedAt !== null
                            ? Number(preserveCreatedAt)
                            : Number(existingEntry?.created_at ?? timestamp),
                    updated_at: timestamp,
                    sort_order: 1,
                }),
            );
            this.normalizeBookmarksSortOrder();
            this.persistBookmarks();
            this.markManagerRowUpdated('bookmarks', nextId);
            this.syncBookmarkTagDraftForEntry(nextId);
        },

        toggleCurrentPageBookmark() {
            const existingEntry = this.bookmarkedPageEntry(this.pageNumber);

            if (existingEntry) {
                this.removeBookmark(existingEntry.id);

                return;
            }

            this.addBookmark({ pageNumber: this.pageNumber });
        },

        removeBookmark(bookmarkId) {
            const normalizedBookmarkId = String(bookmarkId ?? '').trim();

            if (!normalizedBookmarkId) {
                return;
            }

            this.markManagerRowsRemoving('bookmarks', [normalizedBookmarkId]);

            window.setTimeout(() => {
                this.bookmarks = this.bookmarks.filter(
                    (bookmark) => String(bookmark?.id ?? '') !== normalizedBookmarkId,
                );
                this.normalizeBookmarksSortOrder();
                this.persistBookmarks();
                this.setManagerRowEffect('bookmarks', normalizedBookmarkId, '');
                this.syncBookmarkTagDrafts();
            }, managerRowRemoveAnimationDurationMs);
        },

        updateBookmarkNote(bookmarkId, note) {
            const normalizedBookmarkId = String(bookmarkId ?? '').trim();

            if (!normalizedBookmarkId) {
                return;
            }

            const normalizedNote = normalizeTextValue(note);
            let didUpdateBookmark = false;

            this.bookmarks = this.bookmarks.map((bookmark) => {
                if (String(bookmark?.id ?? '') !== normalizedBookmarkId) {
                    return bookmark;
                }

                if (normalizeTextValue(bookmark?.note) === normalizedNote) {
                    return bookmark;
                }

                didUpdateBookmark = true;

                return {
                    ...bookmark,
                    note: normalizedNote,
                    updated_at: Date.now(),
                };
            });

            if (!didUpdateBookmark) {
                return;
            }

            this.normalizeBookmarksSortOrder();
            this.persistBookmarks();
            this.markManagerRowUpdated('bookmarks', normalizedBookmarkId);
        },

        updateBookmarkTags(bookmarkId, rawTags, { markUpdated = true } = {}) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return;
            }

            const parsedTags = normalizeTags(rawTags);
            let didUpdateBookmark = false;

            this.bookmarks = this.bookmarks.map((bookmark) => {
                if (this.normalizeBookmarkEntryId(bookmark?.id) !== normalizedBookmarkId) {
                    return bookmark;
                }

                const currentTags = normalizeTags(bookmark?.tags ?? []);

                if (this.historyTagsMatch(currentTags, parsedTags)) {
                    return bookmark;
                }

                didUpdateBookmark = true;

                return {
                    ...bookmark,
                    tags: parsedTags,
                    updated_at: Date.now(),
                };
            });

            if (!didUpdateBookmark) {
                this.syncBookmarkTagDraftForEntry(normalizedBookmarkId);

                return;
            }

            this.normalizeBookmarksSortOrder();
            this.persistBookmarks();

            if (markUpdated) {
                this.markManagerRowUpdated('bookmarks', normalizedBookmarkId);
            }

            this.syncBookmarkTagDraftForEntry(normalizedBookmarkId);
        },

        replaceBookmarkPage(bookmarkId) {
            const normalizedBookmarkId = String(bookmarkId ?? '').trim();
            const targetBookmark = this.bookmarks.find(
                (bookmark) => String(bookmark?.id ?? '') === normalizedBookmarkId,
            );

            if (!targetBookmark) {
                return;
            }

            const samePageBookmark = this.bookmarkedPageEntry(this.pageNumber);

            this.bookmarks = this.bookmarks.filter((bookmark) => {
                const normalizedBookmarkEntryId = String(bookmark?.id ?? '');

                if (normalizedBookmarkEntryId === normalizedBookmarkId) {
                    return true;
                }

                if (
                    samePageBookmark &&
                    normalizedBookmarkEntryId === String(samePageBookmark?.id ?? '') &&
                    normalizedBookmarkEntryId !== normalizedBookmarkId
                ) {
                    return false;
                }

                return true;
            });
            this.bookmarks = this.bookmarks.map((bookmark) => {
                if (String(bookmark?.id ?? '') !== normalizedBookmarkId) {
                    return bookmark;
                }

                return normalizeBookmarkEntry({
                    ...bookmark,
                    page_number: this.pageNumber,
                    updated_at: Date.now(),
                });
            });
            this.reorderBookmarksByIds([
                normalizedBookmarkId,
                ...this.bookmarks
                    .map((bookmark) => this.normalizeBookmarkEntryId(bookmark?.id))
                    .filter((bookmarkId) => bookmarkId !== normalizedBookmarkId),
            ]);
            this.markManagerRowReplaced('bookmarks', normalizedBookmarkId);
            this.syncBookmarkTagDrafts();
        },

        clearBookmarkButtonPressState({ resetSuppressClick = true } = {}) {
            if (this.bookmarkButtonPress.timer !== null) {
                clearTimeout(this.bookmarkButtonPress.timer);
                this.bookmarkButtonPress.timer = null;
            }

            this.bookmarkButtonPress.pointerId = null;
            this.bookmarkButtonPress.holdTriggered = false;

            if (resetSuppressClick) {
                this.bookmarkButtonPress.suppressClick = false;
            }
        },

        onBookmarkButtonPointerDown(event) {
            this.clearBookmarkButtonPressState();
            this.bookmarkButtonPress.pointerId = Number(event?.pointerId ?? 0) || null;
            this.bookmarkButtonPress.holdTriggered = false;
            this.bookmarkButtonPress.suppressClick = false;
            this.bookmarkButtonPress.timer = window.setTimeout(() => {
                this.bookmarkButtonPress.timer = null;
                this.bookmarkButtonPress.holdTriggered = true;
                this.bookmarkButtonPress.suppressClick = true;
                this.openBookmarksManager();
            }, bookmarkHoldDelayMs);
        },

        onBookmarkButtonPointerUp(event) {
            const pointerId = Number(event?.pointerId ?? 0) || null;

            if (
                this.bookmarkButtonPress.pointerId !== null &&
                pointerId !== null &&
                this.bookmarkButtonPress.pointerId !== pointerId
            ) {
                return;
            }

            if (this.bookmarkButtonPress.timer !== null) {
                clearTimeout(this.bookmarkButtonPress.timer);
                this.bookmarkButtonPress.timer = null;
            }
        },

        onBookmarkButtonPointerCancel() {
            this.clearBookmarkButtonPressState();
        },

        onBookmarkButtonClick() {
            if (this.bookmarkButtonPress.suppressClick) {
                this.clearBookmarkButtonPressState();

                return;
            }

            this.toggleCurrentPageBookmark();
            this.clearBookmarkButtonPressState();
        },

        clearSurahQuickNavigatorPressState({ resetSuppressClick = true } = {}) {
            if (this.surahQuickNavigator.timer !== null) {
                clearTimeout(this.surahQuickNavigator.timer);
                this.surahQuickNavigator.timer = null;
            }

            this.surahQuickNavigator.pointerId = null;
            this.surahQuickNavigator.holdTriggered = false;

            if (resetSuppressClick) {
                this.surahQuickNavigator.suppressClick = false;
            }
        },

        openSurahQuickNavigator() {
            if (this.wirdModeActive) {
                return;
            }

            this.surahQuickNavigator.visible = true;
            this.surahQuickNavigator.holdTriggered = true;
            this.surahQuickNavigator.suppressClick = true;
        },
    };
};
