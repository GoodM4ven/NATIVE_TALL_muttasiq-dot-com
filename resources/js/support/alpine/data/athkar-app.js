document.addEventListener('alpine:init', () => {
    window.Alpine.data('athkarAppGate', () => ({
        hoverSide: null,
        activeSide: null,
        isHovering: false,
        isPinging: false,
        splitValue: 50,
        splitAnimation: null,
        spillOpacity: 0,
        spillTransitionMs: 180,
        spillShowDelayMs: 650,
        spillShowDurationMs: 500,
        spillHideDurationMs: 120,
        spillIntroDelayMs: 900,
        spillTargetOpacity: 0.6,
        spillTimer: null,
        spillHideTimer: null,
        spillReadyTimer: null,
        lastSpillState: null,
        isEnhanced: false,
        isSpillReady: false,
        pingDuration: 1400,
        pingDelay: 650,
        setScrollLock(locked) {
            document.documentElement.style.overflow = locked ? 'hidden' : '';
            document.body.style.overflow = locked ? 'hidden' : '';
        },
        syncPerfProfile() {
            this.$store.bp.current;
            const nextEnhanced = this.$store.bp.is('sm+');

            if (nextEnhanced && !this.isEnhanced) {
                this.deactivateSide();
            }

            this.isEnhanced = nextEnhanced;
            this.spillTargetOpacity = this.isEnhanced ? 0.55 : 0.45;
        },
        animateSplit(value) {
            if (this.splitAnimation?.pause) {
                this.splitAnimation.pause();
            }
            this.splitValue = value;
        },
        setHover(side) {
            if (this.activeSide) {
                return;
            }
            this.hoverSide = side;
            if (side === 'morning') {
                this.animateSplit(40);
            } else if (side === 'night') {
                this.animateSplit(60);
            } else {
                this.animateSplit(50);
            }
        },
        startHover() {
            if (this.isHovering) {
                return;
            }
            this.isHovering = true;
            this.queuePing();
        },
        endHover() {
            this.isHovering = false;
        },
        resetHover() {
            if (this.activeSide) {
                return;
            }
            this.setHover(null);
        },
        activateSide(side) {
            if (!side) {
                return;
            }

            this.activeSide = side;
            this.hoverSide = side;

            if (side === 'morning') {
                this.animateSplit(40);
                return;
            }

            if (side === 'night') {
                this.animateSplit(60);
                return;
            }

            this.animateSplit(50);
        },
        deactivateSide() {
            if (!this.activeSide) {
                return;
            }

            this.activeSide = null;
            this.hoverSide = null;
            this.animateSplit(50);
        },
        handleOutsideActivation() {
            if (this.isEnhanced) {
                return;
            }

            this.deactivateSide();
        },
        sideForMode(mode) {
            if (mode === 'sabah') {
                return 'morning';
            }

            if (mode === 'masaa') {
                return 'night';
            }

            return mode;
        },
        syncSpillState(isActive) {
            if (this.lastSpillState === isActive) {
                return;
            }
            this.lastSpillState = isActive;
            if (this.spillTimer) {
                clearTimeout(this.spillTimer);
            }
            if (this.spillHideTimer) {
                clearTimeout(this.spillHideTimer);
            }
            if (this.spillReadyTimer) {
                clearTimeout(this.spillReadyTimer);
            }
            if (isActive) {
                this.setScrollLock(true);
                this.spillTransitionMs = this.spillShowDurationMs;
                if (!this.isSpillReady) {
                    this.spillReadyTimer = setTimeout(() => {
                        if (!this.lastSpillState) {
                            return;
                        }
                        this.isSpillReady = true;
                        this.spillOpacity = 0;
                        const scheduleShow = () => {
                            this.spillTimer = setTimeout(() => {
                                this.spillOpacity = this.spillTargetOpacity;
                            }, this.spillShowDelayMs);
                        };
                        if (window.requestAnimationFrame) {
                            window.requestAnimationFrame(() =>
                                window.requestAnimationFrame(scheduleShow),
                            );
                            return;
                        }
                        scheduleShow();
                    }, this.spillIntroDelayMs);
                    return;
                }
                this.spillTimer = setTimeout(() => {
                    this.spillOpacity = this.spillTargetOpacity;
                }, this.spillShowDelayMs);
                return;
            }
            this.spillTransitionMs = this.spillHideDurationMs;
            this.spillOpacity = 0;
            this.spillHideTimer = setTimeout(() => {
                this.setScrollLock(false);
            }, this.spillHideDurationMs);
        },
        queuePing() {
            if (this.isPinging || !this.isHovering) {
                return;
            }
            this.isPinging = true;
            setTimeout(() => {
                this.isPinging = false;
                if (this.isHovering) {
                    setTimeout(() => this.queuePing(), this.pingDelay);
                }
            }, this.pingDuration);
        },
        requestOpenMode(mode) {
            if (this.isEnhanced) {
                this.$dispatch('athkar-gate-open', { mode });
                return;
            }

            const side = this.sideForMode(mode);

            if (this.activeSide === side) {
                this.deactivateSide();
                this.$dispatch('athkar-gate-open', { mode });
                return;
            }

            this.activateSide(side);
        },
    }));

    window.Alpine.data('athkarAppReader', (config) => ({
        athkar: config.athkar,
        settings: config.athkarSettings,
        activeMode: window.Alpine.$persist(null).as('athkar-active-mode'),
        isCompletionVisible: false,
        isNoticeVisible: window.Alpine.$persist(false).as('athkar-notice-visible'),
        isRestoring: true,
        completionHack: {
            isVisible: false,
            isPinned: false,
            isArmed: false,
            canHover: window.matchMedia
                ? window.matchMedia('(hover: hover) and (pointer: fine)').matches
                : false,
        },
        completionTimer: null,
        swipe: {
            startX: 0,
            startY: 0,
            active: false,
            ignoreClick: false,
            startedOnTap: false,
            pointerId: null,
            pointerType: null,
            source: null,
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
            prev: '',
            next: '',
        },
        pagePulse: {
            isActive: false,
            direction: null,
            timer: null,
            prev: '',
            next: '',
        },
        totalPulse: {
            isActive: false,
            timer: null,
            prev: '',
            next: '',
        },
        tapPulse: {
            index: null,
            isActive: false,
            timer: null,
        },
        textFit: {
            raf: null,
            resizeObserver: null,
            minSize: 16,
            maxScale: 1.2,
            step: 0.5,
        },
        textShimmer: {
            target: null,
            timer: null,
            runTimer: null,
            duration: 1000,
            delay: 1000,
            pause: 4000,
        },
        hintIndex: null,
        isMobileCounterOpen: false,
        readerLeaveMs: 300,
        slideDurationMs: 900,
        transitionMode: null,
        transitionDistance: '1.5rem',
        isGateMenuTransition: true,
        pulseDurationMs: 520,
        lastSeenDay: window.Alpine.$persist(null).as('athkar-last-day'),
        progress: window.Alpine.$persist({
            sabah: { index: 0, counts: [] },
            masaa: { index: 0, counts: [] },
        }).as('athkar-progress-v1'),
        completedOn: window.Alpine.$persist({
            sabah: null,
            masaa: null,
        }).as('athkar-completed-v1'),
        init() {
            this.ensureState();
            this.syncDay();
            this.ensureProgress('sabah');
            this.ensureProgress('masaa');
            window.addEventListener('focus', () => this.syncDay());
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

            this.setupTextFit();
            this.$watch('activeMode', () => this.queueTextFit());
            this.$watch('activeIndex', () => {
                this.closeHint();
                this.queueTextFit();
            });
            this.$watch(
                () => this.views?.['athkar-app-gate']?.isReaderVisible,
                (isVisible) => {
                    if (isVisible) {
                        this.queueReaderTextFit();
                    }
                },
            );
            this.$watch('isNoticeVisible', (isNoticeVisible) => {
                if (!isNoticeVisible && this.views?.['athkar-app-gate']?.isReaderVisible) {
                    this.queueReaderTextFit();
                }
            });
        },
        applySettings(nextSettings) {
            if (!nextSettings || typeof nextSettings !== 'object') {
                return;
            }

            this.settings = {
                ...this.settings,
                ...nextSettings,
            };

            this.ensureProgress('sabah');
            this.ensureProgress('masaa');

            if (
                this.activeMode &&
                this.shouldPreventSwitching() &&
                this.activeIndex > this.maxNavigableIndex
            ) {
                this.progress[this.activeMode].index = this.maxNavigableIndex;
            }

            if (this.shouldSkipNoticePanels()) {
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
                    this.$hashAction('athkar-app-gate');
                }
            }
        },
        toggleHint(index) {
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
            const nextTotal = this.totalCompletedCount;
            this.triggerTotalPulse(previousTotal, nextTotal);

            this.finishActiveMode();
        },
        ensureState() {
            if (!this.progress || typeof this.progress !== 'object') {
                this.progress = {
                    sabah: { index: 0, counts: [] },
                    masaa: { index: 0, counts: [] },
                };
            }

            ['sabah', 'masaa'].forEach((mode) => {
                if (!this.progress[mode] || typeof this.progress[mode] !== 'object') {
                    this.progress[mode] = { index: 0, counts: [] };

                    return;
                }

                if (!Array.isArray(this.progress[mode].counts)) {
                    this.progress[mode].counts = [];
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
            const list = this.athkarFor(mode);

            this.progress[mode] = {
                index: 0,
                counts: Array.from({ length: list.length }, () => 0),
            };
        },
        ensureProgress(mode) {
            const list = this.athkarFor(mode);

            if (!this.progress[mode]) {
                this.resetProgress(mode);

                return;
            }

            const counts = Array.isArray(this.progress[mode].counts)
                ? this.progress[mode].counts
                : [];

            if (counts.length !== list.length) {
                this.progress[mode].counts = Array.from({ length: list.length }, () => 0);
            } else {
                this.progress[mode].counts = counts.map((value, _index) => {
                    const count = Number(value ?? 0);

                    if (!Number.isFinite(count) || count < 0) {
                        return 0;
                    }

                    return count;
                });
            }

            const maxIndex = Math.max(list.length - 1, 0);
            const currentIndex = Number(this.progress[mode].index ?? 0);

            this.progress[mode].index = Math.min(Math.max(currentIndex, 0), maxIndex);
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
                this.$hashAction('athkar-app-' + mode, { remember: true });
            }

            this.nav.suppressUntil = performance.now() + 250;

            if (this.shouldPreventSwitching() && this.activeIndex > this.maxNavigableIndex) {
                this.progress[this.activeMode].index = this.maxNavigableIndex;
            }

            this.resetNavState();

            return true;
        },
        startModeNotice(mode, { updateHash = false, respectLock = true } = {}) {
            const didActivate = this.activateMode(mode, { updateHash, respectLock });

            if (!didActivate) {
                return;
            }

            if (this.shouldSkipNoticePanels()) {
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

            if (this.shouldSkipNoticePanels()) {
                this.confirmNotice();
                return;
            }

            this.isNoticeVisible = true;

            if (this.views?.['athkar-app-gate']) {
                this.views['athkar-app-gate'].isReaderVisible = false;
            }

            this.$nextTick(() => this.queueTextFit());
        },
        confirmNotice() {
            if (!this.activeMode) {
                return;
            }

            this.isNoticeVisible = false;

            if (this.views?.['athkar-app-gate']) {
                this.views['athkar-app-gate'].isReaderVisible = true;
            }

            this.ensureProgress(this.activeMode);
            this.resumeModeIndex();
            this.$nextTick(() => this.queueTextFit());
            this.queueReaderTextFit();
        },
        returnToGateFromNotice() {
            this.isNoticeVisible = false;

            if (this.views?.['athkar-app-gate']) {
                this.views['athkar-app-gate'].isReaderVisible = false;
            }

            this.closeMode();
        },
        closeMode() {
            const previousHash = window.history.state?.__hashActionPrev;

            if (previousHash === '#athkar-app-gate') {
                window.history.back();
            } else {
                this.$hashAction('athkar-app-gate', { remember: false, force: false });
            }

            this.softCloseMode();
        },
        softCloseMode() {
            this.isNoticeVisible = false;
            this.hideCompletionHack({ force: true });
            this.resetNavState();
            this.stopTextShimmer();

            setTimeout(() => {
                if (!this.views[`athkar-app-gate`].isReaderVisible) {
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
            this.stopTextShimmer();
            this.hideCompletionHack({ force: true });
            this.resetNavState();

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
        get activeList() {
            return this.activeMode ? this.athkarFor(this.activeMode) : [];
        },
        get activeIndex() {
            return this.activeMode ? (this.progress[this.activeMode]?.index ?? 0) : 0;
        },
        get totalRequiredCount() {
            if (!this.activeList.length) {
                return 0;
            }

            return this.activeList.reduce(
                (total, _, index) => total + this.requiredCount(index),
                0,
            );
        },
        get totalCompletedCount() {
            if (!this.activeList.length) {
                return 0;
            }

            return this.activeList.reduce((total, _, index) => {
                return total + this.countAt(index);
            }, 0);
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
        get totalRequiredLetters() {
            if (!this.activeList.length) {
                return 0;
            }

            return this.activeList.reduce((total, item, index) => {
                const letters = this.textLetterCount(item?.text);

                return total + letters * this.requiredCount(index);
            }, 0);
        },
        get totalCompletedLetters() {
            if (!this.activeList.length) {
                return 0;
            }

            return this.activeList.reduce((total, item, index) => {
                const letters = this.textLetterCount(item?.text);
                const required = this.requiredCount(index);
                const completed = Math.min(this.countAt(index), required);

                return total + letters * completed;
            }, 0);
        },
        get totalRemainingLetters() {
            if (!this.activeList.length) {
                return 0;
            }

            return Math.max(this.totalRequiredLetters - this.totalCompletedLetters, 0);
        },
        get slideProgressPercent() {
            const totalLetters = this.totalRequiredLetters;

            if (!this.activeList.length || !totalLetters) {
                return 0;
            }

            const completedLetters = Math.min(this.totalCompletedLetters, totalLetters);
            const percent = Math.round((completedLetters / totalLetters) * 100);

            return Math.min(100, Math.max(0, percent));
        },
        get maxNavigableIndex() {
            if (!this.activeList.length) {
                return 0;
            }

            if (this.shouldPreventSwitching()) {
                const firstIncomplete = this.activeList.findIndex((_, index) => {
                    return !this.isItemComplete(index);
                });

                if (firstIncomplete !== -1) {
                    return firstIncomplete;
                }
            }

            return this.activeList.length - 1;
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
        shouldSkipNoticePanels() {
            return this.settingValue('does_skip_notice_panels', false);
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
            return this.nav.isActive ? this.nav.dragIndex : this.nav.hoverIndex;
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
            if (!this.activeList.length) {
                return 'linear-gradient(90deg, var(--athkar-nav-pending) 0% 100%)';
            }

            const direction = this.navIsRtl() ? 270 : 90;
            const segment = this.segmentWidthPercent();
            const stops = this.activeList.map((_, index) => {
                const start = (index * segment).toFixed(4);
                const end = ((index + 1) * segment).toFixed(4);
                let color = 'var(--athkar-nav-pending)';

                if (this.isItemComplete(index)) {
                    color = 'var(--athkar-nav-complete)';
                } else if (index <= this.maxNavigableIndex) {
                    color = 'var(--athkar-nav-available)';
                }

                return `${color} ${start}% ${end}%`;
            });

            return `linear-gradient(${direction}deg, ${stops.join(', ')})`;
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

            const previousPage = currentIndex + 1;
            this.progress[this.activeMode].index = nextIndex;
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
                    if (this.shouldSkipNoticePanels()) {
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

            this.progress[this.activeMode].counts[index] = nextValue;
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

            if (current < required || allowOvercount) {
                const previousCount = current;
                const previousTotal = this.totalCompletedCount;
                const nextCount = current + 1;
                this.setCount(index, nextCount, { allowOvercount });
                const nextTotal = this.totalCompletedCount;
                this.triggerCountPulse(index, previousCount, nextCount);
                this.triggerTotalPulse(previousTotal, nextTotal);

                if (required > 1) {
                    this.triggerTapPulse(index);
                }
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
            const nextTotal = this.totalCompletedCount;
            this.triggerCountPulse(index, current, required);
            this.triggerTotalPulse(previousTotal, nextTotal);

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
                const nextTotal = this.totalCompletedCount;
                this.triggerCountPulse(index, current, nextCount);
                this.triggerTotalPulse(previousTotal, nextTotal);

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
                this.setActiveIndex(index + 1);

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

            if (this.shouldSkipNoticePanels()) {
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
                        this.$hashAction('athkar-app-gate');
                    }
                }, this.readerLeaveMs);

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
            }, 3000);

            setTimeout(() => {
                if (!this.views[`athkar-app-gate`].isReaderVisible) {
                    this.activeMode = null;
                    this.$hashAction('athkar-app-gate');
                }
            }, this.readerLeaveMs);
        },
        itemKey(item, index) {
            return `${this.activeMode ?? 'athkar'}-${index}`;
        },
        queueReaderTextFit() {
            if (!this.activeMode) {
                return;
            }

            const startedAt = performance.now();
            const timeoutMs = 1800;

            const attemptFit = () => {
                if (!this.views?.['athkar-app-gate']?.isReaderVisible || this.isNoticeVisible) {
                    return;
                }

                if (!window.fitty) {
                    if (performance.now() - startedAt < timeoutMs) {
                        requestAnimationFrame(attemptFit);
                    }

                    return;
                }

                this.$nextTick(() => {
                    const slide = this.$el.querySelector('[data-athkar-slide][data-active="true"]');
                    const box = slide?.querySelector('[data-athkar-text-box]');

                    if (box?.clientWidth && box?.clientHeight) {
                        this.queueTextFit();
                        return;
                    }

                    if (performance.now() - startedAt < timeoutMs) {
                        requestAnimationFrame(attemptFit);
                    }
                });
            };

            requestAnimationFrame(attemptFit);
        },
        setupTextFit() {
            if (!window.fitty) {
                return;
            }

            this.$nextTick(() => this.queueTextFit());

            if (document.fonts?.ready) {
                document.fonts.ready.then(() => this.queueTextFit());
            }

            if ('ResizeObserver' in window) {
                const panel = this.$el.querySelector('.athkar-panel');

                if (panel) {
                    this.textFit.resizeObserver = new ResizeObserver(() => {
                        this.queueTextFit();
                    });
                    this.textFit.resizeObserver.observe(panel);
                }
            }

            window.addEventListener('resize', () => this.queueTextFit(), { passive: true });
        },
        queueTextFit() {
            if (!window.fitty) {
                return;
            }

            if (this.textFit.raf) {
                cancelAnimationFrame(this.textFit.raf);
            }

            this.textFit.raf = requestAnimationFrame(() => {
                this.textFit.raf = null;

                this.$nextTick(() => {
                    this.fitActiveThikrText();
                    this.fitNoticeText();
                });
            });
        },
        fitNoticeText() {
            if (!this.isNoticeVisible) {
                return;
            }

            const text = this.$el.querySelector('[data-athkar-notice-text]');
            const box = this.$el.querySelector('[data-athkar-notice-box]');

            if (!text || !box) {
                return;
            }

            text.classList.remove('is-fit');
            this.fitTextInBox(text, box, 14, 1.8);
        },
        fitActiveThikrText() {
            if (!this.activeMode) {
                return;
            }

            const slide = this.$el.querySelector('[data-athkar-slide][data-active="true"]');

            if (!slide) {
                return;
            }

            const text = slide.querySelector('[data-athkar-text]');
            const box = slide.querySelector('[data-athkar-text-box]');

            if (!text || !box) {
                return;
            }

            text.classList.remove('is-fit');
            this.fitTextInBox(text, box);
            requestAnimationFrame(() => this.setupTextShimmer(text));
        },
        fitTextInBox(text, box, minSizeOverride = null, maxScaleOverride = null) {
            if (!window.fitty) {
                text.classList.add('is-fit');
                return;
            }

            if (!box.clientWidth || !box.clientHeight) {
                text.classList.add('is-fit');
                return;
            }

            text.style.fontSize = '';
            const baseSize = Number.parseFloat(getComputedStyle(text).fontSize);

            if (!Number.isFinite(baseSize)) {
                return;
            }

            const minSize = Number.isFinite(minSizeOverride)
                ? minSizeOverride
                : this.textFit.minSize;
            const maxSize = this.maxTextSizeForBox(text, box, baseSize, maxScaleOverride);
            text.style.fontSize = `${baseSize}px`;

            const instance = this.ensureFittyInstance(text, minSize, maxSize);

            if (instance?.fit) {
                instance.fit();
            }

            this.fitTextToBox(text, box, minSize, maxSize);

            requestAnimationFrame(() => {
                text.classList.add('is-fit');
            });
        },
        setupTextShimmer(text = null) {
            const target =
                text ??
                this.$el.querySelector(
                    '[data-athkar-slide][data-active="true"] [data-athkar-shimmer]',
                );

            if (!target) {
                this.stopTextShimmer();
                return;
            }

            this.attachTextShimmer(target);
        },
        attachTextShimmer(text) {
            if (this.textShimmer.target === text) {
                return;
            }

            this.stopTextShimmer();
            this.textShimmer.target = text;
            text.classList.add('athkar-shimmer');

            const parseTime = (value, fallback) => {
                if (value === null || value === undefined) {
                    return fallback;
                }

                const str = String(value).trim();
                const parsed = Number.parseFloat(str);

                if (!Number.isFinite(parsed)) {
                    return fallback;
                }

                if (str.endsWith('ms')) {
                    return parsed;
                }

                if (str.endsWith('s')) {
                    return parsed * 1000;
                }

                return parsed;
            };

            const duration = parseTime(text.dataset.shimmerDuration, 1000);
            const delay = parseTime(text.dataset.shimmerDelay, 1000);
            const pause = parseTime(text.dataset.shimmerPause, 4000);

            this.textShimmer.duration = Number.isFinite(duration) ? duration : 1000;
            this.textShimmer.delay = Number.isFinite(delay) ? delay : 1000;
            this.textShimmer.pause = Number.isFinite(pause) ? pause : 4000;

            text.style.setProperty('--shimmer-duration', `${this.textShimmer.duration}ms`);
            this.scheduleTextShimmer();
        },
        scheduleTextShimmer() {
            this.clearTextShimmerTimers();

            const { target, delay, duration, pause } = this.textShimmer;

            if (!target) {
                return;
            }

            const run = () => {
                if (!this.textShimmer.target) {
                    return;
                }

                target.classList.add('is-shimmering');
                this.textShimmer.runTimer = setTimeout(() => {
                    target.classList.remove('is-shimmering');
                    this.textShimmer.timer = setTimeout(run, pause);
                }, duration);
            };

            this.textShimmer.timer = setTimeout(run, delay);
        },
        clearTextShimmerTimers() {
            if (this.textShimmer.timer) {
                clearTimeout(this.textShimmer.timer);
                this.textShimmer.timer = null;
            }

            if (this.textShimmer.runTimer) {
                clearTimeout(this.textShimmer.runTimer);
                this.textShimmer.runTimer = null;
            }
        },
        stopTextShimmer() {
            this.clearTextShimmerTimers();

            if (this.textShimmer.target) {
                this.textShimmer.target.classList.remove('is-shimmering');
            }

            this.textShimmer.target = null;
        },
        maxTextSizeForBox(text, box, baseSize, maxScaleOverride = null) {
            const maxScale = Number.isFinite(maxScaleOverride)
                ? maxScaleOverride
                : this.textFit.maxScale;

            if (!Number.isFinite(maxScale) || maxScale <= 1) {
                return baseSize;
            }

            text.style.fontSize = `${baseSize}px`;
            const baseHeight = text.scrollHeight;
            const baseWidth = text.scrollWidth;

            if (!baseHeight) {
                return baseSize;
            }

            const heightScale = box.clientHeight / baseHeight;
            const widthScale = baseWidth ? box.clientWidth / baseWidth : heightScale;

            if (heightScale <= 1 && widthScale <= 1) {
                return baseSize;
            }

            const allowedScale = Math.min(maxScale, Math.max(heightScale, widthScale));

            return Math.max(baseSize, baseSize * allowedScale);
        },
        ensureFittyInstance(text, minSize, maxSize) {
            const storedMin = Number.parseFloat(text.dataset.fittyMinSize ?? '0');
            const storedMax = Number.parseFloat(text.dataset.fittyMaxSize ?? '0');

            if (text._fittyInstance && storedMin === minSize && storedMax === maxSize) {
                return text._fittyInstance;
            }

            if (text._fittyInstance?.unsubscribe) {
                text._fittyInstance.unsubscribe();
            }

            const instance = window.fitty(text, {
                minSize,
                maxSize,
                multiLine: true,
                observeMutations: false,
                observeWindow: false,
            });

            text._fittyInstance = instance;
            text.dataset.fittyMinSize = String(minSize);
            text.dataset.fittyMaxSize = String(maxSize);

            return instance;
        },
        fitTextToBox(text, box, minSize, maxSize) {
            let size = Number.parseFloat(getComputedStyle(text).fontSize);

            if (!Number.isFinite(size)) {
                return;
            }

            const fits = () => {
                return text.scrollHeight <= box.clientHeight && text.scrollWidth <= box.clientWidth;
            };

            if (!fits()) {
                size = Math.max(minSize, Math.min(maxSize, size));
            }

            let low = minSize;
            let high = maxSize;
            let best = Math.max(minSize, Math.min(size, maxSize));

            for (let i = 0; i < 14; i += 1) {
                const mid = (low + high) / 2;
                text.style.fontSize = `${mid}px`;

                if (fits()) {
                    best = mid;
                    low = mid;
                } else {
                    high = mid;
                }
            }

            const rounded = Math.max(minSize, Math.min(maxSize, best));
            const step = this.textFit.step;
            const snapped = step > 0 ? Math.round(rounded / step) * step : rounded;

            text.style.fontSize = `${snapped}px`;
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

            this.swipe.active = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;

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

            if (this.swipe.startedOnTap && isTouchLike && absX < 12 && absY < 12) {
                this.swipe.startedOnTap = false;
                this.swipe.ignoreClick = true;
                this.handleTap();

                return;
            }

            this.swipe.startedOnTap = false;

            if (absX < 40 || absX < absY) {
                return;
            }

            const previousIndex = this.activeIndex;
            let didHandleSwipe = false;

            if (deltaX < 0) {
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

            this.countPulse.index = index;
            this.countPulse.isActive = false;
            this.countPulse.prev = previousValue ?? '';
            this.countPulse.next = nextValue ?? '';

            requestAnimationFrame(() => {
                this.countPulse.isActive = true;
            });

            this.countPulse.timer = setTimeout(() => {
                this.countPulse.isActive = false;
            }, this.pulseDurationMs);
        },
        triggerPagePulse(direction, previousValue, nextValue) {
            if (this.pagePulse.timer) {
                clearTimeout(this.pagePulse.timer);
            }

            this.pagePulse.direction = direction;
            this.pagePulse.isActive = false;
            this.pagePulse.prev = previousValue ?? '';
            this.pagePulse.next = nextValue ?? '';

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

            this.totalPulse.isActive = false;
            this.totalPulse.prev = previousValue ?? '';
            this.totalPulse.next = nextValue ?? '';

            requestAnimationFrame(() => {
                this.totalPulse.isActive = true;
            });

            this.totalPulse.timer = setTimeout(() => {
                this.totalPulse.isActive = false;
            }, this.pulseDurationMs);
        },
        triggerTapPulse(index) {
            if (this.tapPulse.timer) {
                clearTimeout(this.tapPulse.timer);
            }

            this.tapPulse.index = index;
            this.tapPulse.isActive = false;

            requestAnimationFrame(() => {
                this.tapPulse.isActive = true;
            });

            this.tapPulse.timer = setTimeout(() => {
                this.tapPulse.isActive = false;
            }, this.pulseDurationMs);
        },
    }));
});
