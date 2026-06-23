document.addEventListener('alpine:init', () => {
    const modeViewMap = Object.freeze({
        istiham: 'sunna-istiham-app',
    });
    // ponytail: camera shift kept tiny and per-shape so motion reads as depth, not drift.
    const cameraEaseResponseMs = 90;
    const touchPanThresholdPx = 5;

    window.Alpine.data('sunnaAppGate', () => ({
        availability: Object.freeze({
            aamal: false,
            sayd: false,
            istiham: true,
        }),
        hoveredMode: null,
        armedMode: null,
        isPointerInside: false,
        camTargetX: 0,
        camTargetY: 0,
        camRenderX: 0,
        camRenderY: 0,
        cameraFrameId: null,
        cameraLastFrameAt: 0,
        isTouchActive: false,
        touchPointerId: null,
        touchStartX: null,
        touchStartY: null,
        touchDidPan: false,
        hasUserInteracted: false,
        _onSwitchView: null,
        init() {
            this._onSwitchView = (event) => {
                const nextView = String(event?.detail?.to ?? '');

                if (nextView !== 'sunna-gate') {
                    this.disarm();
                }

                if (
                    nextView === 'sunna-gate' ||
                    nextView === 'main-menu' ||
                    nextView.startsWith('sunna-')
                ) {
                    this.resetCamera();
                }
            };
            window.addEventListener('switch-view', this._onSwitchView);
        },
        destroy() {
            this.stopCameraAnimation();

            if (this._onSwitchView) {
                window.removeEventListener('switch-view', this._onSwitchView);
                this._onSwitchView = null;
            }
        },
        hasTouchInput() {
            const store = this.$store?.bp;

            if (typeof store?.isTouch === 'function') {
                return Boolean(store.isTouch() || store.hasTouch);
            }

            return Boolean(store?.hasTouch);
        },
        requiresArmedActivation() {
            return this.hasTouchInput();
        },
        isModeAvailable(mode) {
            return Boolean(this.availability?.[mode] ?? false);
        },
        isModeLocked(mode) {
            return !this.isModeAvailable(mode);
        },
        isModeActive(mode) {
            if (this.requiresArmedActivation()) {
                return this.armedMode === mode;
            }

            return this.hoveredMode === mode;
        },
        shouldShowEnterHint(mode) {
            return (
                this.requiresArmedActivation() &&
                this.armedMode === mode &&
                this.isModeAvailable(mode)
            );
        },
        hoverMode(mode) {
            if (this.requiresArmedActivation()) {
                return;
            }

            this.hoveredMode = mode;
        },
        unhoverMode(mode) {
            if (this.hoveredMode === mode) {
                this.hoveredMode = null;
            }
        },
        disarm() {
            this.armedMode = null;
            this.hoveredMode = null;
        },
        activate(mode, event = null) {
            // Touch pans are camera gestures, never activations.
            if (this.touchDidPan) {
                this.touchDidPan = false;
                return;
            }

            if (this.requiresArmedActivation() && this.armedMode !== mode) {
                this.armedMode = mode;
                return;
            }

            if (!this.isModeAvailable(mode)) {
                return;
            }

            const targetView = modeViewMap[mode];

            if (!targetView) {
                return;
            }

            this.disarm();
            this.$viewNav(targetView);
        },
        // ---- Camera ----------------------------------------------------------
        shellElement() {
            return this.$refs?.shell ?? null;
        },
        resetCamera() {
            this.camTargetX = 0;
            this.camTargetY = 0;
            this.startCameraAnimation();
        },
        updateCameraFromPoint(clientX, clientY) {
            const shell = this.shellElement();

            if (!shell) {
                return;
            }

            const rect = shell.getBoundingClientRect();

            if (rect.width <= 0 || rect.height <= 0) {
                return;
            }

            const normalizedX = ((clientX - rect.left) / rect.width) * 2 - 1;
            const normalizedY = ((clientY - rect.top) / rect.height) * 2 - 1;

            this.camTargetX = Math.max(-1, Math.min(1, normalizedX));
            this.camTargetY = Math.max(-1, Math.min(1, normalizedY));
            this.startCameraAnimation();
        },
        startCameraAnimation() {
            if (this.cameraFrameId !== null) {
                return;
            }

            this.cameraLastFrameAt = 0;
            this.cameraFrameId = requestAnimationFrame((timestamp) =>
                this.animateCameraFrame(timestamp),
            );
        },
        stopCameraAnimation() {
            if (this.cameraFrameId !== null) {
                cancelAnimationFrame(this.cameraFrameId);
                this.cameraFrameId = null;
            }

            this.cameraLastFrameAt = 0;
        },
        animateCameraFrame(timestamp) {
            if (this.cameraLastFrameAt === 0) {
                this.cameraLastFrameAt = timestamp;
            }

            const elapsedMs = Math.min(40, Math.max(8, timestamp - this.cameraLastFrameAt));
            this.cameraLastFrameAt = timestamp;

            const easing = 1 - Math.exp(-(elapsedMs / cameraEaseResponseMs));
            this.camRenderX += (this.camTargetX - this.camRenderX) * easing;
            this.camRenderY += (this.camTargetY - this.camRenderY) * easing;

            this.applyCameraVars();

            const settled =
                Math.abs(this.camTargetX - this.camRenderX) < 0.002 &&
                Math.abs(this.camTargetY - this.camRenderY) < 0.002;

            if (settled) {
                this.camRenderX = this.camTargetX;
                this.camRenderY = this.camTargetY;
                this.applyCameraVars();
                this.cameraFrameId = null;
                this.cameraLastFrameAt = 0;
                return;
            }

            this.cameraFrameId = requestAnimationFrame((nextTimestamp) =>
                this.animateCameraFrame(nextTimestamp),
            );
        },
        applyCameraVars() {
            const shell = this.shellElement();

            if (!shell) {
                return;
            }

            shell.style.setProperty('--cam-x', this.camRenderX.toFixed(4));
            shell.style.setProperty('--cam-y', this.camRenderY.toFixed(4));
        },
        // ---- Pointer / touch wiring -----------------------------------------
        handlePointerMove(event) {
            if (event.pointerType === 'touch') {
                return;
            }

            this.isPointerInside = true;
            this.updateCameraFromPoint(event.clientX, event.clientY);
        },
        handlePointerLeave() {
            this.isPointerInside = false;
            this.resetCamera();
        },
        handleTouchStart(event) {
            if (!this.hasTouchInput()) {
                return;
            }

            const touch = event.changedTouches?.[0] ?? event.touches?.[0] ?? null;

            if (!touch) {
                return;
            }

            this.isTouchActive = true;
            this.hasUserInteracted = true;
            this.touchPointerId = touch.identifier;
            this.touchStartX = touch.clientX;
            this.touchStartY = touch.clientY;
            this.touchDidPan = false;
            this.updateCameraFromPoint(touch.clientX, touch.clientY);
        },
        handleTouchMove(event) {
            if (!this.isTouchActive) {
                return;
            }

            const touch = this.resolveActiveTouch(event);

            if (!touch) {
                return;
            }

            if (
                !this.touchDidPan &&
                Math.hypot(touch.clientX - this.touchStartX, touch.clientY - this.touchStartY) >
                    touchPanThresholdPx
            ) {
                this.touchDidPan = true;
            }

            if (event.cancelable && this.touchDidPan) {
                event.preventDefault();
            }

            this.updateCameraFromPoint(touch.clientX, touch.clientY);
        },
        handleTouchEnd() {
            if (!this.isTouchActive) {
                return;
            }

            this.isTouchActive = false;
            this.touchPointerId = null;
            this.resetCamera();
        },
        resolveActiveTouch(event) {
            const list = event.touches?.length ? event.touches : event.changedTouches;

            if (!list?.length) {
                return null;
            }

            if (this.touchPointerId === null) {
                return list[0];
            }

            for (const touch of list) {
                if (touch.identifier === this.touchPointerId) {
                    return touch;
                }
            }

            return null;
        },
    }));
});
