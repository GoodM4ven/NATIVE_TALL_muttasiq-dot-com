import {
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
} from '../athkar-app-overrides';
import { createShimmerController } from '../shimmer';

const doesEnableVisualEnhancementsKey = 'enable_visual_enhancements';
const skipGuidancePanelsSettingKey = 'does_skip_notice_panels';
const progressStorageKey = 'athkar-progress-v1';
const supportUnlockStorageKey = 'quran-support-unlock-v1';
const supportUnlockModePermanent = 'permanent';
const supportUnlockModeWeekly = 'weekly';
const athkarCopyHoldDelayMs = 1250;
const athkarCopyHoldMoveThresholdPx = 16;
const athkarCopyPopoverVisibleDurationMs = 920;

const defaultProgressState = () => ({
    sabah: { index: 0, counts: [], ids: [], activeId: null },
    masaa: { index: 0, counts: [], ids: [], activeId: null },
});

const emptyProgressStats = Object.freeze({
    totalRequiredCount: 0,
    totalCompletedCount: 0,
    totalRequiredLetters: 0,
    totalCompletedLetters: 0,
    totalRemainingLetters: 0,
    slideProgressPercent: 0,
    maxNavigableIndex: 0,
});

const resolveProgressStatsSafely = (context) => {
    if (typeof context?.resolveProgressStats !== 'function') {
        return emptyProgressStats;
    }

    try {
        return context.resolveProgressStats() ?? emptyProgressStats;
    } catch (_) {
        return emptyProgressStats;
    }
};

const readProgressFromStorage = () => {
    if (typeof localStorage === 'undefined') {
        return defaultProgressState();
    }

    try {
        const parsed = JSON.parse(localStorage.getItem(progressStorageKey) ?? 'null');

        if (!parsed || typeof parsed !== 'object') {
            return defaultProgressState();
        }

        return {
            sabah:
                parsed.sabah && typeof parsed.sabah === 'object'
                    ? {
                          index: Number(parsed.sabah.index ?? 0),
                          counts: Array.isArray(parsed.sabah.counts) ? parsed.sabah.counts : [],
                          ids: Array.isArray(parsed.sabah.ids) ? parsed.sabah.ids : [],
                          activeId: parsed.sabah.activeId ?? null,
                      }
                    : { index: 0, counts: [], ids: [], activeId: null },
            masaa:
                parsed.masaa && typeof parsed.masaa === 'object'
                    ? {
                          index: Number(parsed.masaa.index ?? 0),
                          counts: Array.isArray(parsed.masaa.counts) ? parsed.masaa.counts : [],
                          ids: Array.isArray(parsed.masaa.ids) ? parsed.masaa.ids : [],
                          activeId: parsed.masaa.activeId ?? null,
                      }
                    : { index: 0, counts: [], ids: [], activeId: null },
        };
    } catch (_) {
        return defaultProgressState();
    }
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('athkarAppReader', (config) => ({
        nativeRuntime: Boolean(config?.nativeRuntime ?? false),
        defaultAthkar: normalizeAthkarDefaults(config.athkar),
        athkarOverrides: window.Alpine.$persist([]).as(athkarOverridesStorageKey),
        athkar: [],
        settingsDefaults: config.athkarSettings,
        mainTextSizeLimits: config.athkarMainTextSizeLimits ?? {},
        typeLabels: config.typeLabels ?? {},
        settings: resolveEffectiveSettings(config.athkarSettings),
        activeMode: window.Alpine.$persist(null).as('athkar-active-mode'),
        isCompletionVisible: false,
        isNoticeVisible: window.Alpine.$persist(false).as('athkar-notice-visible'),
        isRestoring: true,
        completionHack: {
            isVisible: false,
            isPinned: false,
            isArmed: false,
            canHover: false,
        },
        completionTimer: null,
        swipe: {
            startX: 0,
            startY: 0,
            active: false,
            ignoreClick: false,
            startedOnTap: false,
            startedInScrollableText: false,
            pointerId: null,
            pointerType: null,
            source: null,
        },
        textScroll: {
            active: false,
            source: null,
            startY: 0,
            startScrollTop: 0,
            pointerId: null,
            element: null,
        },
        nav: {
            isActive: false,
            hoverIndex: null,
            dragIndex: null,
            pointerId: null,
            hasInteracted: false,
            isHovering: false,
            suppressUntil: 0,
        },
        slide: {
            isActive: false,
            direction: null,
            timer: null,
        },
        countPulse: {
            index: null,
            isActive: false,
            timer: null,
            segments: [],
            hasChanges: false,
        },
        pagePulse: {
            isActive: false,
            direction: null,
            timer: null,
            segments: [],
            hasChanges: false,
        },
        totalPulse: {
            isActive: false,
            timer: null,
            segments: [],
            hasChanges: false,
        },
        tapPulse: {
            index: null,
            isActive: false,
            timer: null,
        },
        originToggle: {
            mode: null,
            index: null,
        },
        copyFeedback: {
            visible: false,
            x: 0,
            y: 0,
            timer: null,
            serial: 0,
        },
        copyHold: {
            active: false,
            pointerId: null,
            source: null,
            startX: 0,
            startY: 0,
            index: null,
            target: null,
            triggered: false,
            anchor: null,
        },
        layerScrollOffsets: {},
        topUi: {
            progressOverride: null,
            pulseActive: false,
            lingerTimer: null,
            pulseTimer: null,
        },
        textFit: {
            raf: null,
            settleTimer: null,
        },
        maintenance: {
            tapInterval: 10,
            minimumRequiredCount: 80,
            sequentialTapCount: 0,
            mode: null,
            index: null,
        },
        rapidTap: {
            isActive: false,
            lastTapAt: 0,
            burstCount: 0,
            windowMs: 220,
            threshold: 7,
            holdMs: 900,
            minimumRequiredCount: 40,
            releaseTimer: null,
        },
        textShimmerController: null,
        isFastUiMode: window.__APP_BROWSER_TEST_FAST_UI === true,
        hintIndex: null,
        isMobileCounterOpen: false,
        readerLeaveMs: 300,
        slideDurationMs: 900,
        transitionMode: null,
        transitionDistance: '1.5rem',
        isGateMenuTransition: true,
        pulseDurationMs: 520,
        topUiCompletionLingerMs: 1000,
        topUiPulseDurationMs: 360,
        originResyncDelayMs: 180,
        completionVisibleMs: 3000,
        textFitSettleMs: 96,
        renderWindowRadius: 1,
        _letterCountCache: new Map(),
        _activeListCache: {
            mode: null,
            athkarVersion: -1,
            list: [],
        },
        _modeListCache: {
            sabah: { athkarVersion: -1, list: [] },
            masaa: { athkarVersion: -1, list: [] },
        },
        _progressStatsCache: {
            key: null,
            value: null,
        },
        _navGradientCache: {
            key: null,
            value: null,
        },
        _progressRevision: 0,
        _completionRevision: 0,
        _modeMetrics: {
            sabah: null,
            masaa: null,
        },
        _athkarVersion: 0,
        _persistTimer: null,
        _copyHoldTimer: null,
        _onWindowNativeVolumeButton: null,
        lastSeenDay: window.Alpine.$persist(null).as('athkar-last-day'),
        progress: defaultProgressState(),
        completedOn: window.Alpine.$persist({
            sabah: null,
            masaa: null,
        }).as('athkar-completed-v1'),
        init() {
            if (this.isFastUiMode) {
                this.readerLeaveMs = 40;
                this.slideDurationMs = 120;
                this.pulseDurationMs = 80;
                this.topUiCompletionLingerMs = 80;
                this.topUiPulseDurationMs = 120;
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
            window.addEventListener('resize', () => this.refreshCompletionInputMode());
            window.addEventListener('orientationchange', () => this.refreshCompletionInputMode());
            window.addEventListener('focus', () => this.syncDay());
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
                    this.confirmNotice();
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
                this.next();

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
                (this.countPulse.index === this.activeIndex && this.countPulse.hasChanges)
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

            this.resetTopUiTransition();
            this.setActiveIndex({
                index: nextIndex,
                preserveTopUiTransition: true,
            });

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
        ensureState() {
            if (!this.progress || typeof this.progress !== 'object') {
                this.progress = {
                    sabah: { index: 0, counts: [], ids: [], activeId: null },
                    masaa: { index: 0, counts: [], ids: [], activeId: null },
                };
            }

            ['sabah', 'masaa'].forEach((mode) => {
                if (!this.progress[mode] || typeof this.progress[mode] !== 'object') {
                    this.progress[mode] = { index: 0, counts: [], ids: [], activeId: null };

                    return;
                }

                if (!Array.isArray(this.progress[mode].counts)) {
                    this.progress[mode].counts = [];
                }

                if (!Array.isArray(this.progress[mode].ids)) {
                    this.progress[mode].ids = [];
                }

                if (!('activeId' in this.progress[mode])) {
                    this.progress[mode].activeId = null;
                }

                if (!Number.isFinite(this.progress[mode].index)) {
                    this.progress[mode].index = Number(this.progress[mode].index ?? 0);
                }
            });

            if (!this.completedOn || typeof this.completedOn !== 'object') {
                this.completedOn = { sabah: null, masaa: null };
            }

            if (!('sabah' in this.completedOn)) {
                this.completedOn.sabah = null;
            }

            if (!('masaa' in this.completedOn)) {
                this.completedOn.masaa = null;
            }
        },
        persistProgress() {
            if (this._persistTimer !== null) {
                clearTimeout(this._persistTimer);
            }

            this._persistTimer = setTimeout(() => {
                this._persistTimer = null;
                this._flushProgress();
            }, 150);
        },
        _flushProgress() {
            if (typeof localStorage === 'undefined') {
                return;
            }

            try {
                localStorage.setItem(progressStorageKey, JSON.stringify(this.progress));
            } catch (_) {
                //
            }
        },
        todayKey() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');

            return `${year}-${month}-${day}`;
        },
        syncDay() {
            const today = this.todayKey();

            if (this.lastSeenDay !== today) {
                this.lastSeenDay = today;
                this.completedOn = { sabah: null, masaa: null };
                this.resetProgress('sabah');
                this.resetProgress('masaa');
                this.resetReaderState();

                if (!this.views?.['main-menu']?.isOpen) {
                    if (this.views?.['athkar-app-gate']) {
                        this.views['athkar-app-gate'].isReaderVisible = false;
                    }

                    window.dispatchEvent(
                        new CustomEvent('switch-view', {
                            detail: { to: 'athkar-app-gate', reason: 'day-change' },
                        }),
                    );
                }
            }
        },
        athkarFor(mode) {
            return this.athkar.filter((item) => item.time === 'shared' || item.time === mode);
        },
        resetProgress(mode) {
            const list = this.getModeList(mode);
            const listIds = list.map((item) => item?.id ?? null);

            this.progress[mode] = {
                index: 0,
                counts: Array.from({ length: list.length }, () => 0),
                ids: listIds,
                activeId: listIds[0] ?? null,
            };
            this.invalidateModeMetrics(mode);
            this.persistProgress();
        },
        ensureProgress(mode) {
            const list = this.athkarFor(mode);
            const listIds = list.map((item) => item?.id ?? null);
            const normalizeId = (value) => {
                if (value === null || value === undefined) {
                    return null;
                }

                return String(value);
            };

            if (!this.progress[mode]) {
                this.resetProgress(mode);

                return;
            }

            const counts = Array.isArray(this.progress[mode].counts)
                ? this.progress[mode].counts
                : [];
            const storedIds = Array.isArray(this.progress[mode].ids) ? this.progress[mode].ids : [];
            const hasStoredIds = storedIds.length > 0;
            const countForId = new Map();

            if (hasStoredIds) {
                storedIds.forEach((id, index) => {
                    const normalizedId = normalizeId(id);

                    if (normalizedId === null) {
                        return;
                    }

                    countForId.set(normalizedId, counts[index]);
                });
            }

            const normalizeCount = (value) => {
                const count = Number(value ?? 0);

                if (!Number.isFinite(count) || count < 0) {
                    return 0;
                }

                return count;
            };

            this.progress[mode].counts = listIds.map((id, index) => {
                const normalizedId = normalizeId(id);

                if (hasStoredIds && normalizedId !== null && countForId.has(normalizedId)) {
                    return normalizeCount(countForId.get(normalizedId));
                }

                if (!hasStoredIds) {
                    return normalizeCount(counts[index]);
                }

                return 0;
            });

            this.progress[mode].ids = listIds;

            const maxIndex = Math.max(list.length - 1, 0);
            const activeId = normalizeId(this.progress[mode].activeId);
            const currentIndex = Number(this.progress[mode].index ?? 0);
            const nextIndexById =
                activeId !== null ? listIds.findIndex((id) => normalizeId(id) === activeId) : -1;

            if (nextIndexById >= 0) {
                this.progress[mode].index = nextIndexById;
            } else {
                this.progress[mode].index = Math.min(Math.max(currentIndex, 0), maxIndex);
            }

            this.progress[mode].activeId = listIds[this.progress[mode].index] ?? null;
            this.progress = {
                ...this.progress,
                [mode]: {
                    ...this.progress[mode],
                },
            };
            this.invalidateModeMetrics(mode);
            this.persistProgress();
        },
        isModeLocked(mode) {
            if (!this.shouldPreventSwitching()) {
                return false;
            }

            return this.isModeComplete(mode);
        },
        isModeComplete(mode) {
            return this.completedOn?.[mode] === this.todayKey();
        },
        resumeModeIndex() {
            if (!this.activeMode || !this.activeList.length) {
                return;
            }

            if (!this.shouldPreventSwitching()) {
                return;
            }

            const currentIndex = Number(this.progress[this.activeMode]?.index ?? 0);
            const targetIndex = this.maxNavigableIndex;

            if (!Number.isFinite(currentIndex) || !Number.isFinite(targetIndex)) {
                return;
            }

            if (currentIndex < targetIndex) {
                this.progress[this.activeMode].index = targetIndex;
                this.progress[this.activeMode].activeId = this.activeList[targetIndex]?.id ?? null;
                this.persistProgress();
            }
        },
        activateMode(mode, { updateHash = false, respectLock = true } = {}) {
            this.ensureState();
            this.syncDay();

            if (respectLock && this.isModeLocked(mode)) {
                return false;
            }

            this.ensureProgress(mode);
            this.transitionMode = mode;
            this.activeMode = mode;
            this.resumeModeIndex();

            if (updateHash) {
                this.$viewNav('athkar-app-' + mode);
            }

            this.nav.suppressUntil = performance.now() + 250;

            if (this.shouldPreventSwitching() && this.activeIndex > this.maxNavigableIndex) {
                this.progress[this.activeMode].index = this.maxNavigableIndex;
                this.progress[this.activeMode].activeId =
                    this.activeList[this.maxNavigableIndex]?.id ?? null;
                this.persistProgress();
            }

            this.resetNavState();
            this.closeHint();
            this.resetSwipeState();
            this.syncNativeVolumeNavigation();

            return true;
        },
        startModeNotice(mode, { updateHash = false, respectLock = true } = {}) {
            const didActivate = this.activateMode(mode, { updateHash, respectLock });

            if (!didActivate) {
                return;
            }

            if (this.shouldSkipGuidancePanels()) {
                this.confirmNotice();
                return;
            }

            this.showNotice();
        },
        openMode(mode) {
            this.startModeNotice(mode, { updateHash: true });
        },
        restoreMode(mode) {
            const didActivate = this.activateMode(mode, { updateHash: false, respectLock: false });

            if (!didActivate) {
                return;
            }

            if (this.isNoticeVisible) {
                this.showNotice();
                return;
            }

            if (this.views?.['athkar-app-gate']?.isReaderVisible) {
                this.confirmNotice();
                return;
            }

            this.showNotice();
        },
        showNotice() {
            if (!this.activeMode) {
                return;
            }

            if (this.shouldSkipGuidancePanels()) {
                this.confirmNotice();
                return;
            }

            this.isNoticeVisible = true;

            if (this.views?.['athkar-app-gate']) {
                this.views['athkar-app-gate'].isReaderVisible = false;
            }

            this.$nextTick(() => this.queueTextFit());
            this.syncNativeVolumeNavigation();
        },
        confirmNotice() {
            if (!this.activeMode) {
                return;
            }

            this.isNoticeVisible = false;
            this.closeHint();
            this.resetSwipeState();

            if (this.views?.['athkar-app-gate']) {
                this.views['athkar-app-gate'].isReaderVisible = true;
            }

            this.ensureProgress(this.activeMode);
            this.resumeModeIndex();
            this.$nextTick(() => this.queueTextFit());
            this.queueReaderTextFit();
            this.syncNativeVolumeNavigation();
        },
        returnToGateFromNotice() {
            this.isNoticeVisible = false;

            if (this.views?.['athkar-app-gate']) {
                this.views['athkar-app-gate'].isReaderVisible = false;
            }

            this.closeMode();
            this.syncNativeVolumeNavigation();
        },
        openGateAndManageAthkar() {
            if (!this.activeMode) {
                return;
            }

            if (this.views?.['athkar-app-gate']) {
                this.views['athkar-app-gate'].isReaderVisible = false;
            }

            this.softCloseMode();
            this.$viewNav('athkar-app-gate', { force: true });

            window.setTimeout(() => {
                window.dispatchEvent(new CustomEvent('open-athkar-manager'));
            }, this.readerLeaveMs + 90);
        },
        closeMode() {
            const previousHash = window.history.state?.__hashActionPrev;

            if (previousHash === '#athkar-app-gate') {
                window.history.back();
            } else {
                this.$viewNav('athkar-app-gate', { force: false });
            }

            this.softCloseMode();
        },
        softCloseMode() {
            this.isNoticeVisible = false;
            this.resetMaintenanceTapTracking();
            this.resetRapidTapMode();
            this.resetTopUiTransition();
            this.closeHint();
            this.resetSwipeState();
            this.hideCompletionHack({ force: true });
            this.resetNavState();
            this.stopTextShimmer();
            this.hideCopyFeedback();
            this.cancelHoldCopy();
            this.syncNativeVolumeNavigation();

            setTimeout(() => {
                if (
                    !this.views[`athkar-app-gate`].isReaderVisible &&
                    !this.isNoticeVisible &&
                    !this.isCompletionVisible
                ) {
                    this.activeMode = null;
                }
            }, this.readerLeaveMs);
        },
        resetReaderState() {
            if (this.completionTimer) {
                clearTimeout(this.completionTimer);
                this.completionTimer = null;
            }

            const lastMode = this.activeMode ?? this.transitionMode;

            this.isCompletionVisible = false;
            this.isNoticeVisible = false;
            this.activeMode = null;
            this.transitionMode = lastMode;
            this.resetMaintenanceTapTracking();
            this.resetRapidTapMode();
            this.resetTopUiTransition();
            this.closeHint();
            this.resetSwipeState();
            this.stopTextShimmer();
            this.hideCompletionHack({ force: true });
            this.resetNavState();
            this.hideCopyFeedback();
            this.cancelHoldCopy();
            this.syncNativeVolumeNavigation();

            if (!lastMode) {
                return;
            }

            setTimeout(() => {
                if (!this.activeMode) {
                    this.transitionMode = null;
                }
            }, this.readerLeaveMs);
        },
        transitionDirection(mode) {
            if (mode === 'sabah') {
                return 'left';
            }

            if (mode === 'masaa') {
                return 'right';
            }

            return null;
        },
        transitionStyles() {
            const mode = this.transitionMode ?? this.activeMode;
            const direction = this.transitionDirection(mode);

            if (!direction) {
                return {
                    '--athkar-shift-x': '0px',
                    '--athkar-shift-y': this.transitionDistance,
                };
            }

            return {
                '--athkar-shift-x':
                    direction === 'right' ? this.transitionDistance : `-${this.transitionDistance}`,
                '--athkar-shift-y': '0px',
            };
        },
        gateTransitionStyles() {
            if (this.isGateMenuTransition) {
                return {
                    '--athkar-shift-x': '0px',
                    '--athkar-shift-y': this.transitionDistance,
                };
            }

            return this.transitionStyles();
        },
        showCompletionHack({ pinned = false, armed = null } = {}) {
            this.completionHack.isVisible = true;
            this.completionHack.isPinned = pinned;
            if (!this.completionHack.canHover) {
                this.completionHack.isArmed = armed ?? true;
            }
        },
        refreshCompletionInputMode() {
            const supportsHover = window.matchMedia
                ? window.matchMedia('(hover: hover) and (pointer: fine)').matches
                : false;
            const isTouchContext =
                this.$store?.bp?.isTouch?.() ?? Number(navigator.maxTouchPoints) > 0;
            const canHover = supportsHover && !isTouchContext;

            this.completionHack.canHover = canHover;

            if (canHover) {
                this.completionHack.isArmed = false;
                return;
            }

            if (this.completionHack.isVisible) {
                this.completionHack.isArmed = true;
            }
        },
        hideCompletionHack({ force = false } = {}) {
            if (!force && this.completionHack.isPinned) {
                return;
            }

            this.completionHack.isVisible = false;
            this.completionHack.isPinned = false;
            this.completionHack.isArmed = false;
        },
        toggleCompletionHack() {
            if (this.completionHack.canHover) {
                return;
            }

            if (this.completionHack.isVisible) {
                this.hideCompletionHack({ force: true });
                return;
            }

            this.showCompletionHack({ pinned: true });
        },
        get activeLabel() {
            return this.activeMode === 'sabah' ? 'أذكار الصباح' : 'أذكار المساء';
        },
        defaultType() {
            const [firstType] = Object.keys(this.typeLabels ?? {});

            return firstType ?? 'glorification';
        },
        typeLabelFor(type) {
            const normalizedType = String(type ?? this.defaultType());

            return (
                this.typeLabels?.[normalizedType] ?? this.typeLabels?.[this.defaultType()] ?? 'عام'
            );
        },
        activeTypeLabel(index) {
            return this.typeLabelFor(this.activeList?.[index]?.type);
        },
        scrollLayerKey(index, target, mode = this.activeMode) {
            if (!mode) {
                return null;
            }

            const normalizedIndex = Number(index ?? -1);

            if (!Number.isFinite(normalizedIndex) || normalizedIndex < 0) {
                return null;
            }

            const normalizedTarget = target === 'origin' ? 'origin' : 'text';
            const itemId = this.getModeList(mode)?.[normalizedIndex]?.id ?? normalizedIndex;

            return `${mode}:${itemId}:${normalizedTarget}`;
        },
        rememberScrollOffset(index, target, scrollTop) {
            const key = this.scrollLayerKey(index, target);

            if (!key) {
                return;
            }

            this.layerScrollOffsets[key] = Math.max(0, Number(scrollTop) || 0);
        },
        resolveRememberedScrollOffset(index, target) {
            const key = this.scrollLayerKey(index, target);

            if (!key) {
                return 0;
            }

            return Math.max(0, Number(this.layerScrollOffsets?.[key]) || 0);
        },
        rememberVisibleTextBoxScroll(index = this.activeIndex) {
            const activeSlide = this.$el?.querySelector('[data-athkar-slide][data-active="true"]');
            const box = activeSlide?.querySelector('[data-athkar-text-box]');

            if (!box) {
                return;
            }

            const target = box.dataset.athkarScrollTarget === 'origin' ? 'origin' : 'text';
            this.rememberScrollOffset(index, target, box.scrollTop);
        },
        resolveOverflowPaddingClasses(box) {
            if (!box) {
                return [];
            }

            const classes = new Set();
            const targets = box.querySelectorAll?.('[data-fitty-target]');

            targets?.forEach((node) => {
                const value = String(node?.dataset?.fittyOverflowPaddingClass ?? 'py-2').trim();

                if (value) {
                    classes.add(value);
                }
            });

            return Array.from(classes);
        },
        syncOverflowPaddingClass({ box, target, isOverflowing }) {
            if (!box) {
                return;
            }

            const paddingClasses = this.resolveOverflowPaddingClasses(box);

            if (!paddingClasses.length) {
                return;
            }

            if (!isOverflowing) {
                paddingClasses.forEach((className) => box.classList.remove(className));

                return;
            }

            const activeSlide = this.$el?.querySelector('[data-athkar-slide][data-active="true"]');
            const activeText = activeSlide?.querySelector(
                target === 'origin' ? '[data-athkar-origin-text]' : '[data-athkar-text]',
            );
            const activePaddingClass = String(
                activeText?.dataset?.fittyOverflowPaddingClass ?? 'py-2',
            ).trim();

            paddingClasses.forEach((className) => {
                box.classList.toggle(className, className === activePaddingClass);
            });
        },
        applyVisibleTextBoxScrollState({ box, index, target, isOverflowing }) {
            if (!box) {
                return;
            }

            if (!isOverflowing) {
                box.scrollTop = 0;
                this.rememberScrollOffset(index, target, 0);
                window.requestAnimationFrame(() => {
                    if (document.contains(box) && box.dataset.athkarTouchScroll !== 'true') {
                        box.scrollTop = 0;
                    }
                });

                return;
            }

            const maxScrollTop = Math.max(0, box.scrollHeight - box.clientHeight);
            const remembered = this.resolveRememberedScrollOffset(index, target);
            const resolvedScrollTop = Math.min(maxScrollTop, remembered);

            box.scrollTop = resolvedScrollTop;
            this.rememberScrollOffset(index, target, resolvedScrollTop);
        },
        hasOrigin(index) {
            const item = this.activeList?.[index];
            const normalizedOrigin = String(item?.origin ?? '').trim();

            return normalizedOrigin.length > 0 || Boolean(item?.is_original);
        },
        isOriginVisible(index) {
            return this.originToggle.mode === this.activeMode && this.originToggle.index === index;
        },
        toggleOrigin(index) {
            if (!this.hasOrigin(index)) {
                return;
            }

            this.rememberVisibleTextBoxScroll(index);

            if (this.isOriginVisible(index)) {
                this.hideOrigin();
            } else {
                this.originToggle = {
                    mode: this.activeMode,
                    index,
                };
            }

            this.$nextTick(() => {
                this.syncVisibleTextBoxState(index);
                this.stopTextShimmer();
                this.queueReaderTextFit();
                this.setupTextShimmer(null, { immediate: true });
                window.setTimeout(() => {
                    this.syncVisibleTextBoxState(index);
                    this.setupTextShimmer(null, { immediate: true });
                }, this.originResyncDelayMs);
            });
        },
        syncVisibleTextBoxState(index = this.activeIndex) {
            const activeSlide = this.$el?.querySelector('[data-athkar-slide][data-active="true"]');
            const box = activeSlide?.querySelector('[data-athkar-text-box]');

            if (!box) {
                return;
            }

            const isOriginTarget = this.isOriginVisible(index);
            const target = isOriginTarget ? 'origin' : 'text';
            const isOverflowing = isOriginTarget
                ? box.dataset.athkarOriginOverflow === 'true'
                : box.dataset.athkarTextOverflow === 'true';

            box.dataset.athkarScrollTarget = target;
            box.dataset.athkarTouchScroll = isOverflowing ? 'true' : 'false';
            box.dataset.athkarTouchOverflow = isOverflowing ? 'true' : 'false';
            box.classList.toggle('athkar-text-box--touch-scroll', isOverflowing);
            box.classList.toggle('athkar-text-box--origin-scroll', isOverflowing && isOriginTarget);
            this.syncOverflowPaddingClass({ box, target, isOverflowing });

            this.applyVisibleTextBoxScrollState({ box, index, target, isOverflowing });
        },
        hideOrigin() {
            this.originToggle = {
                mode: null,
                index: null,
            };
        },
        requestSingleThikrCompletion(index) {
            if (!this.activeMode) {
                return;
            }

            const normalizedIndex = Number(index ?? -1);

            if (!Number.isFinite(normalizedIndex) || normalizedIndex < 0) {
                return;
            }

            window.dispatchEvent(
                new CustomEvent('athkar-open-single-completion', {
                    detail: { index: normalizedIndex },
                }),
            );
        },
        isOriginalThikr(index) {
            return this.hasOrigin(index);
        },
        isSlideInRenderWindow(index) {
            const distance = Math.abs(Number(index) - this.activeIndex);

            return Number.isFinite(distance) && distance <= this.renderWindowRadius;
        },
        getModeList(mode) {
            if (mode !== 'sabah' && mode !== 'masaa') {
                return [];
            }

            const cache = this._modeListCache[mode];

            if (cache.athkarVersion === this._athkarVersion) {
                return cache.list;
            }

            const list = this.athkarFor(mode);
            cache.athkarVersion = this._athkarVersion;
            cache.list = list;

            return list;
        },
        markProgressDirty({ completionChanged = false } = {}) {
            this._progressRevision += 1;
            this._progressStatsCache.key = null;
            this._progressStatsCache.value = null;

            if (completionChanged) {
                this._completionRevision += 1;
                this._navGradientCache.key = null;
                this._navGradientCache.value = null;
            }
        },
        invalidateModeMetrics(mode = null) {
            if (mode === null) {
                this._modeMetrics.sabah = null;
                this._modeMetrics.masaa = null;
                this.markProgressDirty({ completionChanged: true });

                return;
            }

            if (mode !== 'sabah' && mode !== 'masaa') {
                return;
            }

            this._modeMetrics[mode] = null;
            this.markProgressDirty({ completionChanged: true });
        },
        ensureModeMetrics(mode) {
            if (mode !== 'sabah' && mode !== 'masaa') {
                return null;
            }

            if (this._modeMetrics[mode]) {
                return this._modeMetrics[mode];
            }

            const list = this.athkarFor(mode);
            const counts = Array.isArray(this.progress?.[mode]?.counts)
                ? this.progress[mode].counts
                : [];
            let totalRequiredCount = 0;
            let totalCompletedCount = 0;
            let totalRequiredLetters = 0;
            let totalCompletedLetters = 0;
            let firstIncomplete = -1;

            list.forEach((item, index) => {
                const requiredCountSeed = Number(item?.count ?? 1);
                const completedCountSeed = Number(counts[index] ?? 0);
                const requiredCount =
                    Number.isFinite(requiredCountSeed) && requiredCountSeed > 0
                        ? requiredCountSeed
                        : 0;
                const completedCount =
                    Number.isFinite(completedCountSeed) && completedCountSeed > 0
                        ? completedCountSeed
                        : 0;

                totalRequiredCount += requiredCount;
                totalCompletedCount += completedCount;

                const letters = this._cachedLetterCount(item?.text);
                totalRequiredLetters += letters * requiredCount;
                totalCompletedLetters += letters * Math.min(completedCount, requiredCount);

                if (firstIncomplete === -1 && requiredCount > 0 && completedCount < requiredCount) {
                    firstIncomplete = index;
                }
            });

            this._modeMetrics[mode] = {
                totalRequiredCount,
                totalCompletedCount,
                totalRequiredLetters,
                totalCompletedLetters,
                firstIncomplete,
            };

            return this._modeMetrics[mode];
        },
        updateModeMetricsForCountChange(mode, index, previousValue, nextValue, requiredCount) {
            const metrics = this.ensureModeMetrics(mode);

            if (!metrics) {
                return;
            }

            const previousCount =
                Number.isFinite(previousValue) && previousValue > 0 ? previousValue : 0;
            const nextCount = Number.isFinite(nextValue) && nextValue > 0 ? nextValue : 0;
            const required =
                Number.isFinite(requiredCount) && requiredCount > 0 ? requiredCount : 0;
            const deltaCount = nextCount - previousCount;

            if (deltaCount === 0) {
                return;
            }

            metrics.totalCompletedCount += deltaCount;

            const list = this.getModeList(mode);
            const item = list[index];
            const letters = this._cachedLetterCount(item?.text);
            const previousLettersCount = Math.min(previousCount, required);
            const nextLettersCount = Math.min(nextCount, required);

            metrics.totalCompletedLetters += letters * (nextLettersCount - previousLettersCount);

            const wasIncomplete = previousCount < required;
            const isIncomplete = nextCount < required;

            if (wasIncomplete && !isIncomplete && metrics.firstIncomplete === index) {
                const counts = Array.isArray(this.progress?.[mode]?.counts)
                    ? this.progress[mode].counts
                    : [];
                let nextIncomplete = -1;

                for (let cursor = index + 1; cursor < list.length; cursor += 1) {
                    const requiredSeed = Number(list[cursor]?.count ?? 1);
                    const requiredAtCursor =
                        Number.isFinite(requiredSeed) && requiredSeed > 0 ? requiredSeed : 0;
                    const completedSeed = Number(counts[cursor] ?? 0);
                    const completedAtCursor =
                        Number.isFinite(completedSeed) && completedSeed > 0 ? completedSeed : 0;

                    if (requiredAtCursor > 0 && completedAtCursor < requiredAtCursor) {
                        nextIncomplete = cursor;
                        break;
                    }
                }

                metrics.firstIncomplete = nextIncomplete;
            } else if (!wasIncomplete && isIncomplete) {
                if (metrics.firstIncomplete === -1 || index < metrics.firstIncomplete) {
                    metrics.firstIncomplete = index;
                }
            }
        },
        resolveProgressStats() {
            const mode = this.activeMode;
            const activeList = Array.isArray(this.activeList) ? this.activeList : [];

            if (mode !== 'sabah' && mode !== 'masaa') {
                return {
                    totalRequiredCount: 0,
                    totalCompletedCount: 0,
                    totalRequiredLetters: 0,
                    totalCompletedLetters: 0,
                    totalRemainingLetters: 0,
                    slideProgressPercent: 0,
                    maxNavigableIndex: 0,
                };
            }

            const metrics = this.ensureModeMetrics(mode);
            const shouldPreventSwitching = this.shouldPreventSwitching();
            const cacheKey = `${mode}:${this._athkarVersion}:${this._progressRevision}:${shouldPreventSwitching ? 1 : 0}`;

            if (this._progressStatsCache.key === cacheKey && this._progressStatsCache.value) {
                return this._progressStatsCache.value;
            }

            if (!activeList.length) {
                const emptyStats = {
                    totalRequiredCount: 0,
                    totalCompletedCount: 0,
                    totalRequiredLetters: 0,
                    totalCompletedLetters: 0,
                    totalRemainingLetters: 0,
                    slideProgressPercent: 0,
                    maxNavigableIndex: 0,
                };

                this._progressStatsCache.key = cacheKey;
                this._progressStatsCache.value = emptyStats;

                return emptyStats;
            }

            const maxNavigableIndex = shouldPreventSwitching
                ? metrics?.firstIncomplete === -1
                    ? activeList.length - 1
                    : (metrics?.firstIncomplete ?? 0)
                : activeList.length - 1;
            const totalRequiredCount = metrics?.totalRequiredCount ?? 0;
            const totalCompletedCount = metrics?.totalCompletedCount ?? 0;
            const totalRequiredLetters = metrics?.totalRequiredLetters ?? 0;
            const totalCompletedLetters = metrics?.totalCompletedLetters ?? 0;
            const totalRemainingLetters = Math.max(totalRequiredLetters - totalCompletedLetters, 0);
            const slideProgressPercent = totalRequiredLetters
                ? Math.min(
                      100,
                      Math.max(
                          0,
                          Math.round(
                              (Math.min(totalCompletedLetters, totalRequiredLetters) /
                                  totalRequiredLetters) *
                                  100,
                          ),
                      ),
                  )
                : 0;

            const stats = {
                totalRequiredCount,
                totalCompletedCount,
                totalRequiredLetters,
                totalCompletedLetters,
                totalRemainingLetters,
                slideProgressPercent,
                maxNavigableIndex,
            };

            this._progressStatsCache.key = cacheKey;
            this._progressStatsCache.value = stats;

            return stats;
        },
        get activeList() {
            const mode = this.activeMode;

            if (mode !== 'sabah' && mode !== 'masaa') {
                return [];
            }

            if (
                this._activeListCache.mode === mode &&
                this._activeListCache.athkarVersion === this._athkarVersion
            ) {
                return this._activeListCache.list;
            }

            const list = this.getModeList(mode);

            this._activeListCache.mode = mode;
            this._activeListCache.athkarVersion = this._athkarVersion;
            this._activeListCache.list = list;

            return list;
        },
        get activeIndex() {
            const mode = this.activeMode;

            if (mode !== 'sabah' && mode !== 'masaa') {
                return 0;
            }

            const index = Number(this.progress?.[mode]?.index ?? 0);

            if (!Number.isFinite(index) || index < 0) {
                return 0;
            }

            return Math.trunc(index);
        },
        get totalRequiredCount() {
            return resolveProgressStatsSafely(this).totalRequiredCount;
        },
        get totalCompletedCount() {
            return resolveProgressStatsSafely(this).totalCompletedCount;
        },
        textLetterCount(text) {
            const normalized = String(text ?? '');

            if (!normalized) {
                return 0;
            }

            try {
                const letters = normalized.match(/\p{L}/gu);

                return letters ? letters.length : 0;
            } catch (_) {
                const stripped = normalized.replace(/\s+/gu, '');

                return stripped ? Array.from(stripped).length : 0;
            }
        },
        _cachedLetterCount(text) {
            const normalized = String(text ?? '');

            if (!normalized) {
                return 0;
            }

            if (this._letterCountCache.has(normalized)) {
                return this._letterCountCache.get(normalized);
            }

            const count = this.textLetterCount(normalized);
            this._letterCountCache.set(normalized, count);

            return count;
        },
        get totalRequiredLetters() {
            return resolveProgressStatsSafely(this).totalRequiredLetters;
        },
        get totalCompletedLetters() {
            return resolveProgressStatsSafely(this).totalCompletedLetters;
        },
        get totalRemainingLetters() {
            return resolveProgressStatsSafely(this).totalRemainingLetters;
        },
        get slideProgressPercent() {
            return resolveProgressStatsSafely(this).slideProgressPercent;
        },
        get maxNavigableIndex() {
            return resolveProgressStatsSafely(this).maxNavigableIndex;
        },
        settingValue(name, fallback) {
            const value = this.settings?.[name];

            if (typeof value === 'boolean') {
                return value;
            }

            if (value === 1 || value === '1') {
                return true;
            }

            if (value === 0 || value === '0') {
                return false;
            }

            return fallback;
        },
        shouldPreventSwitching() {
            return this.settingValue('does_prevent_switching_athkar_until_completion', true);
        },
        shouldSkipGuidancePanels() {
            return this.settingValue(skipGuidancePanelsSettingKey, false);
        },
        shouldEnableVisualEnhancements() {
            return this.settingValue(doesEnableVisualEnhancementsKey, true);
        },
        shouldExitReaderAfterForwardSwipe() {
            if (this.shouldPreventSwitching()) {
                return false;
            }

            if (!this.activeMode || !this.activeList.length) {
                return false;
            }

            if (this.activeIndex < this.activeList.length - 1) {
                return false;
            }

            return this.isAllComplete() || this.isModeComplete(this.activeMode);
        },
        get navPreviewIndex() {
            const nav = this.nav ?? {};

            return nav.isActive ? nav.dragIndex : nav.hoverIndex;
        },
        navIsRtl() {
            const track = this.$refs?.athkarNav;

            if (!track || !window.getComputedStyle) {
                return true;
            }

            return window.getComputedStyle(track).direction === 'rtl';
        },
        segmentWidthPercent() {
            if (!this.activeList.length) {
                return 100;
            }

            return 100 / this.activeList.length;
        },
        segmentLeftPercent(index) {
            const segment = this.segmentWidthPercent();

            if (this.navIsRtl()) {
                return `${100 - segment * (index + 1)}%`;
            }

            return `${segment * index}%`;
        },
        segmentCenterPercent(index) {
            const segment = this.segmentWidthPercent();

            if (this.navIsRtl()) {
                return `${100 - segment * (index + 0.5)}%`;
            }

            return `${segment * (index + 0.5)}%`;
        },
        get navGradient() {
            const activeList = Array.isArray(this.activeList) ? this.activeList : [];

            if (!activeList.length) {
                return 'linear-gradient(90deg, var(--athkar-nav-pending) 0% 100%)';
            }

            const mode = this.activeMode;
            const counts = Array.isArray(this.progress?.[mode]?.counts)
                ? this.progress[mode].counts
                : [];
            const maxNavigableIndex = Number(this.maxNavigableIndex ?? 0);
            const direction = typeof this.navIsRtl === 'function' && this.navIsRtl() ? 270 : 90;
            const cacheKey = `${mode}:${this._athkarVersion}:${this._completionRevision}:${maxNavigableIndex}:${direction}`;

            if (this._navGradientCache.key === cacheKey && this._navGradientCache.value) {
                return this._navGradientCache.value;
            }

            const segment = 100 / activeList.length;
            const stops = activeList.map((item, index) => {
                const start = (index * segment).toFixed(4);
                const end = ((index + 1) * segment).toFixed(4);
                let color = 'var(--athkar-nav-pending)';

                const isItemComplete =
                    typeof this.isItemComplete === 'function'
                        ? this.isItemComplete(index)
                        : Number(counts[index] ?? 0) >= Number(item?.count ?? 1);

                if (isItemComplete) {
                    color = 'var(--athkar-nav-complete)';
                } else if (index <= maxNavigableIndex) {
                    color = 'var(--athkar-nav-available)';
                }

                return `${color} ${start}% ${end}%`;
            });

            const gradient = `linear-gradient(${direction}deg, ${stops.join(', ')})`;

            this._navGradientCache.key = cacheKey;
            this._navGradientCache.value = gradient;

            return gradient;
        },
        resetNavState() {
            this.nav.isActive = false;
            this.nav.hoverIndex = null;
            this.nav.dragIndex = null;
            this.nav.pointerId = null;
            this.nav.hasInteracted = false;
            this.nav.isHovering = false;
            this.nav.suppressUntil = 0;
        },
        navEnter() {
            this.nav.isHovering = true;
            if (!this.nav.hasInteracted) {
                this.nav.suppressUntil = Math.max(this.nav.suppressUntil, performance.now() + 150);
            }
        },
        navPointerIndex(event) {
            const track = this.$refs?.athkarNav;

            if (!track || !this.activeList.length) {
                return 0;
            }

            const rect = track.getBoundingClientRect();
            const offset = Math.min(Math.max(event.clientX - rect.left, 0), rect.width);
            const rawRatio = rect.width ? offset / rect.width : 0;
            const ratio = this.navIsRtl() ? 1 - rawRatio : rawRatio;
            const rawIndex = Math.min(
                Math.floor(ratio * this.activeList.length),
                this.activeList.length - 1,
            );

            return Math.min(rawIndex, this.maxNavigableIndex);
        },
        navStart(event) {
            if (!this.activeMode || this.isCompletionVisible) {
                return;
            }

            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }

            this.nav.isActive = true;
            this.nav.pointerId = event.pointerId;
            this.nav.hasInteracted = true;
            this.nav.dragIndex = this.navPointerIndex(event);
            this.nav.hoverIndex = this.nav.dragIndex;

            if (event.currentTarget?.setPointerCapture) {
                event.currentTarget.setPointerCapture(event.pointerId);
            }
        },
        navMove(event) {
            if (!this.activeMode) {
                return;
            }

            if (this.nav.isActive) {
                this.nav.dragIndex = this.navPointerIndex(event);

                return;
            }

            if (!this.nav.isHovering) {
                return;
            }

            if (performance.now() < this.nav.suppressUntil) {
                return;
            }

            const movementX = Number.isFinite(event?.movementX) ? Number(event.movementX) : 0;
            const movementY = Number.isFinite(event?.movementY) ? Number(event.movementY) : 0;

            if (!this.nav.hasInteracted) {
                if (Math.abs(movementX) < 1 && Math.abs(movementY) < 1) {
                    return;
                }

                this.nav.hasInteracted = true;
            }

            this.nav.hoverIndex = this.navPointerIndex(event);
        },
        navLeave() {
            if (this.nav.isActive) {
                return;
            }

            this.nav.hoverIndex = null;
            this.nav.isHovering = false;
        },
        navEnd(event) {
            if (!this.nav.isActive) {
                return;
            }

            const index =
                this.nav.dragIndex ?? (event ? this.navPointerIndex(event) : this.activeIndex);

            this.nav.isActive = false;
            this.nav.dragIndex = null;
            this.nav.pointerId = null;
            this.nav.hoverIndex = null;

            this.setActiveIndex(index);
        },
        navCancel() {
            if (!this.nav.isActive) {
                return;
            }

            this.resetNavState();
        },
        setActiveIndex(index) {
            let preserveTopUiTransition = false;

            if (typeof index === 'object' && index !== null) {
                preserveTopUiTransition = Boolean(index.preserveTopUiTransition);
                index = index.index;
            }

            if (!this.activeMode) {
                return;
            }

            const currentIndex = this.activeIndex;
            const maxIndex = Math.max(this.activeList.length - 1, 0);
            const nextIndex = Math.min(Math.max(index, 0), maxIndex);

            if (this.shouldPreventSwitching() && nextIndex > this.maxNavigableIndex) {
                return;
            }

            if (nextIndex === currentIndex) {
                return;
            }

            if (!preserveTopUiTransition) {
                this.resetTopUiTransition();
            }

            this.resetMaintenanceTapTracking();
            const previousPage = currentIndex + 1;
            this.progress[this.activeMode].index = nextIndex;
            this.progress[this.activeMode].activeId = this.activeList[nextIndex]?.id ?? null;
            this.persistProgress();
            const direction = nextIndex > currentIndex ? 'next' : 'prev';
            const nextPage = nextIndex + 1;

            this.triggerSlidePulse(direction);
            this.triggerPagePulse(direction, previousPage, nextPage);
        },
        prev() {
            if (!this.activeMode) {
                return;
            }

            if (this.activeIndex <= 0) {
                if (!this.isNoticeVisible && this.views?.['athkar-app-gate']?.isReaderVisible) {
                    if (this.shouldSkipGuidancePanels()) {
                        this.closeMode();
                        return;
                    }

                    this.showNotice();
                }
                return;
            }

            this.setActiveIndex(this.activeIndex - 1);
        },
        canAdvance(index = this.activeIndex) {
            if (!this.activeMode) {
                return false;
            }

            if (index >= this.activeList.length - 1) {
                return false;
            }

            if (this.shouldPreventSwitching() && !this.isItemComplete(index)) {
                return false;
            }

            return true;
        },
        next() {
            if (!this.activeMode) {
                return;
            }

            if (!this.canAdvance()) {
                return;
            }

            this.setActiveIndex(this.activeIndex + 1);
        },
        requiredCount(index) {
            return Number(this.activeList[index]?.count ?? 1);
        },
        countAt(index) {
            if (!this.activeMode) {
                return 0;
            }

            return Number(this.progress[this.activeMode]?.counts?.[index] ?? 0);
        },
        setCount(index, value, { allowOvercount = false } = {}) {
            if (!this.activeMode) {
                return;
            }

            const maxCount = this.requiredCount(index);
            const sanitized = Number.isFinite(value) ? Math.max(0, value) : 0;
            const nextValue = allowOvercount ? sanitized : Math.min(sanitized, maxCount);
            const previousValue = Number(this.progress[this.activeMode].counts[index] ?? 0);

            if (nextValue === previousValue) {
                return;
            }

            this.progress[this.activeMode].counts[index] = nextValue;
            this.updateModeMetricsForCountChange(
                this.activeMode,
                index,
                previousValue,
                nextValue,
                maxCount,
            );
            this.markProgressDirty({
                completionChanged:
                    (previousValue < maxCount && nextValue >= maxCount) ||
                    (previousValue >= maxCount && nextValue < maxCount),
            });
            this.persistProgress();
        },
        isItemComplete(index) {
            return this.countAt(index) >= this.requiredCount(index);
        },
        isAllComplete() {
            return (
                this.activeList.length > 0 &&
                this.activeList.every((_, index) => this.isItemComplete(index))
            );
        },
        shouldAutoAdvance(_index) {
            const autoSwitch = this.settingValue(
                'does_automatically_switch_completed_athkar',
                true,
            );

            if (autoSwitch) {
                return true;
            }

            return false;
        },
        shouldAllowOvercount({ wasComplete = false } = {}) {
            const autoSwitch = this.settingValue(
                'does_automatically_switch_completed_athkar',
                true,
            );

            return !autoSwitch || wasComplete;
        },
        resetMaintenanceTapTracking() {
            this.maintenance.sequentialTapCount = 0;
            this.maintenance.mode = null;
            this.maintenance.index = null;
        },
        clearRapidTapReleaseTimer() {
            if (this.rapidTap.releaseTimer !== null) {
                clearTimeout(this.rapidTap.releaseTimer);
                this.rapidTap.releaseTimer = null;
            }
        },
        resetRapidTapMode() {
            const wasRapidTapMode = this.rapidTap.isActive;

            this.clearRapidTapReleaseTimer();
            this.rapidTap.isActive = false;
            this.rapidTap.lastTapAt = 0;
            this.rapidTap.burstCount = 0;

            if (wasRapidTapMode) {
                this.$nextTick(() => this.setupTextShimmer(null, { immediate: false }));
            }
        },
        shouldUseRapidTapSafeMode(requiredCount) {
            return this.rapidTap.isActive && requiredCount >= this.rapidTap.minimumRequiredCount;
        },
        trackRapidTapBurst(requiredCount) {
            if (requiredCount < this.rapidTap.minimumRequiredCount) {
                this.resetRapidTapMode();

                return;
            }

            const now = performance.now();
            const elapsed = now - this.rapidTap.lastTapAt;

            if (elapsed > 0 && elapsed <= this.rapidTap.windowMs) {
                this.rapidTap.burstCount += 1;
            } else {
                this.rapidTap.burstCount = 1;
            }

            this.rapidTap.lastTapAt = now;

            if (!this.rapidTap.isActive && this.rapidTap.burstCount >= this.rapidTap.threshold) {
                this.rapidTap.isActive = true;
                this.stopTextShimmer();
            }

            if (!this.rapidTap.isActive) {
                return;
            }

            this.clearRapidTapReleaseTimer();
            this.rapidTap.releaseTimer = setTimeout(() => {
                this.rapidTap.releaseTimer = null;
                this.rapidTap.isActive = false;
                this.rapidTap.burstCount = 0;
                this.setupTextShimmer(null, { immediate: false });
            }, this.rapidTap.holdMs);
        },
        trackMaintenanceTap(index, requiredCount) {
            if (
                !this.activeMode ||
                this.rapidTap.isActive ||
                requiredCount < this.maintenance.minimumRequiredCount
            ) {
                this.resetMaintenanceTapTracking();

                return;
            }

            const isSameTarget =
                this.maintenance.mode === this.activeMode && this.maintenance.index === index;

            this.maintenance.sequentialTapCount = isSameTarget
                ? this.maintenance.sequentialTapCount + 1
                : 1;
            this.maintenance.mode = this.activeMode;
            this.maintenance.index = index;

            if (this.maintenance.sequentialTapCount % this.maintenance.tapInterval !== 0) {
                return;
            }

            window.dispatchEvent(new CustomEvent('athkar-reader-maintenance'));
            window.dispatchEvent(
                new CustomEvent('athkar-action-state-pulse', {
                    detail: {
                        durationMs: 34,
                    },
                }),
            );
        },
        handleTap() {
            if (!this.activeMode) {
                return;
            }

            if (this.isMobileCounterOpen && this.isMobileViewport()) {
                this.setMobileCounterOpen(false);
                this.closeHint();
                return;
            }

            if (this.swipe.ignoreClick) {
                this.swipe.ignoreClick = false;

                return;
            }

            const index = this.activeIndex;
            const required = this.requiredCount(index);
            const current = this.countAt(index);
            const wasComplete = current >= required;
            const allowOvercount = this.shouldAllowOvercount({ wasComplete });
            const autoSwitch = this.settingValue(
                'does_automatically_switch_completed_athkar',
                true,
            );
            let didIncrementCount = false;
            const shouldAnimateTapFeedback = !this.shouldUseRapidTapSafeMode(required);

            if (current < required || allowOvercount) {
                const previousCount = current;
                const previousTotal = this.totalCompletedCount;
                const nextCount = current + 1;
                this.setCount(index, nextCount, { allowOvercount });
                didIncrementCount = true;
                if (shouldAnimateTapFeedback) {
                    this.triggerCountPulse(index, previousCount, nextCount);
                    this.triggerTotalPulse(previousTotal, previousTotal + 1);
                }

                if (shouldAnimateTapFeedback && required > 1) {
                    this.triggerTapPulse(index);
                }
            }

            if (didIncrementCount) {
                this.trackRapidTapBurst(required);
                this.trackMaintenanceTap(index, required);
            }

            if (!this.isItemComplete(index)) {
                return;
            }

            const justCompleted = !wasComplete && this.isItemComplete(index);

            if (autoSwitch && justCompleted) {
                this.advanceAfterCompletion(index);

                return;
            }

            if (!wasComplete && this.isAllComplete() && index === this.activeList.length - 1) {
                this.finishActiveMode();
            }
        },
        completeThikr(index) {
            if (!this.activeMode) {
                return;
            }

            const required = this.requiredCount(index);
            const current = this.countAt(index);

            if (current === required) {
                return;
            }

            const previousTotal = this.totalCompletedCount;
            this.progress[this.activeMode].counts[index] = required;
            this.updateModeMetricsForCountChange(
                this.activeMode,
                index,
                current,
                required,
                required,
            );
            this.markProgressDirty({
                completionChanged: current < required,
            });
            this.persistProgress();
            this.triggerCountPulse(index, current, required);
            this.triggerTotalPulse(previousTotal, previousTotal + (required - current));

            if (required > 1) {
                this.triggerTapPulse(index);
            }

            if (this.shouldAutoAdvance(index)) {
                this.advanceAfterCompletion(index);

                return;
            }

            if (this.isAllComplete() && index === this.activeList.length - 1) {
                this.finishActiveMode();
            }
        },
        incrementCurrentForSwipe() {
            if (!this.activeMode) {
                return { didFinish: false, didUpdate: false };
            }

            const index = this.activeIndex;
            const required = this.requiredCount(index);
            const current = this.countAt(index);
            const wasComplete = current >= required;
            const allowOvercount = this.shouldAllowOvercount({ wasComplete });
            let didUpdate = false;

            if (current < required || allowOvercount) {
                const previousTotal = this.totalCompletedCount;
                const nextCount = current + 1;
                this.setCount(index, nextCount, { allowOvercount });
                this.triggerCountPulse(index, current, nextCount);
                this.triggerTotalPulse(previousTotal, previousTotal + 1);

                if (required > 1) {
                    this.triggerTapPulse(index);
                }

                didUpdate = true;
            }

            if (!wasComplete && this.isAllComplete() && index === this.activeList.length - 1) {
                this.finishActiveMode();

                return { didFinish: true, didUpdate };
            }

            return { didFinish: false, didUpdate };
        },
        advanceAfterCompletion(index) {
            if (index < this.activeList.length - 1) {
                this.closeHint();
                this.startTopUiCompletionTransition(index, index + 1);

                return;
            }

            if (this.isAllComplete()) {
                this.finishActiveMode();
            }
        },
        finishActiveMode() {
            const mode = this.activeMode;

            if (!mode) {
                return;
            }

            this.completedOn = {
                ...this.completedOn,
                [mode]: this.todayKey(),
            };

            if (this.shouldSkipGuidancePanels()) {
                this.isNoticeVisible = false;
                this.isCompletionVisible = false;

                if (this.completionTimer) {
                    clearTimeout(this.completionTimer);
                    this.completionTimer = null;
                }

                this.views[`athkar-app-gate`].isReaderVisible = false;
                this.resetNavState();

                setTimeout(() => {
                    if (!this.views[`athkar-app-gate`].isReaderVisible) {
                        this.activeMode = null;
                        this.$viewNav('athkar-app-gate');
                        this.syncNativeVolumeNavigation();
                    }
                }, this.readerLeaveMs);

                this.syncNativeVolumeNavigation();

                return;
            }

            this.isNoticeVisible = false;
            this.views[`athkar-app-gate`].isReaderVisible = false;
            this.resetNavState();
            this.isCompletionVisible = true;

            if (this.completionTimer) {
                clearTimeout(this.completionTimer);
            }

            this.completionTimer = setTimeout(() => {
                this.isCompletionVisible = false;
                this.syncNativeVolumeNavigation();
            }, this.completionVisibleMs);

            setTimeout(() => {
                if (!this.views[`athkar-app-gate`].isReaderVisible) {
                    this.activeMode = null;
                    this.$viewNav('athkar-app-gate');
                    this.syncNativeVolumeNavigation();
                }
            }, this.readerLeaveMs);

            this.syncNativeVolumeNavigation();
        },
        itemKey(item, index) {
            const itemId = item?.id ?? `index-${index}`;

            return `${this.activeMode ?? 'athkar'}-${itemId}`;
        },
        queueReaderTextFit() {
            if (!this.activeMode) {
                return;
            }

            if (!this.views?.['athkar-app-gate']?.isReaderVisible || this.isNoticeVisible) {
                return;
            }

            this.queueTextFit();
            this.$nextTick(() => this.queueTextFit());
        },
        setupTextFit() {
            this.$nextTick(() => this.queueTextFit());

            if (document.fonts?.ready) {
                document.fonts.ready.then(() => this.queueTextFit());
            }
        },
        queueTextFit() {
            if (this.textFit.raf) {
                cancelAnimationFrame(this.textFit.raf);
            }

            if (this.textFit.settleTimer) {
                clearTimeout(this.textFit.settleTimer);
                this.textFit.settleTimer = null;
            }

            this.textFit.raf = requestAnimationFrame(() => {
                this.textFit.raf = requestAnimationFrame(() => {
                    this.textFit.raf = null;
                    window.dispatchEvent(new CustomEvent('fitty-refit'));
                    this.$nextTick(() => this.setupTextShimmer());
                });
            });

            this.textFit.settleTimer = setTimeout(() => {
                this.textFit.settleTimer = null;
                window.dispatchEvent(new CustomEvent('fitty-refit'));
                this.$nextTick(() => this.setupTextShimmer());
            }, this.textFitSettleMs);
        },
        isTouchReaderContext() {
            const bp = this.$store?.bp;
            const isNarrowReaderViewport =
                typeof bp?.is === 'function' ? bp.is('base') || bp.is('sm') : false;
            const isMobileWidth = this.isMobileViewport();

            if (typeof bp?.isTouch === 'function') {
                return bp.isTouch() || isNarrowReaderViewport || isMobileWidth;
            }

            if (typeof bp?.hasTouch === 'boolean') {
                return bp.hasTouch || isNarrowReaderViewport || isMobileWidth;
            }

            return (
                Number(navigator.maxTouchPoints ?? 0) > 0 || isNarrowReaderViewport || isMobileWidth
            );
        },
        shouldAllowTouchScrollForBox(box) {
            if (!box || !this.isTouchReaderContext()) {
                return false;
            }

            const slide = box.closest?.('[data-athkar-slide]');
            if (!slide || slide.dataset.active !== 'true') {
                return false;
            }

            const isOriginActive = this.isOriginVisible(this.activeIndex);
            const hasTextOverflow = box.dataset.athkarTextOverflow === 'true';
            const hasOriginOverflow = box.dataset.athkarOriginOverflow === 'true';
            const hasTouchScrollClass = box.classList.contains('athkar-text-box--touch-scroll');
            const scrollTarget = box.dataset.athkarScrollTarget ?? '';
            const touchScrollEnabled =
                box.dataset.athkarTouchScroll === 'true' ||
                (hasTouchScrollClass && box.dataset.athkarTouchOverflow !== 'false');

            if (isOriginActive) {
                return (
                    hasOriginOverflow ||
                    (touchScrollEnabled &&
                        scrollTarget === 'origin' &&
                        box.classList.contains('athkar-text-box--origin-scroll'))
                );
            }

            return (
                hasTextOverflow ||
                (touchScrollEnabled &&
                    !box.classList.contains('athkar-text-box--origin-scroll') &&
                    scrollTarget !== 'origin')
            );
        },
        beginTextScroll(event) {
            const box = event?.currentTarget;

            if (!box || !this.isTouchReaderContext()) {
                return;
            }

            if (!this.shouldAllowTouchScrollForBox(box)) {
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            this.textScroll.active = true;
            this.textScroll.source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';
            this.textScroll.startY = point.y;
            this.textScroll.startScrollTop = box.scrollTop;
            this.textScroll.pointerId = point.pointerId;
            this.textScroll.element = box;

            event.stopPropagation();
        },
        moveTextScroll(event) {
            if (!this.textScroll.active || !this.textScroll.element) {
                return;
            }

            this.cancelHoldCopy();

            const source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';

            if (this.textScroll.source && source !== this.textScroll.source) {
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            if (
                this.textScroll.pointerId !== null &&
                point.pointerId !== this.textScroll.pointerId
            ) {
                return;
            }

            const deltaY = point.y - this.textScroll.startY;
            this.textScroll.element.scrollTop = this.textScroll.startScrollTop - deltaY;

            event.stopPropagation();
            if (event.cancelable) {
                event.preventDefault();
            }
        },
        endTextScroll() {
            if (this.textScroll.element) {
                const target =
                    this.textScroll.element.dataset.athkarScrollTarget === 'origin'
                        ? 'origin'
                        : 'text';
                this.rememberScrollOffset(
                    this.activeIndex,
                    target,
                    this.textScroll.element.scrollTop,
                );
            }

            this.textScroll.active = false;
            this.textScroll.source = null;
            this.textScroll.startY = 0;
            this.textScroll.startScrollTop = 0;
            this.textScroll.pointerId = null;
            this.textScroll.element = null;
        },
        copyFeedbackStyle() {
            const x = Number(this.copyFeedback?.x ?? 0);
            const y = Number(this.copyFeedback?.y ?? 0);
            const normalizedX = Number.isFinite(x) ? Math.round(x) : 0;
            const normalizedY = Number.isFinite(y) ? Math.round(y) : 0;

            return `left: ${normalizedX}px; top: ${normalizedY}px;`;
        },
        copyPointFromElement(element) {
            if (!(element instanceof Element)) {
                return null;
            }

            const rect = element.getBoundingClientRect();

            if (!Number.isFinite(rect?.left) || !Number.isFinite(rect?.top)) {
                return null;
            }

            return {
                x: rect.left + rect.width / 2,
                y: rect.top,
            };
        },
        copyPointFromAnchor(anchor = null) {
            const directX = Number(anchor?.x);
            const directY = Number(anchor?.y);

            if (
                Number.isFinite(directX) &&
                Number.isFinite(directY) &&
                (directX > 0 || directY > 0)
            ) {
                return {
                    x: directX,
                    y: directY,
                };
            }

            const targetPoint = this.copyPointFromElement(anchor?.target ?? null);

            if (targetPoint) {
                return targetPoint;
            }

            return {
                x: Math.max(0, Math.round((window.innerWidth ?? 0) / 2)),
                y: Math.max(0, Math.round((window.innerHeight ?? 0) / 2)),
            };
        },
        showCopyFeedback(anchor = null) {
            const point = this.copyPointFromAnchor(anchor);

            if (!point) {
                return;
            }

            this.copyFeedback.x = point.x;
            this.copyFeedback.y = point.y;
            this.copyFeedback.visible = true;
            this.copyFeedback.serial += 1;
            const serial = this.copyFeedback.serial;

            if (this.copyFeedback.timer !== null) {
                clearTimeout(this.copyFeedback.timer);
            }

            this.copyFeedback.timer = window.setTimeout(() => {
                if (this.copyFeedback.serial !== serial) {
                    return;
                }

                this.copyFeedback.visible = false;
                this.copyFeedback.timer = null;
            }, athkarCopyPopoverVisibleDurationMs);
        },
        hideCopyFeedback() {
            if (this.copyFeedback.timer !== null) {
                clearTimeout(this.copyFeedback.timer);
                this.copyFeedback.timer = null;
            }

            this.copyFeedback.visible = false;
        },
        fallbackCopyText(text) {
            if (typeof document === 'undefined') {
                return false;
            }

            const textarea = document.createElement('textarea');
            textarea.value = String(text ?? '');
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '-9999px';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);
            textarea.select();

            try {
                return document.execCommand('copy');
            } catch (_) {
                return false;
            } finally {
                textarea.remove();
            }
        },
        async writeClipboardText(text) {
            const normalizedText = String(text ?? '').trim();

            if (!normalizedText) {
                return false;
            }

            if (
                typeof navigator !== 'undefined' &&
                navigator.clipboard &&
                typeof navigator.clipboard.writeText === 'function'
            ) {
                try {
                    await navigator.clipboard.writeText(normalizedText);

                    return true;
                } catch (_) {
                    return this.fallbackCopyText(normalizedText);
                }
            }

            return this.fallbackCopyText(normalizedText);
        },
        resolveCopyTargetFromEvent(event = null, index = null) {
            const target = event?.target;

            if (!(target instanceof Element)) {
                return null;
            }

            if (target.closest('[data-athkar-origin-text]')) {
                return 'origin';
            }

            if (target.closest('[data-athkar-text]')) {
                return 'text';
            }

            const textBox = target.closest('[data-athkar-text-box]');

            if (textBox instanceof Element) {
                const normalizedIndex = Math.max(0, Math.trunc(Number(index ?? -1)));

                if (Number.isFinite(normalizedIndex) && this.isOriginVisible(normalizedIndex)) {
                    return 'origin';
                }

                return 'text';
            }

            return null;
        },
        resolveCopyTextForTarget(index, target = 'text') {
            const item = this.activeList?.[index] ?? null;

            if (!item) {
                return '';
            }

            if (target === 'origin') {
                return String(item?.origin ?? '').trim();
            }

            return String(item?.text ?? '').trim();
        },
        resetHoldCopyState() {
            if (this._copyHoldTimer !== null) {
                clearTimeout(this._copyHoldTimer);
                this._copyHoldTimer = null;
            }

            this.copyHold.active = false;
            this.copyHold.pointerId = null;
            this.copyHold.source = null;
            this.copyHold.startX = 0;
            this.copyHold.startY = 0;
            this.copyHold.index = null;
            this.copyHold.target = null;
            this.copyHold.triggered = false;
            this.copyHold.anchor = null;
        },
        beginHoldCopy(event, index) {
            if (!this.activeMode || this.isCompletionVisible || this.isNoticeVisible) {
                this.resetHoldCopyState();

                return;
            }

            const normalizedIndex = Math.max(0, Math.trunc(Number(index ?? -1)));

            if (!Number.isFinite(normalizedIndex) || normalizedIndex !== this.activeIndex) {
                this.resetHoldCopyState();

                return;
            }

            const target = this.resolveCopyTargetFromEvent(event, normalizedIndex);

            if (!target) {
                this.resetHoldCopyState();

                return;
            }

            const text = this.resolveCopyTextForTarget(normalizedIndex, target);

            if (text === '') {
                this.resetHoldCopyState();

                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                this.resetHoldCopyState();

                return;
            }

            this.resetHoldCopyState();
            this.copyHold.active = true;
            this.copyHold.pointerId = point.pointerId;
            this.copyHold.source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';
            this.copyHold.startX = point.x;
            this.copyHold.startY = point.y;
            this.copyHold.index = normalizedIndex;
            this.copyHold.target = target;
            this.copyHold.triggered = false;
            this.copyHold.anchor = {
                x: point.x,
                y: point.y,
                target: event?.target instanceof Element ? event.target : null,
            };

            this._copyHoldTimer = window.setTimeout(async () => {
                if (!this.copyHold.active || this.copyHold.triggered) {
                    return;
                }

                const holdText = this.resolveCopyTextForTarget(
                    this.copyHold.index,
                    this.copyHold.target,
                );

                if (!holdText) {
                    return;
                }

                const didCopy = await this.writeClipboardText(holdText);

                if (!didCopy) {
                    return;
                }

                this.copyHold.triggered = true;
                this.swipe.ignoreClick = true;
                this.showCopyFeedback(this.copyHold.anchor);
            }, athkarCopyHoldDelayMs);
        },
        moveHoldCopy(event) {
            if (!this.copyHold.active || this.copyHold.triggered) {
                return;
            }

            const source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';

            if (this.copyHold.source && source !== this.copyHold.source) {
                this.resetHoldCopyState();

                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                this.resetHoldCopyState();

                return;
            }

            if (
                this.copyHold.pointerId !== null &&
                point.pointerId !== null &&
                this.copyHold.pointerId !== point.pointerId
            ) {
                this.resetHoldCopyState();

                return;
            }

            const deltaX = Math.abs(point.x - this.copyHold.startX);
            const deltaY = Math.abs(point.y - this.copyHold.startY);

            if (
                deltaX > athkarCopyHoldMoveThresholdPx ||
                deltaY > athkarCopyHoldMoveThresholdPx ||
                this.textScroll.active
            ) {
                this.resetHoldCopyState();
            }
        },
        endHoldCopy(event = null) {
            if (!this.copyHold.active) {
                return;
            }

            if (event) {
                const point = this.swipePoint(event);

                if (
                    this.copyHold.pointerId !== null &&
                    point?.pointerId !== null &&
                    this.copyHold.pointerId !== point.pointerId
                ) {
                    return;
                }
            }

            const didTrigger = this.copyHold.triggered;

            this.resetHoldCopyState();

            if (didTrigger) {
                this.swipe.ignoreClick = true;
            }
        },
        cancelHoldCopy() {
            this.resetHoldCopyState();
        },
        setupTextShimmer(text = null, options = {}) {
            if (this.rapidTap.isActive) {
                this.textShimmerController?.stop();

                return;
            }

            if (!this.shouldEnableVisualEnhancements()) {
                this.textShimmerController?.stop();

                return;
            }

            this.textShimmerController?.setup(text, options);
        },
        stopTextShimmer() {
            this.textShimmerController?.stop();
        },
        swipePoint(event) {
            if (event?.touches?.length) {
                const touch = event.touches[0];

                return {
                    x: touch.clientX,
                    y: touch.clientY,
                    pointerType: 'touch',
                    pointerId: null,
                };
            }

            if (event?.changedTouches?.length) {
                const touch = event.changedTouches[0];

                return {
                    x: touch.clientX,
                    y: touch.clientY,
                    pointerType: 'touch',
                    pointerId: null,
                };
            }

            if (Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)) {
                return {
                    x: event.clientX,
                    y: event.clientY,
                    pointerType: event.pointerType ?? 'mouse',
                    pointerId: event.pointerId ?? null,
                };
            }

            return null;
        },
        swipeStart(event) {
            if (!this.activeMode || this.isCompletionVisible) {
                return;
            }

            const textBox = event.target?.closest?.('[data-athkar-text-box]');
            if (
                this.isTouchReaderContext() &&
                textBox &&
                this.shouldAllowTouchScrollForBox(textBox)
            ) {
                this.swipeCancel();
                return;
            }

            const source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';

            if (this.swipe.source && this.swipe.source !== source) {
                return;
            }

            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }

            if (this.hintIndex !== null) {
                if (event.target?.closest?.('[data-hint-allow]')) {
                    return;
                }

                this.closeHint({ keepMobileOpen: true });

                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            this.swipe.active = true;
            this.swipe.source = source;
            this.swipe.startX = point.x;
            this.swipe.startY = point.y;
            this.swipe.pointerId = point.pointerId;
            this.swipe.pointerType = point.pointerType;
            this.swipe.startedOnTap = Boolean(event.target?.closest?.('[data-athkar-tap]'));
            this.swipe.startedInScrollableText = Boolean(
                event.target?.closest?.(
                    '[data-athkar-text-box][data-athkar-touch-overflow="true"]',
                ),
            );
        },
        swipeEnd(event) {
            if (!this.swipe.active) {
                return;
            }

            const source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';

            if (this.swipe.source && this.swipe.source !== source) {
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            if (this.swipe.pointerId !== null && point.pointerId !== this.swipe.pointerId) {
                return;
            }

            const deltaX = point.x - this.swipe.startX;
            const deltaY = point.y - this.swipe.startY;
            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);
            const isTouchLike = (point.pointerType ?? 'mouse') !== 'mouse';
            const startedInScrollableText = this.swipe.startedInScrollableText;

            this.swipe.active = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;
            this.swipe.startedInScrollableText = false;

            if (this.isNoticeVisible) {
                if (absX < 40 || absX < absY) {
                    return;
                }

                if (deltaX < 0) {
                    this.returnToGateFromNotice();
                } else {
                    this.confirmNotice();
                }

                this.swipe.ignoreClick = true;
                return;
            }

            if (startedInScrollableText && absY >= 12 && absY > absX) {
                this.swipe.startedOnTap = false;
                this.swipe.ignoreClick = true;

                return;
            }

            if (this.swipe.startedOnTap && isTouchLike && absX < 12 && absY < 12) {
                this.swipe.startedOnTap = false;
                this.swipe.ignoreClick = true;
                this.handleTap();

                return;
            }

            this.swipe.startedOnTap = false;

            const isHorizontalSwipe = absX >= 40 && absX >= absY;
            const isVerticalSwipe = absY >= 40 && absY > absX;

            if (!isHorizontalSwipe && !isVerticalSwipe) {
                return;
            }

            const previousIndex = this.activeIndex;
            let didHandleSwipe = false;

            if (isHorizontalSwipe && deltaX < 0) {
                this.prev();
                didHandleSwipe = this.activeIndex !== previousIndex;

                if (didHandleSwipe || previousIndex === 0) {
                    this.swipe.ignoreClick = true;
                }

                return;
            }

            if (this.shouldExitReaderAfterForwardSwipe()) {
                this.finishActiveMode();
                this.swipe.ignoreClick = true;

                return;
            }

            if (this.settingValue('does_clicking_switch_athkar_too', true)) {
                const increment = this.incrementCurrentForSwipe();

                if (increment.didFinish) {
                    this.swipe.ignoreClick = true;

                    return;
                }

                didHandleSwipe = increment.didUpdate;
            }

            const indexBeforeNext = this.activeIndex;
            this.next();
            didHandleSwipe = didHandleSwipe || this.activeIndex !== indexBeforeNext;

            if (!didHandleSwipe && this.shouldExitReaderAfterForwardSwipe()) {
                this.finishActiveMode();
                this.swipe.ignoreClick = true;
                return;
            }

            if (didHandleSwipe) {
                this.swipe.ignoreClick = true;
            }
        },
        swipeCancel() {
            this.swipe.active = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;
            this.swipe.startedInScrollableText = false;
            this.cancelHoldCopy();
            this.endTextScroll();
        },
        buildDigitMorphSegments(previousValue, nextValue) {
            const previous = String(previousValue ?? '');
            const next = String(nextValue ?? '');
            const length = Math.max(previous.length, next.length);
            const previousChars = previous.padStart(length, ' ').split('');
            const nextChars = next.padStart(length, ' ').split('');

            const segments = nextChars
                .map((nextChar, index) => {
                    const previousChar = previousChars[index] ?? '';
                    const prev = previousChar === ' ' ? '' : previousChar;
                    const nextValueChar = nextChar === ' ' ? '' : nextChar;

                    return {
                        key: `${index}:${prev}->${nextValueChar}`,
                        prev,
                        next: nextValueChar,
                        changed: prev !== nextValueChar,
                    };
                })
                .filter((segment) => segment.prev !== '' || segment.next !== '');

            return {
                segments,
                hasChanges: segments.some((segment) => segment.changed),
            };
        },
        triggerSlidePulse(direction) {
            if (this.slide.timer) {
                clearTimeout(this.slide.timer);
            }

            this.slide.direction = direction;
            this.slide.isActive = false;

            requestAnimationFrame(() => {
                this.slide.isActive = true;
            });

            this.slide.timer = setTimeout(() => {
                this.slide.isActive = false;
            }, this.slideDurationMs);
        },
        triggerCountPulse(index, previousValue, nextValue) {
            if (this.countPulse.timer) {
                clearTimeout(this.countPulse.timer);
            }

            const morph = this.buildDigitMorphSegments(previousValue, nextValue);

            this.countPulse.index = index;
            this.countPulse.segments = morph.segments;
            this.countPulse.hasChanges = morph.hasChanges;

            if (!morph.hasChanges) {
                return;
            }

            if (!this.countPulse.isActive) {
                requestAnimationFrame(() => {
                    this.countPulse.isActive = true;
                });
            }

            this.countPulse.timer = setTimeout(() => {
                this.countPulse.isActive = false;
            }, this.pulseDurationMs);
        },
        triggerPagePulse(direction, previousValue, nextValue) {
            if (this.pagePulse.timer) {
                clearTimeout(this.pagePulse.timer);
            }

            const morph = this.buildDigitMorphSegments(previousValue, nextValue);

            this.pagePulse.direction = direction;
            this.pagePulse.isActive = false;
            this.pagePulse.segments = morph.segments;
            this.pagePulse.hasChanges = morph.hasChanges;

            if (!morph.hasChanges) {
                return;
            }

            requestAnimationFrame(() => {
                this.pagePulse.isActive = true;
            });

            this.pagePulse.timer = setTimeout(() => {
                this.pagePulse.isActive = false;
            }, this.pulseDurationMs);
        },
        triggerTotalPulse(previousValue, nextValue) {
            if (this.totalPulse.timer) {
                clearTimeout(this.totalPulse.timer);
            }

            const morph = this.buildDigitMorphSegments(previousValue, nextValue);

            this.totalPulse.segments = morph.segments;
            this.totalPulse.hasChanges = morph.hasChanges;

            if (!morph.hasChanges) {
                return;
            }

            if (!this.totalPulse.isActive) {
                requestAnimationFrame(() => {
                    this.totalPulse.isActive = true;
                });
            }

            this.totalPulse.timer = setTimeout(() => {
                this.totalPulse.isActive = false;
            }, this.pulseDurationMs);
        },
        triggerTapPulse(index) {
            if (this.tapPulse.timer) {
                clearTimeout(this.tapPulse.timer);
            }

            this.tapPulse.index = index;

            if (!this.tapPulse.isActive) {
                requestAnimationFrame(() => {
                    this.tapPulse.isActive = true;
                });
            }

            this.tapPulse.timer = setTimeout(() => {
                this.tapPulse.isActive = false;
            }, this.pulseDurationMs);
        },
    }));
});
