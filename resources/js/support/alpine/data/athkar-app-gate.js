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
});
