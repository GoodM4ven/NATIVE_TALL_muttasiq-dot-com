export const createLineLayoutModule = (deps) => {
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

        surahHeaderTopPaddingWhenFollowingPreviousSurahAyahValue() {
            const configuredPadding = String(
                this.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah ?? '',
            ).trim();

            if (configuredPadding !== '') {
                return configuredPadding;
            }

            return 'var(--quran-surah-section-gap, calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier, 1) * 0.56))';
        },

        lineMarginBlockStart(line) {
            if (this.isSurahHeaderLine(line)) {
                if (this.isSurahHeaderFollowingPreviousSurahAyahOnSamePage(line)) {
                    return this.surahHeaderTopPaddingWhenFollowingPreviousSurahAyahValue();
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

        showAyahMarker(word) {
            return Boolean(word?.ends_ayah) && !Boolean(word?.is_glyph);
        },

        normalizeSearchQuery(value) {
            return String(value ?? '')
                .replace(/[\u200b-\u200f\u061c\u2066-\u2069\ufeff]/g, '')
                .replace(/\u0640/g, '')
                .replace(/[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED]/g, '')
                .replace(/[أإآٱ]/g, 'ا')
                .replace(/ى/g, 'ي')
                .replace(/ؤ/g, 'و')
                .replace(/ئ/g, 'ي')
                .replace(/[^\u0600-\u06FF\u0750-\u077F\u08A0-\u08FF\s]+/g, ' ')
                .replace(/\s+/g, ' ')
                .trim()
                .toLowerCase();
        },

        shouldShowSearchNoResults() {
            const normalizedQuery = this.normalizeSearchQuery(this.search.query);
            const rawQueryLength = String(this.search.query ?? '').trim().length;
            const hasReachedRawMinimum = rawQueryLength >= this.search.minQueryLength;

            return (
                !this.search.isLoading &&
                this.search.results.length === 0 &&
                this.search.lastCompletedNormalizedQuery === normalizedQuery &&
                (normalizedQuery.length >= this.search.minQueryLength ||
                    (hasReachedRawMinimum && normalizedQuery.length === 0))
            );
        },

        searchResultAyahText(result) {
            const uthmaniText = String(result?.text_uthmani ?? '').trim();

            if (uthmaniText !== '') {
                return uthmaniText
                    .replace(/\u0640/g, '')
                    .replace(/[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            return String(result?.text_searchable_typed ?? '')
                .replace(/\s+/g, ' ')
                .trim();
        },

        searchMatchTone(result) {
            const tone = String(result?.match_tone ?? '')
                .trim()
                .toLowerCase();

            if (['success', 'warning', 'info', 'danger'].includes(tone)) {
                return tone;
            }

            return 'warning';
        },

        searchMatchLabel(result) {
            const label = String(result?.match_label ?? '').trim();

            if (label !== '') {
                return label;
            }

            const strategy = String(result?.match_strategy ?? '').trim();

            if (strategy === 'exact_phrase') {
                return 'مطابقة تامة';
            }

            if (strategy === 'surah_exact') {
                return 'مطابقة اسم سورة';
            }

            if (strategy === 'surah_stem') {
                return 'مطابقة صرفية لاسم سورة';
            }

            if (strategy === 'exact_tokens') {
                return 'مطابقة كلمات';
            }

            if (strategy === 'stem_tokens') {
                return 'مطابقة صرفية';
            }

            if (strategy === 'root_tokens') {
                return 'مطابقة جذرية';
            }

            if (strategy === 'word_prefix') {
                return 'مطابقة تقريبية';
            }

            return 'مطابقة';
        },

        searchResultMetaLabel(result) {
            const surahNumber = Math.max(1, Math.trunc(Number(result?.surah_number ?? 1)));
            const pageNumber = Math.max(1, Math.trunc(Number(result?.page_number ?? 1)));
            const strategy = String(result?.match_strategy ?? '')
                .trim()
                .toLowerCase();

            if (strategy.startsWith('surah_')) {
                return `${this.surahLabel(surahNumber)} · صفحة ${pageNumber}`;
            }

            const ayahNumber = Math.max(1, Math.trunc(Number(result?.ayah_number ?? 1)));

            return `${this.surahLabel(surahNumber)} · آية ${ayahNumber} · صفحة ${pageNumber}`;
        },

        isSurahHeaderLine(line) {
            return String(line?.line_type ?? '') === 'surah_name';
        },

        cleanSurahHeaderText(value) {
            const normalized = String(value ?? '').trim();

            if (normalized === '') {
                return '';
            }

            return normalized
                .replace(/^سورة\s+/u, '')
                .replace(/^سور[ةه]\s+/u, '')
                .replace(/\(\s*\d+\s*\)\s*$/u, '')
                .replace(/^\(\s*\d+\s*\)\s*-\s*/u, '')
                .trim();
        },

        surahHeaderGlyph(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 0)));

            if (
                !Number.isFinite(normalizedSurahNumber) ||
                normalizedSurahNumber < 1 ||
                normalizedSurahNumber > 114
            ) {
                return '';
            }

            try {
                return String.fromCodePoint(0xe001 + normalizedSurahNumber - 1);
            } catch {
                return '';
            }
        },

        surahHeaderLineText(line) {
            const surahNumber = Math.max(
                1,
                Math.trunc(Number(line?.surah_number ?? this.currentSurahNumber())),
            );
            const glyph = this.surahHeaderGlyph(surahNumber);
            const hasSurahHeaderFont = String(this.surahHeaderFontFamily ?? '').trim() !== '';

            if (glyph !== '' && hasSurahHeaderFont) {
                return glyph;
            }

            const lineText = this.cleanSurahHeaderText(line?.text ?? '');

            if (lineText !== '') {
                return lineText;
            }

            const mappedName = this.surahNameOnly(surahNumber);

            if (mappedName !== '') {
                return mappedName;
            }

            return `(${surahNumber})`;
        },

        hasSurahHeaderFont() {
            return String(this.surahHeaderFontFamily ?? '').trim() !== '';
        },

        surahTileUsesGlyph(entry) {
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));
            const glyph = this.surahHeaderGlyph(surahNumber);

            return glyph !== '' && this.hasSurahHeaderFont();
        },

        surahTileLabel(entry) {
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));

            if (this.surahTileUsesGlyph(entry)) {
                return this.surahHeaderGlyph(surahNumber);
            }

            const name = this.surahNameOnly(surahNumber);

            if (name !== '') {
                return name;
            }

            return String(surahNumber);
        },

        surahTileLabelStyle(entry) {
            if (!this.surahTileUsesGlyph(entry)) {
                return '';
            }

            const family = String(this.surahHeaderFontFamily ?? '').trim();

            if (!family) {
                return '';
            }

            return `font-family: '${family}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;`;
        },

        directoryActiveSurahNumber() {
            const searchSurahNumber = Math.max(
                0,
                Math.trunc(Number(this.search?.activeSurahNumber ?? 0)),
            );

            if (searchSurahNumber > 0) {
                return searchSurahNumber;
            }

            const triggerSurahNumber = Math.max(
                0,
                Math.trunc(Number(this.surahTriggerSurahNumber ?? 0)),
            );

            if (triggerSurahNumber > 0) {
                return triggerSurahNumber;
            }

            return this.currentSurahNumber();
        },

        syncSearchActiveSurahNumber() {
            const activeSurahNumber = Math.max(
                1,
                Math.trunc(Number(this.currentSurahNumber() ?? 1)),
            );
            this.search.activeSurahNumber = activeSurahNumber;
        },

        isSurahDirectoryEntryActive(entry) {
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));

            return surahNumber === this.directoryActiveSurahNumber();
        },

        resolveSurahDirectoryGridElement() {
            const isElementInOpenModal = (element) => {
                if (!(element instanceof HTMLElement) || !element.isConnected) {
                    return false;
                }

                const modalElement = element.closest('.fi-modal');

                if (!(modalElement instanceof HTMLElement)) {
                    return true;
                }

                return modalElement.classList.contains('fi-modal-open');
            };

            const isElementVisible = (element) => {
                if (!(element instanceof HTMLElement)) {
                    return false;
                }

                const styles = window.getComputedStyle(element);

                return (
                    element.clientHeight > 16 &&
                    element.clientWidth > 16 &&
                    styles.display !== 'none' &&
                    styles.visibility !== 'hidden'
                );
            };

            const candidates = [];

            if (this.$refs.surahDirectoryGrid instanceof Element) {
                candidates.push(this.$refs.surahDirectoryGrid);
            }

            const modalWindow = this.searchModalWindowElement();

            if (modalWindow instanceof Element) {
                const modalScopedGrid = modalWindow.querySelector('[data-quran-surah-grid]');

                if (modalScopedGrid instanceof Element) {
                    candidates.push(modalScopedGrid);
                }
            }

            document
                .querySelectorAll('#quran-reader-search-modal [data-quran-surah-grid]')
                .forEach((node) => {
                    if (node instanceof Element) {
                        candidates.push(node);
                    }
                });

            const uniqueCandidates = Array.from(new Set(candidates));

            const visibleOpenModalCandidate = uniqueCandidates.find(
                (element) => isElementInOpenModal(element) && isElementVisible(element),
            );

            if (visibleOpenModalCandidate instanceof Element) {
                return visibleOpenModalCandidate;
            }

            const visibleCandidate = uniqueCandidates.find((element) => isElementVisible(element));

            if (visibleCandidate instanceof Element) {
                return visibleCandidate;
            }

            const openModalCandidate = uniqueCandidates.find((element) =>
                isElementInOpenModal(element),
            );

            return openModalCandidate ?? uniqueCandidates[0] ?? null;
        },

        scrollSurahDirectoryToActive({ behavior = 'smooth' } = {}) {
            const gridElement = this.resolveSurahDirectoryGridElement();

            if (!(gridElement instanceof HTMLElement) || !gridElement.isConnected) {
                return false;
            }

            if (gridElement.clientHeight < 16) {
                return false;
            }

            const activeSurahNumber = this.directoryActiveSurahNumber();
            const activeTile = gridElement.querySelector(
                `[data-surah-number="${activeSurahNumber}"]`,
            );

            if (!(activeTile instanceof Element)) {
                return false;
            }

            const beforeScrollTop = Math.max(0, Math.trunc(Number(gridElement.scrollTop ?? 0)));

            try {
                activeTile.scrollIntoView({
                    block: 'center',
                    inline: 'nearest',
                    behavior,
                });
            } catch (_) {
                activeTile.scrollIntoView();
            }

            const gridRect = gridElement.getBoundingClientRect();
            const tileRect = activeTile.getBoundingClientRect();
            const isTileVisible =
                tileRect.top >= gridRect.top - 4 && tileRect.bottom <= gridRect.bottom + 4;

            if (isTileVisible) {
                return true;
            }

            const tileTop = tileRect.top - gridRect.top + gridElement.scrollTop;
            const tileHeight = activeTile.clientHeight;
            const maxScrollTop = Math.max(0, gridElement.scrollHeight - gridElement.clientHeight);
            const targetScrollTop = tileTop - (gridElement.clientHeight - tileHeight) / 2;

            const normalizedScrollTop = Math.max(
                0,
                Math.min(maxScrollTop, Math.trunc(targetScrollTop)),
            );

            if (Math.abs(beforeScrollTop - normalizedScrollTop) <= 1) {
                return true;
            }

            if (typeof gridElement.scrollTo === 'function') {
                try {
                    gridElement.scrollTo({ top: normalizedScrollTop, behavior });
                } catch (_) {
                    gridElement.scrollTop = normalizedScrollTop;
                }
            } else {
                gridElement.scrollTop = normalizedScrollTop;
            }

            return true;
        },

        cancelSurahDirectoryAutoFocus() {
            this._surahDirectoryAutoFocusToken += 1;

            if (this._surahDirectoryAutoFocusTimer !== null) {
                clearTimeout(this._surahDirectoryAutoFocusTimer);
                this._surahDirectoryAutoFocusTimer = null;
            }

            if (this._surahDirectoryAutoFocusRaf !== null) {
                cancelAnimationFrame(this._surahDirectoryAutoFocusRaf);
                this._surahDirectoryAutoFocusRaf = null;
            }

            if (Array.isArray(this._surahDirectoryPostOpenTimers)) {
                this._surahDirectoryPostOpenTimers.forEach((timerId) => {
                    clearTimeout(timerId);
                });
                this._surahDirectoryPostOpenTimers = [];
            }
        },

        resolveActiveSurahDirectoryTile(gridElement = null) {
            const resolvedGridElement =
                gridElement instanceof Element
                    ? gridElement
                    : this.resolveSurahDirectoryGridElement();

            if (!(resolvedGridElement instanceof Element)) {
                return null;
            }

            const activeSurahNumber = this.directoryActiveSurahNumber();
            const activeTile = resolvedGridElement.querySelector(
                `[data-surah-number="${activeSurahNumber}"]`,
            );

            return activeTile instanceof HTMLElement ? activeTile : null;
        },

        queueSurahDirectoryAutoFocus() {
            this.cancelSurahDirectoryAutoFocus();

            const token = this._surahDirectoryAutoFocusToken;
            const attemptAutoFocus = (attempt = 0) => {
                const normalizedAttempt = Math.max(0, Math.trunc(Number(attempt) || 0));

                if (token !== this._surahDirectoryAutoFocusToken) {
                    return;
                }

                const modalIsVisible = this.search.modalOpen || this.isSearchModalWindowVisible();

                if (!modalIsVisible) {
                    if (normalizedAttempt >= 28) {
                        return;
                    }

                    this._surahDirectoryAutoFocusRaf = requestAnimationFrame(() => {
                        this._surahDirectoryAutoFocusRaf = null;
                        this._surahDirectoryAutoFocusTimer = window.setTimeout(
                            () => {
                                attemptAutoFocus(normalizedAttempt + 1);
                            },
                            normalizedAttempt < 8 ? 36 : 72,
                        );
                    });

                    return;
                }

                const gridElement = this.resolveSurahDirectoryGridElement();
                const activeTile = this.resolveActiveSurahDirectoryTile(gridElement);
                const isGridReady =
                    gridElement instanceof HTMLElement &&
                    activeTile instanceof HTMLElement &&
                    gridElement.clientHeight > 16 &&
                    activeTile.getClientRects().length > 0;

                if (isGridReady) {
                    this.scrollSurahDirectoryToActive({ behavior: 'auto' });
                    activeTile.focus({ preventScroll: true });

                    if (normalizedAttempt < 8) {
                        this._surahDirectoryAutoFocusTimer = window.setTimeout(
                            () => {
                                attemptAutoFocus(normalizedAttempt + 1);
                            },
                            normalizedAttempt === 0 ? 140 : 180,
                        );
                    }

                    return;
                }

                if (normalizedAttempt >= 28) {
                    return;
                }

                this._surahDirectoryAutoFocusRaf = requestAnimationFrame(() => {
                    this._surahDirectoryAutoFocusRaf = null;
                    this._surahDirectoryAutoFocusTimer = window.setTimeout(
                        () => {
                            attemptAutoFocus(normalizedAttempt + 1);
                        },
                        normalizedAttempt < 8 ? 36 : 72,
                    );
                });
            };

            attemptAutoFocus(0);
        },

        buildSurahDirectory(entries = null) {
            const sourceEntries = Array.isArray(entries) ? entries : this.search.surahDirectory;
            const firstPageBySurah = new Map();

            if (Array.isArray(sourceEntries) && sourceEntries.length > 0) {
                sourceEntries.forEach((entry) => {
                    const surahNumber = Number(entry?.surah_number ?? 0);
                    const pageNumber = Number(entry?.page_number ?? 0);

                    if (surahNumber < 1 || surahNumber > 114 || pageNumber < 1) {
                        return;
                    }

                    if (firstPageBySurah.has(surahNumber)) {
                        return;
                    }

                    firstPageBySurah.set(surahNumber, pageNumber);
                });
            }

            this.search.surahDirectory = Array.from({ length: 114 }, (_, index) => {
                const surahNumber = index + 1;

                return {
                    surah_number: surahNumber,
                    page_number: firstPageBySurah.get(surahNumber) ?? 1,
                };
            });

            if (this.search.modalOpen) {
                this.queueSurahDirectoryAutoFocus();
            }
        },

        deriveSurahDirectoryFromItems(items = []) {
            if (!Array.isArray(items) || items.length === 0) {
                return [];
            }

            const firstPageBySurah = new Map();

            items.forEach((item) => {
                const surahNumber = Number(item?.surah_number ?? 0);
                const pageNumber = Number(item?.page_number ?? item?.mushaf_page ?? 0);

                if (surahNumber < 1 || surahNumber > 114 || pageNumber < 1) {
                    return;
                }

                const normalizedSurahNumber = Math.trunc(surahNumber);
                const normalizedPageNumber = Math.trunc(pageNumber);
                const knownPage = firstPageBySurah.get(normalizedSurahNumber);

                if (knownPage === undefined || normalizedPageNumber < knownPage) {
                    firstPageBySurah.set(normalizedSurahNumber, normalizedPageNumber);
                }
            });

            return Array.from(firstPageBySurah.entries()).map(([surahNumber, pageNumber]) => ({
                surah_number: surahNumber,
                page_number: pageNumber,
            }));
        },

        surahLabel(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            const name = this.surahNameOnly(normalizedSurahNumber);

            if (name !== '') {
                return `سورة ${name}`;
            }

            return `سورة ${normalizedSurahNumber}`;
        },

        surahNameOnly(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            const names =
                Object.keys(this.search.surahNames ?? {}).length > 0
                    ? this.search.surahNames
                    : (this.initialPayload.surahNames ?? {});
            const rawName = String(names?.[normalizedSurahNumber] ?? '').trim();

            if (rawName !== '') {
                return rawName
                    .replace(/^سورة\s+/u, '')
                    .replace(/^سور[ةه]\s+/u, '')
                    .trim();
            }

            const headerLine = this.mushafLines.find((line) => {
                const lineSurahNumber = Number(line?.surah_number ?? 0);
                const lineType = String(line?.line_type ?? '');

                return lineSurahNumber === normalizedSurahNumber && lineType === 'surah_name';
            });
            const headerText = String(headerLine?.text ?? '').trim();

            if (headerText === '') {
                return '';
            }

            return headerText
                .replace(/^سورة\s+/u, '')
                .replace(/^سور[ةه]\s+/u, '')
                .replace(/\(\s*\d+\s*\)\s*$/u, '')
                .trim();
        },

        currentSurahNumber() {
            const firstAyahSurahNumber = this.firstAyahSurahNumberInPage();

            if (firstAyahSurahNumber > 0) {
                return firstAyahSurahNumber;
            }

            for (const line of this.mushafLines) {
                const lineSurahNumber = Number(line?.surah_number ?? 0);

                if (lineSurahNumber > 0) {
                    return lineSurahNumber;
                }
            }

            return 1;
        },

        firstAyahSurahNumberInPage() {
            for (const line of this.mushafLines) {
                if (String(line?.line_type ?? '') !== 'ayah') {
                    continue;
                }

                const lineSurahNumber = Number(line?.surah_number ?? 0);

                if (lineSurahNumber > 0) {
                    return lineSurahNumber;
                }

                if (!Array.isArray(line?.words)) {
                    continue;
                }

                for (const word of line.words) {
                    const wordAyahIndex = Number(word?.ayah_index ?? 0);
                    const wordSurahNumber = Number(word?.surah_number ?? 0);

                    if (wordAyahIndex > 0 && wordSurahNumber > 0) {
                        return wordSurahNumber;
                    }
                }
            }

            return 0;
        },

        currentSurahTitle() {
            return this.surahLabel(this.currentSurahNumber());
        },

        currentSurahTriggerLabel() {
            const currentSurahNumber = Math.max(
                1,
                Math.trunc(Number(this.currentSurahNumber() ?? 1)),
            );

            if (this.surahTriggerCaption !== '') {
                const captionSurahNumber = Math.max(
                    1,
                    Math.trunc(Number(this.surahTriggerSurahNumber ?? 1)),
                );

                if (captionSurahNumber === currentSurahNumber) {
                    return this.surahTriggerCaption;
                }
            }

            return this.resolveCurrentSurahTriggerLabel();
        },

        resolveCurrentSurahTriggerLabel() {
            const surahNumber = this.currentSurahNumber();
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            const surahName = this.surahNameOnly(normalizedSurahNumber);

            if (surahName !== '') {
                return `(${normalizedSurahNumber}) - ${surahName}`;
            }

            return `(${normalizedSurahNumber})`;
        },

        refreshSurahTriggerCaption(animate = true) {
            const nextCaption = this.resolveCurrentSurahTriggerLabel();
            const nextSurahNumber = Math.max(1, Math.trunc(Number(this.currentSurahNumber() ?? 1)));

            if (
                nextCaption === this.surahTriggerCaption &&
                this.surahTriggerCaption !== '' &&
                nextSurahNumber === this.surahTriggerSurahNumber
            ) {
                return;
            }

            if (this._surahTriggerTimer !== null) {
                clearTimeout(this._surahTriggerTimer);
                this._surahTriggerTimer = null;
            }

            if (this._surahTriggerCleanupTimer !== null) {
                clearTimeout(this._surahTriggerCleanupTimer);
                this._surahTriggerCleanupTimer = null;
            }

            if (!animate || this.surahTriggerCaption === '') {
                this.surahTriggerCaption = nextCaption;
                this.surahTriggerSurahNumber = nextSurahNumber;
                this.surahTriggerCaptionAnimClass = '';

                return;
            }

            const isForward = nextSurahNumber >= this.surahTriggerSurahNumber;
            const leaveClass = isForward
                ? 'quran-caption-leave-forward'
                : 'quran-caption-leave-backward';
            const enterClass = isForward
                ? 'quran-caption-enter-forward'
                : 'quran-caption-enter-backward';

            this.surahTriggerCaptionAnimClass = leaveClass;
            this._surahTriggerTimer = window.setTimeout(() => {
                this.surahTriggerCaption = nextCaption;
                this.surahTriggerSurahNumber = nextSurahNumber;
                this.surahTriggerCaptionAnimClass = enterClass;
                this._surahTriggerTimer = null;

                this._surahTriggerCleanupTimer = window.setTimeout(() => {
                    this.surahTriggerCaptionAnimClass = '';
                    this._surahTriggerCleanupTimer = null;
                }, 180);
            }, 140);
        },

        refreshMobileEdgeCaptions(animate = true) {
            const nextSurahText = this.mobileReaderSurahCaption();
            const nextPageText = this.mobileReaderPageCaption();
            const nextSurahNumber = Math.max(1, Math.trunc(Number(this.currentSurahNumber() ?? 1)));

            const surahChanged =
                nextSurahText !== this.mobileEdgeSurahCaptionText ||
                this.mobileEdgeSurahCaptionText === '';
            const pageChanged =
                nextPageText !== this.mobileEdgePageCaptionText ||
                this.mobileEdgePageCaptionText === '';

            if (!surahChanged && !pageChanged) {
                return;
            }

            if (this._mobileEdgeCaptionTimer !== null) {
                clearTimeout(this._mobileEdgeCaptionTimer);
                this._mobileEdgeCaptionTimer = null;
            }

            if (this._mobileEdgeCaptionCleanupTimer !== null) {
                clearTimeout(this._mobileEdgeCaptionCleanupTimer);
                this._mobileEdgeCaptionCleanupTimer = null;
            }

            if (
                !animate ||
                (this.mobileEdgeSurahCaptionText === '' && this.mobileEdgePageCaptionText === '')
            ) {
                this.mobileEdgeSurahCaptionText = nextSurahText;
                this.mobileEdgePageCaptionText = nextPageText;
                this._lastMobileEdgeSurahNumber = nextSurahNumber;
                this.mobileEdgeSurahCaptionAnimClass = '';
                this.mobileEdgePageCaptionAnimClass = '';

                return;
            }

            const isForward = nextSurahNumber >= this._lastMobileEdgeSurahNumber;
            const leaveClass = isForward
                ? 'quran-caption-leave-forward'
                : 'quran-caption-leave-backward';
            const enterClass = isForward
                ? 'quran-caption-enter-forward'
                : 'quran-caption-enter-backward';

            if (surahChanged) {
                this.mobileEdgeSurahCaptionAnimClass = leaveClass;
            }

            if (pageChanged) {
                this.mobileEdgePageCaptionAnimClass = leaveClass;
            }

            this._mobileEdgeCaptionTimer = window.setTimeout(() => {
                this._mobileEdgeCaptionTimer = null;
                this._lastMobileEdgeSurahNumber = nextSurahNumber;

                if (surahChanged) {
                    this.mobileEdgeSurahCaptionText = nextSurahText;
                    this.mobileEdgeSurahCaptionAnimClass = enterClass;
                }

                if (pageChanged) {
                    this.mobileEdgePageCaptionText = nextPageText;
                    this.mobileEdgePageCaptionAnimClass = enterClass;
                }

                this._mobileEdgeCaptionCleanupTimer = window.setTimeout(() => {
                    this._mobileEdgeCaptionCleanupTimer = null;
                    this.mobileEdgeSurahCaptionAnimClass = '';
                    this.mobileEdgePageCaptionAnimClass = '';
                }, 180);
            }, 140);
        },

        searchModalInputElement() {
            const candidates = Array.from(
                document.querySelectorAll('#quran-reader-search-input'),
            ).filter((element) => element instanceof HTMLInputElement && element.isConnected);

            if (candidates.length === 0) {
                return this.$refs.searchModalInput instanceof HTMLInputElement
                    ? this.$refs.searchModalInput
                    : null;
            }

            const isVisible = (element) => {
                if (!(element instanceof HTMLElement) || !element.isConnected) {
                    return false;
                }

                const styles = window.getComputedStyle(element);

                return (
                    element.clientHeight > 8 &&
                    element.clientWidth > 8 &&
                    styles.display !== 'none' &&
                    styles.visibility !== 'hidden'
                );
            };

            const rankedCandidates = candidates
                .map((element) => {
                    const modalElement = element.closest('.fi-modal');
                    const modalStyles =
                        modalElement instanceof HTMLElement
                            ? window.getComputedStyle(modalElement)
                            : null;
                    const modalZIndex = Number(modalStyles?.zIndex ?? '0');
                    const isOpenModal =
                        modalElement instanceof HTMLElement
                            ? modalElement.classList.contains('fi-modal-open')
                            : false;

                    return {
                        element,
                        visible: isVisible(element),
                        isOpenModal,
                        zIndex: Number.isFinite(modalZIndex) ? modalZIndex : 0,
                    };
                })
                .sort(
                    (left, right) =>
                        Number(right.visible) - Number(left.visible) ||
                        Number(right.isOpenModal) - Number(left.isOpenModal) ||
                        right.zIndex - left.zIndex,
                );

            return rankedCandidates[0]?.element ?? null;
        },

        unbindSearchModalInputSyncListener() {
            if (
                this._searchModalInputSyncElement instanceof HTMLInputElement &&
                typeof this._onSearchModalInputSync === 'function'
            ) {
                this._searchModalInputSyncElement.removeEventListener(
                    'input',
                    this._onSearchModalInputSync,
                );
            }

            this._searchModalInputSyncElement = null;
            this._onSearchModalInputSync = null;
        },
    };
};
