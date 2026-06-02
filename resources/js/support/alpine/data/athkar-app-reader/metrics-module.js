export const createMetricsModule = (deps) => {
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
        isSlideInRenderWindow(index) {
            const distance = Math.abs(Number(index) - this.activeIndex);

            return Number.isFinite(distance) && distance <= this.renderWindowRadius;
        },

        getModeList(mode) {
            if (mode !== 'sabah' && mode !== 'masaa') {
                return [];
            }

            const cache = this._modeListCache[mode];

            if (cache.athkarVersion === this._athkarVersion) {
                return cache.list;
            }

            const list = this.athkarFor(mode);
            cache.athkarVersion = this._athkarVersion;
            cache.list = list;

            return list;
        },

        markProgressDirty({ completionChanged = false } = {}) {
            this._progressRevision += 1;
            this._progressStatsCache.key = null;
            this._progressStatsCache.value = null;

            if (completionChanged) {
                this._completionRevision += 1;
                this._navGradientCache.key = null;
                this._navGradientCache.value = null;
            }
        },

        invalidateModeMetrics(mode = null) {
            if (mode === null) {
                this._modeMetrics.sabah = null;
                this._modeMetrics.masaa = null;
                this.markProgressDirty({ completionChanged: true });

                return;
            }

            if (mode !== 'sabah' && mode !== 'masaa') {
                return;
            }

            this._modeMetrics[mode] = null;
            this.markProgressDirty({ completionChanged: true });
        },

        ensureModeMetrics(mode) {
            if (mode !== 'sabah' && mode !== 'masaa') {
                return null;
            }

            if (this._modeMetrics[mode]) {
                return this._modeMetrics[mode];
            }

            const list = this.athkarFor(mode);
            const counts = Array.isArray(this.progress?.[mode]?.counts)
                ? this.progress[mode].counts
                : [];
            let totalRequiredCount = 0;
            let totalCompletedCount = 0;
            let totalRequiredLetters = 0;
            let totalCompletedLetters = 0;
            let firstIncomplete = -1;

            list.forEach((item, index) => {
                const requiredCountSeed = Number(item?.count ?? 1);
                const completedCountSeed = Number(counts[index] ?? 0);
                const requiredCount =
                    Number.isFinite(requiredCountSeed) && requiredCountSeed > 0
                        ? requiredCountSeed
                        : 0;
                const completedCount =
                    Number.isFinite(completedCountSeed) && completedCountSeed > 0
                        ? completedCountSeed
                        : 0;

                totalRequiredCount += requiredCount;
                totalCompletedCount += completedCount;

                const letters = this._cachedLetterCount(item?.text);
                totalRequiredLetters += letters * requiredCount;
                totalCompletedLetters += letters * Math.min(completedCount, requiredCount);

                if (firstIncomplete === -1 && requiredCount > 0 && completedCount < requiredCount) {
                    firstIncomplete = index;
                }
            });

            this._modeMetrics[mode] = {
                totalRequiredCount,
                totalCompletedCount,
                totalRequiredLetters,
                totalCompletedLetters,
                firstIncomplete,
            };

            return this._modeMetrics[mode];
        },

        updateModeMetricsForCountChange(mode, index, previousValue, nextValue, requiredCount) {
            const metrics = this.ensureModeMetrics(mode);

            if (!metrics) {
                return;
            }

            const previousCount =
                Number.isFinite(previousValue) && previousValue > 0 ? previousValue : 0;
            const nextCount = Number.isFinite(nextValue) && nextValue > 0 ? nextValue : 0;
            const required =
                Number.isFinite(requiredCount) && requiredCount > 0 ? requiredCount : 0;
            const deltaCount = nextCount - previousCount;

            if (deltaCount === 0) {
                return;
            }

            metrics.totalCompletedCount += deltaCount;

            const list = this.getModeList(mode);
            const item = list[index];
            const letters = this._cachedLetterCount(item?.text);
            const previousLettersCount = Math.min(previousCount, required);
            const nextLettersCount = Math.min(nextCount, required);

            metrics.totalCompletedLetters += letters * (nextLettersCount - previousLettersCount);

            const wasIncomplete = previousCount < required;
            const isIncomplete = nextCount < required;

            if (wasIncomplete && !isIncomplete && metrics.firstIncomplete === index) {
                const counts = Array.isArray(this.progress?.[mode]?.counts)
                    ? this.progress[mode].counts
                    : [];
                let nextIncomplete = -1;

                for (let cursor = index + 1; cursor < list.length; cursor += 1) {
                    const requiredSeed = Number(list[cursor]?.count ?? 1);
                    const requiredAtCursor =
                        Number.isFinite(requiredSeed) && requiredSeed > 0 ? requiredSeed : 0;
                    const completedSeed = Number(counts[cursor] ?? 0);
                    const completedAtCursor =
                        Number.isFinite(completedSeed) && completedSeed > 0 ? completedSeed : 0;

                    if (requiredAtCursor > 0 && completedAtCursor < requiredAtCursor) {
                        nextIncomplete = cursor;
                        break;
                    }
                }

                metrics.firstIncomplete = nextIncomplete;
            } else if (!wasIncomplete && isIncomplete) {
                if (metrics.firstIncomplete === -1 || index < metrics.firstIncomplete) {
                    metrics.firstIncomplete = index;
                }
            }
        },

        resolveProgressStats() {
            const mode = this.activeMode;
            const activeList = Array.isArray(this.activeList) ? this.activeList : [];

            if (mode !== 'sabah' && mode !== 'masaa') {
                return {
                    totalRequiredCount: 0,
                    totalCompletedCount: 0,
                    totalRequiredLetters: 0,
                    totalCompletedLetters: 0,
                    totalRemainingLetters: 0,
                    slideProgressPercent: 0,
                    maxNavigableIndex: 0,
                };
            }

            const metrics = this.ensureModeMetrics(mode);
            const shouldPreventSwitching = this.shouldPreventSwitching();
            const cacheKey = `${mode}:${this._athkarVersion}:${this._progressRevision}:${shouldPreventSwitching ? 1 : 0}`;

            if (this._progressStatsCache.key === cacheKey && this._progressStatsCache.value) {
                return this._progressStatsCache.value;
            }

            if (!activeList.length) {
                const emptyStats = {
                    totalRequiredCount: 0,
                    totalCompletedCount: 0,
                    totalRequiredLetters: 0,
                    totalCompletedLetters: 0,
                    totalRemainingLetters: 0,
                    slideProgressPercent: 0,
                    maxNavigableIndex: 0,
                };

                this._progressStatsCache.key = cacheKey;
                this._progressStatsCache.value = emptyStats;

                return emptyStats;
            }

            const maxNavigableIndex = shouldPreventSwitching
                ? metrics?.firstIncomplete === -1
                    ? activeList.length - 1
                    : (metrics?.firstIncomplete ?? 0)
                : activeList.length - 1;
            const totalRequiredCount = metrics?.totalRequiredCount ?? 0;
            const totalCompletedCount = metrics?.totalCompletedCount ?? 0;
            const totalRequiredLetters = metrics?.totalRequiredLetters ?? 0;
            const totalCompletedLetters = metrics?.totalCompletedLetters ?? 0;
            const totalRemainingLetters = Math.max(totalRequiredLetters - totalCompletedLetters, 0);
            const slideProgressPercent = totalRequiredLetters
                ? Math.min(
                      100,
                      Math.max(
                          0,
                          Math.round(
                              (Math.min(totalCompletedLetters, totalRequiredLetters) /
                                  totalRequiredLetters) *
                                  100,
                          ),
                      ),
                  )
                : 0;

            const stats = {
                totalRequiredCount,
                totalCompletedCount,
                totalRequiredLetters,
                totalCompletedLetters,
                totalRemainingLetters,
                slideProgressPercent,
                maxNavigableIndex,
            };

            this._progressStatsCache.key = cacheKey;
            this._progressStatsCache.value = stats;

            return stats;
        },

        get activeList() {
            const mode = this.activeMode;

            if (mode !== 'sabah' && mode !== 'masaa') {
                return [];
            }

            if (
                this._activeListCache.mode === mode &&
                this._activeListCache.athkarVersion === this._athkarVersion
            ) {
                return this._activeListCache.list;
            }

            const list = this.getModeList(mode);

            this._activeListCache.mode = mode;
            this._activeListCache.athkarVersion = this._athkarVersion;
            this._activeListCache.list = list;

            return list;
        },

        get activeIndex() {
            const mode = this.activeMode;

            if (mode !== 'sabah' && mode !== 'masaa') {
                return 0;
            }

            const index = Number(this.progress?.[mode]?.index ?? 0);

            if (!Number.isFinite(index) || index < 0) {
                return 0;
            }

            return Math.trunc(index);
        },

        get totalRequiredCount() {
            return resolveProgressStatsSafely(this).totalRequiredCount;
        },

        get totalCompletedCount() {
            return resolveProgressStatsSafely(this).totalCompletedCount;
        },

        textLetterCount(text) {
            const normalized = String(text ?? '');

            if (!normalized) {
                return 0;
            }

            try {
                const letters = normalized.match(/\p{L}/gu);

                return letters ? letters.length : 0;
            } catch (_) {
                const stripped = normalized.replace(/\s+/gu, '');

                return stripped ? Array.from(stripped).length : 0;
            }
        },

        _cachedLetterCount(text) {
            const normalized = String(text ?? '');

            if (!normalized) {
                return 0;
            }

            if (this._letterCountCache.has(normalized)) {
                return this._letterCountCache.get(normalized);
            }

            const count = this.textLetterCount(normalized);
            this._letterCountCache.set(normalized, count);

            return count;
        },

        get totalRequiredLetters() {
            return resolveProgressStatsSafely(this).totalRequiredLetters;
        },

        get totalCompletedLetters() {
            return resolveProgressStatsSafely(this).totalCompletedLetters;
        },

        get totalRemainingLetters() {
            return resolveProgressStatsSafely(this).totalRemainingLetters;
        },

        get slideProgressPercent() {
            return resolveProgressStatsSafely(this).slideProgressPercent;
        },

        get maxNavigableIndex() {
            return resolveProgressStatsSafely(this).maxNavigableIndex;
        },

        settingValue(name, fallback) {
            const value = this.settings?.[name];

            if (typeof value === 'boolean') {
                return value;
            }

            if (value === 1 || value === '1') {
                return true;
            }

            if (value === 0 || value === '0') {
                return false;
            }

            return fallback;
        },

        shouldPreventSwitching() {
            return this.settingValue('does_prevent_switching_athkar_until_completion', true);
        },

        shouldSkipGuidancePanels() {
            return this.settingValue(skipGuidancePanelsSettingKey, false);
        },

        shouldEnableVisualEnhancements() {
            return this.settingValue(doesEnableVisualEnhancementsKey, true);
        },

        shouldExitReaderAfterForwardSwipe() {
            if (this.shouldPreventSwitching()) {
                return false;
            }

            if (!this.activeMode || !this.activeList.length) {
                return false;
            }

            if (this.activeIndex < this.activeList.length - 1) {
                return false;
            }

            return this.isAllComplete() || this.isModeComplete(this.activeMode);
        },

        get navPreviewIndex() {
            const nav = this.nav ?? {};

            return nav.isActive ? nav.dragIndex : nav.hoverIndex;
        },

        navIsRtl() {
            const track = this.$refs?.athkarNav;

            if (!track || !window.getComputedStyle) {
                return true;
            }

            return window.getComputedStyle(track).direction === 'rtl';
        },

        segmentWidthPercent() {
            if (!this.activeList.length) {
                return 100;
            }

            return 100 / this.activeList.length;
        },

        segmentLeftPercent(index) {
            const segment = this.segmentWidthPercent();

            if (this.navIsRtl()) {
                return `${100 - segment * (index + 1)}%`;
            }

            return `${segment * index}%`;
        },

        segmentCenterPercent(index) {
            const segment = this.segmentWidthPercent();

            if (this.navIsRtl()) {
                return `${100 - segment * (index + 0.5)}%`;
            }

            return `${segment * (index + 0.5)}%`;
        },

        get navGradient() {
            const activeList = Array.isArray(this.activeList) ? this.activeList : [];

            if (!activeList.length) {
                return 'linear-gradient(90deg, var(--athkar-nav-pending) 0% 100%)';
            }

            const mode = this.activeMode;
            const counts = Array.isArray(this.progress?.[mode]?.counts)
                ? this.progress[mode].counts
                : [];
            const maxNavigableIndex = Number(this.maxNavigableIndex ?? 0);
            const direction = typeof this.navIsRtl === 'function' && this.navIsRtl() ? 270 : 90;
            const cacheKey = `${mode}:${this._athkarVersion}:${this._completionRevision}:${maxNavigableIndex}:${direction}`;

            if (this._navGradientCache.key === cacheKey && this._navGradientCache.value) {
                return this._navGradientCache.value;
            }

            const segment = 100 / activeList.length;
            const stops = activeList.map((item, index) => {
                const start = (index * segment).toFixed(4);
                const end = ((index + 1) * segment).toFixed(4);
                let color = 'var(--athkar-nav-pending)';

                const isItemComplete =
                    typeof this.isItemComplete === 'function'
                        ? this.isItemComplete(index)
                        : Number(counts[index] ?? 0) >= Number(item?.count ?? 1);

                if (isItemComplete) {
                    color = 'var(--athkar-nav-complete)';
                } else if (index <= maxNavigableIndex) {
                    color = 'var(--athkar-nav-available)';
                }

                return `${color} ${start}% ${end}%`;
            });

            const gradient = `linear-gradient(${direction}deg, ${stops.join(', ')})`;

            this._navGradientCache.key = cacheKey;
            this._navGradientCache.value = gradient;

            return gradient;
        },

        resetNavState() {
            this.nav.isActive = false;
            this.nav.hoverIndex = null;
            this.nav.dragIndex = null;
            this.nav.pointerId = null;
            this.nav.hasInteracted = false;
            this.nav.isHovering = false;
            this.nav.suppressUntil = 0;
        },
    };
};
