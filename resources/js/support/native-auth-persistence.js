const restoreStorageKey = 'auth.telegram.restore';

// Web shares this bundle but has no native bridge — calling SecureStorage there
// throws "No device connected". The body carries `native-platform` only in the
// native runtime, so every entry point bails on web before touching the bridge.
const isNativeRuntime = () => document.body?.classList.contains('native-platform') === true;

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const getBootstrapConfig = () => window.nativeAuthBootstrap || {};

const getSecureStorage = () => window.nativeSecureStorage || null;

const dispatchReloadBlink = () => {
    window.dispatchEvent(
        new CustomEvent('auth-blink-reload', {
            detail: { url: window.location.pathname || '/' },
        }),
    );
};

const deleteRestoreToken = async (secureStorage) => {
    if (!secureStorage?.delete) {
        return;
    }

    try {
        await secureStorage.delete(restoreStorageKey);
    } catch (_) {
        // Ignore storage cleanup failures.
    }
};

const restoreNativeSession = async (secureStorage) => {
    const bootstrapConfig = getBootstrapConfig();
    const restoreUrl = String(bootstrapConfig.restoreUrl || '').trim();

    if (restoreUrl === '' || !secureStorage?.get) {
        return;
    }

    if (window.dataBranch !== 'guest') {
        return;
    }

    if (window.__nativeAuthRestoreInFlight === true) {
        return;
    }

    const storedTokenResponse = await secureStorage.get(restoreStorageKey);
    const storedToken = String(storedTokenResponse?.value || '').trim();

    if (storedToken === '') {
        return;
    }

    window.__nativeAuthRestoreInFlight = true;

    try {
        const response = await fetch(restoreUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                token: storedToken,
            }),
        });

        if (!response.ok) {
            await deleteRestoreToken(secureStorage);

            return;
        }

        dispatchReloadBlink();
    } catch (_) {
        // Leave the token in place for the next launch attempt.
    } finally {
        window.__nativeAuthRestoreInFlight = false;
    }
};

const bootstrapNativeAuthPersistence = async () => {
    if (!isNativeRuntime()) {
        return;
    }

    const secureStorage = getSecureStorage();

    if (!secureStorage) {
        return;
    }

    // Logged-in already (cookie survived): nothing to restore.
    if (window.dataBranch === 'user') {
        return;
    }

    await restoreNativeSession(secureStorage);
};

const scheduleBootstrap = () => {
    if (document.readyState === 'complete') {
        void bootstrapNativeAuthPersistence();

        return;
    }

    window.addEventListener(
        'load',
        () => {
            void bootstrapNativeAuthPersistence();
        },
        { once: true },
    );
};

window.addEventListener('native-auth-forget', () => {
    if (!isNativeRuntime()) {
        return;
    }

    const secureStorage = getSecureStorage();

    if (!secureStorage?.delete) {
        return;
    }

    void deleteRestoreToken(secureStorage);
});

scheduleBootstrap();
