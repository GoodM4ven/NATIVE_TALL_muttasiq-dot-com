export const createLineLayoutRenderCoreModule = (deps) => {
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
        isMultiSurahSegmentedPage() {
            const lines = Array.isArray(this.mushafLines) ? this.mushafLines : [];

            if (lines.length < 1) {
                return false;
            }

            const renderedSurahHeaderCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'surah_name' && this.shouldRenderLine(line),
            ).length;
            const renderedBasmallahCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'basmallah' && this.shouldRenderLine(line),
            ).length;
            const ayahLineCount = lines.filter(
                (line) => String(line?.line_type ?? '') === 'ayah',
            ).length;

            return (
                renderedSurahHeaderCount >= 2 && renderedBasmallahCount >= 2 && ayahLineCount >= 6
            );
        },

        isSingleHeaderLongContentPage() {
            const lines = Array.isArray(this.mushafLines) ? this.mushafLines : [];

            if (lines.length < 1) {
                return false;
            }

            const renderedSurahHeaderCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'surah_name' && this.shouldRenderLine(line),
            ).length;
            const renderedBasmallahCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'basmallah' && this.shouldRenderLine(line),
            ).length;
            const ayahLineCount = lines.filter(
                (line) => String(line?.line_type ?? '') === 'ayah',
            ).length;

            return (
                renderedSurahHeaderCount === 1 && renderedBasmallahCount <= 1 && ayahLineCount >= 10
            );
        },

        isSingleHeaderLongCompactPage() {
            const lines = Array.isArray(this.mushafLines) ? this.mushafLines : [];

            if (lines.length < 1) {
                return false;
            }

            const renderedSurahHeaderCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'surah_name' && this.shouldRenderLine(line),
            ).length;
            const renderedBasmallahCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'basmallah' && this.shouldRenderLine(line),
            ).length;
            const ayahLineCount = lines.filter(
                (line) => String(line?.line_type ?? '') === 'ayah',
            ).length;

            return (
                renderedSurahHeaderCount === 1 &&
                renderedBasmallahCount <= 1 &&
                ayahLineCount === 13
            );
        },

        isDenseShortLinePage() {
            const lines = Array.isArray(this.mushafLines) ? this.mushafLines : [];

            if (lines.length < 1) {
                return false;
            }

            const ayahLineCount = lines.filter(
                (line) => String(line?.line_type ?? '') === 'ayah',
            ).length;

            if (ayahLineCount !== 14) {
                return false;
            }

            const renderedSurahHeaderCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'surah_name' && this.shouldRenderLine(line),
            ).length;
            const renderedBasmallahCount = lines.filter(
                (line) =>
                    String(line?.line_type ?? '') === 'basmallah' && this.shouldRenderLine(line),
            ).length;

            return renderedSurahHeaderCount === 0 && renderedBasmallahCount === 0;
        },

        isAyahLineWithWords(line) {
            return (
                String(line?.line_type ?? '') === 'ayah' &&
                Array.isArray(line?.words) &&
                line.words.length > 0
            );
        },

        lineText(line) {
            return String(line?.text ?? '').trim();
        },

        isBasmallahLine(line) {
            return String(line?.line_type ?? '') === 'basmallah';
        },

        preferredBasmallahText() {
            return String(this.basmallahText ?? '').trim();
        },

        shouldRenderConfiguredBasmallah() {
            const configuredText = this.preferredBasmallahText();

            if (configuredText === '') {
                return false;
            }

            const hasPrivateUseGlyphs = /[\uE000-\uF8FF]/u.test(configuredText);
            const configuredFamily = String(this.basmallahFontFamily ?? '').trim();

            if (hasPrivateUseGlyphs && configuredFamily === '') {
                return false;
            }

            return true;
        },

        isBasmallahLineWithWords(line) {
            return (
                this.isBasmallahLine(line) &&
                !this.shouldRenderConfiguredBasmallah() &&
                Array.isArray(line?.words) &&
                line.words.length > 0
            );
        },

        lineByNumber(lineNumber) {
            const normalizedLineNumber = Math.max(0, Math.trunc(Number(lineNumber) || 0));

            if (normalizedLineNumber < 1 || !Array.isArray(this.mushafLines)) {
                return null;
            }

            return (
                this.mushafLines.find(
                    (entry) =>
                        Math.max(0, Math.trunc(Number(entry?.line_number ?? 0))) ===
                        normalizedLineNumber,
                ) ?? null
            );
        },

        nextLineType(line) {
            const lineNumber = Math.max(0, Math.trunc(Number(line?.line_number ?? 0)));
            const nextLine = this.lineByNumber(lineNumber + 1);

            return String(nextLine?.line_type ?? '');
        },

        previousLine(line) {
            const lineNumber = Math.max(0, Math.trunc(Number(line?.line_number ?? 0)));

            if (lineNumber <= 1) {
                return null;
            }

            return this.lineByNumber(lineNumber - 1);
        },

        previousRenderableLine(line) {
            const lineNumber = Math.max(0, Math.trunc(Number(line?.line_number ?? 0)));
            const maxIterations = Math.max(0, this.mushafLines.length + 4);
            let candidateLineNumber = lineNumber - 1;
            let iterations = 0;

            while (candidateLineNumber >= 1 && iterations < maxIterations) {
                const candidateLine = this.lineByNumber(candidateLineNumber);

                if (candidateLine && this.shouldRenderLine(candidateLine)) {
                    return candidateLine;
                }

                candidateLineNumber -= 1;
                iterations += 1;
            }

            return null;
        },

        nextRenderableLine(line) {
            const lineNumber = Math.max(0, Math.trunc(Number(line?.line_number ?? 0)));
            const maxLineNumber = Math.max(
                0,
                ...this.mushafLines.map((entry) =>
                    Math.max(0, Math.trunc(Number(entry?.line_number ?? 0))),
                ),
            );
            const maxIterations = Math.max(0, this.mushafLines.length + 4);
            let candidateLineNumber = lineNumber + 1;
            let iterations = 0;

            while (candidateLineNumber <= maxLineNumber && iterations < maxIterations) {
                const candidateLine = this.lineByNumber(candidateLineNumber);

                if (candidateLine && this.shouldRenderLine(candidateLine)) {
                    return candidateLine;
                }

                candidateLineNumber += 1;
                iterations += 1;
            }

            return null;
        },

        resolvedLineSurahNumber(line) {
            const lineSurahNumber = Math.max(0, Math.trunc(Number(line?.surah_number ?? 0)));

            if (lineSurahNumber > 0) {
                return lineSurahNumber;
            }

            if (!Array.isArray(line?.words) || line.words.length < 1) {
                return 0;
            }

            const firstWordWithSurah = line.words.find((word) => {
                const wordSurahNumber = Math.max(0, Math.trunc(Number(word?.surah_number ?? 0)));

                return wordSurahNumber > 0;
            });

            return Math.max(0, Math.trunc(Number(firstWordWithSurah?.surah_number ?? 0)));
        },

        nearestPreviousSurahHeaderNumber(line) {
            const lineNumber = Math.max(0, Math.trunc(Number(line?.line_number ?? 0)));

            if (
                lineNumber <= 1 ||
                !Array.isArray(this.mushafLines) ||
                this.mushafLines.length < 1
            ) {
                return 0;
            }

            const previousSurahHeaderLine = this.mushafLines
                .filter((entry) => {
                    const entryLineNumber = Math.max(
                        0,
                        Math.trunc(Number(entry?.line_number ?? 0)),
                    );

                    return entryLineNumber > 0 && entryLineNumber < lineNumber;
                })
                .filter((entry) => String(entry?.line_type ?? '') === 'surah_name')
                .sort((left, right) => {
                    const leftNumber = Math.max(0, Math.trunc(Number(left?.line_number ?? 0)));
                    const rightNumber = Math.max(0, Math.trunc(Number(right?.line_number ?? 0)));

                    return rightNumber - leftNumber;
                })[0];

            return this.resolvedLineSurahNumber(previousSurahHeaderLine);
        },

        isSurahHeaderFollowingPreviousSurahAyahOnSamePage(line) {
            if (!this.isSurahHeaderLine(line)) {
                return false;
            }

            const previousLine = this.previousRenderableLine(line);

            if (String(previousLine?.line_type ?? '') !== 'ayah') {
                return false;
            }

            const previousSurahNumberFromAyah = this.resolvedLineSurahNumber(previousLine);
            const previousSurahNumber =
                previousSurahNumberFromAyah > 0
                    ? previousSurahNumberFromAyah
                    : this.nearestPreviousSurahHeaderNumber(line);
            const currentSurahNumber = this.resolvedLineSurahNumber(line);

            return (
                previousSurahNumber > 0 &&
                currentSurahNumber > 0 &&
                previousSurahNumber !== currentSurahNumber
            );
        },

        surahHeaderTopPaddingWhenFollowingPreviousSurahAyahValue(line = null) {
            const configuredPadding = String(
                this.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah ?? '',
            ).trim();
            const defaultPadding =
                'var(--quran-surah-section-gap, calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * 0.56))';
            const basePadding = configuredPadding !== '' ? configuredPadding : defaultPadding;
            const isHeaderedLongCompactPage =
                typeof this.isSingleHeaderLongCompactPage === 'function' &&
                this.isSingleHeaderLongCompactPage();
            const isHeaderedLongPage =
                typeof this.isSingleHeaderLongContentPage === 'function' &&
                this.isSingleHeaderLongContentPage();
            const isTargetLineSurahHeader =
                line !== null &&
                typeof this.isSurahHeaderLine === 'function' &&
                this.isSurahHeaderLine(line);
            const gapScaleProperty =
                isHeaderedLongCompactPage && isTargetLineSurahHeader
                    ? '--quran-page-headered-long-compact-surah-section-gap-scale'
                    : isHeaderedLongPage && isTargetLineSurahHeader
                      ? '--quran-page-headered-long-surah-section-gap-scale'
                      : '--quran-surah-section-gap-scale';
            const gapScaleValue = `var(${gapScaleProperty}, 1)`;

            return `calc((${basePadding}) * ${gapScaleValue})`;
        },

        lineMarginBlockStart(line) {
            if (this.isSurahHeaderLine(line)) {
                if (this.isSurahHeaderFollowingPreviousSurahAyahOnSamePage(line)) {
                    return this.surahHeaderTopPaddingWhenFollowingPreviousSurahAyahValue(line);
                }

                return '0px';
            }

            if (this.isBasmallahLine(line)) {
                const previousLine = this.previousRenderableLine(line);

                if (this.isSurahHeaderLine(previousLine)) {
                    return 'var(--quran-basmallah-top-gap, calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * 0.12))';
                }

                return '0px';
            }

            return '0px';
        },

        lineMarginBlockEnd(line) {
            if (this.isSurahHeaderLine(line)) {
                const nextLineType = this.nextLineType(line);

                if (nextLineType === 'basmallah') {
                    return 'var(--quran-surah-header-basmallah-overlap, calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * -0.44))';
                }

                if (nextLineType === 'ayah' && this.isTawbahFirstPageSurahHeaderLine(line)) {
                    return 'calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * var(--quran-surah-header-no-basmallah-first-ayah-gap-scale, -0.1))';
                }

                return 'var(--quran-surah-header-bottom-trim, calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * -0.1))';
            }

            if (this.isBasmallahLine(line)) {
                const nextLine = this.nextRenderableLine(line);

                if (String(nextLine?.line_type ?? '') !== 'ayah') {
                    return '0px';
                }

                return 'calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * var(--quran-basmallah-bottom-gap-scale, 0.04))';
            }

            return '0px';
        },

        isTawbahFirstPageSurahHeaderLine(line) {
            if (!this.isSurahHeaderLine(line)) {
                return false;
            }

            const surahNumber = this.resolvedLineSurahNumber(line);

            if (surahNumber !== 9) {
                return false;
            }

            const currentPage = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const surahDirectoryEntry = this.surahDirectoryEntryBySurahNumber(9);
            const configuredFirstPage = Number(surahDirectoryEntry?.page_number ?? 0);

            if (configuredFirstPage > 0) {
                return currentPage === Math.max(1, Math.trunc(configuredFirstPage));
            }

            return true;
        },

        shouldRenderLine(line) {
            if (this.isAyahLineWithWords(line)) {
                return true;
            }

            if (this.isBasmallahLine(line)) {
                return true;
            }

            if (this.isSurahHeaderLine(line)) {
                return this.surahHeaderLineText(line) !== '';
            }

            return this.lineText(line) !== '';
        },

        metaLineStyle(line) {
            if (this.isBasmallahLine(line)) {
                return "font-family: 'Amiri', 'Traditional Arabic', serif; color: var(--quran-ink);";
            }

            return this.lineFontStyle();
        },

        ayahLineClass(line) {
            if (this.isRectangularAyahLine(line)) {
                return 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-rect font-quran';
            }

            return 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-centered font-quran';
        },

        lineWordClusters(line) {
            if (!Array.isArray(line?.words) || line.words.length < 1) {
                return [];
            }

            const clusters = [];
            let currentCluster = null;

            line.words.forEach((word, wordIndex) => {
                const ayahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));
                const wordIndexValue = Math.max(
                    0,
                    Math.trunc(Number(word?.word_index ?? wordIndex + 1)),
                );

                if (!currentCluster || currentCluster.ayahIndex !== ayahIndex) {
                    currentCluster = {
                        key: `${line?.line_number ?? 0}-${ayahIndex}-${wordIndexValue}`,
                        ayahIndex,
                        words: [],
                    };
                    clusters.push(currentCluster);
                }

                currentCluster.words.push(word);
            });

            return clusters;
        },

        isAyahClusterActive(cluster) {
            const ayahIndex = Math.max(0, Math.trunc(Number(cluster?.ayahIndex ?? 0)));

            if (!this.shouldPersistActivationIndexes()) {
                return false;
            }

            return this.activeAyahIndex > 0 && ayahIndex > 0 && this.activeAyahIndex === ayahIndex;
        },

        isAyahClusterSearchHighlighted(cluster) {
            const ayahIndex = Math.max(0, Math.trunc(Number(cluster?.ayahIndex ?? 0)));

            return (
                this.searchHighlightedAyahIndex > 0 &&
                ayahIndex > 0 &&
                this.searchHighlightedAyahIndex === ayahIndex
            );
        },

        isAyahClusterHovered(cluster) {
            const ayahIndex = Math.max(0, Math.trunc(Number(cluster?.ayahIndex ?? 0)));

            return (
                this.hoveredAyahIndex > 0 && ayahIndex > 0 && this.hoveredAyahIndex === ayahIndex
            );
        },

        lineFontStyle() {
            const family = String(this.qpcPageFontFamily ?? '').trim();

            if (!family) {
                return 'color: var(--quran-ink);';
            }

            return `font-family: '${family}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif; color: var(--quran-ink);`;
        },

        basmallahLineStyle(line) {
            const family = String(this.basmallahFontFamily ?? '').trim();

            if (!family) {
                return "font-family: 'Scheherazade New', 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif; color: var(--quran-ink); font-feature-settings: 'liga' 1, 'calt' 1;";
            }

            return `font-family: '${family}', 'Scheherazade New', 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif; color: var(--quran-ink); font-feature-settings: 'liga' 1, 'calt' 1;`;
        },

        basmallahDisplayText(line) {
            const configuredText = this.preferredBasmallahText();
            const text = this.lineText(line);
            const hasPrivateUseGlyphs = /[\uE000-\uF8FF]/u.test(text);

            const fallbackText = 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';

            if (this.shouldRenderConfiguredBasmallah()) {
                return configuredText;
            }

            if (text !== '' && !hasPrivateUseGlyphs) {
                return text;
            }

            return fallbackText;
        },

        surahHeaderLineStyle() {
            const family = String(this.surahHeaderFontFamily ?? '').trim();
            const styles = [];

            if (family) {
                styles.push(
                    `font-family: '${family}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;`,
                );
            }

            return styles.join(' ');
        },

        isWordActive(word) {
            const wordIndex = Number(word?.word_index ?? 0);

            if (!this.shouldPersistActivationIndexes()) {
                return false;
            }

            if (this.activeWordIndex > 0) {
                return wordIndex > 0 && wordIndex === this.activeWordIndex;
            }

            return false;
        },

        isWordHovered(word) {
            const wordIndex = Number(word?.word_index ?? 0);

            if (this.hoveredWordIndex > 0) {
                return wordIndex > 0 && wordIndex === this.hoveredWordIndex;
            }

            return false;
        },

        setHoveredAyah(ayahIndex) {
            const normalizedAyahIndex = Number(ayahIndex);

            if (!Number.isFinite(normalizedAyahIndex) || normalizedAyahIndex < 1) {
                return;
            }

            this.hoveredAyahIndex = Math.trunc(normalizedAyahIndex);
        },

        clearHoveredAyah(ayahIndex = null) {
            if (ayahIndex === null) {
                this.hoveredAyahIndex = 0;

                return;
            }

            const normalizedAyahIndex = Number(ayahIndex);

            if (
                Number.isFinite(normalizedAyahIndex) &&
                this.hoveredAyahIndex === Math.trunc(normalizedAyahIndex)
            ) {
                this.hoveredAyahIndex = 0;
            }
        },
    };
};
