const athkarSettingsStorageKey = 'athkar-settings-v1';
const athkarSettingsUserOverridesStorageKey = 'athkar-settings-user-overrides-v1';
const athkarOverridesStorageKey = 'athkar-overrides-v1';
const visualEnhancementsSettingKey = 'enable_visual_enhancements';
const legacyVisualEnhancementsSettingKey = 'does_enable_main_text_shimmering';
const minimumMainTextSizeKey = 'minimum_main_text_size';
const maximumMainTextSizeKey = 'maximum_main_text_size';
const fallbackMainTextSizeLimits = Object.freeze({
    [minimumMainTextSizeKey]: Object.freeze({
        min: 10,
        max: 28,
        default: 16,
    }),
    [maximumMainTextSizeKey]: Object.freeze({
        min: 10,
        max: 28,
        default: 24,
    }),
});

const toFiniteInteger = (value, fallback) => {
    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return fallback;
    }

    return Math.trunc(numeric);
};

const normalizeMainTextSizeLimits = (value, fallback) => {
    const minimum = toFiniteInteger(value?.min, fallback.min);
    const maximumSeed = toFiniteInteger(value?.max, fallback.max);
    const maximum = Math.max(minimum, maximumSeed);
    const defaultSeed = toFiniteInteger(value?.default, fallback.default);

    return {
        min: minimum,
        max: maximum,
        default: Math.max(minimum, Math.min(maximum, defaultSeed)),
    };
};

const resolveMainTextSizeLimits = () => {
    if (typeof window === 'undefined') {
        return fallbackMainTextSizeLimits;
    }

    const limits = window.athkarMainTextSizeLimits;

    if (!limits || typeof limits !== 'object') {
        return fallbackMainTextSizeLimits;
    }

    return {
        [minimumMainTextSizeKey]: normalizeMainTextSizeLimits(
            limits?.[minimumMainTextSizeKey],
            fallbackMainTextSizeLimits[minimumMainTextSizeKey],
        ),
        [maximumMainTextSizeKey]: normalizeMainTextSizeLimits(
            limits?.[maximumMainTextSizeKey],
            fallbackMainTextSizeLimits[maximumMainTextSizeKey],
        ),
    };
};

const normalizeBooleanSettingValue = (value, fallback) => {
    if (typeof value === 'boolean') {
        return value;
    }

    if (value === 1 || value === '1') {
        return true;
    }

    if (value === 0 || value === '0') {
        return false;
    }

    if (value === undefined || value === null || value === '') {
        return Boolean(fallback);
    }

    const normalized = String(value).trim().toLowerCase();

    if (normalized === 'true' || normalized === 'yes' || normalized === 'on') {
        return true;
    }

    if (normalized === 'false' || normalized === 'no' || normalized === 'off') {
        return false;
    }

    return Boolean(fallback);
};

const normalizeIntegerSettingValue = (key, value, fallback) => {
    const numeric = Number.isFinite(Number(value)) ? Number(value) : Number(fallback);
    let normalized = Number.isFinite(numeric) ? Math.trunc(numeric) : Number(fallback);

    if (key === minimumMainTextSizeKey || key === maximumMainTextSizeKey) {
        const mainTextSizeLimits = resolveMainTextSizeLimits()[key];

        if (mainTextSizeLimits) {
            normalized = Math.max(
                mainTextSizeLimits.min,
                Math.min(mainTextSizeLimits.max, normalized),
            );
        }
    }

    return normalized;
};

const normalizeAthkarSettingValue = (key, value, defaultValue) => {
    if (typeof defaultValue === 'boolean') {
        return normalizeBooleanSettingValue(value, defaultValue);
    }

    if (Number.isFinite(Number(defaultValue))) {
        return normalizeIntegerSettingValue(key, value, defaultValue);
    }

    const normalized = String(value ?? '').trim();

    return normalized === '' ? defaultValue : normalized;
};

const normalizeAthkarSettings = (settings, defaults) => {
    if (!defaults || typeof defaults !== 'object' || Object.keys(defaults).length === 0) {
        if (!settings || typeof settings !== 'object') {
            return {};
        }

        return { ...settings };
    }

    const normalized = { ...defaults };

    if (!settings || typeof settings !== 'object') {
        return normalized;
    }

    Object.keys(defaults).forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(settings, key)) {
            normalized[key] = normalizeAthkarSettingValue(key, settings[key], defaults[key]);
            return;
        }

        normalized[key] = normalizeAthkarSettingValue(key, defaults[key], defaults[key]);
    });

    if (
        Number.isFinite(Number(normalized[minimumMainTextSizeKey])) &&
        Number.isFinite(Number(normalized[maximumMainTextSizeKey]))
    ) {
        const minimum = Number(normalized[minimumMainTextSizeKey]);
        const maximum = Number(normalized[maximumMainTextSizeKey]);
        const safeMinimum = Math.min(minimum, maximum);
        const safeMaximum = Math.max(minimum, maximum);

        normalized[minimumMainTextSizeKey] = safeMinimum;
        normalized[maximumMainTextSizeKey] = safeMaximum;
    }

    return normalized;
};

const resolveAthkarSettingsDefaults = () => {
    if (typeof window === 'undefined') {
        return {};
    }

    const defaults = window.athkarSettingsDefaults;

    if (!defaults || typeof defaults !== 'object') {
        return {};
    }

    return defaults;
};

const migrateLegacyVisualEnhancementsSettingKey = (settings) => {
    if (!settings || typeof settings !== 'object' || Array.isArray(settings)) {
        return {
            settings: {},
            wasMigrated: false,
        };
    }

    const normalized = { ...settings };
    const hasLegacyKey = Object.prototype.hasOwnProperty.call(
        normalized,
        legacyVisualEnhancementsSettingKey,
    );
    const hasCurrentKey = Object.prototype.hasOwnProperty.call(
        normalized,
        visualEnhancementsSettingKey,
    );

    if (hasLegacyKey && !hasCurrentKey) {
        normalized[visualEnhancementsSettingKey] = normalized[legacyVisualEnhancementsSettingKey];
    }

    if (!hasLegacyKey) {
        return {
            settings: normalized,
            wasMigrated: false,
        };
    }

    delete normalized[legacyVisualEnhancementsSettingKey];

    return {
        settings: normalized,
        wasMigrated: true,
    };
};

const readAthkarSettingsFromStorage = (defaults = resolveAthkarSettingsDefaults()) => {
    if (typeof localStorage === 'undefined') {
        return normalizeAthkarSettings({}, defaults);
    }

    try {
        const raw = localStorage.getItem(athkarSettingsStorageKey);

        if (!raw) {
            return normalizeAthkarSettings({}, defaults);
        }

        const migrated = migrateLegacyVisualEnhancementsSettingKey(JSON.parse(raw));

        if (migrated.wasMigrated) {
            localStorage.setItem(athkarSettingsStorageKey, JSON.stringify(migrated.settings));
        }

        return normalizeAthkarSettings(migrated.settings, defaults);
    } catch (_) {
        return normalizeAthkarSettings({}, defaults);
    }
};

const writeAthkarSettingsToStorage = (settings, defaults = resolveAthkarSettingsDefaults()) => {
    const normalized = normalizeAthkarSettings(settings, defaults);

    if (typeof localStorage === 'undefined') {
        return normalized;
    }

    try {
        localStorage.setItem(athkarSettingsStorageKey, JSON.stringify(normalized));
    } catch (_) {
        return normalized;
    }

    return normalized;
};

const readUserSettingsOverrides = () => {
    if (typeof localStorage === 'undefined') {
        return {};
    }

    try {
        const raw = localStorage.getItem(athkarSettingsUserOverridesStorageKey);

        if (!raw) {
            return {};
        }

        const migrated = migrateLegacyVisualEnhancementsSettingKey(JSON.parse(raw));

        if (Object.keys(migrated.settings).length === 0) {
            return {};
        }

        if (migrated.wasMigrated) {
            localStorage.setItem(
                athkarSettingsUserOverridesStorageKey,
                JSON.stringify(migrated.settings),
            );
        }

        return migrated.settings;
    } catch (_) {
        return {};
    }
};

const writeUserSettingsOverrides = (overrides) => {
    if (typeof localStorage === 'undefined') {
        return;
    }

    try {
        const safe =
            overrides && typeof overrides === 'object' && !Array.isArray(overrides)
                ? overrides
                : {};
        localStorage.setItem(athkarSettingsUserOverridesStorageKey, JSON.stringify(safe));
    } catch (_) {
        // Silently fail
    }
};

const writeUserSettingOverride = (key, value) => {
    const overrides = readUserSettingsOverrides();
    const normalizedKey =
        key === legacyVisualEnhancementsSettingKey ? visualEnhancementsSettingKey : key;
    overrides[normalizedKey] = value;

    if (normalizedKey === visualEnhancementsSettingKey) {
        delete overrides[legacyVisualEnhancementsSettingKey];
    }

    writeUserSettingsOverrides(overrides);
};

const resolveEffectiveSettings = (serverDefaults) => {
    const defaults = serverDefaults && typeof serverDefaults === 'object' ? serverDefaults : {};
    const shouldMigrateSettings = Object.keys(defaults).length > 0;

    if (shouldMigrateSettings) {
        migrateSettingsOverrides(defaults);
    }

    const userOverrides = readUserSettingsOverrides();
    const merged = { ...defaults };

    Object.keys(userOverrides).forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(defaults, key)) {
            merged[key] = userOverrides[key];
        }
    });

    return normalizeAthkarSettings(merged, defaults);
};

const migrateSettingsOverrides = (serverDefaults) => {
    if (typeof localStorage === 'undefined') {
        return;
    }

    const defaults = serverDefaults && typeof serverDefaults === 'object' ? serverDefaults : {};

    if (Object.keys(defaults).length === 0) {
        return;
    }

    const existing = localStorage.getItem(athkarSettingsUserOverridesStorageKey);

    if (existing !== null) {
        return;
    }

    const currentSettings = readAthkarSettingsFromStorage(defaults);
    const overrides = {};

    Object.keys(defaults).forEach((key) => {
        if (!Object.prototype.hasOwnProperty.call(currentSettings, key)) {
            return;
        }

        const currentValue = currentSettings[key];
        const defaultValue = defaults[key];

        if (typeof defaultValue === 'boolean') {
            if (Boolean(currentValue) !== Boolean(defaultValue)) {
                overrides[key] = Boolean(currentValue);
            }

            return;
        }

        if (Number.isFinite(Number(defaultValue))) {
            if (Number(currentValue) !== Number(defaultValue)) {
                overrides[key] = Number(currentValue);
            }

            return;
        }

        if (currentValue !== defaultValue) {
            overrides[key] = currentValue;
        }
    });

    writeUserSettingsOverrides(overrides);
};

const normalizeAthkarTime = (value, fallback = 'shared') => {
    const nextValue = String(value ?? fallback);

    if (nextValue === 'sabah' || nextValue === 'masaa' || nextValue === 'shared') {
        return nextValue;
    }

    return fallback;
};

const normalizeAthkarType = (value, fallback = 'glorification') => {
    const nextValue = String(value ?? fallback);

    if (
        nextValue === 'glorification' ||
        nextValue === 'gratitude' ||
        nextValue === 'repentance' ||
        nextValue === 'supplication' ||
        nextValue === 'protection'
    ) {
        return nextValue;
    }

    return fallback;
};

const normalizeAthkarOrigin = (value) => {
    if (value === null || value === undefined) {
        return null;
    }

    const normalized = String(value).trim();

    return normalized === '' ? null : normalized;
};

const hasAthkarOrigin = (origin) => normalizeAthkarOrigin(origin) !== null;

const normalizeAthkarCount = (value, fallback = 1) => {
    const count = Number(value ?? fallback);

    if (!Number.isFinite(count) || count < 1) {
        return Math.max(1, Number(fallback || 1));
    }

    return Math.floor(count);
};

const normalizeAthkarOrder = (value, fallback = 1) => {
    const order = Number(value ?? fallback);

    if (!Number.isFinite(order) || order < 1) {
        return Math.max(1, Number(fallback || 1));
    }

    return Math.floor(order);
};

const normalizeAthkarDefaults = (athkar) => {
    if (!Array.isArray(athkar)) {
        return [];
    }

    return athkar
        .map((item, index) => {
            const id = Number(item?.id ?? 0);

            if (!Number.isInteger(id) || id <= 0) {
                return null;
            }

            return {
                id,
                time: normalizeAthkarTime(item?.time),
                type: normalizeAthkarType(item?.type),
                text: String(item?.text ?? '').trim(),
                origin: normalizeAthkarOrigin(item?.origin),
                is_aayah: Boolean(item?.is_aayah ?? item?.is_quran),
                is_original: hasAthkarOrigin(item?.origin),
                count: normalizeAthkarCount(item?.count, 1),
                order: normalizeAthkarOrder(item?.order, index + 1),
            };
        })
        .filter(Boolean)
        .sort((left, right) => left.order - right.order || left.id - right.id);
};

const normalizeAthkarOverride = (override) => {
    const thikrId = Number(override?.thikr_id ?? 0);

    if (!Number.isInteger(thikrId) || thikrId <= 0) {
        return null;
    }

    const order =
        override?.order === null || override?.order === undefined || override?.order === ''
            ? null
            : normalizeAthkarOrder(override.order, 1);
    const count =
        override?.count === null || override?.count === undefined || override?.count === ''
            ? null
            : normalizeAthkarCount(override.count, 1);
    const time =
        override?.time === null || override?.time === undefined || override?.time === ''
            ? null
            : normalizeAthkarTime(override.time);
    const type =
        override?.type === null || override?.type === undefined || override?.type === ''
            ? null
            : normalizeAthkarType(override.type);
    const textRaw =
        override?.text === null || override?.text === undefined
            ? null
            : String(override.text).trim();
    const text = textRaw === '' ? null : textRaw;
    const origin =
        override?.origin === null || override?.origin === undefined
            ? null
            : normalizeAthkarOrigin(override.origin);
    const isAayahRaw = override?.is_aayah === undefined ? override?.is_quran : override?.is_aayah;
    const isAayah = isAayahRaw === null || isAayahRaw === undefined ? null : Boolean(isAayahRaw);
    const isCustom = Boolean(override?.is_custom);

    return {
        thikr_id: thikrId,
        order,
        time,
        type,
        text,
        origin,
        count,
        is_aayah: isAayah,
        is_deleted: Boolean(override?.is_deleted),
        is_custom: isCustom,
    };
};

const normalizeAthkarOverrides = (overrides) => {
    if (!Array.isArray(overrides)) {
        return [];
    }

    const byThikrId = new Map();

    overrides.forEach((override) => {
        const normalized = normalizeAthkarOverride(override);

        if (!normalized) {
            return;
        }

        byThikrId.set(normalized.thikr_id, normalized);
    });

    return Array.from(byThikrId.values());
};

const resolveAthkarWithOverrides = (defaults, overrides) => {
    const normalizedDefaults = normalizeAthkarDefaults(defaults);
    const normalizedOverrides = normalizeAthkarOverrides(overrides);
    const overridesByThikrId = new Map(
        normalizedOverrides.map((override) => [override.thikr_id, override]),
    );
    const defaultsById = new Map(normalizedDefaults.map((item) => [item.id, item]));

    const defaultCards = normalizedDefaults
        .map((defaultItem) => {
            const override = overridesByThikrId.get(defaultItem.id) ?? null;

            if (override?.is_deleted) {
                return null;
            }

            return {
                ...defaultItem,
                order: override?.order ?? defaultItem.order,
                time: override?.time ?? defaultItem.time,
                type: override?.type ?? defaultItem.type,
                text: override?.text ?? defaultItem.text,
                origin: override?.origin ?? defaultItem.origin,
                count: override?.count ?? defaultItem.count,
                is_aayah:
                    override?.is_aayah === null || override?.is_aayah === undefined
                        ? defaultItem.is_aayah
                        : Boolean(override.is_aayah),
                is_original: hasAthkarOrigin(override?.origin ?? defaultItem.origin),
            };
        })
        .filter(Boolean);

    const customCards = normalizedOverrides
        .filter((override) => Boolean(override?.is_custom))
        .filter((override) => !defaultsById.has(override.thikr_id))
        .filter((override) => !override?.is_deleted)
        .map((override) => ({
            id: override.thikr_id,
            time: normalizeAthkarTime(override?.time, 'shared'),
            type: normalizeAthkarType(override?.type, 'glorification'),
            text: String(override?.text ?? '').trim(),
            origin: normalizeAthkarOrigin(override?.origin),
            is_aayah: Boolean(override?.is_aayah),
            is_original: hasAthkarOrigin(override?.origin),
            count: normalizeAthkarCount(override?.count, 1),
            order: normalizeAthkarOrder(override?.order, 1),
        }));

    return defaultCards
        .concat(customCards)
        .sort((left, right) => left.order - right.order || left.id - right.id);
};

const readAthkarOverridesFromStorage = () => {
    if (typeof localStorage === 'undefined') {
        return [];
    }

    try {
        const raw = localStorage.getItem(athkarOverridesStorageKey);

        if (!raw) {
            return [];
        }

        return normalizeAthkarOverrides(JSON.parse(raw));
    } catch (_) {
        return [];
    }
};

const writeAthkarOverridesToStorage = (overrides) => {
    const normalized = normalizeAthkarOverrides(overrides);

    if (typeof localStorage === 'undefined') {
        return normalized;
    }

    try {
        localStorage.setItem(athkarOverridesStorageKey, JSON.stringify(normalized));
    } catch (_) {
        return normalized;
    }

    return normalized;
};

if (typeof window !== 'undefined') {
    window.getAthkarSettingsFromStorage = () => readAthkarSettingsFromStorage();
    window.readAthkarOverridesFromStorage = readAthkarOverridesFromStorage;
    window.writeAthkarOverridesToStorage = writeAthkarOverridesToStorage;
    window.resolveAthkarWithOverrides = resolveAthkarWithOverrides;
    window.normalizeAthkarOverrides = normalizeAthkarOverrides;
    window.getUserSettingsOverrides = readUserSettingsOverrides;
}

export {
    athkarSettingsStorageKey,
    athkarSettingsUserOverridesStorageKey,
    athkarOverridesStorageKey,
    migrateSettingsOverrides,
    normalizeAthkarDefaults,
    normalizeAthkarOverrides,
    readAthkarSettingsFromStorage,
    readAthkarOverridesFromStorage,
    readUserSettingsOverrides,
    resolveAthkarWithOverrides,
    resolveEffectiveSettings,
    writeAthkarSettingsToStorage,
    writeAthkarOverridesToStorage,
    writeUserSettingOverride,
    writeUserSettingsOverrides,
};
