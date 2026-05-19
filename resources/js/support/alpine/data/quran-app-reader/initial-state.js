export const createInitialState = (config, deps) => {
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
        api: {
            pageDataTemplate: String(config?.api?.pageDataTemplate ?? ''),
            searchIndexUrl: String(config?.api?.searchIndexUrl ?? ''),
        },

        cacheNames: {
            pages: 'quran-reader-pages-v13',
            fonts: 'quran-reader-fonts-v4',
            search: 'quran-reader-search-v3',
            searchLocalIndex: 'quran-reader-search-local-index-v1',
        },

        initialPayload: normalizePayload(config?.initialPayload),

        nativeRuntime: Boolean(config?.nativeRuntime ?? false),

        prewarmPages: Math.max(1, Number(config?.prewarmPages ?? 6)),

        prefetchRadius: Math.max(1, Number(config?.prefetchRadius ?? 2)),

        searchModalId: String(config?.searchModalId ?? ''),

        searchModalDomId: String(config?.searchModalDomId ?? ''),

        searchActionModalId: String(config?.searchActionModalId ?? ''),

        jumpPageModalId: String(config?.jumpPageModalId ?? ''),

        historyModalId: String(config?.historyModalId ?? ''),

        bookmarksModalId: String(config?.bookmarksModalId ?? ''),

        initialSettings:
            config?.settings && typeof config.settings === 'object' ? config.settings : {},

        ready: false,

        pageNumber: window.Alpine.$persist(1).as('quran-reader-page-number-v1'),

        pageInput: 1,

        maxPage: 0,

        activeAyahIndex: 0,

        activeWordIndex: 0,

        searchHighlightedAyahIndex: 0,

        mushafLines: [],

        lineEntry: null,

        line: null,

        qpcPageFontFamily: null,

        qpcPageFontUrl: null,

        qpcPageFontFormat: null,

        basmallahFontFamily: null,

        basmallahFontUrl: null,

        basmallahFontFormat: null,

        basmallahText: null,

        surahHeaderFontFamily: null,

        surahHeaderFontUrl: null,

        surahHeaderFontFormat: null,

        surahHeaderTopPaddingWhenFollowingPreviousSurahAyah: null,

        useCenteredAyahLayout: true,

        hoveredAyahIndex: 0,

        hoveredWordIndex: 0,

        doesEnableVisualEnhancements: true,

        doesTargetWordsByDefault: false,

        doesPreserveHarakatOnCopy: true,

        doesAppendSurahAffixOnMultiCopy: true,

        doesAppendSurahAffixAlwaysOnCopy: false,

        doesShowImmersiveMobileEdgeCaptions: true,

        doesUseVolumeButtonsNavigation: true,

        doesUseWesternNumerals: true,

        wirdFrequencyMode: wirdFrequencyModeMonthly,

        wirdKhatmatTarget: 1,

        wirdModeActive: false,

        wirdNormalPageBeforeMode: 1,

        wirdBrowseStep: null,

        wirdSliderVisualStep: null,

        wirdHoverShimmerRunning: false,

        wirdTodayKey: '',

        wirdDayOffsetDays: readWirdDayOffsetDays(),

        wirdDailyRecord: null,

        wirdState: null,

        westernNumeralCharacters: defaultWesternNumerals.slice(),

        arabicNumeralCharacters: defaultArabicNumerals.slice(),

        isLoadingPage: false,

        isFittingPage: true,

        isTransitioningOutPage: false,

        pageMotionClass: '',

        surahTriggerCaption: '',

        surahTriggerCaptionAnimClass: '',

        surahTriggerSurahNumber: 1,

        mobileEdgeSurahCaptionText: '',

        mobileEdgeSurahCaptionAnimClass: '',

        mobileEdgePageCaptionText: '',

        mobileEdgePageCaptionAnimClass: '',

        pageMotionTimer: null,

        pageScale: 1,

        lineWordGapAdjustments: {},

        pageCounterPulse: {
            isActive: false,
            hasChanges: false,
            segments: [],
            timer: null,
        },

        swipe: {
            active: false,
            startX: 0,
            startY: 0,
            pointerId: null,
            pointerType: null,
            source: null,
        },

        pendingChevronSource: null,

        storage: {
            isPersisted: false,
            persistRequested: false,
            fitCacheBreakpoint: '',
        },

        supportUnlock: {
            mode: 'locked',
            grantedAt: null,
            expiresAt: null,
        },

        search: {
            query: '',
            minQueryLength: 3,
            inputDebounceMs: 600,
            results: [],
            isLoading: false,
            streamHasUpdates: false,
            isReady: false,
            localIndexReady: false,
            lastCompletedNormalizedQuery: '',
            isOpen: false,
            modalOpen: false,
            readyResult: null,
            surahNames: {},
            surahDirectory: [],
            activeSurahNumber: 1,
            preserveActiveSurahOnNextOpen: false,
        },

        navigationHistory: [],

        historyTagDraftById: {},

        bookmarkTagDraftById: {},

        bookmarks: [],

        historyModalOpen: false,

        bookmarksModalOpen: false,

        jumpPageModalOpen: false,

        managerRowEffects: {
            history: {},
            bookmarks: {},
        },

        copyFeedback: {
            visible: false,
            x: 0,
            y: 0,
            timer: null,
            serial: 0,
        },

        isWirdCompletionVisible: false,

        isWirdCompletionPreviewPinned: false,

        isReaderChromeVisible: false,

        isFontScaleOverlayVisible: false,

        quranPageScaleAdjustValue: 0,

        quranPageGapAdjustValue: 0,

        quranPageYOffsetAdjustValue: 0,

        hasCompletedInitialMushafPreparation: false,

        copiedHighlights: {
            wordKeys: [],
            ayahIndexes: [],
        },

        bookmarkButtonPress: {
            pointerId: null,
            holdTriggered: false,
            suppressClick: false,
            timer: null,
        },

        surahQuickNavigator: {
            visible: false,
            pointerId: null,
            holdTriggered: false,
            suppressClick: false,
            timer: null,
        },

        _pendingPageLoads: new Map(),

        _pagePayloadByPage: new Map(),

        _activePageAbortController: null,

        _searchIndexPromise: null,

        _layoutToken: 0,

        _layoutRaf: null,

        _revealTimer: null,

        _lastPageRevealAt: 0,

        _revealBlockedSinceAt: 0,

        _revealBlockedLayoutToken: 0,

        _layoutActivePromise: null,

        _queuedLayoutRequest: null,

        _layoutMutationObserver: null,

        _layoutResizeObserver: null,

        _layoutObservedViewportWidth: 0,

        _layoutObservedViewportHeight: 0,

        _readerPanelLayoutSerial: 0,

        _readerPanelLayoutRaf: null,

        _viewportChangeDebounceTimer: null,

        _onWindowViewportChange: null,

        _onVisualViewportChange: null,

        _onWindowScroll: null,

        _onSwitchView: null,

        _onReaderGoGate: null,

        _onWirdSimulateDay: null,

        _onWirdCongratsPreview: null,

        _onFontScaleToggle: null,

        _pageScaleAdjustRefitRaf: null,

        _deferredBootstrapCheckTimer: null,

        _deferredBootstrapCheckAttempts: 0,

        _onWindowStorage: null,

        _onHistoryManagerRequestSync: null,

        _onBookmarksManagerRequestSync: null,

        _onQrDebugLogsToggle: null,

        _lastQuranReaderView: 'quran-app-tilawa',

        _onWindowKeydown: null,

        _onPanelPointerDown: null,

        _onPanelPointerMove: null,

        _onPanelPointerUp: null,

        _onPanelPointerCancel: null,

        _onWindowPointerMove: null,

        _onWindowPointerUp: null,

        _onWindowPointerCancel: null,

        _onPanelTouchStart: null,

        _onPanelTouchMove: null,

        _onPanelTouchEnd: null,

        _onPanelTouchCancel: null,

        _onWindowTouchMove: null,

        _onWindowTouchEnd: null,

        _onWindowTouchCancel: null,

        _onWindowNativeVolumeButton: null,

        _surahTriggerTimer: null,

        _surahTriggerCleanupTimer: null,

        _mobileEdgeCaptionTimer: null,

        _mobileEdgeCaptionCleanupTimer: null,

        _lastMobileEdgeSurahNumber: 1,

        _pendingNavigationRequest: null,

        _navigationDebounceTimer: null,

        _navigationRevealUnlockTimer: null,

        _navigationRevealLocked: false,

        _swipeRevealWatchdogTimer: null,

        _navigationBurstLastInputAt: 0,

        _navigationBurstCount: 0,

        _navigationBurstFreezeUntil: 0,

        _fitRunCounter: 0,

        _lastFittedPageNumber: 0,

        _fitResultByContext: new Map(),

        _fitSanityCheckTimer: null,

        _fitSanityContextKey: '',

        _fitSanityContextAttemptCount: 0,

        _fitSanityContextLastWidth: 0,

        _fitSanityContextLastHeight: 0,

        _fitSanityContextOutcome: '',

        _fitSanitySuppressedUntil: 0,

        _fitSanityDisabledContextKey: '',

        _fitCachePersistWriteTimer: null,

        _supportUnlockExpiryTimer: null,

        _wirdCompletionTimer: null,

        _fontReadyRecoveryTimer: null,

        _fontReadyRecoveryPage: 0,

        _fontReadyRecoveryAttemptPage: 0,

        _fontReadyRecoveryAttemptCount: 0,

        _fontReadyRecoveryLastAt: 0,

        _fitCacheBreakpoint: '',

        _bypassNextFitCache: false,

        _suppressFitCacheWriteUntil: 0,

        _wirdSliderVisualTweenRaf: null,

        _wirdSliderInputCommitTimer: null,

        _wirdSliderPendingCommitStep: null,

        _wirdSliderLastInputStep: null,

        _wirdSliderLastInputAt: 0,

        _wirdNavigationRequestSerial: 0,

        _wirdLastCommittedTargetPage: 0,

        _wirdLastCommittedStep: null,

        _wirdLastCommittedAt: 0,

        _wirdHoverShimmerTimer: null,

        _pageInputCommitTimer: null,

        _pageInputTweenRaf: null,

        _pageSliderInteractionActive: false,

        _lastPageSliderCommitPage: 0,

        _lastPageSliderCommitAt: 0,

        _searchRequestSerial: 0,

        _searchRequestInFlight: false,

        _searchNavigationInFlight: false,

        _searchQueuedNormalizedQuery: null,

        _searchLocalIndexPromise: null,

        _searchLocalRows: [],

        _searchStreamObserver: null,

        _lastSearchStreamPayloadRaw: '',

        _lastSearchStreamPayloadOffset: 0,

        _searchStreamFrameRemainder: '',

        _searchResultsLeaveTimer: null,

        _stopLivewireMorphedHook: null,

        _supportLockTargetsSyncRaf: null,

        _onSupportLockLivewireMorphed: null,

        _searchResultsAutoAnimateStop: null,

        _historyRowsAutoAnimateStop: null,

        _bookmarksRowsAutoAnimateStop: null,

        _searchModalCloseDebounceTimer: null,

        _searchModalOpenInFlight: null,

        _searchModalInputSyncElement: null,

        _onSearchModalInputSync: null,

        _searchInputSyncDebounceTimer: null,

        _stopIsCalibratingWatcher: null,

        _stopSearchQueryWatcher: null,

        _quranPreparationInFlight: null,

        _quranPreparationRequestPromise: null,

        _startupRestoreInFlight: null,

        _startupCalibrationPending: true,

        _lastDispatchedFontScaleOverlayVisible: null,

        _immersiveEntryAwaitingFirstReveal: true,

        _startupTargetPageNumber: 1,

        _bootstrapDeferred: false,

        calibrationHudTop: 0,

        calibrationHudLeft: 0,

        isCalibrating: false,

        _globalFitCalibrationLayout: null,

        _globalFitCalibrationScale: 0,

        _globalFitCalibrationPageNumber: 0,

        _loadedPayloadPageNumber: 0,

        _surahDirectoryAutoFocusToken: 0,

        _surahDirectoryAutoFocusTimer: null,

        _surahDirectoryAutoFocusRaf: null,

        _surahDirectoryPostOpenTimers: [],

        _wirdEntryRevealTimers: [],

        _wirdEntryLayoutSuppressedUntil: 0,

        _historyManagerSyncTimers: [],

        _bookmarksManagerSyncTimers: [],

        _modalLayoutResumeTimer: null,

        _postModalTargetFitPage: 0,

        _postModalTargetFitRetries: 0,

        _postModalTargetFitTimer: null,

        _modalDrivenFinalRecoveryFitTimer: null,

        _activeModalIds: new Set(),

        _isModalLifecycleSettling: false,

        _managerModalVersion: 0,

        _lastModalLifecycleEventAt: 0,

        _suppressModalLifecycleEffectsUntil: 0,

        _suppressModalLifecycleModalIds: new Set(),

        _wordPressHoldTimer: null,

        _wordBySelectionKey: new Map(),

        _ayahNumberByIndex: new Map(),

        _surahNumberByAyahIndex: new Map(),

        _copiedHighlightTimer: null,

        _suppressWordClickResetTimer: null,

        _suppressNextWordClick: false,

        _skipNextSearchModalCloseLayout: false,

        _lastKnownModalOpenState: false,

        _lastPageInputCommitPage: 0,

        _lastPageInputCommitAt: 0,

        _lastPageInputVisualValue: 1,

        _lastWordHoldAt: 0,

        _lastMobileCopyTapAt: 0,

        _lastMobileCopyTapWordKey: null,

        _lastWordGapRebalancedPageNumber: 0,

        _wirdStateStorageRawSnapshot: null,

        _wirdDayOffsetStorageRawSnapshot: null,

        _idleWarmupQueue: [],

        _idleWarmupQueuedPages: new Set(),

        _idleWarmupHandle: null,

        _idleWarmupHandleKind: null,

        _idleWarmupPausedUntil: 0,

        _idleWarmupInFlight: false,

        _idleWarmupInFlightPage: 0,

        _idleWarmupAbortController: null,

        _idleWarmupHasBackgroundSweepQueued: false,

        _managerRowEffectTimers: new Map(),

        _managerModalsPrewarmPromise: null,

        _managerModalsPrewarmed: false,

        isQrDebugLoggingEnabled: quranReaderDebugLogsEnabledByEnv,

        wordPress: {
            active: false,
            pointerId: null,
            startX: 0,
            startY: 0,
            holdTriggered: false,
            isSecondTap: false,
            word: null,
            target: null,
            dragActive: false,
            trailWordKeys: [],
            trailWords: [],
            trailAyahIndexes: [],
            lastAnchor: null,
        },
    };
};
