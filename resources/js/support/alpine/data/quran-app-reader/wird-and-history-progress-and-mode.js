export const createWirdAndHistoryProgressAndModeModule = (deps) => {
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
        ensureWirdDailyRecord({ forceRebuild = false, preserveProgressOnRebuild = true } = {}) {
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
                    record &&
                    typeof record === 'object' &&
                    (!forceRebuild || preserveProgressOnRebuild);
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
                    ? Boolean(record?.completed) || (maxStep > 0 && carriedProgressStep >= maxStep)
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
    };
};
