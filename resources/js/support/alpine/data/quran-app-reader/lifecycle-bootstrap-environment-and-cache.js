export const createLifecycleBootstrapEnvironmentAndCacheModule = (deps) => {
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

    const nativeInstallFingerprintStorageKey = 'quran-reader-native-install-fingerprint-v1';
    const quranReaderViewNames = Object.freeze([
        'quran-app-tilawa',
        'quran-app-hifth',
        'quran-app-tadabbur',
    ]);

    return {
        traceStartupState(eventName, details = {}) {
            const normalizedEventName = String(eventName ?? '')
                .trim()
                .toLowerCase();
            const payload = {
                event: normalizedEventName || 'event',
                ts: Date.now(),
                view:
                    typeof this.activeQuranReaderView === 'function'
                        ? this.activeQuranReaderView()
                        : '',
                anyReaderViewOpen:
                    typeof this.isAnyQuranReaderViewOpen === 'function'
                        ? this.isAnyQuranReaderViewOpen()
                        : false,
                readerVisible:
                    typeof this.isReaderElementVisible === 'function'
                        ? this.isReaderElementVisible()
                        : false,
                readerPanelVisible:
                    typeof this.isReaderPanelVisible === 'function'
                        ? this.isReaderPanelVisible()
                        : false,
                startupCalibrationPending: Boolean(this._startupCalibrationPending),
                bootstrapDeferred: Boolean(this._bootstrapDeferred),
                bootstrapInFlight: Boolean(this._bootstrapInFlight),
                hasCompletedInitialMushafPreparation: Boolean(
                    this.hasCompletedInitialMushafPreparation,
                ),
                ready: Boolean(this.ready),
                maxPage: Number(this.maxPage ?? 0),
                pageNumber: Number(this.pageNumber ?? 0),
                ...(details && typeof details === 'object' && !Array.isArray(details)
                    ? details
                    : {}),
            };
            let serializedPayload = '{}';

            try {
                serializedPayload = JSON.stringify(payload);
            } catch {
                serializedPayload = '{"event":"serialization-failed"}';
            }

            if (this.nativeRuntime) {
                console.info(`[QR:startup] ${serializedPayload}`);
            }

            this.qrDebugLog(`[QR:startup] ${serializedPayload}`);
        },

        resolveNativeInstallFingerprint() {
            if (!this.nativeRuntime || typeof window === 'undefined') {
                return null;
            }

            const androidBridge =
                window.AndroidBridge && typeof window.AndroidBridge === 'object'
                    ? window.AndroidBridge
                    : null;

            if (!androidBridge || typeof androidBridge.getAppFirstInstallTime !== 'function') {
                return null;
            }

            try {
                const resolvedFingerprint = String(
                    androidBridge.getAppFirstInstallTime() ?? '',
                ).trim();

                if (/^\d{5,}$/.test(resolvedFingerprint)) {
                    return resolvedFingerprint;
                }
            } catch (_) {
                //
            }

            return null;
        },

        syncNativeInstallScopedReaderStorage() {
            if (typeof localStorage === 'undefined') {
                return;
            }

            const installFingerprint = this.resolveNativeInstallFingerprint();

            if (!installFingerprint) {
                this.traceStartupState('native-install-fingerprint-unavailable');

                return;
            }

            const persistedInstallFingerprint = String(
                readLocalStorage(nativeInstallFingerprintStorageKey, '') ?? '',
            ).trim();

            if (persistedInstallFingerprint === installFingerprint) {
                return;
            }

            [
                lastPageStorageKey,
                navigationHistoryStorageKey,
                bookmarksStorageKey,
                wirdProgressStorageKey,
                wirdDayOffsetStorageKey,
                supportUnlockStorageKey,
                quranPageScaleAdjustStorageKey,
                quranPageGapAdjustStorageKey,
                quranPageYOffsetAdjustStorageKey,
                fitCacheStorageKey,
                readerRevealDebugStorageKey,
                'quran-reader-page-number-v1',
                'app-active-view',
                'athkar-reader-visible',
            ].forEach((storageKey) => {
                if (typeof storageKey !== 'string' || storageKey.trim() === '') {
                    return;
                }

                try {
                    localStorage.removeItem(storageKey);
                    localStorage.removeItem(`_x_${storageKey}`);
                } catch (_) {
                    //
                }
            });

            writeLocalStorage(nativeInstallFingerprintStorageKey, installFingerprint);
            this._wirdStateStorageRawSnapshot = null;
            this._wirdDayOffsetStorageRawSnapshot = null;

            this.traceStartupState('native-install-fingerprint-changed', {
                installFingerprint,
                hadPersistedFingerprint: persistedInstallFingerprint !== '',
            });
        },

        isQuranReaderViewName(viewName = '') {
            return quranReaderViewNames.includes(String(viewName ?? '').trim());
        },

        currentHashViewName() {
            if (typeof window === 'undefined') {
                return '';
            }

            return String(window.location?.hash ?? '')
                .trim()
                .replace(/^#+/u, '');
        },

        isQuranReaderHashActive() {
            return this.isQuranReaderViewName(this.currentHashViewName());
        },

        hasRecentReaderLaunchIntent(maxAgeMs = 2800) {
            const startedAt = Number(this._startupReaderLaunchIntentAt ?? 0);

            if (!Number.isFinite(startedAt) || startedAt <= 0) {
                return false;
            }

            return Date.now() - startedAt <= Math.max(120, Math.trunc(Number(maxAgeMs) || 2800));
        },

        markReaderLaunchIntent(viewName = '', source = 'generic') {
            const normalizedViewName = this.isQuranReaderViewName(viewName) ? viewName : '';
            const normalizedSource = String(source ?? '').trim() || 'generic';

            this._startupReaderLaunchIntentAt = Date.now();
            this._startupReaderLaunchIntentView = normalizedViewName;
            this.traceStartupState('reader-launch-intent', {
                source: normalizedSource,
                requestedView: normalizedViewName,
                hashView: this.currentHashViewName(),
            });
        },

        clearStartupRecoveryTimers() {
            if (!Array.isArray(this._startupRecoveryTimerIds)) {
                this._startupRecoveryTimerIds = [];

                return;
            }

            this._startupRecoveryTimerIds.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._startupRecoveryTimerIds = [];
        },

        clearPostBootstrapRecoveryTimers() {
            if (!Array.isArray(this._postBootstrapRecoveryTimerIds)) {
                this._postBootstrapRecoveryTimerIds = [];

                return;
            }

            this._postBootstrapRecoveryTimerIds.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._postBootstrapRecoveryTimerIds = [];
        },

        queueReaderStartupRecoverySweep(source = 'unknown') {
            this.clearStartupRecoveryTimers();

            const recoverySource = String(source ?? '').trim() || 'unknown';
            const delaysMs = [0, 120, 280, 520, 880, 1280];

            delaysMs.forEach((delayMs, index) => {
                const timerId = window.setTimeout(() => {
                    this._startupRecoveryTimerIds = this._startupRecoveryTimerIds.filter(
                        (activeTimerId) => activeTimerId !== timerId,
                    );
                    const attempted = this.attemptPendingStartupBootstrap();

                    this.traceStartupState('reader-startup-recovery-sweep', {
                        source: recoverySource,
                        delayMs,
                        attempt: index + 1,
                        attempted,
                    });
                }, delayMs);

                this._startupRecoveryTimerIds.push(timerId);
            });
        },

        queuePostBootstrapReaderVisibilityRecovery(source = 'bootstrap-finished') {
            this.clearPostBootstrapRecoveryTimers();

            const normalizedSource = String(source ?? '').trim() || 'bootstrap-finished';
            const recoveryDelaysMs = [90, 220, 460, 860, 1280, 1800];

            recoveryDelaysMs.forEach((delayMs, index) => {
                const timerId = window.setTimeout(() => {
                    this._postBootstrapRecoveryTimerIds =
                        this._postBootstrapRecoveryTimerIds.filter(
                            (activeTimerId) => activeTimerId !== timerId,
                        );

                    if (
                        this._startupCalibrationPending ||
                        this._bootstrapInFlight ||
                        !this.shouldTreatReaderAsLaunchPending()
                    ) {
                        return;
                    }

                    const isPageVisible =
                        this.isCurrentPageVisiblyReady?.() ||
                        this.isCurrentPageContentVisible?.(0.12);

                    if (isPageVisible) {
                        this.traceStartupState('post-bootstrap-visibility-ready', {
                            source: normalizedSource,
                            delayMs,
                            attempt: index + 1,
                        });

                        return;
                    }

                    this.traceStartupState('post-bootstrap-visibility-recovery-attempt', {
                        source: normalizedSource,
                        delayMs,
                        attempt: index + 1,
                    });

                    this.scheduleReaderPanelLayoutRefresh?.();
                    this.clearStaleRevealGuards?.();

                    if (!this.isFittingPage) {
                        this.isFittingPage = true;
                    }

                    this.scheduleLayout?.({ revealDelayMs: 110, maxAttempts: 5 });

                    if (index === recoveryDelaysMs.length - 1) {
                        this.traceStartupState('post-bootstrap-visibility-force-reveal', {
                            source: normalizedSource,
                        });
                        this.forceRevealCurrentPage?.('post-bootstrap-visibility-recovery');
                    }
                }, delayMs);

                this._postBootstrapRecoveryTimerIds.push(timerId);
            });
        },

        shouldTreatReaderAsLaunchPending() {
            if (this.isAnyQuranReaderViewOpen()) {
                return true;
            }

            if (this.isReaderElementVisible() || this.isReaderPanelVisible()) {
                return true;
            }

            if (this.isQuranReaderHashActive()) {
                return true;
            }

            return this.hasRecentReaderLaunchIntent();
        },

        init() {
            this.syncNativeInstallScopedReaderStorage();
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
            this._onDocumentVisibilityChange = () => {
                this.traceStartupState('document-visibilitychange', {
                    visibilityState: String(document.visibilityState ?? ''),
                    hidden: Boolean(document.hidden),
                });
                this.attemptPendingStartupBootstrap();
            };
            document.addEventListener('visibilitychange', this._onDocumentVisibilityChange);
            this._onWindowFocus = () => {
                this.traceStartupState('window-focus');
                this.attemptPendingStartupBootstrap();
            };
            window.addEventListener('focus', this._onWindowFocus);
            this._onWindowBlur = () => {
                this.traceStartupState('window-blur');
            };
            window.addEventListener('blur', this._onWindowBlur);
            this._onWindowPageShow = () => {
                this.traceStartupState('window-pageshow');
                this.attemptPendingStartupBootstrap();
            };
            window.addEventListener('pageshow', this._onWindowPageShow);
            this._onNativeLifecycleTrace = (event) => {
                const detail =
                    event?.detail && typeof event.detail === 'object' ? event.detail : {};
                this.traceStartupState('native-lifecycle-event', detail);
                this.attemptPendingStartupBootstrap();
            };
            window.addEventListener('quran-native-lifecycle', this._onNativeLifecycleTrace);
            this._onReaderLaunchRequested = (event) => {
                const detail =
                    event?.detail && typeof event.detail === 'object' ? event.detail : {};
                const requestedView = String(detail?.view ?? '').trim();

                this.markReaderLaunchIntent(requestedView, 'gate-launch-requested-event');
                this.queueReaderStartupRecoverySweep('gate-launch-requested-event');
            };
            window.addEventListener('quran-reader-launch-requested', this._onReaderLaunchRequested);

            this._onSwitchView = (event) => {
                const to = String(event?.detail?.to ?? '');
                const isGoingToQuranReader = this.isQuranReaderViewName(to);

                if (isGoingToQuranReader) {
                    this.markReaderLaunchIntent(to, 'switch-view-event');
                }

                this.traceStartupState('switch-view', {
                    to,
                    isGoingToQuranReader,
                });

                if (!isGoingToQuranReader) {
                    this.isFontScaleOverlayVisible = false;
                    this.isReaderChromeVisible = false;
                    this.syncReaderChromeDocumentClass({ forceInactive: true });
                    this._immersiveEntryAwaitingFirstReveal = true;
                    this.clearDeferredBootstrapCheckTimer();
                    this._deferredBootstrapCheckAttempts = 0;
                    this.clearPostBootstrapRecoveryTimers();

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
                this.clearStartupRecoveryTimers();
                this.clearPostBootstrapRecoveryTimers();
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

                window.setTimeout(() => {
                    this.attemptPendingStartupBootstrap();
                }, 520);
                this.queueReaderStartupRecoverySweep('switch-view-reader-entry');

                this.scheduleLayout({ revealDelayMs: 200 });
            };

            window.addEventListener('switch-view', this._onSwitchView);
            this._onReaderGoGate = () => {
                this.isReaderChromeVisible = false;
                this.isFontScaleOverlayVisible = false;
                this.syncReaderChromeDocumentClass({ forceInactive: true });
                this.clearStartupRecoveryTimers();
                this.clearPostBootstrapRecoveryTimers();
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

        attemptPendingStartupBootstrap() {
            if (this._bootstrapInFlight || !this._startupCalibrationPending) {
                this.traceStartupState('startup-recovery-skipped', {
                    reason: this._bootstrapInFlight ? 'bootstrap-in-flight' : 'not-pending',
                });
                return false;
            }

            const isReaderViewOpen = this.isAnyQuranReaderViewOpen();
            const isReaderElementVisible = this.isReaderElementVisible();
            const isReaderPanelVisible = this.isReaderPanelVisible();
            const isReaderSurfaceVisible = isReaderElementVisible || isReaderPanelVisible;

            if (!this.shouldTreatReaderAsLaunchPending()) {
                this.traceStartupState('startup-recovery-skipped', {
                    reason: 'reader-view-closed-and-hidden',
                    isReaderViewOpen,
                    isReaderSurfaceVisible,
                    hashView: this.currentHashViewName(),
                    hasRecentReaderLaunchIntent: this.hasRecentReaderLaunchIntent(),
                });
                return false;
            }

            const hasPreparedPayload =
                this.ready &&
                this.maxPage > 0 &&
                Array.isArray(this.mushafLines) &&
                this.mushafLines.length > 0;

            if (!hasPreparedPayload) {
                this.traceStartupState('startup-recovery-skipped', {
                    reason: 'payload-not-ready',
                });
                return false;
            }

            this.clearDeferredBootstrapCheckTimer();
            this._deferredBootstrapCheckAttempts = 0;
            this.traceStartupState('startup-recovery-triggering-bootstrap');

            this.$nextTick(() => {
                if (
                    this._bootstrapInFlight ||
                    !this._startupCalibrationPending ||
                    !this.shouldTreatReaderAsLaunchPending()
                ) {
                    this.traceStartupState('startup-recovery-next-tick-skipped');
                    return;
                }

                this.traceStartupState('startup-recovery-bootstrap-call');
                this.bootstrap();
            });

            return true;
        },

        maybeBootstrapDeferred() {
            if (!this._bootstrapDeferred) {
                return true;
            }

            const isReaderViewOpen = this.isAnyQuranReaderViewOpen();
            const isReaderElementVisible = this.isReaderElementVisible();
            const isReaderPanelVisible = this.isReaderPanelVisible();

            if (!this.shouldTreatReaderAsLaunchPending()) {
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
                this.traceStartupState('deferred-bootstrap-check-attempt', {
                    attempt: this._deferredBootstrapCheckAttempts,
                });

                if (this._deferredBootstrapCheckAttempts > 18) {
                    this.traceStartupState('deferred-bootstrap-check-expired');
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

            if (!(readerPanel instanceof Element)) {
                return false;
            }

            if (readerPanel.offsetParent !== null) {
                return true;
            }

            return Boolean(
                readerPanel.offsetWidth ||
                readerPanel.offsetHeight ||
                readerPanel.getClientRects().length,
            );
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
                this.attemptPendingStartupBootstrap();
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

            if (this._onDocumentVisibilityChange) {
                document.removeEventListener('visibilitychange', this._onDocumentVisibilityChange);
                this._onDocumentVisibilityChange = null;
            }

            if (this._onWindowFocus) {
                window.removeEventListener('focus', this._onWindowFocus);
                this._onWindowFocus = null;
            }

            if (this._onWindowBlur) {
                window.removeEventListener('blur', this._onWindowBlur);
                this._onWindowBlur = null;
            }

            if (this._onWindowPageShow) {
                window.removeEventListener('pageshow', this._onWindowPageShow);
                this._onWindowPageShow = null;
            }

            if (this._onNativeLifecycleTrace) {
                window.removeEventListener('quran-native-lifecycle', this._onNativeLifecycleTrace);
                this._onNativeLifecycleTrace = null;
            }

            if (this._onReaderLaunchRequested) {
                window.removeEventListener(
                    'quran-reader-launch-requested',
                    this._onReaderLaunchRequested,
                );
                this._onReaderLaunchRequested = null;
            }

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
            this.clearStartupRecoveryTimers();
            this.clearPostBootstrapRecoveryTimers();

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

            if (this._modalLifecycleFadeOutTimer !== null) {
                clearTimeout(this._modalLifecycleFadeOutTimer);
                this._modalLifecycleFadeOutTimer = null;
            }

            this._modalLifecycleFadeOutPending = false;

            if (this._modalPreOpenPendingTimer !== null) {
                clearTimeout(this._modalPreOpenPendingTimer);
                this._modalPreOpenPendingTimer = null;
            }

            this._modalPreOpenPending = false;

            if (this._postModalTargetFitTimer !== null) {
                clearTimeout(this._postModalTargetFitTimer);
                this._postModalTargetFitTimer = null;
            }

            if (this._modalDrivenFinalRecoveryFitTimer !== null) {
                clearTimeout(this._modalDrivenFinalRecoveryFitTimer);
                this._modalDrivenFinalRecoveryFitTimer = null;
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
    };
};
