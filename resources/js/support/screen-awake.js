const activeScreenAwakeTokens = new Set();
let wakeLockSentinel = null;
let wakeLockRequestPromise = null;
let nativeBridgeAwakeState = null;

const createScreenAwakeToken = () => {
    return `screen-awake-${Date.now().toString(36)}-${Math.random().toString(36).slice(2, 10)}`;
};

const canUseWebWakeLock = () => {
    return (
        typeof navigator !== 'undefined' &&
        navigator.wakeLock &&
        typeof navigator.wakeLock.request === 'function'
    );
};

const resolveNativeBridgeSetter = () => {
    if (typeof window === 'undefined') {
        return null;
    }

    const bridge = window.AndroidBridge;

    if (!bridge || typeof bridge !== 'object') {
        return null;
    }

    if (typeof bridge.setScreenAwake === 'function') {
        return bridge.setScreenAwake.bind(bridge);
    }

    if (typeof bridge.setKeepScreenAwake === 'function') {
        return bridge.setKeepScreenAwake.bind(bridge);
    }

    return null;
};

const applyNativeBridgeAwakeState = (enabled) => {
    const setter = resolveNativeBridgeSetter();

    if (!setter) {
        return false;
    }

    const normalizedEnabled = Boolean(enabled);

    if (nativeBridgeAwakeState === normalizedEnabled) {
        return true;
    }

    try {
        setter(normalizedEnabled);
        nativeBridgeAwakeState = normalizedEnabled;

        return true;
    } catch (_) {
        return false;
    }
};

const clearWakeLockSentinel = () => {
    if (!wakeLockSentinel) {
        return;
    }

    wakeLockSentinel.removeEventListener('release', handleWakeLockRelease);
    wakeLockSentinel = null;
};

const handleWakeLockRelease = () => {
    clearWakeLockSentinel();

    if (
        activeScreenAwakeTokens.size > 0 &&
        typeof document !== 'undefined' &&
        document.visibilityState === 'visible'
    ) {
        void ensureWebWakeLock();
    }
};

const ensureWebWakeLock = async () => {
    if (!canUseWebWakeLock()) {
        return false;
    }

    if (typeof document !== 'undefined' && document.visibilityState !== 'visible') {
        return false;
    }

    if (wakeLockSentinel && !wakeLockSentinel.released) {
        return true;
    }

    if (wakeLockRequestPromise) {
        return await wakeLockRequestPromise;
    }

    wakeLockRequestPromise = (async () => {
        try {
            const nextSentinel = await navigator.wakeLock.request('screen');

            clearWakeLockSentinel();
            wakeLockSentinel = nextSentinel;
            wakeLockSentinel.addEventListener('release', handleWakeLockRelease);

            return true;
        } catch (_) {
            return false;
        } finally {
            wakeLockRequestPromise = null;
        }
    })();

    return await wakeLockRequestPromise;
};

const releaseWebWakeLock = async () => {
    const sentinel = wakeLockSentinel;

    clearWakeLockSentinel();

    if (!sentinel) {
        return;
    }

    try {
        await sentinel.release();
    } catch (_) {
        // No-op: wake lock can already be released by the user agent.
    }
};

const syncScreenAwakeState = () => {
    const shouldKeepScreenAwake = activeScreenAwakeTokens.size > 0;
    const isDocumentVisible =
        typeof document === 'undefined' || document.visibilityState === 'visible';
    const shouldHoldScreenAwake = shouldKeepScreenAwake && isDocumentVisible;
    const nativeBridgeHandled = applyNativeBridgeAwakeState(shouldHoldScreenAwake);

    if (!shouldHoldScreenAwake) {
        void releaseWebWakeLock();

        return;
    }

    if (nativeBridgeHandled) {
        void releaseWebWakeLock();

        return;
    }

    void ensureWebWakeLock();
};

if (typeof document !== 'undefined') {
    document.addEventListener('visibilitychange', () => {
        if (activeScreenAwakeTokens.size === 0) {
            return;
        }

        syncScreenAwakeState();
    });
}

if (typeof window !== 'undefined') {
    window.addEventListener('pagehide', () => {
        if (activeScreenAwakeTokens.size === 0) {
            return;
        }

        syncScreenAwakeState();
    });

    window.addEventListener('beforeunload', () => {
        activeScreenAwakeTokens.clear();
        syncScreenAwakeState();
    });
}

export const acquireScreenAwakeLock = () => {
    const token = createScreenAwakeToken();

    activeScreenAwakeTokens.add(token);
    syncScreenAwakeState();

    return token;
};

export const releaseScreenAwakeLock = (token) => {
    if (!token) {
        return;
    }

    if (!activeScreenAwakeTokens.delete(token)) {
        return;
    }

    syncScreenAwakeState();
};

export const hasAnyScreenAwakeLocks = () => {
    return activeScreenAwakeTokens.size > 0;
};
