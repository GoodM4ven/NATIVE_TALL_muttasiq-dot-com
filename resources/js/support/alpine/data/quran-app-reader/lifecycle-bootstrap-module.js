export const createLifecycleBootstrapModule = (deps) => {
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
        init() {
            this.applyPayload(this.initialPayload, {
                setPageNumber: true,
                persistPageNumber: false,
            });
            const resolveControlPanelSettings =
                typeof this.resolveControlPanelSettingsWithUserOverrides === 'function'
                    ? this.resolveControlPanelSettingsWithUserOverrides.bind(this)
                    : (defaults = {}) => defaults;
            this.applyControlPanelSettings(resolveControlPanelSettings(this.initialSettings));
            this.quranPageScaleAdjustValue = this.readPersistedPageScaleAdjustValue();
            this.quranPageGapAdjustValue = this.readPersistedPageGapAdjustValue();
            this.quranPageYOffsetAdjustValue = this.readPersistedPageYOffsetAdjustValue();
            this.syncSupportUnlockState({ persist: false });
            this.buildSurahDirectory(
                Array.isArray(this.initialPayload.surahDirectory) &&
                    this.initialPayload.surahDirectory.length > 0
                    ? this.initialPayload.surahDirectory
                    : this.search.surahDirectory,
            );
            this.refreshSurahTriggerCaption(false);
            this.refreshMobileEdgeCaptions(false);
            this.syncSearchActiveSurahNumber();
            this.navigationHistory = readNavigationHistory();
            this.syncHistoryTagDrafts();
            this.bookmarks = readBookmarks();
            this.syncBookmarkTagDrafts();
            this.dispatchManagerModalsVisibilityState();
            this._onQrDebugLogsToggle = (event) => {
                const details =
                    event?.detail && typeof event.detail === 'object' ? event.detail : {};

                if (Object.prototype.hasOwnProperty.call(details, 'enabled')) {
                    this.isQrDebugLoggingEnabled = this.normalizeBooleanFlag(
                        details.enabled,
                        false,
                    );

                    return;
                }

                this.isQrDebugLoggingEnabled = !this.isQrDebugLoggingEnabled;
            };
            window.addEventListener(quranReaderDebugLogsToggleEventName, this._onQrDebugLogsToggle);

            const storedLastPageNumber = readLastPageNumber();
            const restoredPage = clampPage(
                storedLastPageNumber ?? this.pageNumber,
                this.maxPage || this.initialPayload.maxPage,
            );
            const shouldRestoreSavedPage =
                restoredPage !== this.initialPayload.pageNumber && this.ready;

            this.pageInput = restoredPage;
            this._lastPageInputVisualValue = restoredPage;
            this._startupTargetPageNumber = restoredPage;
            writeLastPageNumber(restoredPage);

            if (!shouldRestoreSavedPage) {
                this.pageNumber = restoredPage;
            } else {
                this.isFittingPage = true;
                const startupRestorePromise = this.goToPage(restoredPage, {
                    direction: restoredPage > this.initialPayload.pageNumber ? 'next' : 'prev',
                    animate: false,
                    forceRefit: true,
                    source: 'startup-restore',
                });
                this._startupRestoreInFlight = startupRestorePromise;
                void startupRestorePromise.finally(() => {
                    if (this._startupRestoreInFlight === startupRestorePromise) {
                        this._startupRestoreInFlight = null;
                    }
                });
            }

            this.ensureWirdDailyRecord();

            this._onWindowStorage = (event) => {
                const storageKey = String(event?.key ?? '');

                if (
                    storageKey !== wirdProgressStorageKey &&
                    storageKey !== wirdDayOffsetStorageKey
                ) {
                    return;
                }

                this.syncWirdStorageState({
                    force: true,
                    clearDailyRecord: true,
                });
                this.ensureWirdDailyRecord({
                    forceRebuild: storageKey === wirdDayOffsetStorageKey,
                });
            };
            window.addEventListener('storage', this._onWindowStorage);

            this._onWindowViewportChange = () => {
                this.handleViewportChange();
            };
            window.addEventListener('resize', this._onWindowViewportChange, { passive: true });
            window.addEventListener('orientationchange', this._onWindowViewportChange, {
                passive: true,
            });

            this._onVisualViewportChange = () => {
                this.handleViewportChange();
            };

            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', this._onVisualViewportChange, {
                    passive: true,
                });
            }

            this._onWindowScroll = () => {
                if (!this.isCalibrating) {
                    return;
                }

                this.syncCalibrationHudPosition();
            };
            window.addEventListener('scroll', this._onWindowScroll, { passive: true });

            this._onSwitchView = (event) => {
                const to = String(event?.detail?.to ?? '');
                const quranViews = ['quran-app-tilawa', 'quran-app-hifth', 'quran-app-tadabbur'];
                const isGoingToQuranReader = quranViews.includes(to);

                if (!isGoingToQuranReader) {
                    this.isFontScaleOverlayVisible = false;
                    this.isReaderChromeVisible = false;
                    this.syncReaderChromeDocumentClass({ forceInactive: true });
                    this._immersiveEntryAwaitingFirstReveal = true;
                    this.clearDeferredBootstrapCheckTimer();
                    this._deferredBootstrapCheckAttempts = 0;

                    if (this.hasRenderablePage()) {
                        this.isFittingPage = true;
                        this.clearLayoutTimers();
                    }
                    this.recoverStaleModalLifecycleState();
                    this.pruneModalLifecycleSuppression();
                    this.clearPendingPostModalTargetFit();

                    this.resetSwipeState();
                    return;
                }

                this._lastQuranReaderView = to;
                this._immersiveEntryAwaitingFirstReveal = true;
                this.isReaderChromeVisible = false;
                this.isFittingPage = true;
                this.clearLayoutTimers();
                this.scheduleReaderPanelLayoutRefresh();
                this.scheduleDeferredBootstrapCheck();

                [80, 220, 420].forEach((delayMs) => {
                    window.setTimeout(() => this.scheduleReaderPanelLayoutRefresh(), delayMs);
                });

                if (to !== 'quran-app-tadabbur') {
                    this.clearActivationIndexes();
                }

                if (this._bootstrapDeferred) {
                    this._bootstrapDeferred = false;
                    this.qrDebugLog('[QR:switch-view] deferred bootstrap triggered for:', to);
                    this.$nextTick(() => {
                        this.qrDebugLog(
                            '[QR:switch-view] running deferred bootstrap, visible:',
                            this.isReaderElementVisible(),
                        );
                        this.bootstrap();
                    });

                    return;
                }

                this.scheduleLayout({ revealDelayMs: 200 });
            };

            window.addEventListener('switch-view', this._onSwitchView);
            this._onReaderGoGate = () => {
                this.isReaderChromeVisible = false;
                this.isFontScaleOverlayVisible = false;
                this.syncReaderChromeDocumentClass({ forceInactive: true });
            };
            window.addEventListener('quran-reader-go-gate', this._onReaderGoGate);
            this.scheduleReaderPanelLayoutRefresh();
            this._onWirdSimulateDay = (event) => {
                const deltaDays = normalizeDayOffsetDays(event?.detail?.days ?? 1, 1);
                const nextOffset = normalizeDayOffsetDays(this.wirdDayOffsetDays + deltaDays, 0);

                this.wirdDayOffsetDays = nextOffset;
                writeWirdDayOffsetDays(nextOffset);
                this.ensureWirdDailyRecord();
            };
            window.addEventListener('quran-wird-simulate-day', this._onWirdSimulateDay);
            this._onWirdCongratsPreview = (event) => {
                this.handleWirdCompletionPreviewEvent(event?.detail ?? {});
            };
            window.addEventListener('quran-wird-congrats-preview', this._onWirdCongratsPreview);
            this._onFontScaleToggle = () => {
                this.toggleFontScaleOverlay();
            };
            window.addEventListener('quran-reader-font-scale-toggle', this._onFontScaleToggle);
            this._onHistoryManagerRequestSync = () => {
                this.queueHistoryManagerTableSync();
            };
            window.addEventListener(
                'quran-history-manager-request-sync',
                this._onHistoryManagerRequestSync,
            );
            this._onBookmarksManagerRequestSync = () => {
                this.queueBookmarksManagerTableSync();
            };
            window.addEventListener(
                'quran-bookmarks-manager-request-sync',
                this._onBookmarksManagerRequestSync,
            );
            ensureSupportLockLivewireMorphBridge();
            this._onSupportLockLivewireMorphed = () => {
                this.queueSupportLockTargetsUiSync();
            };
            window.addEventListener(
                supportLockLivewireMorphedEventName,
                this._onSupportLockLivewireMorphed,
            );

            if (this.$wire?.$hook) {
                this._stopLivewireMorphedHook = this.$wire.$hook('morphed', () => {
                    if (!this.ready || this.mushafLines.length === 0) {
                        return;
                    }

                    if (this._searchNavigationInFlight) {
                        return;
                    }

                    this.$nextTick(() => {
                        this.registerNativeInputListeners();
                        this.initializeLayoutObservers();
                        this.queueSupportLockTargetsUiSync();
                    });
                    this.scheduleLayout({ revealDelayMs: 170 });
                });
            }

            this.$nextTick(() => {
                this.registerNativeInputListeners();
                this.initializeLayoutObservers();
                this.queueSupportLockTargetsUiSync();
                this.syncCalibrationHudPosition();
            });
            this._stopIsCalibratingWatcher = this.$watch('isCalibrating', (isCalibrating) => {
                this.syncReaderChromeDocumentClass();

                if (isCalibrating) {
                    this.$nextTick(() => {
                        this.syncCalibrationHudPosition();
                    });
                }
            });
            this._stopSearchQueryWatcher = this.$watch('search.query', () => {
                if (this._searchNavigationInFlight) {
                    return;
                }

                this.queueSearchResultsUpdate();
            });
            this.$nextTick(() => {
                const visible = this.isReaderElementVisible();
                this.qrDebugLog(
                    '[QR:init] isReaderElementVisible:',
                    visible,
                    'ready:',
                    this.ready,
                    'maxPage:',
                    this.maxPage,
                    'mushafLines:',
                    this.mushafLines.length,
                );
                if (visible) {
                    this.bootstrap();
                } else {
                    this._bootstrapDeferred = true;
                    this.scheduleDeferredBootstrapCheck();
                }
            });
        },

        clearDeferredBootstrapCheckTimer() {
            if (this._deferredBootstrapCheckTimer === null) {
                return;
            }

            clearTimeout(this._deferredBootstrapCheckTimer);
            this._deferredBootstrapCheckTimer = null;
        },

        maybeBootstrapDeferred() {
            if (!this._bootstrapDeferred) {
                return true;
            }

            if (!this.isAnyQuranReaderViewOpen() || !this.isReaderElementVisible()) {
                return false;
            }

            this._bootstrapDeferred = false;
            this.clearDeferredBootstrapCheckTimer();
            this.qrDebugLog('[QR:deferred-bootstrap] recovered without switch-view');
            this.$nextTick(() => {
                this.bootstrap();
            });

            return true;
        },

        scheduleDeferredBootstrapCheck() {
            if (!this._bootstrapDeferred) {
                this.clearDeferredBootstrapCheckTimer();
                this._deferredBootstrapCheckAttempts = 0;

                return;
            }

            if (this._deferredBootstrapCheckTimer !== null) {
                return;
            }

            const runCheck = () => {
                this._deferredBootstrapCheckTimer = null;

                if (this.maybeBootstrapDeferred()) {
                    this._deferredBootstrapCheckAttempts = 0;

                    return;
                }

                this._deferredBootstrapCheckAttempts += 1;

                if (this._deferredBootstrapCheckAttempts > 18) {
                    this._deferredBootstrapCheckAttempts = 0;

                    return;
                }

                const delayMs = Math.min(900, 120 + this._deferredBootstrapCheckAttempts * 34);
                this._deferredBootstrapCheckTimer = window.setTimeout(runCheck, delayMs);
            };

            this._deferredBootstrapCheckTimer = window.setTimeout(runCheck, 120);
        },

        syncCalibrationHudPosition() {
            const readerPanelElement = this.$refs.readerPanel;

            if (!(readerPanelElement instanceof HTMLElement)) {
                return;
            }

            const panelRect = readerPanelElement.getBoundingClientRect();
            this.calibrationHudLeft = Math.round(panelRect.left + panelRect.width * 0.5);
            this.calibrationHudTop = Math.round(panelRect.top + panelRect.height * 0.5);
        },

        calibrationHudStyle() {
            const fallbackLeft =
                typeof window !== 'undefined' ? Math.round(window.innerWidth * 0.5) : 0;
            const fallbackTop =
                typeof window !== 'undefined' ? Math.round(window.innerHeight * 0.5) : 0;
            const left = Math.max(
                0,
                Number.isFinite(this.calibrationHudLeft) ? this.calibrationHudLeft : fallbackLeft,
            );
            const top = Math.max(
                0,
                Number.isFinite(this.calibrationHudTop) ? this.calibrationHudTop : fallbackTop,
            );

            return `left:${left}px;top:${top}px;`;
        },

        isReaderElementVisible() {
            const el = this.$el;

            if (!el) {
                return false;
            }

            return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
        },

        registerNativeInputListeners() {
            this.unregisterNativeInputListeners();

            const readerPanel = this.$refs.readerPanel;

            this._onWindowKeydown = (event) => {
                const key = String(event?.key ?? '');

                if (key === 'ArrowLeft') {
                    void this.onGlobalArrowNavigate('left', event);

                    return;
                }

                if (key === 'ArrowRight') {
                    void this.onGlobalArrowNavigate('right', event);
                }
            };
            window.addEventListener('keydown', this._onWindowKeydown, true);
            this._onWindowNativeVolumeButton = (event) => {
                void this.handleNativeVolumeButton(event?.detail?.direction ?? null, event);
            };
            window.addEventListener(
                'quran-native-volume-button',
                this._onWindowNativeVolumeButton,
                true,
            );

            if (!(readerPanel instanceof Element)) {
                this.syncNativeVolumeNavigation();

                return;
            }

            this._onPanelPointerDown = (event) => {
                this.onSwipeStart(event);
            };
            this._onPanelPointerMove = (event) => {
                void this.onSwipeMove(event);
            };
            this._onPanelPointerUp = (event) => {
                if (this.wordPress?.active) {
                    this.onWordPointerUp(event);

                    return;
                }

                void this.onSwipeEnd(event);
            };
            this._onPanelPointerCancel = () => {
                if (this.wordPress?.active) {
                    this.onWordPointerCancel();
                }

                this.onSwipeCancel();
            };
            this._onWindowPointerMove = (event) => {
                if (
                    this.usesMobileDoubleTapCopyMode() &&
                    this.wordPress?.active &&
                    this.wordPress?.isSecondTap
                ) {
                    if (event && typeof event === 'object') {
                        event.__quranReaderInputHandled = true;
                    }

                    this.onWordPointerMove(event);

                    return;
                }

                void this.onSwipeMove(event);
            };
            this._onWindowPointerUp = (event) => {
                if (this.wordPress?.active) {
                    this.onWordPointerUp(event);

                    return;
                }

                void this.onSwipeEnd(event);
            };
            this._onWindowPointerCancel = () => {
                if (this.wordPress?.active) {
                    this.onWordPointerCancel();
                }

                this.onSwipeCancel();
            };
            this._onPanelTouchStart = (event) => {
                this.onSwipeStart(event);
            };
            this._onPanelTouchMove = (event) => {
                void this.onSwipeMove(event);
            };
            this._onPanelTouchEnd = (event) => {
                if (this.wordPress?.active) {
                    this.onWordPointerUp(event);

                    return;
                }

                void this.onSwipeEnd(event);
            };
            this._onPanelTouchCancel = () => {
                if (this.wordPress?.active) {
                    this.onWordPointerCancel();
                }

                this.onSwipeCancel();
            };
            this._onWindowTouchMove = (event) => {
                if (
                    this.usesMobileDoubleTapCopyMode() &&
                    this.wordPress?.active &&
                    this.wordPress?.isSecondTap
                ) {
                    if (event && typeof event === 'object') {
                        event.__quranReaderInputHandled = true;
                    }

                    this.onWordPointerMove(event);

                    return;
                }

                void this.onSwipeMove(event);
            };
            this._onWindowTouchEnd = (event) => {
                if (this.wordPress?.active) {
                    this.onWordPointerUp(event);

                    return;
                }

                void this.onSwipeEnd(event);
            };
            this._onWindowTouchCancel = () => {
                if (this.wordPress?.active) {
                    this.onWordPointerCancel();
                }

                this.onSwipeCancel();
            };

            readerPanel.addEventListener('pointerdown', this._onPanelPointerDown, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('pointermove', this._onPanelPointerMove, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('pointerup', this._onPanelPointerUp, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('pointercancel', this._onPanelPointerCancel, {
                passive: true,
                capture: true,
            });
            window.addEventListener('pointermove', this._onWindowPointerMove, {
                passive: true,
                capture: true,
            });
            window.addEventListener('pointerup', this._onWindowPointerUp, {
                passive: true,
                capture: true,
            });
            window.addEventListener('pointercancel', this._onWindowPointerCancel, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('touchstart', this._onPanelTouchStart, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('touchmove', this._onPanelTouchMove, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('touchend', this._onPanelTouchEnd, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('touchcancel', this._onPanelTouchCancel, {
                passive: true,
                capture: true,
            });
            window.addEventListener('touchmove', this._onWindowTouchMove, {
                passive: true,
                capture: true,
            });
            window.addEventListener('touchend', this._onWindowTouchEnd, {
                passive: true,
                capture: true,
            });
            window.addEventListener('touchcancel', this._onWindowTouchCancel, {
                passive: true,
                capture: true,
            });
            this.syncNativeVolumeNavigation();
        },

        unregisterNativeInputListeners() {
            if (this._onWindowKeydown) {
                window.removeEventListener('keydown', this._onWindowKeydown, true);
                this._onWindowKeydown = null;
            }

            const readerPanel = this.$refs.readerPanel;

            if (readerPanel instanceof Element && this._onPanelPointerDown) {
                readerPanel.removeEventListener('pointerdown', this._onPanelPointerDown, true);
            }

            if (readerPanel instanceof Element && this._onPanelPointerMove) {
                readerPanel.removeEventListener('pointermove', this._onPanelPointerMove, true);
            }

            if (readerPanel instanceof Element && this._onPanelPointerUp) {
                readerPanel.removeEventListener('pointerup', this._onPanelPointerUp, true);
            }

            if (readerPanel instanceof Element && this._onPanelPointerCancel) {
                readerPanel.removeEventListener('pointercancel', this._onPanelPointerCancel, true);
            }

            if (this._onWindowPointerMove) {
                window.removeEventListener('pointermove', this._onWindowPointerMove, true);
            }

            if (this._onWindowPointerUp) {
                window.removeEventListener('pointerup', this._onWindowPointerUp, true);
            }

            if (this._onWindowPointerCancel) {
                window.removeEventListener('pointercancel', this._onWindowPointerCancel, true);
            }

            if (readerPanel instanceof Element && this._onPanelTouchStart) {
                readerPanel.removeEventListener('touchstart', this._onPanelTouchStart, true);
            }

            if (readerPanel instanceof Element && this._onPanelTouchMove) {
                readerPanel.removeEventListener('touchmove', this._onPanelTouchMove, true);
            }

            if (readerPanel instanceof Element && this._onPanelTouchEnd) {
                readerPanel.removeEventListener('touchend', this._onPanelTouchEnd, true);
            }

            if (readerPanel instanceof Element && this._onPanelTouchCancel) {
                readerPanel.removeEventListener('touchcancel', this._onPanelTouchCancel, true);
            }

            if (this._onWindowTouchMove) {
                window.removeEventListener('touchmove', this._onWindowTouchMove, true);
            }

            if (this._onWindowTouchEnd) {
                window.removeEventListener('touchend', this._onWindowTouchEnd, true);
            }

            if (this._onWindowTouchCancel) {
                window.removeEventListener('touchcancel', this._onWindowTouchCancel, true);
            }

            if (this._onWindowNativeVolumeButton) {
                window.removeEventListener(
                    'quran-native-volume-button',
                    this._onWindowNativeVolumeButton,
                    true,
                );
                this._onWindowNativeVolumeButton = null;
            }

            this._onPanelPointerDown = null;
            this._onPanelPointerMove = null;
            this._onPanelPointerUp = null;
            this._onPanelPointerCancel = null;
            this._onWindowPointerMove = null;
            this._onWindowPointerUp = null;
            this._onWindowPointerCancel = null;
            this._onPanelTouchStart = null;
            this._onPanelTouchMove = null;
            this._onPanelTouchEnd = null;
            this._onPanelTouchCancel = null;
            this._onWindowTouchMove = null;
            this._onWindowTouchEnd = null;
            this._onWindowTouchCancel = null;
            this.setAndroidVolumeNavigationEnabled(false);
        },

        isReaderPanelVisible() {
            const readerPanel = this.$refs?.readerPanel;

            return readerPanel instanceof Element && readerPanel.offsetParent !== null;
        },

        canUseNativeVolumeButtonNavigation() {
            return (
                this.nativeRuntime &&
                this.ready &&
                this.doesUseVolumeButtonsNavigation &&
                this.isReaderPanelVisible()
            );
        },

        syncNativeVolumeNavigation() {
            this.$nextTick(() => {
                this.setAndroidVolumeNavigationEnabled(this.canUseNativeVolumeButtonNavigation());
            });
        },

        setAndroidVolumeNavigationEnabled(enabled) {
            if (!this.nativeRuntime || !window.AndroidBridge) {
                return;
            }

            const normalizedEnabled = Boolean(enabled);

            if (typeof window.AndroidBridge.setQuranVolumeNavigationEnabled === 'function') {
                window.AndroidBridge.setQuranVolumeNavigationEnabled(normalizedEnabled);
            }
        },

        async handleNativeVolumeButton(direction, event = null) {
            if (!this.canUseNativeVolumeButtonNavigation()) {
                return;
            }

            const normalizedDirection = String(direction ?? '')
                .trim()
                .toLowerCase();

            if (normalizedDirection === 'next') {
                await this.goNextFromChevron('hardware-volume');

                return;
            }

            if (normalizedDirection === 'previous' || normalizedDirection === 'prev') {
                await this.goPreviousFromChevron('hardware-volume');
            }
        },

        async clearDeferredPreparationCaches() {
            this._pagePayloadByPage.clear();
            this._searchIndexPromise = null;
            this._searchRequestSerial += 1;

            if (typeof window !== 'undefined' && typeof window.caches !== 'undefined') {
                await Promise.allSettled([
                    window.caches.delete(this.cacheNames.pages),
                    window.caches.delete(this.cacheNames.search),
                ]);
            }
        },

        async callQuranPreparationMethod(methodName) {
            if (this.$wire && typeof this.$wire.call === 'function') {
                return await this.$wire.call(methodName);
            }

            const fallbackMethod = this.$wire?.[methodName];

            if (typeof fallbackMethod === 'function') {
                return await fallbackMethod.call(this.$wire);
            }

            throw new Error('Unable to communicate with the Quran reader Livewire instance.');
        },

        async requestNativeQuranPreparation() {
            if (this._quranPreparationRequestPromise) {
                return await this._quranPreparationRequestPromise;
            }

            this._quranPreparationRequestPromise = (async () => {
                return await this.callQuranPreparationMethod('prepareQuranData');
            })();

            try {
                return await this._quranPreparationRequestPromise;
            } finally {
                this._quranPreparationRequestPromise = null;
            }
        },

        async readNativeQuranPreparationStatus() {
            return await this.callQuranPreparationMethod('quranPreparationStatus');
        },

        emitNativeQuranPreparationProgress(result = {}) {
            const downloadedBytes = Number(result?.downloadedBytes ?? NaN);
            const totalBytes = Number(result?.totalBytes ?? NaN);
            const rawProgressPercent = Number(result?.progressPercent ?? NaN);
            const fallbackProgressPercent =
                Number.isFinite(downloadedBytes) && Number.isFinite(totalBytes) && totalBytes > 0
                    ? (downloadedBytes / totalBytes) * 100
                    : NaN;
            const resolvedProgressPercent = Number.isFinite(rawProgressPercent)
                ? rawProgressPercent
                : fallbackProgressPercent;
            const progressPercent = Number.isFinite(resolvedProgressPercent)
                ? Math.max(0, Math.min(100, Math.trunc(resolvedProgressPercent)))
                : null;

            window.dispatchEvent(
                new CustomEvent('quran-bootstrap-progress', {
                    detail: {
                        state: String(result?.state ?? ''),
                        message: String(result?.message ?? '').trim() || null,
                        progressPercent,
                        downloadedBytes: Number.isFinite(downloadedBytes)
                            ? Math.max(0, Math.trunc(downloadedBytes))
                            : null,
                        totalBytes: Number.isFinite(totalBytes)
                            ? Math.max(0, Math.trunc(totalBytes))
                            : null,
                    },
                }),
            );
        },

        async syncPreparedQuranPayload(payload) {
            await this.clearDeferredPreparationCaches();
            this.initialPayload = normalizePayload(payload);
            this.applyPayload(payload, {
                setPageNumber: true,
                persistPageNumber: true,
            });
            this.refreshSurahTriggerCaption(false);
            this.refreshMobileEdgeCaptions(false);
            this.syncSearchActiveSurahNumber();
            this.$nextTick(() => {
                this.registerNativeInputListeners();
                this.initializeLayoutObservers();
                this.queueSupportLockTargetsUiSync();
                this.syncNativeVolumeNavigation();
            });
        },

        async queueNativeQuranPreparationInBackground() {
            if (!this.nativeRuntime || this.ready) {
                return;
            }

            try {
                await this.requestNativeQuranPreparation();
            } catch (_) {
                //
            }
        },

        async waitForNativeQuranPreparation() {
            const pollingIntervalMs = 350;
            const maxAttempts = Math.max(120, Math.ceil(180000 / pollingIntervalMs));

            for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
                await wait(pollingIntervalMs);

                const result = await this.readNativeQuranPreparationStatus();

                this.emitNativeQuranPreparationProgress(result);

                if (result?.ready && result?.payload) {
                    return result;
                }

                if (String(result?.state ?? '') === 'failed') {
                    throw new Error(
                        result?.message ?? 'تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.',
                    );
                }
            }

            throw new Error('تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.');
        },

        async prepareQuranFromMainMenu(detail = {}) {
            const openGateOnSuccess = detail?.openGateOnSuccess !== false;

            if (!this.nativeRuntime || this.ready) {
                window.dispatchEvent(
                    new CustomEvent('quran-bootstrap-finished', {
                        detail: {
                            openGateOnSuccess,
                        },
                    }),
                );

                return;
            }

            if (this._quranPreparationInFlight) {
                await this._quranPreparationInFlight;

                return;
            }

            window.dispatchEvent(
                new CustomEvent('quran-bootstrap-started', {
                    detail: {
                        openGateOnSuccess,
                    },
                }),
            );

            this._quranPreparationInFlight = (async () => {
                try {
                    let result = await this.requestNativeQuranPreparation();

                    if (!result?.ready || !result?.payload) {
                        this.emitNativeQuranPreparationProgress(result);

                        if (String(result?.state ?? '') === 'failed') {
                            throw new Error(
                                result?.message ??
                                    'تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.',
                            );
                        }

                        result = await this.waitForNativeQuranPreparation();
                    }

                    await this.syncPreparedQuranPayload(result.payload);

                    window.dispatchEvent(
                        new CustomEvent('quran-bootstrap-finished', {
                            detail: {
                                openGateOnSuccess,
                            },
                        }),
                    );
                } catch (error) {
                    window.dispatchEvent(
                        new CustomEvent('quran-bootstrap-failed', {
                            detail: {
                                message:
                                    String(error?.message ?? '').trim() ||
                                    'تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.',
                            },
                        }),
                    );
                } finally {
                    this._quranPreparationInFlight = null;
                }
            })();

            await this._quranPreparationInFlight;
        },

        destroy() {
            this.unregisterNativeInputListeners();
            this.clearSearchResultsUpdateQueue();
            this.unbindSearchModalInputSyncListener();

            if (this._onWindowViewportChange) {
                window.removeEventListener('resize', this._onWindowViewportChange);
                window.removeEventListener('orientationchange', this._onWindowViewportChange);
            }

            if (this._onVisualViewportChange && window.visualViewport) {
                window.visualViewport.removeEventListener('resize', this._onVisualViewportChange);
            }

            if (this._onWindowScroll) {
                window.removeEventListener('scroll', this._onWindowScroll);
            }

            if (this._onSwitchView) {
                window.removeEventListener('switch-view', this._onSwitchView);
            }

            if (this._onReaderGoGate) {
                window.removeEventListener('quran-reader-go-gate', this._onReaderGoGate);
                this._onReaderGoGate = null;
            }

            if (this._onQrDebugLogsToggle) {
                window.removeEventListener(
                    quranReaderDebugLogsToggleEventName,
                    this._onQrDebugLogsToggle,
                );
                this._onQrDebugLogsToggle = null;
            }

            if (this._onWirdSimulateDay) {
                window.removeEventListener('quran-wird-simulate-day', this._onWirdSimulateDay);
                this._onWirdSimulateDay = null;
            }

            if (this._onWirdCongratsPreview) {
                window.removeEventListener(
                    'quran-wird-congrats-preview',
                    this._onWirdCongratsPreview,
                );
                this._onWirdCongratsPreview = null;
            }

            if (this._onFontScaleToggle) {
                window.removeEventListener(
                    'quran-reader-font-scale-toggle',
                    this._onFontScaleToggle,
                );
                this._onFontScaleToggle = null;
            }

            if (this._pageScaleAdjustRefitRaf !== null) {
                cancelAnimationFrame(this._pageScaleAdjustRefitRaf);
                this._pageScaleAdjustRefitRaf = null;
            }

            this.clearDeferredBootstrapCheckTimer();
            this._deferredBootstrapCheckAttempts = 0;

            if (this._onWindowStorage) {
                window.removeEventListener('storage', this._onWindowStorage);
                this._onWindowStorage = null;
            }

            if (this._onHistoryManagerRequestSync) {
                window.removeEventListener(
                    'quran-history-manager-request-sync',
                    this._onHistoryManagerRequestSync,
                );
                this._onHistoryManagerRequestSync = null;
            }

            if (this._onBookmarksManagerRequestSync) {
                window.removeEventListener(
                    'quran-bookmarks-manager-request-sync',
                    this._onBookmarksManagerRequestSync,
                );
                this._onBookmarksManagerRequestSync = null;
            }

            if (typeof this._stopIsCalibratingWatcher === 'function') {
                this._stopIsCalibratingWatcher();
                this._stopIsCalibratingWatcher = null;
            }

            if (typeof this._stopSearchQueryWatcher === 'function') {
                this._stopSearchQueryWatcher();
                this._stopSearchQueryWatcher = null;
            }

            if (this._onSupportLockLivewireMorphed) {
                window.removeEventListener(
                    supportLockLivewireMorphedEventName,
                    this._onSupportLockLivewireMorphed,
                );
                this._onSupportLockLivewireMorphed = null;
            }

            if (this._supportLockTargetsSyncRaf !== null) {
                cancelAnimationFrame(this._supportLockTargetsSyncRaf);
                this._supportLockTargetsSyncRaf = null;
            }

            if (this._layoutRaf !== null) {
                cancelAnimationFrame(this._layoutRaf);
                this._layoutRaf = null;
            }

            if (this._readerPanelLayoutRaf !== null) {
                cancelAnimationFrame(this._readerPanelLayoutRaf);
                this._readerPanelLayoutRaf = null;
            }

            if (this._revealTimer !== null) {
                clearTimeout(this._revealTimer);
                this._revealTimer = null;
            }

            this.syncReaderChromeDocumentClass({ forceInactive: true });
            this.teardownLayoutObservers();

            if (this._viewportChangeDebounceTimer !== null) {
                clearTimeout(this._viewportChangeDebounceTimer);
                this._viewportChangeDebounceTimer = null;
            }

            if (this._surahTriggerTimer !== null) {
                clearTimeout(this._surahTriggerTimer);
                this._surahTriggerTimer = null;
            }

            if (this._surahTriggerCleanupTimer !== null) {
                clearTimeout(this._surahTriggerCleanupTimer);
                this._surahTriggerCleanupTimer = null;
            }

            if (this._mobileEdgeCaptionTimer !== null) {
                clearTimeout(this._mobileEdgeCaptionTimer);
                this._mobileEdgeCaptionTimer = null;
            }

            if (this._mobileEdgeCaptionCleanupTimer !== null) {
                clearTimeout(this._mobileEdgeCaptionCleanupTimer);
                this._mobileEdgeCaptionCleanupTimer = null;
            }

            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            if (this._swipeRevealWatchdogTimer !== null) {
                clearTimeout(this._swipeRevealWatchdogTimer);
                this._swipeRevealWatchdogTimer = null;
            }

            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }
            this._pageSliderInteractionActive = false;
            this._lastPageSliderCommitPage = 0;
            this._lastPageSliderCommitAt = 0;

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

            if (this._wirdHoverShimmerTimer !== null) {
                clearTimeout(this._wirdHoverShimmerTimer);
                this._wirdHoverShimmerTimer = null;
            }

            if (this._wirdSliderVisualTweenRaf !== null) {
                cancelAnimationFrame(this._wirdSliderVisualTweenRaf);
                this._wirdSliderVisualTweenRaf = null;
            }

            this.wirdSliderVisualStep = null;
            this.wirdHoverShimmerRunning = false;

            if (this._pageInputTweenRaf !== null) {
                cancelAnimationFrame(this._pageInputTweenRaf);
                this._pageInputTweenRaf = null;
            }

            if (this._searchModalCloseDebounceTimer !== null) {
                clearTimeout(this._searchModalCloseDebounceTimer);
                this._searchModalCloseDebounceTimer = null;
            }

            this.clearWirdEntryRevealTimers();

            if (this._modalLayoutResumeTimer !== null) {
                clearTimeout(this._modalLayoutResumeTimer);
                this._modalLayoutResumeTimer = null;
            }

            if (this._postModalTargetFitTimer !== null) {
                clearTimeout(this._postModalTargetFitTimer);
                this._postModalTargetFitTimer = null;
            }

            this._activeModalIds.clear();
            this._isModalLifecycleSettling = false;
            this._postModalTargetFitPage = 0;
            this._postModalTargetFitRetries = 0;
            this._lastPageRevealAt = 0;
            this._suppressModalLifecycleEffectsUntil = 0;
            this._suppressModalLifecycleModalIds.clear();
            this._wirdEntryLayoutSuppressedUntil = 0;

            if (this._wordPressHoldTimer !== null) {
                clearTimeout(this._wordPressHoldTimer);
                this._wordPressHoldTimer = null;
            }

            if (this._suppressWordClickResetTimer !== null) {
                clearTimeout(this._suppressWordClickResetTimer);
                this._suppressWordClickResetTimer = null;
            }

            if (this._copiedHighlightTimer !== null) {
                clearTimeout(this._copiedHighlightTimer);
                this._copiedHighlightTimer = null;
            }

            if (this._fitSanityCheckTimer !== null) {
                clearTimeout(this._fitSanityCheckTimer);
                this._fitSanityCheckTimer = null;
            }
            this._fitSanityContextKey = '';
            this._fitSanityContextAttemptCount = 0;
            this._fitSanityContextLastWidth = 0;
            this._fitSanityContextLastHeight = 0;
            this._fitSanityContextOutcome = '';
            this._fitSanitySuppressedUntil = 0;
            this._fitSanityDisabledContextKey = '';

            if (this._fitCachePersistWriteTimer !== null) {
                clearTimeout(this._fitCachePersistWriteTimer);
                this._fitCachePersistWriteTimer = null;
            }

            if (this._supportUnlockExpiryTimer !== null) {
                clearTimeout(this._supportUnlockExpiryTimer);
                this._supportUnlockExpiryTimer = null;
            }

            if (this._wirdCompletionTimer !== null) {
                clearTimeout(this._wirdCompletionTimer);
                this._wirdCompletionTimer = null;
            }
            this.isWirdCompletionVisible = false;
            this.isWirdCompletionPreviewPinned = false;

            if (this._fontReadyRecoveryTimer !== null) {
                clearTimeout(this._fontReadyRecoveryTimer);
                this._fontReadyRecoveryTimer = null;
            }

            this._fontReadyRecoveryPage = 0;
            this._fontReadyRecoveryAttemptPage = 0;
            this._fontReadyRecoveryAttemptCount = 0;
            this._fontReadyRecoveryLastAt = 0;
            this._layoutObservedViewportWidth = 0;
            this._layoutObservedViewportHeight = 0;
            this._readerPanelLayoutSerial = 0;
            this.isReaderChromeVisible = false;
            this.syncReaderChromeDocumentClass({ forceInactive: true });

            this.hideCopyFeedback();
            this.clearCopiedHighlights();
            this.clearBookmarkButtonPressState();
            this.closeSurahQuickNavigator();

            if (this.pageCounterPulse.timer !== null) {
                clearTimeout(this.pageCounterPulse.timer);
                this.pageCounterPulse.timer = null;
            }

            this.teardownSearchStreamObserver();
            this._lastSearchStreamPayloadRaw = '';
            this._lastSearchStreamPayloadOffset = 0;
            this._searchStreamFrameRemainder = '';
            if (this._searchResultsLeaveTimer !== null) {
                clearTimeout(this._searchResultsLeaveTimer);
                this._searchResultsLeaveTimer = null;
            }

            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                this._searchResultsAutoAnimateStop();
                this._searchResultsAutoAnimateStop = null;
            }

            if (typeof this._historyRowsAutoAnimateStop === 'function') {
                this._historyRowsAutoAnimateStop();
                this._historyRowsAutoAnimateStop = null;
            }

            if (typeof this._bookmarksRowsAutoAnimateStop === 'function') {
                this._bookmarksRowsAutoAnimateStop();
                this._bookmarksRowsAutoAnimateStop = null;
            }

            this._managerRowEffectTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._managerRowEffectTimers.clear();
            this.managerRowEffects = {
                history: {},
                bookmarks: {},
            };
            this.historyModalOpen = false;
            this.bookmarksModalOpen = false;
            this.jumpPageModalOpen = false;
            this.dispatchManagerModalsVisibilityState();

            if (typeof this._stopLivewireMorphedHook === 'function') {
                this._stopLivewireMorphedHook();
                this._stopLivewireMorphedHook = null;
            }

            this.abortActivePageLoad();
            this.abortIdleWarmupFetch();
            this.cancelIdleWarmupHandle();
            this._pendingNavigationRequest = null;
            this._navigationRevealLocked = false;
            this.isTransitioningOutPage = false;
            this.clearNavigationBurstState();
            this._layoutActivePromise = null;
            this._queuedLayoutRequest = null;
            this._bypassNextFitCache = false;
            this._suppressFitCacheWriteUntil = 0;
            this._idleWarmupQueue = [];
            this._idleWarmupQueuedPages.clear();
            this._idleWarmupInFlightPage = 0;
            this.clearFitResultCache({ persist: false });
            this.flushPersistedFitCacheWrite();
        },

        initializeLayoutObservers() {
            this.teardownLayoutObservers();

            const contentElement = this.$refs.pageContent;
            const viewportElement = this.$refs.pageViewport;

            if (contentElement instanceof Element && typeof MutationObserver !== 'undefined') {
                this._layoutMutationObserver = new MutationObserver(() => {
                    if (!this.ready || this.mushafLines.length === 0 || this.isLoadingPage) {
                        return;
                    }

                    if (!this.isReaderElementVisible()) {
                        this.qrDebugLog(
                            '[QR:MutationObserver] skipping scheduleLayout — not visible',
                        );
                        return;
                    }

                    this.qrDebugLog('[QR:MutationObserver] scheduleLayout');
                    this.scheduleLayout({ revealDelayMs: 170 });
                });

                this._layoutMutationObserver.observe(contentElement, {
                    childList: true,
                    subtree: true,
                });
            }

            if (viewportElement instanceof Element && typeof ResizeObserver !== 'undefined') {
                const viewportRect = viewportElement.getBoundingClientRect();
                this._layoutObservedViewportWidth = Math.max(
                    0,
                    Number(viewportRect?.width ?? viewportElement.clientWidth ?? 0),
                );
                this._layoutObservedViewportHeight = Math.max(
                    0,
                    Number(viewportRect?.height ?? viewportElement.clientHeight ?? 0),
                );

                this._layoutResizeObserver = new ResizeObserver((entries) => {
                    if (!this.ready || this.mushafLines.length === 0 || this.isLoadingPage) {
                        return;
                    }

                    if (this._layoutActivePromise !== null || this._revealTimer !== null) {
                        return;
                    }

                    const entry = Array.isArray(entries) ? entries[0] : null;
                    const nextObservedWidth = Math.max(
                        0,
                        Number(
                            entry?.contentRect?.width ??
                                viewportElement.clientWidth ??
                                viewportElement.getBoundingClientRect().width ??
                                0,
                        ),
                    );
                    const nextObservedHeight = Math.max(
                        0,
                        Number(
                            entry?.contentRect?.height ??
                                viewportElement.clientHeight ??
                                viewportElement.getBoundingClientRect().height ??
                                0,
                        ),
                    );

                    if (nextObservedWidth <= 0 || nextObservedHeight <= 0) {
                        return;
                    }

                    const widthDelta = Math.abs(
                        nextObservedWidth - this._layoutObservedViewportWidth,
                    );
                    const heightDelta = Math.abs(
                        nextObservedHeight - this._layoutObservedViewportHeight,
                    );

                    if (widthDelta < 1 && heightDelta < 1) {
                        return;
                    }

                    this._layoutObservedViewportWidth = nextObservedWidth;
                    this._layoutObservedViewportHeight = nextObservedHeight;
                    this.handleViewportChange();
                });

                this._layoutResizeObserver.observe(viewportElement);
            }
        },

        teardownLayoutObservers() {
            if (this._layoutMutationObserver) {
                this._layoutMutationObserver.disconnect();
                this._layoutMutationObserver = null;
            }

            if (this._layoutResizeObserver) {
                this._layoutResizeObserver.disconnect();
                this._layoutResizeObserver = null;
            }

            this._layoutObservedViewportWidth = 0;
            this._layoutObservedViewportHeight = 0;
        },

        resolveCurrentBreakpointName() {
            if (
                typeof window === 'undefined' ||
                !window.Alpine ||
                typeof window.Alpine.store !== 'function'
            ) {
                return '';
            }

            const breakpointStore = window.Alpine.store('bp');

            return String(breakpointStore?.current ?? '').trim();
        },

        viewportBucketValue(value) {
            const normalizedValue = Math.max(0, Math.round(Number(value) || 0));

            if (normalizedValue <= 0) {
                return 0;
            }

            const bucket = Math.max(8, Math.trunc(Number(fitCacheViewportBucketSizePx) || 24));

            return Math.max(bucket, Math.round(normalizedValue / bucket) * bucket);
        },

        cssLengthToPixels(value, contextElement = null, fallback = 0) {
            const rawValue = String(value ?? '').trim();
            const normalizedFallback = Number(fallback);

            if (rawValue === '') {
                return Number.isFinite(normalizedFallback) ? normalizedFallback : 0;
            }

            const directNumber = Number(rawValue);

            if (Number.isFinite(directNumber)) {
                return directNumber;
            }

            const match = rawValue.match(/^(-?\d*\.?\d+)([a-z%]*)$/i);

            if (!match) {
                return Number.isFinite(normalizedFallback) ? normalizedFallback : 0;
            }

            const amount = Number.parseFloat(match[1] ?? '0');
            const unit = String(match[2] ?? 'px').toLowerCase();

            if (!Number.isFinite(amount)) {
                return Number.isFinite(normalizedFallback) ? normalizedFallback : 0;
            }

            if (unit === '' || unit === 'px') {
                return amount;
            }

            if (unit === 'rem') {
                const rootFontSize = Number.parseFloat(
                    window.getComputedStyle(document.documentElement).fontSize,
                );

                return amount * (Number.isFinite(rootFontSize) ? rootFontSize : 16);
            }

            if (unit === 'em') {
                const fontSize =
                    contextElement instanceof Element
                        ? Number.parseFloat(window.getComputedStyle(contextElement).fontSize)
                        : 16;

                return amount * (Number.isFinite(fontSize) ? fontSize : 16);
            }

            if (['vh', 'svh', 'lvh', 'dvh'].includes(unit)) {
                const viewportHeight = Number(window.visualViewport?.height ?? window.innerHeight);

                return (amount / 100) * (Number.isFinite(viewportHeight) ? viewportHeight : 0);
            }

            if (['vw', 'svw', 'lvw', 'dvw'].includes(unit)) {
                const viewportWidth = Number(window.visualViewport?.width ?? window.innerWidth);

                return (amount / 100) * (Number.isFinite(viewportWidth) ? viewportWidth : 0);
            }

            return Number.isFinite(normalizedFallback) ? normalizedFallback : 0;
        },

        cssCustomLengthPixels(computedStyles, propertyName, contextElement = null, fallback = 0) {
            const rawValue = String(
                computedStyles?.getPropertyValue?.(propertyName) ||
                    (typeof window.cssVar === 'function'
                        ? window.cssVar(propertyName, contextElement ?? document.documentElement)
                        : '') ||
                    '',
            ).trim();

            return this.cssLengthToPixels(rawValue, contextElement, fallback);
        },

        normalizeFitCacheEntry(entry) {
            if (!entry || typeof entry !== 'object' || Array.isArray(entry)) {
                return null;
            }

            if (!entry.layout || typeof entry.layout !== 'object' || Array.isArray(entry.layout)) {
                return null;
            }

            const scale = Number(entry.scale);

            if (!Number.isFinite(scale) || scale <= 0) {
                return null;
            }

            return {
                layout: { ...entry.layout },
                scale: Number(scale.toFixed(4)),
            };
        },

        trimFitResultCache() {
            while (this._fitResultByContext.size > fitResultCacheLimit) {
                const oldestCacheKey = this._fitResultByContext.keys().next().value;

                if (!oldestCacheKey) {
                    break;
                }

                this._fitResultByContext.delete(oldestCacheKey);
            }
        },

        rememberFitResult(cacheKey, entry, { persist = true } = {}) {
            if (String(cacheKey ?? '').trim() === '') {
                return;
            }

            const normalizedEntry = this.normalizeFitCacheEntry(entry);

            if (!normalizedEntry) {
                return;
            }

            this._fitResultByContext.set(cacheKey, normalizedEntry);
            this.trimFitResultCache();

            if (persist) {
                this.queuePersistedFitCacheWrite();
            }
        },

        forgetFitResult(cacheKey, { persist = true } = {}) {
            if (String(cacheKey ?? '').trim() === '') {
                return;
            }

            this._fitResultByContext.delete(cacheKey);

            if (persist) {
                this.queuePersistedFitCacheWrite();
            }
        },

        syncFitCacheBreakpoint({ persist = true } = {}) {
            const currentBreakpoint = this.resolveCurrentBreakpointName();

            if (this._fitCacheBreakpoint === '') {
                this._fitCacheBreakpoint = currentBreakpoint;
                this.storage.fitCacheBreakpoint = currentBreakpoint;

                return;
            }

            if (currentBreakpoint === this._fitCacheBreakpoint) {
                return;
            }

            this._fitCacheBreakpoint = currentBreakpoint;
            this.storage.fitCacheBreakpoint = currentBreakpoint;
            this.clearFitResultCache({ persist: false });

            if (persist && shouldPersistFitCacheAcrossReloads) {
                writeLocalStorage(fitCacheStorageKey, {
                    version: fitCacheStorageVersion,
                    breakpoint: currentBreakpoint,
                    updated_at: Date.now(),
                    entries: {},
                    order: [],
                });
            }
        },

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
                await this.calibrateGlobalFitLayoutFromReferencePage();
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

                if (capturedLayout) {
                    this._globalFitCalibrationLayout = capturedLayout;
                    this._globalFitCalibrationScale = Math.max(
                        0.05,
                        Number(capturedLayout.pageScale ?? 1),
                    );
                    this._globalFitCalibrationPageNumber = normalizedReferencePage;
                }
            } catch (error) {
                this._globalFitCalibrationLayout = null;
                this._globalFitCalibrationScale = 0;
                this._globalFitCalibrationPageNumber = 0;
                this.traceReaderReveal('startup-global-fit-calibration-failed', {
                    page: normalizedReferencePage,
                    name: String(error?.name ?? 'Error'),
                    message: String(error?.message ?? ''),
                });
            } finally {
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
                    currentStep >= maxStep ||
                    progressStep >= maxStep;

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

        reconcileWirdNextAbsolutePage(record = this.wirdDailyRecord) {
            if (
                !record ||
                typeof record !== 'object' ||
                !this.wirdState ||
                typeof this.wirdState !== 'object'
            ) {
                return;
            }

            const startAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(record?.startAbsolutePage, 1, { min: 1 }),
            );
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.normalizeIntegerFlag(record?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });
            const progressStep = this.normalizeIntegerFlag(record?.progressStep, currentStep, {
                min: 0,
                max: maxStep,
            });
            const nextAbsolutePage = record?.completed
                ? startAbsolutePage + requiredPages
                : startAbsolutePage + progressStep;

            this.wirdState.nextAbsolutePage = Math.max(1, nextAbsolutePage);
        },

        resolveWirdRecordSignature() {
            const normalizedFrequencyMode = this.normalizeWirdFrequencyMode(
                this.wirdFrequencyMode,
                wirdFrequencyModeMonthly,
            );

            return [
                normalizedFrequencyMode,
                this.normalizeWirdKhatmatTarget(this.wirdKhatmatTarget, 1, {
                    frequencyMode: normalizedFrequencyMode,
                }),
                this.resolveReaderMaxPage(),
            ].join(':');
        },
    };
};
