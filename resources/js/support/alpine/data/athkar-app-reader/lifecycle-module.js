import { acquireScreenAwakeLock, releaseScreenAwakeLock } from '../../../screen-awake';

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

    const minimumMainTextSizeKey = 'minimum_main_text_size';
    const maximumMainTextSizeKey = 'maximum_main_text_size';

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
            this._onAthkarFontScaleToggle = () => {
                this.toggleFontScaleOverlay();
            };
            window.addEventListener(
                'athkar-reader-font-scale-toggle',
                this._onAthkarFontScaleToggle,
            );
            window.addEventListener('switch-view', (event) => {
                const nextView = event?.detail?.to;
                const isRestoring = Boolean(event?.detail?.restoring) || this.isRestoring;

                if (!nextView) {
                    return;
                }

                if (nextView === 'main-menu') {
                    this.isGateMenuTransition = true;
                    this.closeFontScaleOverlay();
                    if (this.views?.['athkar-app-gate']) {
                        this.views['athkar-app-gate'].isReaderVisible = false;
                    }
                    this.resetReaderState();
                    this.syncReaderScreenAwakeLock();
                    if (isRestoring) {
                        this.isRestoring = false;
                    }
                    return;
                }

                if (nextView === 'athkar-app-gate') {
                    this.isGateMenuTransition = !this.activeMode;
                    this.closeFontScaleOverlay();
                    if (this.views?.['athkar-app-gate']) {
                        this.views['athkar-app-gate'].isReaderVisible = false;
                    }
                    this.isNoticeVisible = false;
                    this.softCloseMode();
                    this.syncReaderScreenAwakeLock();
                    if (isRestoring) {
                        this.isRestoring = false;
                    }
                    return;
                }

                if (nextView === 'athkar-app-sabah') {
                    this.isGateMenuTransition = false;
                    this.closeFontScaleOverlay();
                    if (isRestoring && this.activeMode === 'sabah') {
                        this.restoreMode('sabah');
                        this.syncReaderScreenAwakeLock();
                        this.isRestoring = false;
                        return;
                    }
                    this.startModeNotice('sabah', { respectLock: false });
                    this.syncReaderScreenAwakeLock();
                    if (isRestoring) {
                        this.isRestoring = false;
                    }
                    return;
                }

                if (nextView === 'athkar-app-masaa') {
                    this.isGateMenuTransition = false;
                    this.closeFontScaleOverlay();
                    if (isRestoring && this.activeMode === 'masaa') {
                        this.restoreMode('masaa');
                        this.syncReaderScreenAwakeLock();
                        this.isRestoring = false;
                        return;
                    }
                    this.startModeNotice('masaa', { respectLock: false });
                    this.syncReaderScreenAwakeLock();
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
                this.clearTapAura();
                this.unregisterNativeVolumeNavigation();
                if (this._onAthkarFontScaleToggle) {
                    window.removeEventListener(
                        'athkar-reader-font-scale-toggle',
                        this._onAthkarFontScaleToggle,
                    );
                    this._onAthkarFontScaleToggle = null;
                }
                if (this._onNativeBridgeReady) {
                    window.removeEventListener('native-bridge-ready', this._onNativeBridgeReady);
                    this._onNativeBridgeReady = null;
                }
                this.clearOriginTransitionTimer();
                this.releaseReaderScreenAwakeLock();
            });

            this.setupTextFit();
            this.registerNativeVolumeNavigation();
            this.syncNativeVolumeNavigation();
            this._onNativeBridgeReady = () => {
                this.syncNativeVolumeNavigation();
            };
            window.addEventListener('native-bridge-ready', this._onNativeBridgeReady);
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
                this.clearTapAura();
                this.hideOrigin();
                this.queueTextFit();
                this.syncNativeVolumeNavigation();
                this.syncReaderScreenAwakeLock();
            });
            this.$watch('activeIndex', () => {
                this.resetMaintenanceTapTracking();
                this.resetRapidTapMode();
                this.closeHint();
                if (
                    this.tapAura?.isHolding ||
                    this.tapAura?.clickActive ||
                    this.tapAura?.releaseActive
                ) {
                    this.tapAura.index = this.activeIndex;
                } else {
                    this.clearTapAura();
                }
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
                    } else {
                        this.closeFontScaleOverlay();
                    }

                    this.syncNativeVolumeNavigation();
                    this.syncReaderScreenAwakeLock();
                },
            );
            this.$watch('isNoticeVisible', (isNoticeVisible) => {
                if (isNoticeVisible) {
                    this.closeFontScaleOverlay();
                }

                if (!isNoticeVisible && this.views?.['athkar-app-gate']?.isReaderVisible) {
                    this.queueReaderTextFit();
                }

                this.syncNativeVolumeNavigation();
                this.syncReaderScreenAwakeLock();
            });
            this.$watch('isCompletionVisible', () => {
                if (this.isCompletionVisible) {
                    this.closeFontScaleOverlay();
                }

                this.syncNativeVolumeNavigation();
                this.syncReaderScreenAwakeLock();
            });

            this.syncReaderScreenAwakeLock();
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
                this.clearTapAura();
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

        resolveMainTextSizeLimitsFor(settingKey) {
            const limits = this.mainTextSizeLimits?.[settingKey];
            const fallbackMin = 14;
            const fallbackMax = 28;
            const fallbackDefault = settingKey === minimumMainTextSizeKey ? 24 : 25;
            const minimum = Math.trunc(Number(limits?.min ?? fallbackMin));
            const maximumSeed = Math.trunc(Number(limits?.max ?? fallbackMax));
            const maximum = Math.max(minimum, maximumSeed);
            const defaultSeed = Math.trunc(Number(limits?.default ?? fallbackDefault));

            return {
                min: minimum,
                max: maximum,
                default: Math.max(minimum, Math.min(maximum, defaultSeed)),
            };
        },

        normalizeMainTextSizeValue(settingKey, value, fallback = null) {
            const limits = this.resolveMainTextSizeLimitsFor(settingKey);
            const fallbackValue = fallback === null ? limits.default : Number(fallback);
            const numericValue = Number(value ?? fallbackValue);
            const safeValue = Number.isFinite(numericValue)
                ? Math.trunc(numericValue)
                : Math.trunc(fallbackValue);

            return Math.max(limits.min, Math.min(limits.max, safeValue));
        },

        resolveMainTextSizeRange() {
            const minimumLimits = this.resolveMainTextSizeLimitsFor(minimumMainTextSizeKey);
            const maximumLimits = this.resolveMainTextSizeLimitsFor(maximumMainTextSizeKey);
            const minimumValue = this.normalizeMainTextSizeValue(
                minimumMainTextSizeKey,
                this.settings?.[minimumMainTextSizeKey],
                minimumLimits.default,
            );
            const maximumValue = this.normalizeMainTextSizeValue(
                maximumMainTextSizeKey,
                this.settings?.[maximumMainTextSizeKey],
                maximumLimits.default,
            );
            const minimum = Math.min(minimumValue, maximumValue);
            const maximum = Math.max(maximumValue, minimumValue);

            return {
                minimum,
                maximum,
                minimumLimits,
                maximumLimits,
            };
        },

        resolveMainTextSizeSliderLimits() {
            const minimumLimits = this.resolveMainTextSizeLimitsFor(minimumMainTextSizeKey);
            const maximumLimits = this.resolveMainTextSizeLimitsFor(maximumMainTextSizeKey);
            const minimum = Math.max(minimumLimits.min, maximumLimits.min);
            const maximum = Math.max(minimum, Math.min(minimumLimits.max, maximumLimits.max));
            const defaultValue = Math.max(minimum, Math.min(maximum, maximumLimits.default));

            return {
                min: minimum,
                max: maximum,
                default: defaultValue,
            };
        },

        mainTextSizeMinimumValue() {
            return this.resolveMainTextSizeRange().minimum;
        },

        mainTextSizeMaximumValue() {
            return this.resolveMainTextSizeRange().maximum;
        },

        mainTextSizeValue() {
            const sliderLimits = this.resolveMainTextSizeSliderLimits();
            const { maximum } = this.resolveMainTextSizeRange();

            return Math.max(sliderLimits.min, Math.min(sliderLimits.max, maximum));
        },

        updateMainTextSizeRange(nextValue, { persist = false } = {}) {
            const sliderLimits = this.resolveMainTextSizeSliderLimits();
            const normalizedValue = this.normalizeMainTextSizeValue(
                maximumMainTextSizeKey,
                nextValue,
                this.mainTextSizeValue(),
            );
            const nextMainTextSize = Math.max(
                sliderLimits.min,
                Math.min(sliderLimits.max, normalizedValue),
            );

            this.settings = {
                ...(this.settings && typeof this.settings === 'object' ? this.settings : {}),
                [minimumMainTextSizeKey]: nextMainTextSize,
                [maximumMainTextSizeKey]: nextMainTextSize,
            };

            this.persistMainTextSizeRangeSnapshot();

            if (persist) {
                this.applySettings({
                    [minimumMainTextSizeKey]: nextMainTextSize,
                    [maximumMainTextSizeKey]: nextMainTextSize,
                });
                return;
            }

            this.queueTextFit();
            this.queueReaderTextFit();
        },

        persistMainTextSizeRangeSnapshot() {
            const { minimum, maximum } = this.resolveMainTextSizeRange();

            writeUserSettingOverride(minimumMainTextSizeKey, minimum);
            writeUserSettingOverride(maximumMainTextSizeKey, maximum);
            writeAthkarSettingsToStorage(this.settings, this.settingsDefaults);
        },

        handleMainTextSizeInput(event = null) {
            this.updateMainTextSizeRange(event?.target?.value ?? null, {
                persist: false,
            });
        },

        handleMinimumMainTextSizeInput(event = null) {
            this.handleMainTextSizeInput(event);
        },

        handleMaximumMainTextSizeInput(event = null) {
            this.handleMainTextSizeInput(event);
        },

        commitMainTextSizeValue() {
            const nextMainTextSize = this.mainTextSizeValue();

            this.applySettings({
                [minimumMainTextSizeKey]: nextMainTextSize,
                [maximumMainTextSizeKey]: nextMainTextSize,
            });
        },

        commitMainTextSizeRange() {
            this.commitMainTextSizeValue();
        },

        resetMainTextSizeRangeToDefaults() {
            const sliderLimits = this.resolveMainTextSizeSliderLimits();
            const defaultMainTextSize = sliderLimits.default;

            this.settings = {
                ...(this.settings && typeof this.settings === 'object' ? this.settings : {}),
                [minimumMainTextSizeKey]: defaultMainTextSize,
                [maximumMainTextSizeKey]: defaultMainTextSize,
            };

            this.commitMainTextSizeRange();
        },

        toggleFontScaleOverlay() {
            if (
                !this.activeMode ||
                !this.views?.['athkar-app-gate']?.isReaderVisible ||
                this.isNoticeVisible ||
                this.isCompletionVisible
            ) {
                return;
            }

            this.isFontScaleOverlayVisible = !this.isFontScaleOverlayVisible;
            window.dispatchEvent(
                new CustomEvent('athkar-reader-font-scale-overlay-visibility', {
                    detail: {
                        open: this.isFontScaleOverlayVisible,
                    },
                }),
            );
        },

        closeFontScaleOverlay() {
            if (!this.isFontScaleOverlayVisible) {
                return;
            }

            this.isFontScaleOverlayVisible = false;
            window.dispatchEvent(
                new CustomEvent('athkar-reader-font-scale-overlay-visibility', {
                    detail: {
                        open: false,
                    },
                }),
            );
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

        shouldKeepAthkarReaderScreenAwake() {
            return (
                Boolean(this.activeMode) &&
                Boolean(this.views?.['athkar-app-gate']?.isReaderVisible)
            );
        },

        syncReaderScreenAwakeLock() {
            if (!this.shouldKeepAthkarReaderScreenAwake()) {
                this.releaseReaderScreenAwakeLock();

                return;
            }

            if (this._readerScreenAwakeLockToken) {
                return;
            }

            this._readerScreenAwakeLockToken = acquireScreenAwakeLock();
        },

        releaseReaderScreenAwakeLock() {
            if (!this._readerScreenAwakeLockToken) {
                return;
            }

            releaseScreenAwakeLock(this._readerScreenAwakeLockToken);
            this._readerScreenAwakeLockToken = null;
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

        topUiDisplayRequiredCount(index) {
            if (typeof this.topUi.requiredOverride === 'number') {
                return Math.max(0, Math.trunc(this.topUi.requiredOverride));
            }

            return this.requiredCount(index);
        },

        topUiDisplayCount(index) {
            if (typeof this.topUi.countOverride === 'number') {
                return Math.max(0, Math.trunc(this.topUi.countOverride));
            }

            return this.countAt(index);
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
            this.topUi.countOverride = null;
            this.topUi.requiredOverride = null;
            this.topUi.pulseActive = false;
        },

        startTopUiCompletionTransition(
            completedIndex,
            nextIndex,
            { skipIndexSwitch = false } = {},
        ) {
            if (!this.activeMode) {
                return;
            }

            const completedCount = this.countAt(completedIndex);
            const completedRequired = this.requiredCount(completedIndex);
            const nextRequired = this.requiredCount(nextIndex);
            const shouldDelayCountMorphUntilPanelPulse =
                completedRequired <= 1 && nextRequired <= 1 && !this.isMobileViewport();

            this.resetTopUiTransition();

            if (!skipIndexSwitch) {
                this.setActiveIndex({
                    index: nextIndex,
                    preserveTopUiTransition: true,
                });
            }

            if (completedRequired <= 1 && nextRequired <= 1 && this.isMobileViewport()) {
                return;
            }

            const nextCount = this.countAt(nextIndex);
            const shouldHoldCompletedDigitsUntilPulse = completedRequired <= 1 && nextRequired <= 1;

            if (shouldHoldCompletedDigitsUntilPulse) {
                this.triggerCountPulse(nextIndex, nextCount, completedCount);
            }

            this.topUi.progressOverride = 100;

            if (shouldHoldCompletedDigitsUntilPulse) {
                this.topUi.requiredOverride = completedRequired;
                this.topUi.countOverride = completedCount;
            } else if (completedRequired !== nextRequired) {
                this.triggerRequiredPulse(completedRequired, nextRequired);
            }

            this.topUi.lingerTimer = setTimeout(() => {
                this.topUi.lingerTimer = null;
                this.topUi.pulseActive = true;

                if (shouldHoldCompletedDigitsUntilPulse) {
                    if (completedRequired !== nextRequired) {
                        this.triggerRequiredPulse(completedRequired, nextRequired);
                    }
                    this.topUi.requiredOverride = null;
                    this.topUi.countOverride = null;
                }

                if (shouldDelayCountMorphUntilPanelPulse) {
                    this.triggerCountPulse(nextIndex, completedCount, nextCount);
                }

                this.topUi.pulseTimer = setTimeout(() => {
                    this.topUi.pulseTimer = null;
                    this.topUi.pulseActive = false;
                    this.topUi.progressOverride = null;
                }, this.topUiPulseDurationMs);
            }, this.topUiCompletionLingerMs);

            if (!shouldDelayCountMorphUntilPanelPulse) {
                this.triggerCountPulse(nextIndex, completedCount, nextCount);
            }
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
