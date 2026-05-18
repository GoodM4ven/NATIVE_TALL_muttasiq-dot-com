export const createWirdAndHistoryBookmarksAndEffectsModule = (deps) => {
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
        async handleBookmarksManagerGoEvent(detail = {}) {
            const bookmark = this.bookmarkEntryById(detail?.id);

            if (!bookmark) {
                return;
            }

            await this.goToBookmark(bookmark);
        },

        applyBookmarkManagerRecordUpdate(detail = {}) {
            const bookmarkId = this.normalizeBookmarkEntryId(detail?.id);

            if (!bookmarkId) {
                return;
            }

            if (Object.prototype.hasOwnProperty.call(detail ?? {}, 'note')) {
                this.updateBookmarkNote(bookmarkId, detail?.note);
            }

            if (Object.prototype.hasOwnProperty.call(detail ?? {}, 'tags')) {
                this.updateBookmarkTags(bookmarkId, detail?.tags ?? [], {
                    markUpdated: false,
                });
            }

            this.markManagerRowUpdated('bookmarks', bookmarkId);
        },

        applyBookmarksManagerReorder(detail = {}) {
            const orderIds = this.extractReorderIdsFromPayload(detail?.order ?? detail);

            if (orderIds.length < 1) {
                return;
            }

            this.reorderBookmarksByIds(orderIds);
        },

        dispatchManagerModalsVisibilityState() {
            const hasVisibleManagerModal =
                this.isSearchModalWindowVisible() ||
                this.isModalWindowVisibleById(this.historyModalId) ||
                this.isModalWindowVisibleById(this.bookmarksModalId) ||
                this.isModalWindowVisibleById(this.jumpPageModalId);

            window.dispatchEvent(
                new CustomEvent('quran-manager-modals-visibility', {
                    detail: {
                        open:
                            hasVisibleManagerModal ||
                            this.search.modalOpen ||
                            this.historyModalOpen ||
                            this.bookmarksModalOpen ||
                            this.jumpPageModalOpen,
                    },
                }),
            );
        },

        managerRowEffectClass(collection, itemId) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';
            const normalizedItemId = String(itemId ?? '').trim();

            if (normalizedItemId === '') {
                return '';
            }

            const effect = this.managerRowEffects?.[normalizedCollection]?.[normalizedItemId] ?? '';

            if (effect === 'updated') {
                return 'quran-manager-row--updated';
            }

            if (effect === 'replacing') {
                return 'quran-manager-row--replacing';
            }

            if (effect === 'removing') {
                return 'quran-manager-row--removing';
            }

            return '';
        },

        historyRowEffectClass(entry) {
            return this.managerRowEffectClass('history', entry?.id);
        },

        bookmarkRowEffectClass(bookmark) {
            return this.managerRowEffectClass('bookmarks', bookmark?.id);
        },

        managerRowEffectTimerKey(collection, itemId) {
            return `${collection}:${itemId}`;
        },

        clearManagerRowEffectTimer(collection, itemId) {
            const timerKey = this.managerRowEffectTimerKey(collection, itemId);
            const timerId = this._managerRowEffectTimers.get(timerKey);

            if (timerId !== undefined) {
                clearTimeout(timerId);
                this._managerRowEffectTimers.delete(timerKey);
            }
        },

        setManagerRowEffect(collection, itemId, effect) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';
            const normalizedItemId = String(itemId ?? '').trim();

            if (normalizedItemId === '') {
                return;
            }

            const nextCollectionEffects = {
                ...(this.managerRowEffects?.[normalizedCollection] ?? {}),
            };

            if (String(effect ?? '').trim() === '') {
                delete nextCollectionEffects[normalizedItemId];
            } else {
                nextCollectionEffects[normalizedItemId] = String(effect ?? '');
            }

            this.managerRowEffects = {
                ...this.managerRowEffects,
                [normalizedCollection]: nextCollectionEffects,
            };
        },

        markManagerRowUpdated(collection, itemId) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';
            const normalizedItemId = String(itemId ?? '').trim();

            if (normalizedItemId === '') {
                return;
            }

            this.clearManagerRowEffectTimer(normalizedCollection, normalizedItemId);
            this.setManagerRowEffect(normalizedCollection, normalizedItemId, 'updated');

            const timerKey = this.managerRowEffectTimerKey(normalizedCollection, normalizedItemId);
            const timerId = window.setTimeout(() => {
                this.setManagerRowEffect(normalizedCollection, normalizedItemId, '');
                this._managerRowEffectTimers.delete(timerKey);
            }, managerRowUpdateAnimationDurationMs);

            this._managerRowEffectTimers.set(timerKey, timerId);
        },

        markManagerRowReplaced(collection, itemId) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';
            const normalizedItemId = String(itemId ?? '').trim();

            if (normalizedItemId === '') {
                return;
            }

            this.clearManagerRowEffectTimer(normalizedCollection, normalizedItemId);
            this.setManagerRowEffect(normalizedCollection, normalizedItemId, 'replacing');

            const timerKey = this.managerRowEffectTimerKey(normalizedCollection, normalizedItemId);
            const timerId = window.setTimeout(() => {
                this.setManagerRowEffect(normalizedCollection, normalizedItemId, '');
                this._managerRowEffectTimers.delete(timerKey);
            }, managerRowReplaceAnimationDurationMs);

            this._managerRowEffectTimers.set(timerKey, timerId);
        },

        markManagerRowsRemoving(collection, itemIds = []) {
            const normalizedCollection = collection === 'history' ? 'history' : 'bookmarks';

            itemIds
                .map((itemId) => String(itemId ?? '').trim())
                .filter((itemId) => itemId !== '')
                .forEach((itemId) => {
                    this.clearManagerRowEffectTimer(normalizedCollection, itemId);
                    this.setManagerRowEffect(normalizedCollection, itemId, 'removing');
                });
        },

        updateHistoryEntryTags(entryId, rawTags, { markUpdated = true } = {}) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const parsedTags = normalizeTags(rawTags);
            let didUpdateEntry = false;

            this.navigationHistory = this.navigationHistory.map((entry) => {
                if (this.normalizeHistoryEntryId(entry?.id) !== normalizedEntryId) {
                    return entry;
                }

                const currentTags = normalizeTags(entry?.tags ?? []);

                if (this.historyTagsMatch(currentTags, parsedTags)) {
                    return entry;
                }

                didUpdateEntry = true;
                const nextNote = normalizeTextValue(entry?.note);
                const nextSortOrder =
                    parsedTags.length > 0 || Boolean(nextNote)
                        ? Math.max(1, Number(entry?.sort_order ?? this.nextHistorySortOrder()))
                        : 0;

                return {
                    ...entry,
                    tags: parsedTags,
                    created_at: Number(entry?.created_at ?? Date.now()),
                    sort_order: nextSortOrder,
                };
            });

            if (!didUpdateEntry) {
                this.syncHistoryTagDraftForEntry(normalizedEntryId);

                return;
            }

            this.normalizePersistedHistorySortOrder();
            this.persistNavigationHistory();

            if (markUpdated) {
                this.markManagerRowUpdated('history', normalizedEntryId);
            }

            this.syncHistoryTagDraftForEntry(normalizedEntryId);
        },

        updateHistoryEntryNote(entryId, note) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const normalizedNote = normalizeTextValue(note);
            let didUpdateEntry = false;

            this.navigationHistory = this.navigationHistory.map((entry) => {
                if (this.normalizeHistoryEntryId(entry?.id) !== normalizedEntryId) {
                    return entry;
                }

                if (normalizeTextValue(entry?.note) === normalizedNote) {
                    return entry;
                }

                didUpdateEntry = true;
                const existingTags = normalizeTags(entry?.tags ?? []);
                const nextSortOrder =
                    existingTags.length > 0 || Boolean(normalizedNote)
                        ? Math.max(1, Number(entry?.sort_order ?? this.nextHistorySortOrder()))
                        : 0;

                return {
                    ...entry,
                    note: normalizedNote,
                    created_at: Number(entry?.created_at ?? Date.now()),
                    sort_order: nextSortOrder,
                };
            });

            if (!didUpdateEntry) {
                return;
            }

            this.normalizePersistedHistorySortOrder();
            this.persistNavigationHistory();
            this.markManagerRowUpdated('history', normalizedEntryId);
        },

        removeHistoryEntry(entryId) {
            const normalizedEntryId = this.normalizeHistoryEntryId(entryId);

            if (!normalizedEntryId) {
                return;
            }

            const hasEntry = this.navigationHistory.some(
                (entry) => this.normalizeHistoryEntryId(entry?.id) === normalizedEntryId,
            );

            if (!hasEntry) {
                return;
            }

            this.markManagerRowsRemoving('history', [normalizedEntryId]);

            window.setTimeout(() => {
                this.navigationHistory = this.navigationHistory.filter((entry) => {
                    return this.normalizeHistoryEntryId(entry?.id) !== normalizedEntryId;
                });
                this.normalizePersistedHistorySortOrder();
                this.persistNavigationHistory();
                this.syncHistoryTagDrafts();
                this.setManagerRowEffect('history', normalizedEntryId, '');
            }, managerRowRemoveAnimationDurationMs);
        },

        clearNavigationHistory() {
            const removableIds = this.navigationHistory
                .filter((entry) => !historyEntryHasPersistenceMeta(entry))
                .map((entry) => String(entry?.id ?? '').trim())
                .filter((entryId) => entryId !== '');

            if (removableIds.length === 0) {
                return;
            }

            this.markManagerRowsRemoving('history', removableIds);

            window.setTimeout(() => {
                this.navigationHistory = this.navigationHistory.filter((entry) => {
                    const normalizedEntryId = String(entry?.id ?? '').trim();

                    return !removableIds.includes(normalizedEntryId);
                });
                this.normalizePersistedHistorySortOrder();
                this.persistNavigationHistory();
                this.syncHistoryTagDrafts();
                removableIds.forEach((entryId) => {
                    this.setManagerRowEffect('history', entryId, '');
                });
            }, managerRowRemoveAnimationDurationMs);
        },

        recordNavigationHistory({
            source = 'search-result',
            pageNumber = this.pageNumber,
            surahNumber = 0,
            ayahNumber = 0,
            ayahIndex = 0,
            note = null,
            query = null,
            tags = [],
        } = {}) {
            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);
            const normalizedSurahNumber = Math.max(0, Math.trunc(Number(surahNumber ?? 0)));
            const normalizedAyahNumber = Math.max(0, Math.trunc(Number(ayahNumber ?? 0)));
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));
            const sourceValue = String(source ?? '');
            const normalizedSource = supportedHistorySources.includes(sourceValue)
                ? sourceValue
                : 'search-result';
            const normalizedNote = normalizeTextValue(note);
            const normalizedQuery = normalizeTextValue(query);
            const normalizedTags = normalizeTags(tags);

            this.navigationHistory = [
                normalizeHistoryEntry({
                    id: uniqueLocalId(),
                    source: normalizedSource,
                    page_number: normalizedPageNumber,
                    surah_number: normalizedSurahNumber,
                    ayah_number: normalizedAyahNumber,
                    ayah_index: normalizedAyahIndex,
                    note: normalizedNote,
                    query: normalizedQuery,
                    tags: normalizedTags,
                    created_at: Date.now(),
                    sort_order:
                        normalizedTags.length > 0 || Boolean(normalizedNote)
                            ? this.nextHistorySortOrder()
                            : 0,
                }),
                ...this.navigationHistory,
            ];
            this.normalizePersistedHistorySortOrder();
            this.persistNavigationHistory();
            this.syncHistoryTagDrafts();
        },

        normalizeBookmarkEntryId(bookmarkId) {
            return String(bookmarkId ?? '').trim();
        },

        bookmarkEntryById(bookmarkId) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return null;
            }

            return (
                this.bookmarks.find(
                    (bookmark) =>
                        this.normalizeBookmarkEntryId(bookmark?.id) === normalizedBookmarkId,
                ) ?? null
            );
        },

        syncBookmarkTagDraftForEntry(bookmarkId) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return;
            }

            const existingBookmark = this.bookmarkEntryById(normalizedBookmarkId);

            if (!existingBookmark) {
                if (
                    this.bookmarkTagDraftById &&
                    Object.prototype.hasOwnProperty.call(
                        this.bookmarkTagDraftById,
                        normalizedBookmarkId,
                    )
                ) {
                    const nextDrafts = { ...this.bookmarkTagDraftById };
                    delete nextDrafts[normalizedBookmarkId];
                    this.bookmarkTagDraftById = nextDrafts;
                }

                return;
            }

            const currentDraft = String(
                this.bookmarkTagDraftById?.[normalizedBookmarkId] ?? '',
            ).trim();

            if (currentDraft !== '') {
                return;
            }

            this.bookmarkTagDraftById = {
                ...this.bookmarkTagDraftById,
                [normalizedBookmarkId]: '',
            };
        },

        syncBookmarkTagDrafts() {
            const nextDrafts = {};

            this.bookmarks.forEach((bookmark) => {
                const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmark?.id);

                if (!normalizedBookmarkId) {
                    return;
                }

                nextDrafts[normalizedBookmarkId] = String(
                    this.bookmarkTagDraftById?.[normalizedBookmarkId] ?? '',
                );
            });

            this.bookmarkTagDraftById = nextDrafts;
        },

        bookmarkTagDraft(bookmarkId) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return '';
            }

            return String(this.bookmarkTagDraftById?.[normalizedBookmarkId] ?? '');
        },

        setBookmarkTagDraft(bookmarkId, value) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return;
            }

            this.bookmarkTagDraftById = {
                ...this.bookmarkTagDraftById,
                [normalizedBookmarkId]: String(value ?? ''),
            };
        },

        bookmarkTagSuggestions(bookmarkId) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);
            const bookmark = this.bookmarkEntryById(normalizedBookmarkId);
            const existingTags = Array.isArray(bookmark?.tags) ? bookmark.tags : [];
            const draftValue = this.bookmarkTagDraft(normalizedBookmarkId);

            return this.collectSharedManagerTags({
                excludeTags: existingTags,
                draftValue,
            });
        },

        commitBookmarkTagDraft(bookmarkId, { clearInput = true } = {}) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return;
            }

            const draftValue = this.bookmarkTagDraft(normalizedBookmarkId);
            const parsedDraftTags = normalizeTags(draftValue);

            if (parsedDraftTags.length < 1) {
                if (clearInput) {
                    this.setBookmarkTagDraft(normalizedBookmarkId, '');
                }

                return;
            }

            const bookmark = this.bookmarkEntryById(normalizedBookmarkId);
            const existingTags = Array.isArray(bookmark?.tags) ? bookmark.tags : [];
            const nextTags = normalizeTags([...existingTags, ...parsedDraftTags]);

            this.updateBookmarkTags(normalizedBookmarkId, nextTags, {
                markUpdated: true,
            });

            if (clearInput) {
                this.setBookmarkTagDraft(normalizedBookmarkId, '');
            }
        },

        removeBookmarkTag(bookmarkId, tagValue) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);
            const normalizedTagValue = String(tagValue ?? '').trim();

            if (!normalizedBookmarkId || normalizedTagValue === '') {
                return;
            }

            const bookmark = this.bookmarkEntryById(normalizedBookmarkId);

            if (!bookmark || !Array.isArray(bookmark?.tags)) {
                return;
            }

            const nextTags = bookmark.tags.filter(
                (tag) =>
                    String(tag ?? '').toLocaleLowerCase() !==
                    normalizedTagValue.toLocaleLowerCase(),
            );

            this.updateBookmarkTags(normalizedBookmarkId, nextTags, {
                markUpdated: true,
            });
        },

        bookmarkedPageEntry(pageNumber = this.pageNumber) {
            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);

            return (
                this.bookmarks.find(
                    (bookmark) =>
                        clampPage(bookmark?.page_number ?? 1, this.maxPage) ===
                        normalizedPageNumber,
                ) ?? null
            );
        },

        isCurrentPageBookmarked() {
            return this.bookmarkedPageEntry(this.pageNumber) !== null;
        },

        defaultBookmarkNote(pageNumber = this.pageNumber) {
            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);
            const surahTitle = this.currentSurahTitle();

            return `${surahTitle} · صفحة ${normalizedPageNumber}`;
        },

        addBookmark({
            pageNumber = this.pageNumber,
            note = null,
            tags = [],
            preserveCreatedAt = null,
            id = null,
        } = {}) {
            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);
            const timestamp = Date.now();
            const existingEntry = this.bookmarkedPageEntry(normalizedPageNumber);
            const nextId = String(id ?? existingEntry?.id ?? uniqueLocalId());
            const normalizedNote = normalizeTextValue(note ?? existingEntry?.note);
            const normalizedTags = normalizeTags(tags ?? existingEntry?.tags ?? []);

            this.bookmarks = this.bookmarks.filter(
                (bookmark) => String(bookmark?.id ?? '') !== String(existingEntry?.id ?? ''),
            );
            this.normalizeBookmarksSortOrder();
            this.bookmarks.unshift(
                normalizeBookmarkEntry({
                    id: nextId,
                    page_number: normalizedPageNumber,
                    note: normalizedNote,
                    tags: normalizedTags,
                    created_at:
                        preserveCreatedAt !== null
                            ? Number(preserveCreatedAt)
                            : Number(existingEntry?.created_at ?? timestamp),
                    updated_at: timestamp,
                    sort_order: 1,
                }),
            );
            this.normalizeBookmarksSortOrder();
            this.persistBookmarks();
            this.markManagerRowUpdated('bookmarks', nextId);
            this.syncBookmarkTagDraftForEntry(nextId);
        },

        toggleCurrentPageBookmark() {
            const existingEntry = this.bookmarkedPageEntry(this.pageNumber);

            if (existingEntry) {
                this.removeBookmark(existingEntry.id);

                return;
            }

            this.addBookmark({ pageNumber: this.pageNumber });
        },

        removeBookmark(bookmarkId) {
            const normalizedBookmarkId = String(bookmarkId ?? '').trim();

            if (!normalizedBookmarkId) {
                return;
            }

            this.markManagerRowsRemoving('bookmarks', [normalizedBookmarkId]);

            window.setTimeout(() => {
                this.bookmarks = this.bookmarks.filter(
                    (bookmark) => String(bookmark?.id ?? '') !== normalizedBookmarkId,
                );
                this.normalizeBookmarksSortOrder();
                this.persistBookmarks();
                this.setManagerRowEffect('bookmarks', normalizedBookmarkId, '');
                this.syncBookmarkTagDrafts();
            }, managerRowRemoveAnimationDurationMs);
        },

        updateBookmarkNote(bookmarkId, note) {
            const normalizedBookmarkId = String(bookmarkId ?? '').trim();

            if (!normalizedBookmarkId) {
                return;
            }

            const normalizedNote = normalizeTextValue(note);
            let didUpdateBookmark = false;

            this.bookmarks = this.bookmarks.map((bookmark) => {
                if (String(bookmark?.id ?? '') !== normalizedBookmarkId) {
                    return bookmark;
                }

                if (normalizeTextValue(bookmark?.note) === normalizedNote) {
                    return bookmark;
                }

                didUpdateBookmark = true;

                return {
                    ...bookmark,
                    note: normalizedNote,
                    updated_at: Date.now(),
                };
            });

            if (!didUpdateBookmark) {
                return;
            }

            this.normalizeBookmarksSortOrder();
            this.persistBookmarks();
            this.markManagerRowUpdated('bookmarks', normalizedBookmarkId);
        },

        updateBookmarkTags(bookmarkId, rawTags, { markUpdated = true } = {}) {
            const normalizedBookmarkId = this.normalizeBookmarkEntryId(bookmarkId);

            if (!normalizedBookmarkId) {
                return;
            }

            const parsedTags = normalizeTags(rawTags);
            let didUpdateBookmark = false;

            this.bookmarks = this.bookmarks.map((bookmark) => {
                if (this.normalizeBookmarkEntryId(bookmark?.id) !== normalizedBookmarkId) {
                    return bookmark;
                }

                const currentTags = normalizeTags(bookmark?.tags ?? []);

                if (this.historyTagsMatch(currentTags, parsedTags)) {
                    return bookmark;
                }

                didUpdateBookmark = true;

                return {
                    ...bookmark,
                    tags: parsedTags,
                    updated_at: Date.now(),
                };
            });

            if (!didUpdateBookmark) {
                this.syncBookmarkTagDraftForEntry(normalizedBookmarkId);

                return;
            }

            this.normalizeBookmarksSortOrder();
            this.persistBookmarks();

            if (markUpdated) {
                this.markManagerRowUpdated('bookmarks', normalizedBookmarkId);
            }

            this.syncBookmarkTagDraftForEntry(normalizedBookmarkId);
        },

        replaceBookmarkPage(bookmarkId) {
            const normalizedBookmarkId = String(bookmarkId ?? '').trim();
            const targetBookmark = this.bookmarks.find(
                (bookmark) => String(bookmark?.id ?? '') === normalizedBookmarkId,
            );

            if (!targetBookmark) {
                return;
            }

            const samePageBookmark = this.bookmarkedPageEntry(this.pageNumber);

            this.bookmarks = this.bookmarks.filter((bookmark) => {
                const normalizedBookmarkEntryId = String(bookmark?.id ?? '');

                if (normalizedBookmarkEntryId === normalizedBookmarkId) {
                    return true;
                }

                if (
                    samePageBookmark &&
                    normalizedBookmarkEntryId === String(samePageBookmark?.id ?? '') &&
                    normalizedBookmarkEntryId !== normalizedBookmarkId
                ) {
                    return false;
                }

                return true;
            });
            this.bookmarks = this.bookmarks.map((bookmark) => {
                if (String(bookmark?.id ?? '') !== normalizedBookmarkId) {
                    return bookmark;
                }

                return normalizeBookmarkEntry({
                    ...bookmark,
                    page_number: this.pageNumber,
                    updated_at: Date.now(),
                });
            });
            this.reorderBookmarksByIds([
                normalizedBookmarkId,
                ...this.bookmarks
                    .map((bookmark) => this.normalizeBookmarkEntryId(bookmark?.id))
                    .filter((bookmarkId) => bookmarkId !== normalizedBookmarkId),
            ]);
            this.markManagerRowReplaced('bookmarks', normalizedBookmarkId);
            this.syncBookmarkTagDrafts();
        },

        clearBookmarkButtonPressState({ resetSuppressClick = true } = {}) {
            if (this.bookmarkButtonPress.timer !== null) {
                clearTimeout(this.bookmarkButtonPress.timer);
                this.bookmarkButtonPress.timer = null;
            }

            this.bookmarkButtonPress.pointerId = null;
            this.bookmarkButtonPress.holdTriggered = false;

            if (resetSuppressClick) {
                this.bookmarkButtonPress.suppressClick = false;
            }
        },

        onBookmarkButtonPointerDown(event) {
            this.clearBookmarkButtonPressState();
            this.bookmarkButtonPress.pointerId = Number(event?.pointerId ?? 0) || null;
            this.bookmarkButtonPress.holdTriggered = false;
            this.bookmarkButtonPress.suppressClick = false;
            this.bookmarkButtonPress.timer = window.setTimeout(() => {
                this.bookmarkButtonPress.timer = null;
                this.bookmarkButtonPress.holdTriggered = true;
                this.bookmarkButtonPress.suppressClick = true;
                this.openBookmarksManager();
            }, bookmarkHoldDelayMs);
        },
    };
};
