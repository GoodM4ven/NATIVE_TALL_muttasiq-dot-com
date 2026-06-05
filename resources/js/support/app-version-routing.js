const appVersionStorageKey = 'muttasiq-app-version-last-seen-v1';
const appVersionMajorMinorResetEventName = 'muttasiq-app-version-major-minor-reset';

const normalizeAppVersion = (version) => {
    if (typeof version !== 'string') {
        return null;
    }

    const normalizedVersion = version.trim();

    if (normalizedVersion === '') {
        return null;
    }

    const [majorPart = '', minorPart = ''] = normalizedVersion.split('.');
    const major = Number.parseInt(majorPart, 10);
    const minor = Number.parseInt(minorPart, 10);

    if (!Number.isFinite(major) || !Number.isFinite(minor)) {
        return null;
    }

    return {
        major,
        minor,
        version: normalizedVersion,
    };
};

const readStoredAppVersion = () => {
    if (typeof localStorage === 'undefined') {
        return null;
    }

    try {
        const rawValue = localStorage.getItem(appVersionStorageKey);

        if (rawValue === null) {
            return null;
        }

        try {
            const parsedValue = JSON.parse(rawValue);

            return typeof parsedValue === 'string' ? parsedValue.trim() || null : null;
        } catch (_) {
            const normalizedValue = rawValue.trim();

            return normalizedValue === '' ? null : normalizedValue;
        }
    } catch (_) {
        return null;
    }
};

const writeStoredAppVersion = (version) => {
    if (typeof localStorage === 'undefined') {
        return;
    }

    try {
        localStorage.setItem(appVersionStorageKey, JSON.stringify(version));
    } catch (_) {
        //
    }
};

const syncStoredAppVersion = (version) => {
    const normalizedCurrentVersion = normalizeAppVersion(version);

    if (!normalizedCurrentVersion) {
        return null;
    }

    const previousVersion = readStoredAppVersion();
    const normalizedPreviousVersion = normalizeAppVersion(previousVersion ?? '');
    const shouldResetStartupView = Boolean(
        normalizedPreviousVersion &&
        (normalizedPreviousVersion.major !== normalizedCurrentVersion.major ||
            normalizedPreviousVersion.minor !== normalizedCurrentVersion.minor),
    );

    writeStoredAppVersion(normalizedCurrentVersion.version);

    return {
        currentVersion: normalizedCurrentVersion.version,
        previousVersion: normalizedPreviousVersion?.version ?? null,
        shouldResetStartupView,
    };
};

window.appVersionRouting = Object.freeze({
    appVersionMajorMinorResetEventName,
    appVersionStorageKey,
    normalizeAppVersion,
    readStoredAppVersion,
    syncStoredAppVersion,
    writeStoredAppVersion,
});
