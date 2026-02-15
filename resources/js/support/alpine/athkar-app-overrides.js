const athkarSettingsStorageKey = 'athkar-settings-v1';
const athkarOverridesStorageKey = 'athkar-overrides-v1';

const normalizeAthkarSettings = (settings, defaults) => {
    if (!defaults || typeof defaults !== 'object' || Object.keys(defaults).length === 0) {
        if (!settings || typeof settings !== 'object') {
            return {};
        }

        const normalized = {};

        Object.keys(settings).forEach((key) => {
            normalized[key] = Boolean(settings[key]);
        });

        return normalized;
    }

    const normalized = { ...defaults };

    if (!settings || typeof settings !== 'object') {
        return normalized;
    }

    Object.keys(defaults).forEach((key) => {
        if (Object.prototype.hasOwnProperty.call(settings, key)) {
            normalized[key] = Boolean(settings[key]);
        }
    });

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

const readAthkarSettingsFromStorage = (defaults = resolveAthkarSettingsDefaults()) => {
    if (typeof localStorage === 'undefined') {
        return normalizeAthkarSettings({}, defaults);
    }

    try {
        const raw = localStorage.getItem(athkarSettingsStorageKey);

        if (!raw) {
            return normalizeAthkarSettings({}, defaults);
        }

        return normalizeAthkarSettings(JSON.parse(raw), defaults);
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
}

export {
    athkarSettingsStorageKey,
    athkarOverridesStorageKey,
    normalizeAthkarDefaults,
    normalizeAthkarOverrides,
    readAthkarSettingsFromStorage,
    readAthkarOverridesFromStorage,
    resolveAthkarWithOverrides,
    writeAthkarSettingsToStorage,
    writeAthkarOverridesToStorage,
};
