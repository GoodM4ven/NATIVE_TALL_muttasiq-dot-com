export const createLifecycleBootstrapSupportAndWirdStateModule = (deps) => {
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
        hydratePersistedFitCache() {
            if (!shouldPersistFitCacheAcrossReloads) {
                this._fitResultByContext.clear();
                this._fitCacheBreakpoint = this.resolveCurrentBreakpointName();
                this.storage.fitCacheBreakpoint = this._fitCacheBreakpoint;

                return;
            }

            const persistedCache = readLocalStorage(fitCacheStorageKey, null);

            if (!persistedCache || typeof persistedCache !== 'object') {
                return;
            }

            const persistedVersion = Math.trunc(Number(persistedCache?.version ?? 0));

            if (persistedVersion !== fitCacheStorageVersion) {
                return;
            }

            const persistedBreakpoint = String(persistedCache?.breakpoint ?? '').trim();
            const currentBreakpoint = this.resolveCurrentBreakpointName();

            if (
                persistedBreakpoint !== '' &&
                currentBreakpoint !== '' &&
                persistedBreakpoint !== currentBreakpoint
            ) {
                writeLocalStorage(fitCacheStorageKey, {
                    version: fitCacheStorageVersion,
                    breakpoint: currentBreakpoint,
                    updated_at: Date.now(),
                    entries: {},
                    order: [],
                });

                return;
            }

            const persistedEntries =
                persistedCache?.entries &&
                typeof persistedCache.entries === 'object' &&
                !Array.isArray(persistedCache.entries)
                    ? persistedCache.entries
                    : {};
            const persistedOrder = Array.isArray(persistedCache?.order)
                ? persistedCache.order.map((key) => String(key ?? '').trim()).filter(Boolean)
                : Object.keys(persistedEntries);

            this._fitResultByContext.clear();

            persistedOrder.forEach((cacheKey) => {
                const normalizedEntry = this.normalizeFitCacheEntry(persistedEntries[cacheKey]);

                if (!normalizedEntry) {
                    return;
                }

                this._fitResultByContext.set(cacheKey, normalizedEntry);
            });

            this.trimFitResultCache();
            this._fitCacheBreakpoint = currentBreakpoint;
            this.storage.fitCacheBreakpoint = currentBreakpoint;
        },

        queuePersistedFitCacheWrite(delayMs = 140) {
            if (typeof window === 'undefined') {
                return;
            }

            if (!shouldPersistFitCacheAcrossReloads) {
                return;
            }

            if (this._fitCachePersistWriteTimer !== null) {
                clearTimeout(this._fitCachePersistWriteTimer);
                this._fitCachePersistWriteTimer = null;
            }

            this._fitCachePersistWriteTimer = window.setTimeout(
                () => {
                    this._fitCachePersistWriteTimer = null;
                    this.flushPersistedFitCacheWrite();
                },
                Math.max(24, Math.trunc(Number(delayMs) || 140)),
            );
        },

        flushPersistedFitCacheWrite() {
            const currentBreakpoint = this.resolveCurrentBreakpointName();
            const entries = {};
            const order = [];
            const cacheEntries = Array.from(this._fitResultByContext.entries()).slice(
                -fitResultCacheLimit,
            );

            cacheEntries.forEach(([cacheKey, entry]) => {
                const normalizedEntry = this.normalizeFitCacheEntry(entry);

                if (!normalizedEntry) {
                    return;
                }

                entries[cacheKey] = normalizedEntry;
                order.push(cacheKey);
            });

            writeLocalStorage(fitCacheStorageKey, {
                version: fitCacheStorageVersion,
                breakpoint: currentBreakpoint,
                viewport_bucket: {
                    width: this.viewportBucketValue(window.innerWidth),
                    height: this.viewportBucketValue(window.innerHeight),
                },
                updated_at: Date.now(),
                entries,
                order,
            });
        },

        async bootstrap() {
            this.qrDebugLog(
                '[QR:bootstrap] START, visible:',
                this.isReaderElementVisible(),
                'ready:',
                this.ready,
                'maxPage:',
                this.maxPage,
                'pageNumber:',
                this.pageNumber,
            );
            await this.ensurePersistentStorage();
            this.syncSupportLockTargetsUi();
            this.syncFitCacheBreakpoint({ persist: false });
            this.hydratePersistedFitCache();
            this._startupCalibrationPending = true;
            this.syncReaderChromeDocumentClass();
            window.dispatchEvent(new CustomEvent('quran-reader-calibration-started'));

            try {
                if (this._startupRestoreInFlight instanceof Promise) {
                    this.qrDebugLog('[QR:bootstrap] awaiting _startupRestoreInFlight');
                    try {
                        await this._startupRestoreInFlight;
                    } catch (_) {
                        // Ignore startup restore aborts; ensureCurrentPageLoaded() will recover.
                    }
                }

                this.qrDebugLog(
                    '[QR:bootstrap] before calibrate, visible:',
                    this.isReaderElementVisible(),
                );
                await this.calibrateGlobalFitLayoutFromReferencePage(fitCalibrationReferencePage);
                this.qrDebugLog(
                    '[QR:bootstrap] after calibrate, _globalFitCalibrationLayout:',
                    !!this._globalFitCalibrationLayout,
                    '_globalFitCalibrationScale:',
                    this._globalFitCalibrationScale,
                );
                await this.ensureCurrentPageLoaded();
                this.qrDebugLog(
                    '[QR:bootstrap] after ensureCurrentPageLoaded, pageNumber:',
                    this.pageNumber,
                );
                await this.runStartupFinalFitPass();
                this.qrDebugLog(
                    '[QR:bootstrap] after runStartupFinalFitPass, pageScale:',
                    this.pageScale,
                    'isFittingPage:',
                    this.isFittingPage,
                );
                this.queueStartupPreload();
                this.scheduleIdleWarmup();
                this.warmSearchIndex();
                this.scheduleManagerModalsPrewarm();
            } catch (error) {
                this.qrDebugError('[QR:bootstrap] ERROR:', error);
            } finally {
                this._startupCalibrationPending = false;
                this.hasCompletedInitialMushafPreparation = true;
                this.syncReaderChromeDocumentClass();
                window.dispatchEvent(new CustomEvent('quran-reader-calibration-finished'));
                this.qrDebugLog(
                    '[QR:bootstrap] DONE, hasCompletedInitialMushafPreparation:',
                    true,
                    'pageScale:',
                    this.pageScale,
                );
            }
        },

        captureCurrentFitLayoutFromRoot() {
            const rootElement = this.$el.firstElementChild;
            const pageContentElement = this.$refs?.pageContent;

            if (!(rootElement instanceof HTMLElement)) {
                return null;
            }

            const styles = window.getComputedStyle(rootElement);
            const pageContentStyles =
                pageContentElement instanceof HTMLElement
                    ? window.getComputedStyle(pageContentElement)
                    : null;
            const readLayoutNumber = (propertyName, fallback) => {
                const inlineValue = Number.parseFloat(
                    rootElement.style.getPropertyValue(propertyName),
                );

                if (Number.isFinite(inlineValue)) {
                    return inlineValue;
                }

                const computedValue = Number.parseFloat(styles.getPropertyValue(propertyName));

                return Number.isFinite(computedValue) ? computedValue : fallback;
            };
            const readPageScaleNumber = (fallback) => {
                if (pageContentElement instanceof HTMLElement) {
                    const inlineValue = Number.parseFloat(
                        pageContentElement.style.getPropertyValue('--quran-page-scale'),
                    );

                    if (Number.isFinite(inlineValue)) {
                        return inlineValue;
                    }

                    const computedValue = Number.parseFloat(
                        pageContentStyles?.getPropertyValue('--quran-page-scale') ?? '',
                    );

                    if (Number.isFinite(computedValue)) {
                        return computedValue;
                    }
                }

                return readLayoutNumber('--quran-page-scale', fallback);
            };

            return {
                pageTypeScale: Math.max(0.2, readLayoutNumber('--quran-page-type-scale', 1)),
                pageLeadingMultiplier: Math.max(
                    0.25,
                    readLayoutNumber('--quran-page-leading-multiplier', 1),
                ),
                pageGapMultiplier: Math.max(0, readLayoutNumber('--quran-page-gap-multiplier', 1)),
                pageSurahHeaderScale: Math.max(
                    0.5,
                    readLayoutNumber('--quran-page-surah-header-scale', 1),
                ),
                basmallahBottomGapScale: readLayoutNumber(
                    '--quran-basmallah-bottom-gap-scale',
                    defaultBasmallahBottomGapScale,
                ),
                pageScale: Math.max(0.05, Number(this.pageScale) || readPageScaleNumber(1)),
            };
        },

        async calibrateGlobalFitLayoutFromReferencePage(
            referencePage = fitCalibrationReferencePage,
            { persistGlobalCalibration = true } = {},
        ) {
            const normalizedReferencePage = clampPage(referencePage, this.maxPage);

            if (normalizedReferencePage <= 0) {
                return;
            }

            const startupTargetPage = clampPage(
                Number(this._startupTargetPageNumber ?? this.pageInput ?? this.pageNumber),
                this.maxPage,
            );

            this.isCalibrating = true;

            try {
                const referencePayload = await this.getPagePayload(normalizedReferencePage, {
                    preferCache: true,
                });

                if (!referencePayload) {
                    return;
                }

                this.applyPayload(referencePayload, {
                    setPageNumber: true,
                    persistPageNumber: false,
                });
                await this.nextTickAsync();
                await this.waitForPageFontReady();
                await this.resolveWithTimeout(document.fonts?.ready, 3200, {
                    timeoutValue: 'timeout',
                });
                await this.waitForStablePageFrame({
                    maxFrames: 22,
                    requiredStableFrames: 3,
                    tolerancePx: 0.8,
                });
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 110,
                    maxAttempts: 5,
                    useIdleFit: true,
                });
                await this.waitForStableRenderedText(12);
                const rootElement = this.$el.firstElementChild;
                const frameElement = this.$refs.pageFrame;
                const contentElement = this.$refs.pageContent;

                if (
                    rootElement instanceof HTMLElement &&
                    frameElement instanceof HTMLElement &&
                    contentElement instanceof HTMLElement
                ) {
                    const frameRect = frameElement.getBoundingClientRect();
                    const frameParentRect =
                        frameElement.parentElement?.getBoundingClientRect?.() ?? null;
                    const styles = window.getComputedStyle(rootElement);
                    const fitTargetWidthRatio = Math.min(
                        0.95,
                        Math.max(
                            0.55,
                            Number.parseFloat(
                                styles.getPropertyValue('--quran-fit-target-width-ratio'),
                            ) || 0.8,
                        ),
                    );
                    const fitAreaPaddingX = Math.max(
                        0,
                        this.cssCustomLengthPixels(
                            styles,
                            '--quran-fit-area-pad-x',
                            rootElement,
                            0,
                        ),
                    );
                    const fitAreaPaddingY = Math.max(
                        0,
                        this.cssCustomLengthPixels(
                            styles,
                            '--quran-fit-area-pad-y',
                            rootElement,
                            0,
                        ),
                    );
                    const fitHeightRatio = Math.min(
                        1,
                        Math.max(
                            0.7,
                            Number.parseFloat(
                                styles.getPropertyValue('--quran-fit-height-ratio'),
                            ) || 1,
                        ),
                    );
                    const availableWidth = Math.max(
                        1,
                        Number(
                            frameParentRect?.width ??
                                frameRect?.width ??
                                frameElement.parentElement?.clientWidth ??
                                frameElement.clientWidth ??
                                1,
                        ) -
                            fitAreaPaddingX * 2,
                    );
                    const availableHeight = Math.max(
                        1,
                        (Number(frameRect?.height ?? frameElement.clientHeight ?? 1) -
                            fitAreaPaddingY * 2) *
                            fitHeightRatio,
                    );
                    const targetWidth = Math.max(1, availableWidth * fitTargetWidthRatio);
                    const targetHeight = Math.max(1, availableHeight);
                    const measured = this.measureRenderedBounds(contentElement, {
                        useRobustWidth: false,
                    });
                    const downscaleCorrection = Math.min(
                        1,
                        targetWidth / Math.max(1, measured.width),
                        targetHeight / Math.max(1, measured.height),
                    );

                    if (Number.isFinite(downscaleCorrection) && downscaleCorrection < 0.999) {
                        const minScale = Math.max(
                            0.05,
                            Number.parseFloat(styles.getPropertyValue('--quran-min-page-scale')) ||
                                0.1,
                        );
                        const maxScale = Math.max(
                            minScale,
                            Number.parseFloat(styles.getPropertyValue('--quran-max-page-scale')) ||
                                1,
                        );
                        const currentScale = Math.max(
                            minScale,
                            Math.min(
                                maxScale,
                                Number(this.pageScale) ||
                                    Number.parseFloat(
                                        contentElement.style.getPropertyValue('--quran-page-scale'),
                                    ) ||
                                    1,
                            ),
                        );
                        const correctedScale = Math.max(
                            minScale,
                            Math.min(
                                maxScale,
                                Number((currentScale * downscaleCorrection).toFixed(4)),
                            ),
                        );

                        this.pageScale = correctedScale;
                        this.setCurrentPageScale(correctedScale, { forFitting: true });
                    }
                }

                const capturedLayout = this.captureCurrentFitLayoutFromRoot();

                if (capturedLayout && persistGlobalCalibration) {
                    this._globalFitCalibrationLayout = capturedLayout;
                    this._globalFitCalibrationScale = Math.max(
                        0.05,
                        Number(capturedLayout.pageScale ?? 1),
                    );
                    this._globalFitCalibrationPageNumber = normalizedReferencePage;
                }
            } catch (error) {
                if (persistGlobalCalibration) {
                    this._globalFitCalibrationLayout = null;
                    this._globalFitCalibrationScale = 0;
                    this._globalFitCalibrationPageNumber = 0;
                }

                this.traceReaderReveal('startup-global-fit-calibration-failed', {
                    page: normalizedReferencePage,
                    name: String(error?.name ?? 'Error'),
                    message: String(error?.message ?? ''),
                });
            } finally {
                if (!persistGlobalCalibration) {
                    const rootElement = this.$el?.firstElementChild;

                    if (rootElement instanceof HTMLElement) {
                        rootElement.style.removeProperty('--quran-page-type-scale');
                        rootElement.style.removeProperty('--quran-page-leading-multiplier');
                        rootElement.style.removeProperty('--quran-page-gap-multiplier');
                        rootElement.style.removeProperty('--quran-page-surah-header-scale');
                        rootElement.style.removeProperty('--quran-basmallah-bottom-gap-scale');
                    }

                    this.pageScale = 1;
                    this.setCurrentPageScale(1, { forFitting: true });
                }

                this.isCalibrating = false;
                this.pageInput = startupTargetPage;
                this._lastPageInputVisualValue = startupTargetPage;
                this._bypassNextFitCache = true;
            }
        },

        async ensurePersistentStorage() {
            if (typeof navigator === 'undefined' || !navigator.storage) {
                return;
            }

            try {
                this.storage.isPersisted = Boolean(await navigator.storage.persisted());

                if (!this.storage.isPersisted) {
                    this.storage.persistRequested = true;
                    this.storage.isPersisted = Boolean(await navigator.storage.persist());
                }
            } catch (_) {
                this.storage.isPersisted = false;
            }
        },

        supportUnlockMode() {
            return String(this.supportUnlock?.mode ?? 'locked')
                .trim()
                .toLowerCase();
        },

        isSupportUnlockPermanent() {
            return this.supportUnlockMode() === supportUnlockModePermanent;
        },

        isSupportUnlockWeeklyActive(referenceTime = Date.now()) {
            if (this.supportUnlockMode() !== supportUnlockModeWeekly) {
                return false;
            }

            const expiresAt = Number(this.supportUnlock?.expiresAt ?? 0);

            return Number.isFinite(expiresAt) && expiresAt > Math.trunc(Number(referenceTime) || 0);
        },

        isSupportLockActive() {
            if (this.isSupportUnlockPermanent()) {
                return false;
            }

            if (this.isSupportUnlockWeeklyActive()) {
                return false;
            }

            if (this.supportUnlockMode() === supportUnlockModeWeekly) {
                this.syncSupportUnlockState({ persist: true });
            }

            return true;
        },

        scheduleSupportUnlockExpiryTimer() {
            if (this._supportUnlockExpiryTimer !== null) {
                clearTimeout(this._supportUnlockExpiryTimer);
                this._supportUnlockExpiryTimer = null;
            }

            if (!this.isSupportUnlockWeeklyActive()) {
                return;
            }

            const expiresAt = Math.max(0, Math.trunc(Number(this.supportUnlock?.expiresAt ?? 0)));
            const remainingMs = expiresAt - Date.now();

            if (remainingMs <= 0) {
                this.syncSupportUnlockState({ persist: true });
                this.syncSupportLockTargetsUi();

                return;
            }

            this._supportUnlockExpiryTimer = window.setTimeout(
                () => {
                    this._supportUnlockExpiryTimer = null;
                    this.syncSupportUnlockState({ persist: true });
                    this.syncSupportLockTargetsUi();
                },
                Math.max(900, remainingMs),
            );
        },

        syncSupportUnlockState({ persist = true } = {}) {
            const normalized = readSupportUnlockState();

            this.supportUnlock = {
                mode: normalized.mode,
                grantedAt: normalized.granted_at,
                expiresAt: normalized.expires_at,
            };

            if (persist) {
                writeSupportUnlockState(normalized);
            }

            this.scheduleSupportUnlockExpiryTimer();

            return normalized;
        },

        openSupportUnlockModal() {
            const hasMountAction = this.$wire && typeof this.$wire.mountAction === 'function';

            if (hasMountAction) {
                this.$wire.mountAction('supportUnlock');

                return;
            }

            window.dispatchEvent(new CustomEvent('open-support-unlock-modal'));
        },

        async applySupportUnlockDecision(mode = null) {
            const normalizedMode = String(mode ?? '')
                .trim()
                .toLowerCase();

            if (
                normalizedMode !== supportUnlockModePermanent &&
                normalizedMode !== supportUnlockModeWeekly
            ) {
                this.syncSupportUnlockState({ persist: true });
                this.syncSupportLockTargetsUi();

                return;
            }

            const grantedAt = Date.now();
            const persistedState =
                normalizedMode === supportUnlockModePermanent
                    ? writeSupportUnlockState({
                          version: supportUnlockStorageVersion,
                          mode: supportUnlockModePermanent,
                          granted_at: grantedAt,
                          expires_at: null,
                      })
                    : writeSupportUnlockState({
                          version: supportUnlockStorageVersion,
                          mode: supportUnlockModeWeekly,
                          granted_at: grantedAt,
                          expires_at: grantedAt + supportUnlockWeeklyDurationMs,
                      });

            this.supportUnlock = {
                mode: persistedState.mode,
                grantedAt: persistedState.granted_at,
                expiresAt: persistedState.expires_at,
            };

            if (normalizedMode === supportUnlockModePermanent) {
                await this.ensurePersistentStorage();
            }

            this.scheduleSupportUnlockExpiryTimer();
            this.syncSupportLockTargetsUi();
        },

        handleSupportLockTargetInteraction(event) {
            if (!this.isSupportLockActive()) {
                return;
            }

            if (event.type === 'keydown') {
                const pressedKey = String(event.key ?? '');

                if (pressedKey !== 'Enter' && pressedKey !== ' ') {
                    return;
                }
            }

            if (typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            if (typeof event.stopPropagation === 'function') {
                event.stopPropagation();
            }

            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }

            this.openSupportUnlockModal();
        },

        bindSupportLockTarget(targetElement) {
            if (!(targetElement instanceof HTMLElement)) {
                return;
            }

            if (targetElement.dataset.supportLockBound === '1') {
                return;
            }

            if (targetElement.getAttribute('x-on:pointerdown.capture') !== null) {
                return;
            }

            targetElement.dataset.supportLockBound = '1';

            const onTargetInteraction = (event) => {
                this.handleSupportLockTargetInteraction(event);
            };

            targetElement.addEventListener('pointerdown', onTargetInteraction, true);
            targetElement.addEventListener('keydown', onTargetInteraction, true);
        },

        ensureSupportLockBadge(targetElement) {
            if (!(targetElement instanceof HTMLElement)) {
                return null;
            }

            const existingBadge = targetElement.querySelector('[data-support-lock-badge]');

            if (existingBadge instanceof HTMLElement) {
                return existingBadge;
            }

            const badgeElement = document.createElement('span');
            badgeElement.setAttribute('data-support-lock-badge', '1');
            badgeElement.className = 'quran-support-lock-badge';
            badgeElement.innerHTML = `
                <span class="quran-support-lock-badge__icon quran-support-lock-badge__icon--locked">${supportLockClosedOutlineIconSvg}</span>
            `;
            targetElement.appendChild(badgeElement);

            return badgeElement;
        },

        removeSupportLockBadge(targetElement) {
            if (!(targetElement instanceof HTMLElement)) {
                return;
            }

            const existingBadge = targetElement.querySelector('[data-support-lock-badge]');

            if (existingBadge instanceof HTMLElement) {
                existingBadge.remove();
            }
        },

        queueSupportLockTargetsUiSync() {
            if (typeof window === 'undefined') {
                return;
            }

            if (this._supportLockTargetsSyncRaf !== null) {
                return;
            }

            this._supportLockTargetsSyncRaf = window.requestAnimationFrame(() => {
                this._supportLockTargetsSyncRaf = null;
                this.syncSupportLockTargetsUi();
            });
        },

        syncSupportLockTargetsUi() {
            if (typeof document === 'undefined') {
                return;
            }

            const isLocked = this.isSupportLockActive();
            const targets = Array.from(document.querySelectorAll('[data-support-lock-target]'));

            targets.forEach((targetElement) => {
                if (!(targetElement instanceof HTMLElement)) {
                    return;
                }

                this.bindSupportLockTarget(targetElement);
                targetElement.classList.add('quran-support-lock-target');
                targetElement.classList.toggle('quran-support-lock-target--locked', isLocked);

                if (isLocked) {
                    targetElement.setAttribute('aria-disabled', 'true');
                    this.ensureSupportLockBadge(targetElement);
                } else {
                    targetElement.removeAttribute('aria-disabled');
                    targetElement.classList.remove('quran-support-lock-target--unlocked');
                    this.removeSupportLockBadge(targetElement);
                }

                const isNaturallyFocusable = [
                    'A',
                    'BUTTON',
                    'INPUT',
                    'SELECT',
                    'TEXTAREA',
                ].includes(targetElement.tagName);

                if (isLocked && !isNaturallyFocusable && !targetElement.hasAttribute('tabindex')) {
                    targetElement.setAttribute('tabindex', '0');
                    targetElement.dataset.supportLockTabInjected = '1';
                }

                if (!isLocked && targetElement.dataset.supportLockTabInjected === '1') {
                    targetElement.removeAttribute('tabindex');
                    delete targetElement.dataset.supportLockTabInjected;
                }
            });
        },

        persistLastPageNumber(pageNumber = this.pageNumber, { force = false } = {}) {
            if (this.wirdModeActive && !force) {
                return;
            }

            writeLastPageNumber(pageNumber);
        },

        normalizeIntegerFlag(value, fallback = 0, { min = 0, max = Number.MAX_SAFE_INTEGER } = {}) {
            const numericValue = Number(value);
            const fallbackValue = Number.isFinite(Number(fallback)) ? Number(fallback) : 0;
            const normalizedValue = Number.isFinite(numericValue)
                ? Math.trunc(numericValue)
                : fallbackValue;

            return Math.max(min, Math.min(max, normalizedValue));
        },

        resolveReaderMaxPage() {
            const currentMax = Number(this.maxPage ?? 0);
            const initialMax = Number(this.initialPayload?.maxPage ?? 0);

            if (Number.isFinite(currentMax) && currentMax > 0) {
                return Math.trunc(currentMax);
            }

            if (Number.isFinite(initialMax) && initialMax > 0) {
                return Math.trunc(initialMax);
            }

            return 604;
        },

        normalizeWirdFrequencyMode(value, fallback = wirdFrequencyModeMonthly) {
            return this.normalizeIntegerFlag(value, fallback, {
                min: wirdFrequencyModeMonthly,
                max: wirdFrequencyModeDaily,
            });
        },

        normalizeWirdDateKey(value, fallback = currentDateKey()) {
            const normalized = String(value ?? '').trim();

            if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
                return normalized;
            }

            return fallback;
        },

        resolveDaysInMonthFromDateKey(dateKey = currentDateKey()) {
            const normalizedDateKey = this.normalizeWirdDateKey(dateKey);
            const year = Number(normalizedDateKey.slice(0, 4));
            const month = Number(normalizedDateKey.slice(5, 7));

            if (!Number.isFinite(year) || !Number.isFinite(month) || month < 1 || month > 12) {
                return 30;
            }

            return new Date(year, month, 0).getDate();
        },

        resolveWirdKhatmatTargetMax({ frequencyMode = this.wirdFrequencyMode } = {}) {
            const normalizedFrequencyMode = this.normalizeWirdFrequencyMode(
                frequencyMode,
                wirdFrequencyModeMonthly,
            );

            if (normalizedFrequencyMode === wirdFrequencyModeDaily) {
                return wirdDailyKhatmatTargetMax;
            }

            return wirdMonthlyKhatmatTargetMax;
        },

        normalizeWirdKhatmatTarget(
            value,
            fallback = 1,
            { frequencyMode = this.wirdFrequencyMode } = {},
        ) {
            return this.normalizeIntegerFlag(value, fallback, {
                min: wirdKhatmatTargetMin,
                max: this.resolveWirdKhatmatTargetMax({
                    frequencyMode,
                }),
            });
        },

        resolveWirdRequiredPages({
            dateKey = currentDateKey(),
            frequencyMode = this.wirdFrequencyMode,
            khatmatTarget = this.wirdKhatmatTarget,
        } = {}) {
            const maxPage = this.resolveReaderMaxPage();
            const normalizedFrequencyMode = this.normalizeWirdFrequencyMode(
                frequencyMode,
                wirdFrequencyModeMonthly,
            );
            const normalizedDateKey = this.normalizeWirdDateKey(dateKey);
            const normalizedKhatmatTarget = this.normalizeWirdKhatmatTarget(khatmatTarget, 1, {
                frequencyMode: normalizedFrequencyMode,
            });

            if (normalizedFrequencyMode === wirdFrequencyModeDaily) {
                return maxPage * normalizedKhatmatTarget;
            }

            const daysInMonth = Math.max(1, this.resolveDaysInMonthFromDateKey(normalizedDateKey));

            return Math.max(1, Math.ceil((maxPage * normalizedKhatmatTarget) / daysInMonth));
        },

        normalizeWirdState(rawState = null) {
            const maxPage = this.resolveReaderMaxPage();
            const maxRequiredPages = maxPage * wirdDailyKhatmatTargetMax;
            const stateInput =
                rawState && typeof rawState === 'object' && !Array.isArray(rawState)
                    ? rawState
                    : {};
            const normalizedState = {
                version: wirdProgressStorageVersion,
                nextAbsolutePage: Math.max(
                    1,
                    this.normalizeIntegerFlag(stateInput?.nextAbsolutePage, 1, {
                        min: 1,
                    }),
                ),
                dayRecords: {},
            };
            const now = Date.now();
            const cutoffMs = now - wirdRecordRetentionDays * 24 * 60 * 60 * 1000;
            const dayRecords =
                stateInput?.dayRecords &&
                typeof stateInput.dayRecords === 'object' &&
                !Array.isArray(stateInput.dayRecords)
                    ? stateInput.dayRecords
                    : {};

            Object.entries(dayRecords).forEach(([rawDateKey, rawRecord]) => {
                const dateKey = this.normalizeWirdDateKey(rawDateKey, '');

                if (dateKey === '') {
                    return;
                }

                const parsedRecord =
                    rawRecord && typeof rawRecord === 'object' && !Array.isArray(rawRecord)
                        ? rawRecord
                        : {};
                const updatedAt = this.normalizeIntegerFlag(parsedRecord?.updatedAt, now, {
                    min: 0,
                });

                if (updatedAt < cutoffMs) {
                    return;
                }

                const startAbsolutePage = Math.max(
                    1,
                    this.normalizeIntegerFlag(
                        parsedRecord?.startAbsolutePage,
                        normalizedState.nextAbsolutePage,
                        {
                            min: 1,
                        },
                    ),
                );
                const requiredPages = this.normalizeIntegerFlag(parsedRecord?.requiredPages, 1, {
                    min: 1,
                    max: maxRequiredPages,
                });
                const maxStep = Math.max(0, requiredPages - 1);
                const currentStep = this.normalizeIntegerFlag(parsedRecord?.currentStep, 0, {
                    min: 0,
                    max: maxStep,
                });
                const progressStep = this.normalizeIntegerFlag(
                    parsedRecord?.progressStep,
                    currentStep,
                    {
                        min: 0,
                        max: maxStep,
                    },
                );
                const completed =
                    Boolean(parsedRecord?.completed) ||
                    (maxStep > 0 && (currentStep >= maxStep || progressStep >= maxStep));

                normalizedState.dayRecords[dateKey] = {
                    startAbsolutePage,
                    requiredPages,
                    currentStep,
                    progressStep: completed ? maxStep : Math.max(currentStep, progressStep),
                    completed,
                    signature: String(parsedRecord?.signature ?? '').trim(),
                    createdAt: this.normalizeIntegerFlag(parsedRecord?.createdAt, updatedAt, {
                        min: 0,
                    }),
                    updatedAt,
                };
            });

            return normalizedState;
        },

        hydrateWirdState() {
            this.syncWirdStorageState({
                force: true,
                clearDailyRecord: true,
            });

            return this.wirdState;
        },

        persistWirdState() {
            if (!this.wirdState || typeof this.wirdState !== 'object') {
                this.wirdState = this.normalizeWirdState(null);
            }

            writeLocalStorage(wirdProgressStorageKey, this.wirdState);
            this._wirdStateStorageRawSnapshot = readLocalStorageRaw(wirdProgressStorageKey);
        },

        syncWirdStorageState({ force = false, clearDailyRecord = false } = {}) {
            const progressRaw = readLocalStorageRaw(wirdProgressStorageKey);
            const shouldSyncProgress = force || progressRaw !== this._wirdStateStorageRawSnapshot;

            if (shouldSyncProgress) {
                const parsedState =
                    progressRaw === null ? null : readLocalStorage(wirdProgressStorageKey, null);

                this.wirdState = this.normalizeWirdState(parsedState);
                this._wirdStateStorageRawSnapshot = progressRaw;

                if (clearDailyRecord) {
                    this.wirdDailyRecord = null;
                }
            }

            const dayOffsetRaw = readLocalStorageRaw(wirdDayOffsetStorageKey);
            const shouldSyncDayOffset =
                force || dayOffsetRaw !== this._wirdDayOffsetStorageRawSnapshot;

            if (shouldSyncDayOffset) {
                this.wirdDayOffsetDays = normalizeDayOffsetDays(
                    dayOffsetRaw === null ? 0 : readLocalStorage(wirdDayOffsetStorageKey, 0),
                    0,
                );
                this._wirdDayOffsetStorageRawSnapshot = dayOffsetRaw;

                if (clearDailyRecord) {
                    this.wirdDailyRecord = null;
                }
            }
        },

        absolutePageToPageNumber(absolutePage) {
            const maxPage = this.resolveReaderMaxPage();
            const normalizedAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(absolutePage, 1, { min: 1 }),
            );

            return ((normalizedAbsolutePage - 1) % maxPage) + 1;
        },
    };
};
