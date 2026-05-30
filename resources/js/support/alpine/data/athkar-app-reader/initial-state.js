export const createInitialState = (config, deps) => {
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
        noticeBypassFlagsStorageKey,
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

        noticeBypassFlags: window.Alpine.$persist({}).as(noticeBypassFlagsStorageKey),

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

        originOverflowToggle: {
            mode: null,
            index: null,
        },

        originTransition: {
            mode: null,
            index: null,
            fromIsOrigin: null,
            toIsOrigin: null,
            phase: 'idle',
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
            countOverride: null,
            requiredOverride: null,
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

        isFontScaleOverlayVisible: false,

        readerLeaveMs: 300,

        slideDurationMs: 900,

        transitionMode: null,

        transitionDistance: '1.5rem',

        isGateMenuTransition: true,

        pulseDurationMs: 520,

        topUiCompletionLingerMs: 1000,

        topUiPulseDurationMs: 360,

        originFadeDurationMs: 200,

        originResyncDelayMs: 200,

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

        _originTransitionTimer: null,

        _onWindowNativeVolumeButton: null,

        _onAthkarFontScaleToggle: null,

        lastSeenDay: window.Alpine.$persist(null).as('athkar-last-day'),

        progress: defaultProgressState(),

        completedOn: window.Alpine.$persist({
            sabah: null,
            masaa: null,
        }).as('athkar-completed-v1'),
    };
};
