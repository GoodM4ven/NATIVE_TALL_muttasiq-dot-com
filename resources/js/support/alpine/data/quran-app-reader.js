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
    useVolumeButtonsNavigation: 'does_quran_use_volume_buttons_navigation',
    useWesternNumerals: 'does_use_western_numerals',
    wirdFrequencyMode: 'quran_wird_frequency_mode',
    wirdKhatmatTarget: 'quran_wird_khatmat_target',
});
const athkarSettingsUserOverridesStorageKey = 'athkar-settings-user-overrides-v1';

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
const navigationRevealLockDurationMs = 420;
const postModalFitRevealSettleDelayMs = 280;
const modalCloseTransitionDelayMs = 160;
const modalLifecycleSuppressionDurationMs = 980;
const historyNavigationModalLifecycleSuppressionDurationMs = 2600;
const revealBlockedFailOpenDelayMs = 1100;
const swipeRevealWatchdogDelayMs = 760;
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
const quranPageScaleAdjustMin = -48;
const quranPageScaleAdjustMax = 48;
const quranPageScaleAdjustMultiplierStep = 0.015;
const quranPageGapAdjustStorageKey = 'quran-reader-page-gap-adjust-v1';
const quranPageGapAdjustMin = -48;
const quranPageGapAdjustMax = 48;
const quranPageGapAdjustMultiplierStep = 0.025;
const quranPageYOffsetAdjustStorageKey = 'quran-reader-page-y-offset-adjust-v1';
const quranPageYOffsetAdjustMin = -48;
const quranPageYOffsetAdjustMax = 48;
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

document.addEventListener('alpine:init', () => {
    window.Alpine.data('quranAppReader', (config = {}) => ({
        api: {
            pageDataTemplate: String(config?.api?.pageDataTemplate ?? ''),
            searchIndexUrl: String(config?.api?.searchIndexUrl ?? ''),
        },
        cacheNames: {
            pages: 'quran-reader-pages-v13',
            fonts: 'quran-reader-fonts-v4',
            search: 'quran-reader-search-v3',
        },
        initialPayload: normalizePayload(config?.initialPayload),
        nativeRuntime: Boolean(config?.nativeRuntime ?? false),
        prewarmPages: Math.max(1, Number(config?.prewarmPages ?? 6)),
        prefetchRadius: Math.max(1, Number(config?.prefetchRadius ?? 2)),
        searchModalId: String(config?.searchModalId ?? ''),
        searchModalDomId: String(config?.searchModalDomId ?? ''),
        searchActionModalId: String(config?.searchActionModalId ?? ''),
        jumpPageModalId: String(config?.jumpPageModalId ?? ''),
        historyModalId: String(config?.historyModalId ?? ''),
        bookmarksModalId: String(config?.bookmarksModalId ?? ''),
        initialSettings:
            config?.settings && typeof config.settings === 'object' ? config.settings : {},

        ready: false,
        pageNumber: window.Alpine.$persist(1).as('quran-reader-page-number-v1'),
        pageInput: 1,
        maxPage: 0,
        activeAyahIndex: 0,
        activeWordIndex: 0,
        searchHighlightedAyahIndex: 0,
        mushafLines: [],
        lineEntry: null,
        line: null,
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
        useCenteredAyahLayout: true,
        hoveredAyahIndex: 0,
        hoveredWordIndex: 0,
        doesEnableVisualEnhancements: true,
        doesTargetWordsByDefault: false,
        doesPreserveHarakatOnCopy: true,
        doesAppendSurahAffixOnMultiCopy: true,
        doesAppendSurahAffixAlwaysOnCopy: false,
        doesUseVolumeButtonsNavigation: true,
        doesUseWesternNumerals: true,
        wirdFrequencyMode: wirdFrequencyModeMonthly,
        wirdKhatmatTarget: 1,
        wirdModeActive: false,
        wirdNormalPageBeforeMode: 1,
        wirdBrowseStep: null,
        wirdSliderVisualStep: null,
        wirdHoverShimmerRunning: false,
        wirdTodayKey: '',
        wirdDayOffsetDays: readWirdDayOffsetDays(),
        wirdDailyRecord: null,
        wirdState: null,
        westernNumeralCharacters: defaultWesternNumerals.slice(),
        arabicNumeralCharacters: defaultArabicNumerals.slice(),
        isLoadingPage: false,
        isFittingPage: true,
        isTransitioningOutPage: false,
        pageMotionClass: '',
        surahTriggerCaption: '',
        surahTriggerCaptionAnimClass: '',
        surahTriggerSurahNumber: 1,
        pageMotionTimer: null,
        pageScale: 1,
        lineWordGapAdjustments: {},
        pageCounterPulse: {
            isActive: false,
            hasChanges: false,
            segments: [],
            timer: null,
        },
        swipe: {
            active: false,
            startX: 0,
            startY: 0,
            pointerId: null,
            pointerType: null,
            source: null,
        },
        pendingChevronSource: null,
        storage: {
            isPersisted: false,
            persistRequested: false,
            fitCacheBreakpoint: '',
        },
        supportUnlock: {
            mode: 'locked',
            grantedAt: null,
            expiresAt: null,
        },
        search: {
            query: '',
            minQueryLength: 5,
            inputDebounceMs: 600,
            results: [],
            isLoading: false,
            streamHasUpdates: false,
            isReady: false,
            isOpen: false,
            modalOpen: false,
            readyResult: null,
            surahNames: {},
            surahDirectory: [],
            activeSurahNumber: 1,
            preserveActiveSurahOnNextOpen: false,
        },
        navigationHistory: [],
        historyTagDraftById: {},
        bookmarkTagDraftById: {},
        bookmarks: [],
        historyModalOpen: false,
        bookmarksModalOpen: false,
        jumpPageModalOpen: false,
        managerRowEffects: {
            history: {},
            bookmarks: {},
        },
        copyFeedback: {
            visible: false,
            x: 0,
            y: 0,
            timer: null,
            serial: 0,
        },
        isWirdCompletionVisible: false,
        isWirdCompletionPreviewPinned: false,
        isReaderChromeVisible: false,
        isFontScaleOverlayVisible: false,
        quranPageScaleAdjustValue: 0,
        quranPageGapAdjustValue: 0,
        quranPageYOffsetAdjustValue: 0,
        hasCompletedInitialMushafPreparation: false,
        copiedHighlights: {
            wordKeys: [],
            ayahIndexes: [],
        },
        bookmarkButtonPress: {
            pointerId: null,
            holdTriggered: false,
            suppressClick: false,
            timer: null,
        },
        surahQuickNavigator: {
            visible: false,
            pointerId: null,
            holdTriggered: false,
            suppressClick: false,
            timer: null,
        },

        _pendingPageLoads: new Map(),
        _pagePayloadByPage: new Map(),
        _activePageAbortController: null,
        _searchIndexPromise: null,
        _layoutToken: 0,
        _layoutRaf: null,
        _revealTimer: null,
        _lastPageRevealAt: 0,
        _revealBlockedSinceAt: 0,
        _revealBlockedLayoutToken: 0,
        _layoutActivePromise: null,
        _queuedLayoutRequest: null,
        _layoutMutationObserver: null,
        _layoutResizeObserver: null,
        _layoutObservedViewportWidth: 0,
        _layoutObservedViewportHeight: 0,
        _readerPanelLayoutSerial: 0,
        _readerPanelLayoutRaf: null,
        _viewportChangeDebounceTimer: null,
        _onWindowViewportChange: null,
        _onVisualViewportChange: null,
        _onWindowScroll: null,
        _onSwitchView: null,
        _onWirdSimulateDay: null,
        _onWirdCongratsPreview: null,
        _onFontScaleToggle: null,
        _pageScaleAdjustRefitRaf: null,
        _deferredBootstrapCheckTimer: null,
        _deferredBootstrapCheckAttempts: 0,
        _onWindowStorage: null,
        _onHistoryManagerRequestSync: null,
        _onBookmarksManagerRequestSync: null,
        _onQrDebugLogsToggle: null,
        _lastQuranReaderView: 'quran-app-tilawa',
        _onWindowKeydown: null,
        _onPanelPointerDown: null,
        _onPanelPointerMove: null,
        _onPanelPointerUp: null,
        _onPanelPointerCancel: null,
        _onWindowPointerMove: null,
        _onWindowPointerUp: null,
        _onWindowPointerCancel: null,
        _onPanelTouchStart: null,
        _onPanelTouchMove: null,
        _onPanelTouchEnd: null,
        _onPanelTouchCancel: null,
        _onWindowTouchMove: null,
        _onWindowTouchEnd: null,
        _onWindowTouchCancel: null,
        _onWindowNativeVolumeButton: null,
        _surahTriggerTimer: null,
        _surahTriggerCleanupTimer: null,
        _pendingNavigationRequest: null,
        _navigationDebounceTimer: null,
        _navigationRevealUnlockTimer: null,
        _navigationRevealLocked: false,
        _swipeRevealWatchdogTimer: null,
        _navigationBurstLastInputAt: 0,
        _navigationBurstCount: 0,
        _navigationBurstFreezeUntil: 0,
        _fitRunCounter: 0,
        _lastFittedPageNumber: 0,
        _fitResultByContext: new Map(),
        _fitSanityCheckTimer: null,
        _fitSanityContextKey: '',
        _fitSanityContextAttemptCount: 0,
        _fitSanityContextLastWidth: 0,
        _fitSanityContextLastHeight: 0,
        _fitSanityContextOutcome: '',
        _fitSanitySuppressedUntil: 0,
        _fitSanityDisabledContextKey: '',
        _fitCachePersistWriteTimer: null,
        _supportUnlockExpiryTimer: null,
        _wirdCompletionTimer: null,
        _fontReadyRecoveryTimer: null,
        _fontReadyRecoveryPage: 0,
        _fontReadyRecoveryAttemptPage: 0,
        _fontReadyRecoveryAttemptCount: 0,
        _fontReadyRecoveryLastAt: 0,
        _fitCacheBreakpoint: '',
        _bypassNextFitCache: false,
        _suppressFitCacheWriteUntil: 0,
        _wirdSliderVisualTweenRaf: null,
        _wirdSliderInputCommitTimer: null,
        _wirdSliderPendingCommitStep: null,
        _wirdSliderLastInputStep: null,
        _wirdSliderLastInputAt: 0,
        _wirdNavigationRequestSerial: 0,
        _wirdLastCommittedTargetPage: 0,
        _wirdLastCommittedStep: null,
        _wirdLastCommittedAt: 0,
        _wirdHoverShimmerTimer: null,
        _pageInputCommitTimer: null,
        _pageInputTweenRaf: null,
        _pageSliderInteractionActive: false,
        _lastPageSliderCommitPage: 0,
        _lastPageSliderCommitAt: 0,
        _searchRequestSerial: 0,
        _searchRequestInFlight: false,
        _searchQueuedNormalizedQuery: null,
        _searchStreamObserver: null,
        _lastSearchStreamPayloadRaw: '',
        _searchResultsLeaveTimer: null,
        _stopLivewireMorphedHook: null,
        _supportLockTargetsSyncRaf: null,
        _onSupportLockLivewireMorphed: null,
        _searchResultsAutoAnimateStop: null,
        _historyRowsAutoAnimateStop: null,
        _bookmarksRowsAutoAnimateStop: null,
        _searchModalCloseDebounceTimer: null,
        _searchModalOpenInFlight: null,
        _searchModalInputSyncElement: null,
        _onSearchModalInputSync: null,
        _searchInputSyncDebounceTimer: null,
        _stopIsCalibratingWatcher: null,
        _stopSearchQueryWatcher: null,
        _quranPreparationInFlight: null,
        _quranPreparationRequestPromise: null,
        _startupRestoreInFlight: null,
        _startupCalibrationPending: true,
        _startupTargetPageNumber: 1,
        _bootstrapDeferred: false,
        calibrationHudTop: 0,
        calibrationHudLeft: 0,
        isCalibrating: false,
        _globalFitCalibrationLayout: null,
        _globalFitCalibrationScale: 0,
        _globalFitCalibrationPageNumber: 0,
        _loadedPayloadPageNumber: 0,
        _surahDirectoryAutoFocusToken: 0,
        _surahDirectoryAutoFocusTimer: null,
        _surahDirectoryAutoFocusRaf: null,
        _surahDirectoryPostOpenTimers: [],
        _wirdEntryRevealTimers: [],
        _wirdEntryLayoutSuppressedUntil: 0,
        _historyManagerSyncTimers: [],
        _bookmarksManagerSyncTimers: [],
        _modalLayoutResumeTimer: null,
        _postModalTargetFitPage: 0,
        _postModalTargetFitRetries: 0,
        _postModalTargetFitTimer: null,
        _activeModalIds: new Set(),
        _isModalLifecycleSettling: false,
        _lastModalLifecycleEventAt: 0,
        _suppressModalLifecycleEffectsUntil: 0,
        _suppressModalLifecycleModalIds: new Set(),
        _wordPressHoldTimer: null,
        _wordBySelectionKey: new Map(),
        _ayahNumberByIndex: new Map(),
        _surahNumberByAyahIndex: new Map(),
        _copiedHighlightTimer: null,
        _suppressWordClickResetTimer: null,
        _suppressNextWordClick: false,
        _skipNextSearchModalCloseLayout: false,
        _lastKnownModalOpenState: false,
        _lastPageInputCommitPage: 0,
        _lastPageInputCommitAt: 0,
        _lastPageInputVisualValue: 1,
        _lastWordHoldAt: 0,
        _lastWordGapRebalancedPageNumber: 0,
        _wirdStateStorageRawSnapshot: null,
        _wirdDayOffsetStorageRawSnapshot: null,
        _idleWarmupQueue: [],
        _idleWarmupQueuedPages: new Set(),
        _idleWarmupHandle: null,
        _idleWarmupHandleKind: null,
        _idleWarmupPausedUntil: 0,
        _idleWarmupInFlight: false,
        _idleWarmupInFlightPage: 0,
        _idleWarmupAbortController: null,
        _idleWarmupHasBackgroundSweepQueued: false,
        _managerRowEffectTimers: new Map(),
        _managerModalsPrewarmPromise: null,
        _managerModalsPrewarmed: false,
        isQrDebugLoggingEnabled: quranReaderDebugLogsEnabledByEnv,
        wordPress: {
            active: false,
            pointerId: null,
            startX: 0,
            startY: 0,
            holdTriggered: false,
            word: null,
            target: null,
            dragActive: false,
            trailWordKeys: [],
            trailWords: [],
            trailAyahIndexes: [],
            lastAnchor: null,
        },

        init() {
            this.applyPayload(this.initialPayload, {
                setPageNumber: true,
                persistPageNumber: false,
            });
            const resolveControlPanelSettings =
                typeof this.resolveControlPanelSettingsWithUserOverrides === 'function'
                    ? this.resolveControlPanelSettingsWithUserOverrides.bind(this)
                    : (defaults = {}) => defaults;
            this.applyControlPanelSettings(resolveControlPanelSettings(this.initialSettings));
            this.quranPageScaleAdjustValue = this.readPersistedPageScaleAdjustValue();
            this.quranPageGapAdjustValue = this.readPersistedPageGapAdjustValue();
            this.quranPageYOffsetAdjustValue = this.readPersistedPageYOffsetAdjustValue();
            this.syncSupportUnlockState({ persist: false });
            this.buildSurahDirectory(
                Array.isArray(this.initialPayload.surahDirectory) &&
                    this.initialPayload.surahDirectory.length > 0
                    ? this.initialPayload.surahDirectory
                    : this.search.surahDirectory,
            );
            this.refreshSurahTriggerCaption(false);
            this.syncSearchActiveSurahNumber();
            this.navigationHistory = readNavigationHistory();
            this.syncHistoryTagDrafts();
            this.bookmarks = readBookmarks();
            this.syncBookmarkTagDrafts();
            this.dispatchManagerModalsVisibilityState();
            this._onQrDebugLogsToggle = (event) => {
                const details =
                    event?.detail && typeof event.detail === 'object' ? event.detail : {};

                if (Object.prototype.hasOwnProperty.call(details, 'enabled')) {
                    this.isQrDebugLoggingEnabled = this.normalizeBooleanFlag(
                        details.enabled,
                        false,
                    );

                    return;
                }

                this.isQrDebugLoggingEnabled = !this.isQrDebugLoggingEnabled;
            };
            window.addEventListener(quranReaderDebugLogsToggleEventName, this._onQrDebugLogsToggle);

            const storedLastPageNumber = readLastPageNumber();
            const restoredPage = clampPage(
                storedLastPageNumber ?? this.pageNumber,
                this.maxPage || this.initialPayload.maxPage,
            );
            const shouldRestoreSavedPage =
                restoredPage !== this.initialPayload.pageNumber && this.ready;

            this.pageInput = restoredPage;
            this._lastPageInputVisualValue = restoredPage;
            this._startupTargetPageNumber = restoredPage;
            writeLastPageNumber(restoredPage);

            if (!shouldRestoreSavedPage) {
                this.pageNumber = restoredPage;
            } else {
                this.isFittingPage = true;
                const startupRestorePromise = this.goToPage(restoredPage, {
                    direction: restoredPage > this.initialPayload.pageNumber ? 'next' : 'prev',
                    animate: false,
                    forceRefit: true,
                    source: 'startup-restore',
                });
                this._startupRestoreInFlight = startupRestorePromise;
                void startupRestorePromise.finally(() => {
                    if (this._startupRestoreInFlight === startupRestorePromise) {
                        this._startupRestoreInFlight = null;
                    }
                });
            }

            this.ensureWirdDailyRecord();

            this._onWindowStorage = (event) => {
                const storageKey = String(event?.key ?? '');

                if (
                    storageKey !== wirdProgressStorageKey &&
                    storageKey !== wirdDayOffsetStorageKey
                ) {
                    return;
                }

                this.syncWirdStorageState({
                    force: true,
                    clearDailyRecord: true,
                });
                this.ensureWirdDailyRecord({
                    forceRebuild: storageKey === wirdDayOffsetStorageKey,
                });
            };
            window.addEventListener('storage', this._onWindowStorage);

            this._onWindowViewportChange = () => {
                this.handleViewportChange();
            };
            window.addEventListener('resize', this._onWindowViewportChange, { passive: true });
            window.addEventListener('orientationchange', this._onWindowViewportChange, {
                passive: true,
            });

            this._onVisualViewportChange = () => {
                this.handleViewportChange();
            };

            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', this._onVisualViewportChange, {
                    passive: true,
                });
            }

            this._onWindowScroll = () => {
                if (!this.isCalibrating) {
                    return;
                }

                this.syncCalibrationHudPosition();
            };
            window.addEventListener('scroll', this._onWindowScroll, { passive: true });

            this._onSwitchView = (event) => {
                const to = String(event?.detail?.to ?? '');
                const quranViews = ['quran-app-tilawa', 'quran-app-hifth', 'quran-app-tadabbur'];
                const isGoingToQuranReader = quranViews.includes(to);

                if (!isGoingToQuranReader) {
                    this.isFontScaleOverlayVisible = false;
                    this.clearDeferredBootstrapCheckTimer();
                    this._deferredBootstrapCheckAttempts = 0;

                    if (this.hasRenderablePage()) {
                        this.isFittingPage = true;
                        this.clearLayoutTimers();
                    }
                    this.recoverStaleModalLifecycleState();
                    this.pruneModalLifecycleSuppression();
                    this.clearPendingPostModalTargetFit();

                    this.resetSwipeState();
                    return;
                }

                this._lastQuranReaderView = to;
                this.isFittingPage = true;
                this.clearLayoutTimers();
                this.scheduleReaderPanelLayoutRefresh();
                this.scheduleDeferredBootstrapCheck();

                [80, 220, 420].forEach((delayMs) => {
                    window.setTimeout(() => this.scheduleReaderPanelLayoutRefresh(), delayMs);
                });

                if (to !== 'quran-app-tadabbur') {
                    this.clearActivationIndexes();
                }

                if (this._bootstrapDeferred) {
                    this._bootstrapDeferred = false;
                    this.qrDebugLog('[QR:switch-view] deferred bootstrap triggered for:', to);
                    this.$nextTick(() => {
                        this.qrDebugLog(
                            '[QR:switch-view] running deferred bootstrap, visible:',
                            this.isReaderElementVisible(),
                        );
                        this.bootstrap();
                    });

                    return;
                }

                this.scheduleLayout({ revealDelayMs: 200 });
            };

            window.addEventListener('switch-view', this._onSwitchView);
            this.scheduleReaderPanelLayoutRefresh();
            this._onWirdSimulateDay = (event) => {
                const deltaDays = normalizeDayOffsetDays(event?.detail?.days ?? 1, 1);
                const nextOffset = normalizeDayOffsetDays(this.wirdDayOffsetDays + deltaDays, 0);

                this.wirdDayOffsetDays = nextOffset;
                writeWirdDayOffsetDays(nextOffset);
                this.ensureWirdDailyRecord();
            };
            window.addEventListener('quran-wird-simulate-day', this._onWirdSimulateDay);
            this._onWirdCongratsPreview = (event) => {
                this.handleWirdCompletionPreviewEvent(event?.detail ?? {});
            };
            window.addEventListener('quran-wird-congrats-preview', this._onWirdCongratsPreview);
            this._onFontScaleToggle = () => {
                this.toggleFontScaleOverlay();
            };
            window.addEventListener('quran-reader-font-scale-toggle', this._onFontScaleToggle);
            this._onHistoryManagerRequestSync = () => {
                this.queueHistoryManagerTableSync();
            };
            window.addEventListener(
                'quran-history-manager-request-sync',
                this._onHistoryManagerRequestSync,
            );
            this._onBookmarksManagerRequestSync = () => {
                this.queueBookmarksManagerTableSync();
            };
            window.addEventListener(
                'quran-bookmarks-manager-request-sync',
                this._onBookmarksManagerRequestSync,
            );
            ensureSupportLockLivewireMorphBridge();
            this._onSupportLockLivewireMorphed = () => {
                this.queueSupportLockTargetsUiSync();
            };
            window.addEventListener(
                supportLockLivewireMorphedEventName,
                this._onSupportLockLivewireMorphed,
            );

            if (this.$wire?.$hook) {
                this._stopLivewireMorphedHook = this.$wire.$hook('morphed', () => {
                    if (!this.ready || this.mushafLines.length === 0) {
                        return;
                    }

                    this.$nextTick(() => {
                        this.registerNativeInputListeners();
                        this.initializeLayoutObservers();
                        this.queueSupportLockTargetsUiSync();
                    });
                    this.scheduleLayout({ revealDelayMs: 170 });
                });
            }

            this.$nextTick(() => {
                this.registerNativeInputListeners();
                this.initializeLayoutObservers();
                this.queueSupportLockTargetsUiSync();
                this.syncCalibrationHudPosition();
            });
            this._stopIsCalibratingWatcher = this.$watch('isCalibrating', (isCalibrating) => {
                this.syncReaderChromeDocumentClass();

                if (isCalibrating) {
                    this.$nextTick(() => {
                        this.syncCalibrationHudPosition();
                    });
                }
            });
            this._stopSearchQueryWatcher = this.$watch('search.query', () => {
                this.queueSearchResultsUpdate();
            });
            this.$nextTick(() => {
                const visible = this.isReaderElementVisible();
                this.qrDebugLog(
                    '[QR:init] isReaderElementVisible:',
                    visible,
                    'ready:',
                    this.ready,
                    'maxPage:',
                    this.maxPage,
                    'mushafLines:',
                    this.mushafLines.length,
                );
                if (visible) {
                    this.bootstrap();
                } else {
                    this._bootstrapDeferred = true;
                    this.scheduleDeferredBootstrapCheck();
                }
            });
        },

        clearDeferredBootstrapCheckTimer() {
            if (this._deferredBootstrapCheckTimer === null) {
                return;
            }

            clearTimeout(this._deferredBootstrapCheckTimer);
            this._deferredBootstrapCheckTimer = null;
        },

        maybeBootstrapDeferred() {
            if (!this._bootstrapDeferred) {
                return true;
            }

            if (!this.isAnyQuranReaderViewOpen() || !this.isReaderElementVisible()) {
                return false;
            }

            this._bootstrapDeferred = false;
            this.clearDeferredBootstrapCheckTimer();
            this.qrDebugLog('[QR:deferred-bootstrap] recovered without switch-view');
            this.$nextTick(() => {
                this.bootstrap();
            });

            return true;
        },

        scheduleDeferredBootstrapCheck() {
            if (!this._bootstrapDeferred) {
                this.clearDeferredBootstrapCheckTimer();
                this._deferredBootstrapCheckAttempts = 0;

                return;
            }

            if (this._deferredBootstrapCheckTimer !== null) {
                return;
            }

            const runCheck = () => {
                this._deferredBootstrapCheckTimer = null;

                if (this.maybeBootstrapDeferred()) {
                    this._deferredBootstrapCheckAttempts = 0;

                    return;
                }

                this._deferredBootstrapCheckAttempts += 1;

                if (this._deferredBootstrapCheckAttempts > 18) {
                    this._deferredBootstrapCheckAttempts = 0;

                    return;
                }

                const delayMs = Math.min(900, 120 + this._deferredBootstrapCheckAttempts * 34);
                this._deferredBootstrapCheckTimer = window.setTimeout(runCheck, delayMs);
            };

            this._deferredBootstrapCheckTimer = window.setTimeout(runCheck, 120);
        },

        syncCalibrationHudPosition() {
            const readerPanelElement = this.$refs.readerPanel;

            if (!(readerPanelElement instanceof HTMLElement)) {
                return;
            }

            const panelRect = readerPanelElement.getBoundingClientRect();
            this.calibrationHudLeft = Math.round(panelRect.left + panelRect.width * 0.5);
            this.calibrationHudTop = Math.round(panelRect.top + panelRect.height * 0.5);
        },

        calibrationHudStyle() {
            const fallbackLeft =
                typeof window !== 'undefined' ? Math.round(window.innerWidth * 0.5) : 0;
            const fallbackTop =
                typeof window !== 'undefined' ? Math.round(window.innerHeight * 0.5) : 0;
            const left = Math.max(
                0,
                Number.isFinite(this.calibrationHudLeft) ? this.calibrationHudLeft : fallbackLeft,
            );
            const top = Math.max(
                0,
                Number.isFinite(this.calibrationHudTop) ? this.calibrationHudTop : fallbackTop,
            );

            return `left:${left}px;top:${top}px;`;
        },

        isReaderElementVisible() {
            const el = this.$el;

            if (!el) {
                return false;
            }

            return !!(el.offsetWidth || el.offsetHeight || el.getClientRects().length);
        },

        registerNativeInputListeners() {
            this.unregisterNativeInputListeners();

            const readerPanel = this.$refs.readerPanel;

            this._onWindowKeydown = (event) => {
                const key = String(event?.key ?? '');

                if (key === 'ArrowLeft') {
                    void this.onGlobalArrowNavigate('left', event);

                    return;
                }

                if (key === 'ArrowRight') {
                    void this.onGlobalArrowNavigate('right', event);
                }
            };
            window.addEventListener('keydown', this._onWindowKeydown, true);
            this._onWindowNativeVolumeButton = (event) => {
                void this.handleNativeVolumeButton(event?.detail?.direction ?? null, event);
            };
            window.addEventListener(
                'quran-native-volume-button',
                this._onWindowNativeVolumeButton,
                true,
            );

            if (!(readerPanel instanceof Element)) {
                this.syncNativeVolumeNavigation();

                return;
            }

            this._onPanelPointerDown = (event) => {
                this.onSwipeStart(event);
            };
            this._onPanelPointerMove = (event) => {
                void this.onSwipeMove(event);
            };
            this._onPanelPointerUp = (event) => {
                void this.onSwipeEnd(event);
            };
            this._onPanelPointerCancel = () => {
                this.onSwipeCancel();
            };
            this._onWindowPointerMove = (event) => {
                void this.onSwipeMove(event);
            };
            this._onWindowPointerUp = (event) => {
                void this.onSwipeEnd(event);
            };
            this._onWindowPointerCancel = () => {
                this.onSwipeCancel();
            };
            this._onPanelTouchStart = (event) => {
                this.onSwipeStart(event);
            };
            this._onPanelTouchMove = (event) => {
                void this.onSwipeMove(event);
            };
            this._onPanelTouchEnd = (event) => {
                void this.onSwipeEnd(event);
            };
            this._onPanelTouchCancel = () => {
                this.onSwipeCancel();
            };
            this._onWindowTouchMove = (event) => {
                void this.onSwipeMove(event);
            };
            this._onWindowTouchEnd = (event) => {
                void this.onSwipeEnd(event);
            };
            this._onWindowTouchCancel = () => {
                this.onSwipeCancel();
            };

            readerPanel.addEventListener('pointerdown', this._onPanelPointerDown, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('pointermove', this._onPanelPointerMove, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('pointerup', this._onPanelPointerUp, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('pointercancel', this._onPanelPointerCancel, {
                passive: true,
                capture: true,
            });
            window.addEventListener('pointermove', this._onWindowPointerMove, {
                passive: true,
                capture: true,
            });
            window.addEventListener('pointerup', this._onWindowPointerUp, {
                passive: true,
                capture: true,
            });
            window.addEventListener('pointercancel', this._onWindowPointerCancel, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('touchstart', this._onPanelTouchStart, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('touchmove', this._onPanelTouchMove, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('touchend', this._onPanelTouchEnd, {
                passive: true,
                capture: true,
            });
            readerPanel.addEventListener('touchcancel', this._onPanelTouchCancel, {
                passive: true,
                capture: true,
            });
            window.addEventListener('touchmove', this._onWindowTouchMove, {
                passive: true,
                capture: true,
            });
            window.addEventListener('touchend', this._onWindowTouchEnd, {
                passive: true,
                capture: true,
            });
            window.addEventListener('touchcancel', this._onWindowTouchCancel, {
                passive: true,
                capture: true,
            });
            this.syncNativeVolumeNavigation();
        },

        unregisterNativeInputListeners() {
            if (this._onWindowKeydown) {
                window.removeEventListener('keydown', this._onWindowKeydown, true);
                this._onWindowKeydown = null;
            }

            const readerPanel = this.$refs.readerPanel;

            if (readerPanel instanceof Element && this._onPanelPointerDown) {
                readerPanel.removeEventListener('pointerdown', this._onPanelPointerDown, true);
            }

            if (readerPanel instanceof Element && this._onPanelPointerMove) {
                readerPanel.removeEventListener('pointermove', this._onPanelPointerMove, true);
            }

            if (readerPanel instanceof Element && this._onPanelPointerUp) {
                readerPanel.removeEventListener('pointerup', this._onPanelPointerUp, true);
            }

            if (readerPanel instanceof Element && this._onPanelPointerCancel) {
                readerPanel.removeEventListener('pointercancel', this._onPanelPointerCancel, true);
            }

            if (this._onWindowPointerMove) {
                window.removeEventListener('pointermove', this._onWindowPointerMove, true);
            }

            if (this._onWindowPointerUp) {
                window.removeEventListener('pointerup', this._onWindowPointerUp, true);
            }

            if (this._onWindowPointerCancel) {
                window.removeEventListener('pointercancel', this._onWindowPointerCancel, true);
            }

            if (readerPanel instanceof Element && this._onPanelTouchStart) {
                readerPanel.removeEventListener('touchstart', this._onPanelTouchStart, true);
            }

            if (readerPanel instanceof Element && this._onPanelTouchMove) {
                readerPanel.removeEventListener('touchmove', this._onPanelTouchMove, true);
            }

            if (readerPanel instanceof Element && this._onPanelTouchEnd) {
                readerPanel.removeEventListener('touchend', this._onPanelTouchEnd, true);
            }

            if (readerPanel instanceof Element && this._onPanelTouchCancel) {
                readerPanel.removeEventListener('touchcancel', this._onPanelTouchCancel, true);
            }

            if (this._onWindowTouchMove) {
                window.removeEventListener('touchmove', this._onWindowTouchMove, true);
            }

            if (this._onWindowTouchEnd) {
                window.removeEventListener('touchend', this._onWindowTouchEnd, true);
            }

            if (this._onWindowTouchCancel) {
                window.removeEventListener('touchcancel', this._onWindowTouchCancel, true);
            }

            if (this._onWindowNativeVolumeButton) {
                window.removeEventListener(
                    'quran-native-volume-button',
                    this._onWindowNativeVolumeButton,
                    true,
                );
                this._onWindowNativeVolumeButton = null;
            }

            this._onPanelPointerDown = null;
            this._onPanelPointerMove = null;
            this._onPanelPointerUp = null;
            this._onPanelPointerCancel = null;
            this._onWindowPointerMove = null;
            this._onWindowPointerUp = null;
            this._onWindowPointerCancel = null;
            this._onPanelTouchStart = null;
            this._onPanelTouchMove = null;
            this._onPanelTouchEnd = null;
            this._onPanelTouchCancel = null;
            this._onWindowTouchMove = null;
            this._onWindowTouchEnd = null;
            this._onWindowTouchCancel = null;
            this.setAndroidVolumeNavigationEnabled(false);
        },

        isReaderPanelVisible() {
            const readerPanel = this.$refs?.readerPanel;

            return readerPanel instanceof Element && readerPanel.offsetParent !== null;
        },

        canUseNativeVolumeButtonNavigation() {
            return (
                this.nativeRuntime &&
                this.ready &&
                this.doesUseVolumeButtonsNavigation &&
                this.isReaderPanelVisible()
            );
        },

        syncNativeVolumeNavigation() {
            this.$nextTick(() => {
                this.setAndroidVolumeNavigationEnabled(this.canUseNativeVolumeButtonNavigation());
            });
        },

        setAndroidVolumeNavigationEnabled(enabled) {
            if (!this.nativeRuntime || !window.AndroidBridge) {
                return;
            }

            const normalizedEnabled = Boolean(enabled);

            if (typeof window.AndroidBridge.setQuranVolumeNavigationEnabled === 'function') {
                window.AndroidBridge.setQuranVolumeNavigationEnabled(normalizedEnabled);
            }
        },

        async handleNativeVolumeButton(direction, event = null) {
            if (!this.canUseNativeVolumeButtonNavigation()) {
                return;
            }

            const normalizedDirection = String(direction ?? '')
                .trim()
                .toLowerCase();

            if (normalizedDirection === 'next') {
                await this.goNextFromChevron('hardware-volume');

                return;
            }

            if (normalizedDirection === 'previous' || normalizedDirection === 'prev') {
                await this.goPreviousFromChevron('hardware-volume');
            }
        },

        async clearDeferredPreparationCaches() {
            this._pagePayloadByPage.clear();
            this._searchIndexPromise = null;
            this._searchRequestSerial += 1;

            if (typeof window !== 'undefined' && typeof window.caches !== 'undefined') {
                await Promise.allSettled([
                    window.caches.delete(this.cacheNames.pages),
                    window.caches.delete(this.cacheNames.search),
                ]);
            }
        },

        async callQuranPreparationMethod(methodName) {
            if (this.$wire && typeof this.$wire.call === 'function') {
                return await this.$wire.call(methodName);
            }

            const fallbackMethod = this.$wire?.[methodName];

            if (typeof fallbackMethod === 'function') {
                return await fallbackMethod.call(this.$wire);
            }

            throw new Error('Unable to communicate with the Quran reader Livewire instance.');
        },

        async requestNativeQuranPreparation() {
            if (this._quranPreparationRequestPromise) {
                return await this._quranPreparationRequestPromise;
            }

            this._quranPreparationRequestPromise = (async () => {
                return await this.callQuranPreparationMethod('prepareQuranData');
            })();

            try {
                return await this._quranPreparationRequestPromise;
            } finally {
                this._quranPreparationRequestPromise = null;
            }
        },

        async readNativeQuranPreparationStatus() {
            return await this.callQuranPreparationMethod('quranPreparationStatus');
        },

        emitNativeQuranPreparationProgress(result = {}) {
            const downloadedBytes = Number(result?.downloadedBytes ?? NaN);
            const totalBytes = Number(result?.totalBytes ?? NaN);
            const rawProgressPercent = Number(result?.progressPercent ?? NaN);
            const fallbackProgressPercent =
                Number.isFinite(downloadedBytes) && Number.isFinite(totalBytes) && totalBytes > 0
                    ? (downloadedBytes / totalBytes) * 100
                    : NaN;
            const resolvedProgressPercent = Number.isFinite(rawProgressPercent)
                ? rawProgressPercent
                : fallbackProgressPercent;
            const progressPercent = Number.isFinite(resolvedProgressPercent)
                ? Math.max(0, Math.min(100, Math.trunc(resolvedProgressPercent)))
                : null;

            window.dispatchEvent(
                new CustomEvent('quran-bootstrap-progress', {
                    detail: {
                        state: String(result?.state ?? ''),
                        message: String(result?.message ?? '').trim() || null,
                        progressPercent,
                        downloadedBytes: Number.isFinite(downloadedBytes)
                            ? Math.max(0, Math.trunc(downloadedBytes))
                            : null,
                        totalBytes: Number.isFinite(totalBytes)
                            ? Math.max(0, Math.trunc(totalBytes))
                            : null,
                    },
                }),
            );
        },

        async syncPreparedQuranPayload(payload) {
            await this.clearDeferredPreparationCaches();
            this.initialPayload = normalizePayload(payload);
            this.applyPayload(payload, {
                setPageNumber: true,
                persistPageNumber: true,
            });
            this.refreshSurahTriggerCaption(false);
            this.syncSearchActiveSurahNumber();
            this.$nextTick(() => {
                this.registerNativeInputListeners();
                this.initializeLayoutObservers();
                this.queueSupportLockTargetsUiSync();
                this.syncNativeVolumeNavigation();
            });
        },

        async queueNativeQuranPreparationInBackground() {
            if (!this.nativeRuntime || this.ready) {
                return;
            }

            try {
                await this.requestNativeQuranPreparation();
            } catch (_) {
                //
            }
        },

        async waitForNativeQuranPreparation() {
            const pollingIntervalMs = 350;
            const maxAttempts = Math.max(120, Math.ceil(180000 / pollingIntervalMs));

            for (let attempt = 0; attempt < maxAttempts; attempt += 1) {
                await wait(pollingIntervalMs);

                const result = await this.readNativeQuranPreparationStatus();

                this.emitNativeQuranPreparationProgress(result);

                if (result?.ready && result?.payload) {
                    return result;
                }

                if (String(result?.state ?? '') === 'failed') {
                    throw new Error(
                        result?.message ?? 'تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.',
                    );
                }
            }

            throw new Error('تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.');
        },

        async prepareQuranFromMainMenu(detail = {}) {
            const openGateOnSuccess = detail?.openGateOnSuccess !== false;

            if (!this.nativeRuntime || this.ready) {
                window.dispatchEvent(
                    new CustomEvent('quran-bootstrap-finished', {
                        detail: {
                            openGateOnSuccess,
                        },
                    }),
                );

                return;
            }

            if (this._quranPreparationInFlight) {
                await this._quranPreparationInFlight;

                return;
            }

            window.dispatchEvent(
                new CustomEvent('quran-bootstrap-started', {
                    detail: {
                        openGateOnSuccess,
                    },
                }),
            );

            this._quranPreparationInFlight = (async () => {
                try {
                    let result = await this.requestNativeQuranPreparation();

                    if (!result?.ready || !result?.payload) {
                        this.emitNativeQuranPreparationProgress(result);

                        if (String(result?.state ?? '') === 'failed') {
                            throw new Error(
                                result?.message ??
                                    'تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.',
                            );
                        }

                        result = await this.waitForNativeQuranPreparation();
                    }

                    await this.syncPreparedQuranPayload(result.payload);

                    window.dispatchEvent(
                        new CustomEvent('quran-bootstrap-finished', {
                            detail: {
                                openGateOnSuccess,
                            },
                        }),
                    );
                } catch (error) {
                    window.dispatchEvent(
                        new CustomEvent('quran-bootstrap-failed', {
                            detail: {
                                message:
                                    String(error?.message ?? '').trim() ||
                                    'تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.',
                            },
                        }),
                    );
                } finally {
                    this._quranPreparationInFlight = null;
                }
            })();

            await this._quranPreparationInFlight;
        },

        destroy() {
            this.unregisterNativeInputListeners();
            this.clearSearchResultsUpdateQueue();
            this.unbindSearchModalInputSyncListener();

            if (this._onWindowViewportChange) {
                window.removeEventListener('resize', this._onWindowViewportChange);
                window.removeEventListener('orientationchange', this._onWindowViewportChange);
            }

            if (this._onVisualViewportChange && window.visualViewport) {
                window.visualViewport.removeEventListener('resize', this._onVisualViewportChange);
            }

            if (this._onWindowScroll) {
                window.removeEventListener('scroll', this._onWindowScroll);
            }

            if (this._onSwitchView) {
                window.removeEventListener('switch-view', this._onSwitchView);
            }

            if (this._onQrDebugLogsToggle) {
                window.removeEventListener(
                    quranReaderDebugLogsToggleEventName,
                    this._onQrDebugLogsToggle,
                );
                this._onQrDebugLogsToggle = null;
            }

            if (this._onWirdSimulateDay) {
                window.removeEventListener('quran-wird-simulate-day', this._onWirdSimulateDay);
                this._onWirdSimulateDay = null;
            }

            if (this._onWirdCongratsPreview) {
                window.removeEventListener(
                    'quran-wird-congrats-preview',
                    this._onWirdCongratsPreview,
                );
                this._onWirdCongratsPreview = null;
            }

            if (this._onFontScaleToggle) {
                window.removeEventListener(
                    'quran-reader-font-scale-toggle',
                    this._onFontScaleToggle,
                );
                this._onFontScaleToggle = null;
            }

            if (this._pageScaleAdjustRefitRaf !== null) {
                cancelAnimationFrame(this._pageScaleAdjustRefitRaf);
                this._pageScaleAdjustRefitRaf = null;
            }

            this.clearDeferredBootstrapCheckTimer();
            this._deferredBootstrapCheckAttempts = 0;

            if (this._onWindowStorage) {
                window.removeEventListener('storage', this._onWindowStorage);
                this._onWindowStorage = null;
            }

            if (this._onHistoryManagerRequestSync) {
                window.removeEventListener(
                    'quran-history-manager-request-sync',
                    this._onHistoryManagerRequestSync,
                );
                this._onHistoryManagerRequestSync = null;
            }

            if (this._onBookmarksManagerRequestSync) {
                window.removeEventListener(
                    'quran-bookmarks-manager-request-sync',
                    this._onBookmarksManagerRequestSync,
                );
                this._onBookmarksManagerRequestSync = null;
            }

            if (typeof this._stopIsCalibratingWatcher === 'function') {
                this._stopIsCalibratingWatcher();
                this._stopIsCalibratingWatcher = null;
            }

            if (typeof this._stopSearchQueryWatcher === 'function') {
                this._stopSearchQueryWatcher();
                this._stopSearchQueryWatcher = null;
            }

            if (this._onSupportLockLivewireMorphed) {
                window.removeEventListener(
                    supportLockLivewireMorphedEventName,
                    this._onSupportLockLivewireMorphed,
                );
                this._onSupportLockLivewireMorphed = null;
            }

            if (this._supportLockTargetsSyncRaf !== null) {
                cancelAnimationFrame(this._supportLockTargetsSyncRaf);
                this._supportLockTargetsSyncRaf = null;
            }

            if (this._layoutRaf !== null) {
                cancelAnimationFrame(this._layoutRaf);
                this._layoutRaf = null;
            }

            if (this._readerPanelLayoutRaf !== null) {
                cancelAnimationFrame(this._readerPanelLayoutRaf);
                this._readerPanelLayoutRaf = null;
            }

            if (this._revealTimer !== null) {
                clearTimeout(this._revealTimer);
                this._revealTimer = null;
            }

            this.syncReaderChromeDocumentClass({ forceInactive: true });
            this.teardownLayoutObservers();

            if (this._viewportChangeDebounceTimer !== null) {
                clearTimeout(this._viewportChangeDebounceTimer);
                this._viewportChangeDebounceTimer = null;
            }

            if (this._surahTriggerTimer !== null) {
                clearTimeout(this._surahTriggerTimer);
                this._surahTriggerTimer = null;
            }

            if (this._surahTriggerCleanupTimer !== null) {
                clearTimeout(this._surahTriggerCleanupTimer);
                this._surahTriggerCleanupTimer = null;
            }

            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            if (this._swipeRevealWatchdogTimer !== null) {
                clearTimeout(this._swipeRevealWatchdogTimer);
                this._swipeRevealWatchdogTimer = null;
            }

            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }
            this._pageSliderInteractionActive = false;
            this._lastPageSliderCommitPage = 0;
            this._lastPageSliderCommitAt = 0;

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }
            this._wirdSliderPendingCommitStep = null;
            this._wirdSliderLastInputStep = null;
            this._wirdSliderLastInputAt = 0;
            this._wirdLastCommittedTargetPage = 0;
            this._wirdLastCommittedStep = null;
            this._wirdLastCommittedAt = 0;

            if (this._wirdHoverShimmerTimer !== null) {
                clearTimeout(this._wirdHoverShimmerTimer);
                this._wirdHoverShimmerTimer = null;
            }

            if (this._wirdSliderVisualTweenRaf !== null) {
                cancelAnimationFrame(this._wirdSliderVisualTweenRaf);
                this._wirdSliderVisualTweenRaf = null;
            }

            this.wirdSliderVisualStep = null;
            this.wirdHoverShimmerRunning = false;

            if (this._pageInputTweenRaf !== null) {
                cancelAnimationFrame(this._pageInputTweenRaf);
                this._pageInputTweenRaf = null;
            }

            if (this._searchModalCloseDebounceTimer !== null) {
                clearTimeout(this._searchModalCloseDebounceTimer);
                this._searchModalCloseDebounceTimer = null;
            }

            this.clearWirdEntryRevealTimers();

            if (this._modalLayoutResumeTimer !== null) {
                clearTimeout(this._modalLayoutResumeTimer);
                this._modalLayoutResumeTimer = null;
            }

            if (this._postModalTargetFitTimer !== null) {
                clearTimeout(this._postModalTargetFitTimer);
                this._postModalTargetFitTimer = null;
            }

            this._activeModalIds.clear();
            this._isModalLifecycleSettling = false;
            this._postModalTargetFitPage = 0;
            this._postModalTargetFitRetries = 0;
            this._lastPageRevealAt = 0;
            this._suppressModalLifecycleEffectsUntil = 0;
            this._suppressModalLifecycleModalIds.clear();
            this._wirdEntryLayoutSuppressedUntil = 0;

            if (this._wordPressHoldTimer !== null) {
                clearTimeout(this._wordPressHoldTimer);
                this._wordPressHoldTimer = null;
            }

            if (this._suppressWordClickResetTimer !== null) {
                clearTimeout(this._suppressWordClickResetTimer);
                this._suppressWordClickResetTimer = null;
            }

            if (this._copiedHighlightTimer !== null) {
                clearTimeout(this._copiedHighlightTimer);
                this._copiedHighlightTimer = null;
            }

            if (this._fitSanityCheckTimer !== null) {
                clearTimeout(this._fitSanityCheckTimer);
                this._fitSanityCheckTimer = null;
            }
            this._fitSanityContextKey = '';
            this._fitSanityContextAttemptCount = 0;
            this._fitSanityContextLastWidth = 0;
            this._fitSanityContextLastHeight = 0;
            this._fitSanityContextOutcome = '';
            this._fitSanitySuppressedUntil = 0;
            this._fitSanityDisabledContextKey = '';

            if (this._fitCachePersistWriteTimer !== null) {
                clearTimeout(this._fitCachePersistWriteTimer);
                this._fitCachePersistWriteTimer = null;
            }

            if (this._supportUnlockExpiryTimer !== null) {
                clearTimeout(this._supportUnlockExpiryTimer);
                this._supportUnlockExpiryTimer = null;
            }

            if (this._wirdCompletionTimer !== null) {
                clearTimeout(this._wirdCompletionTimer);
                this._wirdCompletionTimer = null;
            }
            this.isWirdCompletionVisible = false;
            this.isWirdCompletionPreviewPinned = false;

            if (this._fontReadyRecoveryTimer !== null) {
                clearTimeout(this._fontReadyRecoveryTimer);
                this._fontReadyRecoveryTimer = null;
            }

            this._fontReadyRecoveryPage = 0;
            this._fontReadyRecoveryAttemptPage = 0;
            this._fontReadyRecoveryAttemptCount = 0;
            this._fontReadyRecoveryLastAt = 0;
            this._layoutObservedViewportWidth = 0;
            this._layoutObservedViewportHeight = 0;
            this._readerPanelLayoutSerial = 0;
            this.isReaderChromeVisible = false;
            this.syncReaderChromeDocumentClass({ forceInactive: true });

            this.hideCopyFeedback();
            this.clearCopiedHighlights();
            this.clearBookmarkButtonPressState();
            this.closeSurahQuickNavigator();

            if (this.pageCounterPulse.timer !== null) {
                clearTimeout(this.pageCounterPulse.timer);
                this.pageCounterPulse.timer = null;
            }

            this.teardownSearchStreamObserver();
            this._lastSearchStreamPayloadRaw = '';
            if (this._searchResultsLeaveTimer !== null) {
                clearTimeout(this._searchResultsLeaveTimer);
                this._searchResultsLeaveTimer = null;
            }

            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                this._searchResultsAutoAnimateStop();
                this._searchResultsAutoAnimateStop = null;
            }

            if (typeof this._historyRowsAutoAnimateStop === 'function') {
                this._historyRowsAutoAnimateStop();
                this._historyRowsAutoAnimateStop = null;
            }

            if (typeof this._bookmarksRowsAutoAnimateStop === 'function') {
                this._bookmarksRowsAutoAnimateStop();
                this._bookmarksRowsAutoAnimateStop = null;
            }

            this._managerRowEffectTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._managerRowEffectTimers.clear();
            this.managerRowEffects = {
                history: {},
                bookmarks: {},
            };
            this.historyModalOpen = false;
            this.bookmarksModalOpen = false;
            this.jumpPageModalOpen = false;
            this.dispatchManagerModalsVisibilityState();

            if (typeof this._stopLivewireMorphedHook === 'function') {
                this._stopLivewireMorphedHook();
                this._stopLivewireMorphedHook = null;
            }

            this.abortActivePageLoad();
            this.abortIdleWarmupFetch();
            this.cancelIdleWarmupHandle();
            this._pendingNavigationRequest = null;
            this._navigationRevealLocked = false;
            this.isTransitioningOutPage = false;
            this.clearNavigationBurstState();
            this._layoutActivePromise = null;
            this._queuedLayoutRequest = null;
            this._bypassNextFitCache = false;
            this._suppressFitCacheWriteUntil = 0;
            this._idleWarmupQueue = [];
            this._idleWarmupQueuedPages.clear();
            this._idleWarmupInFlightPage = 0;
            this.clearFitResultCache({ persist: false });
            this.flushPersistedFitCacheWrite();
        },

        initializeLayoutObservers() {
            this.teardownLayoutObservers();

            const contentElement = this.$refs.pageContent;
            const viewportElement = this.$refs.pageViewport;

            if (contentElement instanceof Element && typeof MutationObserver !== 'undefined') {
                this._layoutMutationObserver = new MutationObserver(() => {
                    if (!this.ready || this.mushafLines.length === 0 || this.isLoadingPage) {
                        return;
                    }

                    if (!this.isReaderElementVisible()) {
                        this.qrDebugLog(
                            '[QR:MutationObserver] skipping scheduleLayout — not visible',
                        );
                        return;
                    }

                    this.qrDebugLog('[QR:MutationObserver] scheduleLayout');
                    this.scheduleLayout({ revealDelayMs: 170 });
                });

                this._layoutMutationObserver.observe(contentElement, {
                    childList: true,
                    subtree: true,
                });
            }

            if (viewportElement instanceof Element && typeof ResizeObserver !== 'undefined') {
                const viewportRect = viewportElement.getBoundingClientRect();
                this._layoutObservedViewportWidth = Math.max(
                    0,
                    Number(viewportRect?.width ?? viewportElement.clientWidth ?? 0),
                );
                this._layoutObservedViewportHeight = Math.max(
                    0,
                    Number(viewportRect?.height ?? viewportElement.clientHeight ?? 0),
                );

                this._layoutResizeObserver = new ResizeObserver((entries) => {
                    if (!this.ready || this.mushafLines.length === 0 || this.isLoadingPage) {
                        return;
                    }

                    if (this._layoutActivePromise !== null || this._revealTimer !== null) {
                        return;
                    }

                    const entry = Array.isArray(entries) ? entries[0] : null;
                    const nextObservedWidth = Math.max(
                        0,
                        Number(
                            entry?.contentRect?.width ??
                                viewportElement.clientWidth ??
                                viewportElement.getBoundingClientRect().width ??
                                0,
                        ),
                    );
                    const nextObservedHeight = Math.max(
                        0,
                        Number(
                            entry?.contentRect?.height ??
                                viewportElement.clientHeight ??
                                viewportElement.getBoundingClientRect().height ??
                                0,
                        ),
                    );

                    if (nextObservedWidth <= 0 || nextObservedHeight <= 0) {
                        return;
                    }

                    const widthDelta = Math.abs(
                        nextObservedWidth - this._layoutObservedViewportWidth,
                    );
                    const heightDelta = Math.abs(
                        nextObservedHeight - this._layoutObservedViewportHeight,
                    );

                    if (widthDelta < 1 && heightDelta < 1) {
                        return;
                    }

                    this._layoutObservedViewportWidth = nextObservedWidth;
                    this._layoutObservedViewportHeight = nextObservedHeight;
                    this.handleViewportChange();
                });

                this._layoutResizeObserver.observe(viewportElement);
            }
        },

        teardownLayoutObservers() {
            if (this._layoutMutationObserver) {
                this._layoutMutationObserver.disconnect();
                this._layoutMutationObserver = null;
            }

            if (this._layoutResizeObserver) {
                this._layoutResizeObserver.disconnect();
                this._layoutResizeObserver = null;
            }

            this._layoutObservedViewportWidth = 0;
            this._layoutObservedViewportHeight = 0;
        },

        resolveCurrentBreakpointName() {
            if (
                typeof window === 'undefined' ||
                !window.Alpine ||
                typeof window.Alpine.store !== 'function'
            ) {
                return '';
            }

            const breakpointStore = window.Alpine.store('bp');

            return String(breakpointStore?.current ?? '').trim();
        },

        viewportBucketValue(value) {
            const normalizedValue = Math.max(0, Math.round(Number(value) || 0));

            if (normalizedValue <= 0) {
                return 0;
            }

            const bucket = Math.max(8, Math.trunc(Number(fitCacheViewportBucketSizePx) || 24));

            return Math.max(bucket, Math.round(normalizedValue / bucket) * bucket);
        },

        cssLengthToPixels(value, contextElement = null, fallback = 0) {
            const rawValue = String(value ?? '').trim();
            const normalizedFallback = Number(fallback);

            if (rawValue === '') {
                return Number.isFinite(normalizedFallback) ? normalizedFallback : 0;
            }

            const directNumber = Number(rawValue);

            if (Number.isFinite(directNumber)) {
                return directNumber;
            }

            const match = rawValue.match(/^(-?\d*\.?\d+)([a-z%]*)$/i);

            if (!match) {
                return Number.isFinite(normalizedFallback) ? normalizedFallback : 0;
            }

            const amount = Number.parseFloat(match[1] ?? '0');
            const unit = String(match[2] ?? 'px').toLowerCase();

            if (!Number.isFinite(amount)) {
                return Number.isFinite(normalizedFallback) ? normalizedFallback : 0;
            }

            if (unit === '' || unit === 'px') {
                return amount;
            }

            if (unit === 'rem') {
                const rootFontSize = Number.parseFloat(
                    window.getComputedStyle(document.documentElement).fontSize,
                );

                return amount * (Number.isFinite(rootFontSize) ? rootFontSize : 16);
            }

            if (unit === 'em') {
                const fontSize =
                    contextElement instanceof Element
                        ? Number.parseFloat(window.getComputedStyle(contextElement).fontSize)
                        : 16;

                return amount * (Number.isFinite(fontSize) ? fontSize : 16);
            }

            if (['vh', 'svh', 'lvh', 'dvh'].includes(unit)) {
                const viewportHeight = Number(window.visualViewport?.height ?? window.innerHeight);

                return (amount / 100) * (Number.isFinite(viewportHeight) ? viewportHeight : 0);
            }

            if (['vw', 'svw', 'lvw', 'dvw'].includes(unit)) {
                const viewportWidth = Number(window.visualViewport?.width ?? window.innerWidth);

                return (amount / 100) * (Number.isFinite(viewportWidth) ? viewportWidth : 0);
            }

            return Number.isFinite(normalizedFallback) ? normalizedFallback : 0;
        },

        cssCustomLengthPixels(computedStyles, propertyName, contextElement = null, fallback = 0) {
            const rawValue = String(
                computedStyles?.getPropertyValue?.(propertyName) ||
                    (typeof window.cssVar === 'function'
                        ? window.cssVar(propertyName, contextElement ?? document.documentElement)
                        : '') ||
                    '',
            ).trim();

            return this.cssLengthToPixels(rawValue, contextElement, fallback);
        },

        normalizeFitCacheEntry(entry) {
            if (!entry || typeof entry !== 'object' || Array.isArray(entry)) {
                return null;
            }

            if (!entry.layout || typeof entry.layout !== 'object' || Array.isArray(entry.layout)) {
                return null;
            }

            const scale = Number(entry.scale);

            if (!Number.isFinite(scale) || scale <= 0) {
                return null;
            }

            return {
                layout: { ...entry.layout },
                scale: Number(scale.toFixed(4)),
            };
        },

        trimFitResultCache() {
            while (this._fitResultByContext.size > fitResultCacheLimit) {
                const oldestCacheKey = this._fitResultByContext.keys().next().value;

                if (!oldestCacheKey) {
                    break;
                }

                this._fitResultByContext.delete(oldestCacheKey);
            }
        },

        rememberFitResult(cacheKey, entry, { persist = true } = {}) {
            if (String(cacheKey ?? '').trim() === '') {
                return;
            }

            const normalizedEntry = this.normalizeFitCacheEntry(entry);

            if (!normalizedEntry) {
                return;
            }

            this._fitResultByContext.set(cacheKey, normalizedEntry);
            this.trimFitResultCache();

            if (persist) {
                this.queuePersistedFitCacheWrite();
            }
        },

        forgetFitResult(cacheKey, { persist = true } = {}) {
            if (String(cacheKey ?? '').trim() === '') {
                return;
            }

            this._fitResultByContext.delete(cacheKey);

            if (persist) {
                this.queuePersistedFitCacheWrite();
            }
        },

        syncFitCacheBreakpoint({ persist = true } = {}) {
            const currentBreakpoint = this.resolveCurrentBreakpointName();

            if (this._fitCacheBreakpoint === '') {
                this._fitCacheBreakpoint = currentBreakpoint;
                this.storage.fitCacheBreakpoint = currentBreakpoint;

                return;
            }

            if (currentBreakpoint === this._fitCacheBreakpoint) {
                return;
            }

            this._fitCacheBreakpoint = currentBreakpoint;
            this.storage.fitCacheBreakpoint = currentBreakpoint;
            this.clearFitResultCache({ persist: false });

            if (persist && shouldPersistFitCacheAcrossReloads) {
                writeLocalStorage(fitCacheStorageKey, {
                    version: fitCacheStorageVersion,
                    breakpoint: currentBreakpoint,
                    updated_at: Date.now(),
                    entries: {},
                    order: [],
                });
            }
        },

        hydratePersistedFitCache() {
            if (!shouldPersistFitCacheAcrossReloads) {
                this._fitResultByContext.clear();
                this._fitCacheBreakpoint = this.resolveCurrentBreakpointName();
                this.storage.fitCacheBreakpoint = this._fitCacheBreakpoint;

                return;
            }

            const persistedCache = readLocalStorage(fitCacheStorageKey, null);

            if (!persistedCache || typeof persistedCache !== 'object') {
                return;
            }

            const persistedVersion = Math.trunc(Number(persistedCache?.version ?? 0));

            if (persistedVersion !== fitCacheStorageVersion) {
                return;
            }

            const persistedBreakpoint = String(persistedCache?.breakpoint ?? '').trim();
            const currentBreakpoint = this.resolveCurrentBreakpointName();

            if (
                persistedBreakpoint !== '' &&
                currentBreakpoint !== '' &&
                persistedBreakpoint !== currentBreakpoint
            ) {
                writeLocalStorage(fitCacheStorageKey, {
                    version: fitCacheStorageVersion,
                    breakpoint: currentBreakpoint,
                    updated_at: Date.now(),
                    entries: {},
                    order: [],
                });

                return;
            }

            const persistedEntries =
                persistedCache?.entries &&
                typeof persistedCache.entries === 'object' &&
                !Array.isArray(persistedCache.entries)
                    ? persistedCache.entries
                    : {};
            const persistedOrder = Array.isArray(persistedCache?.order)
                ? persistedCache.order.map((key) => String(key ?? '').trim()).filter(Boolean)
                : Object.keys(persistedEntries);

            this._fitResultByContext.clear();

            persistedOrder.forEach((cacheKey) => {
                const normalizedEntry = this.normalizeFitCacheEntry(persistedEntries[cacheKey]);

                if (!normalizedEntry) {
                    return;
                }

                this._fitResultByContext.set(cacheKey, normalizedEntry);
            });

            this.trimFitResultCache();
            this._fitCacheBreakpoint = currentBreakpoint;
            this.storage.fitCacheBreakpoint = currentBreakpoint;
        },

        queuePersistedFitCacheWrite(delayMs = 140) {
            if (typeof window === 'undefined') {
                return;
            }

            if (!shouldPersistFitCacheAcrossReloads) {
                return;
            }

            if (this._fitCachePersistWriteTimer !== null) {
                clearTimeout(this._fitCachePersistWriteTimer);
                this._fitCachePersistWriteTimer = null;
            }

            this._fitCachePersistWriteTimer = window.setTimeout(
                () => {
                    this._fitCachePersistWriteTimer = null;
                    this.flushPersistedFitCacheWrite();
                },
                Math.max(24, Math.trunc(Number(delayMs) || 140)),
            );
        },

        flushPersistedFitCacheWrite() {
            const currentBreakpoint = this.resolveCurrentBreakpointName();
            const entries = {};
            const order = [];
            const cacheEntries = Array.from(this._fitResultByContext.entries()).slice(
                -fitResultCacheLimit,
            );

            cacheEntries.forEach(([cacheKey, entry]) => {
                const normalizedEntry = this.normalizeFitCacheEntry(entry);

                if (!normalizedEntry) {
                    return;
                }

                entries[cacheKey] = normalizedEntry;
                order.push(cacheKey);
            });

            writeLocalStorage(fitCacheStorageKey, {
                version: fitCacheStorageVersion,
                breakpoint: currentBreakpoint,
                viewport_bucket: {
                    width: this.viewportBucketValue(window.innerWidth),
                    height: this.viewportBucketValue(window.innerHeight),
                },
                updated_at: Date.now(),
                entries,
                order,
            });
        },

        async bootstrap() {
            this.qrDebugLog(
                '[QR:bootstrap] START, visible:',
                this.isReaderElementVisible(),
                'ready:',
                this.ready,
                'maxPage:',
                this.maxPage,
                'pageNumber:',
                this.pageNumber,
            );
            await this.ensurePersistentStorage();
            this.syncSupportLockTargetsUi();
            this.syncFitCacheBreakpoint({ persist: false });
            this.hydratePersistedFitCache();
            this._startupCalibrationPending = true;
            this.syncReaderChromeDocumentClass();
            window.dispatchEvent(new CustomEvent('quran-reader-calibration-started'));

            try {
                if (this._startupRestoreInFlight instanceof Promise) {
                    this.qrDebugLog('[QR:bootstrap] awaiting _startupRestoreInFlight');
                    try {
                        await this._startupRestoreInFlight;
                    } catch (_) {
                        // Ignore startup restore aborts; ensureCurrentPageLoaded() will recover.
                    }
                }

                this.qrDebugLog(
                    '[QR:bootstrap] before calibrate, visible:',
                    this.isReaderElementVisible(),
                );
                await this.calibrateGlobalFitLayoutFromReferencePage();
                this.qrDebugLog(
                    '[QR:bootstrap] after calibrate, _globalFitCalibrationLayout:',
                    !!this._globalFitCalibrationLayout,
                    '_globalFitCalibrationScale:',
                    this._globalFitCalibrationScale,
                );
                await this.ensureCurrentPageLoaded();
                this.qrDebugLog(
                    '[QR:bootstrap] after ensureCurrentPageLoaded, pageNumber:',
                    this.pageNumber,
                );
                await this.runStartupFinalFitPass();
                this.qrDebugLog(
                    '[QR:bootstrap] after runStartupFinalFitPass, pageScale:',
                    this.pageScale,
                    'isFittingPage:',
                    this.isFittingPage,
                );
                this.queueStartupPreload();
                this.scheduleIdleWarmup();
                this.warmSearchIndex();
                this.scheduleManagerModalsPrewarm();
            } catch (error) {
                this.qrDebugError('[QR:bootstrap] ERROR:', error);
            } finally {
                this._startupCalibrationPending = false;
                this.hasCompletedInitialMushafPreparation = true;
                this.syncReaderChromeDocumentClass();
                window.dispatchEvent(new CustomEvent('quran-reader-calibration-finished'));
                this.qrDebugLog(
                    '[QR:bootstrap] DONE, hasCompletedInitialMushafPreparation:',
                    true,
                    'pageScale:',
                    this.pageScale,
                );
            }
        },

        captureCurrentFitLayoutFromRoot() {
            const rootElement = this.$el.firstElementChild;
            const pageContentElement = this.$refs?.pageContent;

            if (!(rootElement instanceof HTMLElement)) {
                return null;
            }

            const styles = window.getComputedStyle(rootElement);
            const pageContentStyles =
                pageContentElement instanceof HTMLElement
                    ? window.getComputedStyle(pageContentElement)
                    : null;
            const readLayoutNumber = (propertyName, fallback) => {
                const inlineValue = Number.parseFloat(
                    rootElement.style.getPropertyValue(propertyName),
                );

                if (Number.isFinite(inlineValue)) {
                    return inlineValue;
                }

                const computedValue = Number.parseFloat(styles.getPropertyValue(propertyName));

                return Number.isFinite(computedValue) ? computedValue : fallback;
            };
            const readPageScaleNumber = (fallback) => {
                if (pageContentElement instanceof HTMLElement) {
                    const inlineValue = Number.parseFloat(
                        pageContentElement.style.getPropertyValue('--quran-page-scale'),
                    );

                    if (Number.isFinite(inlineValue)) {
                        return inlineValue;
                    }

                    const computedValue = Number.parseFloat(
                        pageContentStyles?.getPropertyValue('--quran-page-scale') ?? '',
                    );

                    if (Number.isFinite(computedValue)) {
                        return computedValue;
                    }
                }

                return readLayoutNumber('--quran-page-scale', fallback);
            };

            return {
                pageTypeScale: Math.max(0.2, readLayoutNumber('--quran-page-type-scale', 1)),
                pageLeadingMultiplier: Math.max(
                    0.25,
                    readLayoutNumber('--quran-page-leading-multiplier', 1),
                ),
                pageGapMultiplier: Math.max(0, readLayoutNumber('--quran-page-gap-multiplier', 1)),
                pageSurahHeaderScale: Math.max(
                    0.5,
                    readLayoutNumber('--quran-page-surah-header-scale', 1),
                ),
                basmallahBottomGapScale: readLayoutNumber(
                    '--quran-basmallah-bottom-gap-scale',
                    defaultBasmallahBottomGapScale,
                ),
                pageScale: Math.max(0.05, Number(this.pageScale) || readPageScaleNumber(1)),
            };
        },

        async calibrateGlobalFitLayoutFromReferencePage(
            referencePage = fitCalibrationReferencePage,
        ) {
            const normalizedReferencePage = clampPage(referencePage, this.maxPage);

            if (normalizedReferencePage <= 0) {
                return;
            }

            const startupTargetPage = clampPage(
                Number(this._startupTargetPageNumber ?? this.pageInput ?? this.pageNumber),
                this.maxPage,
            );

            this.isCalibrating = true;

            try {
                const referencePayload = await this.getPagePayload(normalizedReferencePage, {
                    preferCache: true,
                });

                if (!referencePayload) {
                    return;
                }

                this.applyPayload(referencePayload, {
                    setPageNumber: true,
                    persistPageNumber: false,
                });
                await this.nextTickAsync();
                await this.waitForPageFontReady();
                await this.resolveWithTimeout(document.fonts?.ready, 3200, {
                    timeoutValue: 'timeout',
                });
                await this.waitForStablePageFrame({
                    maxFrames: 22,
                    requiredStableFrames: 3,
                    tolerancePx: 0.8,
                });
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 110,
                    maxAttempts: 5,
                    useIdleFit: true,
                });
                await this.waitForStableRenderedText(12);
                const rootElement = this.$el.firstElementChild;
                const frameElement = this.$refs.pageFrame;
                const contentElement = this.$refs.pageContent;

                if (
                    rootElement instanceof HTMLElement &&
                    frameElement instanceof HTMLElement &&
                    contentElement instanceof HTMLElement
                ) {
                    const frameRect = frameElement.getBoundingClientRect();
                    const frameParentRect =
                        frameElement.parentElement?.getBoundingClientRect?.() ?? null;
                    const styles = window.getComputedStyle(rootElement);
                    const fitTargetWidthRatio = Math.min(
                        0.95,
                        Math.max(
                            0.55,
                            Number.parseFloat(
                                styles.getPropertyValue('--quran-fit-target-width-ratio'),
                            ) || 0.8,
                        ),
                    );
                    const fitAreaPaddingX = Math.max(
                        0,
                        this.cssCustomLengthPixels(
                            styles,
                            '--quran-fit-area-pad-x',
                            rootElement,
                            0,
                        ),
                    );
                    const fitAreaPaddingY = Math.max(
                        0,
                        this.cssCustomLengthPixels(
                            styles,
                            '--quran-fit-area-pad-y',
                            rootElement,
                            0,
                        ),
                    );
                    const fitHeightRatio = Math.min(
                        1,
                        Math.max(
                            0.7,
                            Number.parseFloat(
                                styles.getPropertyValue('--quran-fit-height-ratio'),
                            ) || 1,
                        ),
                    );
                    const availableWidth = Math.max(
                        1,
                        Number(
                            frameParentRect?.width ??
                                frameRect?.width ??
                                frameElement.parentElement?.clientWidth ??
                                frameElement.clientWidth ??
                                1,
                        ) -
                            fitAreaPaddingX * 2,
                    );
                    const availableHeight = Math.max(
                        1,
                        (Number(frameRect?.height ?? frameElement.clientHeight ?? 1) -
                            fitAreaPaddingY * 2) *
                            fitHeightRatio,
                    );
                    const targetWidth = Math.max(1, availableWidth * fitTargetWidthRatio);
                    const targetHeight = Math.max(1, availableHeight);
                    const measured = this.measureRenderedBounds(contentElement, {
                        useRobustWidth: false,
                    });
                    const downscaleCorrection = Math.min(
                        1,
                        targetWidth / Math.max(1, measured.width),
                        targetHeight / Math.max(1, measured.height),
                    );

                    if (Number.isFinite(downscaleCorrection) && downscaleCorrection < 0.999) {
                        const minScale = Math.max(
                            0.05,
                            Number.parseFloat(styles.getPropertyValue('--quran-min-page-scale')) ||
                                0.1,
                        );
                        const maxScale = Math.max(
                            minScale,
                            Number.parseFloat(styles.getPropertyValue('--quran-max-page-scale')) ||
                                1,
                        );
                        const currentScale = Math.max(
                            minScale,
                            Math.min(
                                maxScale,
                                Number(this.pageScale) ||
                                    Number.parseFloat(
                                        contentElement.style.getPropertyValue('--quran-page-scale'),
                                    ) ||
                                    1,
                            ),
                        );
                        const correctedScale = Math.max(
                            minScale,
                            Math.min(
                                maxScale,
                                Number((currentScale * downscaleCorrection).toFixed(4)),
                            ),
                        );

                        this.pageScale = correctedScale;
                        this.setCurrentPageScale(correctedScale, { forFitting: true });
                    }
                }

                const capturedLayout = this.captureCurrentFitLayoutFromRoot();

                if (capturedLayout) {
                    this._globalFitCalibrationLayout = capturedLayout;
                    this._globalFitCalibrationScale = Math.max(
                        0.05,
                        Number(capturedLayout.pageScale ?? 1),
                    );
                    this._globalFitCalibrationPageNumber = normalizedReferencePage;
                }
            } catch (error) {
                this._globalFitCalibrationLayout = null;
                this._globalFitCalibrationScale = 0;
                this._globalFitCalibrationPageNumber = 0;
                this.traceReaderReveal('startup-global-fit-calibration-failed', {
                    page: normalizedReferencePage,
                    name: String(error?.name ?? 'Error'),
                    message: String(error?.message ?? ''),
                });
            } finally {
                this.isCalibrating = false;
                this.pageInput = startupTargetPage;
                this._lastPageInputVisualValue = startupTargetPage;
                this._bypassNextFitCache = true;
            }
        },

        async ensurePersistentStorage() {
            if (typeof navigator === 'undefined' || !navigator.storage) {
                return;
            }

            try {
                this.storage.isPersisted = Boolean(await navigator.storage.persisted());

                if (!this.storage.isPersisted) {
                    this.storage.persistRequested = true;
                    this.storage.isPersisted = Boolean(await navigator.storage.persist());
                }
            } catch (_) {
                this.storage.isPersisted = false;
            }
        },

        supportUnlockMode() {
            return String(this.supportUnlock?.mode ?? 'locked')
                .trim()
                .toLowerCase();
        },

        isSupportUnlockPermanent() {
            return this.supportUnlockMode() === supportUnlockModePermanent;
        },

        isSupportUnlockWeeklyActive(referenceTime = Date.now()) {
            if (this.supportUnlockMode() !== supportUnlockModeWeekly) {
                return false;
            }

            const expiresAt = Number(this.supportUnlock?.expiresAt ?? 0);

            return Number.isFinite(expiresAt) && expiresAt > Math.trunc(Number(referenceTime) || 0);
        },

        isSupportLockActive() {
            if (this.isSupportUnlockPermanent()) {
                return false;
            }

            if (this.isSupportUnlockWeeklyActive()) {
                return false;
            }

            if (this.supportUnlockMode() === supportUnlockModeWeekly) {
                this.syncSupportUnlockState({ persist: true });
            }

            return true;
        },

        scheduleSupportUnlockExpiryTimer() {
            if (this._supportUnlockExpiryTimer !== null) {
                clearTimeout(this._supportUnlockExpiryTimer);
                this._supportUnlockExpiryTimer = null;
            }

            if (!this.isSupportUnlockWeeklyActive()) {
                return;
            }

            const expiresAt = Math.max(0, Math.trunc(Number(this.supportUnlock?.expiresAt ?? 0)));
            const remainingMs = expiresAt - Date.now();

            if (remainingMs <= 0) {
                this.syncSupportUnlockState({ persist: true });
                this.syncSupportLockTargetsUi();

                return;
            }

            this._supportUnlockExpiryTimer = window.setTimeout(
                () => {
                    this._supportUnlockExpiryTimer = null;
                    this.syncSupportUnlockState({ persist: true });
                    this.syncSupportLockTargetsUi();
                },
                Math.max(900, remainingMs),
            );
        },

        syncSupportUnlockState({ persist = true } = {}) {
            const normalized = readSupportUnlockState();

            this.supportUnlock = {
                mode: normalized.mode,
                grantedAt: normalized.granted_at,
                expiresAt: normalized.expires_at,
            };

            if (persist) {
                writeSupportUnlockState(normalized);
            }

            this.scheduleSupportUnlockExpiryTimer();

            return normalized;
        },

        openSupportUnlockModal() {
            window.dispatchEvent(new CustomEvent('open-support-unlock-modal'));
        },

        async applySupportUnlockDecision(mode = null) {
            const normalizedMode = String(mode ?? '')
                .trim()
                .toLowerCase();

            if (
                normalizedMode !== supportUnlockModePermanent &&
                normalizedMode !== supportUnlockModeWeekly
            ) {
                this.syncSupportUnlockState({ persist: true });
                this.syncSupportLockTargetsUi();

                return;
            }

            const grantedAt = Date.now();
            const persistedState =
                normalizedMode === supportUnlockModePermanent
                    ? writeSupportUnlockState({
                          version: supportUnlockStorageVersion,
                          mode: supportUnlockModePermanent,
                          granted_at: grantedAt,
                          expires_at: null,
                      })
                    : writeSupportUnlockState({
                          version: supportUnlockStorageVersion,
                          mode: supportUnlockModeWeekly,
                          granted_at: grantedAt,
                          expires_at: grantedAt + supportUnlockWeeklyDurationMs,
                      });

            this.supportUnlock = {
                mode: persistedState.mode,
                grantedAt: persistedState.granted_at,
                expiresAt: persistedState.expires_at,
            };

            if (normalizedMode === supportUnlockModePermanent) {
                await this.ensurePersistentStorage();
            }

            this.scheduleSupportUnlockExpiryTimer();
            this.syncSupportLockTargetsUi();
        },

        handleSupportLockTargetInteraction(event) {
            if (!this.isSupportLockActive()) {
                return;
            }

            if (event.type === 'keydown') {
                const pressedKey = String(event.key ?? '');

                if (pressedKey !== 'Enter' && pressedKey !== ' ') {
                    return;
                }
            }

            if (typeof event.preventDefault === 'function') {
                event.preventDefault();
            }

            if (typeof event.stopPropagation === 'function') {
                event.stopPropagation();
            }

            if (typeof event.stopImmediatePropagation === 'function') {
                event.stopImmediatePropagation();
            }

            this.openSupportUnlockModal();
        },

        bindSupportLockTarget(targetElement) {
            if (!(targetElement instanceof HTMLElement)) {
                return;
            }

            if (targetElement.dataset.supportLockBound === '1') {
                return;
            }

            if (targetElement.getAttribute('x-on:pointerdown.capture') !== null) {
                return;
            }

            targetElement.dataset.supportLockBound = '1';

            const onTargetInteraction = (event) => {
                this.handleSupportLockTargetInteraction(event);
            };

            targetElement.addEventListener('pointerdown', onTargetInteraction, true);
            targetElement.addEventListener('keydown', onTargetInteraction, true);
        },

        ensureSupportLockBadge(targetElement) {
            if (!(targetElement instanceof HTMLElement)) {
                return null;
            }

            const existingBadge = targetElement.querySelector('[data-support-lock-badge]');

            if (existingBadge instanceof HTMLElement) {
                return existingBadge;
            }

            const badgeElement = document.createElement('span');
            badgeElement.setAttribute('data-support-lock-badge', '1');
            badgeElement.className = 'quran-support-lock-badge';
            badgeElement.innerHTML = `
                <span class="quran-support-lock-badge__icon quran-support-lock-badge__icon--locked">${supportLockClosedOutlineIconSvg}</span>
            `;
            targetElement.appendChild(badgeElement);

            return badgeElement;
        },

        removeSupportLockBadge(targetElement) {
            if (!(targetElement instanceof HTMLElement)) {
                return;
            }

            const existingBadge = targetElement.querySelector('[data-support-lock-badge]');

            if (existingBadge instanceof HTMLElement) {
                existingBadge.remove();
            }
        },

        queueSupportLockTargetsUiSync() {
            if (typeof window === 'undefined') {
                return;
            }

            if (this._supportLockTargetsSyncRaf !== null) {
                return;
            }

            this._supportLockTargetsSyncRaf = window.requestAnimationFrame(() => {
                this._supportLockTargetsSyncRaf = null;
                this.syncSupportLockTargetsUi();
            });
        },

        syncSupportLockTargetsUi() {
            if (typeof document === 'undefined') {
                return;
            }

            const isLocked = this.isSupportLockActive();
            const targets = Array.from(document.querySelectorAll('[data-support-lock-target]'));

            targets.forEach((targetElement) => {
                if (!(targetElement instanceof HTMLElement)) {
                    return;
                }

                this.bindSupportLockTarget(targetElement);
                targetElement.classList.add('quran-support-lock-target');
                targetElement.classList.toggle('quran-support-lock-target--locked', isLocked);

                if (isLocked) {
                    targetElement.setAttribute('aria-disabled', 'true');
                    this.ensureSupportLockBadge(targetElement);
                } else {
                    targetElement.removeAttribute('aria-disabled');
                    targetElement.classList.remove('quran-support-lock-target--unlocked');
                    this.removeSupportLockBadge(targetElement);
                }

                const isNaturallyFocusable = [
                    'A',
                    'BUTTON',
                    'INPUT',
                    'SELECT',
                    'TEXTAREA',
                ].includes(targetElement.tagName);

                if (isLocked && !isNaturallyFocusable && !targetElement.hasAttribute('tabindex')) {
                    targetElement.setAttribute('tabindex', '0');
                    targetElement.dataset.supportLockTabInjected = '1';
                }

                if (!isLocked && targetElement.dataset.supportLockTabInjected === '1') {
                    targetElement.removeAttribute('tabindex');
                    delete targetElement.dataset.supportLockTabInjected;
                }
            });
        },

        persistLastPageNumber(pageNumber = this.pageNumber, { force = false } = {}) {
            if (this.wirdModeActive && !force) {
                return;
            }

            writeLastPageNumber(pageNumber);
        },

        normalizeIntegerFlag(value, fallback = 0, { min = 0, max = Number.MAX_SAFE_INTEGER } = {}) {
            const numericValue = Number(value);
            const fallbackValue = Number.isFinite(Number(fallback)) ? Number(fallback) : 0;
            const normalizedValue = Number.isFinite(numericValue)
                ? Math.trunc(numericValue)
                : fallbackValue;

            return Math.max(min, Math.min(max, normalizedValue));
        },

        resolveReaderMaxPage() {
            const currentMax = Number(this.maxPage ?? 0);
            const initialMax = Number(this.initialPayload?.maxPage ?? 0);

            if (Number.isFinite(currentMax) && currentMax > 0) {
                return Math.trunc(currentMax);
            }

            if (Number.isFinite(initialMax) && initialMax > 0) {
                return Math.trunc(initialMax);
            }

            return 604;
        },

        normalizeWirdFrequencyMode(value, fallback = wirdFrequencyModeMonthly) {
            return this.normalizeIntegerFlag(value, fallback, {
                min: wirdFrequencyModeMonthly,
                max: wirdFrequencyModeDaily,
            });
        },

        normalizeWirdDateKey(value, fallback = currentDateKey()) {
            const normalized = String(value ?? '').trim();

            if (/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
                return normalized;
            }

            return fallback;
        },

        resolveDaysInMonthFromDateKey(dateKey = currentDateKey()) {
            const normalizedDateKey = this.normalizeWirdDateKey(dateKey);
            const year = Number(normalizedDateKey.slice(0, 4));
            const month = Number(normalizedDateKey.slice(5, 7));

            if (!Number.isFinite(year) || !Number.isFinite(month) || month < 1 || month > 12) {
                return 30;
            }

            return new Date(year, month, 0).getDate();
        },

        resolveWirdKhatmatTargetMax({ frequencyMode = this.wirdFrequencyMode } = {}) {
            const normalizedFrequencyMode = this.normalizeWirdFrequencyMode(
                frequencyMode,
                wirdFrequencyModeMonthly,
            );

            if (normalizedFrequencyMode === wirdFrequencyModeDaily) {
                return wirdDailyKhatmatTargetMax;
            }

            return wirdMonthlyKhatmatTargetMax;
        },

        normalizeWirdKhatmatTarget(
            value,
            fallback = 1,
            { frequencyMode = this.wirdFrequencyMode } = {},
        ) {
            return this.normalizeIntegerFlag(value, fallback, {
                min: wirdKhatmatTargetMin,
                max: this.resolveWirdKhatmatTargetMax({
                    frequencyMode,
                }),
            });
        },

        resolveWirdRequiredPages({
            dateKey = currentDateKey(),
            frequencyMode = this.wirdFrequencyMode,
            khatmatTarget = this.wirdKhatmatTarget,
        } = {}) {
            const maxPage = this.resolveReaderMaxPage();
            const normalizedFrequencyMode = this.normalizeWirdFrequencyMode(
                frequencyMode,
                wirdFrequencyModeMonthly,
            );
            const normalizedDateKey = this.normalizeWirdDateKey(dateKey);
            const normalizedKhatmatTarget = this.normalizeWirdKhatmatTarget(khatmatTarget, 1, {
                frequencyMode: normalizedFrequencyMode,
            });

            if (normalizedFrequencyMode === wirdFrequencyModeDaily) {
                return maxPage * normalizedKhatmatTarget;
            }

            const daysInMonth = Math.max(1, this.resolveDaysInMonthFromDateKey(normalizedDateKey));

            return Math.max(1, Math.ceil((maxPage * normalizedKhatmatTarget) / daysInMonth));
        },

        normalizeWirdState(rawState = null) {
            const maxPage = this.resolveReaderMaxPage();
            const maxRequiredPages = maxPage * wirdDailyKhatmatTargetMax;
            const stateInput =
                rawState && typeof rawState === 'object' && !Array.isArray(rawState)
                    ? rawState
                    : {};
            const normalizedState = {
                version: wirdProgressStorageVersion,
                nextAbsolutePage: Math.max(
                    1,
                    this.normalizeIntegerFlag(stateInput?.nextAbsolutePage, 1, {
                        min: 1,
                    }),
                ),
                dayRecords: {},
            };
            const now = Date.now();
            const cutoffMs = now - wirdRecordRetentionDays * 24 * 60 * 60 * 1000;
            const dayRecords =
                stateInput?.dayRecords &&
                typeof stateInput.dayRecords === 'object' &&
                !Array.isArray(stateInput.dayRecords)
                    ? stateInput.dayRecords
                    : {};

            Object.entries(dayRecords).forEach(([rawDateKey, rawRecord]) => {
                const dateKey = this.normalizeWirdDateKey(rawDateKey, '');

                if (dateKey === '') {
                    return;
                }

                const parsedRecord =
                    rawRecord && typeof rawRecord === 'object' && !Array.isArray(rawRecord)
                        ? rawRecord
                        : {};
                const updatedAt = this.normalizeIntegerFlag(parsedRecord?.updatedAt, now, {
                    min: 0,
                });

                if (updatedAt < cutoffMs) {
                    return;
                }

                const startAbsolutePage = Math.max(
                    1,
                    this.normalizeIntegerFlag(
                        parsedRecord?.startAbsolutePage,
                        normalizedState.nextAbsolutePage,
                        {
                            min: 1,
                        },
                    ),
                );
                const requiredPages = this.normalizeIntegerFlag(parsedRecord?.requiredPages, 1, {
                    min: 1,
                    max: maxRequiredPages,
                });
                const maxStep = Math.max(0, requiredPages - 1);
                const currentStep = this.normalizeIntegerFlag(parsedRecord?.currentStep, 0, {
                    min: 0,
                    max: maxStep,
                });
                const progressStep = this.normalizeIntegerFlag(
                    parsedRecord?.progressStep,
                    currentStep,
                    {
                        min: 0,
                        max: maxStep,
                    },
                );
                const completed =
                    Boolean(parsedRecord?.completed) ||
                    currentStep >= maxStep ||
                    progressStep >= maxStep;

                normalizedState.dayRecords[dateKey] = {
                    startAbsolutePage,
                    requiredPages,
                    currentStep,
                    progressStep: completed ? maxStep : Math.max(currentStep, progressStep),
                    completed,
                    signature: String(parsedRecord?.signature ?? '').trim(),
                    createdAt: this.normalizeIntegerFlag(parsedRecord?.createdAt, updatedAt, {
                        min: 0,
                    }),
                    updatedAt,
                };
            });

            return normalizedState;
        },

        hydrateWirdState() {
            this.syncWirdStorageState({
                force: true,
                clearDailyRecord: true,
            });

            return this.wirdState;
        },

        persistWirdState() {
            if (!this.wirdState || typeof this.wirdState !== 'object') {
                this.wirdState = this.normalizeWirdState(null);
            }

            writeLocalStorage(wirdProgressStorageKey, this.wirdState);
            this._wirdStateStorageRawSnapshot = readLocalStorageRaw(wirdProgressStorageKey);
        },

        syncWirdStorageState({ force = false, clearDailyRecord = false } = {}) {
            const progressRaw = readLocalStorageRaw(wirdProgressStorageKey);
            const shouldSyncProgress = force || progressRaw !== this._wirdStateStorageRawSnapshot;

            if (shouldSyncProgress) {
                const parsedState =
                    progressRaw === null ? null : readLocalStorage(wirdProgressStorageKey, null);

                this.wirdState = this.normalizeWirdState(parsedState);
                this._wirdStateStorageRawSnapshot = progressRaw;

                if (clearDailyRecord) {
                    this.wirdDailyRecord = null;
                }
            }

            const dayOffsetRaw = readLocalStorageRaw(wirdDayOffsetStorageKey);
            const shouldSyncDayOffset =
                force || dayOffsetRaw !== this._wirdDayOffsetStorageRawSnapshot;

            if (shouldSyncDayOffset) {
                this.wirdDayOffsetDays = normalizeDayOffsetDays(
                    dayOffsetRaw === null ? 0 : readLocalStorage(wirdDayOffsetStorageKey, 0),
                    0,
                );
                this._wirdDayOffsetStorageRawSnapshot = dayOffsetRaw;

                if (clearDailyRecord) {
                    this.wirdDailyRecord = null;
                }
            }
        },

        absolutePageToPageNumber(absolutePage) {
            const maxPage = this.resolveReaderMaxPage();
            const normalizedAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(absolutePage, 1, { min: 1 }),
            );

            return ((normalizedAbsolutePage - 1) % maxPage) + 1;
        },

        reconcileWirdNextAbsolutePage(record = this.wirdDailyRecord) {
            if (
                !record ||
                typeof record !== 'object' ||
                !this.wirdState ||
                typeof this.wirdState !== 'object'
            ) {
                return;
            }

            const startAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(record?.startAbsolutePage, 1, { min: 1 }),
            );
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.normalizeIntegerFlag(record?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });
            const progressStep = this.normalizeIntegerFlag(record?.progressStep, currentStep, {
                min: 0,
                max: maxStep,
            });
            const nextAbsolutePage = record?.completed
                ? startAbsolutePage + requiredPages
                : startAbsolutePage + progressStep;

            this.wirdState.nextAbsolutePage = Math.max(1, nextAbsolutePage);
        },

        resolveWirdRecordSignature() {
            const normalizedFrequencyMode = this.normalizeWirdFrequencyMode(
                this.wirdFrequencyMode,
                wirdFrequencyModeMonthly,
            );

            return [
                normalizedFrequencyMode,
                this.normalizeWirdKhatmatTarget(this.wirdKhatmatTarget, 1, {
                    frequencyMode: normalizedFrequencyMode,
                }),
                this.resolveReaderMaxPage(),
            ].join(':');
        },

        ensureWirdDailyRecord({ forceRebuild = false } = {}) {
            this.syncWirdStorageState({
                clearDailyRecord: true,
            });

            if (!this.wirdState || typeof this.wirdState !== 'object') {
                this.hydrateWirdState();
            }

            const previousNextAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(this.wirdState?.nextAbsolutePage, 1, { min: 1 }),
            );
            const dateKey = currentDateKey();
            const signature = this.resolveWirdRecordSignature();
            const dayRecords =
                this.wirdState?.dayRecords &&
                typeof this.wirdState.dayRecords === 'object' &&
                !Array.isArray(this.wirdState.dayRecords)
                    ? this.wirdState.dayRecords
                    : {};
            let record = dayRecords[dateKey] ?? null;
            const shouldRebuild =
                forceRebuild ||
                !record ||
                typeof record !== 'object' ||
                String(record?.signature ?? '') !== signature;

            this.wirdTodayKey = dateKey;

            if (shouldRebuild) {
                const requiredPages = this.resolveWirdRequiredPages({
                    dateKey,
                });
                const maxStep = Math.max(0, requiredPages - 1);
                const fallbackStartAbsolutePage = Math.max(
                    1,
                    this.normalizeIntegerFlag(this.wirdState?.nextAbsolutePage, 1, { min: 1 }),
                );
                const canCarryExistingProgress =
                    !forceRebuild && record && typeof record === 'object';
                const startAbsolutePage = canCarryExistingProgress
                    ? Math.max(
                          1,
                          this.normalizeIntegerFlag(
                              record?.startAbsolutePage,
                              fallbackStartAbsolutePage,
                              {
                                  min: 1,
                              },
                          ),
                      )
                    : fallbackStartAbsolutePage;
                const currentStep = canCarryExistingProgress
                    ? this.normalizeIntegerFlag(record?.currentStep, 0, {
                          min: 0,
                          max: maxStep,
                      })
                    : 0;
                const carriedProgressStep = canCarryExistingProgress
                    ? this.normalizeIntegerFlag(record?.progressStep, currentStep, {
                          min: 0,
                          max: maxStep,
                      })
                    : currentStep;
                const completed = canCarryExistingProgress
                    ? Boolean(record?.completed) || carriedProgressStep >= maxStep
                    : false;
                const progressStep = completed
                    ? maxStep
                    : Math.max(currentStep, carriedProgressStep);

                record = {
                    startAbsolutePage,
                    requiredPages,
                    currentStep,
                    progressStep,
                    completed,
                    signature,
                    createdAt: canCarryExistingProgress
                        ? this.normalizeIntegerFlag(record?.createdAt, Date.now(), {
                              min: 0,
                          })
                        : Date.now(),
                    updatedAt: Date.now(),
                };

                this.wirdState.dayRecords[dateKey] = record;
            } else {
                const maxStep = Math.max(0, Number(record.requiredPages ?? 1) - 1);

                record.currentStep = this.normalizeIntegerFlag(record.currentStep, 0, {
                    min: 0,
                    max: maxStep,
                });
                record.progressStep = this.normalizeIntegerFlag(
                    record.progressStep,
                    record.currentStep,
                    {
                        min: 0,
                        max: maxStep,
                    },
                );
                record.completed = Boolean(record.completed);

                if (record.completed) {
                    record.progressStep = maxStep;
                } else {
                    record.progressStep = Math.max(record.progressStep, record.currentStep);
                }

                record.signature = signature;
            }

            this.wirdDailyRecord = record;
            this.reconcileWirdNextAbsolutePage(record);
            const didNextAbsolutePageChange =
                Math.max(
                    1,
                    this.normalizeIntegerFlag(this.wirdState?.nextAbsolutePage, 1, { min: 1 }),
                ) !== previousNextAbsolutePage;

            if (shouldRebuild || didNextAbsolutePageChange) {
                this.persistWirdState();
            }

            return this.wirdDailyRecord;
        },

        wirdCompletedPages(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.normalizeIntegerFlag(normalizedRecord?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });
            const progressStep = this.normalizeIntegerFlag(
                normalizedRecord?.progressStep,
                currentStep,
                {
                    min: 0,
                    max: maxStep,
                },
            );

            if (normalizedRecord?.completed) {
                return requiredPages;
            }

            return progressStep;
        },

        wirdRemainingPages(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );

            return Math.max(0, requiredPages - this.wirdCompletedPages(normalizedRecord));
        },

        wirdProgressPercent(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const completedPages = this.wirdCompletedPages(normalizedRecord);

            return Math.max(0, Math.min(100, Math.round((completedPages / requiredPages) * 100)));
        },

        wirdCurrentAbsolutePage(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const startAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.startAbsolutePage, 1, { min: 1 }),
            );
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.normalizeIntegerFlag(normalizedRecord?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });

            return startAbsolutePage + currentStep;
        },

        wirdCurrentStep(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);

            return this.normalizeIntegerFlag(normalizedRecord?.currentStep, 0, {
                min: 0,
                max: maxStep,
            });
        },

        wirdProgressStep(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const maxStep = Math.max(0, requiredPages - 1);
            const currentStep = this.wirdCurrentStep(normalizedRecord);
            const progressStep = this.normalizeIntegerFlag(
                normalizedRecord?.progressStep,
                currentStep,
                {
                    min: 0,
                    max: maxStep,
                },
            );

            if (normalizedRecord?.completed) {
                return maxStep;
            }

            return Math.max(currentStep, progressStep);
        },

        wirdBrowseStepForProgress(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();

            if (normalizedRecord?.completed) {
                return this.wirdBrowseStepValue(normalizedRecord);
            }

            return this.wirdCurrentStep(normalizedRecord);
        },

        wirdBrowsePercent(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );
            const browseStep = this.wirdBrowseStepForProgress(normalizedRecord);

            return Math.max(0, Math.min(100, Math.round((browseStep / requiredPages) * 100)));
        },

        wirdRangeState(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();
            const startAbsolutePage = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.startAbsolutePage, 1, { min: 1 }),
            );
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(normalizedRecord?.requiredPages, 1, { min: 1 }),
            );

            return {
                record: normalizedRecord,
                startAbsolutePage,
                requiredPages,
                maxStep: Math.max(0, requiredPages - 1),
            };
        },

        wirdBrowseStepValue(record = this.wirdDailyRecord) {
            const range = this.wirdRangeState(record);
            const fallbackStep = this.wirdCurrentStep(range.record);

            return this.normalizeIntegerFlag(this.wirdBrowseStep, fallbackStep, {
                min: 0,
                max: range.maxStep,
            });
        },

        wirdActiveStepForNavigation(record = this.wirdDailyRecord) {
            const normalizedRecord =
                record && typeof record === 'object' ? record : this.ensureWirdDailyRecord();

            if (normalizedRecord?.completed) {
                return this.wirdBrowseStepValue(normalizedRecord);
            }

            return this.wirdCurrentStep(normalizedRecord);
        },

        wirdTargetPageFromStep(step, record = this.wirdDailyRecord) {
            const range = this.wirdRangeState(record);
            const normalizedStep = this.normalizeIntegerFlag(
                step,
                this.wirdActiveStepForNavigation(record),
                {
                    min: 0,
                    max: range.maxStep,
                },
            );

            return this.absolutePageToPageNumber(range.startAbsolutePage + normalizedStep);
        },

        wirdStepForPage(pageNumber, record = this.wirdDailyRecord, { preferredStep = null } = {}) {
            const range = this.wirdRangeState(record);
            const targetPage = clampPage(pageNumber, this.maxPage);
            const maxReaderPage = Math.max(1, this.resolveReaderMaxPage());
            const startPage = this.absolutePageToPageNumber(range.startAbsolutePage);
            const initialStep =
                (((targetPage - startPage) % maxReaderPage) + maxReaderPage) % maxReaderPage;

            if (initialStep > range.maxStep) {
                return null;
            }

            if (!Number.isFinite(Number(preferredStep))) {
                return initialStep;
            }

            const normalizedPreferredStep = this.normalizeIntegerFlag(preferredStep, initialStep, {
                min: 0,
                max: range.maxStep,
            });
            let resolvedStep = initialStep;
            let smallestDistance = Math.abs(initialStep - normalizedPreferredStep);

            for (
                let candidateStep = initialStep + maxReaderPage;
                candidateStep <= range.maxStep;
                candidateStep += maxReaderPage
            ) {
                const distance = Math.abs(candidateStep - normalizedPreferredStep);

                if (distance > smallestDistance) {
                    continue;
                }

                if (distance === smallestDistance && candidateStep < resolvedStep) {
                    continue;
                }

                resolvedStep = candidateStep;
                smallestDistance = distance;
            }

            return resolvedStep;
        },

        clearWirdSliderVisualTween() {
            if (this._wirdSliderVisualTweenRaf === null) {
                return;
            }

            cancelAnimationFrame(this._wirdSliderVisualTweenRaf);
            this._wirdSliderVisualTweenRaf = null;
        },

        syncWirdSliderVisualStep(record = this.wirdDailyRecord) {
            if (!this.wirdModeActive) {
                this.clearWirdSliderVisualTween();
                this.wirdSliderVisualStep = null;

                return;
            }

            const range = this.wirdRangeState(record);
            const activeStep = this.wirdActiveStepForNavigation(range.record);
            this.clearWirdSliderVisualTween();
            this.wirdSliderVisualStep = activeStep;
        },

        wirdSliderDisplayStep(record = this.wirdDailyRecord) {
            if (!this.wirdModeActive) {
                return null;
            }

            const range = this.wirdRangeState(record);
            const activeStep = this.wirdActiveStepForNavigation(range.record);
            const visualStep = Number(this.wirdSliderVisualStep);

            if (!Number.isFinite(visualStep)) {
                return activeStep;
            }

            return Math.max(0, Math.min(range.maxStep, visualStep));
        },

        animateWirdSliderVisualStepTo(
            targetStep,
            { durationMs = 220, record = this.wirdDailyRecord } = {},
        ) {
            if (!this.wirdModeActive) {
                this.syncWirdSliderVisualStep(record);

                return;
            }

            const range = this.wirdRangeState(record);
            const normalizedTargetStep = Math.max(
                0,
                Math.min(range.maxStep, Number(targetStep ?? 0)),
            );
            const startingStep = Number(this.wirdSliderDisplayStep(range.record));

            if (!Number.isFinite(startingStep)) {
                this.wirdSliderVisualStep = normalizedTargetStep;

                return;
            }

            const normalizedDurationMs = Math.max(120, Math.trunc(Number(durationMs) || 220));
            const delta = normalizedTargetStep - startingStep;

            if (Math.abs(delta) < 0.001) {
                this.wirdSliderVisualStep = normalizedTargetStep;

                return;
            }

            this.clearWirdSliderVisualTween();
            this.wirdSliderVisualStep = startingStep;

            const startedAt = performance.now();

            const tick = (timestamp) => {
                if (!this.wirdModeActive) {
                    this._wirdSliderVisualTweenRaf = null;

                    return;
                }

                const elapsed = Math.max(0, timestamp - startedAt);
                const progress = Math.max(0, Math.min(1, elapsed / normalizedDurationMs));
                const easedProgress =
                    progress < 0.5
                        ? 2 * progress * progress
                        : 1 - Math.pow(-2 * progress + 2, 2) / 2;

                this.wirdSliderVisualStep = startingStep + delta * easedProgress;

                if (progress >= 1) {
                    this._wirdSliderVisualTweenRaf = null;
                    this.wirdSliderVisualStep = normalizedTargetStep;

                    return;
                }

                this._wirdSliderVisualTweenRaf = requestAnimationFrame(tick);
            };

            this._wirdSliderVisualTweenRaf = requestAnimationFrame(tick);
        },

        applyWirdNavigationVisualState(
            targetPage,
            targetStep,
            { source = 'generic', sliderDurationMs = 220, previousStep = null } = {},
        ) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);
            const range = this.wirdRangeState();
            const normalizedTargetStep = this.normalizeIntegerFlag(targetStep, 0, {
                min: 0,
                max: range.maxStep,
            });
            const normalizedPreviousStep = this.normalizeIntegerFlag(
                previousStep,
                normalizedTargetStep,
                {
                    min: 0,
                    max: range.maxStep,
                },
            );
            const previousCounterValue = normalizedPreviousStep + 1;
            const nextCounterValue = normalizedTargetStep + 1;

            if (nextCounterValue !== previousCounterValue) {
                this.triggerPageCounterPulse(previousCounterValue, nextCounterValue, {
                    source: `wird-${String(source ?? 'generic').trim() || 'generic'}-counter`,
                });
            }

            this.pageInput = normalizedTargetPage;
            this._lastPageInputVisualValue = normalizedTargetPage;
            this.animateWirdSliderVisualStepTo(normalizedTargetStep, {
                durationMs: sliderDurationMs,
            });
        },

        sliderMin() {
            return this.wirdModeActive ? 0 : 1;
        },

        sliderMax() {
            if (!this.wirdModeActive) {
                return Math.max(1, this.maxPage);
            }

            const range = this.wirdRangeState();

            return Math.max(0, range.maxStep);
        },

        sliderValue() {
            if (!this.wirdModeActive) {
                return clampPage(this.pageInput, this.maxPage);
            }

            return this.wirdSliderDisplayStep();
        },

        formatReaderNumber(value, fallback = '0') {
            const normalizedNumber = Number(value);

            if (!Number.isFinite(normalizedNumber)) {
                return fallback;
            }

            const westernText = String(Math.max(0, Math.trunc(normalizedNumber)));

            if (this.doesUseWesternNumerals) {
                return westernText;
            }

            return westernText.replace(/\d/g, (digit) => {
                const digitIndex = Number(digit);

                if (!Number.isInteger(digitIndex)) {
                    return digit;
                }

                return this.arabicNumeralCharacters[digitIndex] ?? digit;
            });
        },

        wirdProgressPercentLabel() {
            const record = this.ensureWirdDailyRecord();
            const percent = this.wirdProgressPercent(record);

            if (record?.completed) {
                return 'مكتمل';
            }

            return `${this.formatReaderNumber(percent)}%`;
        },

        wirdProgressCounterLabel() {
            const record = this.ensureWirdDailyRecord();
            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
            );
            const activeIndex = this.wirdProgressStep(record) + 1;
            const current = record?.completed
                ? requiredPages
                : Math.min(requiredPages, Math.max(1, activeIndex));

            return `${this.formatReaderNumber(requiredPages)} / ${this.formatReaderNumber(current)}`;
        },

        wirdProgressBarStyle() {
            return `--quran-wird-progress-percent: ${this.wirdProgressPercent()}%; --quran-wird-progress-browse-percent: ${this.wirdBrowsePercent()}%;`;
        },

        clearWirdEntryRevealTimers() {
            this._wirdEntryRevealTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._wirdEntryRevealTimers = [];

            this._historyManagerSyncTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._historyManagerSyncTimers = [];

            this._bookmarksManagerSyncTimers.forEach((timerId) => {
                clearTimeout(timerId);
            });
            this._bookmarksManagerSyncTimers = [];
        },

        clearWirdEntryRecovery({ resetSuppression = true } = {}) {
            this.clearWirdEntryRevealTimers();

            if (resetSuppression) {
                this._wirdEntryLayoutSuppressedUntil = 0;
            }
        },

        suppressWirdEntryLayoutScheduling(durationMs = 900) {
            const normalizedDurationMs = Math.max(120, Math.trunc(Number(durationMs) || 900));

            this._wirdEntryLayoutSuppressedUntil = Math.max(
                this._wirdEntryLayoutSuppressedUntil,
                Date.now() + normalizedDurationMs,
            );
        },

        isWirdEntryLayoutSchedulingSuppressed() {
            return Date.now() < this._wirdEntryLayoutSuppressedUntil;
        },

        queueWirdEntryRevealRecovery(
            targetPage,
            navigationRequestSerial = this._wirdNavigationRequestSerial,
        ) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);

            if (normalizedTargetPage < 1) {
                return;
            }

            this.clearWirdEntryRevealTimers();

            const timerId = window.setTimeout(() => {
                this._wirdEntryRevealTimers = this._wirdEntryRevealTimers.filter(
                    (activeTimerId) => activeTimerId !== timerId,
                );

                if (
                    navigationRequestSerial !== this._wirdNavigationRequestSerial ||
                    !this.wirdModeActive ||
                    this.pageNumber !== normalizedTargetPage ||
                    !this.hasRenderablePage() ||
                    this.isLoadingPage
                ) {
                    return;
                }

                void (async () => {
                    if (navigationRequestSerial !== this._wirdNavigationRequestSerial) {
                        return;
                    }

                    const shouldRunRecoveryLayout =
                        this.isFittingPage ||
                        (this._lastFittedPageNumber !== normalizedTargetPage &&
                            this._revealTimer === null);

                    if (shouldRunRecoveryLayout) {
                        this.pauseIdleWarmup(640, {
                            preservePage: normalizedTargetPage,
                        });
                        this._bypassNextFitCache = true;
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 130,
                            maxAttempts: 3,
                            useIdleFit: false,
                        });

                        if (navigationRequestSerial !== this._wirdNavigationRequestSerial) {
                            return;
                        }
                    }

                    await this.ensureWirdEntryPageVisible(normalizedTargetPage);
                })();
            }, 560);

            this._wirdEntryRevealTimers.push(timerId);
        },

        async ensureWirdEntryPageVisible(targetPage, { forceRecover = false } = {}) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);

            if (normalizedTargetPage < 1) {
                return;
            }

            await this.nextTickAsync();

            if (this.pageNumber !== normalizedTargetPage) {
                return;
            }

            if (!this.hasRenderablePage()) {
                if (this.isLoadingPage || this._pendingNavigationRequest !== null) {
                    return;
                }

                await this.goToPage(normalizedTargetPage, {
                    direction: this.resolveNavigationDirection(normalizedTargetPage),
                    animate: false,
                    forceRefit: true,
                    source: 'wird-recover',
                });
            }

            if (!this.hasRenderablePage()) {
                return;
            }

            const clearedStaleGuards = this.clearStaleRevealGuards();
            const shouldRunRecoveryLayout =
                forceRecover ||
                clearedStaleGuards ||
                this._lastFittedPageNumber !== normalizedTargetPage ||
                !this.isCurrentPageVisiblyReady() ||
                (this.isFittingPage && this._revealTimer === null);

            if (shouldRunRecoveryLayout) {
                this.pauseIdleWarmup(560, {
                    preservePage: normalizedTargetPage,
                });
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 130,
                    maxAttempts: 5,
                    useIdleFit: false,
                });
            }

            this.clearStaleRevealGuards();

            if (
                this.pageNumber === normalizedTargetPage &&
                !this.isLoadingPage &&
                this._pendingNavigationRequest === null
            ) {
                this.isFittingPage = false;
            }
        },

        startWirdHoverEffects() {
            if (this.isSupportLockActive()) {
                this.wirdHoverShimmerRunning = false;

                return;
            }

            if (this._wirdHoverShimmerTimer !== null) {
                clearTimeout(this._wirdHoverShimmerTimer);
                this._wirdHoverShimmerTimer = null;
            }

            this.wirdHoverShimmerRunning = false;

            requestAnimationFrame(() => {
                this.wirdHoverShimmerRunning = true;
            });

            this._wirdHoverShimmerTimer = window.setTimeout(() => {
                this._wirdHoverShimmerTimer = null;
                this.wirdHoverShimmerRunning = false;
            }, wirdHoverShimmerDurationMs);
        },

        endWirdHoverEffects({ immediate = false } = {}) {
            if (!immediate) {
                return;
            }

            if (this._wirdHoverShimmerTimer !== null) {
                clearTimeout(this._wirdHoverShimmerTimer);
                this._wirdHoverShimmerTimer = null;
            }

            this.wirdHoverShimmerRunning = false;
        },

        async toggleWirdMode() {
            this.$el.blur();

            if (this.isSupportLockActive()) {
                this.openSupportUnlockModal();

                return;
            }

            if (this.wirdModeActive) {
                await this.exitWirdMode({
                    restoreNormalPage: true,
                    reason: 'manual-toggle',
                });

                return;
            }

            await this.enterWirdMode();
        },

        async enterWirdMode() {
            if (this.isLoadingPage || !this.ready) {
                return;
            }

            this.closeSurahQuickNavigator();
            const record = this.ensureWirdDailyRecord();

            if (!record || typeof record !== 'object') {
                return;
            }

            this.resetNavigationQueueForPriorityJump();
            this._wirdNavigationRequestSerial += 1;
            this.wirdNormalPageBeforeMode = clampPage(this.pageNumber, this.maxPage);
            this.wirdModeActive = true;
            this.suppressWirdEntryLayoutScheduling();
            this.clearWirdEntryRevealTimers();

            const wirdRange = this.wirdRangeState(record);
            this.wirdBrowseStep = record?.completed ? wirdRange.maxStep : null;
            this.syncWirdSliderVisualStep(record);

            const targetAbsolutePage = record?.completed
                ? wirdRange.startAbsolutePage + this.wirdBrowseStep
                : this.wirdCurrentAbsolutePage(record);
            const targetPage = this.absolutePageToPageNumber(targetAbsolutePage);
            const direction = this.resolveNavigationDirection(targetPage);

            await this.animatePageInputTo(targetPage, {
                source: 'wird-enter',
            });

            if (targetPage === this.pageNumber && this.hasRenderablePage()) {
                this.pageInput = targetPage;
                this._lastPageInputVisualValue = targetPage;
                await this.ensureWirdEntryPageVisible(targetPage);
                this.queueWirdEntryRevealRecovery(targetPage);

                return;
            }

            await this.goToPage(targetPage, {
                direction,
                animate: true,
                forceRefit: true,
                source: 'wird-enter',
            });

            await this.ensureWirdEntryPageVisible(targetPage);
            this.queueWirdEntryRevealRecovery(targetPage);
        },

        async exitWirdMode({ restoreNormalPage = true, reason = 'manual' } = {}) {
            if (!this.wirdModeActive) {
                return;
            }

            this.resetNavigationQueueForPriorityJump();
            this._wirdNavigationRequestSerial += 1;
            this.abortActivePageLoad();

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }
            this._wirdSliderPendingCommitStep = null;
            this._wirdSliderLastInputStep = null;
            this._wirdSliderLastInputAt = 0;
            this._wirdLastCommittedTargetPage = 0;
            this._wirdLastCommittedStep = null;
            this._wirdLastCommittedAt = 0;

            this.clearWirdSliderVisualTween();

            this.wirdModeActive = false;
            this.wirdBrowseStep = null;
            this.clearWirdEntryRevealTimers();
            this._wirdEntryLayoutSuppressedUntil = 0;
            this.syncWirdSliderVisualStep();
            this.endWirdHoverEffects({ immediate: true });

            if (!restoreNormalPage) {
                return;
            }

            const fallbackPage = readLastPageNumber() ?? this.pageNumber;
            const targetPage = clampPage(
                this.wirdNormalPageBeforeMode || fallbackPage,
                this.maxPage,
            );

            if (targetPage === this.pageNumber && this.hasRenderablePage()) {
                this.pageInput = targetPage;
                this._lastPageInputVisualValue = targetPage;
                this.persistLastPageNumber(targetPage, { force: true });
                await this.ensureWirdEntryPageVisible(targetPage, { forceRecover: true });

                return;
            }
            const direction = this.resolveNavigationDirection(targetPage);

            await this.animatePageInputTo(targetPage, {
                source: 'wird-exit',
            });

            await this.goToPage(targetPage, {
                direction,
                animate: reason !== 'auto-complete',
                forceRefit: true,
                source: 'wird-exit',
            });

            await this.ensureWirdEntryPageVisible(targetPage, { forceRecover: true });
        },

        markWirdAsCompleted(record = this.wirdDailyRecord) {
            if (!record || typeof record !== 'object') {
                return;
            }

            const wasCompleted = Boolean(record.completed);

            const requiredPages = Math.max(
                1,
                this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
            );

            record.currentStep = Math.max(0, requiredPages - 1);
            record.progressStep = Math.max(0, requiredPages - 1);
            record.completed = true;
            record.updatedAt = Date.now();
            this.wirdDailyRecord = record;
            this.wirdBrowseStep = Math.max(0, requiredPages - 1);
            this.syncWirdSliderVisualStep(record);
            this.reconcileWirdNextAbsolutePage(record);
            this.persistWirdState();

            if (!wasCompleted) {
                this.showWirdCompletionFeedback();
            }
        },

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
            this.syncHistoryManagerTableRecords();
        },

        persistBookmarks() {
            this.bookmarks = writeBookmarks(this.bookmarks);
            this.syncBookmarksManagerTableRecords();
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

        queueHistoryManagerTableSync() {
            this.clearHistoryManagerSyncQueue();

            [0, 72, 180, 360].forEach((delayMs) => {
                const timerId = window.setTimeout(() => {
                    this._historyManagerSyncTimers = this._historyManagerSyncTimers.filter(
                        (activeTimerId) => activeTimerId !== timerId,
                    );
                    this.syncHistoryManagerTableRecords();
                }, delayMs);

                this._historyManagerSyncTimers.push(timerId);
            });
        },

        queueBookmarksManagerTableSync() {
            this.clearBookmarksManagerSyncQueue();

            [0, 72, 180, 360].forEach((delayMs) => {
                const timerId = window.setTimeout(() => {
                    this._bookmarksManagerSyncTimers = this._bookmarksManagerSyncTimers.filter(
                        (activeTimerId) => activeTimerId !== timerId,
                    );
                    this.syncBookmarksManagerTableRecords();
                }, delayMs);

                this._bookmarksManagerSyncTimers.push(timerId);
            });
        },

        syncHistoryManagerTableRecords() {
            const payload = {
                records: this.navigationHistory,
                surahNames: this.search?.surahNames ?? {},
            };

            this.emitLivewireManagerEvent('quran-history-manager-sync', payload);
        },

        syncBookmarksManagerTableRecords() {
            const payload = {
                records: this.bookmarks,
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
            window.dispatchEvent(
                new CustomEvent('quran-manager-modals-visibility', {
                    detail: {
                        open:
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

        onBookmarkButtonPointerUp(event) {
            const pointerId = Number(event?.pointerId ?? 0) || null;

            if (
                this.bookmarkButtonPress.pointerId !== null &&
                pointerId !== null &&
                this.bookmarkButtonPress.pointerId !== pointerId
            ) {
                return;
            }

            if (this.bookmarkButtonPress.timer !== null) {
                clearTimeout(this.bookmarkButtonPress.timer);
                this.bookmarkButtonPress.timer = null;
            }
        },

        onBookmarkButtonPointerCancel() {
            this.clearBookmarkButtonPressState();
        },

        onBookmarkButtonClick() {
            if (this.bookmarkButtonPress.suppressClick) {
                this.clearBookmarkButtonPressState();

                return;
            }

            this.toggleCurrentPageBookmark();
            this.clearBookmarkButtonPressState();
        },

        clearSurahQuickNavigatorPressState({ resetSuppressClick = true } = {}) {
            if (this.surahQuickNavigator.timer !== null) {
                clearTimeout(this.surahQuickNavigator.timer);
                this.surahQuickNavigator.timer = null;
            }

            this.surahQuickNavigator.pointerId = null;
            this.surahQuickNavigator.holdTriggered = false;

            if (resetSuppressClick) {
                this.surahQuickNavigator.suppressClick = false;
            }
        },

        openSurahQuickNavigator() {
            if (this.wirdModeActive) {
                return;
            }

            this.surahQuickNavigator.visible = true;
            this.surahQuickNavigator.holdTriggered = true;
            this.surahQuickNavigator.suppressClick = true;
        },

        closeSurahQuickNavigator({ resetSuppressClick = true } = {}) {
            this.surahQuickNavigator.visible = false;
            this.clearSurahQuickNavigatorPressState({ resetSuppressClick });
        },

        onSurahTriggerPointerDown(event) {
            if (this.wirdModeActive) {
                return;
            }

            this.clearSurahQuickNavigatorPressState();
            this.surahQuickNavigator.pointerId = Number(event?.pointerId ?? 0) || null;
            this.surahQuickNavigator.holdTriggered = false;
            this.surahQuickNavigator.suppressClick = false;
            this.surahQuickNavigator.timer = window.setTimeout(() => {
                this.surahQuickNavigator.timer = null;
                this.openSurahQuickNavigator();
            }, surahQuickNavigatorHoldDelayMs);
        },

        onSurahTriggerPointerUp(event) {
            const pointerId = Number(event?.pointerId ?? 0) || null;

            if (
                this.surahQuickNavigator.pointerId !== null &&
                pointerId !== null &&
                this.surahQuickNavigator.pointerId !== pointerId
            ) {
                return;
            }

            if (this.surahQuickNavigator.timer !== null) {
                clearTimeout(this.surahQuickNavigator.timer);
                this.surahQuickNavigator.timer = null;
            }

            if (this.surahQuickNavigator.holdTriggered) {
                this.surahQuickNavigator.suppressClick = true;
            }
        },

        onSurahTriggerPointerCancel() {
            const shouldKeepSuppression =
                this.surahQuickNavigator.holdTriggered || this.surahQuickNavigator.suppressClick;

            this.clearSurahQuickNavigatorPressState({
                resetSuppressClick: !shouldKeepSuppression,
            });
        },

        onSurahTriggerClick() {
            const shouldSuppressClick = this.surahQuickNavigator.suppressClick;

            if (shouldSuppressClick || this.wirdModeActive) {
                this.clearSurahQuickNavigatorPressState();

                return;
            }

            this.closeSurahQuickNavigator({ resetSuppressClick: false });
            this.warmSearchIndex();
            this.$wire.mountAction('searchQuran');
            this.queueSurahDirectoryAutoFocus();
            this.clearSurahQuickNavigatorPressState();
        },

        pageSurahHeaderNumbers() {
            const uniqueSurahNumbers = new Set();

            this.mushafLines.forEach((line) => {
                if (String(line?.line_type ?? '') !== 'surah_name') {
                    return;
                }

                const lineSurahNumber = Math.max(0, Math.trunc(Number(line?.surah_number ?? 0)));

                if (lineSurahNumber > 0) {
                    uniqueSurahNumbers.add(lineSurahNumber);
                }
            });

            return [...uniqueSurahNumbers].sort((firstNumber, secondNumber) => {
                return firstNumber - secondNumber;
            });
        },

        surahQuickNavigatorBaseSurahNumber(direction = 'next') {
            const surahHeaderNumbers = this.pageSurahHeaderNumbers();

            if (surahHeaderNumbers.length === 0) {
                return Math.max(1, Math.trunc(Number(this.currentSurahNumber() ?? 1)));
            }

            if (direction === 'prev') {
                return surahHeaderNumbers[0];
            }

            return surahHeaderNumbers[surahHeaderNumbers.length - 1];
        },

        surahQuickNavigatorTargetSurahNumber(direction = 'next') {
            const baseSurahNumber = this.surahQuickNavigatorBaseSurahNumber(direction);

            if (direction === 'prev') {
                if (baseSurahNumber <= 1) {
                    return null;
                }

                return baseSurahNumber - 1;
            }

            if (baseSurahNumber >= 114) {
                return null;
            }

            return baseSurahNumber + 1;
        },

        surahDirectoryEntryBySurahNumber(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            const surahDirectoryEntries = Array.isArray(this.search.surahDirectory)
                ? this.search.surahDirectory
                : [];

            return (
                surahDirectoryEntries.find((entry) => {
                    return (
                        Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1))) ===
                        normalizedSurahNumber
                    );
                }) ?? null
            );
        },

        firstAyahIndexForSurahInCurrentPage(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            let fallbackAyahIndex = 0;

            for (const line of this.mushafLines) {
                if (String(line?.line_type ?? '') !== 'ayah') {
                    continue;
                }

                const lineSurahNumber = Math.max(0, Math.trunc(Number(line?.surah_number ?? 0)));

                if (lineSurahNumber !== normalizedSurahNumber) {
                    continue;
                }

                const lineAyahIndex = Math.max(0, Math.trunc(Number(line?.ayah_index ?? 0)));

                if (lineAyahIndex > 0) {
                    return lineAyahIndex;
                }

                if (!Array.isArray(line?.words)) {
                    continue;
                }

                for (const word of line.words) {
                    const wordAyahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));
                    const wordSurahNumber = Math.max(
                        0,
                        Math.trunc(Number(word?.surah_number ?? 0)),
                    );

                    if (
                        wordSurahNumber === normalizedSurahNumber &&
                        wordAyahIndex > 0 &&
                        (fallbackAyahIndex === 0 || wordAyahIndex < fallbackAyahIndex)
                    ) {
                        fallbackAyahIndex = wordAyahIndex;
                    }
                }
            }

            return fallbackAyahIndex;
        },

        isSurahQuickNavigatorPreviousDisabled() {
            const currentPage = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const targetSurahNumber = this.surahQuickNavigatorTargetSurahNumber('prev');

            return currentPage <= 1 || targetSurahNumber === null;
        },

        isSurahQuickNavigatorNextDisabled() {
            const currentPage = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const targetSurahNumber = this.surahQuickNavigatorTargetSurahNumber('next');

            return currentPage >= surahQuickNavigatorLastPage || targetSurahNumber === null;
        },

        async navigateToAdjacentSurah(direction = 'next') {
            const normalizedDirection = direction === 'prev' ? 'prev' : 'next';
            const isDisabled =
                normalizedDirection === 'prev'
                    ? this.isSurahQuickNavigatorPreviousDisabled()
                    : this.isSurahQuickNavigatorNextDisabled();

            if (isDisabled) {
                return;
            }

            const targetSurahNumber =
                this.surahQuickNavigatorTargetSurahNumber(normalizedDirection);

            if (targetSurahNumber === null) {
                return;
            }

            const targetSurahEntry = this.surahDirectoryEntryBySurahNumber(targetSurahNumber);
            const targetPage = clampPage(Number(targetSurahEntry?.page_number ?? 1), this.maxPage);
            const currentPage = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const activeAyahIndex =
                targetPage === currentPage
                    ? this.firstAyahIndexForSurahInCurrentPage(targetSurahNumber)
                    : 0;

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.activeWordIndex = 0;
            this._bypassNextFitCache = true;

            await this.goToPageFromChevron(targetPage, {
                source: 'surah-directory',
                activeAyahIndex,
                commitNow: true,
                settleDelayMs: 0,
            });

            if (this._lastFittedPageNumber !== this.pageNumber) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 160,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            }

            this.search.activeSurahNumber = targetSurahNumber;
            this.activeAyahIndex = activeAyahIndex;
            this.activeWordIndex = 0;
            this.recordNavigationHistory({
                source: 'surah-directory',
                pageNumber: targetPage,
                surahNumber: targetSurahNumber,
                ayahIndex: activeAyahIndex,
            });
        },

        openBookmarksManager() {
            this.$wire.mountAction('bookmarksManager');
        },

        readerRootStyle() {
            return '';
        },

        pageDataUrl(pageNumber) {
            return this.api.pageDataTemplate.replace('__PAGE__', String(pageNumber));
        },

        abortActivePageLoad() {
            if (
                typeof AbortController === 'undefined' ||
                !(this._activePageAbortController instanceof AbortController)
            ) {
                return;
            }

            this._activePageAbortController.abort();
            this._activePageAbortController = null;
        },

        beginActivePageLoadAbortController() {
            if (typeof AbortController === 'undefined') {
                this._activePageAbortController = null;

                return null;
            }

            this.abortActivePageLoad();
            this._activePageAbortController = new AbortController();

            return this._activePageAbortController;
        },

        async getPagePayload(
            pageNumber,
            { preferCache = true, forceNetwork = false, signal = null } = {},
        ) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);

            if (this._pagePayloadByPage.has(normalizedPage) && !forceNetwork) {
                return this._pagePayloadByPage.get(normalizedPage);
            }

            const pendingLoad = this._pendingPageLoads.get(normalizedPage);

            if (pendingLoad) {
                return await pendingLoad;
            }

            const url = this.pageDataUrl(normalizedPage);
            const loadPromise = (async () => {
                try {
                    const payload = normalizePayload(
                        await fetchJsonWithCache({
                            url,
                            cacheName: this.cacheNames.pages,
                            preferCache,
                            forceNetwork,
                            signal,
                        }),
                    );

                    this._pagePayloadByPage.set(normalizedPage, payload);
                    await this.prefetchFontAsset(payload);

                    return payload;
                } catch (error) {
                    if (error?.name === 'AbortError') {
                        return null;
                    }

                    throw error;
                }
            })();

            this._pendingPageLoads.set(normalizedPage, loadPromise);

            try {
                return await loadPromise;
            } finally {
                this._pendingPageLoads.delete(normalizedPage);
            }
        },

        async ensureCurrentPageLoaded() {
            const normalizedPage = clampPage(this.pageNumber, this.maxPage);
            const targetPage = clampPage(Number(this.pageInput ?? this.pageNumber), this.maxPage);
            const loadedPayloadPageNumber = Math.max(
                0,
                Math.trunc(Number(this._loadedPayloadPageNumber ?? 0)),
            );

            if (
                normalizedPage === targetPage &&
                this.hasRenderablePage() &&
                loadedPayloadPageNumber === targetPage
            ) {
                return;
            }

            await this.goToPage(targetPage, {
                animate: false,
                forceRefit: true,
                source: 'startup-ensure-current-page',
            });
        },

        buildDigitMorphSegments(previousValue, nextValue) {
            const previous = String(this.pageCounterNormalizeDisplayValue(previousValue, 1));
            const next = String(this.pageCounterNormalizeDisplayValue(nextValue, previousValue));
            const length = this.pageCounterDigitLength();
            const previousChars = previous.padStart(length, ' ').split('');
            const nextChars = next.padStart(length, ' ').split('');

            const segments = nextChars.map((nextChar, index) => {
                const previousChar = previousChars[index] ?? '';
                const prev = previousChar === ' ' ? '' : previousChar;
                const nextValueChar = nextChar === ' ' ? '' : nextChar;

                return {
                    key: `${index}:${prev}->${nextValueChar}`,
                    prev,
                    next: nextValueChar,
                    changed: prev !== nextValueChar,
                };
            });

            return {
                segments,
                hasChanges: segments.some((segment) => segment.changed),
            };
        },

        pageCounterDigitLength() {
            return Math.max(3, String(this.pageCounterMaxDisplayValue()).length);
        },

        pageCounterNormalizeDisplayValue(value, fallback = 1) {
            const maxDisplayValue = Math.max(
                1,
                Math.trunc(Number(this.pageCounterMaxDisplayValue()) || 1),
            );
            const normalizedFallback = Number.isFinite(Number(fallback))
                ? Math.trunc(Number(fallback))
                : 1;
            const normalizedValue = Number.isFinite(Number(value))
                ? Math.trunc(Number(value))
                : normalizedFallback;

            return Math.max(1, Math.min(maxDisplayValue, normalizedValue));
        },

        pageCounterDisplayDigits(value) {
            return String(this.pageCounterNormalizeDisplayValue(value))
                .padStart(this.pageCounterDigitLength(), ' ')
                .split('')
                .map((digit) => (digit === ' ' ? '' : digit));
        },

        pageCounterMaxDisplayValue() {
            if (!this.wirdModeActive) {
                return Math.max(1, this.maxPage);
            }

            const range = this.wirdRangeState();

            return Math.max(1, range.requiredPages);
        },

        pageCounterCurrentDisplayValue() {
            if (!this.wirdModeActive) {
                return clampPage(this.pageInput, this.maxPage);
            }

            const range = this.wirdRangeState();
            const currentStep = this.normalizeIntegerFlag(
                this.wirdSliderDisplayStep(range.record),
                this.wirdActiveStepForNavigation(range.record),
                {
                    min: 0,
                    max: range.maxStep,
                },
            );

            return currentStep + 1;
        },

        currentMushafPageDisplayValue() {
            return clampPage(this.pageInput ?? this.pageNumber, this.maxPage);
        },

        shouldShowMushafPageIndicator() {
            if (!this.wirdModeActive) {
                return false;
            }

            return this.pageCounterMaxDisplayValue() > Math.max(1, this.resolveReaderMaxPage());
        },

        clampPage(value, maxPage = this.maxPage) {
            return clampPage(value, maxPage);
        },

        triggerPageCounterPulse(previousValue, nextValue, { source = 'generic' } = {}) {
            if (this.pageCounterPulse.timer !== null) {
                clearTimeout(this.pageCounterPulse.timer);
                this.pageCounterPulse.timer = null;
            }

            const morph = this.buildDigitMorphSegments(previousValue, nextValue);

            this.pageCounterPulse.segments = morph.segments;
            this.pageCounterPulse.hasChanges = morph.hasChanges;

            if (!morph.hasChanges || this.shouldSuspendPageCounterMorph({ source })) {
                this.pageCounterPulse.isActive = false;

                return;
            }

            if (!this.pageCounterPulse.isActive) {
                requestAnimationFrame(() => {
                    this.pageCounterPulse.isActive = true;
                });
            }

            this.pageCounterPulse.timer = window.setTimeout(() => {
                this.pageCounterPulse.isActive = false;
                this.pageCounterPulse.timer = null;
            }, pageCounterPulseDurationMs);
        },

        async animatePageInputTo(
            targetPage,
            { source = 'generic', durationMs = wirdModeEntryPageInputTweenDurationMs } = {},
        ) {
            const startPage = clampPage(this.pageInput, this.maxPage);
            const nextPage = clampPage(targetPage, this.maxPage);

            if (nextPage === startPage) {
                this.pageInput = nextPage;
                this._lastPageInputVisualValue = nextPage;

                return;
            }

            if (this._pageInputTweenRaf !== null) {
                cancelAnimationFrame(this._pageInputTweenRaf);
                this._pageInputTweenRaf = null;
            }

            this.triggerPageCounterPulse(startPage, nextPage, { source });

            const resolvedDurationMs = Math.max(
                120,
                Math.trunc(Number(durationMs) || wirdModeEntryPageInputTweenDurationMs),
            );

            await new Promise((resolve) => {
                const startTimestamp = performance.now();
                const distance = nextPage - startPage;

                const step = (timestamp) => {
                    const elapsed = timestamp - startTimestamp;
                    const progress = Math.max(0, Math.min(1, elapsed / resolvedDurationMs));
                    const easedProgress =
                        progress < 0.5
                            ? 2 * progress * progress
                            : 1 - Math.pow(-2 * progress + 2, 2) / 2;
                    const interpolatedPage = clampPage(
                        Math.round(startPage + distance * easedProgress),
                        this.maxPage,
                    );

                    if (interpolatedPage !== this.pageInput) {
                        this.pageInput = interpolatedPage;
                        this._lastPageInputVisualValue = interpolatedPage;
                    }

                    if (progress >= 1) {
                        this._pageInputTweenRaf = null;
                        this.pageInput = nextPage;
                        this._lastPageInputVisualValue = nextPage;
                        resolve();

                        return;
                    }

                    this._pageInputTweenRaf = requestAnimationFrame(step);
                };

                this._pageInputTweenRaf = requestAnimationFrame(step);
            });
        },

        shouldSuspendPageCounterMorph({ source = 'generic' } = {}) {
            const normalizedSource = String(source ?? 'generic').trim();

            if (
                normalizedSource === 'keyboard' ||
                normalizedSource === 'swipe' ||
                normalizedSource === 'page-input' ||
                normalizedSource === 'page-slider' ||
                normalizedSource === 'page-slider-commit'
            ) {
                return false;
            }

            return this.isLoadingPage;
        },

        navigationBasePage() {
            const pendingTargetPage = Number(this._pendingNavigationRequest?.targetPage ?? 0);

            if (pendingTargetPage > 0) {
                return pendingTargetPage;
            }

            if (!this.isLoadingPage && this._pendingNavigationRequest === null) {
                return clampPage(this.pageNumber, this.maxPage);
            }

            const visualPage = clampPage(
                Number(this.pageInput ?? this._lastPageInputVisualValue ?? this.pageNumber),
                this.maxPage,
            );

            if (visualPage > 0) {
                return visualPage;
            }

            return this.pageNumber;
        },

        syncPageInputToCurrentPage() {
            if (!this.wirdModeActive && this._pageSliderInteractionActive) {
                return;
            }

            const normalizedCurrentPage = clampPage(this.pageNumber, this.maxPage);

            if (
                this.pageInput === normalizedCurrentPage &&
                this._lastPageInputVisualValue === normalizedCurrentPage
            ) {
                return;
            }

            this.pageInput = normalizedCurrentPage;
            this._lastPageInputVisualValue = normalizedCurrentPage;
        },

        resolveNavigationDirection(targetPage, direction = null) {
            if (direction === 'prev' || direction === 'next') {
                return direction;
            }

            const basePage = this.navigationBasePage();

            return targetPage >= basePage ? 'next' : 'prev';
        },

        isHighFrequencyNavigationSource(source = 'generic') {
            const normalizedSource = String(source ?? '').trim();

            return (
                normalizedSource === 'keyboard' ||
                normalizedSource === 'swipe' ||
                normalizedSource.endsWith('-keyboard') ||
                normalizedSource.endsWith('-swipe')
            );
        },

        isImmediateNavigationSource(source = 'generic') {
            const normalizedSource = String(source ?? '').trim();

            return (
                normalizedSource === 'chevron' ||
                this.isHighFrequencyNavigationSource(normalizedSource)
            );
        },

        isFastFitPrioritySource(source = 'generic') {
            const normalizedSource = String(source ?? '').trim();

            return (
                normalizedSource === 'surah-directory' ||
                normalizedSource === 'search-result' ||
                normalizedSource === 'page-jump' ||
                normalizedSource === 'page-slider-commit' ||
                normalizedSource.startsWith('wird-')
            );
        },

        resolveIdleWarmupPauseDuration(source = 'generic') {
            return this.isHighFrequencyNavigationSource(source)
                ? idleWarmupPauseOnHighFrequencyNavigationMs
                : idleWarmupPauseOnStandardNavigationMs;
        },

        clearNavigationBurstState() {
            this._navigationBurstLastInputAt = 0;
            this._navigationBurstCount = 0;
            this._navigationBurstFreezeUntil = 0;
        },

        isNavigationBurstActive() {
            return this._navigationBurstFreezeUntil > Date.now();
        },

        registerNavigationBurst(source = 'generic') {
            if (!this.isHighFrequencyNavigationSource(source)) {
                return;
            }

            if (this.isImmediateNavigationSource(source)) {
                this.clearNavigationBurstState();

                return;
            }

            const now = Date.now();
            const elapsedSinceLastInput = now - this._navigationBurstLastInputAt;
            const isBurstContinuation = elapsedSinceLastInput <= navigationBurstInputThresholdMs;

            this._navigationBurstCount = isBurstContinuation ? this._navigationBurstCount + 1 : 1;
            this._navigationBurstLastInputAt = now;
            this._navigationBurstFreezeUntil =
                now +
                (isBurstContinuation ? navigationBurstSettleDelayMs : navigationSettleDelayMs);
        },

        navigationBurstRemainingMsFor(source = 'generic') {
            if (!this.isHighFrequencyNavigationSource(source)) {
                return 0;
            }

            if (this.isImmediateNavigationSource(source)) {
                return 0;
            }

            return Math.max(0, this._navigationBurstFreezeUntil - Date.now());
        },

        resolveNavigationCommitDelay(source = 'generic', delayMs = navigationSettleDelayMs) {
            const normalizedDelay = Math.max(
                0,
                Math.trunc(Number(delayMs) || navigationSettleDelayMs),
            );

            return Math.max(normalizedDelay, this.navigationBurstRemainingMsFor(source));
        },

        schedulePendingNavigationCommit(delayMs = navigationSettleDelayMs) {
            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            const normalizedDelayMs = Math.max(
                0,
                Math.trunc(Number(delayMs) || navigationSettleDelayMs),
            );

            this.traceReaderReveal('schedule-pending-navigation-commit', {
                delayMs: normalizedDelayMs,
            });

            this._navigationDebounceTimer = window.setTimeout(() => {
                this._navigationDebounceTimer = null;
                void this.commitPendingNavigation();
            }, normalizedDelayMs);
        },

        setNavigationRevealLock(durationMs = navigationRevealLockDurationMs) {
            this._navigationRevealLocked = true;
            this.traceReaderReveal('set-navigation-reveal-lock', {
                durationMs: Math.max(
                    120,
                    Math.trunc(Number(durationMs) || navigationRevealLockDurationMs),
                ),
            });

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            this._navigationRevealUnlockTimer = window.setTimeout(
                () => {
                    this._navigationRevealUnlockTimer = null;
                    this._navigationRevealLocked = false;
                    this.traceReaderReveal('clear-navigation-reveal-lock');

                    if (this._pendingNavigationRequest !== null) {
                        this.schedulePendingNavigationCommit(
                            this.resolveNavigationCommitDelay(
                                this._pendingNavigationRequest?.source ?? 'generic',
                                0,
                            ),
                        );
                    }
                },
                Math.max(120, Math.trunc(Number(durationMs) || navigationRevealLockDurationMs)),
            );
        },

        async commitPendingNavigation() {
            if (this._pendingNavigationRequest === null) {
                return;
            }

            if (this._navigationRevealLocked || this.isLoadingPage) {
                this.traceReaderReveal('skip-commit-pending-navigation', {
                    reason: this._navigationRevealLocked ? 'reveal-locked' : 'loading-page',
                });
                this.schedulePendingNavigationCommit(
                    this.resolveNavigationCommitDelay(
                        this._pendingNavigationRequest?.source ?? 'generic',
                        navigationSettleDelayMs,
                    ),
                );

                return;
            }

            const request = this._pendingNavigationRequest;
            const burstRemainingMs = this.navigationBurstRemainingMsFor(request.source);

            if (burstRemainingMs > 0) {
                this.schedulePendingNavigationCommit(burstRemainingMs);

                return;
            }

            this._pendingNavigationRequest = null;
            const isSamePageNavigation =
                request.targetPage === this.pageNumber && this.mushafLines.length > 0;

            await this.goToPage(request.targetPage, {
                direction: request.direction,
                animate: request.animate,
                activeAyahIndex: request.activeAyahIndex,
                searchHighlightAyahIndex: request.searchHighlightAyahIndex,
                forceRefit: request.forceRefit,
                source: request.source,
            });

            if (request.animate && !isSamePageNavigation) {
                this.setNavigationRevealLock();
            }
        },

        async navigateToPage(
            targetPage,
            {
                direction = 'next',
                animate = true,
                activeAyahIndex = null,
                searchHighlightAyahIndex = null,
                source = 'generic',
                forceRefit = false,
                commitNow = false,
                settleDelayMs = navigationSettleDelayMs,
            } = {},
        ) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);
            const resolvedDirection = this.resolveNavigationDirection(
                normalizedTargetPage,
                direction,
            );
            const previousInputPage = clampPage(this.pageInput, this.maxPage);
            this.pauseIdleWarmup(this.resolveIdleWarmupPauseDuration(source), {
                preservePage: normalizedTargetPage,
            });

            if (previousInputPage !== normalizedTargetPage) {
                this.triggerPageCounterPulse(previousInputPage, normalizedTargetPage, {
                    source,
                });
            }

            this.pageInput = normalizedTargetPage;
            this._lastPageInputVisualValue = normalizedTargetPage;
            this.registerNavigationBurst(source);

            this._pendingNavigationRequest = {
                targetPage: normalizedTargetPage,
                direction: resolvedDirection,
                animate: Boolean(animate),
                activeAyahIndex,
                searchHighlightAyahIndex,
                source,
                forceRefit: Boolean(forceRefit),
            };

            if (this._navigationRevealLocked || this.isLoadingPage) {
                this.traceReaderReveal('defer-navigate-to-page', {
                    source,
                    targetPage: normalizedTargetPage,
                    reason: this._navigationRevealLocked ? 'reveal-locked' : 'loading-page',
                });
                this.schedulePendingNavigationCommit(
                    this.resolveNavigationCommitDelay(source, settleDelayMs),
                );

                return;
            }

            const resolvedCommitDelay = this.resolveNavigationCommitDelay(source, settleDelayMs);

            if (commitNow) {
                if (this.navigationBurstRemainingMsFor(source) <= 0) {
                    await this.commitPendingNavigation();

                    if (this._pendingNavigationRequest !== null) {
                        this.schedulePendingNavigationCommit(
                            this.resolveNavigationCommitDelay(source, settleDelayMs),
                        );
                    }

                    return;
                }

                this.schedulePendingNavigationCommit(
                    this.resolveNavigationCommitDelay(source, settleDelayMs),
                );

                return;
            }

            this.schedulePendingNavigationCommit(resolvedCommitDelay);
        },

        async nextPage(source = 'generic') {
            const sourceProfile = this.navigationSourceProfile(source);

            if (this.wirdModeActive) {
                await this.stepWird('next', sourceProfile);

                return;
            }

            const basePage = this.navigationBasePage();
            const shouldCommitImmediately = this.isImmediateNavigationSource(sourceProfile);

            await this.navigateToPage(basePage + 1, {
                direction: 'next',
                source: sourceProfile,
                commitNow: shouldCommitImmediately,
                settleDelayMs: shouldCommitImmediately ? 0 : navigationSettleDelayMs,
            });
        },

        async previousPage(source = 'generic') {
            const sourceProfile = this.navigationSourceProfile(source);

            if (this.wirdModeActive) {
                await this.stepWird('prev', sourceProfile);

                return;
            }

            const basePage = this.navigationBasePage();
            const shouldCommitImmediately = this.isImmediateNavigationSource(sourceProfile);

            if (basePage <= 1) {
                this.requestReaderGateNavigation(sourceProfile);

                return;
            }

            await this.navigateToPage(basePage - 1, {
                direction: 'prev',
                source: sourceProfile,
                commitNow: shouldCommitImmediately,
                settleDelayMs: shouldCommitImmediately ? 0 : navigationSettleDelayMs,
            });
        },

        isFirstNavigationPage() {
            if (this.wirdModeActive) {
                const record = this.ensureWirdDailyRecord();
                const pageStep = this.wirdStepForPage(this.pageNumber, record, {
                    preferredStep: this.wirdActiveStepForNavigation(record),
                });

                if (pageStep !== null) {
                    return pageStep <= 0;
                }

                if (record?.completed) {
                    return this.wirdBrowseStepValue(record) <= 0;
                }

                return this.wirdCurrentStep(record) <= 0;
            }

            return this.navigationBasePage() <= 1;
        },

        isLastNavigationPage() {
            if (this.wirdModeActive) {
                const record = this.ensureWirdDailyRecord();
                const requiredPages = Math.max(
                    1,
                    this.normalizeIntegerFlag(record?.requiredPages, 1, { min: 1 }),
                );
                const pageStep = this.wirdStepForPage(this.pageNumber, record, {
                    preferredStep: this.wirdActiveStepForNavigation(record),
                });

                if (pageStep !== null) {
                    return pageStep >= requiredPages - 1;
                }

                if (record?.completed) {
                    return this.wirdBrowseStepValue(record) >= requiredPages - 1;
                }

                return this.wirdCurrentStep(record) >= requiredPages - 1;
            }

            return this.maxPage > 0 && this.navigationBasePage() >= this.maxPage;
        },

        async goNextFromChevron(source = 'chevron') {
            const resolvedSource = this.consumePendingChevronSource(source);

            if (!this.wirdModeActive && this.isLastNavigationPage()) {
                return;
            }

            await this.nextPage(resolvedSource);
        },

        async goPreviousFromChevron(source = 'chevron') {
            await this.previousPage(this.consumePendingChevronSource(source));
        },

        async goToPageFromChevron(
            targetPage,
            {
                source = 'chevron-page',
                activeAyahIndex = null,
                searchHighlightAyahIndex = null,
                forceRefit = true,
                animate = true,
                commitNow = null,
                settleDelayMs = null,
            } = {},
        ) {
            const sourceProfile = this.navigationSourceProfile(source);
            const normalizedTargetPage = clampPage(targetPage ?? this.pageInput, this.maxPage);
            const shouldCommitImmediately =
                typeof commitNow === 'boolean'
                    ? commitNow
                    : this.isImmediateNavigationSource(sourceProfile);
            const resolvedSettleDelayMs = Number.isFinite(Number(settleDelayMs))
                ? Math.max(0, Math.trunc(Number(settleDelayMs)))
                : shouldCommitImmediately
                  ? 0
                  : navigationSettleDelayMs;

            await this.navigateToPage(normalizedTargetPage, {
                direction: this.resolveNavigationDirection(normalizedTargetPage),
                animate: Boolean(animate),
                activeAyahIndex,
                searchHighlightAyahIndex,
                source: sourceProfile,
                forceRefit: Boolean(forceRefit),
                commitNow: shouldCommitImmediately,
                settleDelayMs: resolvedSettleDelayMs,
            });

            return normalizedTargetPage;
        },

        consumePendingChevronSource(fallbackSource = 'chevron') {
            const pendingSource = String(this.pendingChevronSource ?? '').trim();

            this.pendingChevronSource = null;

            if (pendingSource !== '') {
                return pendingSource;
            }

            return String(fallbackSource ?? '').trim() || 'chevron';
        },

        resolveChevronButton(direction) {
            if (direction === 'next') {
                const nextButton = this.$refs?.nextChevronButton;

                return nextButton instanceof HTMLButtonElement ? nextButton : null;
            }

            if (direction === 'prev') {
                const previousButton = this.$refs?.prevChevronButton;

                return previousButton instanceof HTMLButtonElement ? previousButton : null;
            }

            return null;
        },

        triggerChevronButtonClick(direction, source = 'chevron') {
            const chevronButton = this.resolveChevronButton(direction);

            if (!(chevronButton instanceof HTMLButtonElement) || chevronButton.disabled) {
                this.pendingChevronSource = null;

                return false;
            }

            this.pendingChevronSource = String(source ?? '').trim() || 'chevron';
            chevronButton.click();

            return true;
        },

        async handleRequestedNavigation(kind, detail = {}) {
            this.resetSwipeState();
            const requestedSource = String(detail?.source ?? '').trim();

            if (this.wirdModeActive && kind === 'page') {
                return;
            }

            if (kind === 'next') {
                await this.goNextFromChevron();

                return;
            }

            if (kind === 'prev') {
                await this.goPreviousFromChevron();

                return;
            }

            if (kind === 'page') {
                const requestedPage = clampPage(detail?.page ?? this.pageInput, this.maxPage);
                const isPriorityPageRequest =
                    requestedSource === 'page-jump' || requestedSource === 'page-slider-commit';
                const isSliderCommitRequest = requestedSource === 'page-slider-commit';
                const shouldResetQueueForPriorityRequest = requestedSource === 'page-jump';
                const shouldCommitImmediately = isPriorityPageRequest;

                if (shouldResetQueueForPriorityRequest) {
                    this.resetNavigationQueueForPriorityJump();
                    this.clearPendingPostModalTargetFit();
                }

                if (isPriorityPageRequest) {
                    this._bypassNextFitCache = true;
                }

                if (requestedSource === 'page-jump') {
                    this.suppressModalLifecycleEffects([this.jumpPageModalId], {
                        durationMs: Math.max(
                            modalLifecycleSuppressionDurationMs,
                            postModalFitRevealSettleDelayMs + 640,
                        ),
                    });
                    await this.waitForModalLifecycleToSettle(28, 28);
                    await wait(modalCloseTransitionDelayMs);
                    this._bypassNextFitCache = true;
                }

                if (isSliderCommitRequest) {
                    if (this._navigationDebounceTimer !== null) {
                        clearTimeout(this._navigationDebounceTimer);
                        this._navigationDebounceTimer = null;
                    }

                    this._pendingNavigationRequest = null;
                    this._navigationRevealLocked = false;
                    this.clearSwipeRevealWatchdog();

                    await this.goToPage(requestedPage, {
                        direction: this.resolveNavigationDirection(requestedPage),
                        animate: false,
                        forceRefit: true,
                        source: requestedSource || 'page-event',
                    });
                } else {
                    await this.goToPageFromChevron(requestedPage, {
                        source: requestedSource || 'page-event',
                        commitNow: shouldCommitImmediately || undefined,
                    });
                }

                if (isPriorityPageRequest) {
                    if (isSliderCommitRequest) {
                        this._bypassNextFitCache = true;
                        this._fitSanityContextKey = '';
                        this._fitSanityContextAttemptCount = 0;
                        this._fitSanityContextLastWidth = 0;
                        this._fitSanityContextLastHeight = 0;
                        this._fitSanityContextOutcome = '';
                        this._fitSanitySuppressedUntil = 0;
                        this._fitSanityDisabledContextKey = '';
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 140,
                            maxAttempts: 5,
                            useIdleFit: false,
                        });
                    }

                    if (
                        !this.isCurrentPageVisiblyReady() &&
                        this._lastFittedPageNumber !== this.pageNumber
                    ) {
                        this._bypassNextFitCache = true;
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 170,
                            maxAttempts: 4,
                            useIdleFit: false,
                        });
                    }

                    if (!this.isCurrentPageVisiblyReady() && this.hasRenderablePage()) {
                        this.clearStaleRevealGuards();
                        this.forceRevealCurrentPage('priority-page-post-fit-fail-open');
                    }
                }

                if (requestedSource === 'page-jump' || requestedSource === 'page-slider-commit') {
                    this.recordNavigationHistory({
                        source: requestedSource,
                        pageNumber: requestedPage,
                        surahNumber: this.currentSurahNumber(),
                    });
                }
            }
        },

        resetNavigationQueueForPriorityJump() {
            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            this._pendingNavigationRequest = null;
            this._navigationRevealLocked = false;
            this.clearSwipeRevealWatchdog();
            this.clearNavigationBurstState();
        },

        async onGlobalArrowNavigate(direction, event = null) {
            if (event?.__quranReaderInputHandled) {
                return;
            }

            if (event && typeof event === 'object') {
                event.__quranReaderInputHandled = true;
            }

            if (this.search.modalOpen) {
                return;
            }

            if (event?.ctrlKey || event?.metaKey || event?.altKey || event?.shiftKey) {
                return;
            }

            if (
                event?.target?.closest?.(
                    'input:not([type="range"]), textarea, select, [contenteditable="true"], [role="textbox"]',
                )
            ) {
                return;
            }

            if (event?.cancelable) {
                event.preventDefault();
            }

            if (direction === 'left') {
                if (this.triggerChevronButtonClick('next', 'keyboard')) {
                    return;
                }

                await this.goNextFromChevron('keyboard');

                return;
            }

            if (direction === 'right') {
                if (this.triggerChevronButtonClick('prev', 'keyboard')) {
                    return;
                }

                await this.goPreviousFromChevron('keyboard');
            }
        },

        dispatchPageNavigationRequest(targetPage, source = 'generic') {
            window.dispatchEvent(
                new CustomEvent('quran-go-page', {
                    detail: {
                        page: clampPage(targetPage, this.maxPage),
                        source,
                    },
                }),
            );
        },

        commitPageSliderTargetPage(
            targetPage,
            { source = 'page-slider-commit', dedupeWindowMs = 340 } = {},
        ) {
            if (this.wirdModeActive) {
                return false;
            }

            const normalizedTargetPage = clampPage(targetPage ?? this.pageInput, this.maxPage);
            const now = Date.now();
            const normalizedDedupeWindowMs = Math.max(
                120,
                Math.trunc(Number(dedupeWindowMs) || 340),
            );

            if (
                normalizedTargetPage === this._lastPageSliderCommitPage &&
                now - this._lastPageSliderCommitAt < normalizedDedupeWindowMs
            ) {
                return false;
            }

            this._lastPageSliderCommitPage = normalizedTargetPage;
            this._lastPageSliderCommitAt = now;
            this.pageInput = normalizedTargetPage;
            this._lastPageInputVisualValue = normalizedTargetPage;
            this.dispatchPageNavigationRequest(
                normalizedTargetPage,
                String(source ?? '').trim() || 'page-slider-commit',
            );

            return true;
        },

        async goToPage(
            pageNumber,
            {
                direction = 'next',
                animate = true,
                activeAyahIndex = null,
                searchHighlightAyahIndex = null,
                forceRefit = false,
                source = 'generic',
            } = {},
        ) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);
            const normalizedSearchHighlightAyahIndex =
                Number.isFinite(Number(searchHighlightAyahIndex)) &&
                Number(searchHighlightAyahIndex) > 0
                    ? Math.trunc(Number(searchHighlightAyahIndex))
                    : 0;
            const nextSearchHighlightedAyahIndex =
                source === 'search-result' ? normalizedSearchHighlightAyahIndex : 0;
            this.clearWordPressState();
            this.hoveredAyahIndex = 0;
            this.hoveredWordIndex = 0;

            if (normalizedPage === this.pageNumber && this.mushafLines.length > 0) {
                if (this.pageInput !== normalizedPage) {
                    this.triggerPageCounterPulse(this.pageInput, normalizedPage, {
                        source,
                    });
                }

                this.pageInput = normalizedPage;
                this._lastPageInputVisualValue = normalizedPage;
                this.persistLastPageNumber(normalizedPage);
                this.searchHighlightedAyahIndex = nextSearchHighlightedAyahIndex;

                const hasOpenModals =
                    this.openModalCount() > 0 ||
                    this._isModalLifecycleSettling ||
                    this._activeModalIds.size > 0;

                if (!hasOpenModals) {
                    this.recoverStaleModalLifecycleState();

                    if (forceRefit) {
                        await this.layoutPageGuaranteed({ revealDelayMs: 200 });
                    } else if (
                        this.isFittingPage ||
                        this._lastFittedPageNumber !== normalizedPage
                    ) {
                        await this.layoutPageGuaranteed({
                            revealDelayMs: 140,
                            maxAttempts: 3,
                            useIdleFit: false,
                        });
                    }

                    if (this.hasRenderablePage()) {
                        this.isFittingPage = false;
                    }
                }

                return;
            }

            this.isLoadingPage = true;
            let didCompletePageTransition = false;
            let didAbortPageTransition = false;
            const pageAbortController = this.beginActivePageLoadAbortController();

            try {
                const payloadPromise = this.getPagePayload(normalizedPage, {
                    signal: pageAbortController?.signal ?? null,
                });
                const transitionDelayMs = this.isHighFrequencyNavigationSource(source) ? 68 : 128;

                if (this.mushafLines.length > 0) {
                    this.isTransitioningOutPage = true;
                    this.isFittingPage = false;
                    await this.nextTickAsync();
                    await wait(transitionDelayMs);
                    this.isTransitioningOutPage = false;
                    this.isFittingPage = true;
                }

                const payload = await payloadPromise;

                if (payload === null) {
                    didAbortPageTransition = true;

                    return;
                }

                const pendingTargetPage = Math.max(
                    0,
                    Math.trunc(Number(this._pendingNavigationRequest?.targetPage ?? 0)),
                );

                if (pendingTargetPage > 0 && pendingTargetPage !== normalizedPage) {
                    return;
                }

                this.applyPayload(payload, { setPageNumber: true });
                this.persistLastPageNumber(this.pageNumber);
                this.refreshSurahTriggerCaption(animate);
                this.syncSearchActiveSurahNumber();
                this.activeAyahIndex =
                    this.shouldPersistActivationIndexes() &&
                    Number.isFinite(Number(activeAyahIndex)) &&
                    Number(activeAyahIndex) > 0
                        ? Math.trunc(Number(activeAyahIndex))
                        : 0;
                this.activeWordIndex = 0;
                this.searchHighlightedAyahIndex = nextSearchHighlightedAyahIndex;

                if (animate) {
                    this.playPageMotion(direction);
                }

                if (this.navigationBurstRemainingMsFor(source) <= 0) {
                    this.prefetchNeighborPages(normalizedPage);
                }

                const shouldUseFastFitPriority = this.isFastFitPrioritySource(source);
                await this.layoutPageGuaranteed({
                    revealDelayMs: shouldUseFastFitPriority
                        ? 170
                        : this.isHighFrequencyNavigationSource(source)
                          ? 180
                          : 220,
                    maxAttempts: shouldUseFastFitPriority
                        ? 3
                        : this.isHighFrequencyNavigationSource(source)
                          ? 3
                          : 4,
                    useIdleFit: !shouldUseFastFitPriority,
                });
                didCompletePageTransition = true;
            } catch (error) {
                if (error?.name === 'AbortError') {
                    didAbortPageTransition = true;

                    return;
                }

                if (this.hasRenderablePage()) {
                    this.isFittingPage = false;
                }
            } finally {
                if (this._activePageAbortController === pageAbortController) {
                    this._activePageAbortController = null;
                }

                this.isLoadingPage = false;
                this.isTransitioningOutPage = false;

                if (!didCompletePageTransition && this.hasRenderablePage()) {
                    this.scheduleLayout({ revealDelayMs: 150 });
                }

                if (
                    didAbortPageTransition &&
                    this._pendingNavigationRequest === null &&
                    this.hasRenderablePage() &&
                    !this._layoutActivePromise
                ) {
                    this.isFittingPage = false;
                }

                this.flushQueuedLayoutRequest();

                if (this._pendingNavigationRequest !== null) {
                    if (this._navigationRevealLocked) {
                        this.schedulePendingNavigationCommit(
                            this.resolveNavigationCommitDelay(
                                this._pendingNavigationRequest?.source ?? 'generic',
                                0,
                            ),
                        );
                    } else {
                        void this.commitPendingNavigation();
                    }
                }

                this.scheduleIdleWarmup();
            }
        },

        onPageInputInput() {
            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }

            const normalizedInputPage = clampPage(this.pageInput, this.maxPage);
            const previousVisualPage = clampPage(this._lastPageInputVisualValue, this.maxPage);

            if (normalizedInputPage !== previousVisualPage) {
                this.triggerPageCounterPulse(previousVisualPage, normalizedInputPage, {
                    source: 'page-input',
                });
            }

            this._lastPageInputVisualValue = normalizedInputPage;
        },

        queueWirdSliderCommit(step, { source = 'slider', delayMs = 0 } = {}) {
            if (!this.wirdModeActive) {
                return;
            }

            const range = this.wirdRangeState();
            const normalizedStep = this.normalizeIntegerFlag(step, this.sliderValue(), {
                min: 0,
                max: range.maxStep,
            });
            this._wirdSliderPendingCommitStep = normalizedStep;

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }

            this._wirdSliderInputCommitTimer = window.setTimeout(
                () => {
                    this._wirdSliderInputCommitTimer = null;

                    if (!this.wirdModeActive) {
                        this._wirdSliderPendingCommitStep = null;

                        return;
                    }

                    const currentRange = this.wirdRangeState();
                    const commitStep = this.normalizeIntegerFlag(
                        this._wirdSliderPendingCommitStep,
                        this.sliderValue(),
                        {
                            min: 0,
                            max: currentRange.maxStep,
                        },
                    );

                    this._wirdSliderPendingCommitStep = null;
                    this._wirdSliderLastInputStep = null;
                    this._wirdSliderLastInputAt = 0;
                    this.clearWirdSliderVisualTween();
                    this.wirdSliderVisualStep = commitStep;
                    const record = this.ensureWirdDailyRecord();
                    const targetPage = this.wirdTargetPageFromStep(commitStep, record);
                    const now = Date.now();

                    if (
                        targetPage === this._wirdLastCommittedTargetPage &&
                        commitStep === this._wirdLastCommittedStep &&
                        now - this._wirdLastCommittedAt < 320 &&
                        (this.isLoadingPage || this._pendingNavigationRequest !== null)
                    ) {
                        return;
                    }

                    this._wirdLastCommittedTargetPage = targetPage;
                    this._wirdLastCommittedStep = commitStep;
                    this._wirdLastCommittedAt = now;
                    void this.navigateWirdToStep(commitStep, source);
                },
                Math.max(0, Math.trunc(Number(delayMs) || 0)),
            );
        },

        onSliderPointerRelease(event = null) {
            if (typeof document === 'undefined') {
                return;
            }

            const releaseTarget = event?.target;
            const targetSlider =
                releaseTarget instanceof HTMLInputElement && releaseTarget.type === 'range'
                    ? releaseTarget
                    : null;
            const activeElement =
                document.activeElement instanceof HTMLInputElement &&
                document.activeElement.type === 'range'
                    ? document.activeElement
                    : null;
            const sliderElement = targetSlider ?? activeElement;

            if (!(sliderElement instanceof HTMLInputElement)) {
                return;
            }

            if (!sliderElement.classList.contains('quran-page-slider')) {
                return;
            }

            const sliderTargetPage = clampPage(
                sliderElement.value ?? this.pageInput ?? this.pageNumber,
                this.maxPage,
            );
            const shouldAttemptFallbackCommit =
                !this.wirdModeActive &&
                sliderTargetPage > 0 &&
                sliderTargetPage !== this.pageNumber;

            window.setTimeout(() => {
                if (document.activeElement === sliderElement) {
                    sliderElement.blur();
                }

                this._pageSliderInteractionActive = false;
            }, 0);

            if (shouldAttemptFallbackCommit) {
                this.commitPageSliderTargetPage(sliderTargetPage, {
                    source: 'page-slider-commit',
                });
            }
        },

        async onSliderInput(event = null) {
            if (!this.wirdModeActive) {
                const targetPage = clampPage(event?.target?.value ?? this.pageInput, this.maxPage);
                this._pageSliderInteractionActive = true;

                this.pageInput = targetPage;
                this.onPageInputInput();

                return;
            }

            const range = this.wirdRangeState();
            const step = this.normalizeIntegerFlag(event?.target?.value, this.sliderValue(), {
                min: 0,
                max: range.maxStep,
            });
            const targetPage = this.wirdTargetPageFromStep(step, range.record);
            const previousCounterValue = this.pageCounterCurrentDisplayValue();
            const nextCounterValue = step + 1;

            if (nextCounterValue !== previousCounterValue) {
                this.triggerPageCounterPulse(previousCounterValue, nextCounterValue, {
                    source: 'page-slider-logical',
                });
            }

            this.pageInput = targetPage;
            this._lastPageInputVisualValue = targetPage;

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }

            this.clearWirdSliderVisualTween();
            this.wirdSliderVisualStep = step;
            this._wirdSliderLastInputStep = step;
            this._wirdSliderLastInputAt = Date.now();
            this.queueWirdSliderCommit(step, {
                source: 'slider-input-idle',
                delayMs: 140,
            });
        },

        async onSliderCommit(event = null) {
            this.onSliderPointerRelease(event);

            if (!this.wirdModeActive) {
                const targetPage = clampPage(event?.target?.value ?? this.pageInput, this.maxPage);
                this._pageSliderInteractionActive = false;
                this.commitPageSliderTargetPage(targetPage, {
                    source: 'page-slider-commit',
                });

                return;
            }

            if (this._wirdSliderInputCommitTimer !== null) {
                clearTimeout(this._wirdSliderInputCommitTimer);
                this._wirdSliderInputCommitTimer = null;
            }

            const range = this.wirdRangeState();
            const directCommitStep = this.normalizeIntegerFlag(
                event?.target?.value,
                this.sliderValue(),
                {
                    min: 0,
                    max: range.maxStep,
                },
            );
            const hasRecentInputStep =
                Number.isFinite(Number(this._wirdSliderLastInputStep)) &&
                Date.now() - this._wirdSliderLastInputAt <= 520;
            const freshestInputStep = Number.isFinite(Number(this._wirdSliderPendingCommitStep))
                ? this._wirdSliderPendingCommitStep
                : this._wirdSliderLastInputStep;
            const commitStep = hasRecentInputStep
                ? this.normalizeIntegerFlag(freshestInputStep, directCommitStep, {
                      min: 0,
                      max: range.maxStep,
                  })
                : directCommitStep;

            this.queueWirdSliderCommit(commitStep, {
                source: 'slider',
                delayMs: 0,
            });
        },

        async onPageInputBlur() {
            if (this.wirdModeActive) {
                return;
            }

            const now = Date.now();
            const targetPage = clampPage(this.pageInput, this.maxPage);

            if (
                targetPage === this._lastPageInputCommitPage &&
                now - this._lastPageInputCommitAt < 420
            ) {
                return;
            }

            await this.onPageInputCommit({
                force: true,
                commitNow: true,
                source: 'page-input-blur',
            });
        },

        async onPageInputCommit({ force = false, commitNow = false, source = 'page-input' } = {}) {
            if (this.wirdModeActive) {
                return;
            }

            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }

            const targetPage = clampPage(this.pageInput, this.maxPage);
            const direction = this.resolveNavigationDirection(targetPage);
            this._lastPageInputCommitPage = targetPage;
            this._lastPageInputCommitAt = Date.now();

            this.pageInput = targetPage;
            this._lastPageInputVisualValue = targetPage;

            if (!force && targetPage === this.pageNumber) {
                return;
            }

            await this.navigateToPage(targetPage, {
                direction,
                animate: true,
                source,
                forceRefit: true,
                commitNow: Boolean(commitNow),
            });
        },

        applyPayload(payload, { setPageNumber = false, persistPageNumber = true } = {}) {
            const normalizedPayload = normalizePayload(payload);
            const previousPageNumber = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const payloadPageNumber = clampPage(
                normalizedPayload.pageNumber,
                normalizedPayload.maxPage,
            );

            this.ready = normalizedPayload.ready;
            this.maxPage = normalizedPayload.maxPage;
            this.mushafLines = normalizedPayload.mushafLines;
            this.lineWordGapAdjustments = {};
            this._lastWordGapRebalancedPageNumber = 0;
            this.rebuildWordSelectionIndex();
            this.useCenteredAyahLayout = normalizedPayload.useCenteredAyahLayout;
            this.qpcPageFontFamily = normalizedPayload.qpcPageFontFamily;
            this.qpcPageFontUrl = normalizedPayload.qpcPageFontUrl;
            this.qpcPageFontFormat = normalizedPayload.qpcPageFontFormat;
            this.basmallahFontFamily = normalizedPayload.basmallahFontFamily;
            this.basmallahFontUrl = normalizedPayload.basmallahFontUrl;
            this.basmallahFontFormat = normalizedPayload.basmallahFontFormat;
            this.basmallahText = normalizedPayload.basmallahText;
            this.surahHeaderFontFamily =
                normalizedPayload.surahHeaderFontFamily ?? this.surahHeaderFontFamily;
            this.surahHeaderFontUrl =
                normalizedPayload.surahHeaderFontUrl ?? this.surahHeaderFontUrl;
            this.surahHeaderFontFormat =
                normalizedPayload.surahHeaderFontFormat ?? this.surahHeaderFontFormat;
            this.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah =
                normalizedPayload.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah ??
                this.surahHeaderTopPaddingWhenFollowingPreviousSurahAyah;

            if (
                normalizedPayload.surahNames &&
                Object.keys(normalizedPayload.surahNames).length > 0
            ) {
                this.search.surahNames = normalizedPayload.surahNames;
            }

            const hasIncomingSurahDirectory =
                Array.isArray(normalizedPayload.surahDirectory) &&
                normalizedPayload.surahDirectory.length > 0;

            this.buildSurahDirectory(
                hasIncomingSurahDirectory
                    ? normalizedPayload.surahDirectory
                    : this.search.surahDirectory,
            );
            this._loadedPayloadPageNumber = payloadPageNumber;

            if (setPageNumber) {
                this.pageNumber = payloadPageNumber;

                if (persistPageNumber) {
                    this.persistLastPageNumber(this.pageNumber);
                }
            }

            if (this.pageInput !== this.pageNumber) {
                this.triggerPageCounterPulse(this.pageInput, this.pageNumber, {
                    source: 'payload-sync',
                });
            }

            this.pageInput = this.pageNumber;
            this._lastPageInputVisualValue = this.pageNumber;
            this.syncPageFontFace();
            this.syncBasmallahFontFace();
            this.syncSurahHeaderFontFace();

            if (
                this.pageNumber !== previousPageNumber ||
                this._lastFittedPageNumber !== this.pageNumber
            ) {
                this.resetCurrentPageFitStyles();
                this._lastFittedPageNumber = 0;
                this._fitSanityContextKey = '';
                this._fitSanityContextAttemptCount = 0;
                this._fitSanityContextLastWidth = 0;
                this._fitSanityContextLastHeight = 0;
                this._fitSanityContextOutcome = '';
                this._fitSanitySuppressedUntil = 0;
                this._fitSanityDisabledContextKey = '';
                this._fontReadyRecoveryAttemptPage = 0;
                this._fontReadyRecoveryAttemptCount = 0;
                this._fontReadyRecoveryLastAt = 0;
            }
        },

        async nextTickAsync() {
            await new Promise((resolve) => this.$nextTick(resolve));
        },

        async resolveWithTimeout(
            promise,
            timeoutMs = pageFontLoadTimeoutMs,
            { timeoutValue = 'timeout', resolveOnReject = true } = {},
        ) {
            let timerId = null;
            const normalizedTimeoutMs = Math.max(
                120,
                Math.trunc(Number(timeoutMs) || pageFontLoadTimeoutMs),
            );
            const timeoutPromise = new Promise((resolve) => {
                timerId = window.setTimeout(() => {
                    resolve(timeoutValue);
                }, normalizedTimeoutMs);
            });

            try {
                if (resolveOnReject) {
                    return await Promise.race([
                        Promise.resolve(promise)
                            .then(() => 'resolved')
                            .catch(() => 'rejected'),
                        timeoutPromise,
                    ]);
                }

                return await Promise.race([Promise.resolve(promise), timeoutPromise]);
            } finally {
                if (timerId !== null) {
                    clearTimeout(timerId);
                }
            }
        },

        scheduleFontReadyRecoveryRefit(
            pageNumber = this.pageNumber,
            { delayMs = pageFontReadyRecoveryDelayMs } = {},
        ) {
            if (!document.fonts?.ready) {
                return;
            }

            const normalizedPageNumber = clampPage(pageNumber, this.maxPage);

            if (normalizedPageNumber <= 0) {
                return;
            }

            const now = Date.now();
            const isSameRecoveryPage = this._fontReadyRecoveryAttemptPage === normalizedPageNumber;

            if (!isSameRecoveryPage) {
                this._fontReadyRecoveryAttemptPage = normalizedPageNumber;
                this._fontReadyRecoveryAttemptCount = 0;
                this._fontReadyRecoveryLastAt = 0;
            }

            if (
                this._fontReadyRecoveryTimer !== null &&
                this._fontReadyRecoveryPage === normalizedPageNumber
            ) {
                return;
            }

            if (
                this._fontReadyRecoveryAttemptCount >= 2 &&
                now - this._fontReadyRecoveryLastAt < 4_800
            ) {
                return;
            }

            if (now - this._fontReadyRecoveryLastAt < 2_200) {
                return;
            }

            if (this._fontReadyRecoveryTimer !== null) {
                clearTimeout(this._fontReadyRecoveryTimer);
                this._fontReadyRecoveryTimer = null;
            }

            this._fontReadyRecoveryAttemptCount += 1;
            this._fontReadyRecoveryLastAt = now;
            this._fontReadyRecoveryPage = normalizedPageNumber;
            this._fontReadyRecoveryTimer = window.setTimeout(
                () => {
                    this._fontReadyRecoveryTimer = null;
                    const recoveryTargetPage = Math.max(
                        0,
                        Math.trunc(Number(this._fontReadyRecoveryPage ?? 0)),
                    );

                    if (recoveryTargetPage <= 0) {
                        return;
                    }

                    Promise.resolve(document.fonts.ready)
                        .then(() => {
                            if (
                                !this.hasRenderablePage() ||
                                this.pageNumber !== recoveryTargetPage ||
                                this.isLoadingPage
                            ) {
                                return;
                            }

                            // Do not alter fit after reveal begins.
                            if (
                                this.isCurrentPageVisiblyReady() &&
                                this.pageFitState() === 'ready'
                            ) {
                                return;
                            }

                            this._bypassNextFitCache = true;
                            this.scheduleLayout({
                                revealDelayMs: 130,
                                maxAttempts: 5,
                            });
                        })
                        .catch(() => {
                            // Ignore delayed font readiness failures.
                        });
                },
                Math.max(120, Math.trunc(Number(delayMs) || pageFontReadyRecoveryDelayMs)),
            );
        },

        async waitForPageFontReady() {
            const family = String(this.qpcPageFontFamily ?? '').trim();
            const basmallahFamily = String(this.basmallahFontFamily ?? '').trim();
            const surahHeaderFamily = String(this.surahHeaderFontFamily ?? '').trim();
            const fallbackMushafFamily = 'MadinaQuran';
            const trackedFamilies = [
                family,
                basmallahFamily,
                surahHeaderFamily,
                fallbackMushafFamily,
            ]
                .map((entry) => String(entry ?? '').trim())
                .filter(Boolean);

            if (trackedFamilies.length === 0 || !document.fonts?.load) {
                return;
            }

            const fontLoadTasks = [];

            try {
                if (family) {
                    fontLoadTasks.push(
                        this.resolveWithTimeout(
                            document.fonts.load(`32px '${family}'`, this.preferredPageProbeText()),
                            pageFontLoadTimeoutMs,
                        ),
                    );
                }

                if (basmallahFamily) {
                    fontLoadTasks.push(
                        this.resolveWithTimeout(
                            document.fonts.load(
                                `32px '${basmallahFamily}'`,
                                this.preferredBasmallahText(),
                            ),
                            pageFontLoadTimeoutMs,
                        ),
                    );
                }

                if (surahHeaderFamily) {
                    fontLoadTasks.push(
                        this.resolveWithTimeout(
                            document.fonts.load(`32px '${surahHeaderFamily}'`, 'الفاتحة'),
                            pageFontLoadTimeoutMs,
                        ),
                    );
                }

                fontLoadTasks.push(
                    this.resolveWithTimeout(
                        document.fonts.load(
                            `32px '${fallbackMushafFamily}'`,
                            this.preferredPageProbeText(),
                        ),
                        pageFontLoadTimeoutMs,
                    ),
                );

                const loadOutcomes = await Promise.all(fontLoadTasks);
                const readyOutcome = await this.resolveWithTimeout(
                    document.fonts.ready,
                    pageFontReadyTimeoutMs,
                );
                const hasMissingTrackedFamily =
                    typeof document.fonts?.check === 'function' &&
                    trackedFamilies.some(
                        (fontFamily) =>
                            !document.fonts.check(
                                `32px '${fontFamily}'`,
                                fontFamily === fallbackMushafFamily
                                    ? this.preferredPageProbeText()
                                    : 'الحمد لله',
                            ),
                    );
                const didTimeout =
                    readyOutcome === 'timeout' ||
                    loadOutcomes.some((outcome) => outcome === 'timeout') ||
                    hasMissingTrackedFamily;

                if (didTimeout) {
                    this._bypassNextFitCache = true;
                    this.scheduleFontReadyRecoveryRefit(this.pageNumber);
                } else if (this._fontReadyRecoveryAttemptPage === this.pageNumber) {
                    const hadPendingTimerForCurrentPage =
                        this._fontReadyRecoveryTimer !== null &&
                        this._fontReadyRecoveryPage === this.pageNumber;
                    this._fontReadyRecoveryAttemptCount = 0;
                    this._fontReadyRecoveryLastAt = 0;
                    this._fontReadyRecoveryPage = 0;

                    if (hadPendingTimerForCurrentPage) {
                        clearTimeout(this._fontReadyRecoveryTimer);
                        this._fontReadyRecoveryTimer = null;
                    }
                }
            } catch (_) {
                // Ignore font loading failures and continue with fallback glyphs.
            }
        },

        normalizeFontFamilyToken(value) {
            return String(value ?? '')
                .trim()
                .replace(/^['"]+|['"]+$/g, '')
                .toLowerCase();
        },

        trackedPageFontFamilies() {
            const families = [
                this.qpcPageFontFamily,
                this.basmallahFontFamily,
                this.surahHeaderFontFamily,
                'MadinaQuran',
            ]
                .map((entry) => String(entry ?? '').trim())
                .filter(Boolean);

            return Array.from(new Set(families));
        },

        areTrackedPageFontsLoaded() {
            if (!document.fonts || typeof document.fonts !== 'object') {
                return true;
            }

            const trackedFamilies = this.trackedPageFontFamilies();

            if (trackedFamilies.length === 0) {
                return true;
            }

            const fontFaces = Array.from(document.fonts);

            return trackedFamilies.every((family) => {
                const normalizedFamily = this.normalizeFontFamilyToken(family);
                const matchingFaces = fontFaces.filter(
                    (fontFace) =>
                        this.normalizeFontFamilyToken(fontFace?.family ?? '') === normalizedFamily,
                );

                if (matchingFaces.length > 0) {
                    return matchingFaces.every(
                        (fontFace) => String(fontFace?.status ?? '') === 'loaded',
                    );
                }

                if (typeof document.fonts.check === 'function') {
                    return document.fonts.check(
                        `32px '${family}'`,
                        family === 'MadinaQuran' ? this.preferredPageProbeText() : 'الحمد لله',
                    );
                }

                return false;
            });
        },

        preferredPageProbeText() {
            if (!Array.isArray(this.mushafLines)) {
                return 'ﱁﱂﱃ';
            }

            for (const line of this.mushafLines) {
                const words = Array.isArray(line?.words) ? line.words : [];

                for (const word of words) {
                    const text = String(word?.text ?? '').trim();

                    if (text !== '') {
                        return text;
                    }
                }
            }

            return 'ﱁﱂﱃ';
        },

        async waitForFontReady(family) {
            const normalizedFamily = String(family ?? '').trim();

            if (!normalizedFamily || !document.fonts?.load) {
                return;
            }

            try {
                const loadOutcome = await this.resolveWithTimeout(
                    document.fonts.load(`32px '${normalizedFamily}'`, 'الحمد لله'),
                    pageFontLoadTimeoutMs,
                );
                const readyOutcome = await this.resolveWithTimeout(
                    document.fonts.ready,
                    pageFontReadyTimeoutMs,
                );

                if (loadOutcome === 'timeout' || readyOutcome === 'timeout') {
                    this.scheduleFontReadyRecoveryRefit(this.pageNumber);
                } else if (this._fontReadyRecoveryAttemptPage === this.pageNumber) {
                    this._fontReadyRecoveryAttemptCount = 0;
                    this._fontReadyRecoveryLastAt = 0;
                    this._fontReadyRecoveryPage = 0;
                }
            } catch (_) {
                // Ignore font loading failures and continue with fallback glyphs.
            }
        },

        syncDynamicFontFace({ styleId, family, url, format = 'woff2' }) {
            let styleTag = document.getElementById(styleId);
            const normalizedFamily = String(family ?? '').trim();
            const normalizedUrl = String(url ?? '').trim();
            const normalizedFormat = String(format ?? 'woff2').trim() || 'woff2';

            if (!normalizedFamily || !normalizedUrl) {
                if (styleTag) {
                    styleTag.remove();
                }

                return;
            }

            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = styleId;
                document.head.appendChild(styleTag);
            }

            styleTag.textContent = `@font-face { font-family: '${normalizedFamily}'; src: url('${normalizedUrl}') format('${normalizedFormat}'); font-display: block; }`;
        },

        syncPageFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-page-font',
                family: this.qpcPageFontFamily,
                url: this.qpcPageFontUrl,
                format: this.qpcPageFontFormat,
            });
        },

        syncBasmallahFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-basmallah-font',
                family: this.basmallahFontFamily,
                url: this.basmallahFontUrl,
                format: this.basmallahFontFormat,
            });
        },

        syncSurahHeaderFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-surah-header-font',
                family: this.surahHeaderFontFamily,
                url: this.surahHeaderFontUrl,
                format: this.surahHeaderFontFormat,
            });
        },

        clearLayoutTimers() {
            if (this._layoutRaf !== null) {
                cancelAnimationFrame(this._layoutRaf);
                this._layoutRaf = null;
            }

            if (this._revealTimer !== null) {
                clearTimeout(this._revealTimer);
                this._revealTimer = null;
            }

            this._revealBlockedSinceAt = 0;
            this._revealBlockedLayoutToken = 0;
        },

        scheduleReaderPanelLayoutRefresh() {
            if (this.$el instanceof Element && !this.$el.isConnected) {
                return;
            }

            if (this._readerPanelLayoutRaf !== null) {
                return;
            }

            this._readerPanelLayoutRaf = requestAnimationFrame(() => {
                this._readerPanelLayoutRaf = null;
                this._readerPanelLayoutSerial += 1;

                requestAnimationFrame(() => {
                    if (
                        !this.ready ||
                        this.isLoadingPage ||
                        !this.hasRenderablePage() ||
                        !this.isReaderElementVisible()
                    ) {
                        return;
                    }

                    this.scheduleLayout({ revealDelayMs: 150, maxAttempts: 5 });
                });
            });
        },

        clearFitResultCache({ persist = true } = {}) {
            this._fitResultByContext.clear();

            if (persist) {
                this.queuePersistedFitCacheWrite();
            }
        },

        normalizeLayoutRequest({ revealDelayMs = 180, maxAttempts = 4 } = {}) {
            return {
                revealDelayMs: Math.max(0, Math.trunc(Number(revealDelayMs) || 180)),
                maxAttempts: Math.max(2, Math.trunc(Number(maxAttempts) || 4)),
            };
        },

        queueLayoutRequest(request = {}) {
            const normalizedRequest = this.normalizeLayoutRequest(request);

            if (this._queuedLayoutRequest === null) {
                this._queuedLayoutRequest = normalizedRequest;

                return;
            }

            this._queuedLayoutRequest = {
                revealDelayMs: Math.min(
                    this._queuedLayoutRequest.revealDelayMs,
                    normalizedRequest.revealDelayMs,
                ),
                maxAttempts: Math.max(
                    this._queuedLayoutRequest.maxAttempts,
                    normalizedRequest.maxAttempts,
                ),
            };
        },

        flushQueuedLayoutRequest() {
            if (this._queuedLayoutRequest === null) {
                return;
            }

            if (
                this.hasBlockingModalLifecycleState() ||
                this.isLoadingPage ||
                !this.hasRenderablePage()
            ) {
                return;
            }

            const queuedRequest = this._queuedLayoutRequest;
            this._queuedLayoutRequest = null;
            this.scheduleLayout(queuedRequest);
        },

        openModalCount() {
            return Array.from(document.querySelectorAll('.fi-modal')).filter((modalElement) =>
                modalElement.classList.contains('fi-modal-open'),
            ).length;
        },

        hasBlockingModalLifecycleState({ recoverStaleState = false } = {}) {
            const openModalCount = this.openModalCount();

            if (openModalCount > 0) {
                return true;
            }

            if (!this._isModalLifecycleSettling && this._activeModalIds.size === 0) {
                return false;
            }

            if (!recoverStaleState) {
                return true;
            }

            return !this.recoverStaleModalLifecycleState();
        },

        recoverStaleModalLifecycleState() {
            const openModalCount = this.openModalCount();

            if (openModalCount > 0) {
                return false;
            }

            if (!this._isModalLifecycleSettling && this._activeModalIds.size === 0) {
                return false;
            }

            this._activeModalIds.clear();
            this._isModalLifecycleSettling = false;

            return true;
        },

        clearStaleRevealGuards({ allowUnlock = true } = {}) {
            let didClearState = false;

            if (
                (this._isModalLifecycleSettling || this._activeModalIds.size > 0) &&
                this.openModalCount() <= 0
            ) {
                this._activeModalIds.clear();
                this._isModalLifecycleSettling = false;
                didClearState = true;
            }

            if (!this.isLoadingPage) {
                const pendingTargetPage = clampPage(
                    Number(this._pendingNavigationRequest?.targetPage ?? 0),
                    this.maxPage,
                );

                if (
                    pendingTargetPage === this.pageNumber &&
                    this._pendingNavigationRequest !== null
                ) {
                    this._pendingNavigationRequest = null;
                    didClearState = true;
                }
            }

            if (
                allowUnlock &&
                this._navigationRevealLocked &&
                !this.isLoadingPage &&
                this._pendingNavigationRequest === null &&
                this._navigationRevealUnlockTimer === null
            ) {
                this._navigationRevealLocked = false;
                didClearState = true;
            }

            return didClearState;
        },

        readerRevealDebugEnabled() {
            try {
                return this.normalizeBooleanFlag(
                    window.localStorage?.getItem?.(readerRevealDebugStorageKey),
                    false,
                );
            } catch (_) {
                return false;
            }
        },

        traceReaderReveal(eventName, details = {}) {
            if (!this.readerRevealDebugEnabled()) {
                return;
            }

            const normalizedEventName = String(eventName ?? '').trim() || 'event';
            const payload =
                details && typeof details === 'object' && !Array.isArray(details) ? details : {};

            console.debug('[quran-reader][reveal]', normalizedEventName, {
                pageNumber: this.pageNumber,
                isFittingPage: this.isFittingPage,
                isLoadingPage: this.isLoadingPage,
                pendingTargetPage: clampPage(
                    Number(this._pendingNavigationRequest?.targetPage ?? 0),
                    this.maxPage,
                ),
                navigationRevealLocked: this._navigationRevealLocked,
                modalLifecycleSettling: this._isModalLifecycleSettling,
                activeModalCount: this._activeModalIds.size,
                openModalCount: this.openModalCount(),
                ...payload,
            });
        },

        clearSwipeRevealWatchdog() {
            if (this._swipeRevealWatchdogTimer === null) {
                return;
            }

            clearTimeout(this._swipeRevealWatchdogTimer);
            this._swipeRevealWatchdogTimer = null;
        },

        forceRevealCurrentPage(reason = 'generic') {
            if (!this.hasRenderablePage()) {
                return false;
            }

            if (this.hasBlockingModalLifecycleState()) {
                this.traceReaderReveal('force-reveal-skipped', {
                    reason,
                    blockedByModalLifecycle: true,
                });

                return false;
            }

            this.clearSwipeRevealWatchdog();
            this.syncPageInputToCurrentPage();
            this.isFittingPage = false;
            this._lastPageRevealAt = Date.now();
            this._revealBlockedSinceAt = 0;
            this._revealBlockedLayoutToken = 0;
            this.traceReaderReveal('force-reveal-current-page', { reason });

            return true;
        },

        scheduleSwipeRevealWatchdog(
            source = 'swipe',
            { delayMs = swipeRevealWatchdogDelayMs, startedAtMs = null } = {},
        ) {
            if (String(source ?? '').trim() !== 'swipe') {
                return;
            }

            this.clearSwipeRevealWatchdog();
            const normalizedStartedAtMs = Number.isFinite(Number(startedAtMs))
                ? Math.max(0, Math.trunc(Number(startedAtMs)))
                : Date.now();
            const normalizedDelayMs = Math.max(
                200,
                Math.trunc(Number(delayMs) || swipeRevealWatchdogDelayMs),
            );
            this.traceReaderReveal('schedule-swipe-reveal-watchdog', {
                delayMs: normalizedDelayMs,
                startedAtMs: normalizedStartedAtMs,
            });

            this._swipeRevealWatchdogTimer = window.setTimeout(() => {
                this._swipeRevealWatchdogTimer = null;
                const hasRenderablePage = this.hasRenderablePage();

                if (!hasRenderablePage) {
                    return;
                }

                if (this.hasBlockingModalLifecycleState()) {
                    return;
                }

                const blockedByNavigationOrLayout =
                    this.isLoadingPage ||
                    this._layoutActivePromise !== null ||
                    this._pendingNavigationRequest !== null ||
                    this._navigationRevealLocked;

                if (blockedByNavigationOrLayout) {
                    const blockedForMs = Date.now() - normalizedStartedAtMs;
                    this.traceReaderReveal('swipe-watchdog-blocked', {
                        blockedForMs,
                        isLoadingPage: this.isLoadingPage,
                        hasLayoutPromise: this._layoutActivePromise !== null,
                        hasPendingNavigation: this._pendingNavigationRequest !== null,
                        navigationRevealLocked: this._navigationRevealLocked,
                    });

                    if (blockedForMs < revealBlockedFailOpenDelayMs * 2) {
                        this.scheduleSwipeRevealWatchdog('swipe', {
                            delayMs: Math.min(420, Math.max(220, normalizedDelayMs)),
                            startedAtMs: normalizedStartedAtMs,
                        });

                        return;
                    }

                    this.clearStaleRevealGuards();

                    if (this.isLoadingPage) {
                        this.abortActivePageLoad();
                        this._activePageAbortController = null;
                        this.isLoadingPage = false;
                    }

                    if (this.hasRenderablePage()) {
                        this.forceRevealCurrentPage('swipe-watchdog-blocked-fail-open');
                    }

                    if (
                        this._pendingNavigationRequest !== null &&
                        !this._navigationRevealLocked &&
                        !this.isLoadingPage
                    ) {
                        void this.commitPendingNavigation();
                    }

                    return;
                }

                if (this.isCurrentPageVisiblyReady()) {
                    this.clearStaleRevealGuards();
                    this.forceRevealCurrentPage('swipe-watchdog-already-visible');

                    return;
                }

                if (!this.isFittingPage) {
                    return;
                }

                this.traceReaderReveal('swipe-watchdog-layout-recovery');
                this.clearStaleRevealGuards();
                this.clearLayoutTimers();
                this.isFittingPage = true;
                this._bypassNextFitCache = true;
                void this.layoutPageGuaranteed({
                    revealDelayMs: 120,
                    maxAttempts: 4,
                    useIdleFit: false,
                }).finally(() => {
                    if (
                        !this.isLoadingPage &&
                        this._pendingNavigationRequest === null &&
                        !this._navigationRevealLocked &&
                        this.openModalCount() <= 0 &&
                        !this._isModalLifecycleSettling
                    ) {
                        this.forceRevealCurrentPage('swipe-watchdog-fail-open');
                    }
                });
            }, normalizedDelayMs);
        },

        beginLayoutCycle() {
            this._layoutToken += 1;
            this.isFittingPage = true;
            this._revealBlockedSinceAt = 0;
            this._revealBlockedLayoutToken = 0;

            return this._layoutToken;
        },

        hasRenderablePage() {
            return this.ready && this.mushafLines.length > 0;
        },

        isCurrentPageVisiblyReady() {
            if (!this.hasRenderablePage()) {
                return false;
            }

            const contentElement = this.$refs.pageContent;

            if (!(contentElement instanceof HTMLElement)) {
                return false;
            }

            const styles = window.getComputedStyle(contentElement);
            const opacity = Number.parseFloat(styles.opacity || '0');
            const visibleLineCount = Array.from(
                contentElement.querySelectorAll('[data-quran-line-text]'),
            ).filter(
                (lineElement) =>
                    String(lineElement.textContent ?? '')
                        .replace(/\s+/g, '')
                        .trim().length > 0,
            ).length;

            return (
                this.pageFitState() === 'ready' &&
                styles.visibility !== 'hidden' &&
                opacity > 0.35 &&
                visibleLineCount > 0
            );
        },

        async waitForStablePageFrame({
            maxFrames = 18,
            requiredStableFrames = 3,
            tolerancePx = 0.75,
        } = {}) {
            const frameElement = this.$refs.pageFrame;

            if (!(frameElement instanceof Element)) {
                await nextAnimationFrame();

                return;
            }

            let previousWidth = 0;
            let previousHeight = 0;
            let stableFrameCount = 0;
            const normalizedMaxFrames = Math.max(4, Math.trunc(Number(maxFrames) || 18));
            const normalizedRequiredStableFrames = Math.max(
                2,
                Math.trunc(Number(requiredStableFrames) || 3),
            );
            const normalizedTolerancePx = Math.max(0.25, Number(tolerancePx) || 0.75);

            for (let frame = 0; frame < normalizedMaxFrames; frame += 1) {
                await nextAnimationFrame();

                const frameRect = frameElement.getBoundingClientRect();
                const frameParentRect =
                    frameElement.parentElement?.getBoundingClientRect?.() ?? null;
                const width = Math.max(
                    0,
                    Number(
                        frameParentRect?.width ??
                            frameRect?.width ??
                            frameElement.parentElement?.clientWidth ??
                            frameElement.clientWidth ??
                            0,
                    ),
                );
                const height = Math.max(
                    0,
                    Number(frameRect?.height ?? frameElement.clientHeight ?? 0),
                );

                if (width <= 1 || height <= 1) {
                    stableFrameCount = 0;
                    previousWidth = width;
                    previousHeight = height;

                    continue;
                }

                const widthDelta = Math.abs(width - previousWidth);
                const heightDelta = Math.abs(height - previousHeight);

                if (widthDelta <= normalizedTolerancePx && heightDelta <= normalizedTolerancePx) {
                    stableFrameCount += 1;
                } else {
                    stableFrameCount = 0;
                }

                previousWidth = width;
                previousHeight = height;

                if (stableFrameCount >= normalizedRequiredStableFrames) {
                    return;
                }
            }
        },

        async runStartupFinalFitPass() {
            if (!this.hasRenderablePage()) {
                return;
            }

            await this.nextTickAsync();
            await this.waitForPageFontReady();
            await this.waitForStablePageFrame({
                maxFrames: 16,
                requiredStableFrames: 3,
                tolerancePx: 0.8,
            });

            this._bypassNextFitCache = true;
            await this.layoutPageGuaranteed({
                revealDelayMs: 220,
                maxAttempts: 4,
                useIdleFit: false,
            });

            await this.waitForStablePageFrame({
                maxFrames: 12,
                requiredStableFrames: 2,
                tolerancePx: 0.6,
            });

            if (
                !this.isCurrentPageVisiblyReady() ||
                this._lastFittedPageNumber !== this.pageNumber
            ) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 180,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            }

            if (
                this.isFittingPage &&
                this.hasRenderablePage() &&
                !this.isLoadingPage &&
                this._pendingNavigationRequest === null &&
                !this._navigationRevealLocked
            ) {
                this.forceRevealCurrentPage('startup-final-fit-pass-fail-open');
            }
        },

        holdPageHiddenForModalLifecycle() {
            if (!this.hasRenderablePage()) {
                return;
            }

            this._isModalLifecycleSettling = true;
            this.clearLayoutTimers();
            this.beginLayoutCycle();
        },

        scheduleLayoutAfterModalLifecycle(delayMs = 220) {
            if (!this.hasRenderablePage()) {
                return;
            }

            if (this._modalLayoutResumeTimer !== null) {
                clearTimeout(this._modalLayoutResumeTimer);
                this._modalLayoutResumeTimer = null;
            }

            this._modalLayoutResumeTimer = window.setTimeout(
                () => {
                    this._modalLayoutResumeTimer = null;
                    this._isModalLifecycleSettling = false;
                    this.clearLayoutTimers();
                    this.isFittingPage = true;
                    this._bypassNextFitCache = true;

                    void this.layoutPageGuaranteed({
                        revealDelayMs: 180,
                        maxAttempts: 5,
                        useIdleFit: false,
                    });
                },
                Math.max(0, Math.trunc(Number(delayMs) || 220)),
            );
        },

        clearPendingPostModalTargetFit() {
            if (this._postModalTargetFitTimer !== null) {
                clearTimeout(this._postModalTargetFitTimer);
                this._postModalTargetFitTimer = null;
            }

            this._postModalTargetFitPage = 0;
            this._postModalTargetFitRetries = 0;
        },

        queuePendingPostModalTargetFit(pageNumber) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);

            if (normalizedPageNumber <= 0) {
                this.clearPendingPostModalTargetFit();

                return;
            }

            this._postModalTargetFitPage = normalizedPageNumber;
            this._postModalTargetFitRetries = 0;
        },

        async fitSpecificPageAfterModalClose(
            pageNumber,
            { revealDelayMs = 240, maxAttempts = 6 } = {},
        ) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);

            if (
                normalizedPageNumber <= 0 ||
                this.pageNumber !== normalizedPageNumber ||
                !this.hasRenderablePage() ||
                this.isLoadingPage ||
                this._navigationRevealLocked ||
                this._isModalLifecycleSettling ||
                this._activeModalIds.size > 0 ||
                this.openModalCount() > 0
            ) {
                return false;
            }

            if (this.isCurrentPageVisiblyReady()) {
                return true;
            }

            this.pauseIdleWarmup(960);
            this._bypassNextFitCache = true;
            this.isFittingPage = true;
            this.clearLayoutTimers();

            await this.nextTickAsync();
            await this.layoutPageGuaranteed({
                revealDelayMs,
                maxAttempts,
            });

            if (this._lastFittedPageNumber !== normalizedPageNumber) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 180,
                    maxAttempts: Math.max(4, maxAttempts - 1),
                });
            }

            return this._lastFittedPageNumber === normalizedPageNumber;
        },

        schedulePendingModalCloseFit(
            pageNumber,
            { retries = 36, delayMs = 90, revealDelayMs = 240, maxAttempts = 6 } = {},
        ) {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);

            if (normalizedPageNumber <= 0) {
                this.clearPendingPostModalTargetFit();

                return;
            }

            if (this._postModalTargetFitPage !== normalizedPageNumber) {
                this._postModalTargetFitRetries = Math.max(0, Math.trunc(Number(retries) || 36));
            } else {
                this._postModalTargetFitRetries = Math.max(
                    this._postModalTargetFitRetries,
                    Math.max(0, Math.trunc(Number(retries) || 36)),
                );
            }

            this._postModalTargetFitPage = normalizedPageNumber;

            if (this._postModalTargetFitTimer !== null) {
                clearTimeout(this._postModalTargetFitTimer);
                this._postModalTargetFitTimer = null;
            }

            const attemptFit = () => {
                if (this._postModalTargetFitPage !== normalizedPageNumber) {
                    return;
                }

                const canFitNow =
                    this.pageNumber === normalizedPageNumber &&
                    this.hasRenderablePage() &&
                    !this.isLoadingPage &&
                    !this._navigationRevealLocked &&
                    !this._isModalLifecycleSettling &&
                    this._activeModalIds.size === 0 &&
                    this.openModalCount() <= 0 &&
                    !(
                        this._layoutActivePromise !== null ||
                        this._revealTimer !== null ||
                        this.isFittingPage ||
                        (this._lastPageRevealAt > 0 &&
                            Date.now() - this._lastPageRevealAt < postModalFitRevealSettleDelayMs)
                    );

                if (canFitNow) {
                    if (this.isCurrentPageVisiblyReady()) {
                        this.clearPendingPostModalTargetFit();

                        return;
                    }

                    void this.fitSpecificPageAfterModalClose(normalizedPageNumber, {
                        revealDelayMs,
                        maxAttempts,
                    }).finally(() => {
                        if (this._postModalTargetFitPage === normalizedPageNumber) {
                            this.clearPendingPostModalTargetFit();
                        }
                    });

                    return;
                }

                if (this._postModalTargetFitRetries <= 0) {
                    this.clearPendingPostModalTargetFit();

                    return;
                }

                this._postModalTargetFitRetries -= 1;
                this._postModalTargetFitTimer = window.setTimeout(
                    () => {
                        this._postModalTargetFitTimer = null;
                        attemptFit();
                    },
                    Math.max(36, Math.trunc(Number(delayMs) || 90)),
                );
            };

            attemptFit();
        },

        shouldDeferPostModalTargetFit(pageNumber = this.pageNumber, source = 'generic') {
            const normalizedPageNumber = clampPage(Number(pageNumber ?? 0), this.maxPage);
            const normalizedSource = String(source ?? 'generic').trim();

            if (
                normalizedPageNumber <= 0 ||
                this._postModalTargetFitPage !== normalizedPageNumber
            ) {
                return false;
            }

            return normalizedSource === 'surah-directory' || normalizedSource === 'search-result';
        },

        resumeLayoutWhenNoOpenModals(attempt = 0) {
            if (!this.hasRenderablePage()) {
                this._isModalLifecycleSettling = false;

                return;
            }

            const normalizedAttempt = Math.max(0, Math.trunc(Number(attempt) || 0));
            const remainingModalCount = this.openModalCount();

            if (remainingModalCount <= 0) {
                this.recoverStaleModalLifecycleState();
                this.scheduleLayoutAfterModalLifecycle(220);

                return;
            }

            this._isModalLifecycleSettling = true;

            if (normalizedAttempt >= 24) {
                this._activeModalIds.clear();
                this._isModalLifecycleSettling = false;
                this.scheduleLayout({ revealDelayMs: 240 });

                return;
            }

            window.setTimeout(() => {
                this.resumeLayoutWhenNoOpenModals(normalizedAttempt + 1);
            }, 40);
        },

        trackModalLifecycle(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const openModalCount = this.openModalCount();
            const hasTrackedModalId = modalId !== '' && this._activeModalIds.has(modalId);
            const hasTrackedModalState =
                this._activeModalIds.size > 0 || this._isModalLifecycleSettling;
            const isLateCloseLikeEvent = kind === 'closing' || kind === 'closed';
            const now = Date.now();

            this.pruneModalLifecycleSuppression(now);

            const isSuppressedCloseEvent =
                isLateCloseLikeEvent &&
                now < this._suppressModalLifecycleEffectsUntil &&
                (modalId !== ''
                    ? this._suppressModalLifecycleModalIds.has(modalId)
                    : this._suppressModalLifecycleModalIds.size > 0);

            if (isSuppressedCloseEvent) {
                if (modalId !== '') {
                    this._activeModalIds.delete(modalId);

                    if (kind === 'closed') {
                        this._suppressModalLifecycleModalIds.delete(modalId);
                    }
                }

                if (openModalCount <= 0) {
                    this._isModalLifecycleSettling = false;
                }

                if (
                    this._postModalTargetFitPage === this.pageNumber &&
                    this.isCurrentPageVisiblyReady()
                ) {
                    this.clearPendingPostModalTargetFit();
                }

                return;
            }

            if (
                isLateCloseLikeEvent &&
                openModalCount <= 0 &&
                this.hasRenderablePage() &&
                this.isCurrentPageVisiblyReady()
            ) {
                if (modalId !== '') {
                    this._activeModalIds.delete(modalId);
                }

                this._isModalLifecycleSettling = false;

                if (this._postModalTargetFitPage === this.pageNumber) {
                    this.clearPendingPostModalTargetFit();
                }

                return;
            }

            if (
                (kind === 'closing' || kind === 'closed') &&
                modalId === '' &&
                openModalCount <= 0 &&
                this._activeModalIds.size === 0
            ) {
                if (this._isModalLifecycleSettling) {
                    this._isModalLifecycleSettling = false;
                    this.scheduleLayoutAfterModalLifecycle(kind === 'closed' ? 120 : 180);
                }

                return;
            }

            if (kind === 'closing') {
                if (modalId === '' && openModalCount <= 0 && !hasTrackedModalState) {
                    return;
                }

                if (modalId !== '' && !hasTrackedModalId && openModalCount <= 0) {
                    return;
                }
            }

            if (kind === 'closed') {
                if (modalId === '' && openModalCount <= 0 && !hasTrackedModalState) {
                    return;
                }

                if (modalId !== '' && !hasTrackedModalId && openModalCount <= 0) {
                    return;
                }
            }

            this._lastModalLifecycleEventAt = Date.now();

            if (kind === 'opened') {
                if (modalId !== '') {
                    this._activeModalIds.add(modalId);
                }

                this._bypassNextFitCache = true;
                this.holdPageHiddenForModalLifecycle();

                return;
            }

            if (kind === 'closing') {
                if (modalId === '' || this._activeModalIds.has(modalId) || openModalCount > 0) {
                    const navigatedAway = this._lastFittedPageNumber !== this.pageNumber;

                    if (navigatedAway) {
                        this._bypassNextFitCache = true;
                        this.holdPageHiddenForModalLifecycle();
                    }

                    this.resumeLayoutWhenNoOpenModals();
                }

                return;
            }

            if (kind === 'closed') {
                if (modalId !== '') {
                    this._activeModalIds.delete(modalId);
                }

                const navigatedAway = this._lastFittedPageNumber !== this.pageNumber;

                if (navigatedAway) {
                    this._bypassNextFitCache = true;
                    this.holdPageHiddenForModalLifecycle();
                }

                window.setTimeout(() => {
                    this.resumeLayoutWhenNoOpenModals();
                }, 24);
            }
        },

        queuePageReveal(layoutToken, delayMs = 180) {
            this._revealTimer = window.setTimeout(() => {
                this._revealTimer = null;

                if (layoutToken !== this._layoutToken) {
                    if (this.hasRenderablePage()) {
                        this.queuePageReveal(this._layoutToken, 120);
                    }

                    return;
                }

                this.clearStaleRevealGuards({ allowUnlock: false });
                this.traceReaderReveal('queue-page-reveal-tick', {
                    layoutToken,
                });

                if (this._startupCalibrationPending) {
                    this.isFittingPage = true;
                    this.queuePageReveal(layoutToken, 90);

                    return;
                }

                if (this.hasBlockingModalLifecycleState({ recoverStaleState: true })) {
                    this.traceReaderReveal('queue-page-reveal-blocked', {
                        reason: 'modal-lifecycle',
                    });
                    this.isFittingPage = true;
                    this.queuePageReveal(layoutToken, 120);

                    return;
                }

                if (
                    this._pendingNavigationRequest !== null &&
                    !this._navigationRevealLocked &&
                    !this.isLoadingPage
                ) {
                    const stalePendingTargetPage = clampPage(
                        Number(this._pendingNavigationRequest?.targetPage ?? 0),
                        this.maxPage,
                    );

                    if (stalePendingTargetPage === this.pageNumber) {
                        this._pendingNavigationRequest = null;
                    } else {
                        this.traceReaderReveal('queue-page-reveal-commit-pending-navigation', {
                            stalePendingTargetPage,
                        });
                        void this.commitPendingNavigation();
                    }
                }

                if (
                    this._navigationRevealLocked ||
                    this._pendingNavigationRequest !== null ||
                    this.isLoadingPage
                ) {
                    if (this._revealBlockedLayoutToken !== layoutToken) {
                        this._revealBlockedLayoutToken = layoutToken;
                        this._revealBlockedSinceAt = Date.now();
                    }

                    const revealBlockedForMs = Date.now() - this._revealBlockedSinceAt;
                    const pendingTargetPage = clampPage(
                        Number(this._pendingNavigationRequest?.targetPage ?? 0),
                        this.maxPage,
                    );
                    const hasOpenModal = this.openModalCount() > 0;
                    const mayFailOpenReveal =
                        this.hasRenderablePage() &&
                        !hasOpenModal &&
                        !this._isModalLifecycleSettling &&
                        this._activeModalIds.size === 0 &&
                        (pendingTargetPage <= 0 || pendingTargetPage === this.pageNumber);

                    if (
                        revealBlockedForMs >= revealBlockedFailOpenDelayMs &&
                        this.hasRenderablePage()
                    ) {
                        const clearedStaleGuards = this.clearStaleRevealGuards();
                        this.traceReaderReveal('queue-page-reveal-stale-guards', {
                            revealBlockedForMs,
                            clearedStaleGuards,
                            pendingTargetPage,
                            mayFailOpenReveal,
                        });

                        if (
                            mayFailOpenReveal &&
                            this.forceRevealCurrentPage('queue-page-reveal-stale-guards')
                        ) {
                            this.clearSwipeRevealWatchdog();
                            return;
                        }
                    }

                    this.traceReaderReveal('queue-page-reveal-blocked', {
                        reason: this._navigationRevealLocked
                            ? 'navigation-lock'
                            : this._pendingNavigationRequest !== null
                              ? 'pending-navigation'
                              : 'loading-page',
                        revealBlockedForMs,
                    });
                    this.isFittingPage = true;
                    this.queuePageReveal(layoutToken, 90);

                    return;
                }

                this._revealBlockedSinceAt = 0;
                this._revealBlockedLayoutToken = 0;

                this.syncPageInputToCurrentPage();
                this.isFittingPage = false;
                this._lastPageRevealAt = Date.now();
                this.clearSwipeRevealWatchdog();
                this.traceReaderReveal('queue-page-reveal-ready');
            }, delayMs);
        },

        handleViewportChange() {
            this.syncFitCacheBreakpoint();
            this.syncCalibrationHudPosition();
            this.scheduleReaderPanelLayoutRefresh();

            if (this._viewportChangeDebounceTimer !== null) {
                clearTimeout(this._viewportChangeDebounceTimer);
            }

            this._viewportChangeDebounceTimer = window.setTimeout(() => {
                this._viewportChangeDebounceTimer = null;
                this.scheduleLayout({ revealDelayMs: 150 });
            }, 90);
        },

        scheduleLayout({ revealDelayMs = 180, maxAttempts = 4 } = {}) {
            if (
                this.wirdModeActive &&
                this.isWirdEntryLayoutSchedulingSuppressed() &&
                this.hasRenderablePage() &&
                !this.isFittingPage
            ) {
                return;
            }

            const hadTrackedModalLifecycleState =
                this._isModalLifecycleSettling || this._activeModalIds.size > 0;

            if (this.hasBlockingModalLifecycleState({ recoverStaleState: true })) {
                this.holdPageHiddenForModalLifecycle();

                return;
            }

            if (
                hadTrackedModalLifecycleState &&
                !this._isModalLifecycleSettling &&
                this._activeModalIds.size === 0
            ) {
                this._bypassNextFitCache = true;
            }

            const isWaitingOnlyForReveal =
                this._revealTimer !== null &&
                this.isFittingPage &&
                !this.isLoadingPage &&
                !this._layoutActivePromise &&
                this._pendingNavigationRequest === null &&
                !this._navigationRevealLocked &&
                !this._isModalLifecycleSettling &&
                this._activeModalIds.size === 0 &&
                Date.now() - this._lastModalLifecycleEventAt > 420 &&
                this.hasRenderablePage();

            if (isWaitingOnlyForReveal) {
                return;
            }

            const layoutRequest = this.normalizeLayoutRequest({
                revealDelayMs,
                maxAttempts,
            });

            if (this.isLoadingPage || this._layoutActivePromise) {
                this.queueLayoutRequest(layoutRequest);

                return;
            }

            this.clearLayoutTimers();

            this._layoutRaf = requestAnimationFrame(() => {
                this._layoutRaf = null;
                void this.layoutPageGuaranteed(layoutRequest);
            });
        },

        async waitForStableRenderedText(maxFrames = 8) {
            const contentElement = this.$refs.pageContent;

            if (!(contentElement instanceof Element)) {
                await nextAnimationFrame();

                return;
            }

            let previousWidth = 0;
            let previousHeight = 0;
            let stableFrames = 0;
            const frames = Math.max(2, Math.trunc(Number(maxFrames) || 8));

            for (let frame = 0; frame < frames; frame += 1) {
                await nextAnimationFrame();

                const { width, height } = this.measureRenderedBounds(contentElement);

                if (width <= 1 || height <= 1) {
                    stableFrames = 0;
                    previousWidth = width;
                    previousHeight = height;

                    continue;
                }

                const widthDelta = Math.abs(width - previousWidth);
                const heightDelta = Math.abs(height - previousHeight);

                if (widthDelta < 0.6 && heightDelta < 0.6) {
                    stableFrames += 1;
                } else {
                    stableFrames = 0;
                }

                previousWidth = width;
                previousHeight = height;

                if (stableFrames >= 2) {
                    return;
                }
            }
        },

        async layoutPage({ revealDelayMs = 180, useIdleFit = true } = {}) {
            const layoutToken = this.beginLayoutCycle();

            await this.nextTickAsync();
            await this.waitForPageFontReady();
            await nextAnimationFrame();
            await this.waitForStableRenderedText(10);

            try {
                this.rebalanceRectangularAyahLineWordSpacing();
            } catch (_) {
                this.lineWordGapAdjustments = {};
            }

            await this.nextTickAsync();
            await nextAnimationFrame();

            if (useIdleFit) {
                await this.runFitPageToViewportLazily();
            } else {
                this.fitPageToViewport();
            }
            this.queuePageReveal(layoutToken, revealDelayMs);
        },

        async runFitPageToViewportLazily() {
            if (typeof window.requestIdleCallback === 'function') {
                await new Promise((resolve) => {
                    window.requestIdleCallback(
                        () => {
                            this.fitPageToViewport();
                            resolve();
                        },
                        {
                            timeout: 80,
                        },
                    );
                });

                return;
            }

            await wait(0);
            this.fitPageToViewport();
        },

        async layoutPageGuaranteed({
            revealDelayMs = 180,
            maxAttempts = 4,
            useIdleFit = true,
        } = {}) {
            const layoutRequest = this.normalizeLayoutRequest({
                revealDelayMs,
                maxAttempts,
            });

            if (this._layoutActivePromise) {
                this.queueLayoutRequest(layoutRequest);
                await this._layoutActivePromise;

                return;
            }

            const runLayoutPromise = (async () => {
                const shouldUseIdleFit = Boolean(useIdleFit || this.isCalibrating);

                for (let attempt = 0; attempt < layoutRequest.maxAttempts; attempt += 1) {
                    const fitRunsBeforeAttempt = this._fitRunCounter;
                    await this.layoutPage({
                        revealDelayMs: attempt === 0 ? layoutRequest.revealDelayMs : 160,
                        useIdleFit: shouldUseIdleFit,
                    });

                    if (
                        this._fitRunCounter > fitRunsBeforeAttempt &&
                        this._lastFittedPageNumber === this.pageNumber
                    ) {
                        return;
                    }

                    await wait(55);
                }
            })();

            this._layoutActivePromise = runLayoutPromise;

            try {
                await runLayoutPromise;
            } finally {
                if (this._layoutActivePromise === runLayoutPromise) {
                    this._layoutActivePromise = null;
                }

                this.flushQueuedLayoutRequest();
            }
        },

        measureRenderedBounds(contentElement, { useRobustWidth = true } = {}) {
            const lineTargets = Array.from(
                contentElement.querySelectorAll('[data-quran-line-text]'),
            );
            const ayahLineTargets = Array.from(
                contentElement.querySelectorAll(
                    "[data-quran-line][data-quran-line-type='ayah'] [data-quran-line-text]",
                ),
            );
            const boundsTargets = lineTargets.length > 0 ? lineTargets : [contentElement];
            const widthTargets =
                ayahLineTargets.length >= 3
                    ? ayahLineTargets
                    : lineTargets.length > 0
                      ? lineTargets
                      : [contentElement];
            const widths = [];

            let minLeft = Number.POSITIVE_INFINITY;
            let minTop = Number.POSITIVE_INFINITY;
            let maxRight = Number.NEGATIVE_INFINITY;
            let maxBottom = Number.NEGATIVE_INFINITY;

            boundsTargets.forEach((target) => {
                const rect = target.getBoundingClientRect();

                if (rect.width <= 0 || rect.height <= 0) {
                    return;
                }

                minLeft = Math.min(minLeft, rect.left);
                minTop = Math.min(minTop, rect.top);
                maxRight = Math.max(maxRight, rect.right);
                maxBottom = Math.max(maxBottom, rect.bottom);
            });

            widthTargets.forEach((target) => {
                const rect = target.getBoundingClientRect();

                if (rect.width <= 0 || rect.height <= 0) {
                    return;
                }

                widths.push(rect.width);
            });

            if (!Number.isFinite(minLeft) || !Number.isFinite(maxRight)) {
                const fallbackRect = contentElement.getBoundingClientRect();

                return {
                    width: Math.max(1, Number(fallbackRect.width ?? 1)),
                    height: Math.max(1, Number(fallbackRect.height ?? 1)),
                    strictWidth: Math.max(1, Number(fallbackRect.width ?? 1)),
                    robustWidth: Math.max(1, Number(fallbackRect.width ?? 1)),
                };
            }

            const strictWidth = Math.max(1, maxRight - minLeft);
            let robustWidth = strictWidth;
            const shouldUseRobustWidth = Boolean(useRobustWidth) && widths.length >= 7;

            if (shouldUseRobustWidth) {
                const sortedWidths = widths.slice().sort((first, second) => first - second);
                const medianIndex = Math.floor((sortedWidths.length - 1) * 0.5);
                const quantileIndex = Math.floor(
                    (sortedWidths.length - 1) * fitRobustWidthQuantile,
                );
                const medianWidth = sortedWidths[medianIndex] ?? strictWidth;
                const quantileWidth = sortedWidths[quantileIndex] ?? strictWidth;
                const candidateRobustWidth = Math.max(quantileWidth, medianWidth * 1.02);

                if (strictWidth > candidateRobustWidth * fitRobustWidthOutlierThreshold) {
                    robustWidth = candidateRobustWidth;
                }
            }

            return {
                width: Math.max(1, robustWidth),
                height: Math.max(1, maxBottom - minTop),
                strictWidth,
                robustWidth: Math.max(1, robustWidth),
            };
        },

        resetFitLayoutVariables(rootElement) {
            this.applyFitLayoutVariables(rootElement, {
                pageTypeScale: 1,
                pageLeadingMultiplier: 1,
                pageGapMultiplier: 1,
                pageSurahHeaderScale: 1,
                basmallahBottomGapScale: defaultBasmallahBottomGapScale,
            });
        },

        resetCurrentPageFitStyles() {
            const rootElement = this.$el.firstElementChild;

            if (!(rootElement instanceof HTMLElement)) {
                this.pageScale = 1;

                return;
            }

            this.resetFitLayoutVariables(rootElement);
            this.pageScale = 1;
            this.setCurrentPageScale(1, { forFitting: true });
        },

        applyFitLayoutVariables(rootElement, layout = {}) {
            if (!(rootElement instanceof HTMLElement)) {
                return;
            }

            rootElement.style.setProperty(
                '--quran-page-type-scale',
                String(layout.pageTypeScale ?? 1),
            );
            rootElement.style.setProperty(
                '--quran-page-leading-multiplier',
                String(layout.pageLeadingMultiplier ?? 1),
            );
            rootElement.style.setProperty(
                '--quran-page-gap-multiplier',
                String(layout.pageGapMultiplier ?? 1),
            );
            rootElement.style.setProperty(
                '--quran-page-surah-header-scale',
                String(layout.pageSurahHeaderScale ?? 1),
            );
            rootElement.style.setProperty(
                '--quran-basmallah-bottom-gap-scale',
                String(layout.basmallahBottomGapScale ?? defaultBasmallahBottomGapScale),
            );
        },

        fitLayoutFromCompressionLevel(level) {
            const normalizedLevel = Math.max(0, Math.min(1, Number(level) || 0));
            const profile = this.resolveFitProfile();
            const normalizedBaseLeadingMultiplier = Math.max(
                0.2,
                Number(profile.baseLeadingMultiplier ?? 1),
            );
            const normalizedBaseGapMultiplier = Math.max(0, Number(profile.baseGapMultiplier ?? 1));
            const pageTypeScaleRaw =
                Number(profile.layoutTypeScaleBase ?? 1) +
                normalizedLevel * Number(profile.layoutTypeScaleGain ?? 0);
            const pageLeadingRaw =
                Number(profile.layoutLeadingBase ?? 1) -
                normalizedLevel * Number(profile.layoutLeadingDrop ?? 0);
            const pageGapRaw =
                Number(profile.layoutGapBase ?? 1) -
                normalizedLevel * Number(profile.layoutGapDrop ?? 0);
            const pageSurahHeaderRaw =
                Number(profile.layoutSurahHeaderBase ?? 1) -
                normalizedLevel * Number(profile.layoutSurahHeaderDrop ?? 0);
            const basmallahBottomGapRaw =
                Number(profile.layoutBasmallahBase ?? defaultBasmallahBottomGapScale) -
                normalizedLevel * Number(profile.layoutBasmallahDrop ?? 0);

            return {
                pageTypeScale: Math.min(
                    profile.compressionTypeScaleCeiling,
                    Math.max(0.2, pageTypeScaleRaw),
                ),
                pageLeadingMultiplier: Math.max(
                    0.2,
                    Math.max(profile.compressionLeadingFloor, pageLeadingRaw) *
                        normalizedBaseLeadingMultiplier,
                ),
                pageGapMultiplier: Math.max(
                    0,
                    Math.max(profile.compressionGapFloor, pageGapRaw) * normalizedBaseGapMultiplier,
                ),
                pageSurahHeaderScale: Math.max(
                    profile.compressionSurahHeaderFloor,
                    pageSurahHeaderRaw,
                ),
                basmallahBottomGapScale: basmallahBottomGapRaw,
            };
        },

        fitScore({ fillWidth, fillHeight, compressionLevel }) {
            const profile = this.resolveFitProfile();
            const normalizedFillWidth = Math.max(0, Math.min(1, Number(fillWidth) || 0));
            const normalizedFillHeight = Math.max(0, Math.min(1, Number(fillHeight) || 0));
            const normalizedCompression = Math.max(0, Math.min(1, Number(compressionLevel) || 0));
            const minimumFill = Math.min(normalizedFillWidth, normalizedFillHeight);
            const areaFill = normalizedFillWidth * normalizedFillHeight;
            const balancePenalty = Math.abs(normalizedFillWidth - normalizedFillHeight);
            const widthDeficit = Math.max(0, profile.targetWidthRatio - normalizedFillWidth);
            const heightDeficit = Math.max(0, profile.targetHeightRatio - normalizedFillHeight);

            return (
                minimumFill * 0.48 +
                Math.sqrt(Math.max(0, areaFill)) * 0.16 -
                balancePenalty * 0.03 -
                widthDeficit * profile.widthDeficitWeight -
                heightDeficit * profile.heightDeficitWeight -
                normalizedCompression * profile.compressionPenaltyWeight
            );
        },

        resolveFitProfile() {
            const lines = Array.isArray(this.mushafLines) ? this.mushafLines : [];
            const pageNumber = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const breakpointName = String(this.resolveCurrentBreakpointName() ?? '').trim();
            const isTabletBreakpoint = ['sm', 'md', 'lg'].includes(breakpointName);
            const ayahLines = lines.filter((line) => String(line?.line_type ?? '') === 'ayah');
            const ayahLineCount = ayahLines.length;
            const centeredAyahLineCount = ayahLines.filter((line) =>
                Boolean(line?.is_centered),
            ).length;
            const surahHeaderCount = lines.filter(
                (line) => String(line?.line_type ?? '') === 'surah_name',
            ).length;
            const basmallahCount = lines.filter(
                (line) => String(line?.line_type ?? '') === 'basmallah',
            ).length;
            const centeredAyahRatio = ayahLineCount > 0 ? centeredAyahLineCount / ayahLineCount : 0;
            const hasOpeningSpreadSignature =
                ayahLineCount > 0 &&
                ayahLineCount <= 9 &&
                lines.length <= 12 &&
                surahHeaderCount <= 1 &&
                basmallahCount <= 1 &&
                centeredAyahRatio >= 0.45;
            const isOpeningSpread = pageNumber <= 2 && hasOpeningSpreadSignature;
            const isLineHeavyCenteredPage =
                !isOpeningSpread &&
                surahHeaderCount === 0 &&
                basmallahCount === 0 &&
                ayahLineCount >= 14 &&
                centeredAyahRatio >= 0.85;
            const isMultiSurahSegmentedPage =
                !isOpeningSpread && surahHeaderCount >= 2 && basmallahCount >= 2;
            const isSingleHeaderLongContentPage =
                !isOpeningSpread &&
                surahHeaderCount === 1 &&
                basmallahCount >= 1 &&
                ayahLineCount >= 10;

            if (isOpeningSpread) {
                const openingSpreadProfile = {
                    ...fitDefaultProfile,
                    compressionLeadingFloor: 0.92,
                    compressionGapFloor: 0.34,
                    compressionSurahHeaderFloor: 0.9,
                    compressionTypeScaleCeiling: 0.6,
                    layoutTypeScaleBase: 0.56,
                    layoutTypeScaleGain: 0.03,
                    layoutLeadingBase: 1,
                    layoutLeadingDrop: 0.04,
                    layoutGapBase: 0.5,
                    layoutGapDrop: 0.1,
                    layoutSurahHeaderBase: 1,
                    layoutSurahHeaderDrop: 0.03,
                    layoutBasmallahBase: -0.18,
                    layoutBasmallahDrop: 0.04,
                    baseLeadingMultiplier: 1,
                    baseGapMultiplier: 1,
                    minimumCompressionLevel: 0,
                    targetWidthRatio: 0.68,
                    targetHeightRatio: 0.76,
                    widthDeficitWeight: 0.34,
                    heightDeficitWeight: 0.14,
                    compressionPenaltyWeight: 0.01,
                    strictWidthOverflowTolerance: 1.03,
                    strictHeightOverflowTolerance: 1.0,
                    candidateSteps: 24,
                    maxScaleMultiplier: 0.56,
                };

                if (!isTabletBreakpoint) {
                    return openingSpreadProfile;
                }

                return {
                    ...openingSpreadProfile,
                    compressionTypeScaleCeiling: 0.53,
                    layoutTypeScaleBase: 0.5,
                    layoutTypeScaleGain: 0.02,
                    layoutLeadingBase: 1.08,
                    layoutLeadingDrop: 0.03,
                    layoutGapBase: 0.62,
                    layoutGapDrop: 0.08,
                    minimumCompressionLevel: 0,
                    targetWidthRatio: 0.62,
                    targetHeightRatio: 0.72,
                    maxScaleMultiplier: 0.44,
                };
            }

            if (isLineHeavyCenteredPage) {
                const lineHeavyProfile = {
                    ...fitDefaultProfile,
                    compressionLeadingFloor: 0.52,
                    compressionGapFloor: 0.1,
                    compressionSurahHeaderFloor: 0.58,
                    compressionTypeScaleCeiling: 1.92,
                    layoutTypeScaleBase: 1.774286,
                    layoutTypeScaleGain: 0.16,
                    layoutLeadingBase: 0.88,
                    layoutLeadingDrop: 0.22,
                    layoutGapBase: 0.428571,
                    layoutGapDrop: 0.26,
                    layoutSurahHeaderBase: 0.92,
                    layoutSurahHeaderDrop: 0.1,
                    layoutBasmallahBase: -0.27714285714285714,
                    layoutBasmallahDrop: 0.12,
                    baseLeadingMultiplier: 1,
                    baseGapMultiplier: 1,
                    minimumCompressionLevel: 0.48,
                    targetWidthRatio: 0.95,
                    targetHeightRatio: 0.9,
                    widthDeficitWeight: 0.86,
                    heightDeficitWeight: 0.05,
                    compressionPenaltyWeight: 0,
                    strictWidthOverflowTolerance: 1.04,
                    strictHeightOverflowTolerance: 1.0,
                    candidateSteps: 36,
                    maxScaleMultiplier: 1.08,
                };

                if (!isTabletBreakpoint) {
                    return lineHeavyProfile;
                }

                return {
                    ...lineHeavyProfile,
                    compressionTypeScaleCeiling: 1.56,
                    layoutTypeScaleBase: 1.42,
                    layoutTypeScaleGain: 0.09,
                    layoutLeadingBase: 1.02,
                    layoutLeadingDrop: 0.12,
                    layoutGapBase: 0.66,
                    layoutGapDrop: 0.18,
                    minimumCompressionLevel: 0.06,
                    targetWidthRatio: 0.88,
                    targetHeightRatio: 0.86,
                    widthDeficitWeight: 0.72,
                    heightDeficitWeight: 0.07,
                    compressionPenaltyWeight: 0,
                    maxScaleMultiplier: 0.9,
                };
            }

            if (isMultiSurahSegmentedPage) {
                const segmentedProfile = {
                    ...fitDefaultProfile,
                    compressionLeadingFloor: 0.66,
                    compressionGapFloor: 0.34,
                    compressionSurahHeaderFloor: 0.72,
                    compressionTypeScaleCeiling: 1.42,
                    layoutTypeScaleBase: 1.1207142857142858,
                    layoutTypeScaleGain: 0.14,
                    layoutLeadingBase: 0.8049999999999999,
                    layoutLeadingDrop: 0.1,
                    layoutGapBase: 0.5589285714285714,
                    layoutGapDrop: 0.18,
                    layoutSurahHeaderBase: 0.87,
                    layoutSurahHeaderDrop: 0.08,
                    layoutBasmallahBase: -0.33785714285714286,
                    layoutBasmallahDrop: 0.1,
                    minimumCompressionLevel: 0.18,
                    targetWidthRatio: 0.88,
                    targetHeightRatio: 0.94,
                    widthDeficitWeight: 0.42,
                    heightDeficitWeight: 0.14,
                    compressionPenaltyWeight: 0.008,
                    candidateSteps: 30,
                    maxScaleMultiplier: 1,
                };

                if (!isTabletBreakpoint) {
                    return segmentedProfile;
                }

                return {
                    ...segmentedProfile,
                    compressionTypeScaleCeiling: 1.12,
                    layoutTypeScaleBase: 0.98,
                    layoutTypeScaleGain: 0.08,
                    layoutLeadingBase: 0.96,
                    layoutLeadingDrop: 0.06,
                    layoutGapBase: 0.74,
                    layoutGapDrop: 0.1,
                    layoutSurahHeaderBase: 0.92,
                    layoutSurahHeaderDrop: 0.05,
                    layoutBasmallahBase: -0.24,
                    layoutBasmallahDrop: 0.07,
                    minimumCompressionLevel: 0,
                    targetWidthRatio: 0.84,
                    targetHeightRatio: 0.9,
                    widthDeficitWeight: 0.36,
                    heightDeficitWeight: 0.16,
                    compressionPenaltyWeight: 0.014,
                    maxScaleMultiplier: 0.84,
                };
            }

            if (isSingleHeaderLongContentPage && isTabletBreakpoint) {
                return {
                    ...fitDefaultProfile,
                    compressionLeadingFloor: 0.7,
                    compressionGapFloor: 0.34,
                    compressionSurahHeaderFloor: 0.78,
                    compressionTypeScaleCeiling: 1.12,
                    layoutTypeScaleBase: 0.98,
                    layoutTypeScaleGain: 0.08,
                    layoutLeadingBase: 0.97,
                    layoutLeadingDrop: 0.07,
                    layoutGapBase: 0.72,
                    layoutGapDrop: 0.11,
                    layoutSurahHeaderBase: 0.9,
                    layoutSurahHeaderDrop: 0.05,
                    layoutBasmallahBase: -0.24,
                    layoutBasmallahDrop: 0.07,
                    minimumCompressionLevel: 0.02,
                    targetWidthRatio: 0.84,
                    targetHeightRatio: 0.9,
                    widthDeficitWeight: 0.38,
                    heightDeficitWeight: 0.14,
                    compressionPenaltyWeight: 0.01,
                    candidateSteps: 30,
                    maxScaleMultiplier: 0.86,
                };
            }

            return { ...fitDefaultProfile };
        },

        fitSanityContextKey({
            pageNumber = this.pageNumber,
            availableWidth = 0,
            availableHeight = 0,
            minimumFillWidth = 0,
            minimumFillHeight = 0,
            strictWidthOverflowTolerance = 1.06,
            strictHeightOverflowTolerance = 1.01,
        } = {}) {
            const normalizedPageNumber = Math.max(
                1,
                Math.trunc(Number(pageNumber ?? this.pageNumber)),
            );
            const normalizedAvailableWidth = Math.max(1, Number(availableWidth) || 1);
            const normalizedAvailableHeight = Math.max(1, Number(availableHeight) || 1);
            const breakpointName = this.resolveCurrentBreakpointName();

            return [
                normalizedPageNumber,
                breakpointName || 'bp-unknown',
                this.viewportBucketValue(normalizedAvailableWidth),
                this.viewportBucketValue(normalizedAvailableHeight),
                Number(minimumFillWidth ?? 0).toFixed(3),
                Number(minimumFillHeight ?? 0).toFixed(3),
                Number(strictWidthOverflowTolerance ?? 1.06).toFixed(3),
                Number(strictHeightOverflowTolerance ?? 1.01).toFixed(3),
                this.useCenteredAyahLayout ? 'centered' : 'rect',
                this.mushafLines.length,
                String(this.qpcPageFontFamily ?? ''),
            ].join('|');
        },

        recordFitSanityRecoveryAttempt({
            contextKey = '',
            measuredWidth = 0,
            measuredHeight = 0,
            hasOverflow = false,
            hasSuspiciousUnderfill = false,
        } = {}) {
            const normalizedContextKey = String(contextKey ?? '').trim();
            const normalizedMeasuredWidth = Math.max(0, Number(measuredWidth) || 0);
            const normalizedMeasuredHeight = Math.max(0, Number(measuredHeight) || 0);
            const normalizedOutcome = `${hasOverflow ? 'overflow' : 'no-overflow'}|${
                hasSuspiciousUnderfill ? 'underfill' : 'no-underfill'
            }`;
            const now = Date.now();
            const sameContext =
                normalizedContextKey !== '' && this._fitSanityContextKey === normalizedContextKey;

            if (sameContext && this._fitSanityDisabledContextKey === normalizedContextKey) {
                return {
                    shouldSuppressRecovery: true,
                    reason: 'disabled-context',
                    attemptCount: this._fitSanityContextAttemptCount,
                };
            }

            if (sameContext && now < this._fitSanitySuppressedUntil) {
                return {
                    shouldSuppressRecovery: true,
                    reason: 'cooldown',
                    attemptCount: this._fitSanityContextAttemptCount,
                };
            }

            if (!sameContext) {
                this._fitSanityContextKey = normalizedContextKey;
                this._fitSanityContextAttemptCount = 1;
                this._fitSanityDisabledContextKey = '';
            } else {
                const widthDelta = Math.abs(
                    normalizedMeasuredWidth - this._fitSanityContextLastWidth,
                );
                const heightDelta = Math.abs(
                    normalizedMeasuredHeight - this._fitSanityContextLastHeight,
                );
                const hasStableMeasurements =
                    widthDelta < 6 &&
                    heightDelta < 6 &&
                    this._fitSanityContextOutcome === normalizedOutcome;

                this._fitSanityContextAttemptCount = hasStableMeasurements
                    ? this._fitSanityContextAttemptCount + 1
                    : 1;
            }

            this._fitSanityContextLastWidth = normalizedMeasuredWidth;
            this._fitSanityContextLastHeight = normalizedMeasuredHeight;
            this._fitSanityContextOutcome = normalizedOutcome;

            const attemptLimit = hasOverflow ? 4 : hasSuspiciousUnderfill ? 3 : 2;

            if (this._fitSanityContextAttemptCount > attemptLimit) {
                this._fitSanitySuppressedUntil = now + 2600;
                this._fitSanityDisabledContextKey = normalizedContextKey;

                return {
                    shouldSuppressRecovery: true,
                    reason: 'stable-loop',
                    attemptCount: this._fitSanityContextAttemptCount,
                };
            }

            return {
                shouldSuppressRecovery: false,
                reason: '',
                attemptCount: this._fitSanityContextAttemptCount,
            };
        },

        scheduleFitSanityCheck({
            cacheKey = '',
            pageNumber = this.pageNumber,
            availableWidth = 0,
            availableHeight = 0,
            strictWidthOverflowTolerance = 1.06,
            strictHeightOverflowTolerance = 1.01,
            minimumFillWidth = 0.32,
            minimumFillHeight = 0.22,
        } = {}) {
            const normalizedCacheKey = String(cacheKey ?? '').trim();
            const normalizedPageNumber = Math.max(
                1,
                Math.trunc(Number(pageNumber ?? this.pageNumber)),
            );
            const normalizedAvailableWidth = Math.max(1, Number(availableWidth) || 1);
            const normalizedAvailableHeight = Math.max(1, Number(availableHeight) || 1);
            const widthOverflowThreshold =
                normalizedAvailableWidth * Number(strictWidthOverflowTolerance ?? 1.06);
            const heightOverflowThreshold =
                normalizedAvailableHeight * Number(strictHeightOverflowTolerance ?? 1.01);
            const normalizedMinimumFillWidth = Math.max(
                0.1,
                Math.min(1, Number(minimumFillWidth ?? 0.32)),
            );
            const normalizedMinimumFillHeight = Math.max(
                0.1,
                Math.min(1, Number(minimumFillHeight ?? 0.22)),
            );

            if (this.hasManualPageLayoutAdjustments()) {
                return;
            }

            const fitSanityContextKey = this.fitSanityContextKey({
                pageNumber: normalizedPageNumber,
                availableWidth: normalizedAvailableWidth,
                availableHeight: normalizedAvailableHeight,
                minimumFillWidth: normalizedMinimumFillWidth,
                minimumFillHeight: normalizedMinimumFillHeight,
                strictWidthOverflowTolerance: Number(strictWidthOverflowTolerance ?? 1.06),
                strictHeightOverflowTolerance: Number(strictHeightOverflowTolerance ?? 1.01),
            });

            if (this._fitSanityCheckTimer !== null) {
                clearTimeout(this._fitSanityCheckTimer);
                this._fitSanityCheckTimer = null;
            }

            this._fitSanityCheckTimer = window.setTimeout(() => {
                this._fitSanityCheckTimer = null;

                if (
                    Math.max(1, Math.trunc(Number(this.pageNumber ?? 1))) !== normalizedPageNumber
                ) {
                    return;
                }

                // Do not run post-reveal fit adjustments.
                if (this.isCurrentPageVisiblyReady() && this.pageFitState() === 'ready') {
                    return;
                }

                const contentElement = this.$refs.pageContent;

                if (!(contentElement instanceof Element)) {
                    return;
                }

                const measured = this.measureRenderedBounds(contentElement, {
                    useRobustWidth: false,
                });
                const hasOverflow =
                    measured.width > widthOverflowThreshold ||
                    measured.height > heightOverflowThreshold;
                const fillWidth = measured.width / normalizedAvailableWidth;
                const fillHeight = measured.height / normalizedAvailableHeight;
                const hasSuspiciousUnderfill =
                    fillWidth < normalizedMinimumFillWidth ||
                    fillHeight < normalizedMinimumFillHeight;

                if (!hasOverflow && !hasSuspiciousUnderfill) {
                    this._fitSanityContextKey = '';
                    this._fitSanityContextAttemptCount = 0;
                    this._fitSanityContextLastWidth = 0;
                    this._fitSanityContextLastHeight = 0;
                    this._fitSanityContextOutcome = '';
                    this._fitSanitySuppressedUntil = 0;
                    this._fitSanityDisabledContextKey = '';

                    return;
                }

                if (
                    this._fitSanityContextKey === fitSanityContextKey &&
                    Date.now() < this._fitSanitySuppressedUntil
                ) {
                    return;
                }

                const isLayoutPipelineBusy =
                    this.isLoadingPage ||
                    this.isFittingPage ||
                    this._layoutActivePromise !== null ||
                    this._revealTimer !== null ||
                    this._pendingNavigationRequest !== null ||
                    this._navigationRevealLocked ||
                    this._isModalLifecycleSettling ||
                    this._activeModalIds.size > 0 ||
                    this.openModalCount() > 0;

                if (isLayoutPipelineBusy) {
                    this._fitSanityCheckTimer = window.setTimeout(() => {
                        this._fitSanityCheckTimer = null;
                        this.scheduleFitSanityCheck({
                            cacheKey: normalizedCacheKey,
                            pageNumber: normalizedPageNumber,
                            availableWidth: normalizedAvailableWidth,
                            availableHeight: normalizedAvailableHeight,
                            strictWidthOverflowTolerance: Number(
                                strictWidthOverflowTolerance ?? 1.06,
                            ),
                            strictHeightOverflowTolerance: Number(
                                strictHeightOverflowTolerance ?? 1.01,
                            ),
                            minimumFillWidth: normalizedMinimumFillWidth,
                            minimumFillHeight: normalizedMinimumFillHeight,
                        });
                    }, 180);

                    return;
                }

                const fitRecoveryDecision = this.recordFitSanityRecoveryAttempt({
                    contextKey: fitSanityContextKey,
                    measuredWidth: measured.width,
                    measuredHeight: measured.height,
                    hasOverflow,
                    hasSuspiciousUnderfill,
                });

                if (fitRecoveryDecision.shouldSuppressRecovery) {
                    if (this.hasRenderablePage() && !this.isLoadingPage) {
                        this.forceRevealCurrentPage('fit-sanity-churn-circuit-breaker');
                    }

                    this.traceReaderReveal('fit-sanity-recovery-suppressed', {
                        reason: fitRecoveryDecision.reason,
                        attemptCount: fitRecoveryDecision.attemptCount,
                        fillWidth,
                        fillHeight,
                        hasOverflow,
                        hasSuspiciousUnderfill,
                    });

                    return;
                }

                if (normalizedCacheKey !== '') {
                    this.forgetFitResult(normalizedCacheKey);
                }

                if (this.isCurrentPageVisiblyReady()) {
                    this._bypassNextFitCache = true;
                    this.fitPageToViewport();
                    this._lastPageRevealAt = Date.now();

                    return;
                }

                this.scheduleLayout({
                    revealDelayMs: 120,
                    maxAttempts: 5,
                });
            }, 160);
        },

        applySafetyScaleForCurrentPageOverflow() {
            const rootElement = this.$el.firstElementChild;
            const frameElement = this.$refs.pageFrame;
            const contentElement = this.$refs.pageContent;

            if (!rootElement || !frameElement || !contentElement) {
                return false;
            }

            if (this.hasManualPageLayoutAdjustments()) {
                return false;
            }

            const frameRect = frameElement.getBoundingClientRect();
            const frameParentRect = frameElement.parentElement?.getBoundingClientRect?.() ?? null;
            const availableWidth = Math.max(
                1,
                Number(
                    frameParentRect?.width ??
                        frameRect?.width ??
                        frameElement.parentElement?.clientWidth ??
                        frameElement.clientWidth ??
                        1,
                ),
            );
            const computedRootStyles = window.getComputedStyle(rootElement);
            const fitAreaPaddingX = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-area-pad-x',
                    rootElement,
                    0,
                ),
            );
            const fitAreaPaddingY = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-area-pad-y',
                    rootElement,
                    0,
                ),
            );
            const immersiveFitTopPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          computedRootStyles,
                          '--quran-immersive-page-pad-top',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const immersiveFitBottomPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          computedRootStyles,
                          '--quran-immersive-page-pad-bottom',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const fitHeightRatio = Math.min(
                1,
                Math.max(
                    0.5,
                    Number.parseFloat(
                        computedRootStyles.getPropertyValue('--quran-fit-height-ratio'),
                    ) || 1,
                ),
            );
            const availableHeight = Math.max(
                1,
                (Number(frameRect?.height ?? frameElement.clientHeight ?? 1) -
                    fitAreaPaddingY * 2 -
                    immersiveFitTopPadding -
                    immersiveFitBottomPadding) *
                    fitHeightRatio,
            );
            const adjustedAvailableWidth = Math.max(1, availableWidth - fitAreaPaddingX * 2);
            const minScale = Math.max(
                0.05,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-min-page-scale')) ||
                    0.1,
            );
            const maxScale = Math.max(
                minScale,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-max-page-scale')) ||
                    1,
            );
            const measured = this.measureRenderedBounds(contentElement, {
                useRobustWidth: false,
            });

            if (measured.width <= 1 || measured.height <= 1) {
                return false;
            }

            const adjustScale = Math.min(
                adjustedAvailableWidth / Math.max(1, measured.width),
                availableHeight / Math.max(1, measured.height),
            );

            if (!Number.isFinite(adjustScale) || adjustScale >= 0.995) {
                return false;
            }

            const currentScale = Math.max(
                minScale,
                Math.min(
                    maxScale,
                    Number.parseFloat(
                        contentElement.style.getPropertyValue('--quran-page-scale'),
                    ) ||
                        Number(this.pageScale) ||
                        1,
                ),
            );
            const nextScale = Math.max(
                minScale,
                Math.min(maxScale, Number((currentScale * adjustScale).toFixed(4))),
            );

            this.pageScale = nextScale;
            this.setCurrentPageScale(nextScale, { forFitting: true });

            return true;
        },

        fitPageToViewport() {
            const rootElement = this.$el.firstElementChild;
            const frameElement = this.$refs.pageFrame;
            const contentElement = this.$refs.pageContent;

            if (!rootElement || !frameElement || !contentElement) {
                this.qrDebugLog('[QR:fitPageToViewport] missing element refs');
                return;
            }

            // Always start from CSS-declared defaults to avoid cross-page drift from
            // previously applied inline fit values.
            rootElement.style.removeProperty('--quran-page-type-scale');
            rootElement.style.removeProperty('--quran-page-leading-multiplier');
            rootElement.style.removeProperty('--quran-page-gap-multiplier');
            rootElement.style.removeProperty('--quran-page-surah-header-scale');
            rootElement.style.removeProperty('--quran-basmallah-bottom-gap-scale');

            const frameRect = frameElement.getBoundingClientRect();
            const frameParentRect = frameElement.parentElement?.getBoundingClientRect?.() ?? null;
            const computedRootStyles = window.getComputedStyle(rootElement);
            const computedContentStyles = window.getComputedStyle(contentElement);
            const readCssNumber = (propertyName, fallback) => {
                const contentRawValue = Number.parseFloat(
                    computedContentStyles.getPropertyValue(propertyName),
                );

                if (Number.isFinite(contentRawValue)) {
                    return contentRawValue;
                }

                const rootRawValue = Number.parseFloat(
                    computedRootStyles.getPropertyValue(propertyName),
                );

                return Number.isFinite(rootRawValue) ? rootRawValue : fallback;
            };
            const breakpointName = this.resolveCurrentBreakpointName();
            const isBaseBreakpoint = breakpointName === 'base';
            const isTabletBreakpoint = ['sm', 'md', 'lg'].includes(breakpointName);
            const canUseGlobalFitCalibration = ['xl', '2xl', '3xl', '4xl'].includes(
                String(breakpointName ?? '').trim(),
            );
            const cssBaselineLayout = {
                pageTypeScale: Math.max(0.2, readCssNumber('--quran-page-type-scale', 1)),
                pageLeadingMultiplier: Math.max(
                    0.25,
                    readCssNumber('--quran-page-leading-multiplier', 1),
                ),
                pageGapMultiplier: Math.max(0, readCssNumber('--quran-page-gap-multiplier', 1)),
                pageSurahHeaderScale: Math.max(
                    0.5,
                    readCssNumber('--quran-page-surah-header-scale', 1),
                ),
                basmallahBottomGapScale: readCssNumber(
                    '--quran-basmallah-bottom-gap-scale',
                    defaultBasmallahBottomGapScale,
                ),
            };
            const calibrationLayout =
                canUseGlobalFitCalibration &&
                this._globalFitCalibrationLayout &&
                typeof this._globalFitCalibrationLayout === 'object'
                    ? this._globalFitCalibrationLayout
                    : null;
            const baselineLayout = calibrationLayout
                ? {
                      pageTypeScale: Math.max(
                          0.2,
                          Number(
                              calibrationLayout.pageTypeScale ?? cssBaselineLayout.pageTypeScale,
                          ),
                      ),
                      pageLeadingMultiplier: Math.max(
                          0.25,
                          Number(
                              calibrationLayout.pageLeadingMultiplier ??
                                  cssBaselineLayout.pageLeadingMultiplier,
                          ),
                      ),
                      pageGapMultiplier: Math.max(
                          0,
                          Number(
                              calibrationLayout.pageGapMultiplier ??
                                  cssBaselineLayout.pageGapMultiplier,
                          ),
                      ),
                      pageSurahHeaderScale: Math.max(
                          0.5,
                          Number(
                              calibrationLayout.pageSurahHeaderScale ??
                                  cssBaselineLayout.pageSurahHeaderScale,
                          ),
                      ),
                      basmallahBottomGapScale: Number(
                          calibrationLayout.basmallahBottomGapScale ??
                              cssBaselineLayout.basmallahBottomGapScale,
                      ),
                  }
                : { ...cssBaselineLayout };

            this.applyFitLayoutVariables(rootElement, baselineLayout);
            this.setCurrentPageScale(1, { forFitting: true });
            const rawAvailableWidth = Math.max(
                1,
                Number(
                    frameParentRect?.width ??
                        frameRect?.width ??
                        frameElement.parentElement?.clientWidth ??
                        frameElement.clientWidth ??
                        1,
                ),
            );
            const fitTargetWidthRatio = Math.min(
                0.95,
                Math.max(
                    0.55,
                    Number.parseFloat(
                        computedRootStyles.getPropertyValue('--quran-fit-target-width-ratio'),
                    ) || 0.8,
                ),
            );
            const fitAreaPaddingX = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-area-pad-x',
                    rootElement,
                    0,
                ),
            );
            const fitAreaPaddingY = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-area-pad-y',
                    rootElement,
                    0,
                ),
            );
            const fitTopClearance = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-top-clearance',
                    rootElement,
                    0,
                ),
            );
            const fitBottomClearance = Math.max(
                0,
                this.cssCustomLengthPixels(
                    computedRootStyles,
                    '--quran-fit-bottom-clearance',
                    rootElement,
                    8,
                ),
            );
            const immersiveFitTopPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          computedRootStyles,
                          '--quran-immersive-page-pad-top',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const immersiveFitBottomPadding = this.shouldUseImmersiveReaderChrome()
                ? Math.max(
                      0,
                      this.cssCustomLengthPixels(
                          computedRootStyles,
                          '--quran-immersive-page-pad-bottom',
                          rootElement,
                          0,
                      ),
                  )
                : 0;
            const availableWidth = Math.max(1, rawAvailableWidth - fitAreaPaddingX * 2);
            const fitHeightRatio = Math.min(
                1,
                Math.max(
                    0.7,
                    Number.parseFloat(
                        computedRootStyles.getPropertyValue('--quran-fit-height-ratio'),
                    ) || 1,
                ),
            );
            const frameAreaTop =
                Number(frameRect?.top ?? 0) +
                fitAreaPaddingY +
                fitTopClearance +
                immersiveFitTopPadding;
            let frameAreaBottom =
                Number(frameRect?.bottom ?? 0) > 0
                    ? Number(frameRect.bottom) - fitAreaPaddingY - immersiveFitBottomPadding
                    : Number(frameRect?.top ?? 0) +
                      Number(frameRect?.height ?? frameElement.clientHeight ?? 1) -
                      fitAreaPaddingY -
                      immersiveFitBottomPadding;
            const protectedBottomElements = this.shouldUseImmersiveReaderChrome()
                ? []
                : [
                      rootElement.querySelector('.quran-page-slider-chip'),
                      rootElement.querySelector('.quran-page-slider'),
                  ];

            protectedBottomElements.forEach((element) => {
                if (!(element instanceof Element)) {
                    return;
                }

                const elementRect = element.getBoundingClientRect();

                if (
                    elementRect.width <= 0 ||
                    elementRect.height <= 0 ||
                    elementRect.top <= frameAreaTop ||
                    elementRect.top > frameAreaBottom + fitBottomClearance * 1.5
                ) {
                    return;
                }

                frameAreaBottom = Math.min(frameAreaBottom, elementRect.top - fitBottomClearance);
            });

            const rawAvailableHeight = Math.max(1, frameAreaBottom - frameAreaTop);
            const availableHeight = Math.max(1, rawAvailableHeight * fitHeightRatio);
            const targetWidth = Math.max(1, availableWidth * fitTargetWidthRatio);
            const targetHeight = Math.max(1, availableHeight);
            const targetAreaLeft =
                Number(frameRect?.left ?? 0) +
                fitAreaPaddingX +
                (availableWidth - targetWidth) * 0.5;
            const targetAreaRight = targetAreaLeft + targetWidth;
            const targetAreaTop = frameAreaTop;
            const targetAreaBottom = targetAreaTop + targetHeight;
            const minScale = Math.max(
                0.05,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-min-page-scale')) ||
                    0.1,
            );
            let maxScale = Math.max(
                minScale,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-max-page-scale')) ||
                    1,
            );
            const activeFitProfile = this.resolveFitProfile();
            const profileMaxScaleMultiplier = Math.max(
                0.1,
                Number(activeFitProfile?.maxScaleMultiplier ?? 1),
            );
            maxScale = Math.max(minScale, maxScale * profileMaxScaleMultiplier);
            const hasGlobalCalibrationProfile =
                canUseGlobalFitCalibration &&
                this._globalFitCalibrationLayout &&
                typeof this._globalFitCalibrationLayout === 'object' &&
                Number.isFinite(Number(this._globalFitCalibrationScale)) &&
                Number(this._globalFitCalibrationScale) > 0;

            this.qrDebugLog(
                '[QR:fitPageToViewport] hasGlobalCalibration:',
                hasGlobalCalibrationProfile,
                'bypass:',
                this._bypassNextFitCache,
                'frameW:',
                frameRect?.width,
                'frameH:',
                frameRect?.height,
                'bp:',
                breakpointName,
                'page:',
                this.pageNumber,
                'visible:',
                this.isReaderElementVisible(),
            );

            if (hasGlobalCalibrationProfile) {
                const globalLayout = {
                    pageTypeScale: Math.max(
                        0.2,
                        Number(this._globalFitCalibrationLayout.pageTypeScale ?? 1),
                    ),
                    pageLeadingMultiplier: Math.max(
                        0.25,
                        Number(this._globalFitCalibrationLayout.pageLeadingMultiplier ?? 1),
                    ),
                    pageGapMultiplier: Math.max(
                        0,
                        Number(this._globalFitCalibrationLayout.pageGapMultiplier ?? 1),
                    ),
                    pageSurahHeaderScale: Math.max(
                        0.5,
                        Number(this._globalFitCalibrationLayout.pageSurahHeaderScale ?? 1),
                    ),
                    basmallahBottomGapScale: Number(
                        this._globalFitCalibrationLayout.basmallahBottomGapScale ??
                            defaultBasmallahBottomGapScale,
                    ),
                };
                const globalScale = Math.max(
                    minScale,
                    Math.min(maxScale, Number(this._globalFitCalibrationScale)),
                );

                this.applyFitLayoutVariables(rootElement, globalLayout);
                this.pageScale = globalScale;
                this.setCurrentPageScale(globalScale);
                this._bypassNextFitCache = false;
                this._fitRunCounter += 1;
                this._lastFittedPageNumber = this.pageNumber;

                return;
            }
            const minimumLeadingMultiplier = Math.max(
                0.35,
                Math.min(
                    baselineLayout.pageLeadingMultiplier,
                    readCssNumber(
                        '--quran-min-page-leading-multiplier',
                        baselineLayout.pageLeadingMultiplier,
                    ),
                ),
            );
            const minimumGapMultiplier = Math.max(
                0,
                Math.min(
                    baselineLayout.pageGapMultiplier,
                    readCssNumber(
                        '--quran-min-page-gap-multiplier',
                        baselineLayout.pageGapMultiplier,
                    ),
                ),
            );
            const minimumSurahHeaderScale = Math.max(
                0.5,
                Math.min(
                    baselineLayout.pageSurahHeaderScale,
                    readCssNumber(
                        '--quran-min-page-surah-header-scale',
                        baselineLayout.pageSurahHeaderScale,
                    ),
                ),
            );
            const minimumBasmallahBottomGapScale = Math.min(
                baselineLayout.basmallahBottomGapScale,
                readCssNumber(
                    '--quran-min-basmallah-bottom-gap-scale',
                    baselineLayout.basmallahBottomGapScale - 0.12,
                ),
            );
            const normalizedPageNumber = Math.max(1, Math.trunc(Number(this.pageNumber ?? 1)));
            const isModalLayoutContext =
                this._bypassNextFitCache ||
                this._isModalLifecycleSettling ||
                this._activeModalIds.size > 0 ||
                this.openModalCount() > 0;
            const isFontLayoutPending = !this.areTrackedPageFontsLoaded();
            const shouldBypassFitCache = isModalLayoutContext || isFontLayoutPending;
            const shouldSuppressPersistedCacheWrite = shouldBypassFitCache;
            const ayahLineCount = Array.isArray(this.mushafLines)
                ? this.mushafLines.filter((line) => String(line?.line_type ?? '') === 'ayah').length
                : 0;
            const targetMinimumFillWidth =
                ayahLineCount >= 10 ? 0.9 : ayahLineCount >= 6 ? 0.86 : 0.8;
            const targetMinimumFillHeight = Math.max(
                0.64,
                Math.min(0.9, Number(activeFitProfile?.targetHeightRatio ?? 0.84) - 0.08),
            );
            const fitCacheKey = [
                normalizedPageNumber,
                breakpointName || 'bp-unknown',
                this.viewportBucketValue(availableWidth),
                this.viewportBucketValue(availableHeight),
                isModalLayoutContext ? 'modal-open' : 'modal-closed',
                isFontLayoutPending ? 'fonts-pending' : 'fonts-loaded',
                this.useCenteredAyahLayout ? 'centered' : 'rect',
                'scale-only-v4',
                Number(fitTargetWidthRatio).toFixed(3),
                Number(fitAreaPaddingX).toFixed(2),
                Number(fitAreaPaddingY).toFixed(2),
                Number(fitTopClearance).toFixed(2),
                Number(fitBottomClearance).toFixed(2),
                Number(immersiveFitTopPadding).toFixed(2),
                Number(immersiveFitBottomPadding).toFixed(2),
                Number(minimumLeadingMultiplier).toFixed(3),
                Number(minimumGapMultiplier).toFixed(3),
                Number(minimumSurahHeaderScale).toFixed(3),
                Number(minimumBasmallahBottomGapScale).toFixed(3),
                this._globalFitCalibrationPageNumber > 0
                    ? `cal-${this._globalFitCalibrationPageNumber}`
                    : 'cal-none',
                String(this.qpcPageFontFamily ?? ''),
                String(this.basmallahFontFamily ?? ''),
                String(this.surahHeaderFontFamily ?? ''),
                Number(minScale).toFixed(3),
                Number(maxScale).toFixed(3),
            ].join('|');

            const strictWidthOverflowTolerance = 1.0025;
            const strictHeightOverflowTolerance = 1.0025;
            const strictWidthOverflowThreshold = targetWidth * strictWidthOverflowTolerance;
            const strictHeightOverflowThreshold = targetHeight * strictHeightOverflowTolerance;
            const minimumHealthyFillWidth = Math.max(0.5, targetMinimumFillWidth - 0.1);
            const minimumHealthyFillHeight = Math.max(0.56, targetMinimumFillHeight - 0.08);

            const measureVisualBounds = () => {
                const measured = this.measureRenderedBounds(contentElement, {
                    useRobustWidth: false,
                });
                const lineTargets = Array.from(
                    contentElement.querySelectorAll('[data-quran-line-text]'),
                );
                let minLeft = Number.POSITIVE_INFINITY;
                let minTop = Number.POSITIVE_INFINITY;
                let maxRight = Number.NEGATIVE_INFINITY;
                let maxBottom = Number.NEGATIVE_INFINITY;

                lineTargets.forEach((target) => {
                    const rect = target.getBoundingClientRect();

                    if (rect.width <= 0 || rect.height <= 0) {
                        return;
                    }

                    minLeft = Math.min(minLeft, rect.left);
                    minTop = Math.min(minTop, rect.top);
                    maxRight = Math.max(maxRight, rect.right);
                    maxBottom = Math.max(maxBottom, rect.bottom);
                });

                if (!Number.isFinite(minLeft) || !Number.isFinite(maxRight)) {
                    const fallbackRect = contentElement.getBoundingClientRect();

                    return {
                        width: Math.max(1, measured.width, Number(fallbackRect.width ?? 0)),
                        height: Math.max(1, measured.height, Number(fallbackRect.height ?? 0)),
                        minLeft: Number(fallbackRect.left ?? 0),
                        minTop: Number(fallbackRect.top ?? 0),
                        maxRight: Number(fallbackRect.right ?? 0),
                        maxBottom: Number(fallbackRect.bottom ?? 0),
                    };
                }

                return {
                    width: Math.max(1, measured.width, maxRight - minLeft),
                    height: Math.max(1, measured.height, maxBottom - minTop),
                    minLeft,
                    minTop,
                    maxRight,
                    maxBottom,
                };
            };

            const isWithinTargetArea = (bounds, tolerancePx = 0.5) => {
                const overflowLeft = bounds.minLeft < targetAreaLeft - tolerancePx;
                const overflowRight = bounds.maxRight > targetAreaRight + tolerancePx;
                const overflowTop = bounds.minTop < targetAreaTop - tolerancePx;
                const overflowBottom = bounds.maxBottom > targetAreaBottom + tolerancePx;

                return !(overflowLeft || overflowRight || overflowTop || overflowBottom);
            };

            const evaluateScale = (scale) => {
                const normalizedScale = Math.max(
                    minScale,
                    Math.min(maxScale, Number(scale) || minScale),
                );
                this.setCurrentPageScale(normalizedScale, { forFitting: true });
                const bounds = measureVisualBounds();
                const fillWidth = bounds.width / targetWidth;
                const fillHeight = bounds.height / targetHeight;
                const fitsBox =
                    bounds.width <= strictWidthOverflowThreshold &&
                    bounds.height <= strictHeightOverflowThreshold;
                const isInsideTargetArea = isWithinTargetArea(bounds, 1.5);

                return {
                    scale: normalizedScale,
                    bounds,
                    fillWidth,
                    fillHeight,
                    isInsideTargetArea,
                    fits: fitsBox && isInsideTargetArea,
                };
            };

            const cachedFitResult = isModalLayoutContext
                ? null
                : isFontLayoutPending
                  ? null
                  : this._fitResultByContext.get(fitCacheKey);

            if (
                cachedFitResult &&
                cachedFitResult.layout &&
                Number.isFinite(Number(cachedFitResult.scale))
            ) {
                this.applyFitLayoutVariables(rootElement, cachedFitResult.layout);
                const cached = evaluateScale(Number(cachedFitResult.scale));
                const cacheHasHealthyFill =
                    cached.fillWidth >= minimumHealthyFillWidth &&
                    cached.fillHeight >= minimumHealthyFillHeight;

                if (cached.fits && cacheHasHealthyFill) {
                    this.pageScale = cached.scale;
                    this.setCurrentPageScale(cached.scale);
                    this._fitRunCounter += 1;
                    this._lastFittedPageNumber = this.pageNumber;
                    this.scheduleFitSanityCheck({
                        cacheKey: fitCacheKey,
                        pageNumber: normalizedPageNumber,
                        availableWidth: targetWidth,
                        availableHeight: targetHeight,
                        strictWidthOverflowTolerance,
                        strictHeightOverflowTolerance,
                        minimumFillWidth: minimumHealthyFillWidth,
                        minimumFillHeight: minimumHealthyFillHeight,
                    });

                    return;
                }

                this.forgetFitResult(fitCacheKey);
            }

            const midpointLayout = {
                ...baselineLayout,
                pageLeadingMultiplier:
                    baselineLayout.pageLeadingMultiplier -
                    (baselineLayout.pageLeadingMultiplier - minimumLeadingMultiplier) * 0.5,
                pageGapMultiplier:
                    baselineLayout.pageGapMultiplier -
                    (baselineLayout.pageGapMultiplier - minimumGapMultiplier) * 0.5,
                pageSurahHeaderScale:
                    baselineLayout.pageSurahHeaderScale -
                    (baselineLayout.pageSurahHeaderScale - minimumSurahHeaderScale) * 0.5,
                basmallahBottomGapScale:
                    baselineLayout.basmallahBottomGapScale -
                    (baselineLayout.basmallahBottomGapScale - minimumBasmallahBottomGapScale) * 0.5,
            };
            const tightLayout = {
                ...baselineLayout,
                pageLeadingMultiplier: minimumLeadingMultiplier,
                pageGapMultiplier: minimumGapMultiplier,
                pageSurahHeaderScale: minimumSurahHeaderScale,
                basmallahBottomGapScale: minimumBasmallahBottomGapScale,
            };
            const expandedLayout = {
                ...baselineLayout,
                pageLeadingMultiplier: Math.min(
                    baselineLayout.pageLeadingMultiplier * 1.18,
                    baselineLayout.pageLeadingMultiplier + 0.28,
                ),
                pageGapMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageGapMultiplier * 1.65,
                        baselineLayout.pageGapMultiplier + 0.32,
                    ),
                    2.45,
                ),
                pageSurahHeaderScale: Math.min(baselineLayout.pageSurahHeaderScale * 1.06, 1.32),
            };
            const expandedVerticalLayout = {
                ...baselineLayout,
                pageLeadingMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageLeadingMultiplier * 1.44,
                        baselineLayout.pageLeadingMultiplier + 0.42,
                    ),
                    2.1,
                ),
                pageGapMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageGapMultiplier * 2.35,
                        baselineLayout.pageGapMultiplier + 0.7,
                    ),
                    3.35,
                ),
                pageSurahHeaderScale: Math.min(baselineLayout.pageSurahHeaderScale * 1.08, 1.36),
            };
            const expandedTallVerticalLayout = {
                ...baselineLayout,
                pageLeadingMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageLeadingMultiplier * 1.78,
                        baselineLayout.pageLeadingMultiplier + 0.72,
                    ),
                    2.65,
                ),
                pageGapMultiplier: Math.min(
                    Math.max(
                        baselineLayout.pageGapMultiplier * 3.4,
                        baselineLayout.pageGapMultiplier + 1.05,
                    ),
                    4.4,
                ),
                pageSurahHeaderScale: Math.min(baselineLayout.pageSurahHeaderScale * 1.1, 1.42),
            };
            const profileCandidateCount = Math.max(
                isBaseBreakpoint ? 6 : 8,
                Math.min(
                    isBaseBreakpoint ? 12 : 18,
                    Math.trunc(Number(activeFitProfile?.candidateSteps ?? 24)),
                ),
            );
            const profileMinimumCompression = Math.max(
                0,
                Math.min(1, Number(activeFitProfile?.minimumCompressionLevel ?? 0)),
            );
            const profileLayoutCandidates = Array.from(
                { length: profileCandidateCount },
                (_, index) => {
                    const denominator = Math.max(1, profileCandidateCount - 1);
                    const level =
                        profileMinimumCompression +
                        ((1 - profileMinimumCompression) * index) / denominator;

                    return this.fitLayoutFromCompressionLevel(level);
                },
            );
            const layoutCandidates = [
                baselineLayout,
                expandedLayout,
                expandedVerticalLayout,
                expandedTallVerticalLayout,
                midpointLayout,
                tightLayout,
                ...profileLayoutCandidates,
            ].filter((candidateLayout, index, candidates) => {
                const candidateKey = [
                    Number(candidateLayout.pageTypeScale ?? 1).toFixed(4),
                    Number(candidateLayout.pageLeadingMultiplier ?? 1).toFixed(4),
                    Number(candidateLayout.pageGapMultiplier ?? 1).toFixed(4),
                    Number(candidateLayout.pageSurahHeaderScale ?? 1).toFixed(4),
                    Number(candidateLayout.basmallahBottomGapScale ?? 0).toFixed(4),
                ].join('|');

                return (
                    candidates.findIndex((candidate) => {
                        const existingKey = [
                            Number(candidate.pageTypeScale ?? 1).toFixed(4),
                            Number(candidate.pageLeadingMultiplier ?? 1).toFixed(4),
                            Number(candidate.pageGapMultiplier ?? 1).toFixed(4),
                            Number(candidate.pageSurahHeaderScale ?? 1).toFixed(4),
                            Number(candidate.basmallahBottomGapScale ?? 0).toFixed(4),
                        ].join('|');

                        return existingKey === candidateKey;
                    }) === index
                );
            });
            const solveBestScaleForCurrentLayout = () => {
                let lower = minScale;
                let upper = maxScale;
                let best = evaluateScale(minScale);

                if (!best.fits) {
                    return best;
                }

                const binarySearchSteps = isBaseBreakpoint ? 12 : 16;

                for (let step = 0; step < binarySearchSteps; step += 1) {
                    const mid = (lower + upper) * 0.5;
                    const evaluation = evaluateScale(mid);

                    if (evaluation.fits) {
                        best = evaluation;
                        lower = mid;
                    } else {
                        upper = mid;
                    }
                }

                return best;
            };

            let bestLayout = baselineLayout;
            let finalEvaluation = evaluateScale(minScale);
            let bestScore = Number.NEGATIVE_INFINITY;

            layoutCandidates.forEach((candidateLayout) => {
                this.applyFitLayoutVariables(rootElement, candidateLayout);
                this.setCurrentPageScale(1, { forFitting: true });
                const evaluation = solveBestScaleForCurrentLayout();

                if (!evaluation.fits) {
                    return;
                }

                const widthDeficitPenalty = Math.max(
                    0,
                    targetMinimumFillWidth - evaluation.fillWidth,
                );
                const areaOverflowPenalty = evaluation.isInsideTargetArea ? 0 : 0.08;
                const heightDeficitPenalty = Math.max(
                    0,
                    targetMinimumFillHeight - evaluation.fillHeight,
                );
                const compressionPenalty =
                    Math.max(
                        0,
                        baselineLayout.pageLeadingMultiplier -
                            Number(candidateLayout.pageLeadingMultiplier ?? 1),
                    ) *
                        0.08 +
                    Math.max(
                        0,
                        baselineLayout.pageGapMultiplier -
                            Number(candidateLayout.pageGapMultiplier ?? 1),
                    ) *
                        0.04;
                const expansionPenalty =
                    Math.max(
                        0,
                        Number(candidateLayout.pageGapMultiplier ?? 1) -
                            baselineLayout.pageGapMultiplier,
                    ) * 0.018;
                const score =
                    Math.min(1.04, evaluation.fillWidth) * 1.24 +
                    Math.min(1.04, evaluation.fillHeight) * 0.9 -
                    widthDeficitPenalty * 1.6 -
                    heightDeficitPenalty * 0.72 -
                    compressionPenalty -
                    expansionPenalty -
                    areaOverflowPenalty;

                if (score > bestScore) {
                    bestScore = score;
                    bestLayout = { ...candidateLayout };
                    finalEvaluation = evaluation;
                }
            });

            if (bestScore === Number.NEGATIVE_INFINITY) {
                let relaxedBestLayout = { ...baselineLayout };
                let relaxedBestEvaluation = evaluateScale(minScale);
                let relaxedBestScore = Number.NEGATIVE_INFINITY;

                layoutCandidates.forEach((candidateLayout) => {
                    this.applyFitLayoutVariables(rootElement, candidateLayout);
                    this.setCurrentPageScale(1, { forFitting: true });
                    const natural = measureVisualBounds();

                    if (natural.width <= 1 || natural.height <= 1) {
                        return;
                    }

                    const geometricScale = Math.max(
                        minScale,
                        Math.min(
                            maxScale,
                            Math.min(targetWidth / natural.width, targetHeight / natural.height),
                        ),
                    );
                    const evaluation = evaluateScale(geometricScale);
                    const relaxedScore =
                        Math.min(1.08, evaluation.fillWidth) * 1.2 +
                        Math.min(1.08, evaluation.fillHeight) * 0.8;

                    if (relaxedScore > relaxedBestScore) {
                        relaxedBestScore = relaxedScore;
                        relaxedBestLayout = { ...candidateLayout };
                        relaxedBestEvaluation = evaluation;
                    }
                });

                bestLayout = relaxedBestLayout;
                finalEvaluation = relaxedBestEvaluation;
            }

            this.applyFitLayoutVariables(rootElement, bestLayout);
            this.pageScale = finalEvaluation.scale;
            this.setCurrentPageScale(finalEvaluation.scale);
            const safetyAdjustedScale = this.applySafetyScaleForCurrentPageOverflow()
                ? this.pageScale
                : finalEvaluation.scale;

            this.rememberFitResult(
                fitCacheKey,
                {
                    layout: { ...bestLayout },
                    scale: safetyAdjustedScale,
                },
                {
                    persist: !shouldSuppressPersistedCacheWrite,
                },
            );
            this.scheduleFitSanityCheck({
                cacheKey: fitCacheKey,
                pageNumber: normalizedPageNumber,
                availableWidth: targetWidth,
                availableHeight: targetHeight,
                strictWidthOverflowTolerance,
                strictHeightOverflowTolerance,
                minimumFillWidth: minimumHealthyFillWidth,
                minimumFillHeight: minimumHealthyFillHeight,
            });

            if (isFontLayoutPending) {
                this.scheduleFontReadyRecoveryRefit(normalizedPageNumber, {
                    delayMs: 110,
                });
            }

            this._bypassNextFitCache = false;
            this._fitRunCounter += 1;
            this._lastFittedPageNumber = this.pageNumber;
        },

        async prefetchFontAsset(payload) {
            const fontUrl = String(payload?.qpcPageFontUrl ?? '').trim();
            const surahHeaderFontUrl = String(payload?.surahHeaderFontUrl ?? '').trim();

            if (fontUrl) {
                await cacheAssetResponse({
                    url: fontUrl,
                    cacheName: this.cacheNames.fonts,
                });
            }

            if (surahHeaderFontUrl) {
                await cacheAssetResponse({
                    url: surahHeaderFontUrl,
                    cacheName: this.cacheNames.fonts,
                });
            }
        },

        cancelIdleWarmupHandle() {
            if (this._idleWarmupHandle === null) {
                return;
            }

            if (
                this._idleWarmupHandleKind === 'idle' &&
                typeof window.cancelIdleCallback === 'function'
            ) {
                window.cancelIdleCallback(this._idleWarmupHandle);
            } else {
                clearTimeout(this._idleWarmupHandle);
            }

            this._idleWarmupHandle = null;
            this._idleWarmupHandleKind = null;
        },

        abortIdleWarmupFetch() {
            if (
                typeof AbortController === 'undefined' ||
                !(this._idleWarmupAbortController instanceof AbortController)
            ) {
                return;
            }

            this._idleWarmupAbortController.abort();
            this._idleWarmupAbortController = null;
        },

        beginIdleWarmupAbortController() {
            if (typeof AbortController === 'undefined') {
                this._idleWarmupAbortController = null;

                return null;
            }

            this.abortIdleWarmupFetch();
            this._idleWarmupAbortController = new AbortController();

            return this._idleWarmupAbortController;
        },

        pauseIdleWarmup(
            durationMs = idleWarmupPauseOnHighFrequencyNavigationMs,
            { preservePage = 0 } = {},
        ) {
            const normalizedPreservePage = Math.max(0, Math.trunc(Number(preservePage) || 0));
            const shouldPreserveInFlightPage =
                normalizedPreservePage > 0 &&
                this._idleWarmupInFlightPage === normalizedPreservePage;

            this._idleWarmupPausedUntil = Math.max(
                this._idleWarmupPausedUntil,
                Date.now() +
                    Math.max(
                        80,
                        Math.trunc(
                            Number(durationMs) || idleWarmupPauseOnHighFrequencyNavigationMs,
                        ),
                    ),
            );

            if (!shouldPreserveInFlightPage) {
                this.abortIdleWarmupFetch();
            }

            this.cancelIdleWarmupHandle();
        },

        canRunIdleWarmup() {
            return (
                Date.now() >= this._idleWarmupPausedUntil &&
                !this.isLoadingPage &&
                !this.isNavigationBurstActive() &&
                this._pendingNavigationRequest === null
            );
        },

        enqueueIdleWarmupPages(pages = [], { prepend = false } = {}) {
            const normalizedPages = (Array.isArray(pages) ? pages : [])
                .map((page) => clampPage(page, this.maxPage))
                .filter((page) => page >= 1 && (this.maxPage < 1 || page <= this.maxPage));

            if (normalizedPages.length === 0) {
                return;
            }

            if (prepend) {
                const uniquePriorityPages = Array.from(new Set(normalizedPages));
                const priorityPageSet = new Set(uniquePriorityPages);

                this._idleWarmupQueue = this._idleWarmupQueue.filter(
                    (queuedPage) => !priorityPageSet.has(queuedPage),
                );
                this._idleWarmupQueue = [...uniquePriorityPages, ...this._idleWarmupQueue];
                uniquePriorityPages.forEach((page) => {
                    this._idleWarmupQueuedPages.add(page);
                });

                return;
            }

            const appendPages = [];
            normalizedPages.forEach((page) => {
                if (this._idleWarmupQueuedPages.has(page)) {
                    return;
                }

                this._idleWarmupQueuedPages.add(page);
                appendPages.push(page);
            });

            if (appendPages.length === 0) {
                return;
            }

            this._idleWarmupQueue.push(...appendPages);
        },

        buildBackgroundWarmupSweep(centerPage = this.pageNumber) {
            const maxPage = Math.max(1, Math.trunc(Number(this.maxPage || 0)));
            const startPage = clampPage(centerPage, maxPage);
            const sweep = [];

            for (let offset = 0; offset < maxPage; offset += 1) {
                const nextPage = startPage + offset;
                const previousPage = startPage - offset;

                if (offset === 0) {
                    sweep.push(startPage);

                    continue;
                }

                if (nextPage >= 1 && nextPage <= maxPage) {
                    sweep.push(nextPage);
                }

                if (previousPage >= 1 && previousPage <= maxPage) {
                    sweep.push(previousPage);
                }
            }

            return sweep;
        },

        scheduleIdleWarmup(delayMs = 0) {
            if (this._idleWarmupQueue.length === 0 || this._idleWarmupHandle !== null) {
                return;
            }

            const normalizedDelay = Math.max(0, Math.trunc(Number(delayMs) || 0));

            if (typeof window.requestIdleCallback === 'function') {
                this._idleWarmupHandleKind = 'idle';
                this._idleWarmupHandle = window.requestIdleCallback(
                    (deadline) => {
                        this._idleWarmupHandle = null;
                        this._idleWarmupHandleKind = null;
                        void this.processIdleWarmupQueue(deadline);
                    },
                    {
                        timeout: Math.max(120, normalizedDelay || idleWarmupResumeDelayMs),
                    },
                );

                return;
            }

            this._idleWarmupHandleKind = 'timeout';
            this._idleWarmupHandle = window.setTimeout(
                () => {
                    this._idleWarmupHandle = null;
                    this._idleWarmupHandleKind = null;
                    void this.processIdleWarmupQueue(null);
                },
                Math.max(60, normalizedDelay || idleWarmupResumeDelayMs),
            );
        },

        async processIdleWarmupQueue(deadline = null) {
            if (this._idleWarmupInFlight) {
                this.scheduleIdleWarmup(idleWarmupResumeDelayMs);

                return;
            }

            if (!this.canRunIdleWarmup()) {
                this.scheduleIdleWarmup(idleWarmupResumeDelayMs);

                return;
            }

            if (
                deadline &&
                typeof deadline.timeRemaining === 'function' &&
                deadline.timeRemaining() < 6
            ) {
                this.scheduleIdleWarmup(80);

                return;
            }

            const nextPage = this._idleWarmupQueue.shift();

            if (!Number.isFinite(Number(nextPage)) || Number(nextPage) < 1) {
                this.scheduleIdleWarmup();

                return;
            }

            this._idleWarmupQueuedPages.delete(nextPage);
            this._idleWarmupInFlight = true;
            this._idleWarmupInFlightPage = Math.max(0, Math.trunc(Number(nextPage) || 0));
            const idleAbortController = this.beginIdleWarmupAbortController();

            try {
                await this.prefetchPage(nextPage, {
                    signal: idleAbortController?.signal ?? null,
                });
            } catch (_) {
                // Ignore idle warmup failures and continue queue progress.
            } finally {
                if (this._idleWarmupAbortController === idleAbortController) {
                    this._idleWarmupAbortController = null;
                }

                this._idleWarmupInFlight = false;
                this._idleWarmupInFlightPage = 0;
            }

            if (this._idleWarmupQueue.length > 0) {
                this.scheduleIdleWarmup(40);
            }
        },

        queueStartupPreload() {
            const pages = [];

            for (let page = 1; page <= this.prewarmPages; page += 1) {
                pages.push(page);
            }

            for (let offset = 1; offset <= this.prefetchRadius; offset += 1) {
                pages.push(this.pageNumber + offset, this.pageNumber - offset);
            }

            const uniquePages = Array.from(
                new Set(
                    pages
                        .map((page) => clampPage(page, this.maxPage))
                        .filter((page) => page >= 1 && (this.maxPage < 1 || page <= this.maxPage)),
                ),
            );

            window.setTimeout(() => {
                this.enqueueIdleWarmupPages(uniquePages, { prepend: true });

                if (!this._idleWarmupHasBackgroundSweepQueued && this.maxPage > 0) {
                    this._idleWarmupHasBackgroundSweepQueued = true;
                    this.enqueueIdleWarmupPages(this.buildBackgroundWarmupSweep(this.pageNumber));
                }

                this.scheduleIdleWarmup();
            }, 40);
        },

        prefetchNeighborPages(pageNumber) {
            const pages = [];

            for (let offset = 1; offset <= this.prefetchRadius; offset += 1) {
                pages.push(pageNumber + offset, pageNumber - offset);
            }

            this.enqueueIdleWarmupPages(pages, { prepend: true });
            this.scheduleIdleWarmup();
        },

        async prefetchPage(pageNumber, { signal = null } = {}) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);

            if (normalizedPage < 1 || (this.maxPage > 0 && normalizedPage > this.maxPage)) {
                return;
            }

            try {
                await this.getPagePayload(normalizedPage, {
                    signal,
                });
            } catch (_) {
                // Ignore background prefetch failures.
            }
        },

        playPageMotion(direction) {
            const nextClass =
                direction === 'prev' ? 'quran-page-motion-prev' : 'quran-page-motion-next';

            if (this.pageMotionTimer !== null) {
                clearTimeout(this.pageMotionTimer);
            }

            this.pageMotionClass = nextClass;
            this.pageMotionTimer = window.setTimeout(() => {
                this.pageMotionClass = '';
                this.pageMotionTimer = null;
            }, 260);
        },

        swipePoint(event) {
            if (event?.touches?.length) {
                const touch = event.touches[0];

                return {
                    x: touch.clientX,
                    y: touch.clientY,
                    pointerType: 'touch',
                    pointerId: null,
                };
            }

            if (event?.changedTouches?.length) {
                const touch = event.changedTouches[0];

                return {
                    x: touch.clientX,
                    y: touch.clientY,
                    pointerType: 'touch',
                    pointerId: null,
                };
            }

            if (Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)) {
                return {
                    x: event.clientX,
                    y: event.clientY,
                    pointerType: event.pointerType ?? 'mouse',
                    pointerId: event.pointerId ?? null,
                };
            }

            return null;
        },

        swipeEventSource(event) {
            return event?.type?.startsWith('touch') ? 'touch' : 'pointer';
        },

        swipeNavigationDirection(deltaX, deltaY) {
            if (Date.now() - this._lastWordHoldAt < 360) {
                return null;
            }

            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);

            if (absX < swipeActivationThresholdPx || absX < absY) {
                return null;
            }

            return deltaX > 0 ? 'next' : 'prev';
        },

        async dispatchSwipeNavigation(direction) {
            this.resetSwipeState();

            if (direction === 'next') {
                this.traceReaderReveal('dispatch-swipe-navigation', { direction: 'next' });
                this.scheduleSwipeRevealWatchdog('swipe');

                if (this.triggerChevronButtonClick('next', 'swipe')) {
                    return true;
                }

                await this.goNextFromChevron('swipe');

                return true;
            }

            if (direction === 'prev') {
                this.traceReaderReveal('dispatch-swipe-navigation', { direction: 'prev' });
                this.scheduleSwipeRevealWatchdog('swipe');

                if (this.triggerChevronButtonClick('prev', 'swipe')) {
                    return true;
                }

                await this.goPreviousFromChevron('swipe');

                return true;
            }

            return false;
        },

        activationAnchorFromEvent(event = null) {
            const targetElement =
                event?.currentTarget instanceof Element
                    ? event.currentTarget
                    : event?.target instanceof Element
                      ? event.target
                      : null;
            const point = this.swipePoint(event);

            if (point && Number.isFinite(point.x) && Number.isFinite(point.y)) {
                return {
                    x: point.x,
                    y: point.y,
                    target: targetElement,
                };
            }

            if (targetElement instanceof Element) {
                return {
                    target: targetElement,
                };
            }

            return null;
        },

        onSwipeStart(event) {
            if (event?.__quranReaderInputHandled) {
                return;
            }

            if (event && typeof event === 'object') {
                event.__quranReaderInputHandled = true;
            }

            if (event.target?.closest?.('[data-no-swipe]')) {
                this.resetSwipeState();

                return;
            }

            const source = this.swipeEventSource(event);

            const isTouchPointer =
                String(event?.pointerType ?? '').toLowerCase() === 'touch' ||
                String(event?.pointerType ?? '').toLowerCase() === 'pen';

            if (
                event.target?.closest?.('[data-quran-word-button], [data-quran-line-text]') &&
                source !== 'touch' &&
                !isTouchPointer
            ) {
                this.resetSwipeState();

                return;
            }

            if (event.target?.closest?.('input, textarea, select, [contenteditable="true"]')) {
                this.resetSwipeState();

                return;
            }

            if (this.swipe.source && this.swipe.source !== source) {
                return;
            }

            if (
                event.pointerType === 'mouse' &&
                Number.isFinite(Number(event.button)) &&
                Number(event.button) > 0
            ) {
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            this.swipe.active = true;
            this.swipe.source = source;
            this.swipe.startX = point.x;
            this.swipe.startY = point.y;
            this.swipe.pointerId = point.pointerId;
            this.swipe.pointerType = point.pointerType;
            this.hoveredAyahIndex = 0;
            this.hoveredWordIndex = 0;
        },

        async onSwipeMove(event) {
            if (event?.__quranReaderInputHandled) {
                return;
            }

            if (event && typeof event === 'object') {
                event.__quranReaderInputHandled = true;
            }

            if (!this.swipe.active) {
                return;
            }

            if (
                event?.target?.closest?.('[data-no-swipe]') ||
                event?.target?.closest?.('input, textarea, select, [contenteditable="true"]')
            ) {
                this.resetSwipeState();

                return;
            }

            const source = this.swipeEventSource(event);

            if (this.swipe.source && this.swipe.source !== source) {
                this.resetSwipeState();

                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            if (this.wordPress?.holdTriggered || this.wordPress?.dragActive) {
                return;
            }

            if (
                this.wordPress?.active &&
                String(point.pointerType ?? this.swipe.pointerType ?? '').toLowerCase() === 'mouse'
            ) {
                return;
            }

            if (this.swipe.pointerId !== null && point.pointerId !== this.swipe.pointerId) {
                this.resetSwipeState();

                return;
            }

            const direction = this.swipeNavigationDirection(
                point.x - this.swipe.startX,
                point.y - this.swipe.startY,
            );

            if (!direction) {
                return;
            }

            await this.dispatchSwipeNavigation(direction);
        },

        resetSwipeState() {
            this.swipe.active = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;
        },

        async onSwipeEnd(event) {
            if (event?.__quranReaderInputHandled) {
                return;
            }

            if (event && typeof event === 'object') {
                event.__quranReaderInputHandled = true;
            }

            if (!this.swipe.active) {
                return;
            }

            if (event?.target?.closest?.('[data-no-swipe]')) {
                this.resetSwipeState();

                return;
            }

            if (event?.target?.closest?.('input, textarea, select, [contenteditable="true"]')) {
                this.resetSwipeState();

                return;
            }

            const source = this.swipeEventSource(event);

            if (this.swipe.source && this.swipe.source !== source) {
                this.resetSwipeState();

                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                this.resetSwipeState();

                return;
            }

            if (this.wordPress?.holdTriggered || this.wordPress?.dragActive) {
                this.resetSwipeState();

                return;
            }

            if (
                this.wordPress?.active &&
                String(point.pointerType ?? this.swipe.pointerType ?? '').toLowerCase() === 'mouse'
            ) {
                this.resetSwipeState();

                return;
            }

            if (this.swipe.pointerId !== null && point.pointerId !== this.swipe.pointerId) {
                this.resetSwipeState();

                return;
            }

            const direction = this.swipeNavigationDirection(
                point.x - this.swipe.startX,
                point.y - this.swipe.startY,
            );

            if (!direction) {
                this.resetSwipeState();

                return;
            }

            await this.dispatchSwipeNavigation(direction);
        },

        onSwipeCancel() {
            this.resetSwipeState();
            this.clearWordPressState();
        },

        pageContentStyle() {
            return 'width: max-content;';
        },

        hasManualPageLayoutAdjustments() {
            return (
                this.normalizePageScaleAdjustValue(this.quranPageScaleAdjustValue, 0) !== 0 ||
                this.normalizePageGapAdjustValue(this.quranPageGapAdjustValue, 0) !== 0 ||
                this.normalizePageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue, 0) !== 0
            );
        },

        normalizePageScaleAdjustValue(value, fallback = 0) {
            const parsedValue = Math.trunc(Number(value));
            const normalizedFallback = Math.trunc(Number(fallback) || 0);
            const candidate = Number.isFinite(parsedValue) ? parsedValue : normalizedFallback;

            return Math.max(quranPageScaleAdjustMin, Math.min(quranPageScaleAdjustMax, candidate));
        },

        normalizePageGapAdjustValue(value, fallback = 0) {
            const parsedValue = Math.trunc(Number(value));
            const normalizedFallback = Math.trunc(Number(fallback) || 0);
            const candidate = Number.isFinite(parsedValue) ? parsedValue : normalizedFallback;

            return Math.max(quranPageGapAdjustMin, Math.min(quranPageGapAdjustMax, candidate));
        },

        normalizePageYOffsetAdjustValue(value, fallback = 0) {
            const parsedValue = Math.trunc(Number(value));
            const normalizedFallback = Math.trunc(Number(fallback) || 0);
            const candidate = Number.isFinite(parsedValue) ? parsedValue : normalizedFallback;

            return Math.max(
                quranPageYOffsetAdjustMin,
                Math.min(quranPageYOffsetAdjustMax, candidate),
            );
        },

        readPersistedPageScaleAdjustValue() {
            return this.normalizePageScaleAdjustValue(
                readLocalStorage(quranPageScaleAdjustStorageKey, 0),
                0,
            );
        },

        persistPageScaleAdjustValue(value = this.quranPageScaleAdjustValue) {
            writeLocalStorage(
                quranPageScaleAdjustStorageKey,
                this.normalizePageScaleAdjustValue(value, 0),
            );
        },

        readPersistedPageGapAdjustValue() {
            return this.normalizePageGapAdjustValue(
                readLocalStorage(quranPageGapAdjustStorageKey, 0),
                0,
            );
        },

        persistPageGapAdjustValue(value = this.quranPageGapAdjustValue) {
            writeLocalStorage(
                quranPageGapAdjustStorageKey,
                this.normalizePageGapAdjustValue(value, 0),
            );
        },

        readPersistedPageYOffsetAdjustValue() {
            return this.normalizePageYOffsetAdjustValue(
                readLocalStorage(quranPageYOffsetAdjustStorageKey, 0),
                0,
            );
        },

        persistPageYOffsetAdjustValue(value = this.quranPageYOffsetAdjustValue) {
            writeLocalStorage(
                quranPageYOffsetAdjustStorageKey,
                this.normalizePageYOffsetAdjustValue(value, 0),
            );
        },

        pageScaleAdjustFactor() {
            return Math.max(
                0.2,
                1 +
                    this.normalizePageScaleAdjustValue(this.quranPageScaleAdjustValue, 0) *
                        quranPageScaleAdjustMultiplierStep,
            );
        },

        pageScaleAdjustDisplayValue() {
            const value = this.normalizePageScaleAdjustValue(this.quranPageScaleAdjustValue, 0);

            return value > 0 ? `+${value}` : String(value);
        },

        pageGapAdjustFactor() {
            return Math.max(
                0.2,
                1 +
                    this.normalizePageGapAdjustValue(this.quranPageGapAdjustValue, 0) *
                        quranPageGapAdjustMultiplierStep,
            );
        },

        pageGapAdjustDisplayValue() {
            const value = this.normalizePageGapAdjustValue(this.quranPageGapAdjustValue, 0);

            return value > 0 ? `+${value}` : String(value);
        },

        pageYOffsetAdjustRemValue() {
            return (
                this.normalizePageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue, 0) *
                quranPageYOffsetAdjustRemStep
            );
        },

        pageYOffsetAdjustDisplayValue() {
            const value = this.normalizePageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue, 0);

            return value > 0 ? `+${value}` : String(value);
        },

        pageScaleElement() {
            if (this.$refs?.pageContent instanceof HTMLElement) {
                return this.$refs.pageContent;
            }

            if (this.$el?.firstElementChild instanceof HTMLElement) {
                return this.$el.firstElementChild;
            }

            return null;
        },

        setCurrentPageScale(baseScale, { forFitting = false } = {}) {
            const scaleElement = this.pageScaleElement();

            if (!(scaleElement instanceof HTMLElement)) {
                return;
            }

            const normalizedBaseScale = Math.max(0.05, Number(baseScale) || 1);
            const effectiveScale = Number(
                forFitting
                    ? normalizedBaseScale
                    : (normalizedBaseScale * this.pageScaleAdjustFactor()).toFixed(4),
            );
            const effectiveGapFactor = forFitting ? 1 : this.pageGapAdjustFactor();
            const effectiveYOffset = forFitting
                ? '0rem'
                : `${this.pageYOffsetAdjustRemValue().toFixed(3)}rem`;

            scaleElement.style.setProperty('--quran-page-scale', String(effectiveScale));
            scaleElement.style.setProperty(
                '--quran-page-gap-adjust-factor',
                String(effectiveGapFactor),
            );
            scaleElement.style.setProperty('--quran-page-y-offset-adjust', effectiveYOffset);
        },

        schedulePageScaleAdjustRefit() {
            if (this._pageScaleAdjustRefitRaf !== null) {
                return;
            }

            this._pageScaleAdjustRefitRaf = requestAnimationFrame(() => {
                this._pageScaleAdjustRefitRaf = null;

                if (
                    !this.hasRenderablePage() ||
                    this.isLoadingPage ||
                    this._layoutActivePromise !== null ||
                    this._pendingNavigationRequest !== null
                ) {
                    return;
                }

                this._bypassNextFitCache = true;
                this.fitPageToViewport();
            });
        },

        applyPageScaleAdjustValue(
            value,
            { persist = true, closeOverlay = false, refit = false } = {},
        ) {
            this.quranPageScaleAdjustValue = this.normalizePageScaleAdjustValue(value, 0);

            if (persist) {
                this.persistPageScaleAdjustValue(this.quranPageScaleAdjustValue);
            }

            this.setCurrentPageScale(this.pageScale);

            if (refit) {
                this.schedulePageScaleAdjustRefit();
            }

            if (closeOverlay) {
                this.isFontScaleOverlayVisible = false;
            }
        },

        handlePageScaleAdjustInput(event = null) {
            this.applyPageScaleAdjustValue(event?.target?.value ?? 0, {
                persist: false,
                refit: false,
            });
        },

        applyPageGapAdjustValue(value, { persist = true, refit = false } = {}) {
            this.quranPageGapAdjustValue = this.normalizePageGapAdjustValue(value, 0);

            if (persist) {
                this.persistPageGapAdjustValue(this.quranPageGapAdjustValue);
            }

            this.setCurrentPageScale(this.pageScale);

            if (refit) {
                this.schedulePageScaleAdjustRefit();
            }
        },

        handlePageGapAdjustInput(event = null) {
            this.applyPageGapAdjustValue(event?.target?.value ?? 0, {
                persist: false,
                refit: false,
            });
        },

        applyPageYOffsetAdjustValue(value, { persist = true, refit = false } = {}) {
            this.quranPageYOffsetAdjustValue = this.normalizePageYOffsetAdjustValue(value, 0);

            if (persist) {
                this.persistPageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue);
            }

            this.setCurrentPageScale(this.pageScale);

            if (refit) {
                this.schedulePageScaleAdjustRefit();
            }
        },

        handlePageYOffsetAdjustInput(event = null) {
            this.applyPageYOffsetAdjustValue(event?.target?.value ?? 0, {
                persist: false,
                refit: false,
            });
        },

        commitPageLayoutAdjustments() {
            this.persistPageScaleAdjustValue(this.quranPageScaleAdjustValue);
            this.persistPageGapAdjustValue(this.quranPageGapAdjustValue);
            this.persistPageYOffsetAdjustValue(this.quranPageYOffsetAdjustValue);
            this.schedulePageScaleAdjustRefit();
        },

        toggleFontScaleOverlay() {
            if (!this.isAnyQuranReaderViewOpen()) {
                return;
            }

            this.isFontScaleOverlayVisible = !this.isFontScaleOverlayVisible;
            this.syncReaderChromeDocumentClass();
        },

        closeFontScaleOverlay() {
            this.isFontScaleOverlayVisible = false;
            this.syncReaderChromeDocumentClass();
        },

        shouldUseImmersiveReaderChrome() {
            return String(this.resolveCurrentBreakpointName() ?? '').trim() === 'base';
        },

        syncReaderChromeDocumentClass({ forceInactive = false } = {}) {
            if (typeof document === 'undefined' || !(document.body instanceof HTMLElement)) {
                return;
            }

            const isActive = !forceInactive && this.shouldUseImmersiveReaderChrome();
            const isCalibrating =
                isActive &&
                (this.isCalibrating ||
                    this._startupCalibrationPending ||
                    !this.hasCompletedInitialMushafPreparation);

            document.body.classList.toggle('quran-reader-immersive-active', isActive);
            document.body.classList.toggle(
                'quran-reader-immersive-chrome-visible',
                isActive && this.isReaderChromeVisible,
            );
            document.body.classList.toggle('quran-reader-calibrating', isCalibrating);
            document.body.classList.toggle(
                'quran-reader-font-scale-overlay-open',
                isActive && this.isFontScaleOverlayVisible,
            );
        },

        isReaderChromeToggleTarget(event = null) {
            if (!this.shouldUseImmersiveReaderChrome()) {
                return false;
            }

            const target = event?.target instanceof Element ? event.target : null;

            if (!(target instanceof Element)) {
                return false;
            }

            return !target.closest(
                [
                    '[data-quran-reader-chrome]',
                    '[data-quran-word-button]',
                    '[data-quran-line-text]',
                    '[data-no-swipe]',
                    'button',
                    'a',
                    'input',
                    'textarea',
                    'select',
                    '[contenteditable="true"]',
                ].join(', '),
            );
        },

        showReaderChrome() {
            if (!this.shouldUseImmersiveReaderChrome()) {
                this.isReaderChromeVisible = false;
                this.syncReaderChromeDocumentClass();

                return;
            }

            this.isReaderChromeVisible = true;
            this.syncReaderChromeDocumentClass();
        },

        hideReaderChrome() {
            this.isReaderChromeVisible = false;
            this.syncReaderChromeDocumentClass();
        },

        toggleReaderChrome() {
            if (!this.shouldUseImmersiveReaderChrome()) {
                this.hideReaderChrome();

                return;
            }

            this.isReaderChromeVisible = !this.isReaderChromeVisible;
            this.syncReaderChromeDocumentClass();
        },

        handleReaderChromeToggleTap(event = null) {
            if (!this.isReaderChromeToggleTarget(event)) {
                return;
            }

            this.toggleReaderChrome();
        },

        readerPanelStyle() {
            void this._readerPanelLayoutSerial;

            const styleEntries = ['touch-action: pan-y'];
            const breakpointName = String(this.resolveCurrentBreakpointName() ?? '').trim();

            if (!['base', 'sm'].includes(breakpointName)) {
                return `${styleEntries.join('; ')};`;
            }

            const viewportHeight = Number(window.visualViewport?.height ?? window.innerHeight ?? 0);

            if (!Number.isFinite(viewportHeight) || viewportHeight <= 0) {
                return `${styleEntries.join('; ')};`;
            }

            const rootElement = this.$el?.firstElementChild;
            const rootStyles =
                rootElement instanceof Element ? window.getComputedStyle(rootElement) : null;

            if (this.shouldUseImmersiveReaderChrome()) {
                const edgePadding = Math.max(
                    0,
                    this.cssCustomLengthPixels(
                        rootStyles,
                        '--quran-immersive-panel-edge-padding',
                        rootElement,
                        10,
                    ),
                );
                const availablePanelHeight = Math.max(1, viewportHeight - edgePadding);

                styleEntries.push(
                    `height: ${Math.round(availablePanelHeight)}px`,
                    `width: min(calc(100vw - ${Math.round(edgePadding * 2)}px), 25rem)`,
                );

                return `${styleEntries.join('; ')};`;
            }

            const stackClearance = this.cssCustomLengthPixels(
                rootStyles,
                '--quran-fit-panel-stack-clearance',
                rootElement,
                10,
            );
            const fallbackStackBottom = this.cssCustomLengthPixels(
                rootStyles,
                '--quran-fit-panel-top-reserve',
                rootElement,
                breakpointName === 'base' ? 64 : 70,
            );
            const stageRect = this.$el
                ?.closest?.('.quran-app-reader-stage')
                ?.getBoundingClientRect?.();
            const stackElement = document.querySelector('.app-action-buttons-stack');
            const stackRects = Array.from(
                stackElement instanceof Element
                    ? [stackElement, ...stackElement.querySelectorAll('[data-stack-item]')]
                    : [],
            )
                .map((element) => element?.getBoundingClientRect?.() ?? null)
                .filter(
                    (rect) =>
                        rect &&
                        Number.isFinite(rect.top) &&
                        Number.isFinite(rect.bottom) &&
                        rect.width > 0 &&
                        rect.height > 0 &&
                        rect.bottom <= viewportHeight * 0.38,
                );
            const stackBottom = stackRects.reduce(
                (bottom, rect) => Math.max(bottom, Number(rect.bottom ?? 0)),
                0,
            );
            const effectiveStackBottom = Math.max(stackBottom, fallbackStackBottom);
            const hasUsableStageBottom =
                Number.isFinite(stageRect?.bottom) &&
                Number(stageRect.bottom) > effectiveStackBottom
                    ? true
                    : false;
            const stageBottom = hasUsableStageBottom
                ? Math.min(viewportHeight, Number(stageRect.bottom))
                : viewportHeight;
            const availableViewportHeight = viewportHeight - effectiveStackBottom - stackClearance;
            const availableStageHeight = stageBottom - effectiveStackBottom - stackClearance;
            const minimumPanelHeight = breakpointName === 'base' ? 320 : 420;
            const rawAvailablePanelHeight = hasUsableStageBottom
                ? Math.min(availableViewportHeight, availableStageHeight)
                : availableViewportHeight;
            const availablePanelHeight = Math.max(
                1,
                Math.max(
                    Math.min(minimumPanelHeight, rawAvailablePanelHeight),
                    rawAvailablePanelHeight,
                ),
            );

            if (breakpointName === 'base') {
                styleEntries.push(
                    `height: ${Math.round(availablePanelHeight)}px`,
                    'width: min(91vw, 25rem)',
                );
            } else {
                styleEntries.push(
                    `height: min(${Math.round(availablePanelHeight)}px, 82svh, 50rem)`,
                );
            }

            return `${styleEntries.join('; ')};`;
        },

        pageFitState() {
            if (this.isTransitioningOutPage) {
                return 'fading-out';
            }

            if (this.isFittingPage || this.hasBlockingModalLifecycleState()) {
                return 'fitting';
            }

            return 'ready';
        },

        normalizeBooleanFlag(value, fallback = false) {
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

            const normalized = String(value).trim().toLowerCase();

            if (['true', 'yes', 'on'].includes(normalized)) {
                return true;
            }

            if (['false', 'no', 'off'].includes(normalized)) {
                return false;
            }

            return Boolean(fallback);
        },

        qrDebugLog(...messages) {
            if (!this.isQrDebugLoggingEnabled) {
                return;
            }

            console.log(...messages);
        },

        qrDebugError(...messages) {
            if (!this.isQrDebugLoggingEnabled) {
                return;
            }

            console.error(...messages);
        },

        normalizeNumeralCharacters(characters, fallback = defaultWesternNumerals) {
            if (!Array.isArray(characters)) {
                return fallback.slice();
            }

            const normalizedCharacters = characters
                .map((character) => String(character ?? ''))
                .filter((character) => character !== '');

            if (normalizedCharacters.length !== 10) {
                return fallback.slice();
            }

            return normalizedCharacters;
        },

        formatAyahTokenNumber(value) {
            const numericValue = Math.max(0, Math.trunc(Number(value ?? 0)));

            if (numericValue < 1) {
                return null;
            }

            const westernText = String(numericValue);

            if (this.doesUseWesternNumerals) {
                return westernText;
            }

            return westernText.replace(/\d/g, (digit) => {
                const digitIndex = Number(digit);

                if (!Number.isInteger(digitIndex)) {
                    return digit;
                }

                return this.arabicNumeralCharacters[digitIndex] ?? digit;
            });
        },

        resolveControlPanelSettingsWithUserOverrides(defaultSettings = {}) {
            const defaults =
                defaultSettings &&
                typeof defaultSettings === 'object' &&
                !Array.isArray(defaultSettings)
                    ? defaultSettings
                    : {};

            if (typeof window === 'undefined') {
                return defaults;
            }

            let helperOverrides = {};

            if (typeof window.getUserSettingsOverrides === 'function') {
                const resolvedOverrides = window.getUserSettingsOverrides();

                if (
                    resolvedOverrides &&
                    typeof resolvedOverrides === 'object' &&
                    !Array.isArray(resolvedOverrides)
                ) {
                    helperOverrides = resolvedOverrides;
                }
            }

            let storageOverrides = {};

            if (typeof localStorage !== 'undefined') {
                try {
                    const parsedOverrides = JSON.parse(
                        localStorage.getItem(athkarSettingsUserOverridesStorageKey) ?? 'null',
                    );

                    if (
                        parsedOverrides &&
                        typeof parsedOverrides === 'object' &&
                        !Array.isArray(parsedOverrides)
                    ) {
                        storageOverrides = parsedOverrides;
                    }
                } catch (_) {
                    storageOverrides = {};
                }
            }

            const userOverrides = {
                ...storageOverrides,
                ...helperOverrides,
            };

            if (
                !userOverrides ||
                typeof userOverrides !== 'object' ||
                Array.isArray(userOverrides)
            ) {
                return defaults;
            }

            const merged = { ...defaults };
            const applyOverrideValue = (key, value) => {
                merged[key] = value;

                if (
                    Object.prototype.hasOwnProperty.call(controlPanelSettingKeys, key) &&
                    typeof controlPanelSettingKeys[key] === 'string'
                ) {
                    merged[controlPanelSettingKeys[key]] = value;
                }
            };

            Object.keys(defaults).forEach((key) => {
                const persistedSettingKey =
                    Object.prototype.hasOwnProperty.call(controlPanelSettingKeys, key) &&
                    typeof controlPanelSettingKeys[key] === 'string'
                        ? controlPanelSettingKeys[key]
                        : key;

                if (Object.prototype.hasOwnProperty.call(userOverrides, key)) {
                    applyOverrideValue(key, userOverrides[key]);

                    return;
                }

                if (Object.prototype.hasOwnProperty.call(userOverrides, persistedSettingKey)) {
                    applyOverrideValue(key, userOverrides[persistedSettingKey]);
                }
            });

            Object.keys(controlPanelSettingKeys).forEach((key) => {
                const persistedSettingKey = controlPanelSettingKeys[key];

                if (
                    typeof persistedSettingKey !== 'string' ||
                    Object.prototype.hasOwnProperty.call(merged, persistedSettingKey)
                ) {
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(userOverrides, persistedSettingKey)) {
                    applyOverrideValue(key, userOverrides[persistedSettingKey]);

                    return;
                }

                if (Object.prototype.hasOwnProperty.call(userOverrides, key)) {
                    applyOverrideValue(key, userOverrides[key]);
                }
            });

            return merged;
        },

        applyControlPanelSettings(controlPanel = {}) {
            const previousWirdSignature = this.resolveWirdRecordSignature();
            const input =
                controlPanel && typeof controlPanel === 'object' && !Array.isArray(controlPanel)
                    ? controlPanel
                    : {};
            const hasVisualEnhancements = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.enableVisualEnhancements,
            );
            const hasWordTargeting = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.targetWordsByDefault,
            );
            const hasPreserveHarakatOnCopy = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.preserveHarakatOnCopy,
            );
            const hasAppendSurahAffixOnMultiCopy = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.appendSurahAffixOnMultiCopy,
            );
            const hasAppendSurahAffixAlwaysOnCopy = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.appendSurahAffixAlwaysOnCopy,
            );
            const hasUseVolumeButtonsNavigation = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.useVolumeButtonsNavigation,
            );
            const hasUseWesternNumerals = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.useWesternNumerals,
            );
            const hasWirdFrequencyMode = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.wirdFrequencyMode,
            );
            const hasWirdKhatmatTarget = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.wirdKhatmatTarget,
            );
            const defaultVisualEnhancements = this.normalizeBooleanFlag(
                this.initialSettings?.enableVisualEnhancements,
                false,
            );
            const defaultWordTargeting = this.normalizeBooleanFlag(
                this.initialSettings?.targetWordsByDefault,
                false,
            );
            const defaultPreserveHarakatOnCopy = this.normalizeBooleanFlag(
                this.initialSettings?.preserveHarakatOnCopy,
                true,
            );
            const defaultAppendSurahAffixOnMultiCopy = this.normalizeBooleanFlag(
                this.initialSettings?.appendSurahAffixOnMultiCopy,
                true,
            );
            const defaultAppendSurahAffixAlwaysOnCopy = this.normalizeBooleanFlag(
                this.initialSettings?.appendSurahAffixAlwaysOnCopy,
                false,
            );
            const defaultUseVolumeButtonsNavigation = this.normalizeBooleanFlag(
                this.initialSettings?.useVolumeButtonsNavigation,
                false,
            );
            const defaultUseWesternNumerals = this.normalizeBooleanFlag(
                this.initialSettings?.useWesternNumerals,
                true,
            );
            const defaultWirdFrequencyMode = this.normalizeWirdFrequencyMode(
                this.initialSettings?.wirdFrequencyMode,
                wirdFrequencyModeMonthly,
            );
            const defaultWirdKhatmatTarget = this.normalizeWirdKhatmatTarget(
                this.initialSettings?.wirdKhatmatTarget,
                1,
                {
                    frequencyMode: defaultWirdFrequencyMode,
                },
            );

            this.westernNumeralCharacters = this.normalizeNumeralCharacters(
                this.initialSettings?.numeralCharacters?.western,
                defaultWesternNumerals,
            );
            this.arabicNumeralCharacters = this.normalizeNumeralCharacters(
                this.initialSettings?.numeralCharacters?.arabic,
                defaultArabicNumerals,
            );

            this.doesEnableVisualEnhancements = this.normalizeBooleanFlag(
                hasVisualEnhancements
                    ? input[controlPanelSettingKeys.enableVisualEnhancements]
                    : defaultVisualEnhancements,
                false,
            );
            this.doesTargetWordsByDefault = this.normalizeBooleanFlag(
                hasWordTargeting
                    ? input[controlPanelSettingKeys.targetWordsByDefault]
                    : defaultWordTargeting,
                false,
            );
            this.doesPreserveHarakatOnCopy = this.normalizeBooleanFlag(
                hasPreserveHarakatOnCopy
                    ? input[controlPanelSettingKeys.preserveHarakatOnCopy]
                    : defaultPreserveHarakatOnCopy,
                true,
            );
            this.doesAppendSurahAffixOnMultiCopy = this.normalizeBooleanFlag(
                hasAppendSurahAffixOnMultiCopy
                    ? input[controlPanelSettingKeys.appendSurahAffixOnMultiCopy]
                    : defaultAppendSurahAffixOnMultiCopy,
                true,
            );
            this.doesAppendSurahAffixAlwaysOnCopy = this.normalizeBooleanFlag(
                hasAppendSurahAffixAlwaysOnCopy
                    ? input[controlPanelSettingKeys.appendSurahAffixAlwaysOnCopy]
                    : defaultAppendSurahAffixAlwaysOnCopy,
                false,
            );
            this.doesUseVolumeButtonsNavigation = this.normalizeBooleanFlag(
                hasUseVolumeButtonsNavigation
                    ? input[controlPanelSettingKeys.useVolumeButtonsNavigation]
                    : defaultUseVolumeButtonsNavigation,
                false,
            );
            this.doesUseWesternNumerals = this.normalizeBooleanFlag(
                hasUseWesternNumerals
                    ? input[controlPanelSettingKeys.useWesternNumerals]
                    : defaultUseWesternNumerals,
                true,
            );
            this.wirdFrequencyMode = this.normalizeWirdFrequencyMode(
                hasWirdFrequencyMode
                    ? input[controlPanelSettingKeys.wirdFrequencyMode]
                    : defaultWirdFrequencyMode,
                defaultWirdFrequencyMode,
            );
            this.wirdKhatmatTarget = this.normalizeWirdKhatmatTarget(
                hasWirdKhatmatTarget
                    ? input[controlPanelSettingKeys.wirdKhatmatTarget]
                    : defaultWirdKhatmatTarget,
                defaultWirdKhatmatTarget,
                {
                    frequencyMode: this.wirdFrequencyMode,
                },
            );

            const nextWirdSignature = this.resolveWirdRecordSignature();

            if (nextWirdSignature !== previousWirdSignature) {
                this.ensureWirdDailyRecord({ forceRebuild: true });

                if (this.wirdModeActive) {
                    void this.exitWirdMode({
                        restoreNormalPage: true,
                        reason: 'settings-change',
                    });
                }
            } else {
                this.ensureWirdDailyRecord();
            }

            this.$nextTick(() => {
                this.syncSupportLockTargetsUi();
                this.syncNativeVolumeNavigation();
            });
        },

        interactionTargetsWords() {
            return Boolean(this.doesTargetWordsByDefault);
        },

        activeQuranReaderView() {
            if (this.views?.['quran-app-tadabbur']?.isOpen) {
                return 'quran-app-tadabbur';
            }

            if (this.views?.['quran-app-hifth']?.isOpen) {
                return 'quran-app-hifth';
            }

            if (this.views?.['quran-app-tilawa']?.isOpen) {
                return 'quran-app-tilawa';
            }

            const fallbackView = String(this._lastQuranReaderView ?? '').trim();

            if (
                ['quran-app-tilawa', 'quran-app-hifth', 'quran-app-tadabbur'].includes(fallbackView)
            ) {
                return fallbackView;
            }

            return 'quran-app-tilawa';
        },

        isAnyQuranReaderViewOpen() {
            return Boolean(
                this.views?.['quran-app-tilawa']?.isOpen ||
                this.views?.['quran-app-hifth']?.isOpen ||
                this.views?.['quran-app-tadabbur']?.isOpen,
            );
        },

        isAnyAthkarViewOpen() {
            return Boolean(
                this.views?.['athkar-app-gate']?.isOpen ||
                this.views?.['athkar-app-gate']?.isReaderVisible ||
                this.views?.['athkar-app-sabah']?.isOpen ||
                this.views?.['athkar-app-masaa']?.isOpen,
            );
        },

        shouldShowCalibrationHud() {
            return Boolean(
                this.isCalibrating &&
                this._startupCalibrationPending &&
                !this.hasCompletedInitialMushafPreparation &&
                this.isAnyQuranReaderViewOpen() &&
                !this.isAnyAthkarViewOpen() &&
                this.isReaderPanelVisible(),
            );
        },

        shouldPersistActivationIndexes() {
            return this.activeQuranReaderView() === 'quran-app-tadabbur';
        },

        clearActivationIndexes() {
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;
            this.searchHighlightedAyahIndex = 0;
        },

        isSelectableWord(word) {
            const ayahIndex = Number(word?.ayah_index ?? 0);
            const wordIndex = Number(word?.word_index ?? 0);

            if (this.interactionTargetsWords()) {
                return Number.isFinite(wordIndex) && wordIndex > 0;
            }

            return Number.isFinite(ayahIndex) && ayahIndex > 0;
        },

        selectAyah(ayahIndex) {
            const normalizedAyahIndex = Number(ayahIndex);

            if (!Number.isFinite(normalizedAyahIndex) || normalizedAyahIndex < 1) {
                return false;
            }
            const normalized = Math.trunc(normalizedAyahIndex);
            this.searchHighlightedAyahIndex = 0;

            if (!this.shouldPersistActivationIndexes()) {
                this.clearActivationIndexes();

                return true;
            }

            if (this.activeAyahIndex === normalized) {
                this.activeAyahIndex = 0;
                this.hoveredAyahIndex = 0;
                this.activeWordIndex = 0;
                this.hoveredWordIndex = 0;

                return false;
            }

            this.activeAyahIndex = normalized;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;

            return true;
        },

        selectWord(wordIndex) {
            const normalizedWordIndex = Number(wordIndex);

            if (!Number.isFinite(normalizedWordIndex) || normalizedWordIndex < 1) {
                return false;
            }

            const normalized = Math.trunc(normalizedWordIndex);
            this.searchHighlightedAyahIndex = 0;

            if (!this.shouldPersistActivationIndexes()) {
                this.clearActivationIndexes();

                return true;
            }

            if (this.activeWordIndex === normalized) {
                this.activeWordIndex = 0;
                this.hoveredWordIndex = 0;
                this.activeAyahIndex = 0;
                this.hoveredAyahIndex = 0;

                return false;
            }

            this.activeWordIndex = normalized;
            this.hoveredWordIndex = 0;
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;

            return true;
        },

        selectDefaultSegment(word, activationAnchor = null) {
            if (this.interactionTargetsWords()) {
                const isActivated = this.selectWord(Number(word?.word_index ?? 0));

                if (isActivated) {
                    void this.copyWordSelection(word, activationAnchor);
                }

                return;
            }

            const ayahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));
            const isActivated = this.selectAyah(ayahIndex);

            if (isActivated) {
                void this.copyAyahSelection(ayahIndex, activationAnchor);
            }
        },

        selectHoldSegment(word, activationAnchor = null) {
            if (this.interactionTargetsWords()) {
                const ayahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));
                const isActivated = this.selectAyah(ayahIndex);

                if (isActivated) {
                    void this.copyAyahSelection(ayahIndex, activationAnchor);
                }

                return;
            }

            const isActivated = this.selectWord(Number(word?.word_index ?? 0));

            if (isActivated) {
                void this.copyWordSelection(word, activationAnchor);
            }
        },

        copyFeedbackStyle() {
            const x = Number(this.copyFeedback?.x ?? 0);
            const y = Number(this.copyFeedback?.y ?? 0);
            const normalizedX = Number.isFinite(x) ? Math.round(x) : 0;
            const normalizedY = Number.isFinite(y) ? Math.round(y) : 0;

            return `left: ${normalizedX}px; top: ${normalizedY}px;`;
        },

        copyPointFromElement(element) {
            if (!(element instanceof Element)) {
                return null;
            }

            const rect = element.getBoundingClientRect();

            if (!Number.isFinite(rect?.left) || !Number.isFinite(rect?.top)) {
                return null;
            }

            return {
                x: rect.left + rect.width / 2,
                y: rect.top,
            };
        },

        readerPanelCenterPoint() {
            const panelElement = this.$refs.readerPanel;
            const panelRect = panelElement?.getBoundingClientRect?.();

            if (
                !Number.isFinite(panelRect?.left) ||
                !Number.isFinite(panelRect?.top) ||
                !Number.isFinite(panelRect?.width)
            ) {
                return {
                    x: Math.max(0, Math.round((window.innerWidth ?? 0) / 2)),
                    y: Math.max(0, Math.round((window.innerHeight ?? 0) / 2)),
                };
            }

            return {
                x: panelRect.left + panelRect.width / 2,
                y: panelRect.top + 56,
            };
        },

        copyPointFromAnchor(anchor = null) {
            const directX = Number(anchor?.x);
            const directY = Number(anchor?.y);

            if (
                Number.isFinite(directX) &&
                Number.isFinite(directY) &&
                (directX > 0 || directY > 0)
            ) {
                return {
                    x: directX,
                    y: directY,
                };
            }

            const swipePoint = this.swipePoint(anchor);

            if (swipePoint && Number.isFinite(swipePoint.x) && Number.isFinite(swipePoint.y)) {
                return {
                    x: swipePoint.x,
                    y: swipePoint.y,
                };
            }

            const targetPoint = this.copyPointFromElement(anchor?.target ?? null);

            if (targetPoint) {
                return targetPoint;
            }

            return this.readerPanelCenterPoint();
        },

        copiedWordKey(word) {
            return this.wordSelectionKeyFromMeta(this.normalizeSelectableWordMeta(word));
        },

        isWordCopied(word) {
            const wordKey = this.copiedWordKey(word);

            if (!wordKey || !Array.isArray(this.copiedHighlights.wordKeys)) {
                return false;
            }

            return this.copiedHighlights.wordKeys.includes(wordKey);
        },

        isAyahClusterCopied(cluster) {
            const ayahIndex = Math.max(0, Math.trunc(Number(cluster?.ayahIndex ?? 0)));

            if (ayahIndex < 1 || !Array.isArray(this.copiedHighlights.ayahIndexes)) {
                return false;
            }

            return this.copiedHighlights.ayahIndexes.includes(ayahIndex);
        },

        clearCopiedHighlights() {
            if (this._copiedHighlightTimer !== null) {
                clearTimeout(this._copiedHighlightTimer);
                this._copiedHighlightTimer = null;
            }

            this.copiedHighlights.wordKeys = [];
            this.copiedHighlights.ayahIndexes = [];
        },

        applyCopiedHighlights({ words = [], ayahIndexes = [] } = {}) {
            const uniqueWordKeys = [
                ...new Set(
                    (Array.isArray(words) ? words : [])
                        .map((word) => this.copiedWordKey(word))
                        .filter((wordKey) => typeof wordKey === 'string' && wordKey !== ''),
                ),
            ];
            const uniqueAyahIndexes = [
                ...new Set(
                    (Array.isArray(ayahIndexes) ? ayahIndexes : [])
                        .map((ayahIndex) => Math.max(0, Math.trunc(Number(ayahIndex ?? 0))))
                        .filter((ayahIndex) => ayahIndex > 0),
                ),
            ];

            this.copiedHighlights.wordKeys = uniqueWordKeys;
            this.copiedHighlights.ayahIndexes = uniqueAyahIndexes;

            if (this._copiedHighlightTimer !== null) {
                clearTimeout(this._copiedHighlightTimer);
                this._copiedHighlightTimer = null;
            }

            if (uniqueWordKeys.length < 1 && uniqueAyahIndexes.length < 1) {
                return;
            }

            this._copiedHighlightTimer = window.setTimeout(() => {
                this.clearCopiedHighlights();
            }, copiedHighlightVisibleDurationMs);
        },

        setWordClickSuppression(
            enabled = false,
            { durationMs = wordClickSuppressionResetMs } = {},
        ) {
            this._suppressNextWordClick = Boolean(enabled);

            if (this._suppressWordClickResetTimer !== null) {
                clearTimeout(this._suppressWordClickResetTimer);
                this._suppressWordClickResetTimer = null;
            }

            if (!this._suppressNextWordClick) {
                return;
            }

            this._suppressWordClickResetTimer = window.setTimeout(
                () => {
                    this._suppressNextWordClick = false;
                    this._suppressWordClickResetTimer = null;
                },
                Math.max(120, Math.trunc(Number(durationMs) || wordClickSuppressionResetMs)),
            );
        },

        normalizeSelectableWordMeta(word, fallbackWordIndex = 0) {
            return {
                ayahIndex: Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0))),
                ayahNumber: Math.max(0, Math.trunc(Number(word?.ayah_number ?? 0))),
                surahNumber: Math.max(0, Math.trunc(Number(word?.surah_number ?? 0))),
                wordIndex: Math.max(
                    0,
                    Math.trunc(Number(word?.word_index ?? fallbackWordIndex ?? 0)),
                ),
            };
        },

        wordSelectionKeyFromMeta(meta = {}) {
            const ayahIndex = Math.max(0, Math.trunc(Number(meta?.ayahIndex ?? 0)));
            const wordIndex = Math.max(0, Math.trunc(Number(meta?.wordIndex ?? 0)));

            if (ayahIndex < 1 || wordIndex < 1) {
                return null;
            }

            return `${ayahIndex}:${wordIndex}`;
        },

        rebuildWordSelectionIndex() {
            this._wordBySelectionKey = new Map();
            this._ayahNumberByIndex = new Map();
            this._surahNumberByAyahIndex = new Map();

            if (!Array.isArray(this.mushafLines)) {
                return;
            }

            this.mushafLines.forEach((line) => {
                if (!Array.isArray(line?.words)) {
                    return;
                }

                line.words.forEach((word, wordOffset) => {
                    const wordMeta = this.normalizeSelectableWordMeta(word, wordOffset + 1);
                    const selectionKey = this.wordSelectionKeyFromMeta(wordMeta);

                    if (selectionKey && !this._wordBySelectionKey.has(selectionKey)) {
                        this._wordBySelectionKey.set(selectionKey, word);
                    }

                    if (
                        wordMeta.ayahIndex > 0 &&
                        wordMeta.ayahNumber > 0 &&
                        !this._ayahNumberByIndex.has(wordMeta.ayahIndex)
                    ) {
                        this._ayahNumberByIndex.set(wordMeta.ayahIndex, wordMeta.ayahNumber);
                    }

                    if (
                        wordMeta.ayahIndex > 0 &&
                        wordMeta.surahNumber > 0 &&
                        !this._surahNumberByAyahIndex.has(wordMeta.ayahIndex)
                    ) {
                        this._surahNumberByAyahIndex.set(wordMeta.ayahIndex, wordMeta.surahNumber);
                    }
                });
            });
        },

        wordFromButtonElement(buttonElement) {
            if (!(buttonElement instanceof Element)) {
                return null;
            }

            const wordMeta = {
                ayahIndex: Math.max(
                    0,
                    Math.trunc(Number(buttonElement.getAttribute('data-quran-ayah-index') ?? 0)),
                ),
                wordIndex: Math.max(
                    0,
                    Math.trunc(Number(buttonElement.getAttribute('data-quran-word-index') ?? 0)),
                ),
                ayahNumber: Math.max(
                    0,
                    Math.trunc(Number(buttonElement.getAttribute('data-quran-ayah-number') ?? 0)),
                ),
                surahNumber: Math.max(
                    0,
                    Math.trunc(Number(buttonElement.getAttribute('data-quran-surah-number') ?? 0)),
                ),
            };
            const selectionKey = this.wordSelectionKeyFromMeta(wordMeta);

            if (selectionKey) {
                const indexedWord = this._wordBySelectionKey.get(selectionKey);

                if (indexedWord) {
                    return indexedWord;
                }
            }

            const fallbackText = normalizeTextValue(buttonElement.textContent);

            if (!fallbackText || wordMeta.ayahIndex < 1) {
                return null;
            }

            return {
                ayah_index: wordMeta.ayahIndex,
                ayah_number: wordMeta.ayahNumber,
                surah_number: wordMeta.surahNumber,
                word_index: wordMeta.wordIndex,
                text: fallbackText,
                copy_text: fallbackText,
            };
        },

        wordButtonElementFromPoint(x, y) {
            if (typeof document === 'undefined' || !Number.isFinite(x) || !Number.isFinite(y)) {
                return null;
            }

            const elementAtPoint = document.elementFromPoint(x, y);

            if (!(elementAtPoint instanceof Element)) {
                return null;
            }

            const buttonElement = elementAtPoint.closest('[data-quran-word-button]');

            if (!(buttonElement instanceof Element)) {
                return null;
            }

            return buttonElement;
        },

        collectWordPressTrailWord(word, activationAnchor = null) {
            if (!this.wordPress.active) {
                return false;
            }

            const wordMeta = this.normalizeSelectableWordMeta(word);

            if (wordMeta.ayahIndex < 1) {
                return false;
            }

            if (wordMeta.ayahNumber > 0 && !this._ayahNumberByIndex.has(wordMeta.ayahIndex)) {
                this._ayahNumberByIndex.set(wordMeta.ayahIndex, wordMeta.ayahNumber);
            }

            if (
                wordMeta.ayahIndex > 0 &&
                wordMeta.surahNumber > 0 &&
                !this._surahNumberByAyahIndex.has(wordMeta.ayahIndex)
            ) {
                this._surahNumberByAyahIndex.set(wordMeta.ayahIndex, wordMeta.surahNumber);
            }

            if (this.interactionTargetsWords()) {
                const selectionKey = this.wordSelectionKeyFromMeta(wordMeta);

                if (!selectionKey || this.wordPress.trailWordKeys.includes(selectionKey)) {
                    if (activationAnchor) {
                        this.wordPress.lastAnchor = activationAnchor;
                    }

                    return false;
                }

                this.wordPress.trailWordKeys.push(selectionKey);
                this.wordPress.trailWords.push(word);
            } else if (!this.wordPress.trailAyahIndexes.includes(wordMeta.ayahIndex)) {
                this.wordPress.trailAyahIndexes.push(wordMeta.ayahIndex);
            } else {
                if (activationAnchor) {
                    this.wordPress.lastAnchor = activationAnchor;
                }

                return false;
            }

            if (activationAnchor) {
                this.wordPress.lastAnchor = activationAnchor;
            }

            return true;
        },

        ayahSplitterToken(ayahIndex, fallbackAyahNumber = 0) {
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));
            const mappedAyahNumber = Math.max(
                0,
                Math.trunc(Number(this._ayahNumberByIndex.get(normalizedAyahIndex) ?? 0)),
            );
            const normalizedFallbackAyahNumber = Math.max(
                0,
                Math.trunc(Number(fallbackAyahNumber ?? 0)),
            );
            const ayahNumber =
                normalizedFallbackAyahNumber || mappedAyahNumber || normalizedAyahIndex;

            if (ayahNumber < 1) {
                return null;
            }

            return `(${this.formatAyahTokenNumber(ayahNumber)})`;
        },

        selectedDraggedAyahIndexes() {
            const sourceAyahIndexes = this.interactionTargetsWords()
                ? this.wordPress.trailWords.map(
                      (word) => this.normalizeSelectableWordMeta(word).ayahIndex,
                  )
                : this.wordPress.trailAyahIndexes;

            return sourceAyahIndexes
                .map((ayahIndex) => Math.max(0, Math.trunc(Number(ayahIndex ?? 0))))
                .filter(
                    (ayahIndex, index, array) =>
                        ayahIndex > 0 && array.indexOf(ayahIndex) === index,
                )
                .sort((firstAyahIndex, secondAyahIndex) => firstAyahIndex - secondAyahIndex);
        },

        selectedDraggedSurahNumbers() {
            const selectedAyahIndexes = this.selectedDraggedAyahIndexes();
            const surahNumbers = [];

            selectedAyahIndexes.forEach((ayahIndex) => {
                const surahNumber = this.surahNumberForAyahIndex(ayahIndex);

                if (surahNumber < 1 || surahNumbers.includes(surahNumber)) {
                    return;
                }

                surahNumbers.push(surahNumber);
            });

            return surahNumbers;
        },

        surahNumberForAyahIndex(ayahIndex) {
            const normalizedAyahIndex = Math.max(0, Math.trunc(Number(ayahIndex ?? 0)));

            if (normalizedAyahIndex < 1) {
                return 0;
            }

            const mappedSurahNumber = Math.max(
                0,
                Math.trunc(Number(this._surahNumberByAyahIndex.get(normalizedAyahIndex) ?? 0)),
            );

            if (mappedSurahNumber > 0) {
                return mappedSurahNumber;
            }

            if (!Array.isArray(this.mushafLines)) {
                return 0;
            }

            for (const line of this.mushafLines) {
                if (!Array.isArray(line?.words)) {
                    continue;
                }

                for (const word of line.words) {
                    const wordAyahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));

                    if (wordAyahIndex !== normalizedAyahIndex) {
                        continue;
                    }

                    const wordSurahNumber = Math.max(
                        0,
                        Math.trunc(Number(word?.surah_number ?? line?.surah_number ?? 0)),
                    );

                    if (wordSurahNumber < 1) {
                        continue;
                    }

                    this._surahNumberByAyahIndex.set(normalizedAyahIndex, wordSurahNumber);

                    return wordSurahNumber;
                }
            }

            return 0;
        },

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

            if (
                this.wordPress.pointerId !== null &&
                point?.pointerId !== null &&
                this.wordPress.pointerId !== point.pointerId
            ) {
                return;
            }

            if (this.wordPress.dragActive) {
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

                void this.copyDraggedSelection(
                    activationAnchor ??
                        this.wordPress.lastAnchor ?? {
                            x: this.wordPress.startX,
                            y: this.wordPress.startY,
                            target: this.wordPress.target,
                        },
                );
                this.setWordClickSuppression(shouldSuppressNextWordClick);
            }

            if (this.wordPress.holdTriggered) {
                this.setWordClickSuppression(true, {
                    durationMs: 520,
                });
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
                .trim()
                .replace(/\s+/g, ' ')
                .toLowerCase();
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

        clearSearchResultsUpdateQueue() {
            if (this._searchInputSyncDebounceTimer !== null) {
                clearTimeout(this._searchInputSyncDebounceTimer);
                this._searchInputSyncDebounceTimer = null;
            }
        },

        queueSearchResultsUpdate(delayMs = null) {
            const fallbackDelayMs = Math.max(
                120,
                Math.trunc(Number(this.search.inputDebounceMs ?? 600) || 600),
            );
            const normalizedDelayMs =
                delayMs === null
                    ? fallbackDelayMs
                    : Math.max(0, Math.trunc(Number(delayMs) || fallbackDelayMs));

            this.clearSearchResultsUpdateQueue();

            if (normalizedDelayMs === 0) {
                void this.updateSearchResults();

                return;
            }

            this._searchInputSyncDebounceTimer = window.setTimeout(() => {
                this._searchInputSyncDebounceTimer = null;
                void this.updateSearchResults();
            }, normalizedDelayMs);
        },

        bindSearchModalInputSyncListener() {
            const inputElement = this.searchModalInputElement();

            if (!(inputElement instanceof HTMLInputElement)) {
                return false;
            }

            if (
                this._searchModalInputSyncElement === inputElement &&
                typeof this._onSearchModalInputSync === 'function'
            ) {
                return true;
            }

            this.unbindSearchModalInputSyncListener();

            this._onSearchModalInputSync = (event) => {
                const targetInput =
                    event?.target instanceof HTMLInputElement ? event.target : inputElement;
                const nextQuery = String(targetInput?.value ?? '');

                if (nextQuery === this.search.query) {
                    return;
                }

                this.search.query = nextQuery;
                this.queueSearchResultsUpdate();
            };
            this._searchModalInputSyncElement = inputElement;
            this._searchModalInputSyncElement.addEventListener(
                'input',
                this._onSearchModalInputSync,
            );

            const nextQuery = String(inputElement.value ?? '');

            if (nextQuery !== this.search.query) {
                this.search.query = nextQuery;
            }

            return true;
        },

        jumpPageInputElement() {
            const inputElement = document.getElementById('quran-reader-page-counter-input');

            if (inputElement instanceof HTMLInputElement && inputElement.isConnected) {
                return inputElement;
            }

            return null;
        },

        isJumpPageInputVisible() {
            const inputElement = this.jumpPageInputElement();

            if (!(inputElement instanceof HTMLInputElement)) {
                return false;
            }

            const modalElement = inputElement.closest('.fi-modal');

            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }

            const styles = window.getComputedStyle(inputElement);

            return (
                inputElement.clientHeight > 8 &&
                inputElement.clientWidth > 8 &&
                styles.display !== 'none' &&
                styles.visibility !== 'hidden'
            );
        },

        queueJumpPageModalInputSync({ shouldSelect = true } = {}) {
            let didSyncOnce = false;

            [0, 30, 80, 170].forEach((delayMs) => {
                window.setTimeout(() => {
                    if (didSyncOnce) {
                        return;
                    }

                    const didSync = this.syncJumpPageModalInputValue({
                        shouldSelect,
                    });

                    if (!didSync) {
                        return;
                    }

                    didSyncOnce = true;

                    if (!this.jumpPageModalOpen) {
                        this.jumpPageModalOpen = true;
                        this.dispatchManagerModalsVisibilityState();
                    }
                }, delayMs);
            });
        },

        syncJumpPageModalInputValue({ shouldSelect = true } = {}) {
            const inputElement = this.jumpPageInputElement();

            if (!(inputElement instanceof HTMLInputElement) || !this.isJumpPageInputVisible()) {
                return false;
            }

            const normalizedPageNumber = this.clampPage(this.pageNumber, this.maxPage);
            const nextValue = String(normalizedPageNumber);

            if (inputElement.value !== nextValue) {
                inputElement.value = nextValue;
                inputElement.dispatchEvent(new Event('input', { bubbles: true }));
            }

            if (shouldSelect) {
                inputElement.focus();
                inputElement.select();
            }

            return true;
        },

        searchModalWindowElement() {
            const candidates = [];
            const isVisible = (element) => {
                if (!(element instanceof HTMLElement) || !element.isConnected) {
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

            const modalWindowFromElement = (element) => {
                if (!(element instanceof Element)) {
                    return null;
                }

                if (element.classList.contains('fi-modal-window')) {
                    return element;
                }

                const nestedWindow = element.querySelector('.fi-modal-window');

                return nestedWindow instanceof Element ? nestedWindow : null;
            };

            const pushCandidate = (element) => {
                if (!(element instanceof Element)) {
                    return;
                }

                candidates.push(element);
            };

            const searchInput = this.searchModalInputElement();

            if (searchInput instanceof HTMLElement) {
                const fromInput = searchInput.closest('.fi-modal-window');
                pushCandidate(fromInput);
            }

            [this.searchActionModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '')
                .forEach((actionModalId) => {
                    const escapedId = window.CSS?.escape
                        ? window.CSS.escape(actionModalId)
                        : actionModalId;
                    const actionModalCandidates = [
                        ...document.querySelectorAll(`#${escapedId}`),
                        ...document.querySelectorAll(`[data-fi-modal-id="${escapedId}"]`),
                    ];

                    actionModalCandidates.forEach((modalElement) => {
                        pushCandidate(modalWindowFromElement(modalElement));
                    });
                });

            [this.searchModalDomId, this.searchModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '')
                .forEach((modalWindowId) => {
                    const escapedId = window.CSS?.escape
                        ? window.CSS.escape(modalWindowId)
                        : modalWindowId;

                    document.querySelectorAll(`#${escapedId}`).forEach((element) => {
                        pushCandidate(modalWindowFromElement(element));
                    });
                });

            const uniqueCandidates = Array.from(new Set(candidates)).filter(
                (element) => element instanceof HTMLElement && element.isConnected,
            );

            if (uniqueCandidates.length === 0) {
                return null;
            }

            const visibleCandidates = uniqueCandidates.filter((element) => isVisible(element));

            if (visibleCandidates.length > 0) {
                const rankedVisibleCandidates = visibleCandidates
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
                            zIndex: Number.isFinite(modalZIndex) ? modalZIndex : 0,
                            isOpenModal,
                        };
                    })
                    .sort(
                        (left, right) =>
                            Number(right.isOpenModal) - Number(left.isOpenModal) ||
                            right.zIndex - left.zIndex,
                    );

                return rankedVisibleCandidates[0]?.element ?? null;
            }

            return uniqueCandidates[0] ?? null;
        },

        searchStreamTargetElement() {
            if (this.$refs.searchResultsStream instanceof Element) {
                return this.$refs.searchResultsStream;
            }

            const modalWindow = this.searchModalWindowElement();

            if (!(modalWindow instanceof Element)) {
                return null;
            }

            return modalWindow.querySelector('[data-quran-search-stream-target]');
        },

        clearSearchStreamTarget() {
            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element)) {
                this._lastSearchStreamPayloadRaw = '';

                return;
            }

            target.textContent = '';
            this._lastSearchStreamPayloadRaw = '';
        },

        teardownSearchStreamObserver() {
            if (this._searchStreamObserver) {
                this._searchStreamObserver.disconnect();
                this._searchStreamObserver = null;
            }
        },

        setupSearchStreamObserver() {
            this.teardownSearchStreamObserver();

            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element) || typeof MutationObserver === 'undefined') {
                return;
            }

            this._searchStreamObserver = new MutationObserver(() => {
                this.consumeSearchStreamPayload();
            });

            this._searchStreamObserver.observe(target, {
                childList: true,
                subtree: true,
                characterData: true,
            });
        },

        consumeSearchStreamPayload() {
            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element)) {
                return;
            }

            const rawPayload = String(target.textContent ?? '').trim();

            if (rawPayload === '' || rawPayload === this._lastSearchStreamPayloadRaw) {
                return;
            }

            this._lastSearchStreamPayloadRaw = rawPayload;

            let payload = null;

            try {
                payload = JSON.parse(rawPayload);
            } catch (_) {
                try {
                    payload = JSON.parse(this.decodeSearchStreamPayload(rawPayload));
                } catch (__) {
                    return;
                }
            }

            this.applySearchStreamPayload(payload);
        },

        decodeSearchStreamPayload(rawPayload) {
            if (typeof document === 'undefined') {
                return String(rawPayload ?? '');
            }

            const parser = document.createElement('textarea');
            parser.innerHTML = String(rawPayload ?? '');

            return parser.value;
        },

        searchResultKey(result) {
            const id = Math.max(0, Math.trunc(Number(result?.id ?? 0)));

            if (id > 0) {
                return `id:${id}`;
            }

            return `fallback:${Math.max(0, Math.trunc(Number(result?.surah_number ?? 0)))}:${Math.max(0, Math.trunc(Number(result?.ayah_number ?? 0)))}:${Math.max(0, Math.trunc(Number(result?.page_number ?? 0)))}:${Math.max(0, Math.trunc(Number(result?.match_rank ?? 0)))}`;
        },

        searchResultIsLeaving(result) {
            return Boolean(result?.__leaving);
        },

        activeSearchResults() {
            return (Array.isArray(this.search.results) ? this.search.results : []).filter(
                (result) => !this.searchResultIsLeaving(result),
            );
        },

        normalizeSearchResults(nextResults = []) {
            return (Array.isArray(nextResults) ? nextResults : [])
                .map((result) => {
                    if (!result || typeof result !== 'object') {
                        return null;
                    }

                    const key = this.searchResultKey(result);

                    return {
                        ...result,
                        __key: key,
                        __leaving: false,
                    };
                })
                .filter((result) => result !== null)
                .slice(0, 24);
        },

        mergeSearchResults(existingResults, incomingResults) {
            const mergedByKey = new Map();

            this.normalizeSearchResults(existingResults).forEach((result) => {
                mergedByKey.set(result.__key, result);
            });

            this.normalizeSearchResults(incomingResults).forEach((result) => {
                const previous = mergedByKey.get(result.__key) ?? {};
                mergedByKey.set(result.__key, {
                    ...previous,
                    ...result,
                    __key: result.__key,
                    __leaving: false,
                });
            });

            return Array.from(mergedByKey.values()).slice(0, 24);
        },

        syncSearchResultMetadata(results = []) {
            const activeResults = (Array.isArray(results) ? results : []).filter(
                (result) => !this.searchResultIsLeaving(result),
            );

            this.search.isOpen = (Array.isArray(results) ? results : []).length > 0;
            this.search.readyResult = activeResults.length === 1 ? activeResults[0] : null;
        },

        queueSearchLeaveCleanup() {
            if (this._searchResultsLeaveTimer !== null) {
                clearTimeout(this._searchResultsLeaveTimer);
                this._searchResultsLeaveTimer = null;
            }

            const hasLeavingResults = (
                Array.isArray(this.search.results) ? this.search.results : []
            ).some((result) => this.searchResultIsLeaving(result));

            if (!hasLeavingResults) {
                return;
            }

            this._searchResultsLeaveTimer = window.setTimeout(() => {
                this.search.results = (
                    Array.isArray(this.search.results) ? this.search.results : []
                ).filter((result) => !this.searchResultIsLeaving(result));
                this.syncSearchResultMetadata(this.search.results);
                this._searchResultsLeaveTimer = null;
            }, 260);
        },

        setSearchResults(nextResults, { immediate = false } = {}) {
            const normalizedNextResults = this.normalizeSearchResults(nextResults);

            if (immediate) {
                if (this._searchResultsLeaveTimer !== null) {
                    clearTimeout(this._searchResultsLeaveTimer);
                    this._searchResultsLeaveTimer = null;
                }

                this.search.results = normalizedNextResults;
                this.syncSearchResultMetadata(this.search.results);

                return;
            }

            const currentResults = Array.isArray(this.search.results) ? this.search.results : [];
            const nextByKey = new Map(
                normalizedNextResults.map((result) => [result.__key, result]),
            );
            const composedResults = [];
            const usedKeys = new Set();

            currentResults.forEach((result) => {
                const resultKey = this.searchResultKey(result);
                const nextMatch = nextByKey.get(resultKey);

                if (nextMatch) {
                    composedResults.push({
                        ...result,
                        ...nextMatch,
                        __key: resultKey,
                        __leaving: false,
                    });
                    usedKeys.add(resultKey);

                    return;
                }

                if (this.searchResultIsLeaving(result)) {
                    composedResults.push(result);

                    return;
                }

                composedResults.push({
                    ...result,
                    __key: resultKey,
                    __leaving: true,
                });
            });

            normalizedNextResults.forEach((result) => {
                if (usedKeys.has(result.__key)) {
                    return;
                }

                composedResults.push(result);
            });

            this.search.results = composedResults;
            this.syncSearchResultMetadata(this.search.results);
            this.queueSearchLeaveCleanup();
        },

        applySearchStreamPayload(payload) {
            const requestSerial = Math.max(0, Math.trunc(Number(payload?.request_serial ?? 0)));

            if (requestSerial !== this._searchRequestSerial) {
                return;
            }

            const stage = String(payload?.stage ?? '').trim();
            const stageResults = Array.isArray(payload?.stage_items)
                ? payload.stage_items.slice(0, 24)
                : [];
            const allResults = Array.isArray(payload?.items) ? payload.items.slice(0, 24) : [];
            const hasStreamChunk = stageResults.length > 0;

            if (hasStreamChunk && stage !== 'complete') {
                this.search.streamHasUpdates = true;
                this.setSearchResults(
                    this.mergeSearchResults(this.activeSearchResults(), stageResults),
                );
            } else if (allResults.length > 0) {
                this.setSearchResults(
                    this.search.streamHasUpdates
                        ? this.mergeSearchResults(this.activeSearchResults(), allResults)
                        : allResults,
                );
            } else if (!this.search.streamHasUpdates) {
                this.setSearchResults([], { immediate: true });
            }

            if (typeof payload?.is_loading === 'boolean') {
                this.search.isLoading = payload.is_loading;
            }

            this.$nextTick(() => {
                this.ensureSearchResultAnimations();
            });
        },

        modalWindowElementById(modalId) {
            const normalizedModalId = String(modalId ?? '').trim();

            if (!normalizedModalId) {
                return null;
            }

            const resolveModalWindowFromElement = (element) => {
                if (!(element instanceof Element)) {
                    return null;
                }

                if (element.classList.contains('fi-modal-window')) {
                    return element;
                }

                const nestedModalWindow = element.querySelector('.fi-modal-window');

                return nestedModalWindow instanceof Element ? nestedModalWindow : null;
            };

            const directElement = document.getElementById(normalizedModalId);
            const directModalWindow = resolveModalWindowFromElement(directElement);

            if (directModalWindow) {
                return directModalWindow;
            }

            const escapedId = window.CSS?.escape
                ? window.CSS.escape(normalizedModalId)
                : normalizedModalId;
            const modalByDataId = document.querySelector(`[data-fi-modal-id="${escapedId}"]`);
            const modalWindowFromDataId = resolveModalWindowFromElement(modalByDataId);

            if (modalWindowFromDataId) {
                return modalWindowFromDataId;
            }

            return null;
        },

        isModalWindowVisibleById(modalId) {
            const modalWindowElement = this.modalWindowElementById(modalId);

            if (!(modalWindowElement instanceof Element)) {
                return false;
            }

            const modalElement = modalWindowElement.closest('.fi-modal');

            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }

            const styles = window.getComputedStyle(modalWindowElement);

            return styles.display !== 'none' && styles.visibility !== 'hidden';
        },

        isSearchModalWindowVisible() {
            const modalWindowElement = this.searchModalWindowElement();

            if (!(modalWindowElement instanceof Element)) {
                return false;
            }

            const modalElement = modalWindowElement.closest('.fi-modal');

            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }

            const styles = window.getComputedStyle(modalWindowElement);

            return styles.display !== 'none' && styles.visibility !== 'hidden';
        },

        isSearchModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.searchModalId, this.searchModalDomId, this.searchActionModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);

            if (kind === 'opened') {
                if (matchedKnownId) {
                    this.searchActionModalId = modalId;

                    return true;
                }

                const isVisible = this.isSearchModalWindowVisible();

                if (isVisible && modalId !== '') {
                    this.searchActionModalId = modalId;
                }

                return isVisible;
            }

            if (matchedKnownId) {
                return true;
            }

            if (modalId === '') {
                return this.search.modalOpen || this._lastKnownModalOpenState;
            }

            return this.search.modalOpen || this._lastKnownModalOpenState;
        },

        isHistoryModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.historyModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);

            if (matchedKnownId) {
                return true;
            }

            if (kind === 'opened') {
                return this.isModalWindowVisibleById(this.historyModalId);
            }

            return this.historyModalOpen;
        },

        isBookmarksModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.bookmarksModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);

            if (matchedKnownId) {
                return true;
            }

            if (kind === 'opened') {
                return this.isModalWindowVisibleById(this.bookmarksModalId);
            }

            return this.bookmarksModalOpen;
        },

        isJumpPageModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.jumpPageModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);

            if (matchedKnownId) {
                return true;
            }

            if (kind === 'opened') {
                return (
                    this.isModalWindowVisibleById(this.jumpPageModalId) ||
                    this.isJumpPageInputVisible()
                );
            }

            return this.jumpPageModalOpen;
        },

        queueSearchModalCloseSync({ delayMs = 0 } = {}) {
            const normalizedDelayMs = Math.max(0, Math.trunc(Number(delayMs) || 0));

            window.setTimeout(() => {
                const hasStaleSearchState =
                    this.search.modalOpen ||
                    String(this.search.query ?? '').trim() !== '' ||
                    Number(this.search.results?.length ?? 0) > 0;

                if (!hasStaleSearchState) {
                    return;
                }

                if (!this.isSearchModalWindowVisible()) {
                    this.handleSearchModalClosed();
                }
            }, normalizedDelayMs);
        },

        suppressModalLifecycleEffects(
            modalIds = [],
            { durationMs = modalLifecycleSuppressionDurationMs } = {},
        ) {
            const normalizedModalIds = (Array.isArray(modalIds) ? modalIds : [modalIds])
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            if (normalizedModalIds.length < 1) {
                return;
            }

            const suppressionDurationMs = Math.max(120, Math.trunc(Number(durationMs) || 0));

            this._suppressModalLifecycleEffectsUntil = Math.max(
                this._suppressModalLifecycleEffectsUntil,
                Date.now() + suppressionDurationMs,
            );

            normalizedModalIds.forEach((modalId) => {
                this._suppressModalLifecycleModalIds.add(modalId);
            });
        },

        pruneModalLifecycleSuppression(now = Date.now()) {
            if (now < this._suppressModalLifecycleEffectsUntil) {
                return;
            }

            this._suppressModalLifecycleEffectsUntil = 0;
            this._suppressModalLifecycleModalIds.clear();
        },

        handleModalLifecycleEvent(kind, event) {
            if (!this.isAnyQuranReaderViewOpen()) {
                this.recoverStaleModalLifecycleState();
                this.pruneModalLifecycleSuppression();
                this.clearPendingPostModalTargetFit();

                return;
            }

            this.trackModalLifecycle(kind, event);
            const isSearchModalEvent = this.isSearchModalEvent(kind, event);
            const isHistoryModalEvent = this.isHistoryModalEvent(kind, event);
            const isBookmarksModalEvent = this.isBookmarksModalEvent(kind, event);
            const isJumpPageModalEvent = this.isJumpPageModalEvent(kind, event);
            let shouldSyncManagerModalsVisibility = false;

            if (kind === 'opened' || kind === 'closing' || kind === 'closed') {
                this.$nextTick(() => {
                    this.syncSupportLockTargetsUi();
                });
            }

            if (kind === 'opened') {
                this.$nextTick(() => {
                    this.queueJumpPageModalInputSync();
                });
            }

            if (kind === 'closing' || kind === 'closed') {
                this.queueSearchModalCloseSync({
                    delayMs: kind === 'closed' ? 0 : 96,
                });
            }

            if (isJumpPageModalEvent) {
                if (kind === 'opened') {
                    this.jumpPageModalOpen = true;
                    shouldSyncManagerModalsVisibility = true;
                }

                if (kind === 'closing' || kind === 'closed') {
                    window.setTimeout(
                        () => {
                            const isStillVisible = this.isJumpPageInputVisible();

                            if (isStillVisible) {
                                this.jumpPageModalOpen = true;

                                return;
                            }

                            this.jumpPageModalOpen = false;
                            this.dispatchManagerModalsVisibilityState();
                        },
                        kind === 'closed' ? 0 : 48,
                    );
                    shouldSyncManagerModalsVisibility = true;
                }
            }

            if (isHistoryModalEvent) {
                if (kind === 'opened') {
                    this.historyModalOpen = true;
                    this.$nextTick(() => {
                        this.ensureHistoryRowsAnimations();
                        this.queueHistoryManagerTableSync();
                    });
                }

                if (kind === 'closing' || kind === 'closed') {
                    this.clearHistoryManagerSyncQueue();
                }

                if (kind === 'closed') {
                    this.historyModalOpen = false;
                    this.teardownHistoryRowsAnimations();
                }

                shouldSyncManagerModalsVisibility = true;
            }

            if (isBookmarksModalEvent) {
                if (kind === 'opened') {
                    this.bookmarksModalOpen = true;
                    this.$nextTick(() => {
                        this.ensureBookmarksRowsAnimations();
                        this.queueBookmarksManagerTableSync();
                    });
                }

                if (kind === 'closing' || kind === 'closed') {
                    this.clearBookmarksManagerSyncQueue();
                }

                if (kind === 'closed') {
                    this.bookmarksModalOpen = false;
                    this.teardownBookmarksRowsAnimations();
                }

                shouldSyncManagerModalsVisibility = true;
            }

            if (shouldSyncManagerModalsVisibility) {
                this.dispatchManagerModalsVisibilityState();
            }

            if (!isSearchModalEvent) {
                if (
                    (kind === 'closing' || kind === 'closed') &&
                    this.search.modalOpen &&
                    !this.isSearchModalWindowVisible()
                ) {
                    this.handleSearchModalClosed();
                }

                return;
            }

            if (kind === 'opened') {
                this.clearPendingPostModalTargetFit();

                if (this._searchModalOpenInFlight) {
                    return;
                }

                if (this.search.modalOpen) {
                    this.handleSearchModalClosed();
                }

                this._searchModalOpenInFlight = Promise.resolve(this.handleSearchModalOpened())
                    .catch(() => {
                        //
                    })
                    .finally(() => {
                        this._searchModalOpenInFlight = null;
                    });

                return;
            }

            if (kind === 'closing' || kind === 'closed') {
                this.handleSearchModalClosed();

                if (this._postModalTargetFitPage > 0) {
                    this.schedulePendingModalCloseFit(this._postModalTargetFitPage, {
                        retries: kind === 'closed' ? 42 : 18,
                        delayMs: kind === 'closed' ? 90 : 110,
                        revealDelayMs: 230,
                        maxAttempts: 6,
                    });
                }
            }
        },

        ensureHistoryRowsAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._historyRowsAutoAnimateStop === 'function') {
                return;
            }

            const historyRowsContainer = this.$refs.historyRowsList;

            if (!(historyRowsContainer instanceof Element)) {
                return;
            }

            this._historyRowsAutoAnimateStop = window.autoAnimate(historyRowsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        ensureBookmarksRowsAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._bookmarksRowsAutoAnimateStop === 'function') {
                return;
            }

            const bookmarksRowsContainer = this.$refs.bookmarksRowsList;

            if (!(bookmarksRowsContainer instanceof Element)) {
                return;
            }

            this._bookmarksRowsAutoAnimateStop = window.autoAnimate(bookmarksRowsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        teardownHistoryRowsAnimations() {
            if (typeof this._historyRowsAutoAnimateStop !== 'function') {
                return;
            }

            this._historyRowsAutoAnimateStop();
            this._historyRowsAutoAnimateStop = null;
        },

        teardownBookmarksRowsAnimations() {
            if (typeof this._bookmarksRowsAutoAnimateStop !== 'function') {
                return;
            }

            this._bookmarksRowsAutoAnimateStop();
            this._bookmarksRowsAutoAnimateStop = null;
        },

        teardownSearchResultAnimations() {
            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                this._searchResultsAutoAnimateStop();
                this._searchResultsAutoAnimateStop = null;
            }
        },
        ensureSearchResultAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                return;
            }

            const searchResultsContainer = this.$refs.searchResultsList;

            if (!(searchResultsContainer instanceof Element)) {
                return;
            }

            this._searchResultsAutoAnimateStop = window.autoAnimate(searchResultsContainer, {
                duration: 260,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        async handleSearchModalOpened() {
            await this.warmSearchIndex();
            this.search.modalOpen = true;
            this._lastKnownModalOpenState = true;
            this._skipNextSearchModalCloseLayout = false;
            this.refreshSurahTriggerCaption(false);
            this.dispatchManagerModalsVisibilityState();

            if (this.search.preserveActiveSurahOnNextOpen) {
                this.search.preserveActiveSurahOnNextOpen = false;
            } else {
                this.syncSearchActiveSurahNumber();
            }

            this.search.query = '';
            this.setSearchResults([], { immediate: true });
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;

            await this.nextTickAsync();
            this.setupSearchStreamObserver();
            this.clearSearchStreamTarget();
            this.ensureSearchResultAnimations();
            this.bindSearchModalInputSyncListener();
            this.searchModalInputElement()?.focus?.();
            this.queueSurahDirectoryAutoFocus();
            this._surahDirectoryPostOpenTimers = [260, 560, 920].map((delayMs) =>
                window.setTimeout(() => {
                    if (!this.search.modalOpen) {
                        return;
                    }

                    this.scrollSurahDirectoryToActive({ behavior: 'auto' });
                }, delayMs),
            );
        },

        handleSearchModalClosed() {
            this.cancelSurahDirectoryAutoFocus();
            this.clearSearchResultsUpdateQueue();
            this.unbindSearchModalInputSyncListener();
            this.teardownSearchStreamObserver();
            this.clearSearchStreamTarget();
            this.teardownSearchResultAnimations();
            this._searchRequestSerial += 1;
            this._searchRequestInFlight = false;
            this._searchQueuedNormalizedQuery = null;
            this.search.modalOpen = false;
            this._lastKnownModalOpenState = false;
            this.search.query = '';
            this.setSearchResults([], { immediate: true });
            this.search.isLoading = false;
            this.dispatchManagerModalsVisibilityState();

            if (this._searchModalCloseDebounceTimer !== null) {
                clearTimeout(this._searchModalCloseDebounceTimer);
                this._searchModalCloseDebounceTimer = null;
            }

            if (this._skipNextSearchModalCloseLayout) {
                this._skipNextSearchModalCloseLayout = false;

                return;
            }
        },

        async requestModalCloseByKnownIds(
            knownModalIds = [],
            { onFallback = null, isModalStillVisible = null } = {},
        ) {
            const modalId = knownModalIds
                .map((value) => String(value ?? '').trim())
                .find((value) => value !== '');

            if (modalId) {
                window.dispatchEvent(
                    new CustomEvent('close-modal', {
                        detail: {
                            id: modalId,
                        },
                    }),
                );
                await wait(12);

                const modalRemainsVisible =
                    typeof isModalStillVisible === 'function'
                        ? Boolean(isModalStillVisible())
                        : this.isModalWindowVisibleById(modalId);

                if (!modalRemainsVisible) {
                    return;
                }
            }

            if (typeof this.$wire?.unmountAction === 'function') {
                try {
                    await this.$wire.unmountAction(false);

                    return;
                } catch (_) {
                    //
                }
            }

            if (typeof onFallback === 'function') {
                onFallback();
            }
        },

        async requestSearchModalClose({ skipLayout = false } = {}) {
            if (skipLayout) {
                this._skipNextSearchModalCloseLayout = true;
            }

            await this.requestModalCloseByKnownIds(
                [this.searchActionModalId, this.searchModalId, this.searchModalDomId],
                {
                    onFallback: () => {
                        this.handleSearchModalClosed();
                    },
                    isModalStillVisible: () => this.isSearchModalWindowVisible(),
                },
            );

            if (this.isSearchModalWindowVisible()) {
                window.dispatchEvent(new CustomEvent('close-modal'));
                await wait(24);
            }

            if (this.search.modalOpen && !this.isSearchModalWindowVisible()) {
                this.handleSearchModalClosed();
            }
        },

        async waitForModalLifecycleToSettle(maxAttempts = 14, delayMs = 24) {
            const attempts = Math.max(1, Math.trunc(Number(maxAttempts) || 14));
            const waitDelayMs = Math.max(12, Math.trunc(Number(delayMs) || 24));

            for (let attempt = 0; attempt < attempts; attempt += 1) {
                if (this.openModalCount() <= 0) {
                    this.recoverStaleModalLifecycleState();
                }

                if (
                    this.openModalCount() <= 0 &&
                    !this._isModalLifecycleSettling &&
                    this._activeModalIds.size === 0
                ) {
                    return true;
                }

                await wait(waitDelayMs);
            }

            if (this.openModalCount() <= 0) {
                this.recoverStaleModalLifecycleState();
            }

            return (
                this.openModalCount() <= 0 &&
                !this._isModalLifecycleSettling &&
                this._activeModalIds.size === 0
            );
        },

        async requestHistoryModalClose() {
            await this.requestModalCloseByKnownIds([this.historyModalId], {
                onFallback: () => {
                    this.historyModalOpen = false;
                    this.teardownHistoryRowsAnimations();
                    this.dispatchManagerModalsVisibilityState();
                },
            });
        },

        async requestBookmarksModalClose() {
            await this.requestModalCloseByKnownIds([this.bookmarksModalId], {
                onFallback: () => {
                    this.bookmarksModalOpen = false;
                    this.teardownBookmarksRowsAnimations();
                    this.dispatchManagerModalsVisibilityState();
                },
            });
        },

        requestReaderGateNavigation(_source = 'generic') {
            this.resetSwipeState();
            this.clearWordPressState();
            void this.requestSearchModalClose({ skipLayout: true });
            window.dispatchEvent(new CustomEvent('quran-go-gate'));
        },

        async goToHistoryEntry(entry) {
            const targetPage = clampPage(Number(entry?.page_number ?? 1), this.maxPage);
            const ayahIndex = Math.max(0, Math.trunc(Number(entry?.ayah_index ?? 0)));

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.suppressModalLifecycleEffects([this.historyModalId], {
                durationMs: historyNavigationModalLifecycleSuppressionDurationMs,
            });
            await this.requestHistoryModalClose();
            await this.waitForModalLifecycleToSettle();
            await wait(modalCloseTransitionDelayMs);
            this.suppressModalLifecycleEffects([this.historyModalId], {
                durationMs: historyNavigationModalLifecycleSuppressionDurationMs,
            });
            this._bypassNextFitCache = true;
            await this.goToPageFromChevron(targetPage, {
                activeAyahIndex: ayahIndex,
                source: 'history-entry',
                commitNow: true,
                settleDelayMs: 0,
            });

            const shouldQueuePostModalFit =
                !this.isCurrentPageVisiblyReady() || this._lastFittedPageNumber !== this.pageNumber;

            if (shouldQueuePostModalFit) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 160,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            } else if (this.hasRenderablePage()) {
                this._bypassNextFitCache = true;
                this.fitPageToViewport();
                this.applySafetyScaleForCurrentPageOverflow();
                this._lastPageRevealAt = Date.now();
            }

            this.activeWordIndex = 0;
        },

        async goToBookmark(bookmark) {
            const targetPage = clampPage(Number(bookmark?.page_number ?? 1), this.maxPage);

            this.resetNavigationQueueForPriorityJump();
            this.clearPendingPostModalTargetFit();
            this.suppressModalLifecycleEffects([this.bookmarksModalId]);
            await this.requestBookmarksModalClose();
            await this.waitForModalLifecycleToSettle();
            await wait(modalCloseTransitionDelayMs);
            this._bypassNextFitCache = true;
            await this.goToPageFromChevron(targetPage, {
                activeAyahIndex: 0,
                source: 'bookmark',
                commitNow: true,
                settleDelayMs: 0,
            });

            const shouldQueuePostModalFit =
                !this.isCurrentPageVisiblyReady() || this._lastFittedPageNumber !== this.pageNumber;

            if (shouldQueuePostModalFit) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 160,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            } else if (this.hasRenderablePage()) {
                this._bypassNextFitCache = true;
                this.fitPageToViewport();
                this.applySafetyScaleForCurrentPageOverflow();
                this._lastPageRevealAt = Date.now();
            }
            this.activeAyahIndex = 0;
            this.activeWordIndex = 0;
            this.recordNavigationHistory({
                source: 'bookmark-navigation',
                pageNumber: targetPage,
                surahNumber: this.currentSurahNumber(),
            });
        },

        async confirmSearchSelection() {
            if (!this.search.readyResult) {
                return;
            }

            await this.goToSearchResult(this.search.readyResult);
        },

        async goToSurahFromDirectory(entry) {
            const pageNumber = clampPage(Number(entry?.page_number ?? 1), this.maxPage);
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));

            this.search.activeSurahNumber = surahNumber;
            this.search.preserveActiveSurahOnNextOpen = true;

            this.resetNavigationQueueForPriorityJump();
            await this.requestSearchModalClose();
            await this.waitForModalLifecycleToSettle();
            await wait(modalCloseTransitionDelayMs);
            this.activeAyahIndex = 0;
            this.activeWordIndex = 0;
            this._bypassNextFitCache = true;
            await this.goToPageFromChevron(pageNumber, {
                activeAyahIndex: 0,
                source: 'surah-directory',
                commitNow: true,
                settleDelayMs: 0,
            });
            if (this._lastFittedPageNumber !== this.pageNumber) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 160,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            }
            this.search.activeSurahNumber = surahNumber;
            this.activeAyahIndex = 0;
            this.activeWordIndex = 0;
            this.recordNavigationHistory({
                source: 'surah-directory',
                pageNumber,
                surahNumber,
            });
        },

        scheduleManagerModalsPrewarm(delayMs = 220) {
            const normalizedDelay = Math.max(0, Math.trunc(Number(delayMs) || 0));

            window.setTimeout(() => {
                if (
                    this._managerModalsPrewarmed ||
                    this._managerModalsPrewarmPromise !== null ||
                    this.historyModalOpen ||
                    this.bookmarksModalOpen ||
                    this.search.modalOpen ||
                    this.jumpPageModalOpen
                ) {
                    return;
                }

                const runPrewarm = () => {
                    void this.prewarmManagerModals();
                };

                if (typeof window.requestIdleCallback === 'function') {
                    window.requestIdleCallback(runPrewarm, { timeout: 640 });

                    return;
                }

                runPrewarm();
            }, normalizedDelay);
        },

        async prewarmManagerModals() {
            if (this._managerModalsPrewarmed) {
                return;
            }

            if (this._managerModalsPrewarmPromise !== null) {
                await this._managerModalsPrewarmPromise;

                return;
            }

            if (typeof this.$wire?.prewarmManagerModals !== 'function') {
                return;
            }

            this._managerModalsPrewarmPromise = (async () => {
                try {
                    await this.$wire.prewarmManagerModals();
                    this._managerModalsPrewarmed = true;
                } catch (_) {
                    // Ignore background prewarm failures.
                } finally {
                    this._managerModalsPrewarmPromise = null;
                }
            })();

            await this._managerModalsPrewarmPromise;
        },

        searchRequestUrl(query = '') {
            const baseUrl = String(this.api.searchIndexUrl ?? '').trim();

            if (!baseUrl) {
                return '';
            }

            if (!query) {
                return baseUrl;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', query);

            return url.toString();
        },

        async warmSearchIndex() {
            if (this.search.isReady || this.search.isLoading || !this.api.searchIndexUrl) {
                return;
            }

            if (this._searchIndexPromise) {
                await this._searchIndexPromise;

                return;
            }

            this.search.isLoading = true;

            this._searchIndexPromise = (async () => {
                try {
                    const payload = await fetchJsonWithCache({
                        url: this.searchRequestUrl(),
                        cacheName: this.cacheNames.search,
                        preferCache: true,
                    });

                    if (
                        payload &&
                        typeof payload === 'object' &&
                        payload.surah_names &&
                        Object.keys(payload.surah_names).length > 0
                    ) {
                        this.search.surahNames = payload.surah_names;
                    }

                    let surahDirectory = Array.isArray(payload?.surah_directory)
                        ? payload.surah_directory
                        : [];

                    if (
                        surahDirectory.length === 0 &&
                        Array.isArray(payload?.items) &&
                        payload.items.length > 0
                    ) {
                        surahDirectory = this.deriveSurahDirectoryFromItems(payload.items);
                    }

                    this.buildSurahDirectory(surahDirectory);
                    this.refreshSurahTriggerCaption(false);
                    this.search.isReady = true;
                } catch (_) {
                    if (
                        !Array.isArray(this.search.surahDirectory) ||
                        this.search.surahDirectory.length === 0
                    ) {
                        this.buildSurahDirectory([]);
                    }

                    this.search.isReady = false;
                } finally {
                    this.search.isLoading = false;
                    this._searchIndexPromise = null;
                }
            })();

            await this._searchIndexPromise;
        },

        async updateSearchResults() {
            const normalizedQuery = this.normalizeSearchQuery(this.search.query);

            if (!normalizedQuery) {
                this._searchQueuedNormalizedQuery = null;
                this.setSearchResults([], { immediate: true });
                this.search.isLoading = false;
                this.search.streamHasUpdates = false;
                this._searchRequestSerial += 1;
                this.clearSearchStreamTarget();

                return;
            }

            if (normalizedQuery.length < this.search.minQueryLength) {
                this._searchQueuedNormalizedQuery = null;
                this.setSearchResults([], { immediate: true });
                this.search.isLoading = false;
                this.search.streamHasUpdates = false;
                this._searchRequestSerial += 1;
                this.clearSearchStreamTarget();

                return;
            }

            if (!this.search.isReady) {
                await this.warmSearchIndex();
            }

            if (!this.search.isReady) {
                this._searchQueuedNormalizedQuery = null;
                this.setSearchResults([], { immediate: true });
                this.search.isLoading = false;
                this.search.streamHasUpdates = false;
                this._searchRequestSerial += 1;
                this.clearSearchStreamTarget();

                return;
            }

            if (this._searchRequestInFlight) {
                this._searchQueuedNormalizedQuery = normalizedQuery;

                return;
            }

            const isSearchModalVisible = this.search.modalOpen || this.isSearchModalWindowVisible();

            if (!isSearchModalVisible) {
                return;
            }

            if (!this.search.modalOpen) {
                this.search.modalOpen = true;
                this._lastKnownModalOpenState = true;
            }

            const requestSerial = ++this._searchRequestSerial;
            this._searchRequestInFlight = true;
            this._searchQueuedNormalizedQuery = null;
            this.search.isLoading = true;
            this.search.streamHasUpdates = false;
            this.clearSearchStreamTarget();

            try {
                const livewireResults = await this.$wire.streamSearch(
                    normalizedQuery,
                    requestSerial,
                );
                const results = Array.isArray(livewireResults) ? livewireResults.slice(0, 24) : [];

                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                this.setSearchResults(
                    this.search.streamHasUpdates
                        ? this.mergeSearchResults(this.activeSearchResults(), results)
                        : results,
                );
                this.$nextTick(() => {
                    this.ensureSearchResultAnimations();
                });
            } catch (error) {
                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                this.setSearchResults([], { immediate: true });
            } finally {
                if (requestSerial === this._searchRequestSerial) {
                    this.search.isLoading = false;
                }

                this._searchRequestInFlight = false;

                const normalizedCurrentQuery = this.normalizeSearchQuery(this.search.query);
                const queuedQuery = String(this._searchQueuedNormalizedQuery ?? '').trim();
                const nextQuery = queuedQuery !== '' ? queuedQuery : normalizedCurrentQuery;
                const shouldQueueFollowUpSearch =
                    (this.search.modalOpen || this.isSearchModalWindowVisible()) &&
                    nextQuery !== '' &&
                    nextQuery.length >= this.search.minQueryLength &&
                    nextQuery !== normalizedQuery;

                this._searchQueuedNormalizedQuery = null;

                if (shouldQueueFollowUpSearch) {
                    void this.updateSearchResults();
                }
            }
        },

        async goToSearchResult(result) {
            const targetPage = clampPage(Number(result?.page_number ?? 1), this.maxPage);
            const ayahIndex = Math.max(0, Math.trunc(Number(result?.ayah_index ?? 0)));
            const activeQuery = this.search.query;
            const surahNumber = Math.max(1, Math.trunc(Number(result?.surah_number ?? 1)));
            const ayahNumber = Math.max(0, Math.trunc(Number(result?.ayah_number ?? 0)));

            this.resetNavigationQueueForPriorityJump();
            await this.requestSearchModalClose();
            await this.waitForModalLifecycleToSettle();
            await wait(modalCloseTransitionDelayMs);
            this._bypassNextFitCache = true;
            await this.goToPageFromChevron(targetPage, {
                activeAyahIndex: ayahIndex,
                searchHighlightAyahIndex: ayahIndex,
                source: 'search-result',
                commitNow: true,
                settleDelayMs: 0,
            });
            if (this._lastFittedPageNumber !== this.pageNumber) {
                this._bypassNextFitCache = true;
                await this.layoutPageGuaranteed({
                    revealDelayMs: 160,
                    maxAttempts: 3,
                    useIdleFit: false,
                });
            }
            this.activeWordIndex = 0;
            this.recordNavigationHistory({
                source: 'search-result',
                pageNumber: targetPage,
                surahNumber,
                ayahNumber,
                ayahIndex,
                query: activeQuery,
            });
        },
    }));
});
