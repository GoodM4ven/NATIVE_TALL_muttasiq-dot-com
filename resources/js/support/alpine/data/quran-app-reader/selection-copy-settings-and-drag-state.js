import { migrateSettingsOverrides } from '../../athkar-app-overrides';

export const createSelectionCopySettingsAndDragStateModule = (deps) => {
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
        normalizeBooleanFlag(value, fallback = false) {
            if (typeof value === 'boolean') {
                return value;
            }

            if (value === 1 || value === '1') {
                return true;
            }

            if (value === 0 || value === '0') {
                return false;
            }

            if (value === null || value === undefined || value === '') {
                return Boolean(fallback);
            }

            const normalized = String(value).trim().toLowerCase();

            if (['true', 'yes', 'on'].includes(normalized)) {
                return true;
            }

            if (['false', 'no', 'off'].includes(normalized)) {
                return false;
            }

            return Boolean(fallback);
        },

        qrDebugLog(...messages) {
            if (!this.isQrDebugLoggingEnabled) {
                return;
            }

            console.log(...messages);
        },

        qrTimingLog(phase, details = {}) {
            if (!this.isQrDebugLoggingEnabled) {
                return;
            }

            console.log('[QR:timing]', phase, details);
        },

        qrDebugError(...messages) {
            if (!this.isQrDebugLoggingEnabled) {
                return;
            }

            console.error(...messages);
        },

        normalizeNumeralCharacters(characters, fallback = defaultWesternNumerals) {
            if (!Array.isArray(characters)) {
                return fallback.slice();
            }

            const normalizedCharacters = characters
                .map((character) => String(character ?? ''))
                .filter((character) => character !== '');

            if (normalizedCharacters.length !== 10) {
                return fallback.slice();
            }

            return normalizedCharacters;
        },

        formatAyahTokenNumber(value) {
            const numericValue = Math.max(0, Math.trunc(Number(value ?? 0)));

            if (numericValue < 1) {
                return null;
            }

            const westernText = String(numericValue);

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

        resolveControlPanelSettingsWithUserOverrides(defaultSettings = {}) {
            const defaults =
                defaultSettings &&
                typeof defaultSettings === 'object' &&
                !Array.isArray(defaultSettings)
                    ? defaultSettings
                    : {};

            if (typeof window === 'undefined') {
                return defaults;
            }

            const storageDefaults = {};

            Object.keys(controlPanelSettingKeys).forEach((key) => {
                const persistedSettingKey = controlPanelSettingKeys[key];

                if (typeof persistedSettingKey !== 'string') {
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(defaults, persistedSettingKey)) {
                    storageDefaults[persistedSettingKey] = defaults[persistedSettingKey];
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(defaults, key)) {
                    storageDefaults[persistedSettingKey] = defaults[key];
                }
            });

            migrateSettingsOverrides(storageDefaults);

            let helperOverrides = {};

            if (typeof window.getUserSettingsOverrides === 'function') {
                const resolvedOverrides = window.getUserSettingsOverrides();

                if (
                    resolvedOverrides &&
                    typeof resolvedOverrides === 'object' &&
                    !Array.isArray(resolvedOverrides)
                ) {
                    helperOverrides = resolvedOverrides;
                }
            }

            let storageOverrides = {};

            if (typeof localStorage !== 'undefined') {
                try {
                    const parsedOverrides = JSON.parse(
                        localStorage.getItem(athkarSettingsUserOverridesStorageKey) ?? 'null',
                    );

                    if (
                        parsedOverrides &&
                        typeof parsedOverrides === 'object' &&
                        !Array.isArray(parsedOverrides)
                    ) {
                        storageOverrides = parsedOverrides;
                    }
                } catch (_) {
                    storageOverrides = {};
                }
            }

            const userOverrides = {
                ...storageOverrides,
                ...helperOverrides,
            };

            if (
                !userOverrides ||
                typeof userOverrides !== 'object' ||
                Array.isArray(userOverrides)
            ) {
                return defaults;
            }

            const merged = { ...defaults };
            const applyOverrideValue = (key, value) => {
                merged[key] = value;

                if (
                    Object.prototype.hasOwnProperty.call(controlPanelSettingKeys, key) &&
                    typeof controlPanelSettingKeys[key] === 'string'
                ) {
                    merged[controlPanelSettingKeys[key]] = value;
                }
            };

            Object.keys(defaults).forEach((key) => {
                const persistedSettingKey =
                    Object.prototype.hasOwnProperty.call(controlPanelSettingKeys, key) &&
                    typeof controlPanelSettingKeys[key] === 'string'
                        ? controlPanelSettingKeys[key]
                        : key;

                if (Object.prototype.hasOwnProperty.call(userOverrides, key)) {
                    applyOverrideValue(key, userOverrides[key]);

                    return;
                }

                if (Object.prototype.hasOwnProperty.call(userOverrides, persistedSettingKey)) {
                    applyOverrideValue(key, userOverrides[persistedSettingKey]);
                }
            });

            Object.keys(controlPanelSettingKeys).forEach((key) => {
                const persistedSettingKey = controlPanelSettingKeys[key];

                if (
                    typeof persistedSettingKey !== 'string' ||
                    Object.prototype.hasOwnProperty.call(merged, persistedSettingKey)
                ) {
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(userOverrides, persistedSettingKey)) {
                    applyOverrideValue(key, userOverrides[persistedSettingKey]);

                    return;
                }

                if (Object.prototype.hasOwnProperty.call(userOverrides, key)) {
                    applyOverrideValue(key, userOverrides[key]);
                }
            });

            return merged;
        },

        applyControlPanelSettings(controlPanel = {}) {
            const previousWirdSignature = this.resolveWirdRecordSignature();
            const mergedDefaults =
                controlPanel && typeof controlPanel === 'object' && !Array.isArray(controlPanel)
                    ? {
                          ...(this.initialSettings && typeof this.initialSettings === 'object'
                              ? this.initialSettings
                              : {}),
                          ...controlPanel,
                      }
                    : {
                          ...(this.initialSettings && typeof this.initialSettings === 'object'
                              ? this.initialSettings
                              : {}),
                      };
            const resolvedSettings =
                this.resolveControlPanelSettingsWithUserOverrides(mergedDefaults);

            this.westernNumeralCharacters = this.normalizeNumeralCharacters(
                resolvedSettings?.numeralCharacters?.western,
                defaultWesternNumerals,
            );
            this.arabicNumeralCharacters = this.normalizeNumeralCharacters(
                resolvedSettings?.numeralCharacters?.arabic,
                defaultArabicNumerals,
            );

            this.doesEnableVisualEnhancements = this.normalizeBooleanFlag(
                resolvedSettings?.enableVisualEnhancements ??
                    resolvedSettings?.[controlPanelSettingKeys.enableVisualEnhancements],
                false,
            );
            this.doesTargetWordsByDefault = this.normalizeBooleanFlag(
                resolvedSettings?.targetWordsByDefault ??
                    resolvedSettings?.[controlPanelSettingKeys.targetWordsByDefault],
                false,
            );
            this.doesPreserveHarakatOnCopy = this.normalizeBooleanFlag(
                resolvedSettings?.preserveHarakatOnCopy ??
                    resolvedSettings?.[controlPanelSettingKeys.preserveHarakatOnCopy],
                true,
            );
            this.doesAppendSurahAffixOnMultiCopy = this.normalizeBooleanFlag(
                resolvedSettings?.appendSurahAffixOnMultiCopy ??
                    resolvedSettings?.[controlPanelSettingKeys.appendSurahAffixOnMultiCopy],
                true,
            );
            this.doesAppendSurahAffixAlwaysOnCopy = this.normalizeBooleanFlag(
                resolvedSettings?.appendSurahAffixAlwaysOnCopy ??
                    resolvedSettings?.[controlPanelSettingKeys.appendSurahAffixAlwaysOnCopy],
                false,
            );
            this.doesShowImmersiveMobileEdgeCaptions = this.normalizeBooleanFlag(
                resolvedSettings?.showImmersiveMobileEdgeCaptions ??
                    resolvedSettings?.[controlPanelSettingKeys.showImmersiveMobileEdgeCaptions],
                true,
            );
            this.doesUseVolumeButtonsNavigation = this.normalizeBooleanFlag(
                resolvedSettings?.useVolumeButtonsNavigation ??
                    resolvedSettings?.[controlPanelSettingKeys.useVolumeButtonsNavigation],
                false,
            );
            this.doesUseWesternNumerals = this.normalizeBooleanFlag(
                resolvedSettings?.useWesternNumerals ??
                    resolvedSettings?.[controlPanelSettingKeys.useWesternNumerals],
                true,
            );
            this.wirdFrequencyMode = this.normalizeWirdFrequencyMode(
                resolvedSettings?.wirdFrequencyMode ??
                    resolvedSettings?.[controlPanelSettingKeys.wirdFrequencyMode],
                this.normalizeWirdFrequencyMode(
                    this.initialSettings?.wirdFrequencyMode,
                    wirdFrequencyModeMonthly,
                ),
            );
            this.wirdKhatmatTarget = this.normalizeWirdKhatmatTarget(
                resolvedSettings?.wirdKhatmatTarget ??
                    resolvedSettings?.[controlPanelSettingKeys.wirdKhatmatTarget],
                this.normalizeWirdKhatmatTarget(this.initialSettings?.wirdKhatmatTarget, 1, {
                    frequencyMode: this.wirdFrequencyMode,
                }),
                {
                    frequencyMode: this.wirdFrequencyMode,
                },
            );

            const nextWirdSignature = this.resolveWirdRecordSignature();

            if (nextWirdSignature !== previousWirdSignature) {
                this.ensureWirdDailyRecord({ forceRebuild: true });

                if (this.wirdModeActive) {
                    void this.exitWirdMode({
                        restoreNormalPage: true,
                        reason: 'settings-change',
                    });
                }
            } else {
                this.ensureWirdDailyRecord();
            }

            this.$nextTick(() => {
                this.syncSupportLockTargetsUi();
                this.syncNativeVolumeNavigation();
            });
        },

        interactionTargetsWords() {
            return Boolean(this.doesTargetWordsByDefault);
        },

        usesMobileDoubleTapCopyMode() {
            if (typeof this.$store?.bp?.isTouch === 'function') {
                return Boolean(this.$store.bp.isTouch() || this.$store.bp.hasTouch);
            }

            return Boolean(this.$store?.bp?.hasTouch);
        },

        activeQuranReaderView() {
            if (this.views?.['quran-app-tadabbur']?.isOpen) {
                return 'quran-app-tadabbur';
            }

            if (this.views?.['quran-app-hifth']?.isOpen) {
                return 'quran-app-hifth';
            }

            if (this.views?.['quran-app-tilawa']?.isOpen) {
                return 'quran-app-tilawa';
            }

            const fallbackView = String(this._lastQuranReaderView ?? '').trim();

            if (
                ['quran-app-tilawa', 'quran-app-hifth', 'quran-app-tadabbur'].includes(fallbackView)
            ) {
                return fallbackView;
            }

            return 'quran-app-tilawa';
        },

        isAnyQuranReaderViewOpen() {
            return Boolean(
                this.views?.['quran-app-tilawa']?.isOpen ||
                this.views?.['quran-app-hifth']?.isOpen ||
                this.views?.['quran-app-tadabbur']?.isOpen,
            );
        },

        isAnyAthkarViewOpen() {
            return Boolean(
                this.views?.['athkar-app-gate']?.isOpen ||
                this.views?.['athkar-app-gate']?.isReaderVisible ||
                this.views?.['athkar-app-sabah']?.isOpen ||
                this.views?.['athkar-app-masaa']?.isOpen,
            );
        },

        shouldShowCalibrationHud() {
            return Boolean(
                this.isCalibrating &&
                this._startupCalibrationPending &&
                !this.hasCompletedInitialMushafPreparation &&
                this.isAnyQuranReaderViewOpen() &&
                !this.isAnyAthkarViewOpen() &&
                this.isReaderPanelVisible(),
            );
        },

        shouldPersistActivationIndexes() {
            return this.activeQuranReaderView() === 'quran-app-tadabbur';
        },

        clearActivationIndexes() {
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;
            this.searchHighlightedAyahIndex = 0;
        },

        isSelectableWord(word) {
            const ayahIndex = Number(word?.ayah_index ?? 0);
            const wordIndex = Number(word?.word_index ?? 0);

            if (this.interactionTargetsWords()) {
                return Number.isFinite(wordIndex) && wordIndex > 0;
            }

            return Number.isFinite(ayahIndex) && ayahIndex > 0;
        },

        selectAyah(ayahIndex) {
            const normalizedAyahIndex = Number(ayahIndex);

            if (!Number.isFinite(normalizedAyahIndex) || normalizedAyahIndex < 1) {
                return false;
            }
            const normalized = Math.trunc(normalizedAyahIndex);
            this.searchHighlightedAyahIndex = 0;

            if (!this.shouldPersistActivationIndexes()) {
                this.clearActivationIndexes();

                return true;
            }

            if (this.activeAyahIndex === normalized) {
                this.activeAyahIndex = 0;
                this.hoveredAyahIndex = 0;
                this.activeWordIndex = 0;
                this.hoveredWordIndex = 0;

                return false;
            }

            this.activeAyahIndex = normalized;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;

            return true;
        },

        selectWord(wordIndex) {
            const normalizedWordIndex = Number(wordIndex);

            if (!Number.isFinite(normalizedWordIndex) || normalizedWordIndex < 1) {
                return false;
            }

            const normalized = Math.trunc(normalizedWordIndex);
            this.searchHighlightedAyahIndex = 0;

            if (!this.shouldPersistActivationIndexes()) {
                this.clearActivationIndexes();

                return true;
            }

            if (this.activeWordIndex === normalized) {
                this.activeWordIndex = 0;
                this.hoveredWordIndex = 0;
                this.activeAyahIndex = 0;
                this.hoveredAyahIndex = 0;

                return false;
            }

            this.activeWordIndex = normalized;
            this.hoveredWordIndex = 0;
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;

            return true;
        },

        selectDefaultSegment(word, activationAnchor = null) {
            if (this.interactionTargetsWords()) {
                const isActivated = this.selectWord(Number(word?.word_index ?? 0));

                if (isActivated) {
                    void this.copyWordSelection(word, activationAnchor);
                }

                return;
            }

            const ayahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));
            const isActivated = this.selectAyah(ayahIndex);

            if (isActivated) {
                void this.copyAyahSelection(ayahIndex, activationAnchor);
            }
        },

        selectHoldSegment(word, activationAnchor = null) {
            if (this.interactionTargetsWords()) {
                const ayahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));
                const isActivated = this.selectAyah(ayahIndex);

                if (isActivated) {
                    void this.copyAyahSelection(ayahIndex, activationAnchor);
                }

                return;
            }

            const isActivated = this.selectWord(Number(word?.word_index ?? 0));

            if (isActivated) {
                void this.copyWordSelection(word, activationAnchor);
            }
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

        readerPanelCenterPoint() {
            const panelElement = this.$refs.readerPanel;
            const panelRect = panelElement?.getBoundingClientRect?.();

            if (
                !Number.isFinite(panelRect?.left) ||
                !Number.isFinite(panelRect?.top) ||
                !Number.isFinite(panelRect?.width)
            ) {
                return {
                    x: Math.max(0, Math.round((window.innerWidth ?? 0) / 2)),
                    y: Math.max(0, Math.round((window.innerHeight ?? 0) / 2)),
                };
            }

            return {
                x: panelRect.left + panelRect.width / 2,
                y: panelRect.top + 56,
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

            const swipePoint = this.swipePoint(anchor);

            if (swipePoint && Number.isFinite(swipePoint.x) && Number.isFinite(swipePoint.y)) {
                return {
                    x: swipePoint.x,
                    y: swipePoint.y,
                };
            }

            const targetPoint = this.copyPointFromElement(anchor?.target ?? null);

            if (targetPoint) {
                return targetPoint;
            }

            return this.readerPanelCenterPoint();
        },

        copiedWordKey(word) {
            return this.wordSelectionKeyFromMeta(this.normalizeSelectableWordMeta(word));
        },

        isWordCopied(word) {
            const wordKey = this.copiedWordKey(word);

            if (!wordKey || !Array.isArray(this.copiedHighlights.wordKeys)) {
                return false;
            }

            return this.copiedHighlights.wordKeys.includes(wordKey);
        },

        isAyahClusterCopied(cluster) {
            const ayahIndex = Math.max(0, Math.trunc(Number(cluster?.ayahIndex ?? 0)));

            if (ayahIndex < 1 || !Array.isArray(this.copiedHighlights.ayahIndexes)) {
                return false;
            }

            return this.copiedHighlights.ayahIndexes.includes(ayahIndex);
        },

        clearCopiedHighlights() {
            if (this._copiedHighlightTimer !== null) {
                clearTimeout(this._copiedHighlightTimer);
                this._copiedHighlightTimer = null;
            }

            this.copiedHighlights.wordKeys = [];
            this.copiedHighlights.ayahIndexes = [];
        },

        applyCopiedHighlights({ words = [], ayahIndexes = [] } = {}) {
            const uniqueWordKeys = [
                ...new Set(
                    (Array.isArray(words) ? words : [])
                        .map((word) => this.copiedWordKey(word))
                        .filter((wordKey) => typeof wordKey === 'string' && wordKey !== ''),
                ),
            ];
            const uniqueAyahIndexes = [
                ...new Set(
                    (Array.isArray(ayahIndexes) ? ayahIndexes : [])
                        .map((ayahIndex) => Math.max(0, Math.trunc(Number(ayahIndex ?? 0))))
                        .filter((ayahIndex) => ayahIndex > 0),
                ),
            ];

            this.copiedHighlights.wordKeys = uniqueWordKeys;
            this.copiedHighlights.ayahIndexes = uniqueAyahIndexes;

            if (this._copiedHighlightTimer !== null) {
                clearTimeout(this._copiedHighlightTimer);
                this._copiedHighlightTimer = null;
            }

            if (uniqueWordKeys.length < 1 && uniqueAyahIndexes.length < 1) {
                return;
            }

            this._copiedHighlightTimer = window.setTimeout(() => {
                this.clearCopiedHighlights();
            }, copiedHighlightVisibleDurationMs);
        },

        setWordClickSuppression(
            enabled = false,
            { durationMs = wordClickSuppressionResetMs } = {},
        ) {
            this._suppressNextWordClick = Boolean(enabled);

            if (this._suppressWordClickResetTimer !== null) {
                clearTimeout(this._suppressWordClickResetTimer);
                this._suppressWordClickResetTimer = null;
            }

            if (!this._suppressNextWordClick) {
                return;
            }

            this._suppressWordClickResetTimer = window.setTimeout(
                () => {
                    this._suppressNextWordClick = false;
                    this._suppressWordClickResetTimer = null;
                },
                Math.max(120, Math.trunc(Number(durationMs) || wordClickSuppressionResetMs)),
            );
        },

        normalizeSelectableWordMeta(word, fallbackWordIndex = 0) {
            return {
                ayahIndex: Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0))),
                ayahNumber: Math.max(0, Math.trunc(Number(word?.ayah_number ?? 0))),
                surahNumber: Math.max(0, Math.trunc(Number(word?.surah_number ?? 0))),
                wordIndex: Math.max(
                    0,
                    Math.trunc(Number(word?.word_index ?? fallbackWordIndex ?? 0)),
                ),
            };
        },

        wordSelectionKeyFromMeta(meta = {}) {
            const ayahIndex = Math.max(0, Math.trunc(Number(meta?.ayahIndex ?? 0)));
            const wordIndex = Math.max(0, Math.trunc(Number(meta?.wordIndex ?? 0)));

            if (ayahIndex < 1 || wordIndex < 1) {
                return null;
            }

            return `${ayahIndex}:${wordIndex}`;
        },

        rebuildWordSelectionIndex() {
            this._wordBySelectionKey = new Map();
            this._ayahNumberByIndex = new Map();
            this._surahNumberByAyahIndex = new Map();

            if (!Array.isArray(this.mushafLines)) {
                return;
            }

            this.mushafLines.forEach((line) => {
                if (!Array.isArray(line?.words)) {
                    return;
                }

                line.words.forEach((word, wordOffset) => {
                    const wordMeta = this.normalizeSelectableWordMeta(word, wordOffset + 1);
                    const selectionKey = this.wordSelectionKeyFromMeta(wordMeta);

                    if (selectionKey && !this._wordBySelectionKey.has(selectionKey)) {
                        this._wordBySelectionKey.set(selectionKey, word);
                    }

                    if (
                        wordMeta.ayahIndex > 0 &&
                        wordMeta.ayahNumber > 0 &&
                        !this._ayahNumberByIndex.has(wordMeta.ayahIndex)
                    ) {
                        this._ayahNumberByIndex.set(wordMeta.ayahIndex, wordMeta.ayahNumber);
                    }

                    if (
                        wordMeta.ayahIndex > 0 &&
                        wordMeta.surahNumber > 0 &&
                        !this._surahNumberByAyahIndex.has(wordMeta.ayahIndex)
                    ) {
                        this._surahNumberByAyahIndex.set(wordMeta.ayahIndex, wordMeta.surahNumber);
                    }
                });
            });
        },

        wordFromButtonElement(buttonElement) {
            if (!(buttonElement instanceof Element)) {
                return null;
            }

            const wordMeta = {
                ayahIndex: Math.max(
                    0,
                    Math.trunc(Number(buttonElement.getAttribute('data-quran-ayah-index') ?? 0)),
                ),
                wordIndex: Math.max(
                    0,
                    Math.trunc(Number(buttonElement.getAttribute('data-quran-word-index') ?? 0)),
                ),
                ayahNumber: Math.max(
                    0,
                    Math.trunc(Number(buttonElement.getAttribute('data-quran-ayah-number') ?? 0)),
                ),
                surahNumber: Math.max(
                    0,
                    Math.trunc(Number(buttonElement.getAttribute('data-quran-surah-number') ?? 0)),
                ),
            };
            const selectionKey = this.wordSelectionKeyFromMeta(wordMeta);

            if (selectionKey) {
                const indexedWord = this._wordBySelectionKey.get(selectionKey);

                if (indexedWord) {
                    return indexedWord;
                }
            }

            const fallbackText = normalizeTextValue(buttonElement.textContent);

            if (!fallbackText || wordMeta.ayahIndex < 1) {
                return null;
            }

            return {
                ayah_index: wordMeta.ayahIndex,
                ayah_number: wordMeta.ayahNumber,
                surah_number: wordMeta.surahNumber,
                word_index: wordMeta.wordIndex,
                text: fallbackText,
                copy_text: fallbackText,
            };
        },

        wordButtonElementFromPoint(x, y) {
            if (typeof document === 'undefined' || !Number.isFinite(x) || !Number.isFinite(y)) {
                return null;
            }

            const elementAtPoint = document.elementFromPoint(x, y);

            if (!(elementAtPoint instanceof Element)) {
                return null;
            }

            const buttonElement = elementAtPoint.closest('[data-quran-word-button]');

            if (!(buttonElement instanceof Element)) {
                return null;
            }

            return buttonElement;
        },

        collectWordPressTrailWord(word, activationAnchor = null) {
            if (!this.wordPress.active) {
                return false;
            }

            const wordMeta = this.normalizeSelectableWordMeta(word);

            if (wordMeta.ayahIndex < 1) {
                return false;
            }

            if (wordMeta.ayahNumber > 0 && !this._ayahNumberByIndex.has(wordMeta.ayahIndex)) {
                this._ayahNumberByIndex.set(wordMeta.ayahIndex, wordMeta.ayahNumber);
            }

            if (
                wordMeta.ayahIndex > 0 &&
                wordMeta.surahNumber > 0 &&
                !this._surahNumberByAyahIndex.has(wordMeta.ayahIndex)
            ) {
                this._surahNumberByAyahIndex.set(wordMeta.ayahIndex, wordMeta.surahNumber);
            }

            if (this.interactionTargetsWords()) {
                const selectionKey = this.wordSelectionKeyFromMeta(wordMeta);

                if (!selectionKey || this.wordPress.trailWordKeys.includes(selectionKey)) {
                    if (activationAnchor) {
                        this.wordPress.lastAnchor = activationAnchor;
                    }

                    return false;
                }

                this.wordPress.trailWordKeys.push(selectionKey);
                this.wordPress.trailWords.push(word);
            } else if (!this.wordPress.trailAyahIndexes.includes(wordMeta.ayahIndex)) {
                this.wordPress.trailAyahIndexes.push(wordMeta.ayahIndex);
            } else {
                if (activationAnchor) {
                    this.wordPress.lastAnchor = activationAnchor;
                }

                return false;
            }

            if (activationAnchor) {
                this.wordPress.lastAnchor = activationAnchor;
            }

            return true;
        },

        ayahSplitterToken(ayahIndex, fallbackAyahNumber = 0) {
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));
            const mappedAyahNumber = Math.max(
                0,
                Math.trunc(Number(this._ayahNumberByIndex.get(normalizedAyahIndex) ?? 0)),
            );
            const normalizedFallbackAyahNumber = Math.max(
                0,
                Math.trunc(Number(fallbackAyahNumber ?? 0)),
            );
            const ayahNumber =
                normalizedFallbackAyahNumber || mappedAyahNumber || normalizedAyahIndex;

            if (ayahNumber < 1) {
                return null;
            }

            return `(${this.formatAyahTokenNumber(ayahNumber)})`;
        },

        selectedDraggedAyahIndexes() {
            const sourceAyahIndexes = this.interactionTargetsWords()
                ? this.wordPress.trailWords.map(
                      (word) => this.normalizeSelectableWordMeta(word).ayahIndex,
                  )
                : this.wordPress.trailAyahIndexes;

            return sourceAyahIndexes
                .map((ayahIndex) => Math.max(0, Math.trunc(Number(ayahIndex ?? 0))))
                .filter(
                    (ayahIndex, index, array) =>
                        ayahIndex > 0 && array.indexOf(ayahIndex) === index,
                )
                .sort((firstAyahIndex, secondAyahIndex) => firstAyahIndex - secondAyahIndex);
        },

        selectedDraggedSurahNumbers() {
            const selectedAyahIndexes = this.selectedDraggedAyahIndexes();
            const surahNumbers = [];

            selectedAyahIndexes.forEach((ayahIndex) => {
                const surahNumber = this.surahNumberForAyahIndex(ayahIndex);

                if (surahNumber < 1 || surahNumbers.includes(surahNumber)) {
                    return;
                }

                surahNumbers.push(surahNumber);
            });

            return surahNumbers;
        },

        surahNumberForAyahIndex(ayahIndex) {
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));

            if (normalizedAyahIndex < 1) {
                return 0;
            }

            const mappedSurahNumber = Math.max(
                0,
                Math.trunc(Number(this._surahNumberByAyahIndex.get(normalizedAyahIndex) ?? 0)),
            );

            if (mappedSurahNumber > 0) {
                return mappedSurahNumber;
            }

            if (!Array.isArray(this.mushafLines)) {
                return 0;
            }

            for (const line of this.mushafLines) {
                if (!Array.isArray(line?.words)) {
                    continue;
                }

                for (const word of line.words) {
                    const wordAyahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));

                    if (wordAyahIndex !== normalizedAyahIndex) {
                        continue;
                    }

                    const wordSurahNumber = Math.max(
                        0,
                        Math.trunc(Number(word?.surah_number ?? line?.surah_number ?? 0)),
                    );

                    if (wordSurahNumber < 1) {
                        continue;
                    }

                    this._surahNumberByAyahIndex.set(normalizedAyahIndex, wordSurahNumber);

                    return wordSurahNumber;
                }
            }

            return 0;
        },
    };
};
