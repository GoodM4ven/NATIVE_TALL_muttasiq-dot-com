export const createModeFlowModule = (deps) => {
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
        athkarReaderNoticeBypassKey,
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

                const isInQuranView =
                    Boolean(this.views?.['quran-app-gate']?.isOpen) ||
                    Boolean(this.views?.['quran-app-tilawa']?.isOpen) ||
                    Boolean(this.views?.['quran-app-hifth']?.isOpen) ||
                    Boolean(this.views?.['quran-app-tadabbur']?.isOpen);

                if (!this.views?.['main-menu']?.isOpen && !isInQuranView) {
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
                this.confirmNotice({ markBypassed: false });
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
                this.confirmNotice({ markBypassed: false });
                return;
            }

            this.showNotice();
        },

        showNotice() {
            if (!this.activeMode) {
                return;
            }

            if (this.shouldSkipGuidancePanels()) {
                this.confirmNotice({ markBypassed: false });
                return;
            }

            if (this.shouldBypassNoticeOnce(athkarReaderNoticeBypassKey)) {
                this.confirmNotice({
                    noticeKey: athkarReaderNoticeBypassKey,
                    markBypassed: false,
                });
                return;
            }

            this.markNoticeDisplayed(athkarReaderNoticeBypassKey);
            this.isNoticeVisible = true;

            if (this.views?.['athkar-app-gate']) {
                this.views['athkar-app-gate'].isReaderVisible = false;
            }

            this.$nextTick(() => this.queueTextFit());
            this.syncNativeVolumeNavigation();
        },

        confirmNotice(options = {}) {
            if (!this.activeMode) {
                return;
            }

            const normalizedNoticeKey = String(
                options?.noticeKey ?? athkarReaderNoticeBypassKey,
            ).trim();
            const shouldMarkBypassed = options?.markBypassed === true;

            if (shouldMarkBypassed) {
                this.markNoticeBypassedOnce(normalizedNoticeKey);
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

        confirmNoticeAndBypassFutureDisplay() {
            this.confirmNotice({
                noticeKey: athkarReaderNoticeBypassKey,
                markBypassed: true,
            });
        },

        resolveNoticeBypassFlags() {
            if (!this.noticeBypassFlags || typeof this.noticeBypassFlags !== 'object') {
                this.noticeBypassFlags = {};
            }

            return this.noticeBypassFlags;
        },

        noticeBypassState(noticeKey) {
            const normalizedNoticeKey = String(noticeKey ?? '').trim();

            if (normalizedNoticeKey === '') {
                return { hasDisplayed: false, hasBypassedOnce: false };
            }

            const flags = this.resolveNoticeBypassFlags();
            const storedState = flags[normalizedNoticeKey];

            if (!storedState || typeof storedState !== 'object') {
                return { hasDisplayed: false, hasBypassedOnce: false };
            }

            return {
                hasDisplayed: Boolean(storedState.hasDisplayed),
                hasBypassedOnce: Boolean(storedState.hasBypassedOnce),
            };
        },

        persistNoticeBypassState(noticeKey, state = {}) {
            const normalizedNoticeKey = String(noticeKey ?? '').trim();

            if (normalizedNoticeKey === '') {
                return;
            }

            const flags = this.resolveNoticeBypassFlags();

            this.noticeBypassFlags = {
                ...flags,
                [normalizedNoticeKey]: {
                    hasDisplayed: Boolean(state?.hasDisplayed),
                    hasBypassedOnce: Boolean(state?.hasBypassedOnce),
                },
            };
        },

        markNoticeDisplayed(noticeKey) {
            const state = this.noticeBypassState(noticeKey);

            if (state.hasDisplayed) {
                return;
            }

            this.persistNoticeBypassState(noticeKey, {
                hasDisplayed: true,
                hasBypassedOnce: state.hasBypassedOnce,
            });
        },

        markNoticeBypassedOnce(noticeKey) {
            const state = this.noticeBypassState(noticeKey);

            if (state.hasBypassedOnce) {
                return;
            }

            this.persistNoticeBypassState(noticeKey, {
                hasDisplayed: true,
                hasBypassedOnce: true,
            });
        },

        shouldBypassNoticeOnce(noticeKey) {
            const state = this.noticeBypassState(noticeKey);

            return state.hasDisplayed && state.hasBypassedOnce;
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
    };
};
