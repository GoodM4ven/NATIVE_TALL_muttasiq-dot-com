export const createSelectionCopyComposeAndPointerModule = (deps) => {
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
        shouldAppendDraggedSurahAffix() {
            if (this.doesAppendSurahAffixAlwaysOnCopy) {
                return true;
            }

            if (!this.doesAppendSurahAffixOnMultiCopy) {
                return false;
            }

            return this.selectedDraggedAyahIndexes().length > 1;
        },

        draggedSelectionSurahAffixes() {
            if (!this.shouldAppendDraggedSurahAffix()) {
                return [];
            }

            return this.selectedDraggedSurahNumbers()
                .map((surahNumber) => `~ [${this.surahLabel(surahNumber)}]`)
                .filter((affix) => normalizeTextValue(affix) !== null);
        },

        draggedSelectionSurahAffix() {
            return this.draggedSelectionSurahAffixes()[0] ?? null;
        },

        composeDraggedWordSelectionText() {
            if (!Array.isArray(this.wordPress.trailWords) || this.wordPress.trailWords.length < 1) {
                return null;
            }

            const orderedUniqueWords = [];
            const normalizedWords = this.wordPress.trailWords
                .map((word) => {
                    const wordText = this.extractWordText(word);
                    const wordMeta = this.normalizeSelectableWordMeta(word);

                    if (!wordText || wordMeta.ayahIndex < 1) {
                        return null;
                    }

                    return {
                        wordText,
                        ayahIndex: wordMeta.ayahIndex,
                        ayahNumber: wordMeta.ayahNumber,
                        wordIndex: Math.max(0, Math.trunc(Number(wordMeta.wordIndex ?? 0))),
                        selectionKey: this.wordSelectionKeyFromMeta(wordMeta),
                    };
                })
                .filter((entry) => entry !== null)
                .sort((firstEntry, secondEntry) => {
                    if (firstEntry.ayahIndex !== secondEntry.ayahIndex) {
                        return firstEntry.ayahIndex - secondEntry.ayahIndex;
                    }

                    if (firstEntry.wordIndex !== secondEntry.wordIndex) {
                        return firstEntry.wordIndex - secondEntry.wordIndex;
                    }

                    return firstEntry.wordText.localeCompare(secondEntry.wordText, 'ar');
                });
            const usedWordKeys = new Set();

            normalizedWords.forEach((entry) => {
                const uniqueKey =
                    entry.selectionKey ?? `${entry.ayahIndex}:${entry.wordIndex}:${entry.wordText}`;

                if (usedWordKeys.has(uniqueKey)) {
                    return;
                }

                usedWordKeys.add(uniqueKey);
                orderedUniqueWords.push(entry);
            });

            if (orderedUniqueWords.length < 1) {
                return null;
            }

            const ayahGroups = [];

            orderedUniqueWords.forEach((entry) => {
                const currentGroup = ayahGroups[ayahGroups.length - 1] ?? null;

                if (!currentGroup || currentGroup.ayahIndex !== entry.ayahIndex) {
                    ayahGroups.push({
                        ayahIndex: entry.ayahIndex,
                        ayahNumber: entry.ayahNumber,
                        words: [entry.wordText],
                    });

                    return;
                }

                currentGroup.words.push(entry.wordText);
            });

            if (ayahGroups.length < 1) {
                return null;
            }

            const shouldAppendAyahSplitters = ayahGroups.length > 1;
            const shouldAppendSurahAffixes = this.shouldAppendDraggedSurahAffix();
            const parts = [];

            ayahGroups.forEach((group, groupIndex) => {
                const groupedText = normalizeTextValue(group.words.join(' '));

                if (!groupedText) {
                    return;
                }

                parts.push(groupedText);

                if (!shouldAppendAyahSplitters) {
                    if (!shouldAppendSurahAffixes) {
                        return;
                    }
                } else {
                    const splitter = this.ayahSplitterToken(group.ayahIndex, group.ayahNumber);

                    if (splitter) {
                        parts.push(splitter);
                    }
                }

                if (!shouldAppendSurahAffixes) {
                    return;
                }

                const currentSurahNumber = this.surahNumberForAyahIndex(group.ayahIndex);
                const nextGroup = ayahGroups[groupIndex + 1] ?? null;
                const nextSurahNumber = nextGroup
                    ? this.surahNumberForAyahIndex(nextGroup.ayahIndex)
                    : 0;

                if (currentSurahNumber < 1 || currentSurahNumber === nextSurahNumber) {
                    return;
                }

                parts.push(`~ [${this.surahLabel(currentSurahNumber)}]`);
            });

            return normalizeTextValue(parts.join(' '));
        },

        composeDraggedAyahSelectionText() {
            if (
                !Array.isArray(this.wordPress.trailAyahIndexes) ||
                this.wordPress.trailAyahIndexes.length < 1
            ) {
                return null;
            }

            const normalizedAyahIndexes = this.selectedDraggedAyahIndexes();

            if (normalizedAyahIndexes.length < 1) {
                return null;
            }

            const shouldAppendAyahSplitters = normalizedAyahIndexes.length > 1;
            const shouldAppendSurahAffixes = this.shouldAppendDraggedSurahAffix();
            const parts = [];

            normalizedAyahIndexes.forEach((ayahIndex, ayahIndexPosition) => {
                const ayahText = this.extractAyahText(ayahIndex);

                if (!ayahText) {
                    return;
                }

                parts.push(ayahText);

                if (!shouldAppendAyahSplitters) {
                    if (!shouldAppendSurahAffixes) {
                        return;
                    }
                } else {
                    const splitter = this.ayahSplitterToken(ayahIndex);

                    if (splitter) {
                        parts.push(splitter);
                    }
                }

                if (!shouldAppendSurahAffixes) {
                    return;
                }

                const currentSurahNumber = this.surahNumberForAyahIndex(ayahIndex);
                const nextAyahIndex = normalizedAyahIndexes[ayahIndexPosition + 1] ?? 0;
                const nextSurahNumber = this.surahNumberForAyahIndex(nextAyahIndex);

                if (currentSurahNumber < 1 || currentSurahNumber === nextSurahNumber) {
                    return;
                }

                parts.push(`~ [${this.surahLabel(currentSurahNumber)}]`);
            });

            return normalizeTextValue(parts.join(' '));
        },

        composeDraggedSelectionText() {
            const selectionText = this.interactionTargetsWords()
                ? this.composeDraggedWordSelectionText()
                : this.composeDraggedAyahSelectionText();

            const normalizedSelectionText = normalizeTextValue(selectionText);

            if (!normalizedSelectionText) {
                return null;
            }

            return normalizedSelectionText;
        },

        extractWordText(word) {
            const copyText = normalizeTextValue(word?.copy_text);

            if (copyText && !hasArabicPresentationForms(copyText)) {
                return copyText;
            }

            const displayText = normalizeTextValue(word?.text);

            if (displayText && !hasArabicPresentationForms(displayText)) {
                return displayText;
            }

            const canonicalAyahText = normalizeTextValue(word?.ayah_copy_text);

            if (canonicalAyahText) {
                return canonicalAyahText;
            }

            return copyText ?? displayText;
        },

        canonicalAyahCopyText(ayahIndex) {
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));

            if (normalizedAyahIndex < 1 || !Array.isArray(this.mushafLines)) {
                return null;
            }

            for (const line of this.mushafLines) {
                if (Array.isArray(line?.segments)) {
                    for (const segment of line.segments) {
                        const segmentAyahIndex = Math.max(
                            0,
                            Math.trunc(Number(segment?.ayah_index ?? 0)),
                        );

                        if (segmentAyahIndex !== normalizedAyahIndex) {
                            continue;
                        }

                        const segmentAyahCopyText = normalizeTextValue(segment?.ayah_copy_text);

                        if (segmentAyahCopyText) {
                            return segmentAyahCopyText;
                        }
                    }
                }

                if (Array.isArray(line?.words)) {
                    for (const word of line.words) {
                        const wordAyahIndex = Math.max(
                            0,
                            Math.trunc(Number(word?.ayah_index ?? 0)),
                        );

                        if (wordAyahIndex !== normalizedAyahIndex) {
                            continue;
                        }

                        const wordAyahCopyText = normalizeTextValue(word?.ayah_copy_text);

                        if (wordAyahCopyText) {
                            return wordAyahCopyText;
                        }
                    }
                }
            }

            return null;
        },

        ayahSegments(ayahIndex) {
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));

            if (normalizedAyahIndex < 1 || !Array.isArray(this.mushafLines)) {
                return [];
            }

            const segments = [];

            this.mushafLines.forEach((line) => {
                if (!Array.isArray(line?.segments)) {
                    return;
                }

                line.segments.forEach((segment) => {
                    const segmentAyahIndex = Math.max(
                        0,
                        Math.trunc(Number(segment?.ayah_index ?? 0)),
                    );

                    if (segmentAyahIndex !== normalizedAyahIndex) {
                        return;
                    }

                    const segmentText = normalizeTextValue(segment?.text);
                    const segmentCopyText = normalizeTextValue(segment?.copy_text);

                    if (!segmentText && !segmentCopyText) {
                        return;
                    }

                    segments.push(segmentCopyText ?? segmentText);
                });
            });

            return segments;
        },

        composeAyahTextFromWords(ayahIndex) {
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));

            if (normalizedAyahIndex < 1 || !Array.isArray(this.mushafLines)) {
                return null;
            }

            const words = [];

            this.mushafLines.forEach((line) => {
                if (!Array.isArray(line?.words)) {
                    return;
                }

                line.words.forEach((word) => {
                    const wordAyahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));

                    if (wordAyahIndex !== normalizedAyahIndex) {
                        return;
                    }

                    const text = normalizeTextValue(word?.text);
                    const copyText = normalizeTextValue(word?.copy_text);
                    const normalizedWordText = copyText ?? text;

                    if (!normalizedWordText) {
                        return;
                    }

                    words.push({
                        text: normalizedWordText,
                        joinWithoutSpace: Boolean(word?.is_glyph) && !copyText,
                    });
                });
            });

            if (words.length < 1) {
                return null;
            }

            let joined = '';

            words.forEach((word, index) => {
                if (index === 0) {
                    joined = word.text;

                    return;
                }

                joined += word.joinWithoutSpace ? word.text : ` ${word.text}`;
            });

            return normalizeTextValue(joined);
        },

        extractAyahText(ayahIndex) {
            const canonicalAyahText = this.canonicalAyahCopyText(ayahIndex);

            if (canonicalAyahText) {
                return canonicalAyahText;
            }

            const segments = this.ayahSegments(ayahIndex);

            if (segments.length > 0) {
                return normalizeTextValue(segments.join(' '));
            }

            return this.composeAyahTextFromWords(ayahIndex);
        },

        fallbackCopyText(text) {
            if (typeof document === 'undefined') {
                return false;
            }

            const textarea = document.createElement('textarea');
            textarea.value = String(text ?? '');
            textarea.setAttribute('readonly', '');
            textarea.style.position = 'fixed';
            textarea.style.top = '-1000px';
            textarea.style.left = '-1000px';
            textarea.style.opacity = '0';
            document.body.appendChild(textarea);

            const selection = window.getSelection?.() ?? null;
            const originalRange =
                selection && selection.rangeCount > 0 ? selection.getRangeAt(0) : null;

            textarea.focus();
            textarea.select();
            textarea.setSelectionRange(0, textarea.value.length);

            let copied = false;

            try {
                copied = Boolean(document.execCommand?.('copy'));
            } catch (_) {
                copied = false;
            }

            textarea.remove();

            if (selection && originalRange) {
                selection.removeAllRanges();
                selection.addRange(originalRange);
            }

            return copied;
        },

        normalizeCopiedText(text) {
            const normalizedText = normalizeTextValue(text);

            if (!normalizedText) {
                return null;
            }

            if (this.doesPreserveHarakatOnCopy) {
                return normalizedText;
            }

            const withoutHarakat = normalizeTextValue(stripArabicHarakat(normalizedText));

            return withoutHarakat ?? normalizedText;
        },

        async writeClipboardText(text) {
            const normalizedText = this.normalizeCopiedText(text);

            if (!normalizedText) {
                return false;
            }

            if (
                typeof navigator !== 'undefined' &&
                navigator.clipboard &&
                typeof navigator.clipboard.writeText === 'function'
            ) {
                try {
                    await navigator.clipboard.writeText(normalizedText);

                    return true;
                } catch (_) {
                    return this.fallbackCopyText(normalizedText);
                }
            }

            return this.fallbackCopyText(normalizedText);
        },

        showCopyFeedback(anchor = null) {
            const point = this.copyPointFromAnchor(anchor);

            if (!point) {
                return;
            }

            this.copyFeedback.x = point.x;
            this.copyFeedback.y = point.y;
            this.copyFeedback.visible = true;
            this.copyFeedback.serial += 1;
            const serial = this.copyFeedback.serial;

            if (this.copyFeedback.timer !== null) {
                clearTimeout(this.copyFeedback.timer);
            }

            this.copyFeedback.timer = window.setTimeout(() => {
                if (this.copyFeedback.serial !== serial) {
                    return;
                }

                this.copyFeedback.visible = false;
                this.copyFeedback.timer = null;
            }, copyPopoverVisibleDurationMs);
        },

        hideCopyFeedback() {
            if (this.copyFeedback.timer !== null) {
                clearTimeout(this.copyFeedback.timer);
                this.copyFeedback.timer = null;
            }

            this.copyFeedback.visible = false;
        },

        showWirdCompletionFeedback({ durationMs = wirdCompletionVisibleDurationMs } = {}) {
            if (this._wirdCompletionTimer !== null) {
                clearTimeout(this._wirdCompletionTimer);
                this._wirdCompletionTimer = null;
            }

            this.isWirdCompletionVisible = true;

            if (this.isWirdCompletionPreviewPinned) {
                return;
            }

            this._wirdCompletionTimer = window.setTimeout(
                () => {
                    this._wirdCompletionTimer = null;
                    this.isWirdCompletionVisible = false;
                },
                Math.max(1200, Math.trunc(Number(durationMs) || wirdCompletionVisibleDurationMs)),
            );
        },

        openWirdCompletionPreview() {
            if (this._wirdCompletionTimer !== null) {
                clearTimeout(this._wirdCompletionTimer);
                this._wirdCompletionTimer = null;
            }

            this.isWirdCompletionPreviewPinned = true;
            this.isWirdCompletionVisible = true;
        },

        closeWirdCompletionPreview() {
            if (this._wirdCompletionTimer !== null) {
                clearTimeout(this._wirdCompletionTimer);
                this._wirdCompletionTimer = null;
            }

            this.isWirdCompletionPreviewPinned = false;
            this.isWirdCompletionVisible = false;
        },

        handleWirdCompletionPreviewEvent(detail = {}) {
            const mode = String(detail?.mode ?? 'open')
                .trim()
                .toLowerCase();

            if (mode === 'close') {
                this.closeWirdCompletionPreview();

                return;
            }

            if (mode === 'toggle') {
                if (this.isWirdCompletionPreviewPinned) {
                    this.closeWirdCompletionPreview();
                } else {
                    this.openWirdCompletionPreview();
                }

                return;
            }

            this.openWirdCompletionPreview();
        },

        async copyWordSelection(word, activationAnchor = null) {
            const wordText = this.extractWordText(word);
            const copied = await this.writeClipboardText(wordText);

            if (copied) {
                this.applyCopiedHighlights({ words: [word] });
                this.showCopyFeedback(activationAnchor);
            }
        },

        async copyAyahSelection(ayahIndex, activationAnchor = null) {
            const ayahText = this.extractAyahText(ayahIndex);
            const copied = await this.writeClipboardText(ayahText);

            if (copied) {
                this.applyCopiedHighlights({ ayahIndexes: [ayahIndex] });
                this.showCopyFeedback(activationAnchor);
            }
        },

        async copyDraggedSelection(activationAnchor = null) {
            const draggedText = this.composeDraggedSelectionText();
            const copiedWords = this.interactionTargetsWords()
                ? this.wordPress.trailWords.slice()
                : [];
            const copiedAyahIndexes = this.interactionTargetsWords()
                ? []
                : this.wordPress.trailAyahIndexes.slice();
            const copied = await this.writeClipboardText(draggedText);

            if (copied) {
                this.applyCopiedHighlights({
                    words: copiedWords,
                    ayahIndexes: copiedAyahIndexes,
                });
                this.showCopyFeedback(activationAnchor);
            }
        },

        clearWordPressState() {
            if (this._wordPressHoldTimer !== null) {
                clearTimeout(this._wordPressHoldTimer);
                this._wordPressHoldTimer = null;
            }

            this.wordPress.active = false;
            this.wordPress.pointerId = null;
            this.wordPress.startX = 0;
            this.wordPress.startY = 0;
            this.wordPress.holdTriggered = false;
            this.wordPress.isSecondTap = false;
            this.wordPress.word = null;
            this.wordPress.target = null;
            this.wordPress.dragActive = false;
            this.wordPress.trailWordKeys = [];
            this.wordPress.trailWords = [];
            this.wordPress.trailAyahIndexes = [];
            this.wordPress.lastAnchor = null;
        },

        onWordPointerDown(event, word) {
            if (!this.isSelectableWord(word)) {
                this.clearWordPressState();

                return;
            }

            if (this.usesMobileDoubleTapCopyMode()) {
                this.onSwipeStart(event);
            }

            this.setWordClickSuppression(false);

            const point = this.swipePoint(event);

            if (!point) {
                this.clearWordPressState();

                return;
            }

            this.clearWordPressState();
            this.wordPress.active = true;
            this.wordPress.pointerId = point.pointerId;
            this.wordPress.startX = point.x;
            this.wordPress.startY = point.y;
            this.wordPress.holdTriggered = false;
            this.wordPress.isSecondTap = false;
            this.wordPress.word = word;
            this.wordPress.target =
                event?.currentTarget instanceof Element
                    ? event.currentTarget
                    : event?.target instanceof Element
                      ? event.target
                      : null;
            this.wordPress.dragActive = false;
            this.wordPress.trailWordKeys = [];
            this.wordPress.trailWords = [];
            this.wordPress.trailAyahIndexes = [];
            this.wordPress.lastAnchor = null;
            this.collectWordPressTrailWord(word, {
                x: point.x,
                y: point.y,
                target: this.wordPress.target,
            });
            const useMobileDoubleTapCopyMode = this.usesMobileDoubleTapCopyMode();
            const wordSelectionKey = this.wordSelectionKeyFromMeta(
                this.normalizeSelectableWordMeta(word),
            );

            if (useMobileDoubleTapCopyMode) {
                const now = Date.now();
                const hasRecentTapWindow =
                    now - this._lastMobileCopyTapAt <= mobileDoubleTapCopyWindowMs;
                const hasPreviousTap =
                    this._lastMobileCopyTapAt > 0 && this._lastMobileCopyTapWordKey !== null;
                const isSecondTap =
                    typeof wordSelectionKey === 'string' &&
                    wordSelectionKey !== '' &&
                    this._lastMobileCopyTapWordKey === wordSelectionKey &&
                    hasRecentTapWindow;

                const shouldTreatAsSecondTap =
                    isSecondTap || (hasRecentTapWindow && hasPreviousTap);

                this.wordPress.isSecondTap = shouldTreatAsSecondTap;

                return;
            }

            this._wordPressHoldTimer = window.setTimeout(() => {
                if (!this.wordPress.active || !this.wordPress.word) {
                    return;
                }

                this.wordPress.holdTriggered = true;
                this.setWordClickSuppression(true);
                this._lastWordHoldAt = Date.now();
                this.selectHoldSegment(this.wordPress.word, {
                    x: this.wordPress.startX,
                    y: this.wordPress.startY,
                    target: this.wordPress.target,
                });
            }, wordPressHoldDelayMs);
        },

        onWordPointerMove(event) {
            if (!this.wordPress.active || this.wordPress.holdTriggered) {
                return;
            }

            if (this.usesMobileDoubleTapCopyMode() && !this.wordPress.isSecondTap) {
                void this.onSwipeMove(event);
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            if (
                this.wordPress.pointerId !== null &&
                point.pointerId !== null &&
                this.wordPress.pointerId !== point.pointerId
            ) {
                return;
            }

            const deltaX = Math.abs(point.x - this.wordPress.startX);
            const deltaY = Math.abs(point.y - this.wordPress.startY);

            if (!this.wordPress.dragActive) {
                if (deltaX <= wordPressDragThresholdPx && deltaY <= wordPressDragThresholdPx) {
                    return;
                }

                this.wordPress.dragActive = true;

                if (this._wordPressHoldTimer !== null) {
                    clearTimeout(this._wordPressHoldTimer);
                    this._wordPressHoldTimer = null;
                }
            }

            const buttonAtPoint = this.wordButtonElementFromPoint(point.x, point.y);
            const hoveredWord = this.wordFromButtonElement(buttonAtPoint);
            const activationAnchor = {
                x: point.x,
                y: point.y,
                target:
                    buttonAtPoint ??
                    (event?.currentTarget instanceof Element ? event.currentTarget : null),
            };

            if (hoveredWord) {
                this.collectWordPressTrailWord(hoveredWord, activationAnchor);
                this.setHoveredSegment(hoveredWord);
            } else {
                this.wordPress.lastAnchor = activationAnchor;
            }
        },

        onWordPointerUp(event = null) {
            if (!this.wordPress.active) {
                this.clearWordPressState();

                return;
            }

            const point = this.swipePoint(event);
            const movedBeyondTapThreshold =
                point !== null
                    ? Math.abs(point.x - this.wordPress.startX) > wordPressDragThresholdPx ||
                      Math.abs(point.y - this.wordPress.startY) > wordPressDragThresholdPx
                    : false;

            if (
                this.usesMobileDoubleTapCopyMode() &&
                !this.wordPress.isSecondTap &&
                !this.wordPress.holdTriggered
            ) {
                void this.onSwipeEnd(event);
            }

            if (
                this.wordPress.pointerId !== null &&
                point?.pointerId !== null &&
                this.wordPress.pointerId !== point.pointerId
            ) {
                return;
            }

            if (this.wordPress.dragActive) {
                const shouldCopyDraggedSelection =
                    !this.usesMobileDoubleTapCopyMode() || this.wordPress.isSecondTap;
                let activationAnchor = this.activationAnchorFromEvent(event);
                let shouldSuppressNextWordClick = false;

                if (point) {
                    const buttonAtPoint = this.wordButtonElementFromPoint(point.x, point.y);
                    const releaseWord = this.wordFromButtonElement(buttonAtPoint);
                    const releaseAnchor = {
                        x: point.x,
                        y: point.y,
                        target: buttonAtPoint ?? activationAnchor?.target ?? null,
                    };

                    if (releaseWord) {
                        this.collectWordPressTrailWord(releaseWord, releaseAnchor);
                        shouldSuppressNextWordClick = true;
                    } else {
                        this.wordPress.lastAnchor = releaseAnchor;
                    }

                    activationAnchor = releaseAnchor;
                }

                if (
                    !shouldSuppressNextWordClick &&
                    activationAnchor?.target instanceof Element &&
                    activationAnchor.target.closest('[data-quran-word-button]')
                ) {
                    shouldSuppressNextWordClick = true;
                }

                if (shouldCopyDraggedSelection) {
                    void this.copyDraggedSelection(
                        activationAnchor ??
                            this.wordPress.lastAnchor ?? {
                                x: this.wordPress.startX,
                                y: this.wordPress.startY,
                                target: this.wordPress.target,
                            },
                    );
                }
                this.setWordClickSuppression(shouldSuppressNextWordClick);
            }

            if (!this.wordPress.dragActive && this.usesMobileDoubleTapCopyMode()) {
                const currentWord = this.wordPress.word;
                const wordSelectionKey =
                    currentWord === null
                        ? null
                        : this.wordSelectionKeyFromMeta(
                              this.normalizeSelectableWordMeta(currentWord),
                          );

                if (
                    this.wordPress.isSecondTap &&
                    !this.wordPress.holdTriggered &&
                    this.wordPress.word
                ) {
                    this.selectDefaultSegment(
                        this.wordPress.word,
                        this.activationAnchorFromEvent(event) ?? {
                            x: this.wordPress.startX,
                            y: this.wordPress.startY,
                            target: this.wordPress.target,
                        },
                    );
                    this.setWordClickSuppression(true, { durationMs: 520 });
                    this._lastMobileCopyTapAt = 0;
                    this._lastMobileCopyTapWordKey = null;
                } else if (
                    !this.wordPress.isSecondTap &&
                    !movedBeyondTapThreshold &&
                    typeof wordSelectionKey === 'string' &&
                    wordSelectionKey !== ''
                ) {
                    this._lastMobileCopyTapAt = Date.now();
                    this._lastMobileCopyTapWordKey = wordSelectionKey;
                    this.setWordClickSuppression(true, { durationMs: 360 });
                }
            }

            if (this.wordPress.holdTriggered) {
                this.setWordClickSuppression(true, {
                    durationMs: 520,
                });
                this._lastMobileCopyTapAt = 0;
                this._lastMobileCopyTapWordKey = null;
            }

            this.clearWordPressState();
        },

        onWordPointerCancel() {
            this.clearWordPressState();
        },

        onWordPointerLeave(word) {
            this.clearHoveredSegment(word);
        },

        onWordClick(event, word) {
            if (this.usesMobileDoubleTapCopyMode()) {
                event?.preventDefault?.();

                return;
            }

            if (this._suppressNextWordClick) {
                event?.preventDefault?.();
                this.setWordClickSuppression(false);

                return;
            }

            this.selectDefaultSegment(word, this.activationAnchorFromEvent(event));
        },

        setHoveredSegment(word) {
            if (this.interactionTargetsWords()) {
                const wordIndex = Number(word?.word_index ?? 0);

                if (Number.isFinite(wordIndex) && wordIndex > 0) {
                    this.hoveredWordIndex = Math.trunc(wordIndex);
                    this.hoveredAyahIndex = 0;
                }

                return;
            }

            this.setHoveredAyah(Number(word?.ayah_index ?? 0));
            this.hoveredWordIndex = 0;
        },

        clearHoveredSegment(word = null) {
            if (word === null) {
                this.hoveredAyahIndex = 0;
                this.hoveredWordIndex = 0;

                return;
            }

            if (this.interactionTargetsWords()) {
                const wordIndex = Number(word?.word_index ?? 0);

                if (Number.isFinite(wordIndex) && this.hoveredWordIndex === Math.trunc(wordIndex)) {
                    this.hoveredWordIndex = 0;
                }

                return;
            }

            this.clearHoveredAyah(Number(word?.ayah_index ?? 0));
        },

        clearAyahSelectionOnBackground(event) {
            if (event?.target?.closest?.('.quran-word-button')) {
                return;
            }

            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;
        },

        isRectangularAyahLine(line) {
            return line?.line_type === 'ayah' && !this.useCenteredAyahLayout;
        },

        lineAlignmentClass(line) {
            if (this.isRectangularAyahLine(line)) {
                return 'text-right';
            }

            if (Boolean(line?.is_centered)) {
                return 'text-center';
            }

            return '';
        },

        lineWordGapExtraEm(line) {
            if (!this.isRectangularAyahLine(line)) {
                return 0;
            }

            const lineNumber = Math.max(0, Math.trunc(Number(line?.line_number ?? 0)));
            const rawExtraGap = Number(this.lineWordGapAdjustments?.[lineNumber] ?? 0);

            if (!Number.isFinite(rawExtraGap) || rawExtraGap <= 0) {
                return 0;
            }

            return Math.max(0, Math.min(0.16, rawExtraGap));
        },

        rebalanceRectangularAyahLineWordSpacing() {
            const currentPageNumber = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));

            if (this._lastWordGapRebalancedPageNumber === currentPageNumber) {
                return;
            }

            this._lastWordGapRebalancedPageNumber = currentPageNumber;

            if (this.useCenteredAyahLayout || !Array.isArray(this.mushafLines)) {
                this.lineWordGapAdjustments = {};

                return;
            }

            const contentElement = this.$refs.pageContent;

            if (!(contentElement instanceof Element)) {
                this.lineWordGapAdjustments = {};

                return;
            }

            const ayahLinesByNumber = new Map(
                this.mushafLines
                    .filter(
                        (line) =>
                            String(line?.line_type ?? '') === 'ayah' &&
                            !Boolean(line?.is_centered) &&
                            Array.isArray(line?.words),
                    )
                    .map((line) => [Math.max(0, Math.trunc(Number(line?.line_number ?? 0))), line]),
            );

            if (ayahLinesByNumber.size < 2) {
                this.lineWordGapAdjustments = {};

                return;
            }

            const lineElements = Array.from(
                contentElement.querySelectorAll('[data-quran-line][data-quran-line-type="ayah"]'),
            );
            const measurements = [];

            lineElements.forEach((lineElement) => {
                const lineNumber = Math.max(
                    0,
                    Math.trunc(Number(lineElement.getAttribute('data-quran-line-number') ?? 0)),
                );
                const line = ayahLinesByNumber.get(lineNumber);
                const textElement = lineElement.querySelector('[data-quran-line-text]');

                if (!line || !(textElement instanceof Element)) {
                    return;
                }

                const lineWidth = Number(textElement.getBoundingClientRect().width ?? 0);

                if (!Number.isFinite(lineWidth) || lineWidth <= 1) {
                    return;
                }

                const words = Array.isArray(line?.words) ? line.words : [];
                const wordCount = words.length;
                const gapCount = Math.max(0, wordCount - 1);

                if (gapCount < 1) {
                    return;
                }

                const computedStyle = window.getComputedStyle(textElement);
                const fontSize = Math.max(
                    8,
                    Number.parseFloat(computedStyle.fontSize || '16') || 16,
                );

                measurements.push({
                    lineNumber,
                    width: lineWidth,
                    gapCount,
                    fontSize,
                });
            });

            if (measurements.length < 2) {
                this.lineWordGapAdjustments = {};

                return;
            }

            const sortedWidths = measurements
                .map((entry) => entry.width)
                .sort((first, second) => first - second);
            const targetWidth =
                sortedWidths[Math.floor((sortedWidths.length - 1) * 0.88)] ??
                sortedWidths[sortedWidths.length - 1] ??
                0;
            const gapAdjustments = {};

            measurements.forEach((entry) => {
                const widthDeficit = targetWidth - entry.width;

                if (widthDeficit <= 2) {
                    return;
                }

                const extraGapPx = widthDeficit / entry.gapCount;
                const normalizedGapEm = Math.max(0, Math.min(0.16, extraGapPx / entry.fontSize));

                if (normalizedGapEm <= 0.003) {
                    return;
                }

                gapAdjustments[entry.lineNumber] = Number(normalizedGapEm.toFixed(4));
            });

            this.lineWordGapAdjustments = gapAdjustments;
        },

        lineEntryStyle(line) {
            const lineNumber = Math.max(0, Number(line?.line_number ?? 0));
            const marginBlockStart = this.lineMarginBlockStart(line);
            const marginBlockEnd = this.lineMarginBlockEnd(line);
            const wordGapExtra = this.lineWordGapExtraEm(line);

            return `--quran-line-index: ${lineNumber}; --quran-word-gap-extra: ${wordGapExtra}em; margin-block-start: ${marginBlockStart}; margin-block-end: ${marginBlockEnd};`;
        },

        isDenseFullLinePage() {
            const lines = Array.isArray(this.mushafLines) ? this.mushafLines : [];

            if (lines.length < 1) {
                return false;
            }

            const ayahLines = lines.filter((line) => String(line?.line_type ?? '') === 'ayah');
            const ayahLineCount = ayahLines.length;

            if (ayahLineCount < 14) {
                return false;
            }

            const surahHeaderCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'surah_name' && this.shouldRenderLine(line),
            ).length;
            const basmallahCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'basmallah' && this.shouldRenderLine(line),
            ).length;

            if (surahHeaderCount > 0 || basmallahCount > 0) {
                return false;
            }

            return true;
        },
    };
};
