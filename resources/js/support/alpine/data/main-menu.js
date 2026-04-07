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
        isInsightsExpanded: false,
        isInsightsPointerInside: false,
        insightsHideDelayMs: 2500,
        insightsHideTimer: null,
        insightsRefreshTimer: null,
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
        _onWindowMouseMove: null,
        _onWindowBlur: null,
        _onWindowPointerDown: null,
        _onWindowStorage: null,
        _onSwitchView: null,
        init() {
            this.captionShadow = window.makeBoxShadowFromColor?.('--primary-500') ?? 'none';
            this.captionShadowDark = window.makeBoxShadowFromColor?.('--primary-100') ?? 'none';
            this.isTouchDevice =
                'ontouchstart' in window ||
                window.matchMedia?.('(pointer: coarse)')?.matches ||
                navigator.maxTouchPoints > 0;
            el.addEventListener('mouseenter', () => (this.containerHovered = true));
            el.addEventListener('mouseleave', () => (this.containerHovered = false));

            this.$watch('containerHovered', (value) => {
                if (!value) {
                    this.handleOutside();
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
            };
            this._onSwitchView = (event) => {
                const nextView = String(event?.detail?.to ?? '').trim();

                if (nextView === 'main-menu') {
                    this.refreshDailyProgress();
                    return;
                }

                this.resetInsightsPanelState();
            };

            window.addEventListener('pointerdown', this._onWindowPointerDown, true);
            window.addEventListener('storage', this._onWindowStorage);
            window.addEventListener('switch-view', this._onSwitchView);

            this.refreshDailyProgress();

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
            this.stopInsightsRefreshLoop();

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
        clearInsightsHideTimer() {
            if (this.insightsHideTimer !== null) {
                clearTimeout(this.insightsHideTimer);
                this.insightsHideTimer = null;
            }
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

            if (refresh) {
                this.refreshDailyProgress();
            }

            this.isInsightsExpanded = true;
        },
        scheduleInsightsCollapse() {
            this.clearInsightsHideTimer();

            if (!this.isInsightsExpanded) {
                return;
            }

            this.insightsHideTimer = window.setTimeout(() => {
                if (this.isInsightsPointerInside) {
                    return;
                }

                this.isInsightsExpanded = false;
            }, this.insightsHideDelayMs);
        },
        resetInsightsPanelState() {
            this.clearInsightsHideTimer();
            this.stopInsightsRefreshLoop();
            this.isInsightsPointerInside = false;
            this.isInsightsExpanded = false;
        },
        handleInsightsHoverEnter() {
            if (this.isTouchDevice) {
                return;
            }

            this.isInsightsPointerInside = true;
            this.showInsightsPanel();
        },
        handleInsightsHoverLeave() {
            if (this.isTouchDevice) {
                return;
            }

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

            this.isInsightsPointerInside = true;
            this.showInsightsPanel();
        },
        toggleInsightsPanel() {
            if (this.isTouchDevice) {
                this.isInsightsPointerInside = true;
                this.showInsightsPanel();
                return;
            }

            if (this.isInsightsExpanded) {
                this.isInsightsPointerInside = false;
                this.scheduleInsightsCollapse();
                return;
            }

            this.isInsightsPointerInside = true;
            this.showInsightsPanel();
        },
        handleInsightsWindowPointerDown(event) {
            if (!this.isTouchDevice || !this.isInsightsExpanded) {
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
        handleOutside(clearHover = false) {
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
            this.isInsightsPointerInside = false;
            this.scheduleInsightsCollapse();

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
