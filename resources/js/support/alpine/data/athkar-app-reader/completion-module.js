export const createCompletionModule = (deps) => {
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
            const previousValue = Number(this.progress[this.activeMode].counts[index] ?? 0);

            if (nextValue === previousValue) {
                return;
            }

            this.progress[this.activeMode].counts[index] = nextValue;
            this.updateModeMetricsForCountChange(
                this.activeMode,
                index,
                previousValue,
                nextValue,
                maxCount,
            );
            this.markProgressDirty({
                completionChanged:
                    (previousValue < maxCount && nextValue >= maxCount) ||
                    (previousValue >= maxCount && nextValue < maxCount),
            });
            this.persistProgress();
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

        resetMaintenanceTapTracking() {
            this.maintenance.sequentialTapCount = 0;
            this.maintenance.mode = null;
            this.maintenance.index = null;
        },

        clearRapidTapReleaseTimer() {
            if (this.rapidTap.releaseTimer !== null) {
                clearTimeout(this.rapidTap.releaseTimer);
                this.rapidTap.releaseTimer = null;
            }
        },

        resetRapidTapMode() {
            const wasRapidTapMode = this.rapidTap.isActive;

            this.clearRapidTapReleaseTimer();
            this.rapidTap.isActive = false;
            this.rapidTap.lastTapAt = 0;
            this.rapidTap.burstCount = 0;

            if (wasRapidTapMode) {
                this.$nextTick(() => this.setupTextShimmer(null, { immediate: false }));
            }
        },

        shouldUseRapidTapSafeMode(requiredCount) {
            return this.rapidTap.isActive && requiredCount >= this.rapidTap.minimumRequiredCount;
        },

        trackRapidTapBurst(requiredCount) {
            if (requiredCount < this.rapidTap.minimumRequiredCount) {
                this.resetRapidTapMode();

                return;
            }

            const now = performance.now();
            const elapsed = now - this.rapidTap.lastTapAt;

            if (elapsed > 0 && elapsed <= this.rapidTap.windowMs) {
                this.rapidTap.burstCount += 1;
            } else {
                this.rapidTap.burstCount = 1;
            }

            this.rapidTap.lastTapAt = now;

            if (!this.rapidTap.isActive && this.rapidTap.burstCount >= this.rapidTap.threshold) {
                this.rapidTap.isActive = true;
                this.stopTextShimmer();
            }

            if (!this.rapidTap.isActive) {
                return;
            }

            this.clearRapidTapReleaseTimer();
            this.rapidTap.releaseTimer = setTimeout(() => {
                this.rapidTap.releaseTimer = null;
                this.rapidTap.isActive = false;
                this.rapidTap.burstCount = 0;
                this.setupTextShimmer(null, { immediate: false });
            }, this.rapidTap.holdMs);
        },

        trackMaintenanceTap(index, requiredCount) {
            if (
                !this.activeMode ||
                this.rapidTap.isActive ||
                requiredCount < this.maintenance.minimumRequiredCount
            ) {
                this.resetMaintenanceTapTracking();

                return;
            }

            const isSameTarget =
                this.maintenance.mode === this.activeMode && this.maintenance.index === index;

            this.maintenance.sequentialTapCount = isSameTarget
                ? this.maintenance.sequentialTapCount + 1
                : 1;
            this.maintenance.mode = this.activeMode;
            this.maintenance.index = index;

            if (this.maintenance.sequentialTapCount % this.maintenance.tapInterval !== 0) {
                return;
            }

            window.dispatchEvent(new CustomEvent('athkar-reader-maintenance'));
            window.dispatchEvent(
                new CustomEvent('athkar-action-state-pulse', {
                    detail: {
                        durationMs: 34,
                    },
                }),
            );
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
            let didIncrementCount = false;
            const shouldAnimateTapFeedback = !this.shouldUseRapidTapSafeMode(required);

            if (current < required || allowOvercount) {
                const previousCount = current;
                const previousTotal = this.totalCompletedCount;
                const nextCount = current + 1;
                this.setCount(index, nextCount, { allowOvercount });
                didIncrementCount = true;
                if (shouldAnimateTapFeedback) {
                    this.triggerCountPulse(index, previousCount, nextCount);
                    this.triggerTotalPulse(previousTotal, previousTotal + 1);
                }

                if (shouldAnimateTapFeedback && required > 1) {
                    this.triggerTapPulse(index);
                }
            }

            if (didIncrementCount) {
                this.trackRapidTapBurst(required);
                this.trackMaintenanceTap(index, required);
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
            this.updateModeMetricsForCountChange(
                this.activeMode,
                index,
                current,
                required,
                required,
            );
            this.markProgressDirty({
                completionChanged: current < required,
            });
            this.persistProgress();
            this.triggerCountPulse(index, current, required);
            this.triggerTotalPulse(previousTotal, previousTotal + (required - current));

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
                this.triggerCountPulse(index, current, nextCount);
                this.triggerTotalPulse(previousTotal, previousTotal + 1);

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
                this.closeHint();
                this.startTopUiCompletionTransition(index, index + 1);

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

            if (this.shouldSkipGuidancePanels()) {
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
                        this.$viewNav('athkar-app-gate');
                        this.syncNativeVolumeNavigation();
                    }
                }, this.readerLeaveMs);

                this.syncNativeVolumeNavigation();

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
                this.syncNativeVolumeNavigation();
            }, this.completionVisibleMs);

            setTimeout(() => {
                if (!this.views[`athkar-app-gate`].isReaderVisible) {
                    this.activeMode = null;
                    this.$viewNav('athkar-app-gate');
                    this.syncNativeVolumeNavigation();
                }
            }, this.readerLeaveMs);

            this.syncNativeVolumeNavigation();
        },

        itemKey(item, index) {
            const itemId = item?.id ?? `index-${index}`;

            return `${this.activeMode ?? 'athkar'}-${itemId}`;
        },
    };
};
