document.addEventListener('alpine:init', () => {
    window.Alpine.data('mainMenu', (el, config = {}) => ({
        containerHovered: false,
        currentCaption: '',
        isItemActive: false,
        isIdle: true,
        isHidden: true,
        splitInstance: null,
        captionAnimation: null,
        idleTimeout: null,
        animationToken: 0,
        pendingCaption: null,
        captionShadow: null,
        captionShadowDark: null,
        lockedCaption: 'قريبا...',
        activeItemElement: null,
        activeItemCaption: null,
        activeItemLocked: false,
        lockedItemElement: null,
        touchActiveElement: null,
        touchStartItem: null,
        touchStartWasActive: false,
        touchLeftStartItem: false,
        lastTouchMoved: false,
        lastTouchAt: 0,
        touchMoved: false,
        touchStartX: null,
        touchStartY: null,
        touchLastX: null,
        touchLastY: null,
        isTouching: false,
        lockWiggles: new WeakMap(),
        isTouchDevice: false,
        doesEnableVisualEnhancements: false,
        isInsightsExpanded: false,
        isInsightsFastClosing: false,
        isInsightsPointerInside: false,
        insightsHideDelayMs: 2500,
        insightsHideTimer: null,
        insightsRefreshTimer: null,
        insightsPanelHeight: 0,
        insightsFastCloseDurationMs: 315,
        insightsGateLaunchDelayMs: 420,
        progressLabels: {
            sabah: config?.progressLabels?.sabah ?? 'أذكار الصباح',
            wird: config?.progressLabels?.wird ?? 'الوِرد اليومي',
            masaa: config?.progressLabels?.masaa ?? 'أذكار المساء',
        },
        progressStateLabels: {
            completed: config?.progressStateLabels?.completed ?? 'مكتمل',
            inProgress: config?.progressStateLabels?.inProgress ?? 'قيد التقدّم',
            notStarted: config?.progressStateLabels?.notStarted ?? 'لم يبدأ',
        },
        dailyProgress: {
            sabah: { percent: 0, isComplete: false, stateLabel: '' },
            wird: { percent: 0, isComplete: false, stateLabel: '' },
            masaa: { percent: 0, isComplete: false, stateLabel: '' },
        },
        insightsRowOrder: ['sabah', 'wird', 'masaa'],
        insightsMostlyDoneThreshold: 70,
        _onWindowMouseMove: null,
        _onWindowBlur: null,
        _onWindowPointerDown: null,
        _onWindowStorage: null,
        _onSwitchView: null,
        _onWindowResize: null,
        _insightsFastCloseToken: null,
        _insightsGateLaunchTimer: null,
        _quranWirdAutoEntryTimer: null,
        _quranWirdAutoEntryDeadlineAt: 0,
        shouldAutoEnterQuranWirdMode: false,
        init() {
            this.captionShadow = window.makeBoxShadowFromColor?.('--primary-500') ?? 'none';
            this.captionShadowDark = window.makeBoxShadowFromColor?.('--primary-100') ?? 'none';
            this.isTouchDevice =
                'ontouchstart' in window ||
                window.matchMedia?.('(pointer: coarse)')?.matches ||
                navigator.maxTouchPoints > 0;
            this.refreshVisualEnhancementsSetting();
            el.addEventListener('mouseenter', () => (this.containerHovered = true));
            el.addEventListener('mouseleave', () => (this.containerHovered = false));

            this.$watch('containerHovered', (value) => {
                if (!value) {
                    this.handleOutside(false, {
                        preserveInsights: this.isInsightsPointerInside,
                    });
                } else if (!this.isItemActive) {
                    this.idleCaption();
                }
            });

            this._onWindowMouseMove = (event) => {
                if (this.isTouchDevice || this.isTouching || !this.containerHovered) {
                    return;
                }

                const pointerX = Number(event?.clientX);
                const pointerY = Number(event?.clientY);

                if (!Number.isFinite(pointerX) || !Number.isFinite(pointerY)) {
                    return;
                }

                if (this.isPointInsideGrid(pointerX, pointerY)) {
                    return;
                }

                if (this.isPointInsideInsightsZone(pointerX, pointerY)) {
                    this.handleOutside(false, { preserveInsights: true });
                    return;
                }

                this.handleOutside(true);
            };

            this._onWindowBlur = () => {
                this.handleOutside(true);
            };

            window.addEventListener('mousemove', this._onWindowMouseMove, {
                passive: true,
            });
            window.addEventListener('blur', this._onWindowBlur);

            this._onWindowPointerDown = (event) => {
                this.handleInsightsWindowPointerDown(event);
            };
            this._onWindowStorage = () => {
                this.refreshDailyProgress();
                this.refreshVisualEnhancementsSetting();
            };
            this._onSwitchView = (event) => {
                const nextView = String(event?.detail?.to ?? '').trim();
                const shouldPreserveGateLaunch =
                    this._insightsGateLaunchTimer !== null &&
                    (nextView === 'athkar-app-gate' || nextView === 'quran-app-gate');

                if (nextView === 'main-menu') {
                    this.clearInsightsGateLaunchTimer();
                    this.clearQuranWirdAutoEntry();
                    this.refreshDailyProgress();
                    this.refreshVisualEnhancementsSetting();
                    this.measureInsightsPanelHeight();
                    return;
                }

                if (nextView.startsWith('quran-app-') && this.shouldAutoEnterQuranWirdMode) {
                    this.scheduleQuranWirdAutoEntry();
                } else if (!nextView.startsWith('quran-app-')) {
                    this.clearQuranWirdAutoEntry();
                }

                this.resetInsightsPanelState({
                    preservePendingGateLaunch: shouldPreserveGateLaunch,
                });
            };
            this._onWindowResize = () => {
                this.measureInsightsPanelHeight();
            };

            window.addEventListener('pointerdown', this._onWindowPointerDown, true);
            window.addEventListener('storage', this._onWindowStorage);
            window.addEventListener('switch-view', this._onSwitchView);
            window.addEventListener('resize', this._onWindowResize, {
                passive: true,
            });

            this.refreshDailyProgress();
            this.$nextTick(() => {
                this.measureInsightsPanelHeight();
            });

            this.$watch('isInsightsExpanded', (isExpanded) => {
                if (isExpanded) {
                    this.refreshDailyProgress();
                    this.startInsightsRefreshLoop();
                } else {
                    this.stopInsightsRefreshLoop();
                }
            });
        },
        destroy() {
            this.clearInsightsHideTimer();
            this.clearInsightsGateLaunchTimer();
            this.stopInsightsRefreshLoop();
            this.clearQuranWirdAutoEntry();

            if (this._onWindowMouseMove) {
                window.removeEventListener('mousemove', this._onWindowMouseMove);
                this._onWindowMouseMove = null;
            }

            if (this._onWindowBlur) {
                window.removeEventListener('blur', this._onWindowBlur);
                this._onWindowBlur = null;
            }

            if (this._onWindowPointerDown) {
                window.removeEventListener('pointerdown', this._onWindowPointerDown, true);
                this._onWindowPointerDown = null;
            }

            if (this._onWindowStorage) {
                window.removeEventListener('storage', this._onWindowStorage);
                this._onWindowStorage = null;
            }

            if (this._onSwitchView) {
                window.removeEventListener('switch-view', this._onSwitchView);
                this._onSwitchView = null;
            }

            if (this._onWindowResize) {
                window.removeEventListener('resize', this._onWindowResize);
                this._onWindowResize = null;
            }
        },
        getCaptionElements() {
            const captionWrap = this.$refs?.captionWrap;
            const captionText = this.$refs?.captionText;

            if (!captionWrap || !captionText) {
                return null;
            }

            return { captionWrap, captionText };
        },
        getItemsGrid() {
            return this.$refs?.itemsGrid ?? null;
        },
        getItemFromPoint(x, y) {
            const element = document.elementFromPoint(x, y);

            if (!element) {
                return null;
            }

            return element.closest('[data-main-menu-item]');
        },
        isPointInsideGrid(x, y) {
            const grid = this.getItemsGrid();

            if (!grid) {
                return false;
            }

            const rect = grid.getBoundingClientRect();

            return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
        },
        getInsightsZoneElement() {
            return this.$refs?.insightsZone ?? null;
        },
        isPointInsideInsightsZone(x, y) {
            const insightsZone = this.getInsightsZoneElement();

            if (!(insightsZone instanceof Element)) {
                return false;
            }

            const rect = insightsZone.getBoundingClientRect();

            return x >= rect.left && x <= rect.right && y >= rect.top && y <= rect.bottom;
        },
        isInsightsZoneHoveredNow() {
            const insightsZone = this.getInsightsZoneElement();

            if (!(insightsZone instanceof Element) || this.isTouchDevice) {
                return false;
            }

            try {
                return insightsZone.matches(':hover');
            } catch {
                return false;
            }
        },
        handleRootOutsideClick() {
            if (!this.isTouchDevice && this.isInsightsExpanded) {
                this.collapseInsightsForNavigation();
            }

            this.handleOutside(true);
        },
        normalizeProgressPercent(value) {
            const numeric = Number(value ?? NaN);

            if (!Number.isFinite(numeric)) {
                return 0;
            }

            return Math.max(0, Math.min(100, Math.round(numeric)));
        },
        resolveProgressStateLabel(percent, isComplete) {
            if (isComplete || percent >= 100) {
                return this.progressStateLabels.completed;
            }

            if (percent > 0) {
                return this.progressStateLabels.inProgress;
            }

            return this.progressStateLabels.notStarted;
        },
        setDailyProgressEntry(key, percent, isComplete) {
            if (!Object.prototype.hasOwnProperty.call(this.dailyProgress, key)) {
                return;
            }

            const normalizedPercent = this.normalizeProgressPercent(percent);
            const normalizedCompletion = Boolean(isComplete) || normalizedPercent >= 100;

            this.dailyProgress[key] = {
                percent: normalizedCompletion ? 100 : normalizedPercent,
                isComplete: normalizedCompletion,
                stateLabel: this.resolveProgressStateLabel(
                    normalizedCompletion ? 100 : normalizedPercent,
                    normalizedCompletion,
                ),
            };
        },
        getAthkarReaderData() {
            if (!window.Alpine?.$data) {
                return null;
            }

            const root = document.querySelector('[data-athkar-app-reader-root]');

            if (!(root instanceof Element)) {
                return null;
            }

            try {
                return window.Alpine.$data(root);
            } catch {
                return null;
            }
        },
        getQuranReaderData() {
            if (!window.Alpine?.$data) {
                return null;
            }

            const root = document.querySelector('[data-quran-app-reader-root]');

            if (!(root instanceof Element)) {
                return null;
            }

            try {
                return window.Alpine.$data(root);
            } catch {
                return null;
            }
        },
        refreshAthkarProgress(mode) {
            const athkarReader = this.getAthkarReaderData();

            if (!athkarReader) {
                this.setDailyProgressEntry(mode, 0, false);
                return;
            }

            try {
                if (typeof athkarReader.ensureState === 'function') {
                    athkarReader.ensureState();
                }

                if (typeof athkarReader.syncDay === 'function') {
                    athkarReader.syncDay();
                }

                if (typeof athkarReader.ensureProgress === 'function') {
                    athkarReader.ensureProgress(mode);
                }

                const metrics =
                    typeof athkarReader.ensureModeMetrics === 'function'
                        ? athkarReader.ensureModeMetrics(mode)
                        : null;
                const requiredLetters = Number(metrics?.totalRequiredLetters ?? 0);
                const completedLetters = Number(metrics?.totalCompletedLetters ?? 0);
                const requiredCount = Number(metrics?.totalRequiredCount ?? 0);
                const completedCount = Number(metrics?.totalCompletedCount ?? 0);
                const useLettersScale = requiredLetters > 0;
                const requiredUnits = useLettersScale ? requiredLetters : requiredCount;
                const completedUnitsRaw = useLettersScale ? completedLetters : completedCount;
                const normalizedRequired = Number.isFinite(requiredUnits)
                    ? Math.max(0, requiredUnits)
                    : 0;
                const normalizedCompleted = Number.isFinite(completedUnitsRaw)
                    ? Math.max(0, completedUnitsRaw)
                    : 0;
                const percent =
                    normalizedRequired > 0
                        ? (Math.min(normalizedCompleted, normalizedRequired) / normalizedRequired) *
                          100
                        : 0;
                const isComplete =
                    typeof athkarReader.isModeComplete === 'function'
                        ? Boolean(athkarReader.isModeComplete(mode))
                        : percent >= 100;

                this.setDailyProgressEntry(mode, percent, isComplete);
            } catch {
                this.setDailyProgressEntry(mode, 0, false);
            }
        },
        refreshWirdProgress() {
            const quranReader = this.getQuranReaderData();

            if (!quranReader) {
                this.setDailyProgressEntry('wird', 0, false);
                return;
            }

            try {
                const record =
                    typeof quranReader.ensureWirdDailyRecord === 'function'
                        ? quranReader.ensureWirdDailyRecord()
                        : null;
                const percent =
                    typeof quranReader.wirdProgressPercent === 'function'
                        ? quranReader.wirdProgressPercent(record)
                        : 0;
                const isComplete = Boolean(record?.completed) || Number(percent) >= 100;

                this.setDailyProgressEntry('wird', percent, isComplete);
            } catch {
                this.setDailyProgressEntry('wird', 0, false);
            }
        },
        refreshDailyProgress() {
            this.refreshAthkarProgress('sabah');
            this.refreshWirdProgress();
            this.refreshAthkarProgress('masaa');
        },
        resolveInsightsRowPriority(entry) {
            const percent = this.normalizeProgressPercent(entry?.percent ?? 0);
            const isComplete = Boolean(entry?.isComplete) || percent >= 100;

            if (isComplete) {
                return 2;
            }

            if (percent >= this.insightsMostlyDoneThreshold) {
                return 0;
            }

            return 1;
        },
        sortedInsightsRows() {
            return this.insightsRowOrder
                .map((key, index) => {
                    const entry = this.dailyProgress?.[key] ?? {
                        percent: 0,
                        isComplete: false,
                        stateLabel: this.progressStateLabels.notStarted,
                    };
                    const percent = this.normalizeProgressPercent(entry.percent);

                    return {
                        key,
                        label: this.progressLabels?.[key] ?? key,
                        percent,
                        isComplete: Boolean(entry.isComplete) || percent >= 100,
                        stateLabel: String(entry.stateLabel ?? ''),
                        priority: this.resolveInsightsRowPriority(entry),
                        originalOrder: index,
                    };
                })
                .sort((left, right) => {
                    if (left.priority !== right.priority) {
                        return left.priority - right.priority;
                    }

                    if (left.percent !== right.percent) {
                        return right.percent - left.percent;
                    }

                    return left.originalOrder - right.originalOrder;
                });
        },
        measureInsightsPanelHeight() {
            const panelBody = this.$refs?.insightsPanelBody;

            if (!(panelBody instanceof HTMLElement)) {
                return;
            }

            const nextHeight = Math.max(0, Math.ceil(panelBody.scrollHeight));

            if (nextHeight !== this.insightsPanelHeight) {
                this.insightsPanelHeight = nextHeight;
            }
        },
        clearInsightsHideTimer() {
            if (this.insightsHideTimer !== null) {
                clearTimeout(this.insightsHideTimer);
                this.insightsHideTimer = null;
            }
        },
        clearInsightsGateLaunchTimer() {
            if (this._insightsGateLaunchTimer !== null) {
                clearTimeout(this._insightsGateLaunchTimer);
                this._insightsGateLaunchTimer = null;
            }
        },
        queueInsightsGateLaunch(task, delayMs = null) {
            if (typeof task !== 'function') {
                return;
            }

            this.clearInsightsGateLaunchTimer();

            const resolvedDelay = Math.max(
                0,
                Math.trunc(
                    Number(delayMs === null ? this.insightsGateLaunchDelayMs : delayMs) ||
                        this.insightsGateLaunchDelayMs,
                ),
            );

            this._insightsGateLaunchTimer = window.setTimeout(() => {
                this._insightsGateLaunchTimer = null;
                task();
            }, resolvedDelay);
        },
        clearQuranWirdAutoEntry() {
            if (this._quranWirdAutoEntryTimer !== null) {
                clearTimeout(this._quranWirdAutoEntryTimer);
                this._quranWirdAutoEntryTimer = null;
            }

            this._quranWirdAutoEntryDeadlineAt = 0;
            this.shouldAutoEnterQuranWirdMode = false;
        },
        runAfterInsightsCollapse(task) {
            if (typeof task !== 'function') {
                return;
            }

            const delayMs = this.collapseInsightsForNavigation();

            if (delayMs > 0) {
                window.setTimeout(() => {
                    task();
                }, delayMs);
                return;
            }

            task();
        },
        runViewNavigation(viewName) {
            const normalizedView = String(viewName ?? '').trim();

            if (normalizedView === '') {
                return;
            }

            this.executeItemCallback(`() => ($viewNav(\`${normalizedView}\`))`, this.$el);
        },
        runQuranEntryNavigation() {
            this.executeItemCallback('() => openQuranEntry()', this.$el);
        },
        runWhenQuranGateOpens(task, { timeoutMs = 26000 } = {}) {
            if (typeof task !== 'function') {
                return;
            }

            let switchViewListener = null;
            let guardTimeoutId = null;
            let didRun = false;

            const cleanup = () => {
                if (switchViewListener !== null) {
                    window.removeEventListener('switch-view', switchViewListener);
                    switchViewListener = null;
                }

                if (guardTimeoutId !== null) {
                    clearTimeout(guardTimeoutId);
                    guardTimeoutId = null;
                }
            };

            const runTaskOnce = () => {
                if (didRun) {
                    return;
                }

                didRun = true;
                cleanup();
                task();
            };

            switchViewListener = (event) => {
                const nextView = String(event?.detail?.to ?? '').trim();

                if (nextView !== 'quran-app-gate') {
                    return;
                }

                runTaskOnce();
            };

            window.addEventListener('switch-view', switchViewListener);

            const normalizedTimeout = Math.max(1200, Math.trunc(Number(timeoutMs) || 26000));
            guardTimeoutId = window.setTimeout(() => {
                cleanup();
            }, normalizedTimeout);
        },
        normalizeBooleanSettingValue(value, fallback = false) {
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

            if (normalized === 'true' || normalized === 'yes' || normalized === 'on') {
                return true;
            }

            if (normalized === 'false' || normalized === 'no' || normalized === 'off') {
                return false;
            }

            return Boolean(fallback);
        },
        readAthkarSettingFromStorage(settingName, storageKey = 'athkar-settings-v1') {
            if (typeof localStorage === 'undefined') {
                return null;
            }

            try {
                const raw = localStorage.getItem(storageKey);
                const parsed = raw ? JSON.parse(raw) : null;

                if (
                    !parsed ||
                    typeof parsed !== 'object' ||
                    Array.isArray(parsed) ||
                    !Object.prototype.hasOwnProperty.call(parsed, settingName)
                ) {
                    return null;
                }

                return parsed[settingName];
            } catch {
                return null;
            }
        },
        resolveVisualEnhancementsSetting() {
            const visualEnhancementsSettingKey = 'enable_visual_enhancements';
            const athkarReader = this.getAthkarReaderData();

            if (athkarReader && typeof athkarReader.settingValue === 'function') {
                try {
                    return this.normalizeBooleanSettingValue(
                        athkarReader.settingValue(visualEnhancementsSettingKey, false),
                        false,
                    );
                } catch {
                    // Fall through to other sources.
                }
            }

            const quranReader = this.getQuranReaderData();

            if (
                quranReader &&
                Object.prototype.hasOwnProperty.call(quranReader, 'doesEnableVisualEnhancements')
            ) {
                return this.normalizeBooleanSettingValue(
                    quranReader.doesEnableVisualEnhancements,
                    false,
                );
            }

            const userOverrideValue = this.readAthkarSettingFromStorage(
                visualEnhancementsSettingKey,
                'athkar-settings-user-overrides-v1',
            );

            if (userOverrideValue !== null) {
                return this.normalizeBooleanSettingValue(userOverrideValue, false);
            }

            const defaultStorageValue = this.readAthkarSettingFromStorage(
                visualEnhancementsSettingKey,
            );

            if (defaultStorageValue !== null) {
                return this.normalizeBooleanSettingValue(defaultStorageValue, false);
            }

            return false;
        },
        refreshVisualEnhancementsSetting() {
            this.doesEnableVisualEnhancements = this.resolveVisualEnhancementsSetting();
        },
        doesPreventSwitchingAthkarUntilCompletion() {
            const athkarReader = this.getAthkarReaderData();

            if (athkarReader && typeof athkarReader.settingValue === 'function') {
                try {
                    return Boolean(
                        athkarReader.settingValue(
                            'does_prevent_switching_athkar_until_completion',
                            true,
                        ),
                    );
                } catch {
                    // Fall through to storage/default.
                }
            }

            try {
                const raw = localStorage.getItem('athkar-settings-v1');
                const parsed = raw ? JSON.parse(raw) : null;
                const value = parsed?.does_prevent_switching_athkar_until_completion;

                if (typeof value === 'boolean') {
                    return value;
                }
            } catch {
                // Ignore malformed storage and fallback to default.
            }

            return true;
        },
        isQuranSupportUnlockedForWird() {
            const quranReader = this.getQuranReaderData();

            if (quranReader && typeof quranReader.isSupportLockActive === 'function') {
                try {
                    return !Boolean(quranReader.isSupportLockActive());
                } catch {
                    // Fall through to storage/default.
                }
            }

            try {
                const raw = localStorage.getItem('quran-support-unlock-v1');
                const parsed = raw ? JSON.parse(raw) : null;
                const mode = String(parsed?.mode ?? '')
                    .trim()
                    .toLowerCase();

                if (mode === 'permanent') {
                    return true;
                }

                if (mode === 'weekly') {
                    const expiresAt = Math.trunc(Number(parsed?.expires_at ?? 0));

                    return Number.isFinite(expiresAt) && expiresAt > Date.now();
                }
            } catch {
                // Ignore malformed storage and fallback to locked.
            }

            return false;
        },
        scheduleQuranWirdAutoEntry() {
            if (!this.shouldAutoEnterQuranWirdMode) {
                return;
            }

            if (this._quranWirdAutoEntryDeadlineAt <= 0) {
                this._quranWirdAutoEntryDeadlineAt = Date.now() + 16000;
            }

            if (this._quranWirdAutoEntryTimer !== null) {
                clearTimeout(this._quranWirdAutoEntryTimer);
                this._quranWirdAutoEntryTimer = null;
            }

            const attempt = () => {
                if (!this.shouldAutoEnterQuranWirdMode) {
                    this.clearQuranWirdAutoEntry();
                    return;
                }

                if (Date.now() >= this._quranWirdAutoEntryDeadlineAt) {
                    this.clearQuranWirdAutoEntry();
                    return;
                }

                const quranReader = this.getQuranReaderData();
                const wirdButton = document.querySelector('[data-quran-wird-toggle]');
                const isButtonVisible =
                    wirdButton instanceof HTMLElement && wirdButton.offsetParent !== null;
                const isSupportUnlocked = this.isQuranSupportUnlockedForWird();
                const isReaderReady =
                    Boolean(quranReader?.ready) && !Boolean(quranReader?.isLoadingPage);
                const isPageVisiblyReady =
                    quranReader && typeof quranReader.pageFitState === 'function'
                        ? quranReader.pageFitState() === 'ready'
                        : false;

                if (!isSupportUnlocked) {
                    this.clearQuranWirdAutoEntry();
                    return;
                }

                if (isButtonVisible && isReaderReady && isPageVisiblyReady) {
                    if (!quranReader?.wirdModeActive && wirdButton instanceof HTMLElement) {
                        wirdButton.click();
                    }

                    this.clearQuranWirdAutoEntry();
                    return;
                }

                this._quranWirdAutoEntryTimer = window.setTimeout(attempt, 130);
            };

            this._quranWirdAutoEntryTimer = window.setTimeout(attempt, 180);
        },
        handleInsightsAthkarRowClick(mode) {
            const normalizedMode = mode === 'masaa' ? 'masaa' : 'sabah';
            const entry = this.dailyProgress?.[normalizedMode];
            const isModeComplete = Boolean(entry?.isComplete);
            const targetReaderView =
                normalizedMode === 'masaa' ? 'athkar-app-masaa' : 'athkar-app-sabah';

            this.runAfterInsightsCollapse(() => {
                if (isModeComplete) {
                    this.runViewNavigation('athkar-app-gate');
                    return;
                }

                this.runViewNavigation(targetReaderView);
            });
        },
        handleInsightsRowClick(mode) {
            if (mode === 'wird') {
                this.handleInsightsWirdRowClick();

                return;
            }

            this.handleInsightsAthkarRowClick(mode);
        },
        handleInsightsWirdRowClick() {
            const isWirdComplete = Boolean(this.dailyProgress?.wird?.isComplete);
            const canAutoEnterWirdMode = !isWirdComplete && this.isQuranSupportUnlockedForWird();

            this.runAfterInsightsCollapse(() => {
                this.clearQuranWirdAutoEntry();
                this.shouldAutoEnterQuranWirdMode = canAutoEnterWirdMode;

                if (canAutoEnterWirdMode) {
                    this._quranWirdAutoEntryDeadlineAt = Date.now() + 22000;
                }

                this.runWhenQuranGateOpens(() => {
                    this.queueInsightsGateLaunch(() => {
                        window.dispatchEvent(
                            new CustomEvent('quran-gate-open', {
                                detail: { mode: 'tilawa' },
                            }),
                        );

                        if (canAutoEnterWirdMode) {
                            this.scheduleQuranWirdAutoEntry();
                        }
                    });
                });

                this.runQuranEntryNavigation();
            });
        },
        startInsightsRefreshLoop() {
            this.stopInsightsRefreshLoop();
            this.insightsRefreshTimer = window.setInterval(() => {
                this.refreshDailyProgress();
            }, 1400);
        },
        stopInsightsRefreshLoop() {
            if (this.insightsRefreshTimer !== null) {
                clearInterval(this.insightsRefreshTimer);
                this.insightsRefreshTimer = null;
            }
        },
        showInsightsPanel({ refresh = true } = {}) {
            this.clearInsightsHideTimer();
            this.isInsightsFastClosing = false;

            if (refresh) {
                this.refreshDailyProgress();
            }

            this.measureInsightsPanelHeight();
            this.isInsightsExpanded = true;
        },
        scheduleInsightsCollapse() {
            this.clearInsightsHideTimer();

            if (!this.isInsightsExpanded) {
                return;
            }

            this.insightsHideTimer = window.setTimeout(() => {
                if (this.isInsightsPointerInside || this.isInsightsZoneHoveredNow()) {
                    this.isInsightsPointerInside = true;
                    this.clearInsightsHideTimer();
                    return;
                }

                this.isInsightsFastClosing = false;
                this.isInsightsExpanded = false;
            }, this.insightsHideDelayMs);
        },
        resetInsightsPanelState({ preservePendingGateLaunch = false } = {}) {
            this.clearInsightsHideTimer();
            if (!preservePendingGateLaunch) {
                this.clearInsightsGateLaunchTimer();
            }
            this.stopInsightsRefreshLoop();
            this.isInsightsFastClosing = false;
            this.isInsightsPointerInside = false;
            this.isInsightsExpanded = false;
        },
        collapseInsightsForNavigation() {
            this.clearInsightsHideTimer();
            this.isInsightsPointerInside = false;

            if (!this.isInsightsExpanded) {
                this.isInsightsFastClosing = false;
                return 0;
            }

            const durationMs = Math.max(
                0,
                Math.trunc(Number(this.insightsFastCloseDurationMs) || 450),
            );
            const resetToken = Symbol('insights-fast-close-token');

            this._insightsFastCloseToken = resetToken;
            this.isInsightsFastClosing = true;
            this.isInsightsExpanded = false;

            window.setTimeout(() => {
                if (this._insightsFastCloseToken !== resetToken) {
                    return;
                }

                this.isInsightsFastClosing = false;
            }, durationMs + 40);

            return durationMs;
        },
        shouldDelayNavigationCallback(callback) {
            if (typeof callback !== 'string') {
                return false;
            }

            const normalized = callback.replace(/\s+/g, '');

            return (
                normalized.includes('athkar-app-gate') ||
                normalized.includes('quran-app-gate') ||
                normalized.includes('openQuranEntry()')
            );
        },
        executeItemCallback(callback, element) {
            if (typeof callback === 'function') {
                callback();
                return;
            }

            if (typeof callback !== 'string') {
                return;
            }

            try {
                if (element && window.Alpine?.evaluate) {
                    const result = window.Alpine.evaluate(element, callback);

                    if (typeof result === 'function') {
                        result();
                    }
                    return;
                }
            } catch {
                // Fall through to direct execution.
            }

            try {
                const maybeFunction = new Function(`return (${callback})`)();

                if (typeof maybeFunction === 'function') {
                    maybeFunction();
                    return;
                }
            } catch {
                // Fall through to direct execution.
            }

            try {
                new Function(callback)();
            } catch {
                // Silently ignore malformed callbacks.
            }
        },
        handleInsightsHoverEnter() {
            this.isInsightsPointerInside = true;
            this.showInsightsPanel();
        },
        handleInsightsHoverLeave() {
            this.isInsightsPointerInside = false;
            this.scheduleInsightsCollapse();
        },
        handleInsightsFocusIn() {
            this.isInsightsPointerInside = true;
            this.showInsightsPanel();
        },
        handleInsightsFocusOut(event) {
            const zone = this.getInsightsZoneElement();
            const nextTarget = event?.relatedTarget;

            if (
                zone instanceof Element &&
                nextTarget instanceof Node &&
                zone.contains(nextTarget)
            ) {
                return;
            }

            this.isInsightsPointerInside = false;
            this.scheduleInsightsCollapse();
        },
        handleInsightsTouchStart() {
            if (!this.isTouchDevice) {
                return;
            }

            this.clearActiveItemForInsightsTrigger();
            this.isInsightsPointerInside = true;
            this.showInsightsPanel();
        },
        shouldClearActiveItemOnInsightsTrigger() {
            const breakpointStore = window.Alpine?.store?.('bp');

            if (typeof breakpointStore?.is === 'function') {
                return breakpointStore.is('lg-');
            }

            const currentBreakpoint = String(breakpointStore?.current ?? '').trim();

            if (currentBreakpoint === '') {
                return true;
            }

            return ['base', 'sm', 'md', 'lg'].includes(currentBreakpoint);
        },
        clearActiveItemForInsightsTrigger() {
            if (!this.shouldClearActiveItemOnInsightsTrigger()) {
                return;
            }

            this.handleOutside(false, { preserveInsights: true });
        },
        toggleInsightsPanel() {
            this.clearActiveItemForInsightsTrigger();

            if (this.isInsightsExpanded) {
                this.collapseInsightsForNavigation();
                return;
            }

            this.isInsightsPointerInside = true;
            this.showInsightsPanel();
        },
        handleInsightsWindowPointerDown(event) {
            if (!this.isTouchDevice || !this.isInsightsExpanded) {
                return;
            }

            const pointerType = String(event?.pointerType ?? '').toLowerCase();

            if (pointerType && pointerType !== 'touch' && pointerType !== 'pen') {
                return;
            }

            const pointerX = Number(event?.clientX);
            const pointerY = Number(event?.clientY);
            const hasCoordinates = Number.isFinite(pointerX) && Number.isFinite(pointerY);

            if (hasCoordinates && this.isPointInsideInsightsZone(pointerX, pointerY)) {
                this.isInsightsPointerInside = true;
                this.showInsightsPanel({ refresh: false });
                return;
            }

            this.isInsightsPointerInside = false;
            this.scheduleInsightsCollapse();
        },
        getItemDetailsFromElement(element) {
            if (!element) {
                return null;
            }

            const onClickCallback = element.dataset?.onClickCallback;

            return {
                element,
                caption: element.dataset?.caption ?? '',
                iconName: element.dataset?.iconName ?? '',
                onClickCallback: onClickCallback && onClickCallback.trim() ? onClickCallback : null,
                locked: element.dataset?.locked === 'true',
            };
        },
        broadcastTouchState() {
            window.dispatchEvent(
                new CustomEvent('main-menu-touch-state', {
                    detail: {
                        element: this.touchActiveElement,
                        isTouching: this.isTouching,
                    },
                }),
            );
        },
        setTouchActiveElement(element, force = false) {
            if (this.touchActiveElement === element && !force) {
                return;
            }

            this.touchActiveElement = element;
            this.broadcastTouchState();
        },
        broadcastLockState(element, active) {
            if (!element) {
                return;
            }

            window.dispatchEvent(
                new CustomEvent('main-menu-lock-state', {
                    detail: { element, active },
                }),
            );
        },
        broadcastActiveState(element) {
            window.dispatchEvent(
                new CustomEvent('main-menu-active-state', {
                    detail: { element },
                }),
            );
        },
        handleTouchStart(event) {
            if (!event.touches?.length) {
                return;
            }

            if (event.cancelable) {
                event.preventDefault();
            }

            const touch = event.touches[0];
            const item = this.getItemFromPoint(touch.clientX, touch.clientY);

            if (item) {
                this.collapseInsightsForNavigation();
            }

            this.isTouching = true;
            this.lastTouchAt = Date.now();
            this.touchStartX = touch.clientX;
            this.touchStartY = touch.clientY;
            this.touchLastX = touch.clientX;
            this.touchLastY = touch.clientY;
            this.touchStartItem = item;
            this.touchStartWasActive = Boolean(
                item && item === this.activeItemElement && this.isItemActive,
            );
            this.touchLeftStartItem = false;
            this.touchMoved = false;

            this.broadcastTouchState();
            this.handleTouchPoint(touch.clientX, touch.clientY);
        },
        handleTouchMove(event) {
            if (!this.isTouching || !event.touches?.length) {
                return;
            }

            if (event.cancelable) {
                event.preventDefault();
            }

            const touch = event.touches[0];

            this.touchLastX = touch.clientX;
            this.touchLastY = touch.clientY;

            this.handleTouchPoint(touch.clientX, touch.clientY);
        },
        handleTouchEnd(event) {
            if (!this.isTouching) {
                return;
            }

            if (event?.cancelable) {
                event.preventDefault();
            }

            this.isTouching = false;
            this.lastTouchAt = Date.now();
            this.lastTouchMoved = this.touchMoved;

            this.broadcastTouchState();

            const x = this.touchLastX ?? this.touchStartX;
            const y = this.touchLastY ?? this.touchStartY;

            if (x === null || y === null) {
                return;
            }

            if (!this.isPointInsideGrid(x, y)) {
                this.setTouchActiveElement(null, true);
                this.handleOutside(true);
                return;
            }

            const item = this.getItemFromPoint(x, y);

            if (!item) {
                return;
            }

            const isActiveItem = item === this.activeItemElement && this.isItemActive;

            const isTapRepeatActivation =
                !this.touchMoved &&
                this.touchStartWasActive &&
                item === this.touchStartItem &&
                isActiveItem;

            const isSwipeReturnActivation =
                this.touchMoved &&
                this.touchLeftStartItem &&
                item === this.touchStartItem &&
                isActiveItem;

            if (isTapRepeatActivation || isSwipeReturnActivation) {
                const detail = this.getItemDetailsFromElement(item);
                this.attemptLockActivation(detail);
                return;
            }

            if (!isActiveItem) {
                const detail = this.getItemDetailsFromElement(item);
                this.setActiveItem(detail, 'touch', true);
            }
        },
        handleTouchPoint(x, y) {
            const item = this.getItemFromPoint(x, y);

            if (!item) {
                return;
            }

            this.containerHovered = true;

            if (
                this.touchStartX !== null &&
                this.touchStartY !== null &&
                !this.touchMoved &&
                Math.hypot(x - this.touchStartX, y - this.touchStartY) > 6
            ) {
                this.touchMoved = true;
            }

            if (this.touchStartItem && item !== this.touchStartItem) {
                this.touchLeftStartItem = true;
            }

            const detail = this.getItemDetailsFromElement(item);

            this.setActiveItem(detail, 'touch', true);
        },
        handleItemEnter(detail) {
            if (this.isTouchDevice) {
                return;
            }

            if (detail?.source === 'click') {
                return;
            }
            this.setActiveItem(detail, detail?.source ?? 'hover');
        },
        handleItemClick(detail) {
            if (!detail?.element || !detail.caption) {
                return;
            }

            if (this.isTouchDevice) {
                return;
            }

            const isWithinTouchWindow = this.lastTouchAt && Date.now() - this.lastTouchAt < 500;

            // ✅ block ghost clicks after touch interactions
            if (isWithinTouchWindow && !this.lastTouchMoved) {
                return;
            }

            const isActive = this.activeItemElement === detail.element && this.isItemActive;

            // ✅ FIRST CLICK on new item
            if (!isActive) {
                this.setActiveItem(detail, 'click');
                this.lastTouchMoved = false;
                return;
            }

            // ✅ SECOND CLICK on active item
            this.attemptLockActivation(detail);
            this.lastTouchMoved = false;
        },
        attemptLockActivation(detail) {
            if (!detail?.element || !detail.caption) {
                return;
            }

            if (this.activeItemElement !== detail.element || !this.isItemActive) {
                return;
            }

            if (!detail.locked && detail.onClickCallback) {
                this.runItemCallback(detail.onClickCallback, detail.element);
                return;
            }

            if (!detail.locked) {
                return;
            }

            if (this.lockedItemElement === detail.element) {
                this.wiggleLockIcon(detail.element);
                this.replayCaption();
                return;
            }

            this.activateLockedItem(detail);
        },
        runItemCallback(callback, element) {
            if (!callback) {
                return;
            }

            if (this.shouldDelayNavigationCallback(callback)) {
                const delayMs = this.collapseInsightsForNavigation();

                if (delayMs > 0) {
                    window.setTimeout(() => {
                        this.executeItemCallback(callback, element);
                    }, delayMs);
                    return;
                }
            }

            this.executeItemCallback(callback, element);
        },
        setActiveItem(detail, source, fromTouch = false) {
            if (!detail?.element || !detail.caption) {
                return;
            }

            const { element, caption, locked } = detail;
            const previousActive = this.activeItemElement;
            const isNewItem = !previousActive || previousActive !== element;

            // ✅ only reset lock if switching away from the locked element
            if (isNewItem && this.lockedItemElement && this.lockedItemElement !== element) {
                this.resetLockedItem();
            }

            this.activeItemElement = element;
            this.activeItemCaption = caption;
            this.activeItemLocked = Boolean(locked);
            this.isItemActive = true;
            this.broadcastActiveState(element);

            if (fromTouch) {
                this.setTouchActiveElement(element);
            }

            this.syncUI();
        },
        handleItemLeave(fromTouch = false) {
            if (this.isTouchDevice && !fromTouch) {
                return;
            }

            if (this.isTouching && !fromTouch) {
                return;
            }

            this.isItemActive = false;
            this.broadcastActiveState(null);
            this.resetLockedItem();
            clearTimeout(this.idleTimeout);
            this.idleTimeout = setTimeout(() => {
                if (!this.isItemActive && this.containerHovered) {
                    this.idleCaption();
                }
            }, 80);
        },
        handleOutside(clearHover = false, { preserveInsights = false } = {}) {
            if (clearHover) {
                this.containerHovered = false;
            }

            this.isItemActive = false;
            this.activeItemElement = null;
            this.activeItemCaption = null;
            this.activeItemLocked = false;

            this.broadcastActiveState(null);
            this.resetLockedItem();
            this.setTouchActiveElement(null, true);
            clearTimeout(this.idleTimeout);

            if (preserveInsights) {
                this.isInsightsPointerInside = true;
                this.clearInsightsHideTimer();
            } else {
                this.isInsightsPointerInside = false;
                this.scheduleInsightsCollapse();
            }

            this.syncUI();
        },
        activateLockedItem(detail) {
            if (!detail?.element || !detail.caption || !detail.locked) {
                return;
            }

            // Turn off previous lock (if any)
            if (this.lockedItemElement && this.lockedItemElement !== detail.element) {
                this.broadcastLockState(this.lockedItemElement, false);
            }

            // Ensure this is the active element
            this.activeItemElement = detail.element;
            this.activeItemCaption = detail.caption;
            this.activeItemLocked = true;
            this.isItemActive = true;
            this.broadcastActiveState(detail.element);

            // Lock state
            this.lockedItemElement = detail.element;
            this.syncUI();
        },
        resetLockedItem() {
            if (!this.lockedItemElement) {
                return;
            }

            const prev = this.lockedItemElement;
            this.lockedItemElement = null;

            this.broadcastLockState(prev, false);

            // restore caption to current active item (or hide)
            if (this.isItemActive && this.activeItemCaption) {
                this.showCaption(this.activeItemCaption);
            } else {
                this.hideCaption();
            }
        },
        wiggleLockIcon(element) {
            const lockIcon = element?.querySelector('[data-lock-icon]');

            if (!lockIcon || !window.animate) {
                return;
            }

            const previous = this.lockWiggles.get(lockIcon);

            if (previous) {
                previous.cancel();
            }

            const animation = window.animate(lockIcon, {
                rotate: ['0deg', '10deg', '-8deg', '6deg', '-4deg', '0deg'],
                duration: 520,
                ease: 'out(4)',
            });

            this.lockWiggles.set(lockIcon, animation);
        },
        showCaption(caption) {
            const token = ++this.animationToken;
            const elements = this.getCaptionElements();

            if (!elements) {
                if (this.pendingCaption === caption) {
                    return;
                }

                this.pendingCaption = caption;
                this.$nextTick(() => {
                    if (this.pendingCaption === caption && this.getCaptionElements()) {
                        this.pendingCaption = null;
                        this.showCaption(caption);
                    }
                });
                return;
            }

            const { captionWrap, captionText } = elements;

            if (this.currentCaption === caption && !this.isHidden) {
                this.isIdle = false;
                this.animateActive();
                return;
            }

            const wasHidden = this.isHidden;
            this.isHidden = false;
            this.isIdle = false;
            this.cancelAnimations();

            const swapCaption = () => {
                if (token !== this.animationToken) {
                    return;
                }

                this.currentCaption = caption;
                captionText.textContent = caption;
                this.animateIn();
            };

            if (this.currentCaption && !wasHidden) {
                window.animate(captionWrap, {
                    opacity: { to: 0 },
                    y: { to: -6 },
                    duration: 180,
                    ease: 'out(3)',
                    onComplete: swapCaption,
                });
            } else {
                swapCaption();
            }
        },
        animateIn() {
            const elements = this.getCaptionElements();

            if (!elements) {
                return;
            }

            const { captionWrap, captionText } = elements;

            this.triggerBurst(captionWrap);
            this.splitInstance?.revert();
            const split = window.animateSplitText(captionText, {
                words: true,
            });
            this.splitInstance = split;

            window.animate(captionWrap, {
                opacity: { from: 0, to: 1 },
                y: { from: 10, to: 0 },
                scale: { from: 0.98, to: 1 },
                duration: 320,
                ease: 'out(3)',
            });

            this.captionAnimation = window.animate(split.words, {
                opacity: { from: 0, to: 1 },
                y: { from: '70%', to: '0%' },
                duration: 420,
                delay: (_, index) => index * 60,
                ease: 'out(4)',
            });
        },
        replayCaption() {
            const elements = this.getCaptionElements();

            if (!elements) {
                return;
            }

            const { captionWrap, captionText } = elements;

            this.triggerBurst(captionWrap);
            this.splitInstance?.revert();
            const split = window.animateSplitText(captionText, {
                words: true,
            });
            this.splitInstance = split;

            window.animate(captionWrap, {
                scale: { from: 0.98, to: 1 },
                duration: 200,
                ease: 'out(3)',
            });

            this.captionAnimation = window.animate(split.words, {
                opacity: { from: 0, to: 1 },
                y: { from: '60%', to: '0%' },
                duration: 420,
                delay: (_, index) => index * 60,
                ease: 'out(4)',
            });
        },
        triggerBurst(captionWrap) {
            if (!captionWrap) {
                return;
            }

            captionWrap.classList.remove('main-menu-caption--burst');
            void captionWrap.offsetHeight;
            captionWrap.classList.add('main-menu-caption--burst');
        },
        animateActive() {
            const elements = this.getCaptionElements();

            if (!elements) {
                return;
            }

            this.triggerBurst(elements.captionWrap);
            window.animate(elements.captionWrap, {
                opacity: { to: 1 },
                scale: { to: 1 },
                y: { to: 0 },
                duration: 200,
                ease: 'out(3)',
            });
        },
        idleCaption() {
            this.syncUI();
            return;

            // this.isIdle = true;
            // this.cancelAnimations();

            // window.animate(this.$refs.captionWrap, {
            //     opacity: { to: 0.35 },
            //     scale: { to: 0.98 },
            //     duration: 240,
            //     ease: 'out(3)',
            // });
        },
        hideCaption() {
            if (this.isHidden) {
                return;
            }

            const elements = this.getCaptionElements();

            if (!elements) {
                return;
            }

            this.isHidden = true;
            this.isIdle = true;
            this.cancelAnimations();
            elements.captionWrap.classList.remove('main-menu-caption--burst');

            window.animate(elements.captionWrap, {
                opacity: { to: 0 },
                y: { to: -6 },
                duration: 200,
                ease: 'out(3)',
            });
        },
        cancelAnimations() {
            this.captionAnimation?.cancel();
            this.splitInstance?.revert();
            this.splitInstance = null;
        },
        syncUI() {
            const hasActive = this.isItemActive && this.activeItemElement;

            if (this.lockedItemElement && this.lockedItemElement !== this.activeItemElement) {
                this.resetLockedItem();
            }

            if (this.activeItemElement) {
                const caption = this.activeItemElement.dataset?.caption ?? '';
                this.activeItemCaption = caption;
            }

            // no active and no lock => hide
            if (!hasActive && !this.lockedItemElement) {
                this.hideCaption();
                return;
            }

            // keep lock icon in sync
            if (this.lockedItemElement) {
                this.broadcastLockState(this.lockedItemElement, true);
            }

            // ✅ CAPTION RULE:
            // locked caption ONLY when locked item is the active item
            const caption =
                this.lockedItemElement &&
                this.activeItemElement &&
                this.lockedItemElement === this.activeItemElement
                    ? this.lockedCaption
                    : (this.activeItemCaption ?? '');

            if (!caption) {
                this.hideCaption();
                return;
            }

            this.showCaption(caption);
        },
    }));
});
