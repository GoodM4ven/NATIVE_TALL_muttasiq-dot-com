const defaultPagePayload = Object.freeze({
    ready: false,
    pageNumber: 1,
    maxPage: 0,
    activeAyahIndex: 0,
    mushafLines: [],
    qpcPageFontFamily: null,
    qpcPageFontUrl: null,
    qpcPageFontFormat: null,
    basmallahFontFamily: null,
    basmallahFontUrl: null,
    basmallahFontFormat: null,
    basmallahText: null,
    surahHeaderFontFamily: null,
    surahHeaderFontUrl: null,
    surahHeaderFontFormat: null,
    surahHeaderTopPaddingWhenFollowingPreviousSurahAyah: null,
    surahNames: null,
    surahDirectory: null,
    useCenteredAyahLayout: true,
});

const controlPanelSettingKeys = Object.freeze({
    enableVisualEnhancements: 'enable_visual_enhancements',
    targetWordsByDefault: 'does_quran_target_words_by_default',
    preserveHarakatOnCopy: 'does_quran_preserve_harakat_on_copy',
    appendSurahAffixOnMultiCopy: 'does_quran_append_surah_affix_on_multi_copy',
    appendSurahAffixAlwaysOnCopy: 'does_quran_append_surah_affix_always_on_copy',
    showImmersiveMobileEdgeCaptions: 'does_quran_show_immersive_mobile_edge_captions',
    useVolumeButtonsNavigation: 'does_quran_use_volume_buttons_navigation',
    useWesternNumerals: 'does_use_western_numerals',
    wirdFrequencyMode: 'quran_wird_frequency_mode',
    wirdKhatmatTarget: 'quran_wird_khatmat_target',
});
const athkarSettingsUserOverridesStorageKey = 'athkar-settings-user-overrides-v1';
const quranSearchStreamFrameDelimiter = '\n<<<QURAN_SEARCH_STREAM_FRAME>>>\n';

const normalizePayload = (payload = {}) => ({
    ready: Boolean(payload?.ready),
    pageNumber: Number(payload?.pageNumber ?? defaultPagePayload.pageNumber),
    maxPage: Number(payload?.maxPage ?? defaultPagePayload.maxPage),
    activeAyahIndex: Number(payload?.activeAyahIndex ?? defaultPagePayload.activeAyahIndex),
    mushafLines: Array.isArray(payload?.mushafLines) ? payload.mushafLines : [],
    qpcPageFontFamily: payload?.qpcPageFontFamily ?? null,
    qpcPageFontUrl: payload?.qpcPageFontUrl ?? null,
    qpcPageFontFormat: payload?.qpcPageFontFormat ?? null,
    basmallahFontFamily: payload?.basmallahFontFamily ?? null,
    basmallahFontUrl: payload?.basmallahFontUrl ?? null,
    basmallahFontFormat: payload?.basmallahFontFormat ?? null,
    basmallahText: payload?.basmallahText ?? null,
    surahHeaderFontFamily: payload?.surahHeaderFontFamily ?? null,
    surahHeaderFontUrl: payload?.surahHeaderFontUrl ?? null,
    surahHeaderFontFormat: payload?.surahHeaderFontFormat ?? null,
    surahHeaderTopPaddingWhenFollowingPreviousSurahAyah:
        payload?.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah ?? null,
    surahNames:
        payload?.surahNames && typeof payload.surahNames === 'object' ? payload.surahNames : null,
    surahDirectory: Array.isArray(payload?.surahDirectory) ? payload.surahDirectory : null,
    useCenteredAyahLayout: Boolean(payload?.useCenteredAyahLayout),
});

const normalizeNumerals = (rawValue) =>
    String(rawValue ?? '')
        .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x660))
        .replace(/[۰-۹]/g, (digit) => String(digit.charCodeAt(0) - 0x6f0))
        .trim();

const clampPage = (value, maxPage) => {
    const numeric = typeof value === 'string' ? Number(normalizeNumerals(value)) : Number(value);

    if (!Number.isFinite(numeric)) {
        return 1;
    }

    const rounded = Math.trunc(numeric);

    if (maxPage > 0) {
        return Math.max(1, Math.min(maxPage, rounded));
    }

    return Math.max(1, rounded);
};

const nextAnimationFrame = async () => {
    await new Promise((resolve) => {
        requestAnimationFrame(() => {
            resolve();
        });
    });
};

const wait = async (durationMs) => {
    await new Promise((resolve) => {
        window.setTimeout(resolve, durationMs);
    });
};

const wordPressHoldDelayMs = 750;
const wordPressDragThresholdPx = 14;
const mobileDoubleTapCopyWindowMs = 520;
const mobileDoubleTapHoldDelayMs = 340;
const bookmarkHoldDelayMs = 680;
const surahQuickNavigatorHoldDelayMs = 620;
const surahQuickNavigatorLastPage = 604;
const managerRowRemoveAnimationDurationMs = 220;
const managerRowUpdateAnimationDurationMs = 520;
const managerRowReplaceAnimationDurationMs = 560;
const swipeActivationThresholdPx = 40;
const copyPopoverVisibleDurationMs = 920;
const wirdCompletionVisibleDurationMs = 3400;
const copiedHighlightVisibleDurationMs = 3000;
const wordClickSuppressionResetMs = 180;
const pageCounterPulseDurationMs = 540;
const wirdModeEntryPageInputTweenDurationMs = 320;
const wirdHoverShimmerDurationMs = 1180;
const navigationSettleDelayMs = 28;
const navigationBurstInputThresholdMs = 140;
const navigationBurstSettleDelayMs = 72;
const navigationRevealLockDurationMs = 180;
const postModalFitRevealSettleDelayMs = 180;
const modalCloseTransitionDelayMs = 90;
const modalLifecycleSuppressionDurationMs = 980;
const historyNavigationModalLifecycleSuppressionDurationMs = 2600;
const revealBlockedFailOpenDelayMs = 280;
const swipeRevealWatchdogDelayMs = 520;
const pageFontLoadTimeoutMs = 960;
const pageFontReadyTimeoutMs = 1400;
const pageFontReadyRecoveryDelayMs = 200;
const readerRevealDebugStorageKey = 'quran-reader-debug-reveal';
const defaultBasmallahBottomGapScale = -0.18;
const openingSpreadFinalScaleMultiplier = 0.72;
const fitRobustWidthQuantile = 0.88;
const fitRobustWidthOutlierThreshold = 1.2;
const fitResultCacheLimit = 180;
const fitCacheStorageVersion = 18;
const fitCacheStorageKey = 'quran-reader-fit-cache-v18';
const fitCacheViewportBucketSizePx = 24;
const shouldPersistFitCacheAcrossReloads = false;
const quranReaderDebugLogsToggleEventName = 'quran-reader-debug-logs';
const quranReaderDebugLogsEnabledByEnv = (() => {
    const normalizeBoolean = (value, fallback = false) => {
        if (typeof value === 'boolean') {
            return value;
        }

        if (value === 1 || value === '1') {
            return true;
        }

        if (value === 0 || value === '0') {
            return false;
        }

        if (value === null || value === undefined || value === '') {
            return Boolean(fallback);
        }

        const normalizedValue = String(value).trim().toLowerCase();

        if (['true', 'yes', 'on'].includes(normalizedValue)) {
            return true;
        }

        if (['false', 'no', 'off'].includes(normalizedValue)) {
            return false;
        }

        return Boolean(fallback);
    };

    return normalizeBoolean(import.meta.env?.VITE_QURAN_READER_DEBUG_LOGS, false);
})();
const fitCalibrationReferencePage = 3;
const idleWarmupPauseOnHighFrequencyNavigationMs = 520;
const idleWarmupPauseOnStandardNavigationMs = 160;
const idleWarmupResumeDelayMs = 220;
const fitDefaultProfile = Object.freeze({
    compressionLeadingFloor: 0.46,
    compressionGapFloor: 0,
    compressionSurahHeaderFloor: 0.58,
    compressionTypeScaleCeiling: 1.28,
    layoutTypeScaleBase: 1.1207142857142858,
    layoutTypeScaleGain: 0.18,
    layoutLeadingBase: 0.805,
    layoutLeadingDrop: 0.12,
    layoutGapBase: 0.5589285714285714,
    layoutGapDrop: 0.2,
    layoutSurahHeaderBase: 0.87,
    layoutSurahHeaderDrop: 0.08,
    layoutBasmallahBase: -0.33785714285714286,
    layoutBasmallahDrop: 0.08,
    baseLeadingMultiplier: 1,
    baseGapMultiplier: 1,
    minimumCompressionLevel: 0,
    targetWidthRatio: 0.9,
    targetHeightRatio: 0.93,
    widthDeficitWeight: 0.55,
    heightDeficitWeight: 0.08,
    compressionPenaltyWeight: 0.002,
    strictWidthOverflowTolerance: 1.06,
    strictHeightOverflowTolerance: 1.01,
    candidateSteps: 28,
    maxScaleMultiplier: 1,
});
const navigationHistoryLimit = 100;
const supportedHistorySources = Object.freeze([
    'surah-directory',
    'bookmark-navigation',
    'page-jump',
    'page-slider-commit',
]);
const lastPageStorageKey = 'quran-reader-last-page-v1';
const navigationHistoryStorageKey = 'quran-reader-navigation-history-v1';
const bookmarksStorageKey = 'quran-reader-bookmarks-v1';
const wirdProgressStorageKey = 'quran-reader-wird-progress-v1';
const wirdDayOffsetStorageKey = 'quran-reader-wird-day-offset-v1';
const wirdProgressStorageVersion = 1;
const supportUnlockStorageKey = 'quran-support-unlock-v1';
const supportUnlockStorageVersion = 1;
const supportUnlockModePermanent = 'permanent';
const supportUnlockModeWeekly = 'weekly';
const supportUnlockWeeklyDurationMs = 7 * 24 * 60 * 60 * 1000;
const quranPageScaleAdjustStorageKey = 'quran-reader-page-scale-adjust-v1';
const quranPageScaleAdjustMin = -100;
const quranPageScaleAdjustMax = 100;
const quranPageScaleAdjustMultiplierStep = 0.015;
const quranPageGapAdjustStorageKey = 'quran-reader-page-gap-adjust-v1';
const quranPageGapAdjustMin = -100;
const quranPageGapAdjustMax = 100;
const quranPageGapAdjustMultiplierStep = 0.025;
const quranPageYOffsetAdjustStorageKey = 'quran-reader-page-y-offset-adjust-v1';
const quranPageYOffsetAdjustMin = -100;
const quranPageYOffsetAdjustMax = 100;
const quranPageYOffsetAdjustRemStep = 0.06;
const supportLockClosedOutlineIconSvg = `<svg xmlns="http://www.w3.org/2000/svg" class="quran-support-lock-badge__icon-svg" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12 1.5a5.25 5.25 0 0 0-5.25 5.25v3a3 3 0 0 0-3 3v6.75a3 3 0 0 0 3 3h10.5a3 3 0 0 0 3-3v-6.75a3 3 0 0 0-3-3v-3c0-2.9-2.35-5.25-5.25-5.25Zm3.75 8.25v-3a3.75 3.75 0 1 0-7.5 0v3h7.5Z" clip-rule="evenodd" /></svg>`;
const wirdFrequencyModeMonthly = 0;
const wirdFrequencyModeDaily = 1;
const wirdKhatmatTargetMin = 1;
const wirdDailyKhatmatTargetMax = 4;
const wirdMonthlyKhatmatTargetMax = 20;
const wirdRecordRetentionDays = 120;

const uniqueLocalId = () => {
    if (typeof crypto !== 'undefined' && typeof crypto.randomUUID === 'function') {
        return crypto.randomUUID();
    }

    return `quran-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
};

const normalizeTextValue = (value) => {
    const normalized = String(value ?? '')
        .trim()
        .replace(/\s+/g, ' ');

    return normalized === '' ? null : normalized;
};

const defaultWesternNumerals = Object.freeze(['0', '1', '2', '3', '4', '5', '6', '7', '8', '9']);
const defaultArabicNumerals = Object.freeze(['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩']);

const arabicHarakatPattern = /[\u0610-\u061A\u064B-\u0653\u0656-\u065F\u0670\u06D6-\u06ED]/gu;
const arabicPresentationFormsPattern = /[\uFB50-\uFDFF\uFE70-\uFEFF]/u;

const stripArabicHarakat = (value) =>
    String(value ?? '')
        .replace(arabicHarakatPattern, '')
        .replace(/\s+/g, ' ')
        .trim();

const hasArabicPresentationForms = (value) =>
    arabicPresentationFormsPattern.test(String(value ?? ''));

const normalizeTags = (value) => {
    const source = Array.isArray(value) ? value : String(value ?? '').split(',');
    const uniqueTags = [];
    const usedTags = new Set();

    source.forEach((tag) => {
        const normalizedTag = normalizeTextValue(tag);

        if (!normalizedTag) {
            return;
        }

        const uniqueKey = normalizedTag.toLowerCase();

        if (usedTags.has(uniqueKey)) {
            return;
        }

        usedTags.add(uniqueKey);
        uniqueTags.push(normalizedTag);
    });

    return uniqueTags;
};

const readLocalStorage = (key, fallbackValue) => {
    if (typeof localStorage === 'undefined') {
        return fallbackValue;
    }

    try {
        const rawValue = localStorage.getItem(key);

        if (rawValue === null) {
            return fallbackValue;
        }

        return JSON.parse(rawValue);
    } catch (_) {
        return fallbackValue;
    }
};

const readLocalStorageRaw = (key) => {
    if (typeof localStorage === 'undefined') {
        return null;
    }

    try {
        return localStorage.getItem(key);
    } catch (_) {
        return null;
    }
};

const writeLocalStorage = (key, value) => {
    if (typeof localStorage === 'undefined') {
        return;
    }

    try {
        localStorage.setItem(key, JSON.stringify(value));
    } catch (_) {
        //
    }
};

const normalizeDayOffsetDays = (value, fallback = 0) => {
    const parsed = Math.trunc(Number(value));

    if (!Number.isFinite(parsed)) {
        return Math.trunc(Number(fallback) || 0);
    }

    return Math.max(-3650, Math.min(3650, parsed));
};

const readWirdDayOffsetDays = () =>
    normalizeDayOffsetDays(readLocalStorage(wirdDayOffsetStorageKey, 0), 0);

const writeWirdDayOffsetDays = (value) => {
    writeLocalStorage(wirdDayOffsetStorageKey, normalizeDayOffsetDays(value, 0));
};

const currentDateKey = () => {
    const now = new Date();
    now.setDate(now.getDate() + readWirdDayOffsetDays());
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
};

const historyEntryHasPersistenceMeta = (entry = {}) => {
    const tags = Array.isArray(entry?.tags) ? entry.tags : [];
    const note = normalizeTextValue(entry?.note);

    return tags.length > 0 || Boolean(note);
};

const normalizeHistoryEntry = (entry = {}) => {
    const normalizedPageNumber = clampPage(
        entry?.page_number ?? entry?.pageNumber ?? entry?.page ?? 1,
        0,
    );
    const rawSource = String(entry?.source ?? '').trim();
    const source = supportedHistorySources.includes(rawSource) ? rawSource : 'search-result';
    const createdAt = Number(entry?.created_at ?? entry?.createdAt ?? Date.now());

    return {
        id: String(entry?.id ?? uniqueLocalId()),
        source,
        page_number: normalizedPageNumber,
        surah_number: Math.max(0, Math.trunc(Number(entry?.surah_number ?? 0))),
        ayah_number: Math.max(0, Math.trunc(Number(entry?.ayah_number ?? 0))),
        ayah_index: Math.max(0, Math.trunc(Number(entry?.ayah_index ?? 0))),
        note: normalizeTextValue(entry?.note),
        query: normalizeTextValue(entry?.query),
        tags: normalizeTags(entry?.tags),
        created_at: Number.isFinite(createdAt) ? Math.trunc(createdAt) : Date.now(),
        sort_order: Math.max(0, Math.trunc(Number(entry?.sort_order ?? entry?.sortOrder ?? 0))),
    };
};

const pruneNavigationHistory = (entries = []) => {
    const sortedEntries = entries
        .slice()
        .sort((firstEntry, secondEntry) => secondEntry.created_at - firstEntry.created_at);
    const persistedEntries = [];
    const untrackedEntries = [];

    sortedEntries.forEach((entry) => {
        if (historyEntryHasPersistenceMeta(entry)) {
            persistedEntries.push(entry);

            return;
        }

        untrackedEntries.push(entry);
    });

    const normalizedPersistedEntries = persistedEntries
        .slice()
        .sort((firstEntry, secondEntry) => {
            const firstSortOrder = Number(firstEntry?.sort_order ?? 0);
            const secondSortOrder = Number(secondEntry?.sort_order ?? 0);

            if (firstSortOrder > 0 && secondSortOrder > 0 && firstSortOrder !== secondSortOrder) {
                return firstSortOrder - secondSortOrder;
            }

            if (firstSortOrder > 0 && secondSortOrder <= 0) {
                return -1;
            }

            if (firstSortOrder <= 0 && secondSortOrder > 0) {
                return 1;
            }

            return Number(secondEntry?.created_at ?? 0) - Number(firstEntry?.created_at ?? 0);
        })
        .map((entry, index) => ({
            ...entry,
            sort_order: index + 1,
        }));

    const retainedEntries = untrackedEntries.slice(0, navigationHistoryLimit).map((entry) => ({
        ...entry,
        sort_order: 0,
    }));

    return [...normalizedPersistedEntries, ...retainedEntries];
};

const normalizeNavigationHistory = (entries = []) => {
    if (!Array.isArray(entries)) {
        return [];
    }

    const normalizedEntries = [];
    const usedIds = new Set();

    entries.forEach((entry) => {
        const normalizedEntry = normalizeHistoryEntry(entry);

        if (usedIds.has(normalizedEntry.id)) {
            return;
        }

        usedIds.add(normalizedEntry.id);
        normalizedEntries.push(normalizedEntry);
    });

    return pruneNavigationHistory(normalizedEntries);
};

const readNavigationHistory = () =>
    normalizeNavigationHistory(readLocalStorage(navigationHistoryStorageKey, []));

const writeNavigationHistory = (entries = []) => {
    const normalizedEntries = normalizeNavigationHistory(entries);
    writeLocalStorage(navigationHistoryStorageKey, normalizedEntries);

    return normalizedEntries;
};

const normalizeBookmarkEntry = (entry = {}) => {
    const normalizedPageNumber = clampPage(
        entry?.page_number ?? entry?.pageNumber ?? entry?.page ?? 1,
        0,
    );
    const updatedAt = Number(entry?.updated_at ?? entry?.updatedAt ?? Date.now());
    const createdAt = Number(entry?.created_at ?? entry?.createdAt ?? updatedAt);

    return {
        id: String(entry?.id ?? uniqueLocalId()),
        page_number: normalizedPageNumber,
        note: normalizeTextValue(entry?.note ?? entry?.title),
        tags: normalizeTags(entry?.tags),
        created_at: Number.isFinite(createdAt) ? Math.trunc(createdAt) : Date.now(),
        updated_at: Number.isFinite(updatedAt) ? Math.trunc(updatedAt) : Date.now(),
        sort_order: Math.max(1, Math.trunc(Number(entry?.sort_order ?? entry?.sortOrder ?? 0))),
    };
};

const normalizeBookmarks = (entries = []) => {
    if (!Array.isArray(entries)) {
        return [];
    }

    const uniqueById = [];
    const usedIds = new Set();

    entries.forEach((entry) => {
        const normalizedEntry = normalizeBookmarkEntry(entry);

        if (usedIds.has(normalizedEntry.id)) {
            return;
        }

        usedIds.add(normalizedEntry.id);
        uniqueById.push(normalizedEntry);
    });

    return uniqueById
        .sort((firstEntry, secondEntry) => {
            const firstSortOrder = Number(firstEntry?.sort_order ?? 0);
            const secondSortOrder = Number(secondEntry?.sort_order ?? 0);

            if (firstSortOrder > 0 && secondSortOrder > 0 && firstSortOrder !== secondSortOrder) {
                return firstSortOrder - secondSortOrder;
            }

            if (firstSortOrder > 0 && secondSortOrder <= 0) {
                return -1;
            }

            if (firstSortOrder <= 0 && secondSortOrder > 0) {
                return 1;
            }

            return Number(secondEntry?.updated_at ?? 0) - Number(firstEntry?.updated_at ?? 0);
        })
        .map((entry, index) => ({
            ...entry,
            sort_order: index + 1,
        }));
};

const readBookmarks = () => normalizeBookmarks(readLocalStorage(bookmarksStorageKey, []));

const writeBookmarks = (entries = []) => {
    const normalizedEntries = normalizeBookmarks(entries);
    writeLocalStorage(bookmarksStorageKey, normalizedEntries);

    return normalizedEntries;
};

const readLastPageNumber = () => {
    const rawValue = readLocalStorage(lastPageStorageKey, null);
    const parsedValue = Number(rawValue);

    if (!Number.isFinite(parsedValue)) {
        return null;
    }

    return Math.max(1, Math.trunc(parsedValue));
};

const writeLastPageNumber = (pageNumber) => {
    writeLocalStorage(lastPageStorageKey, Math.max(1, Math.trunc(Number(pageNumber) || 1)));
};

const normalizeSupportUnlockState = (value = {}) => {
    const normalizedValue =
        value && typeof value === 'object' && !Array.isArray(value) ? value : {};
    const normalizedMode = String(normalizedValue?.mode ?? '')
        .trim()
        .toLowerCase();
    const grantedAt = Math.max(0, Math.trunc(Number(normalizedValue?.granted_at ?? Date.now())));
    const now = Date.now();

    if (normalizedMode === supportUnlockModePermanent) {
        return {
            version: supportUnlockStorageVersion,
            mode: supportUnlockModePermanent,
            granted_at: grantedAt || now,
            expires_at: null,
        };
    }

    if (normalizedMode === supportUnlockModeWeekly) {
        const rawExpiry = Number(normalizedValue?.expires_at ?? 0);
        const expiresAt = Math.max(0, Math.trunc(rawExpiry));

        if (expiresAt > now) {
            return {
                version: supportUnlockStorageVersion,
                mode: supportUnlockModeWeekly,
                granted_at: grantedAt || now,
                expires_at: expiresAt,
            };
        }
    }

    return {
        version: supportUnlockStorageVersion,
        mode: 'locked',
        granted_at: null,
        expires_at: null,
    };
};

const readSupportUnlockState = () =>
    normalizeSupportUnlockState(readLocalStorage(supportUnlockStorageKey, null));

const writeSupportUnlockState = (value = {}) => {
    const normalized = normalizeSupportUnlockState(value);
    writeLocalStorage(supportUnlockStorageKey, normalized);

    return normalized;
};

const openCacheSafely = async (cacheName) => {
    if (!cacheName || typeof window === 'undefined' || typeof window.caches === 'undefined') {
        return null;
    }

    try {
        return await window.caches.open(cacheName);
    } catch (_) {
        return null;
    }
};

const fetchJsonWithCache = async ({
    url,
    cacheName,
    preferCache = true,
    forceNetwork = false,
    signal = null,
}) => {
    const cache = await openCacheSafely(cacheName);

    if (cache && preferCache && !forceNetwork) {
        const cached = await cache.match(url);

        if (cached) {
            return await cached.json();
        }
    }

    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
            signal,
        });

        if (!response.ok) {
            throw new Error(`Unexpected response ${response.status} for ${url}`);
        }

        if (cache) {
            await cache.put(url, response.clone());
        }

        return await response.json();
    } catch (error) {
        if (error?.name === 'AbortError') {
            throw error;
        }

        if (cache) {
            const stale = await cache.match(url);

            if (stale) {
                return await stale.json();
            }
        }

        throw error;
    }
};

const cacheAssetResponse = async ({ url, cacheName }) => {
    if (!url) {
        return;
    }

    const cache = await openCacheSafely(cacheName);

    if (!cache) {
        return;
    }

    const cached = await cache.match(url);

    if (cached) {
        return;
    }

    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) {
            return;
        }

        await cache.put(url, response);
    } catch (_) {
        // Ignore cache misses in offline / flaky network states.
    }
};

const supportLockLivewireMorphedEventName = 'quran-support-lock-livewire-morphed';

const ensureSupportLockLivewireMorphBridge = (() => {
    let isInstalled = false;

    const emitSupportLockLivewireMorphed = () => {
        if (typeof window === 'undefined') {
            return;
        }

        window.dispatchEvent(new CustomEvent(supportLockLivewireMorphedEventName));
    };

    const install = () => {
        if (isInstalled || !window.Livewire?.hook) {
            return;
        }

        let didRegisterAnyHook = false;
        const scheduleSupportLockSyncSignal = () => {
            if (typeof window === 'undefined') {
                return;
            }

            window.requestAnimationFrame(() => {
                emitSupportLockLivewireMorphed();
            });
        };

        try {
            window.Livewire.hook('request', ({ succeed, fail }) => {
                if (typeof succeed === 'function') {
                    succeed(() => {
                        scheduleSupportLockSyncSignal();
                    });
                }

                if (typeof fail === 'function') {
                    fail(() => {
                        scheduleSupportLockSyncSignal();
                    });
                }
            });
            didRegisterAnyHook = true;
        } catch (_) {
            //
        }

        ['morphed', 'partial.morphed', 'morph.updated', 'morph.added'].forEach((hookName) => {
            try {
                window.Livewire.hook(hookName, () => {
                    emitSupportLockLivewireMorphed();
                });
                didRegisterAnyHook = true;
            } catch (_) {
                //
            }
        });

        if (didRegisterAnyHook) {
            isInstalled = true;
        }
    };

    if (typeof document !== 'undefined') {
        document.addEventListener('livewire:init', install, { once: true });
        document.addEventListener('livewire:initialized', install, { once: true });
    }

    return install;
})();

export {
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
};
