document.addEventListener('alpine:init', () => {
    const visualEnhancementsSettingKey = 'enable_visual_enhancements';
    const athkarGateBackgroundPreviewEventName = 'athkar-gate-background-preview';

    window.Alpine.data('athkarAppGate', () => ({
        isFastUiMode: window.__APP_BROWSER_TEST_FAST_UI === true,
        hoverSide: null,
        activeSide: null,
        isVisualEnhancementsEnabled: true,
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
        init() {
            if (!this.isFastUiMode) {
                this.syncVisualEnhancementsSetting();
                this.dispatchBackgroundPreview();
                window.addEventListener('control-panel-updated', (event) => {
                    this.syncVisualEnhancementsSetting(event?.detail?.controlPanel ?? null);
                    this.dispatchBackgroundPreview();
                });

                return;
            }

            this.spillTransitionMs = 0;
            this.spillShowDelayMs = 0;
            this.spillShowDurationMs = 0;
            this.spillHideDurationMs = 0;
            this.spillIntroDelayMs = 0;
            this.pingDuration = 0;
            this.pingDelay = 0;
            this.isSpillReady = true;
            this.syncVisualEnhancementsSetting();
            this.dispatchBackgroundPreview();
            window.addEventListener('control-panel-updated', (event) => {
                this.syncVisualEnhancementsSetting(event?.detail?.controlPanel ?? null);
                this.dispatchBackgroundPreview();
            });
        },
        normalizeBooleanSettingValue(value, fallback = true) {
            if (typeof value === 'boolean') {
                return value;
            }

            if (value === 1 || value === '1') {
                return true;
            }

            if (value === 0 || value === '0') {
                return false;
            }

            if (value === undefined || value === null || value === '') {
                return Boolean(fallback);
            }

            const normalizedValue = String(value).trim().toLowerCase();

            if (
                normalizedValue === 'true' ||
                normalizedValue === 'yes' ||
                normalizedValue === 'on'
            ) {
                return true;
            }

            if (
                normalizedValue === 'false' ||
                normalizedValue === 'no' ||
                normalizedValue === 'off'
            ) {
                return false;
            }

            return Boolean(fallback);
        },
        resolveVisualEnhancementsSetting(settings = null) {
            if (
                settings &&
                typeof settings === 'object' &&
                Object.prototype.hasOwnProperty.call(settings, visualEnhancementsSettingKey)
            ) {
                return this.normalizeBooleanSettingValue(
                    settings[visualEnhancementsSettingKey],
                    true,
                );
            }

            const storedSettings = window.getAthkarSettingsFromStorage?.() ?? {};

            return this.normalizeBooleanSettingValue(
                storedSettings?.[visualEnhancementsSettingKey],
                true,
            );
        },
        syncVisualEnhancementsSetting(settings = null) {
            this.isVisualEnhancementsEnabled = this.resolveVisualEnhancementsSetting(settings);
        },
        dispatchBackgroundPreview() {
            const previewSide =
                this.isVisualEnhancementsEnabled &&
                (this.activeSide === 'morning' || this.activeSide === 'night')
                    ? this.activeSide
                    : null;

            window.dispatchEvent(
                new CustomEvent(athkarGateBackgroundPreviewEventName, {
                    detail: {
                        side: previewSide,
                        isVisualEnhancementsEnabled: this.isVisualEnhancementsEnabled,
                    },
                }),
            );
        },
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

            if (!this.isEnhanced) {
                this.spillOpacity = 0;
                this.isSpillReady = false;
            }
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
                this.dispatchBackgroundPreview();
                return;
            }

            if (side === 'night') {
                this.animateSplit(60);
                this.dispatchBackgroundPreview();
                return;
            }

            this.animateSplit(50);
            this.dispatchBackgroundPreview();
        },
        deactivateSide() {
            if (!this.activeSide) {
                return;
            }

            this.activeSide = null;
            this.hoverSide = null;
            this.animateSplit(50);
            this.dispatchBackgroundPreview();
        },
        handleOutsideActivation() {
            if (this.hasTouchInput()) {
                this.deactivateSide();
                return;
            }

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
        hasTouchInput() {
            return Boolean(this.$store?.bp?.hasTouch);
        },
        syncSpillState(isActive) {
            if (!this.isEnhanced) {
                this.spillOpacity = 0;
                this.setScrollLock(Boolean(isActive));
                this.lastSpillState = isActive;

                return;
            }

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

            if (this.isFastUiMode) {
                this.spillTransitionMs = 0;
                this.spillOpacity = isActive ? this.spillTargetOpacity : 0;
                this.setScrollLock(isActive);
                return;
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
            if (this.isFastUiMode) {
                this.isPinging = false;
                return;
            }

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
            const side = this.sideForMode(mode);

            if (this.hasTouchInput()) {
                if (this.activeSide === side) {
                    this.deactivateSide();
                    this.$dispatch('athkar-gate-open', { mode });
                    return;
                }

                this.activateSide(side);
                return;
            }

            if (this.isEnhanced) {
                this.$dispatch('athkar-gate-open', { mode });
                return;
            }

            if (this.activeSide === side) {
                this.deactivateSide();
                this.$dispatch('athkar-gate-open', { mode });
                return;
            }

            this.activateSide(side);
        },
    }));
});
