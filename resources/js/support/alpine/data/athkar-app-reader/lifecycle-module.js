export const createLifecycleModule = (deps) => {
    const {
        athkarOverridesStorageKey,
        migrateSettingsOverrides,
        normalizeAthkarDefaults,
        normalizeAthkarOverrides,
        readAthkarOverridesFromStorage,
        readAthkarSettingsFromStorage,
        resolveAthkarWithOverrides,
        resolveEffectiveSettings,
        writeAthkarOverridesToStorage,
        writeAthkarSettingsToStorage,
        writeUserSettingOverride,
        createShimmerController,
        doesEnableVisualEnhancementsKey,
        skipGuidancePanelsSettingKey,
        progressStorageKey,
        supportUnlockStorageKey,
        supportUnlockModePermanent,
        supportUnlockModeWeekly,
        athkarCopyHoldDelayMs,
        athkarCopyHoldMoveThresholdPx,
        athkarCopyPopoverVisibleDurationMs,
        defaultProgressState,
        emptyProgressStats,
        resolveProgressStatsSafely,
        readProgressFromStorage,
    } = deps;

    return {
        init() {
            if (this.isFastUiMode) {
                this.readerLeaveMs = 40;
                this.slideDurationMs = 120;
                this.pulseDurationMs = 80;
                this.topUiCompletionLingerMs = 80;
                this.topUiPulseDurationMs = 120;
                this.originFadeDurationMs = 0;
                this.originResyncDelayMs = 0;
                this.completionVisibleMs = 250;
                this.textFitSettleMs = 0;
                this.transitionDistance = '0rem';
            }

            window.athkarSettingsDefaults = this.settingsDefaults;
            window.athkarMainTextSizeLimits = this.mainTextSizeLimits;
            migrateSettingsOverrides(this.settingsDefaults);
            this.settings = resolveEffectiveSettings(this.settingsDefaults);
            this.progress = readProgressFromStorage();
            this.ensureState();
            this.refreshCompletionInputMode();
            this.applyAthkarOverrides(this.athkarOverrides, { persist: true });
            this.syncDay();
            this.ensureProgress('sabah');
            this.ensureProgress('masaa');
            const handleReaderViewportChange = () => {
                this.refreshCompletionInputMode();
                this.resetLayerScrollOffsets();
                this.queueReaderTextFit();
            };
            window.addEventListener('resize', handleReaderViewportChange);
            window.addEventListener('orientationchange', handleReaderViewportChange);
            window.addEventListener('focus', () => this.syncDay());
            window.addEventListener('fitty-refit-complete', () => {
                if (!this.activeMode) {
                    return;
                }

                if (!this.views?.['athkar-app-gate']?.isReaderVisible || this.isNoticeVisible) {
                    return;
                }

                this.$nextTick(() => this.syncVisibleTextBoxState(this.activeIndex));
            });
            window.addEventListener('athkar-overrides-updated', (event) => {
                this.applyAthkarOverrides(event?.detail?.overrides ?? [], { persist: true });
            });
            window.addEventListener('storage', (event) => {
                if (event.key !== athkarOverridesStorageKey) {
                    return;
                }

                this.applyAthkarOverrides(readAthkarOverridesFromStorage(), { persist: false });
            });
            window.addEventListener('athkar-single-completion-confirmed', (event) => {
                const index = Number(event?.detail?.index ?? -1);

                if (!Number.isFinite(index) || index < 0) {
                    return;
                }

                this.completeThikr(index);
            });
            window.addEventListener('switch-view', (event) => {
                const nextView = event?.detail?.to;
                const isRestoring = Boolean(event?.detail?.restoring) || this.isRestoring;

                if (!nextView) {
                    return;
                }

                if (nextView === 'main-menu') {
                    this.isGateMenuTransition = true;
                    if (this.views?.['athkar-app-gate']) {
                        this.views['athkar-app-gate'].isReaderVisible = false;
                    }
                    this.resetReaderState();
                    if (isRestoring) {
                        this.isRestoring = false;
                    }
                    return;
                }

                if (nextView === 'athkar-app-gate') {
                    this.isGateMenuTransition = !this.activeMode;
                    if (this.views?.['athkar-app-gate']) {
                        this.views['athkar-app-gate'].isReaderVisible = false;
                    }
                    this.isNoticeVisible = false;
                    this.softCloseMode();
                    if (isRestoring) {
                        this.isRestoring = false;
                    }
                    return;
                }

                if (nextView === 'athkar-app-sabah') {
                    this.isGateMenuTransition = false;
                    if (isRestoring && this.activeMode === 'sabah') {
                        this.restoreMode('sabah');
                        this.isRestoring = false;
                        return;
                    }
                    this.startModeNotice('sabah', { respectLock: false });
                    if (isRestoring) {
                        this.isRestoring = false;
                    }
                    return;
                }

                if (nextView === 'athkar-app-masaa') {
                    this.isGateMenuTransition = false;
                    if (isRestoring && this.activeMode === 'masaa') {
                        this.restoreMode('masaa');
                        this.isRestoring = false;
                        return;
                    }
                    this.startModeNotice('masaa', { respectLock: false });
                    if (isRestoring) {
                        this.isRestoring = false;
                    }
                }
            });
            document.addEventListener('visibilitychange', () => {
                if (document.visibilityState === 'visible') {
                    this.syncDay();
                }
            });
            window.addEventListener('beforeunload', () => {
                if (this._persistTimer !== null) {
                    clearTimeout(this._persistTimer);
                    this._flushProgress();
                }

                this.clearRapidTapReleaseTimer();
                this.hideCopyFeedback();
                this.cancelHoldCopy();
                this.unregisterNativeVolumeNavigation();
                this.clearOriginTransitionTimer();
            });

            this.setupTextFit();
            this.registerNativeVolumeNavigation();
            this.syncNativeVolumeNavigation();
            window.isMuttasiqSupportUnlocked = () => this.isSupportUnlocked();
            window.guardMuttasiqSupportLockedAction = (event = null) =>
                this.guardSupportLockedAction(event);
            this.textShimmerController = createShimmerController({
                resolveRoot: () => this.$el,
                resolveUseAlternateTarget: () => this.isOriginVisible(this.activeIndex),
                selectors: {
                    activeContainer: '[data-athkar-slide][data-active="true"]',
                    primaryTarget: '[data-athkar-text]',
                    alternateTarget: '[data-athkar-origin-text]',
                    shimmerTarget: '[data-athkar-shimmer]',
                },
                classes: {
                    muted: 'athkar-text--muted',
                    shimmer: 'athkar-shimmer',
                    shimmering: 'is-shimmering',
                },
            });
            this.$watch('activeMode', () => {
                this.resetMaintenanceTapTracking();
                this.resetRapidTapMode();
                this.closeHint();
                this.resetSwipeState();
                this.hideOrigin();
                this.queueTextFit();
                this.syncNativeVolumeNavigation();
            });
            this.$watch('activeIndex', () => {
                this.resetMaintenanceTapTracking();
                this.resetRapidTapMode();
                this.closeHint();
                this.hideOrigin();
                this.queueTextFit();
            });
            this.$watch(
                () => this.views?.['athkar-app-gate']?.isReaderVisible,
                (isVisible) => {
                    if (isVisible) {
                        this.closeHint();
                        this.resetSwipeState();
                        this.queueReaderTextFit();
                    }

                    this.syncNativeVolumeNavigation();
                },
            );
            this.$watch('isNoticeVisible', (isNoticeVisible) => {
                if (!isNoticeVisible && this.views?.['athkar-app-gate']?.isReaderVisible) {
                    this.queueReaderTextFit();
                }

                this.syncNativeVolumeNavigation();
            });
            this.$watch('isCompletionVisible', () => {
                this.syncNativeVolumeNavigation();
            });
        },

        applyAthkarOverrides(nextOverrides, { persist = true } = {}) {
            if (!Array.isArray(nextOverrides)) {
                return;
            }

            const normalized = normalizeAthkarOverrides(nextOverrides);

            this.athkarOverrides = persist ? writeAthkarOverridesToStorage(normalized) : normalized;

            this.syncAthkarWithOverrides();
        },

        syncAthkarWithOverrides() {
            this.athkar = resolveAthkarWithOverrides(this.defaultAthkar, this.athkarOverrides);
            this._athkarVersion++;
            this.invalidateModeMetrics();

            if (!this.progress || typeof this.progress !== 'object') {
                return;
            }

            this.ensureProgress('sabah');
            this.ensureProgress('masaa');

            if (!this.activeMode) {
                return;
            }

            if (!this.activeList.length) {
                this.closeMode();

                return;
            }

            this.resumeModeIndex();
            this.$nextTick(() => this.queueReaderTextFit());
        },

        applySettings(nextSettings, options = {}) {
            if (!nextSettings || typeof nextSettings !== 'object') {
                return;
            }

            const isMaintenancePulse = Boolean(options?.maintenancePulse);
            const previousSettings =
                this.settings && typeof this.settings === 'object' ? { ...this.settings } : {};

            if (!isMaintenancePulse) {
                Object.keys(nextSettings).forEach((key) => {
                    writeUserSettingOverride(key, nextSettings[key]);
                });
            }

            this.settings = resolveEffectiveSettings(this.settingsDefaults);
            if (!isMaintenancePulse) {
                writeAthkarSettingsToStorage(this.settings, this.settingsDefaults);
            }

            this.ensureProgress('sabah');
            this.ensureProgress('masaa');

            if (
                this.activeMode &&
                this.shouldPreventSwitching() &&
                this.activeIndex > this.maxNavigableIndex
            ) {
                this.progress[this.activeMode].index = this.maxNavigableIndex;
                this.progress[this.activeMode].activeId =
                    this.activeList[this.maxNavigableIndex]?.id ?? null;
            }

            if (this.shouldSkipGuidancePanels()) {
                this.closeHint();

                if (this.isNoticeVisible) {
                    this.confirmNotice({ markBypassed: false });
                }

                if (this.isCompletionVisible) {
                    this.isCompletionVisible = false;

                    if (this.completionTimer) {
                        clearTimeout(this.completionTimer);
                        this.completionTimer = null;
                    }

                    if (this.views?.['athkar-app-gate']) {
                        this.views['athkar-app-gate'].isReaderVisible = false;
                    }

                    this.activeMode = null;
                    this.$viewNav('athkar-app-gate');
                }
            }

            if (!this.shouldEnableVisualEnhancements()) {
                this.stopTextShimmer();
            }

            const didTextFitSettingsChange =
                Number(previousSettings.minimum_main_text_size ?? NaN) !==
                    Number(this.settings.minimum_main_text_size ?? NaN) ||
                Number(previousSettings.maximum_main_text_size ?? NaN) !==
                    Number(this.settings.maximum_main_text_size ?? NaN) ||
                Boolean(previousSettings[doesEnableVisualEnhancementsKey]) !==
                    Boolean(this.settings[doesEnableVisualEnhancementsKey]);

            if (isMaintenancePulse && !didTextFitSettingsChange) {
                return;
            }

            this.queueTextFit();
            this.queueReaderTextFit();
            this.syncNativeVolumeNavigation();
        },

        readSupportUnlockState() {
            if (typeof localStorage === 'undefined') {
                return { mode: 'locked', expiresAt: null };
            }

            try {
                const rawValue = JSON.parse(
                    localStorage.getItem(supportUnlockStorageKey) ?? 'null',
                );
                const normalizedMode = String(rawValue?.mode ?? '')
                    .trim()
                    .toLowerCase();
                const expiresAt = Math.max(0, Math.trunc(Number(rawValue?.expires_at ?? 0)));

                if (normalizedMode === supportUnlockModePermanent) {
                    return { mode: supportUnlockModePermanent, expiresAt: null };
                }

                if (normalizedMode === supportUnlockModeWeekly && expiresAt > Date.now()) {
                    return { mode: supportUnlockModeWeekly, expiresAt };
                }
            } catch (_) {
                //
            }

            return { mode: 'locked', expiresAt: null };
        },

        isSupportUnlocked() {
            return this.readSupportUnlockState().mode !== 'locked';
        },

        guardSupportLockedAction(event = null) {
            if (this.isSupportUnlocked()) {
                return true;
            }

            if (event && typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            if (event && typeof event.stopPropagation === 'function') {
                event.stopPropagation();
            }

            window.dispatchEvent(new CustomEvent('open-support-unlock-modal'));

            return false;
        },

        canUseNativeVolumeButtonNavigation() {
            const isReaderVisible = Boolean(this.views?.['athkar-app-gate']?.isReaderVisible);

            return (
                this.nativeRuntime &&
                Boolean(this.activeMode) &&
                isReaderVisible &&
                !this.isNoticeVisible &&
                !this.isCompletionVisible &&
                this.settingValue('does_quran_use_volume_buttons_navigation', false)
            );
        },

        setAndroidVolumeNavigationEnabled(enabled) {
            if (!this.nativeRuntime || !window.AndroidBridge) {
                return;
            }

            if (typeof window.AndroidBridge.setQuranVolumeNavigationEnabled !== 'function') {
                return;
            }

            window.AndroidBridge.setQuranVolumeNavigationEnabled(Boolean(enabled));
        },

        syncNativeVolumeNavigation() {
            this.$nextTick(() => {
                this.setAndroidVolumeNavigationEnabled(this.canUseNativeVolumeButtonNavigation());
            });
        },

        registerNativeVolumeNavigation() {
            if (this._onWindowNativeVolumeButton) {
                return;
            }

            this._onWindowNativeVolumeButton = (event) => {
                void this.handleNativeVolumeButton(event?.detail?.direction ?? null, event);
            };

            window.addEventListener(
                'quran-native-volume-button',
                this._onWindowNativeVolumeButton,
                true,
            );
        },

        unregisterNativeVolumeNavigation() {
            if (!this._onWindowNativeVolumeButton) {
                this.setAndroidVolumeNavigationEnabled(false);

                return;
            }

            window.removeEventListener(
                'quran-native-volume-button',
                this._onWindowNativeVolumeButton,
                true,
            );
            this._onWindowNativeVolumeButton = null;
            this.setAndroidVolumeNavigationEnabled(false);
        },

        async handleNativeVolumeButton(direction, _event = null) {
            if (!this.canUseNativeVolumeButtonNavigation()) {
                return;
            }

            const normalizedDirection = String(direction ?? '')
                .trim()
                .toLowerCase();

            if (normalizedDirection === 'next') {
                // Match primary tap behavior: increment current thikr, and auto-advance
                // when completion conditions are met.
                this.handleTap();

                return;
            }

            if (normalizedDirection === 'previous' || normalizedDirection === 'prev') {
                this.prev();
            }
        },

        toggleHint(index) {
            if (this.shouldSkipGuidancePanels() && !this.isMobileViewport()) {
                this.closeHint();
                return;
            }

            const nextIndex = this.hintIndex === index ? null : index;
            this.hintIndex = nextIndex;
            this.setMobileCounterOpen(nextIndex !== null);
        },

        closeHint({ keepMobileOpen = false } = {}) {
            this.hintIndex = null;
            if (!keepMobileOpen) {
                this.setMobileCounterOpen(false);
            }
        },

        resetSwipeState() {
            this.swipe.active = false;
            this.swipe.ignoreClick = false;
            this.swipe.startedOnTap = false;
            this.swipe.startedInScrollableText = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;
        },

        shouldShowSharedMobileCounter() {
            if (!this.activeMode) {
                return false;
            }

            const required = this.requiredCount(this.activeIndex);
            const count = this.countAt(this.activeIndex);

            return (
                required > 1 ||
                count > required ||
                this.topUi.progressOverride !== null ||
                this.topUi.pulseActive
            );
        },

        counterProgressPercent(index) {
            const required = this.requiredCount(index);

            if (required <= 0) {
                return 0;
            }

            return Math.min(100, (this.countAt(index) / required) * 100);
        },

        sharedCounterProgressPercent() {
            if (typeof this.topUi.progressOverride === 'number') {
                return Math.min(Math.max(this.topUi.progressOverride, 0), 100);
            }

            return this.counterProgressPercent(this.activeIndex);
        },

        sharedCounterProgressStyle() {
            return `--progress: ${this.sharedCounterProgressPercent()}%`;
        },

        sharedCounterPulseState() {
            return this.topUi.pulseActive ? 'active' : 'inactive';
        },

        resetTopUiTransition() {
            if (this.topUi.lingerTimer) {
                clearTimeout(this.topUi.lingerTimer);
                this.topUi.lingerTimer = null;
            }

            if (this.topUi.pulseTimer) {
                clearTimeout(this.topUi.pulseTimer);
                this.topUi.pulseTimer = null;
            }

            this.topUi.progressOverride = null;
            this.topUi.pulseActive = false;
        },

        startTopUiCompletionTransition(completedIndex, nextIndex) {
            if (!this.activeMode) {
                return;
            }

            const completedCount = this.countAt(completedIndex);
            const completedRequired = this.requiredCount(completedIndex);
            const nextRequired = this.requiredCount(nextIndex);

            this.resetTopUiTransition();
            this.setActiveIndex({
                index: nextIndex,
                preserveTopUiTransition: true,
            });

            if (completedRequired <= 1 && nextRequired <= 1) {
                return;
            }

            const nextCount = this.countAt(nextIndex);

            this.topUi.progressOverride = 100;
            this.triggerCountPulse(nextIndex, completedCount, nextCount);

            this.topUi.lingerTimer = setTimeout(() => {
                this.topUi.lingerTimer = null;
                this.topUi.pulseActive = true;

                this.topUi.pulseTimer = setTimeout(() => {
                    this.topUi.pulseTimer = null;
                    this.topUi.pulseActive = false;
                    this.topUi.progressOverride = null;
                }, this.topUiPulseDurationMs);
            }, this.topUiCompletionLingerMs);
        },

        isHintOpen(index) {
            return this.hintIndex === index;
        },

        isMobileViewport() {
            if (!window.matchMedia) {
                return false;
            }

            return window.matchMedia('(max-width: 639px)').matches;
        },

        setMobileCounterOpen(isOpen) {
            if (!this.isMobileViewport()) {
                this.isMobileCounterOpen = false;
                return;
            }

            this.isMobileCounterOpen = Boolean(isOpen);
        },

        markAllActiveModeComplete() {
            if (!this.activeMode) {
                return;
            }

            this.hideCompletionHack({ force: true });
            const previousTotal = this.totalCompletedCount;
            this.progress[this.activeMode].counts = this.activeList.map((_, index) =>
                this.requiredCount(index),
            );
            this.progress[this.activeMode].ids = this.activeList.map((item) => item?.id ?? null);
            this.progress[this.activeMode].activeId = this.activeList[this.activeIndex]?.id ?? null;
            this.invalidateModeMetrics(this.activeMode);
            this.persistProgress();
            const nextTotal = this.totalCompletedCount;
            this.triggerTotalPulse(previousTotal, nextTotal);

            this.finishActiveMode();
        },
    };
};
