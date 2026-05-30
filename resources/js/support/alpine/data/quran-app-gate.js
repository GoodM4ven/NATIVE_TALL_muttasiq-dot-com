document.addEventListener('alpine:init', () => {
    const launchNavigateDelayMs = 120;
    const launchCleanupDelayMs = 720;
    const returnNavigateDelayMs = 110;
    const returnCleanupDelayMs = 700;
    const baseLaunchNavigateDelayMs = 96;
    const baseLaunchCleanupDelayMs = 320;
    const baseReturnNavigateDelayMs = 0;
    const baseReturnCleanupDelayMs = 220;
    const defaultOrbitAngleDeg = 180;
    const modeOrbitAngles = Object.freeze({
        tilawa: 0,
        hifth: 120,
        tadabbur: 240,
    });
    const defaultLaunchOrigins = Object.freeze({
        tilawa: { x: 50, y: 31 },
        hifth: { x: 74, y: 73 },
        tadabbur: { x: 26, y: 73 },
    });

    window.Alpine.data('quranAppGate', () => ({
        modeAvailability: Object.freeze({
            tilawa: true,
            hifth: false,
            tadabbur: false,
        }),
        projectedMode: null,
        pinnedMode: null,
        armedMode: null,
        isPointerInside: false,
        isModePinned: false,
        orbitAngleDeg: 0,
        orbitRenderAngleDeg: 0,
        orbitTargetAngleDeg: 0,
        touchPointerId: null,
        activeTouchIdentifier: null,
        isTouchPointerActive: false,
        touchStartClientX: null,
        touchStartClientY: null,
        didTouchOrbitMove: false,
        suppressNextOpenMode: null,
        touchReleaseArmedMode: null,
        orbitAnimationFrameId: null,
        orbitLastFrameAt: 0,
        isLaunchTransitioning: false,
        launchMode: null,
        launchNavigateTimeoutId: null,
        launchCleanupTimeoutId: null,
        activeTransitionDirection: null,
        geometryCache: null,
        geometryCacheFrameId: null,
        pendingOrbitUpdate: null,
        orbitPointerFrameId: null,
        _onSwitchView: null,
        _onReaderGoGate: null,
        _onExternalGateOpen: null,
        _onWindowResize: null,
        externalGateOpenTimerId: null,
        init() {
            this._onSwitchView = (event) => {
                const nextView = String(event?.detail?.to ?? '');

                if (
                    nextView === 'quran-app-gate' &&
                    this.activeTransitionDirection === 'backward'
                ) {
                    return;
                }

                if (
                    nextView === 'quran-app-gate' ||
                    nextView === 'main-menu' ||
                    nextView.startsWith('athkar-app-')
                ) {
                    this.clearLaunchTransitionState();
                }
            };
            this._onReaderGoGate = () => {
                this.returnToGate();
            };
            this._onExternalGateOpen = (event) => {
                const requestedMode = String(event?.detail?.mode ?? 'tilawa')
                    .trim()
                    .toLowerCase();
                const mode = ['tilawa', 'hifth', 'tadabbur'].includes(requestedMode)
                    ? requestedMode
                    : 'tilawa';
                const requestedDelay = Number(event?.detail?.delayMs ?? NaN);
                const delayMs = Number.isFinite(requestedDelay)
                    ? Math.max(0, Math.trunc(requestedDelay))
                    : 0;

                if (!this.views?.['quran-app-gate']?.isOpen) {
                    return;
                }

                if (this.externalGateOpenTimerId !== null) {
                    clearTimeout(this.externalGateOpenTimerId);
                    this.externalGateOpenTimerId = null;
                }

                const openRequestedMode = () => {
                    this.externalGateOpenTimerId = null;
                    this.openMode(mode);
                };

                if (delayMs > 0) {
                    this.externalGateOpenTimerId = window.setTimeout(openRequestedMode, delayMs);
                    return;
                }

                openRequestedMode();
            };
            window.addEventListener('switch-view', this._onSwitchView);
            window.addEventListener('quran-reader-go-gate', this._onReaderGoGate);
            window.addEventListener('quran-gate-open', this._onExternalGateOpen);

            this._onWindowResize = () => {
                this.scheduleGeometryCacheRefresh();
            };
            window.addEventListener('resize', this._onWindowResize, { passive: true });
            window.addEventListener('orientationchange', this._onWindowResize, { passive: true });

            this.$nextTick(() => {
                this.refreshGeometryCache();
                this.positionPuckAtDefault();
            });
        },
        destroy() {
            this.clearLaunchTransitionState();
            this.cancelPointerOrbitUpdate();
            this.cancelGeometryCacheRefresh();

            if (typeof window !== 'undefined' && this._onSwitchView) {
                window.removeEventListener('switch-view', this._onSwitchView);
                this._onSwitchView = null;
            }

            if (typeof window !== 'undefined' && this._onReaderGoGate) {
                window.removeEventListener('quran-reader-go-gate', this._onReaderGoGate);
                this._onReaderGoGate = null;
            }

            if (typeof window !== 'undefined' && this._onExternalGateOpen) {
                window.removeEventListener('quran-gate-open', this._onExternalGateOpen);
                this._onExternalGateOpen = null;
            }

            if (this.externalGateOpenTimerId !== null) {
                clearTimeout(this.externalGateOpenTimerId);
                this.externalGateOpenTimerId = null;
            }

            if (typeof window !== 'undefined' && this._onWindowResize) {
                window.removeEventListener('resize', this._onWindowResize);
                window.removeEventListener('orientationchange', this._onWindowResize);
                this._onWindowResize = null;
            }
        },
        isFastUiMode() {
            return window.__APP_BROWSER_TEST_FAST_UI === true;
        },
        quranShellElement() {
            return this.$root?.closest?.('[data-quran-app-shell]') ?? null;
        },
        clearLaunchTransitionTimers() {
            if (this.launchNavigateTimeoutId !== null) {
                clearTimeout(this.launchNavigateTimeoutId);
                this.launchNavigateTimeoutId = null;
            }

            if (this.launchCleanupTimeoutId !== null) {
                clearTimeout(this.launchCleanupTimeoutId);
                this.launchCleanupTimeoutId = null;
            }
        },
        resolveLaunchOrigin(mode, event = null) {
            const shellElement = this.quranShellElement();
            const fallbackOrigin = defaultLaunchOrigins[mode] ?? defaultLaunchOrigins.tilawa;

            if (!shellElement) {
                return fallbackOrigin;
            }

            const shellRect = shellElement.getBoundingClientRect();
            const pointerX = Number(event?.clientX ?? NaN);
            const pointerY = Number(event?.clientY ?? NaN);
            const hasValidPointer =
                Number.isFinite(pointerX) &&
                Number.isFinite(pointerY) &&
                pointerX >= shellRect.left &&
                pointerX <= shellRect.right &&
                pointerY >= shellRect.top &&
                pointerY <= shellRect.bottom;

            if (!hasValidPointer || shellRect.width <= 0 || shellRect.height <= 0) {
                return fallbackOrigin;
            }

            return {
                x: ((pointerX - shellRect.left) / shellRect.width) * 100,
                y: ((pointerY - shellRect.top) / shellRect.height) * 100,
            };
        },
        applyTransitionState(mode, event = null) {
            const shellElement = this.quranShellElement();

            if (!shellElement) {
                return false;
            }

            const origin = this.resolveLaunchOrigin(mode, event);
            shellElement.style.setProperty('--quran-gate-launch-origin-x', `${origin.x}%`);
            shellElement.style.setProperty('--quran-gate-launch-origin-y', `${origin.y}%`);
            shellElement.setAttribute('data-quran-launch-mode', mode);
            shellElement.classList.remove('quran-app-shell--reader-launching');
            shellElement.classList.remove('quran-app-shell--reader-entering');
            shellElement.classList.remove('quran-app-shell--reader-leaving');
            shellElement.classList.remove('quran-app-shell--gate-returning');
            void shellElement.offsetWidth;

            return true;
        },
        activeReaderMode() {
            if (this.views?.['quran-app-tilawa']?.isOpen) {
                return 'tilawa';
            }

            if (this.views?.['quran-app-hifth']?.isOpen) {
                return 'hifth';
            }

            if (this.views?.['quran-app-tadabbur']?.isOpen) {
                return 'tadabbur';
            }

            return this.launchMode ?? 'tilawa';
        },
        returnToGate(mode = null) {
            if (this.activeTransitionDirection !== null) {
                return;
            }

            if (this.isFastUiMode()) {
                this.$viewNav('quran-app-gate');
                return;
            }

            const resolvedMode =
                typeof mode === 'string' && mode.length > 0 ? mode : this.activeReaderMode();
            const isMobileBasePerfMode = this.shouldUseMobileBasePerfMode();
            const didApplyTransitionState = !isMobileBasePerfMode
                ? this.applyTransitionState(resolvedMode)
                : false;

            this.isLaunchTransitioning = true;
            this.launchMode = resolvedMode;
            this.activeTransitionDirection = 'backward';

            if (didApplyTransitionState && !isMobileBasePerfMode) {
                const shellElement = this.quranShellElement();

                if (shellElement) {
                    shellElement.classList.add('quran-app-shell--reader-leaving');
                }
            }

            this.launchNavigateTimeoutId = window.setTimeout(
                () => {
                    this.launchNavigateTimeoutId = null;
                    this.$viewNav('quran-app-gate');

                    const shellElement = this.quranShellElement();

                    if (shellElement && !isMobileBasePerfMode) {
                        shellElement.classList.add('quran-app-shell--gate-returning');
                    }
                },
                isMobileBasePerfMode
                    ? baseReturnNavigateDelayMs
                    : didApplyTransitionState
                      ? returnNavigateDelayMs
                      : 0,
            );

            this.launchCleanupTimeoutId = window.setTimeout(
                () => {
                    this.clearLaunchTransitionState();
                },
                isMobileBasePerfMode ? baseReturnCleanupDelayMs : returnCleanupDelayMs,
            );
        },
        clearLaunchTransitionState() {
            this.clearLaunchTransitionTimers();
            this.cancelPointerOrbitUpdate();
            this.isLaunchTransitioning = false;
            this.launchMode = null;
            this.activeTransitionDirection = null;
            this.armedMode = null;
            this.suppressNextOpenMode = null;
            this.touchReleaseArmedMode = null;
            this.clearTouchGestureState();

            const shellElement = this.quranShellElement();

            if (!shellElement) {
                return;
            }

            shellElement.classList.remove('quran-app-shell--reader-launching');
            shellElement.classList.remove('quran-app-shell--reader-entering');
            shellElement.classList.remove('quran-app-shell--reader-leaving');
            shellElement.classList.remove('quran-app-shell--gate-returning');
            shellElement.classList.remove('quran-app-shell--reader-launching-base');
            shellElement.removeAttribute('data-quran-launch-mode');
            shellElement.style.removeProperty('--quran-gate-launch-origin-x');
            shellElement.style.removeProperty('--quran-gate-launch-origin-y');
        },
        hasTouchInput() {
            return Boolean(this.$store?.bp?.hasTouch);
        },
        shouldUseMobileBasePerfMode() {
            if (typeof document !== 'undefined') {
                if (document.documentElement.classList.contains('native-platform')) {
                    return true;
                }
            }

            if (typeof this.$store?.bp?.is === 'function') {
                return this.$store.bp.is('base');
            }

            return false;
        },
        cancelGeometryCacheRefresh() {
            if (this.geometryCacheFrameId === null) {
                return;
            }

            cancelAnimationFrame(this.geometryCacheFrameId);
            this.geometryCacheFrameId = null;
        },
        scheduleGeometryCacheRefresh() {
            if (!this.shouldUseMobileBasePerfMode()) {
                return;
            }

            if (this.geometryCacheFrameId !== null) {
                return;
            }

            this.geometryCacheFrameId = requestAnimationFrame(() => {
                this.geometryCacheFrameId = null;
                this.refreshGeometryCache();
            });
        },
        refreshGeometryCache() {
            if (!this.shouldUseMobileBasePerfMode()) {
                this.geometryCache = null;
                return;
            }

            const shellElement = this.$refs?.shell;
            const anchorCircle = this.$refs?.anchorCircle;

            if (!shellElement || !anchorCircle) {
                this.geometryCache = null;
                return;
            }

            const shellRect = shellElement.getBoundingClientRect();
            const anchorRect = anchorCircle.getBoundingClientRect();

            if (!shellRect.width || !shellRect.height || !anchorRect.width || !anchorRect.height) {
                this.geometryCache = null;
                return;
            }

            const anchorCenterX = anchorRect.left + anchorRect.width / 2;
            const anchorCenterY = anchorRect.top + anchorRect.height / 2;

            this.geometryCache = {
                shellRect,
                anchorCenterX,
                anchorCenterY,
                radius: anchorRect.width / 2,
            };
        },
        resolveGeometryCache() {
            if (!this.shouldUseMobileBasePerfMode()) {
                return null;
            }

            if (this.geometryCache) {
                return this.geometryCache;
            }

            this.refreshGeometryCache();

            return this.geometryCache;
        },
        resolveGeometryFromDom() {
            const shellElement = this.$refs?.shell;
            const anchorCircle = this.$refs?.anchorCircle;

            if (!shellElement || !anchorCircle) {
                return null;
            }

            const shellRect = shellElement.getBoundingClientRect();
            const anchorRect = anchorCircle.getBoundingClientRect();

            if (!shellRect.width || !shellRect.height || !anchorRect.width || !anchorRect.height) {
                return null;
            }

            return {
                shellRect,
                anchorCenterX: anchorRect.left + anchorRect.width / 2,
                anchorCenterY: anchorRect.top + anchorRect.height / 2,
                radius: anchorRect.width / 2,
            };
        },
        normalizeAngle(angle) {
            const fullTurn = Math.PI * 2;

            return ((angle % fullTurn) + fullTurn) % fullTurn;
        },
        isAngleWithinArc(angle, arcStart, arcEnd) {
            const fullTurn = Math.PI * 2;
            const normalizedAngle = this.normalizeAngle(angle);
            const normalizedStart = this.normalizeAngle(arcStart);
            const normalizedEnd = this.normalizeAngle(arcEnd);
            const span = (normalizedEnd - normalizedStart + fullTurn) % fullTurn;
            const progress = (normalizedAngle - normalizedStart + fullTurn) % fullTurn;

            return progress <= span;
        },
        resolveModeFromAngle(angle, centerX, centerY, shellRect) {
            if (!shellRect) {
                return null;
            }

            const topLeftAngle = Math.atan2(shellRect.top - centerY, shellRect.left - centerX);
            const topRightAngle = Math.atan2(shellRect.top - centerY, shellRect.right - centerX);
            const bottomCenterAngle = Math.atan2(
                shellRect.bottom - centerY,
                shellRect.left + shellRect.width / 2 - centerX,
            );

            if (this.isAngleWithinArc(angle, topLeftAngle, topRightAngle)) {
                return 'tilawa';
            }

            if (this.isAngleWithinArc(angle, topRightAngle, bottomCenterAngle)) {
                return 'hifth';
            }

            return 'tadabbur';
        },
        isModeActive(mode) {
            return this.modeForUiState() === mode;
        },
        isModeAvailable(mode) {
            return Boolean(this.modeAvailability?.[mode] ?? false);
        },
        isModeLocked(mode) {
            return !this.isModeAvailable(mode);
        },
        modeForUiState() {
            if (this.hasTouchInput() && this.isTouchPointerActive && this.projectedMode) {
                return this.projectedMode;
            }

            return this.armedMode ?? this.pinnedMode ?? this.projectedMode;
        },
        currentMode() {
            return this.modeForUiState();
        },
        requiresArmedActivation() {
            return this.hasTouchInput();
        },
        armMode(mode) {
            this.armedMode = mode;
            this.setOrbitAngle(modeOrbitAngles[mode] ?? defaultOrbitAngleDeg);
        },
        armProjectedModeAfterTouchRelease() {
            if (!this.projectedMode) {
                return;
            }

            const previouslyArmedMode = this.armedMode;
            this.armMode(this.projectedMode);
            this.touchReleaseArmedMode = this.projectedMode;
            this.suppressNextOpenMode =
                previouslyArmedMode !== this.projectedMode ? this.projectedMode : null;
        },
        clearTouchGestureState() {
            this.touchStartClientX = null;
            this.touchStartClientY = null;
            this.didTouchOrbitMove = false;
        },
        markTouchGestureMovement(clientX, clientY) {
            const startX = Number(this.touchStartClientX);
            const startY = Number(this.touchStartClientY);

            if (!Number.isFinite(startX) || !Number.isFinite(startY)) {
                return;
            }

            const deltaX = Number(clientX) - startX;
            const deltaY = Number(clientY) - startY;
            const movement = Math.hypot(deltaX, deltaY);

            if (movement >= 4) {
                this.didTouchOrbitMove = true;
            }
        },
        clearArmedMode() {
            this.armedMode = null;
        },
        resolveModeFromOrbitAngleDeg(orbitAngleDeg) {
            const geometry = this.shouldUseMobileBasePerfMode()
                ? this.resolveGeometryCache()
                : this.resolveGeometryFromDom();

            if (!geometry) {
                return 'tilawa';
            }
            const projectedAngle = ((orbitAngleDeg - 90) * Math.PI) / 180;

            return (
                this.resolveModeFromAngle(
                    projectedAngle,
                    geometry.anchorCenterX,
                    geometry.anchorCenterY,
                    geometry.shellRect,
                ) ?? 'tilawa'
            );
        },
        syncProjectedModeWithOrbitAngle(orbitAngleDeg = this.orbitRenderAngleDeg) {
            const nextProjectedMode = this.resolveModeFromOrbitAngleDeg(orbitAngleDeg);

            if (nextProjectedMode === this.projectedMode) {
                return;
            }

            this.projectedMode = nextProjectedMode;
        },
        setOrbitAngle(targetAngleDeg) {
            if (!Number.isFinite(targetAngleDeg)) {
                return;
            }

            if (this.shouldUseMobileBasePerfMode() && this.isTouchPointerActive) {
                this.orbitTargetAngleDeg = targetAngleDeg;
                this.orbitRenderAngleDeg = targetAngleDeg;
                this.orbitAngleDeg = targetAngleDeg;
                this.syncProjectedModeWithOrbitAngle(targetAngleDeg);

                if (this.orbitAnimationFrameId !== null) {
                    cancelAnimationFrame(this.orbitAnimationFrameId);
                    this.orbitAnimationFrameId = null;
                    this.orbitLastFrameAt = 0;
                }

                return;
            }

            const wrappedDelta = ((targetAngleDeg - this.orbitTargetAngleDeg + 540) % 360) - 180;

            this.orbitTargetAngleDeg += wrappedDelta;
            this.startOrbitAnimation();
        },
        startOrbitAnimation() {
            if (this.orbitAnimationFrameId !== null) {
                return;
            }

            this.orbitLastFrameAt = 0;
            this.orbitAnimationFrameId = requestAnimationFrame((timestamp) => {
                this.animateOrbitFrame(timestamp);
            });
        },
        animateOrbitFrame(timestamp) {
            if (this.orbitLastFrameAt === 0) {
                this.orbitLastFrameAt = timestamp;
            }

            const elapsedMs = Math.min(34, Math.max(8, timestamp - this.orbitLastFrameAt));
            this.orbitLastFrameAt = timestamp;

            const delta = ((this.orbitTargetAngleDeg - this.orbitRenderAngleDeg + 540) % 360) - 180;
            const responseMs = this.isPointerInside ? 54 : 78;
            const easingFactor = 1 - Math.exp(-(elapsedMs / responseMs));

            this.orbitRenderAngleDeg += delta * easingFactor;
            this.orbitAngleDeg = this.orbitRenderAngleDeg;
            this.syncProjectedModeWithOrbitAngle(this.orbitRenderAngleDeg);

            if (Math.abs(delta) <= 0.08) {
                this.orbitRenderAngleDeg = this.orbitTargetAngleDeg;
                this.orbitAngleDeg = this.orbitTargetAngleDeg;
                this.syncProjectedModeWithOrbitAngle(this.orbitRenderAngleDeg);
                this.orbitAnimationFrameId = null;
                this.orbitLastFrameAt = 0;

                return;
            }

            this.orbitAnimationFrameId = requestAnimationFrame((nextTimestamp) => {
                this.animateOrbitFrame(nextTimestamp);
            });
        },
        pinMode(mode) {
            if (!this.isModeAvailable(mode)) {
                return;
            }

            this.pinnedMode = mode;
            this.isModePinned = true;

            if (!this.isPointerInside) {
                this.setOrbitAngle(
                    {
                        tilawa: 0,
                        hifth: 120,
                        tadabbur: 240,
                    }[mode] ?? 0,
                );
            }
        },
        unpinMode(mode) {
            if (this.pinnedMode !== mode) {
                return;
            }

            this.pinnedMode = null;
            this.isModePinned = false;
        },
        handlePointerEnter() {
            this.isPointerInside = true;

            if (this.shouldUseMobileBasePerfMode()) {
                this.scheduleGeometryCacheRefresh();
            }
        },
        handlePointerLeave() {
            this.isPointerInside = false;
        },
        handlePointerDown(event) {
            if (event.pointerType !== 'touch' || !this.hasTouchInput()) {
                return;
            }

            this.touchPointerId = event.pointerId;
            this.isTouchPointerActive = true;
            this.isPointerInside = true;

            if (this.shouldUseMobileBasePerfMode()) {
                this.scheduleGeometryCacheRefresh();
            }

            this.touchStartClientX = event.clientX;
            this.touchStartClientY = event.clientY;
            this.didTouchOrbitMove = false;

            if (event.cancelable) {
                event.preventDefault();
            }

            if (this.$refs?.shell?.setPointerCapture) {
                try {
                    this.$refs.shell.setPointerCapture(event.pointerId);
                } catch {
                    // No-op: pointer capture can fail if pointer is no longer active.
                }
            }

            this.handlePointerMove(event);
        },
        handlePointerUp(event) {
            if (event.pointerType !== 'touch' || !this.hasTouchInput()) {
                return;
            }

            if (this.touchPointerId !== null && event.pointerId !== this.touchPointerId) {
                return;
            }

            this.touchPointerId = null;
            this.isTouchPointerActive = false;
            this.isPointerInside = false;

            if (event.type !== 'pointercancel' && this.projectedMode && this.didTouchOrbitMove) {
                this.armProjectedModeAfterTouchRelease();
            }

            this.cancelPointerOrbitUpdate();
            this.clearTouchGestureState();

            if (this.$refs?.shell?.releasePointerCapture) {
                try {
                    this.$refs.shell.releasePointerCapture(event.pointerId);
                } catch {
                    // No-op: pointer capture can already be released.
                }
            }
        },
        positionPuckAtDefault() {
            this.setOrbitAngle(defaultOrbitAngleDeg);
        },
        resolveActiveTouch(touchList) {
            if (!touchList?.length) {
                return null;
            }

            if (this.activeTouchIdentifier === null) {
                return touchList[0];
            }

            for (const touch of touchList) {
                if (touch.identifier === this.activeTouchIdentifier) {
                    return touch;
                }
            }

            return null;
        },
        handleTouchStart(event) {
            if (!this.hasTouchInput()) {
                return;
            }

            if (this.touchPointerId !== null) {
                return;
            }

            const touch =
                this.resolveActiveTouch(event.changedTouches) ??
                this.resolveActiveTouch(event.touches);

            if (!touch) {
                return;
            }

            this.activeTouchIdentifier = touch.identifier;
            this.isTouchPointerActive = true;
            this.isPointerInside = true;

            if (this.shouldUseMobileBasePerfMode()) {
                this.scheduleGeometryCacheRefresh();
            }

            this.touchStartClientX = touch.clientX;
            this.touchStartClientY = touch.clientY;
            this.didTouchOrbitMove = false;

            if (this.shouldUseMobileBasePerfMode()) {
                this.queueOrbitUpdate(touch.clientX, touch.clientY);
                return;
            }

            this.updateOrbitFromClientPoint(touch.clientX, touch.clientY);
        },
        handleTouchMove(event) {
            if (!this.hasTouchInput() || !this.isTouchPointerActive) {
                return;
            }

            if (this.touchPointerId !== null) {
                return;
            }

            const touch =
                this.resolveActiveTouch(event.touches) ??
                this.resolveActiveTouch(event.changedTouches);

            if (!touch) {
                return;
            }

            this.markTouchGestureMovement(touch.clientX, touch.clientY);

            if (this.shouldUseMobileBasePerfMode()) {
                this.queueOrbitUpdate(touch.clientX, touch.clientY);
                return;
            }

            this.updateOrbitFromClientPoint(touch.clientX, touch.clientY);
        },
        handleTouchEnd(event) {
            if (!this.hasTouchInput()) {
                return;
            }

            if (this.touchPointerId !== null) {
                return;
            }

            const touch = this.resolveActiveTouch(event.changedTouches);

            if (!touch && this.isTouchPointerActive) {
                return;
            }

            this.activeTouchIdentifier = null;
            this.isTouchPointerActive = false;
            this.isPointerInside = false;

            if (event.type !== 'touchcancel' && this.projectedMode && this.didTouchOrbitMove) {
                this.armProjectedModeAfterTouchRelease();
            }

            this.cancelPointerOrbitUpdate();
            this.clearTouchGestureState();
        },
        handlePointerMove(event) {
            if (
                event.pointerType === 'touch' &&
                (!this.isTouchPointerActive ||
                    (this.touchPointerId !== null && event.pointerId !== this.touchPointerId))
            ) {
                return;
            }

            if (event.pointerType === 'touch') {
                this.markTouchGestureMovement(event.clientX, event.clientY);
            }

            if (this.shouldUseMobileBasePerfMode()) {
                this.queueOrbitUpdate(event.clientX, event.clientY);
                return;
            }

            this.updateOrbitFromClientPoint(event.clientX, event.clientY);
        },
        cancelPointerOrbitUpdate() {
            if (this.orbitPointerFrameId !== null) {
                cancelAnimationFrame(this.orbitPointerFrameId);
                this.orbitPointerFrameId = null;
            }

            this.pendingOrbitUpdate = null;
        },
        queueOrbitUpdate(clientX, clientY) {
            this.pendingOrbitUpdate = {
                clientX,
                clientY,
            };

            if (this.orbitPointerFrameId !== null) {
                return;
            }

            this.orbitPointerFrameId = requestAnimationFrame(() => {
                this.orbitPointerFrameId = null;
                const pendingUpdate = this.pendingOrbitUpdate;

                if (!pendingUpdate) {
                    return;
                }

                this.pendingOrbitUpdate = null;
                this.updateOrbitFromClientPoint(pendingUpdate.clientX, pendingUpdate.clientY);
            });
        },
        updateOrbitFromClientPoint(clientX, clientY) {
            const geometry = this.shouldUseMobileBasePerfMode()
                ? this.resolveGeometryCache()
                : this.resolveGeometryFromDom();

            if (!geometry) {
                return;
            }

            this.isPointerInside = true;

            const deltaX = clientX - geometry.anchorCenterX;
            const deltaY = clientY - geometry.anchorCenterY;
            const distance = Math.hypot(deltaX, deltaY);

            if (distance <= 0.001) {
                this.positionPuckAtDefault();
                return;
            }

            const projectedX = geometry.anchorCenterX + (deltaX / distance) * geometry.radius;
            const projectedY = geometry.anchorCenterY + (deltaY / distance) * geometry.radius;
            const projectedAngle = Math.atan2(
                projectedY - geometry.anchorCenterY,
                projectedX - geometry.anchorCenterX,
            );
            const orbitAngleDeg = (projectedAngle * 180) / Math.PI + 90;

            if (this.shouldUseMobileBasePerfMode() && this.isTouchPointerActive) {
                this.setOrbitAngle(orbitAngleDeg);
                return;
            }

            // Keep touch-hover sector activation in sync with the pointer immediately,
            // without waiting for orbit easing frames.
            this.syncProjectedModeWithOrbitAngle(orbitAngleDeg);
            this.setOrbitAngle(orbitAngleDeg);
        },
        openMode(mode, event = null) {
            if (this.activeTransitionDirection !== null) {
                return;
            }

            if (this.suppressNextOpenMode === mode) {
                this.suppressNextOpenMode = null;
                return;
            }

            this.suppressNextOpenMode = null;

            const isAvailable = this.isModeAvailable(mode);

            if (this.requiresArmedActivation()) {
                if (this.touchReleaseArmedMode && this.touchReleaseArmedMode !== mode) {
                    this.armMode(mode);
                    this.touchReleaseArmedMode = mode;
                    return;
                }

                if (this.armedMode !== mode) {
                    this.armMode(mode);
                    this.touchReleaseArmedMode = mode;
                    return;
                }
            }

            if (!isAvailable) {
                return;
            }

            this.clearArmedMode();
            this.touchReleaseArmedMode = null;

            const modeViewMap = {
                tilawa: 'quran-app-tilawa',
                hifth: 'quran-app-hifth',
                tadabbur: 'quran-app-tadabbur',
            };
            const targetView = modeViewMap[mode] ?? 'quran-app-gate';

            if (this.isFastUiMode()) {
                this.$viewNav(targetView);
                return;
            }

            this.isLaunchTransitioning = true;
            this.activeTransitionDirection = 'forward';
            this.launchMode = mode;
            const isMobileBasePerfMode = this.shouldUseMobileBasePerfMode();
            const didApplyLaunchState = !isMobileBasePerfMode
                ? this.applyTransitionState(mode, event)
                : false;

            if (didApplyLaunchState && !isMobileBasePerfMode) {
                const shellElement = this.quranShellElement();

                if (shellElement) {
                    shellElement.classList.add('quran-app-shell--reader-launching');
                }
            }

            if (isMobileBasePerfMode) {
                const shellElement = this.quranShellElement();

                if (shellElement) {
                    shellElement.setAttribute('data-quran-launch-mode', mode);
                    shellElement.classList.add('quran-app-shell--reader-launching-base');
                }
            }

            this.launchNavigateTimeoutId = window.setTimeout(
                () => {
                    this.launchNavigateTimeoutId = null;
                    this.$viewNav(targetView);

                    const shellElement = this.quranShellElement();

                    if (shellElement && !isMobileBasePerfMode) {
                        shellElement.classList.add('quran-app-shell--reader-entering');
                    }
                },
                isMobileBasePerfMode
                    ? baseLaunchNavigateDelayMs
                    : didApplyLaunchState
                      ? launchNavigateDelayMs
                      : 0,
            );

            this.launchCleanupTimeoutId = window.setTimeout(
                () => {
                    this.clearLaunchTransitionState();
                },
                isMobileBasePerfMode ? baseLaunchCleanupDelayMs : launchCleanupDelayMs,
            );
        },
    }));
});
