document.addEventListener('alpine:init', () => {
    window.Alpine.data('quranAppGate', () => ({
        modeAvailability: Object.freeze({
            tilawa: true,
            hifth: false,
            tadabbur: false,
        }),
        projectedMode: null,
        pinnedMode: null,
        isPointerInside: false,
        isModePinned: false,
        orbitAngleDeg: 0,
        orbitRenderAngleDeg: 0,
        orbitTargetAngleDeg: 0,
        touchPointerId: null,
        activeTouchIdentifier: null,
        isTouchPointerActive: false,
        orbitAnimationFrameId: null,
        orbitLastFrameAt: 0,
        init() {
            this.$nextTick(() => {
                this.positionPuckAtDefault();
            });
        },
        hasTouchInput() {
            return Boolean(this.$store?.bp?.hasTouch);
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
            return (this.pinnedMode ?? this.projectedMode) === mode;
        },
        isModeAvailable(mode) {
            return Boolean(this.modeAvailability?.[mode] ?? false);
        },
        isModeLocked(mode) {
            return !this.isModeAvailable(mode);
        },
        currentMode() {
            return this.pinnedMode ?? this.projectedMode;
        },
        resolveModeFromOrbitAngleDeg(orbitAngleDeg) {
            const shellElement = this.$refs?.shell;
            const anchorCircle = this.$refs?.anchorCircle;

            if (!shellElement || !anchorCircle) {
                return 'tilawa';
            }

            const shellRect = shellElement.getBoundingClientRect();
            const anchorRect = anchorCircle.getBoundingClientRect();

            if (!shellRect.width || !shellRect.height || !anchorRect.width || !anchorRect.height) {
                return 'tilawa';
            }

            const anchorCenterX = anchorRect.left + anchorRect.width / 2;
            const anchorCenterY = anchorRect.top + anchorRect.height / 2;
            const projectedAngle = ((orbitAngleDeg - 90) * Math.PI) / 180;

            return (
                this.resolveModeFromAngle(
                    projectedAngle,
                    anchorCenterX,
                    anchorCenterY,
                    shellRect,
                ) ?? 'tilawa'
            );
        },
        syncProjectedModeWithOrbitAngle(orbitAngleDeg = this.orbitRenderAngleDeg) {
            this.projectedMode = this.resolveModeFromOrbitAngleDeg(orbitAngleDeg);
        },
        setOrbitAngle(targetAngleDeg) {
            if (!Number.isFinite(targetAngleDeg)) {
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
        },
        handlePointerLeave() {
            this.isPointerInside = false;

            if (!this.isModePinned) {
                this.positionPuckAtDefault();
            }
        },
        handlePointerDown(event) {
            if (event.pointerType === 'touch' && this.hasTouchInput()) {
                return;
            }

            if (event.pointerType !== 'touch') {
                return;
            }

            this.touchPointerId = event.pointerId;
            this.isTouchPointerActive = true;
            this.isPointerInside = true;

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
            if (event.pointerType === 'touch' && this.hasTouchInput()) {
                return;
            }

            if (event.pointerType !== 'touch') {
                return;
            }

            if (this.touchPointerId !== null && event.pointerId !== this.touchPointerId) {
                return;
            }

            this.touchPointerId = null;
            this.isTouchPointerActive = false;
            this.isPointerInside = false;

            if (this.$refs?.shell?.releasePointerCapture) {
                try {
                    this.$refs.shell.releasePointerCapture(event.pointerId);
                } catch {
                    // No-op: pointer capture can already be released.
                }
            }

            if (!this.isModePinned) {
                this.positionPuckAtDefault();
            }
        },
        positionPuckAtDefault() {
            this.setOrbitAngle(0);
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

            const touch =
                this.resolveActiveTouch(event.changedTouches) ??
                this.resolveActiveTouch(event.touches);

            if (!touch) {
                return;
            }

            this.activeTouchIdentifier = touch.identifier;
            this.isTouchPointerActive = true;
            this.isPointerInside = true;
            this.updateOrbitFromClientPoint(touch.clientX, touch.clientY);
        },
        handleTouchMove(event) {
            if (!this.hasTouchInput() || !this.isTouchPointerActive) {
                return;
            }

            const touch =
                this.resolveActiveTouch(event.touches) ??
                this.resolveActiveTouch(event.changedTouches);

            if (!touch) {
                return;
            }

            this.updateOrbitFromClientPoint(touch.clientX, touch.clientY);
        },
        handleTouchEnd(event) {
            if (!this.hasTouchInput()) {
                return;
            }

            const touch = this.resolveActiveTouch(event.changedTouches);

            if (!touch && this.isTouchPointerActive) {
                return;
            }

            this.activeTouchIdentifier = null;
            this.isTouchPointerActive = false;
            this.isPointerInside = false;

            if (!this.isModePinned) {
                this.positionPuckAtDefault();
            }
        },
        handlePointerMove(event) {
            if (event.pointerType === 'touch' && this.hasTouchInput()) {
                return;
            }

            if (
                event.pointerType === 'touch' &&
                (!this.isTouchPointerActive ||
                    (this.touchPointerId !== null && event.pointerId !== this.touchPointerId))
            ) {
                return;
            }

            this.updateOrbitFromClientPoint(event.clientX, event.clientY);
        },
        updateOrbitFromClientPoint(clientX, clientY) {
            const shellElement = this.$refs?.shell;
            const anchorCircle = this.$refs?.anchorCircle;

            if (!shellElement || !anchorCircle) {
                return;
            }

            this.isPointerInside = true;

            const shellRect = shellElement.getBoundingClientRect();
            const anchorRect = anchorCircle.getBoundingClientRect();
            const anchorCenterX = anchorRect.left + anchorRect.width / 2;
            const anchorCenterY = anchorRect.top + anchorRect.height / 2;
            const radius = anchorRect.width / 2;
            const deltaX = clientX - anchorCenterX;
            const deltaY = clientY - anchorCenterY;
            const distance = Math.hypot(deltaX, deltaY);

            if (distance <= 0.001) {
                this.positionPuckAtDefault();
                return;
            }

            const projectedX = anchorCenterX + (deltaX / distance) * radius;
            const projectedY = anchorCenterY + (deltaY / distance) * radius;
            const projectedAngle = Math.atan2(
                projectedY - anchorCenterY,
                projectedX - anchorCenterX,
            );

            this.setOrbitAngle((projectedAngle * 180) / Math.PI + 90);
        },
        openMode(mode) {
            if (!this.isModeAvailable(mode)) {
                return;
            }

            const modeViewMap = {
                tilawa: 'quran-app-tilawa',
                hifth: 'quran-app-hifth',
                tadabbur: 'quran-app-tadabbur',
            };
            const targetView = modeViewMap[mode] ?? 'quran-app-gate';

            this.$viewNav(targetView);
        },
    }));
});
