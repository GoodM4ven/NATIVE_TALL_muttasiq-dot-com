export const createWirdAndHistoryNavigationAndManagerSyncModule = (deps) => {
    const {
        arabicHarakatPattern,
        arabicPresentationFormsPattern,
        athkarSettingsUserOverridesStorageKey,
        bookmarkHoldDelayMs,
        bookmarksStorageKey,
        cacheAssetResponse,
        clampPage,
        controlPanelSettingKeys,
        copiedHighlightVisibleDurationMs,
        copyPopoverVisibleDurationMs,
        currentDateKey,
        defaultArabicNumerals,
        defaultBasmallahBottomGapScale,
        defaultPagePayload,
        defaultWesternNumerals,
        ensureSupportLockLivewireMorphBridge,
        fetchJsonWithCache,
        fitCacheStorageKey,
        fitCacheStorageVersion,
        fitCacheViewportBucketSizePx,
        fitCalibrationReferencePage,
        fitDefaultProfile,
        fitResultCacheLimit,
        fitRobustWidthOutlierThreshold,
        fitRobustWidthQuantile,
        hasArabicPresentationForms,
        historyEntryHasPersistenceMeta,
        historyNavigationModalLifecycleSuppressionDurationMs,
        idleWarmupPauseOnHighFrequencyNavigationMs,
        idleWarmupPauseOnStandardNavigationMs,
        idleWarmupResumeDelayMs,
        lastPageStorageKey,
        managerRowRemoveAnimationDurationMs,
        managerRowReplaceAnimationDurationMs,
        managerRowUpdateAnimationDurationMs,
        mobileDoubleTapCopyWindowMs,
        mobileDoubleTapHoldDelayMs,
        modalCloseTransitionDelayMs,
        modalLifecycleSuppressionDurationMs,
        navigationBurstInputThresholdMs,
        navigationBurstSettleDelayMs,
        navigationHistoryLimit,
        navigationHistoryStorageKey,
        navigationRevealLockDurationMs,
        navigationSettleDelayMs,
        nextAnimationFrame,
        normalizeBookmarkEntry,
        normalizeBookmarks,
        normalizeDayOffsetDays,
        normalizeHistoryEntry,
        normalizeNavigationHistory,
        normalizeNumerals,
        normalizePayload,
        normalizeSupportUnlockState,
        normalizeTags,
        normalizeTextValue,
        openCacheSafely,
        openingSpreadFinalScaleMultiplier,
        pageCounterPulseDurationMs,
        pageFontLoadTimeoutMs,
        pageFontReadyRecoveryDelayMs,
        pageFontReadyTimeoutMs,
        postModalFitRevealSettleDelayMs,
        pruneNavigationHistory,
        quranPageGapAdjustMax,
        quranPageGapAdjustMin,
        quranPageGapAdjustMultiplierStep,
        quranPageGapAdjustStorageKey,
        quranPageScaleAdjustMax,
        quranPageScaleAdjustMin,
        quranPageScaleAdjustMultiplierStep,
        quranPageScaleAdjustStorageKey,
        quranPageYOffsetAdjustMax,
        quranPageYOffsetAdjustMin,
        quranPageYOffsetAdjustRemStep,
        quranPageYOffsetAdjustStorageKey,
        quranReaderDebugLogsEnabledByEnv,
        quranReaderDebugLogsToggleEventName,
        quranSearchStreamFrameDelimiter,
        readBookmarks,
        readLastPageNumber,
        readLocalStorage,
        readLocalStorageRaw,
        readNavigationHistory,
        readSupportUnlockState,
        readWirdDayOffsetDays,
        readerRevealDebugStorageKey,
        revealBlockedFailOpenDelayMs,
        shouldPersistFitCacheAcrossReloads,
        stripArabicHarakat,
        supportLockClosedOutlineIconSvg,
        supportLockLivewireMorphedEventName,
        supportUnlockModePermanent,
        supportUnlockModeWeekly,
        supportUnlockStorageKey,
        supportUnlockStorageVersion,
        supportUnlockWeeklyDurationMs,
        supportedHistorySources,
        surahQuickNavigatorHoldDelayMs,
        surahQuickNavigatorLastPage,
        swipeActivationThresholdPx,
        swipeRevealWatchdogDelayMs,
        uniqueLocalId,
        wait,
        wirdCompletionVisibleDurationMs,
        wirdDailyKhatmatTargetMax,
        wirdDayOffsetStorageKey,
        wirdFrequencyModeDaily,
        wirdFrequencyModeMonthly,
        wirdHoverShimmerDurationMs,
        wirdKhatmatTargetMin,
        wirdModeEntryPageInputTweenDurationMs,
        wirdMonthlyKhatmatTargetMax,
        wirdProgressStorageKey,
        wirdProgressStorageVersion,
        wirdRecordRetentionDays,
        wordClickSuppressionResetMs,
        wordPressDragThresholdPx,
        wordPressHoldDelayMs,
        writeBookmarks,
        writeLastPageNumber,
        writeLocalStorage,
        writeNavigationHistory,
        writeSupportUnlockState,
        writeWirdDayOffsetDays,
    } = deps;

    return {
        navigationSourceProfile(source = 'generic') {
            const normalizedSource = String(source ?? 'generic').trim();

            if (!normalizedSource) {
                return 'generic';
            }

            return normalizedSource;
        },

        wirdNavigationSourceProfile(source = 'generic') {
            return this.navigationSourceProfile(source);
        },

        shouldScheduleWirdRevealRecovery(source = 'generic') {
            const normalizedSource = this.wirdNavigationSourceProfile(source);

            return !normalizedSource.startsWith('slider');
        },

        async stepWird(direction = 'next', source = 'generic') {
            if (!this.wirdModeActive) {
                return;
            }

            const navigationRequestSerial = this._wirdNavigationRequestSerial + 1;
            this._wirdNavigationRequestSerial = navigationRequestSerial;
            this.clearWirdEntryRecovery();

            const record = this.ensureWirdDailyRecord();

            if (!record || typeof record !== 'object') {
                return;
            }

            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.normalizeIntegerFlag(record?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });
            const isNextDirection = direction === 'next';
            const sourceProfile = this.wirdNavigationSourceProfile(source);
            const pageStep = this.wirdStepForPage(this.pageNumber, record, {
                preferredStep: this.wirdActiveStepForNavigation(record),
            });

            if (record?.completed) {
                let browseStep = this.wirdBrowseStepValue(record);

                if (pageStep !== null) {
                    browseStep = pageStep;
                }

                if (isNextDirection && browseStep >= maxStep) {
                    await this.exitWirdMode({
                        restoreNormalPage: true,
                        reason: 'boundary-next',
                    });

                    return;
                }

                if (!isNextDirection && browseStep <= 0) {
                    await this.exitWirdMode({
                        restoreNormalPage: true,
                        reason: 'boundary-prev',
                    });

                    return;
                }

                const previousBrowseStep = browseStep;
                browseStep = isNextDirection ? browseStep + 1 : browseStep - 1;
                this.wirdBrowseStep = browseStep;
                const startAbsolutePage = Math.max(
                    1,
                    this.normalizeIntegerFlag(record?.startAbsolutePage, 1, { min: 1 }),
                );
                const targetPage = this.absolutePageToPageNumber(startAbsolutePage + browseStep);
                this.applyWirdNavigationVisualState(targetPage, browseStep, {
                    source: sourceProfile,
                    previousStep: previousBrowseStep,
                });

                await this.goToPage(targetPage, {
                    direction: isNextDirection ? 'next' : 'prev',
                    animate: true,
                    forceRefit: true,
                    source: `wird-${sourceProfile}`,
                });

                if (
                    navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                    !this.wirdModeActive
                ) {
                    return;
                }

                await this.ensureWirdEntryPageVisible(targetPage);

                if (
                    navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                    !this.wirdModeActive
                ) {
                    return;
                }

                if (this.shouldScheduleWirdRevealRecovery(source)) {
                    this.queueWirdEntryRevealRecovery(targetPage, navigationRequestSerial);
                }

                return;
            }

            this.wirdBrowseStep = null;
            const effectiveCurrentStep = pageStep === null ? currentStep : pageStep;

            if (isNextDirection && effectiveCurrentStep >= maxStep) {
                this.markWirdAsCompleted(record);
                await this.exitWirdMode({
                    restoreNormalPage: true,
                    reason: 'auto-complete',
                });

                return;
            }

            if (!isNextDirection && effectiveCurrentStep <= 0) {
                await this.exitWirdMode({
                    restoreNormalPage: true,
                    reason: 'boundary-prev',
                });

                return;
            }

            record.currentStep = isNextDirection
                ? effectiveCurrentStep + 1
                : effectiveCurrentStep - 1;
            record.progressStep = this.normalizeIntegerFlag(
                record?.progressStep,
                effectiveCurrentStep,
                {
                    min: 0,
                    max: maxStep,
                },
            );
            record.progressStep = Math.max(record.progressStep, record.currentStep);
            record.completed = Boolean(record.completed);
            record.updatedAt = Date.now();
            this.wirdDailyRecord = record;
            this.reconcileWirdNextAbsolutePage(record);
            this.persistWirdState();

            const targetPage = this.absolutePageToPageNumber(this.wirdCurrentAbsolutePage(record));
            this.applyWirdNavigationVisualState(targetPage, record.currentStep, {
                source: sourceProfile,
                previousStep: effectiveCurrentStep,
            });

            await this.goToPage(targetPage, {
                direction: isNextDirection ? 'next' : 'prev',
                animate: true,
                forceRefit: true,
                source: `wird-${sourceProfile}`,
            });

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            await this.ensureWirdEntryPageVisible(targetPage);

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            if (this.shouldScheduleWirdRevealRecovery(source)) {
                this.queueWirdEntryRevealRecovery(targetPage, navigationRequestSerial);
            }
        },

        async navigateWirdToStep(step, source = 'slider') {
            if (!this.wirdModeActive) {
                return;
            }

            const navigationRequestSerial = this._wirdNavigationRequestSerial + 1;
            this._wirdNavigationRequestSerial = navigationRequestSerial;
            this.clearWirdEntryRecovery();

            const record = this.ensureWirdDailyRecord();

            if (!record || typeof record !== 'object') {
                return;
            }

            const range = this.wirdRangeState(record);
            const normalizedStep = this.normalizeIntegerFlag(
                step,
                this.wirdActiveStepForNavigation(record),
                {
                    min: 0,
                    max: range.maxStep,
                },
            );
            const targetPage = this.wirdTargetPageFromStep(normalizedStep, record);
            const direction = this.resolveNavigationDirection(targetPage);

            if (record?.completed) {
                this.wirdBrowseStep = normalizedStep;
            } else {
                const maxStep = Math.max(0, range.maxStep);
                record.currentStep = normalizedStep;
                record.progressStep = this.normalizeIntegerFlag(
                    record?.progressStep,
                    normalizedStep,
                    {
                        min: 0,
                        max: maxStep,
                    },
                );
                record.progressStep = Math.max(record.progressStep, normalizedStep);
                record.completed = Boolean(record.completed);
                record.updatedAt = Date.now();
                this.wirdDailyRecord = record;
                this.wirdBrowseStep = null;
                this.reconcileWirdNextAbsolutePage(record);
                this.persistWirdState();
            }

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            if (targetPage === this.pageNumber && this.hasRenderablePage()) {
                this.pageInput = targetPage;
                this._lastPageInputVisualValue = targetPage;
                this.clearWirdSliderVisualTween();
                this.wirdSliderVisualStep = normalizedStep;
                await this.ensureWirdEntryPageVisible(targetPage);

                if (
                    navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                    !this.wirdModeActive
                ) {
                    return;
                }

                if (this.shouldScheduleWirdRevealRecovery(source)) {
                    this.queueWirdEntryRevealRecovery(targetPage, navigationRequestSerial);
                }

                return;
            }

            await this.goToPage(targetPage, {
                direction,
                animate: true,
                forceRefit: true,
                source: `wird-${source}`,
            });

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            await this.ensureWirdEntryPageVisible(targetPage);

            if (
                navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                !this.wirdModeActive
            ) {
                return;
            }

            if (this.shouldScheduleWirdRevealRecovery(source)) {
                this.queueWirdEntryRevealRecovery(targetPage, navigationRequestSerial);
            }
        },

        persistNavigationHistory() {
            this.navigationHistory = writeNavigationHistory(this.navigationHistory);
            this.syncHistoryManagerTableRecords({
                allowRecentOpenRequest: false,
            });
        },

        persistBookmarks() {
            this.bookmarks = writeBookmarks(this.bookmarks);
            this.syncBookmarksManagerTableRecords({
                allowRecentOpenRequest: false,
            });
        },

        normalizeHistoryEntryId(entryId) {
            return String(entryId ?? '').trim();
        },

        historyTagsMatch(currentTags = [], nextTags = []) {
            if (currentTags.length !== nextTags.length) {
                return false;
            }

            return currentTags.every((tag, index) => tag === nextTags[index]);
        },

        historyEntryById(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return null;
            }

            return (
                this.navigationHistory.find(
                    (entry) => this.normalizeHistoryEntryId(entry?.id) === normalizedEntryId,
                ) ?? null
            );
        },

        syncHistoryTagDraftForEntry(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const existingEntry = this.historyEntryById(normalizedEntryId);

            if (!existingEntry) {
                if (
                    this.historyTagDraftById &&
                    Object.prototype.hasOwnProperty.call(
                        this.historyTagDraftById,
                        normalizedEntryId,
                    )
                ) {
                    const nextDrafts = { ...this.historyTagDraftById };
                    delete nextDrafts[normalizedEntryId];
                    this.historyTagDraftById = nextDrafts;
                }

                return;
            }

            const currentDraft = String(this.historyTagDraftById?.[normalizedEntryId] ?? '').trim();

            if (currentDraft !== '') {
                return;
            }

            this.historyTagDraftById = {
                ...this.historyTagDraftById,
                [normalizedEntryId]: '',
            };
        },

        syncHistoryTagDrafts() {
            const nextDrafts = {};

            this.navigationHistory.forEach((entry) => {
                const normalizedEntryId = this.normalizeHistoryEntryId(entry?.id);

                if (!normalizedEntryId) {
                    return;
                }

                nextDrafts[normalizedEntryId] = String(
                    this.historyTagDraftById?.[normalizedEntryId] ?? '',
                );
            });

            this.historyTagDraftById = nextDrafts;
        },

        historyTagDraft(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return '';
            }

            return String(this.historyTagDraftById?.[normalizedEntryId] ?? '');
        },

        setHistoryTagDraft(entryId, value) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            this.historyTagDraftById = {
                ...this.historyTagDraftById,
                [normalizedEntryId]: String(value ?? ''),
            };
        },

        collectSharedManagerTags({ excludeTags = [], draftValue = '' } = {}) {
            const excluded = new Set(
                normalizeTags(excludeTags).map((tag) => String(tag ?? '').toLocaleLowerCase()),
            );
            const normalizedDraftValue = String(draftValue ?? '')
                .toLocaleLowerCase()
                .trim();
            const suggestions = [];
            const usedSuggestions = new Set();
            const suggestionSources = [...this.navigationHistory, ...this.bookmarks];

            suggestionSources.forEach((entry) => {
                (Array.isArray(entry?.tags) ? entry.tags : []).forEach((rawTag) => {
                    const normalizedTag = String(rawTag ?? '').trim();

                    if (normalizedTag === '') {
                        return;
                    }

                    const normalizedKey = normalizedTag.toLocaleLowerCase();

                    if (excluded.has(normalizedKey) || usedSuggestions.has(normalizedKey)) {
                        return;
                    }

                    if (
                        normalizedDraftValue !== '' &&
                        !normalizedKey.includes(normalizedDraftValue)
                    ) {
                        return;
                    }

                    usedSuggestions.add(normalizedKey);
                    suggestions.push(normalizedTag);
                });
            });

            return suggestions.slice(0, 18);
        },

        historyTagSuggestions(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);
            const entry = this.historyEntryById(normalizedEntryId);
            const existingTags = Array.isArray(entry?.tags) ? entry.tags : [];
            const draftValue = this.historyTagDraft(normalizedEntryId);

            return this.collectSharedManagerTags({
                excludeTags: existingTags,
                draftValue,
            });
        },

        commitHistoryTagDraft(entryId, { clearInput = true } = {}) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const draftValue = this.historyTagDraft(normalizedEntryId);
            const parsedDraftTags = normalizeTags(draftValue);

            if (parsedDraftTags.length < 1) {
                if (clearInput) {
                    this.setHistoryTagDraft(normalizedEntryId, '');
                }

                return;
            }

            const entry = this.historyEntryById(normalizedEntryId);
            const existingTags = Array.isArray(entry?.tags) ? entry.tags : [];
            const nextTags = normalizeTags([...existingTags, ...parsedDraftTags]);

            this.updateHistoryEntryTags(normalizedEntryId, nextTags, {
                markUpdated: true,
            });

            if (clearInput) {
                this.setHistoryTagDraft(normalizedEntryId, '');
            }
        },

        removeHistoryEntryTag(entryId, tagValue) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);
            const normalizedTagValue = String(tagValue ?? '').trim();

            if (!normalizedEntryId || normalizedTagValue === '') {
                return;
            }

            const entry = this.historyEntryById(normalizedEntryId);

            if (!entry || !Array.isArray(entry?.tags)) {
                return;
            }

            const nextTags = entry.tags.filter(
                (tag) =>
                    String(tag ?? '').toLocaleLowerCase() !==
                    normalizedTagValue.toLocaleLowerCase(),
            );

            this.updateHistoryEntryTags(normalizedEntryId, nextTags, {
                markUpdated: true,
            });
        },

        historyEntryTagsAsText(entry) {
            if (!Array.isArray(entry?.tags)) {
                return '';
            }

            return entry.tags.join(', ');
        },

        historyEntrySourceLabel(entry) {
            const source = String(entry?.source ?? '');

            if (source === 'surah-directory') {
                return 'تنقّل سريع';
            }

            if (source === 'bookmark-navigation') {
                return 'إشارة مرجعية';
            }

            if (source === 'page-jump') {
                return 'قفزة صفحة';
            }

            if (source === 'page-slider-commit') {
                return 'شريط الصفحات';
            }

            return 'بحث';
        },

        historyEntryContextLabel(entry) {
            const surahNumber = Math.max(0, Math.trunc(Number(entry?.surah_number ?? 0)));
            const ayahNumber = Math.max(0, Math.trunc(Number(entry?.ayah_number ?? 0)));

            if (
                String(entry?.source ?? '') === 'surah-directory' ||
                String(entry?.source ?? '') === 'bookmark-navigation' ||
                String(entry?.source ?? '') === 'page-jump' ||
                String(entry?.source ?? '') === 'page-slider-commit'
            ) {
                return this.surahLabel(surahNumber > 0 ? surahNumber : this.currentSurahNumber());
            }

            if (surahNumber > 0 && ayahNumber > 0) {
                return `${this.surahLabel(surahNumber)} · آية ${ayahNumber}`;
            }

            if (surahNumber > 0) {
                return this.surahLabel(surahNumber);
            }

            const query = normalizeTextValue(entry?.query);

            if (query) {
                return `بحث: ${query}`;
            }

            return 'انتقال عبر البحث';
        },

        historyEntrySurahName(entry) {
            const surahNumber = Math.max(0, Math.trunc(Number(entry?.surah_number ?? 0)));
            const resolvedName = this.surahNameOnly(surahNumber);

            if (resolvedName !== '') {
                return resolvedName;
            }

            return '-';
        },

        nextHistorySortOrder() {
            return (
                this.navigationHistory
                    .filter((entry) => historyEntryHasPersistenceMeta(entry))
                    .reduce((maxValue, entry) => {
                        const sortOrder = Number(entry?.sort_order ?? 0);

                        return sortOrder > maxValue ? sortOrder : maxValue;
                    }, 0) + 1
            );
        },

        normalizePersistedHistorySortOrder() {
            let nextSortOrder = 1;

            this.navigationHistory = this.navigationHistory.map((entry) => {
                if (!historyEntryHasPersistenceMeta(entry)) {
                    return {
                        ...entry,
                        sort_order: 0,
                    };
                }

                const normalizedEntry = {
                    ...entry,
                    sort_order: nextSortOrder,
                };

                nextSortOrder += 1;

                return normalizedEntry;
            });
        },

        reorderNavigationHistoryByIds(orderIds = []) {
            const normalizedOrderIds = (Array.isArray(orderIds) ? orderIds : [])
                .map((entryId) => this.normalizeHistoryEntryId(entryId))
                .filter((entryId) => entryId !== '');

            if (normalizedOrderIds.length < 1) {
                return;
            }

            const historyById = new Map(
                this.navigationHistory.map((entry) => [
                    this.normalizeHistoryEntryId(entry?.id),
                    entry,
                ]),
            );
            const orderedEntries = [];
            const usedIds = new Set();

            normalizedOrderIds.forEach((entryId) => {
                const entry = historyById.get(entryId);

                if (!entry || usedIds.has(entryId)) {
                    return;
                }

                usedIds.add(entryId);
                orderedEntries.push(entry);
            });

            this.navigationHistory.forEach((entry) => {
                const entryId = this.normalizeHistoryEntryId(entry?.id);

                if (entryId === '' || usedIds.has(entryId)) {
                    return;
                }

                orderedEntries.push(entry);
            });

            this.navigationHistory = orderedEntries;
            this.normalizePersistedHistorySortOrder();
            this.persistNavigationHistory();
        },

        normalizeBookmarksSortOrder() {
            let nextSortOrder = 1;

            this.bookmarks = this.bookmarks.map((bookmark) => {
                const normalizedBookmark = {
                    ...bookmark,
                    sort_order: nextSortOrder,
                };

                nextSortOrder += 1;

                return normalizedBookmark;
            });
        },

        reorderBookmarksByIds(orderIds = []) {
            const normalizedOrderIds = (Array.isArray(orderIds) ? orderIds : [])
                .map((entryId) => this.normalizeBookmarkEntryId(entryId))
                .filter((entryId) => entryId !== '');

            if (normalizedOrderIds.length < 1) {
                return;
            }

            const bookmarksById = new Map(
                this.bookmarks.map((bookmark) => [
                    this.normalizeBookmarkEntryId(bookmark?.id),
                    bookmark,
                ]),
            );
            const orderedBookmarks = [];
            const usedIds = new Set();

            normalizedOrderIds.forEach((bookmarkId) => {
                const bookmark = bookmarksById.get(bookmarkId);

                if (!bookmark || usedIds.has(bookmarkId)) {
                    return;
                }

                usedIds.add(bookmarkId);
                orderedBookmarks.push(bookmark);
            });

            this.bookmarks.forEach((bookmark) => {
                const bookmarkId = this.normalizeBookmarkEntryId(bookmark?.id);

                if (bookmarkId === '' || usedIds.has(bookmarkId)) {
                    return;
                }

                orderedBookmarks.push(bookmark);
            });

            this.bookmarks = orderedBookmarks;
            this.normalizeBookmarksSortOrder();
            this.persistBookmarks();
        },

        emitLivewireManagerEvent(eventName, detail = {}) {
            const normalizedEventName = String(eventName ?? '').trim();

            if (normalizedEventName === '') {
                return;
            }

            if (typeof window?.Livewire?.dispatch === 'function') {
                window.Livewire.dispatch(normalizedEventName, detail);
            }
        },

        hasRecentHistoryModalOpenRequest(windowMs = 2200) {
            const requestAgeMs = Date.now() - Number(this._historyModalOpenRequestedAt ?? 0);

            return (
                Number.isFinite(requestAgeMs) &&
                requestAgeMs >= 0 &&
                requestAgeMs <= Math.max(400, Math.trunc(Number(windowMs) || 2200))
            );
        },

        hasRecentBookmarksModalOpenRequest(windowMs = 2200) {
            const requestAgeMs = Date.now() - Number(this._bookmarksModalOpenRequestedAt ?? 0);

            return (
                Number.isFinite(requestAgeMs) &&
                requestAgeMs >= 0 &&
                requestAgeMs <= Math.max(400, Math.trunc(Number(windowMs) || 2200))
            );
        },

        shouldSyncHistoryManagerTableNow({ allowRecentOpenRequest = false } = {}) {
            if (this._modalNavigationCloseGuardActive) {
                return false;
            }

            const isHistoryModalVisible =
                this.historyModalOpen || this.isModalWindowVisibleById(this.historyModalId);

            if (isHistoryModalVisible) {
                return true;
            }

            if (this._isModalLifecycleSettling) {
                return false;
            }

            if (
                this._activeModalIds.size > 0 &&
                !this._activeModalIds.has(String(this.historyModalId ?? '').trim())
            ) {
                return false;
            }

            if (!allowRecentOpenRequest) {
                return false;
            }

            return this.hasRecentHistoryModalOpenRequest();
        },

        shouldSyncBookmarksManagerTableNow({ allowRecentOpenRequest = false } = {}) {
            if (this._modalNavigationCloseGuardActive) {
                return false;
            }

            const isBookmarksModalVisible =
                this.bookmarksModalOpen || this.isModalWindowVisibleById(this.bookmarksModalId);

            if (isBookmarksModalVisible) {
                return true;
            }

            if (this._isModalLifecycleSettling) {
                return false;
            }

            if (
                this._activeModalIds.size > 0 &&
                !this._activeModalIds.has(String(this.bookmarksModalId ?? '').trim())
            ) {
                return false;
            }

            if (!allowRecentOpenRequest) {
                return false;
            }

            return this.hasRecentBookmarksModalOpenRequest();
        },

        clearHistoryManagerSyncQueue() {
            this._historyManagerSyncTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._historyManagerSyncTimers = [];
        },

        clearBookmarksManagerSyncQueue() {
            this._bookmarksManagerSyncTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._bookmarksManagerSyncTimers = [];
        },

        queueHistoryManagerTableSync({ force = false } = {}) {
            this.clearHistoryManagerSyncQueue();

            const shouldAllowRecentOpenRequest = Boolean(force);

            if (
                !this.shouldSyncHistoryManagerTableNow({
                    allowRecentOpenRequest: shouldAllowRecentOpenRequest,
                })
            ) {
                return;
            }

            const syncPulseDelays = [0];

            syncPulseDelays.forEach((delayMs) => {
                const timerId = window.setTimeout(() => {
                    this._historyManagerSyncTimers = this._historyManagerSyncTimers.filter(
                        (activeTimerId) => activeTimerId !== timerId,
                    );
                    this.syncHistoryManagerTableRecords({
                        force,
                        allowRecentOpenRequest: shouldAllowRecentOpenRequest,
                    });
                }, delayMs);

                this._historyManagerSyncTimers.push(timerId);
            });
        },

        queueBookmarksManagerTableSync({ force = false } = {}) {
            this.clearBookmarksManagerSyncQueue();

            const shouldAllowRecentOpenRequest = Boolean(force);

            if (
                !this.shouldSyncBookmarksManagerTableNow({
                    allowRecentOpenRequest: shouldAllowRecentOpenRequest,
                })
            ) {
                return;
            }

            const syncPulseDelays = [0];

            syncPulseDelays.forEach((delayMs) => {
                const timerId = window.setTimeout(() => {
                    this._bookmarksManagerSyncTimers = this._bookmarksManagerSyncTimers.filter(
                        (activeTimerId) => activeTimerId !== timerId,
                    );
                    this.syncBookmarksManagerTableRecords({
                        force,
                        allowRecentOpenRequest: shouldAllowRecentOpenRequest,
                    });
                }, delayMs);

                this._bookmarksManagerSyncTimers.push(timerId);
            });
        },

        syncHistoryManagerTableRecords({ force = false, allowRecentOpenRequest = false } = {}) {
            if (
                !this.shouldSyncHistoryManagerTableNow({
                    allowRecentOpenRequest,
                })
            ) {
                return;
            }

            const payload = {
                records: this.navigationHistory,
                surahNames: this.search?.surahNames ?? {},
            };

            this.emitLivewireManagerEvent('quran-history-manager-sync', payload);
        },

        syncBookmarksManagerTableRecords({ force = false, allowRecentOpenRequest = false } = {}) {
            if (
                !this.shouldSyncBookmarksManagerTableNow({
                    allowRecentOpenRequest,
                })
            ) {
                return;
            }

            const payload = {
                records: this.bookmarks,
                surahNames: this.search?.surahNames ?? {},
                surahDirectory: Array.isArray(this.search?.surahDirectory)
                    ? this.search.surahDirectory
                    : [],
            };

            this.emitLivewireManagerEvent('quran-bookmarks-manager-sync', payload);
        },

        extractReorderIdsFromPayload(payload = null) {
            if (Array.isArray(payload)) {
                return payload
                    .map((value) => String(value ?? '').trim())
                    .filter((value) => value !== '');
            }

            if (!payload || typeof payload !== 'object') {
                return [];
            }

            return Object.entries(payload)
                .map(([recordId, order]) => ({
                    recordId: String(recordId ?? '').trim(),
                    order: Number(order ?? 0),
                }))
                .filter((entry) => entry.recordId !== '')
                .sort((left, right) => left.order - right.order)
                .map((entry) => entry.recordId);
        },

        async handleHistoryManagerGoEvent(detail = {}) {
            const entry = this.historyEntryById(detail?.id);

            if (!entry) {
                return;
            }

            await this.goToHistoryEntry(entry);
        },

        applyHistoryManagerRecordUpdate(detail = {}) {
            const entryId = this.normalizeHistoryEntryId(detail?.id);

            if (!entryId) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(detail ?? {}, 'note')) {
                this.updateHistoryEntryNote(entryId, detail?.note);
            }

            if (Object.prototype.hasOwnProperty.call(detail ?? {}, 'tags')) {
                this.updateHistoryEntryTags(entryId, detail?.tags ?? [], {
                    markUpdated: false,
                });
            }

            this.markManagerRowUpdated('history', entryId);
        },

        applyHistoryManagerReorder(detail = {}) {
            const orderIds = this.extractReorderIdsFromPayload(detail?.order ?? detail);

            if (orderIds.length < 1) {
                return;
            }

            this.reorderNavigationHistoryByIds(orderIds);
        },
    };
};
