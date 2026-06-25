import { resolveEffectiveSettings } from '../athkar-app-overrides';

document.addEventListener('alpine:init', () => {
    const visualEnhancementsSettingKey = 'enable_visual_enhancements';

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
                window.addEventListener('control-panel-updated', (event) => {
                    this.syncVisualEnhancementsSetting(event?.detail?.controlPanel ?? null);
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
            window.addEventListener('control-panel-updated', (event) => {
                this.syncVisualEnhancementsSetting(event?.detail?.controlPanel ?? null);
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
            const defaults =
                settings && typeof settings === 'object' && !Array.isArray(settings)
                    ? settings
                    : (window.athkarSettingsDefaults ?? {});
            const effectiveSettings = resolveEffectiveSettings(defaults);

            if (
                Object.prototype.hasOwnProperty.call(
                    effectiveSettings,
                    visualEnhancementsSettingKey,
                )
            ) {
                return this.normalizeBooleanSettingValue(
                    effectiveSettings[visualEnhancementsSettingKey],
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
        effectiveBackgroundSide() {
            return this.activeSide ?? this.hoverSide;
        },
        gateBackgroundMorningOpacity() {
            const side = this.effectiveBackgroundSide();

            if (side === 'morning') {
                return 1;
            }

            if (side === 'night') {
                return 0;
            }

            return this.$store.colorScheme.isDarkModeOn ? 0 : 1;
        },
        gateBackgroundNightOpacity() {
            const side = this.effectiveBackgroundSide();

            if (side === 'night') {
                return 1;
            }

            if (side === 'morning') {
                return 0;
            }

            return this.$store.colorScheme.isDarkModeOn ? 1 : 0;
        },
        setScrollLock(locked) {
            document.documentElement.style.overflow = locked ? 'hidden' : '';
            document.body.style.overflow = locked ? 'hidden' : '';
        },
        syncPerfProfile() {
            this.$store.bp.current;
            // ponytail: the rich "panes/spill" presentation is dropped entirely — it was too costly even at sm+ with VE on. The gate keeps its own lightweight blurred backdrop locally, while the only VE-on extra left here is the ripple, gated separately via the `is-ve-on` class.
            const nextEnhanced = false;

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

            // ponytail: the ripple is a visual-enhancements-only flourish; skip queueing it entirely when VE is off so no work happens (the CSS also gates it via .is-ve-on).
            if (!this.isVisualEnhancementsEnabled) {
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
