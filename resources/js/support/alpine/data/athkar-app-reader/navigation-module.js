export const createNavigationModule = (deps) => {
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
            let preserveTopUiTransition = false;

            if (typeof index === 'object' && index !== null) {
                preserveTopUiTransition = Boolean(index.preserveTopUiTransition);
                index = index.index;
            }

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
                this.showMobileOvercountHint?.(nextIndex);
                return;
            }

            if (!preserveTopUiTransition) {
                this.resetTopUiTransition();
            }

            this.hideMobileOvercountHint?.();
            this.resetMaintenanceTapTracking();
            const previousPage = currentIndex + 1;
            this.progress[this.activeMode].index = nextIndex;
            this.progress[this.activeMode].activeId = this.activeList[nextIndex]?.id ?? null;
            this.persistProgress();
            const direction = nextIndex > currentIndex ? 'next' : 'prev';
            const nextPage = nextIndex + 1;

            this.triggerSlidePulse(direction);
            this.triggerPagePulse(direction, previousPage, nextPage);
            this.showMobileOvercountHint?.(nextIndex);
        },

        prev() {
            if (!this.activeMode) {
                return;
            }

            if (this.activeIndex <= 0) {
                if (!this.isNoticeVisible && this.views?.['athkar-app-gate']?.isReaderVisible) {
                    if (this.shouldSkipGuidancePanels()) {
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
    };
};
