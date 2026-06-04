export const createTextInteractionModule = (deps) => {
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

            const isOriginActive = this.isOriginOverflowVisible(this.activeIndex);
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
            this.syncTextBoxEdgeFade(this.textScroll.element);

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
                this.syncTextBoxEdgeFade(this.textScroll.element);
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

        tapAuraStyle(index) {
            const xPercent = Number(this.tapAura?.xPercent ?? 50);
            const yPercent = Number(this.tapAura?.yPercent ?? 50);
            const normalizedX = Number.isFinite(xPercent)
                ? Math.max(0, Math.min(100, xPercent))
                : 50;
            const normalizedY = Number.isFinite(yPercent)
                ? Math.max(0, Math.min(100, yPercent))
                : 50;
            const isActiveIndex = this.tapAura?.index === index;
            const clickActive = isActiveIndex && this.tapAura?.clickActive === true;
            const isHolding = isActiveIndex && this.tapAura?.isHolding === true;
            const releaseActive = isActiveIndex && this.tapAura?.releaseActive === true;
            const opacity = clickActive || isHolding || releaseActive ? 1 : 0;

            return `--athkar-tap-aura-x: ${normalizedX}%; --athkar-tap-aura-y: ${normalizedY}%; opacity: ${opacity};`;
        },

        tapAuraSource(event) {
            return event?.type?.startsWith('touch') ? 'touch' : 'pointer';
        },

        resolveTapAuraTarget(event = null) {
            const directTarget =
                event?.currentTarget?.closest?.('[data-athkar-tap]') ??
                event?.target?.closest?.('[data-athkar-tap]');

            if (directTarget instanceof Element) {
                return directTarget;
            }

            return (
                this.$el?.querySelector?.(
                    '[data-athkar-slide][data-active="true"] [data-athkar-tap]',
                ) ?? null
            );
        },

        resolveTapAuraPoint(event = null, target = null) {
            if (!(target instanceof Element)) {
                return null;
            }

            const rect = target.getBoundingClientRect();

            if (
                !Number.isFinite(rect.width) ||
                !Number.isFinite(rect.height) ||
                rect.width <= 0 ||
                rect.height <= 0
            ) {
                return null;
            }

            const point = this.swipePoint(event) ?? {
                x: rect.left + rect.width / 2,
                y: rect.top + rect.height / 2,
                pointerType: event?.pointerType ?? 'mouse',
                pointerId: event?.pointerId ?? null,
            };

            const boundedX = Math.max(0, Math.min(rect.width, point.x - rect.left));
            const boundedY = Math.max(0, Math.min(rect.height, point.y - rect.top));

            return {
                xPercent: (boundedX / rect.width) * 100,
                yPercent: (boundedY / rect.height) * 100,
                pointerId: point.pointerId ?? null,
            };
        },

        clearTapAuraTimers() {
            if (this.tapAura.clickTimer !== null) {
                clearTimeout(this.tapAura.clickTimer);
                this.tapAura.clickTimer = null;
            }

            if (this.tapAura.releaseTimer !== null) {
                clearTimeout(this.tapAura.releaseTimer);
                this.tapAura.releaseTimer = null;
            }
        },

        clearTapAura({ keepIndex = false } = {}) {
            this.tapAura.isHolding = false;
            this.tapAura.clickActive = false;
            this.tapAura.releaseActive = false;
            this.tapAura.source = null;
            this.tapAura.pointerId = null;

            if (!keepIndex) {
                this.tapAura.index = null;
            }

            this.clearTapAuraTimers();
        },

        startTapAuraClickPhase() {
            this.tapAura.clickActive = true;

            if (this.tapAura.clickTimer !== null) {
                clearTimeout(this.tapAura.clickTimer);
            }

            this.tapAura.clickTimer = window.setTimeout(
                () => {
                    this.tapAura.clickActive = false;
                    this.tapAura.clickTimer = null;

                    if (!this.tapAura.isHolding && !this.tapAura.releaseActive) {
                        this.tapAura.index = null;
                    }
                },
                Number(this.tapAuraClickDurationMs ?? 180),
            );
        },

        startTapAuraReleasePhase() {
            this.tapAura.releaseActive = true;

            if (this.tapAura.releaseTimer !== null) {
                clearTimeout(this.tapAura.releaseTimer);
            }

            this.tapAura.releaseTimer = window.setTimeout(
                () => {
                    this.tapAura.releaseActive = false;
                    this.tapAura.releaseTimer = null;

                    if (!this.tapAura.isHolding && !this.tapAura.clickActive) {
                        this.tapAura.index = null;
                    }
                },
                Number(this.tapAuraReleaseDurationMs ?? 620),
            );
        },

        beginTapAuraHold(event, index) {
            if (
                !this.activeMode ||
                this.activeIndex !== index ||
                !this.shouldEnableVisualEnhancements()
            ) {
                this.clearTapAura();
                return;
            }

            if (event?.pointerType === 'mouse' && event.button !== 0) {
                return;
            }

            const source = this.tapAuraSource(event);

            if (this.tapAura.source && this.tapAura.source !== source) {
                return;
            }

            const target = this.resolveTapAuraTarget(event);
            const point = this.resolveTapAuraPoint(event, target);

            if (!point) {
                return;
            }

            this.tapAura.index = index;
            this.tapAura.isHolding = true;
            this.tapAura.source = source;
            this.tapAura.pointerId = point.pointerId;
            this.tapAura.xPercent = point.xPercent;
            this.tapAura.yPercent = point.yPercent;
            this.tapAura.clickActive = false;
            this.tapAura.releaseActive = false;

            if (this.tapAura.releaseTimer !== null) {
                clearTimeout(this.tapAura.releaseTimer);
                this.tapAura.releaseTimer = null;
            }
        },

        moveTapAuraHold(event) {
            if (!this.tapAura?.isHolding || !this.shouldEnableVisualEnhancements()) {
                return;
            }

            const source = this.tapAuraSource(event);

            if (this.tapAura.source && this.tapAura.source !== source) {
                return;
            }

            const target = this.resolveTapAuraTarget(event);
            const point = this.resolveTapAuraPoint(event, target);

            if (!point) {
                return;
            }

            if (this.tapAura.pointerId !== null && point.pointerId !== this.tapAura.pointerId) {
                return;
            }

            this.tapAura.xPercent = point.xPercent;
            this.tapAura.yPercent = point.yPercent;
        },

        endTapAuraHold(event = null) {
            if (!this.tapAura?.isHolding) {
                return;
            }

            const source = this.tapAuraSource(event);

            if (this.tapAura.source && this.tapAura.source !== source) {
                return;
            }

            const target = this.resolveTapAuraTarget(event);
            const point = this.resolveTapAuraPoint(event, target);

            if (
                point &&
                (this.tapAura.pointerId === null || point.pointerId === this.tapAura.pointerId)
            ) {
                this.tapAura.xPercent = point.xPercent;
                this.tapAura.yPercent = point.yPercent;
            }

            this.tapAura.isHolding = false;
            this.tapAura.source = null;
            this.tapAura.pointerId = null;
        },

        cancelTapAuraHold(event = null) {
            if (!this.tapAura?.isHolding) {
                return;
            }

            if (event) {
                const source = this.tapAuraSource(event);

                if (this.tapAura.source && this.tapAura.source !== source) {
                    return;
                }
            }

            this.tapAura.isHolding = false;
            this.tapAura.source = null;
            this.tapAura.pointerId = null;
        },

        triggerTapAuraRelease(index, event = null) {
            if (!this.shouldEnableVisualEnhancements()) {
                this.clearTapAura();
                return;
            }

            const shouldResolveInteractionPoint = Boolean(event) || this.tapAura.index !== index;

            if (shouldResolveInteractionPoint) {
                const target = this.resolveTapAuraTarget(event);
                const point = this.resolveTapAuraPoint(event, target);

                if (point) {
                    this.tapAura.xPercent = point.xPercent;
                    this.tapAura.yPercent = point.yPercent;
                }
            }

            if (
                !Number.isFinite(this.tapAura.xPercent) ||
                !Number.isFinite(this.tapAura.yPercent)
            ) {
                this.tapAura.xPercent = 50;
                this.tapAura.yPercent = 50;
            }

            this.tapAura.index = index;
            this.tapAura.isHolding = false;
            this.tapAura.source = null;
            this.tapAura.pointerId = null;
            this.startTapAuraClickPhase();
            this.startTapAuraReleasePhase();
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

        async writeNativeBridgeClipboardText(text) {
            if (typeof window === 'undefined') {
                return false;
            }

            const bridge = window.AndroidBridge;

            if (!bridge || typeof bridge.copyText !== 'function') {
                return false;
            }

            try {
                const didCopy = await bridge.copyText(String(text ?? ''));

                return didCopy !== false;
            } catch (_) {
                return false;
            }
        },

        async writeClipboardText(text) {
            const normalizedText = String(text ?? '').trim();

            if (!normalizedText) {
                return false;
            }

            if (await this.writeNativeBridgeClipboardText(normalizedText)) {
                return true;
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

        resolveScrollbarEdgeHitZone(box) {
            if (!(box instanceof HTMLElement)) {
                return 0;
            }

            if (!box.classList.contains('athkar-text-box--touch-scroll')) {
                return 0;
            }

            const totalInlineGutter = Math.max(0, box.offsetWidth - box.clientWidth);

            if (totalInlineGutter > 0) {
                return Math.max(1, Math.ceil(totalInlineGutter / 2));
            }

            return 14;
        },

        didHoldCopyStartOnScrollbar(event) {
            if (event?.type?.startsWith('touch')) {
                return false;
            }

            const box = event?.currentTarget;

            if (!(box instanceof HTMLElement)) {
                return false;
            }

            if (!this.shouldAllowTouchScrollForBox(box)) {
                return false;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return false;
            }

            const rect = box.getBoundingClientRect();
            const edgeZone = this.resolveScrollbarEdgeHitZone(box);

            if (rect.width <= 0 || edgeZone <= 0) {
                return false;
            }

            const x = point.x - rect.left;

            return x <= edgeZone || x >= rect.width - edgeZone;
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

            if (this.didHoldCopyStartOnScrollbar(event)) {
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

            if (
                typeof document !== 'undefined' &&
                document.body instanceof HTMLElement &&
                document.body.classList.contains('nativephp-ios') &&
                event?.type?.startsWith?.('touch')
            ) {
                event.preventDefault?.();
            }

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

                return;
            }

            if (
                typeof document !== 'undefined' &&
                document.body instanceof HTMLElement &&
                document.body.classList.contains('nativephp-ios') &&
                event?.type?.startsWith?.('touch')
            ) {
                event.preventDefault?.();
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

                if (increment.didAdvance) {
                    didHandleSwipe = true;
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
    };
};
