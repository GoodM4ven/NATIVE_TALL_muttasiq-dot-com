import {
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
} from '../../athkar-app-overrides';
import { createShimmerController } from '../../shimmer';

export {
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
};

export const doesEnableVisualEnhancementsKey = 'enable_visual_enhancements';
export const skipGuidancePanelsSettingKey = 'does_skip_notice_panels';
export const progressStorageKey = 'athkar-progress-v1';
export const supportUnlockStorageKey = 'quran-support-unlock-v1';
export const supportUnlockModePermanent = 'permanent';
export const supportUnlockModeWeekly = 'weekly';
export const athkarCopyHoldDelayMs = 1250;
export const athkarCopyHoldMoveThresholdPx = 16;
export const athkarCopyPopoverVisibleDurationMs = 920;

export const defaultProgressState = () => ({
    sabah: { index: 0, counts: [], ids: [], activeId: null },
    masaa: { index: 0, counts: [], ids: [], activeId: null },
});

export const emptyProgressStats = Object.freeze({
    totalRequiredCount: 0,
    totalCompletedCount: 0,
    totalRequiredLetters: 0,
    totalCompletedLetters: 0,
    totalRemainingLetters: 0,
    slideProgressPercent: 0,
    maxNavigableIndex: 0,
});

export const resolveProgressStatsSafely = (context) => {
    if (typeof context?.resolveProgressStats !== 'function') {
        return emptyProgressStats;
    }

    try {
        return context.resolveProgressStats() ?? emptyProgressStats;
    } catch (_) {
        return emptyProgressStats;
    }
};

export const readProgressFromStorage = () => {
    if (typeof localStorage === 'undefined') {
        return defaultProgressState();
    }

    try {
        const parsed = JSON.parse(localStorage.getItem(progressStorageKey) ?? 'null');

        if (!parsed || typeof parsed !== 'object') {
            return defaultProgressState();
        }

        return {
            sabah:
                parsed.sabah && typeof parsed.sabah === 'object'
                    ? {
                          index: Number(parsed.sabah.index ?? 0),
                          counts: Array.isArray(parsed.sabah.counts) ? parsed.sabah.counts : [],
                          ids: Array.isArray(parsed.sabah.ids) ? parsed.sabah.ids : [],
                          activeId: parsed.sabah.activeId ?? null,
                      }
                    : { index: 0, counts: [], ids: [], activeId: null },
            masaa:
                parsed.masaa && typeof parsed.masaa === 'object'
                    ? {
                          index: Number(parsed.masaa.index ?? 0),
                          counts: Array.isArray(parsed.masaa.counts) ? parsed.masaa.counts : [],
                          ids: Array.isArray(parsed.masaa.ids) ? parsed.masaa.ids : [],
                          activeId: parsed.masaa.activeId ?? null,
                      }
                    : { index: 0, counts: [], ids: [], activeId: null },
        };
    } catch (_) {
        return defaultProgressState();
    }
};
