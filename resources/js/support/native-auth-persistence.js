const restoreStorageKey = 'auth.telegram.restore';
const pendingKey = 'auth.telegram.pending';

// Web shares this bundle but has no native bridge — calling SecureStorage there
// throws "No device connected". The body carries `native-platform` only in the
// native runtime, so every entry point bails on web before touching the bridge.
const isNativeRuntime = () => document.body?.classList.contains('native-platform') === true;

const getCsrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const getBootstrapConfig = () => window.nativeAuthBootstrap || {};

const getSecureStorage = () => window.nativeSecureStorage || null;

// On native the home shell holds the blinker while logged out; this tells it to
// stop holding and show the (guest) UI because there is nothing to restore.
const revealApp = () => {
    window.dispatchEvent(new CustomEvent('native-auth-reveal'));
};

const dispatchReloadBlink = () => {
    window.dispatchEvent(
        new CustomEvent('auth-blink-reload', {
            detail: { url: window.location.pathname || '/' },
        }),
    );
};

// True while a Telegram login is mid-flight (the user tapped login recently), so
// the blinker keeps holding for the incoming deeplink handoff instead of flashing
// the guest UI. 5 minutes covers a slow OAuth screen.
const isTelegramAuthPending = () => {
    try {
        const at = window.localStorage.getItem(pendingKey);

        return at !== null && Date.now() - Number(at) < 300000;
    } catch (_) {
        return false;
    }
};

const clearTelegramAuthPending = () => {
    try {
        window.localStorage.removeItem(pendingKey);
    } catch (_) {
        // Ignore cleanup failures.
    }
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
    const restoreUrl = String(getBootstrapConfig().restoreUrl || '').trim();

    if (restoreUrl === '' || !secureStorage?.get) {
        revealApp();

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
        // No saved session. If a Telegram login is mid-flight, keep holding the
        // blinker for the deeplink handoff; otherwise reveal the guest UI.
        if (!isTelegramAuthPending()) {
            revealApp();
        }

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
            revealApp();

            return;
        }

        dispatchReloadBlink();
    } catch (_) {
        // Couldn't reach the local runtime; reveal so the app never hangs.
        revealApp();
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
        // No bridge to restore from — reveal so the blinker doesn't hang.
        revealApp();

        return;
    }

    // Logged-in already (cookie survived): nothing to restore.
    if (window.dataBranch === 'user') {
        clearTelegramAuthPending();

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
    clearTelegramAuthPending();

    if (!isNativeRuntime()) {
        return;
    }

    const secureStorage = getSecureStorage();

    if (!secureStorage?.delete) {
        return;
    }

    void deleteRestoreToken(secureStorage);
});

window.addEventListener('native-auth-reveal', () => {
    clearTelegramAuthPending();
});

// Grace window after the app is resumed mid-login: long enough for a real
// deeplink handoff to start reloading the app, short enough that an empty-handed
// return doesn't leave the blinker stuck on white for long.
const authReturnGraceMs = 2000;
let authReturnRevealTimer = null;

const cancelAuthReturnReveal = () => {
    if (authReturnRevealTimer !== null) {
        window.clearTimeout(authReturnRevealTimer);
        authReturnRevealTimer = null;
    }
};

// A real login navigates the WebView to the handoff route (then restarts), which
// fires pagehide — cancel the reveal so we never flash the app right before it
// reloads. An empty-handed return never navigates, so the timer below survives.
window.addEventListener('pagehide', cancelAuthReturnReveal);

// When the app comes back to the foreground while a Telegram login is pending,
// wait a beat: if the deeplink brought auth data back it will reload/restart the
// app (this context dies). If it didn't — the user just closed the browser with
// the system back button — reveal the held blinker instead of hanging on white.
const handleNativeAuthResume = () => {
    if (!isNativeRuntime() || window.dataBranch === 'user' || !isTelegramAuthPending()) {
        return;
    }

    cancelAuthReturnReveal();

    authReturnRevealTimer = window.setTimeout(() => {
        authReturnRevealTimer = null;

        // Still here, still guest, nothing restoring/restarting → no auth came back.
        if (
            window.dataBranch === 'user' ||
            window.__nativeAuthRestoreInFlight === true ||
            window.nativeAuthRestart
        ) {
            return;
        }

        clearTelegramAuthPending();
        revealApp();
    }, authReturnGraceMs);
};

window.addEventListener('quran-native-lifecycle', (event) => {
    if (
        String(event?.detail?.event ?? '')
            .trim()
            .toLowerCase() === 'activity-resume'
    ) {
        handleNativeAuthResume();
    }
});

scheduleBootstrap();
