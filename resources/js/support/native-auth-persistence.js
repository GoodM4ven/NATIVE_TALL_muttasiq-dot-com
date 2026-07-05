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

const stateKey = 'auth.telegram.state';

const clearTelegramAuthPending = () => {
    try {
        window.localStorage.removeItem(pendingKey);
        window.localStorage.removeItem(stateKey);
    } catch (_) {
        // Ignore cleanup failures.
    }
};

// Recovery for a login that finished in the browser when the user returned via the
// system back button (no deeplink). Polls the public server for the one-time code
// bound to this device's registered `state`, then navigates to the same local
// handoff route the deeplink button would have. Returns true if a handoff started.
const claimPendingLogin = async () => {
    const claimUrl = String(getBootstrapConfig().claimUrl || '').trim();

    let state = '';

    try {
        state = String(window.localStorage.getItem(stateKey) || '').trim();
    } catch (_) {
        // Ignore storage read failures.
    }

    if (claimUrl === '' || state === '') {
        return false;
    }

    try {
        const response = await fetch(claimUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
            },
            body: JSON.stringify({ state }),
        });

        if (!response.ok) {
            return false;
        }

        const data = await response.json();
        const code = String(data?.code || '').trim();

        if (data?.ready !== true || code === '') {
            return false;
        }

        // One-time state consumed server-side; drop the local copy too.
        try {
            window.localStorage.removeItem(stateKey);
        } catch (_) {
            // Ignore storage cleanup failures.
        }

        // The local handoff route mirrors the account, logs in, and triggers the
        // restart — identical to tapping the deeplink button.
        window.location.assign('/auth/telegram/handoff?code=' + encodeURIComponent(code));

        return true;
    } catch (_) {
        return false;
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

// How long to keep the loading overlay up while polling the claim endpoint on
// resume, and how often to poll. The window has to outlast a slow OAuth finish so
// we don't declare "no login" while the server is still binding the result.
const claimPollIntervalMs = 1500;
const claimPollMaxMs = 12000;

const hasClaimableLogin = () => {
    const claimUrl = String(getBootstrapConfig().claimUrl || '').trim();

    let state = '';

    try {
        state = String(window.localStorage.getItem(stateKey) || '').trim();
    } catch (_) {
        // Ignore storage read failures.
    }

    return claimUrl !== '' && state !== '';
};

const authFlowResolvedElsewhere = () =>
    window.dataBranch === 'user' ||
    window.__nativeAuthRestoreInFlight === true ||
    Boolean(window.nativeAuthRestart) ||
    !isTelegramAuthPending();

// When the app comes back to the foreground while a Telegram login is pending,
// keep the loading overlay up and poll the claim endpoint for a bounded window:
// if the login finished in the browser (deeplink OR system back button) we hand
// off and this context tears down; only if nothing comes back within the window
// do we close the overlay and reveal the app for normal use.
const handleNativeAuthResume = () => {
    if (!isNativeRuntime() || window.dataBranch === 'user' || !isTelegramAuthPending()) {
        return;
    }

    cancelAuthReturnReveal();

    // No registered state / claim endpoint → can't poll; short grace then reveal.
    if (!hasClaimableLogin()) {
        authReturnRevealTimer = window.setTimeout(() => {
            authReturnRevealTimer = null;

            if (authFlowResolvedElsewhere()) {
                return;
            }

            clearTelegramAuthPending();
            revealApp();
        }, authReturnGraceMs);

        return;
    }

    const pollStartedAt = Date.now();

    const pollForClaim = async () => {
        authReturnRevealTimer = null;

        // Resolved elsewhere (deeplink handoff / restore / restart) → stop polling.
        if (authFlowResolvedElsewhere()) {
            return;
        }

        // Claimed → navigating to the handoff; this context tears down.
        if (await claimPendingLogin()) {
            return;
        }

        // Still nothing after the window → the login didn't come back; close overlay.
        if (Date.now() - pollStartedAt >= claimPollMaxMs) {
            clearTelegramAuthPending();
            revealApp();

            return;
        }

        authReturnRevealTimer = window.setTimeout(pollForClaim, claimPollIntervalMs);
    };

    void pollForClaim();
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

// The login launcher fires this when the OAuth browser resolves (returned or
// failed to open): run the same claim-poll so the loading overlay persists until
// the outcome is known, instead of revealing the app immediately.
window.addEventListener('native-auth-return-check', () => handleNativeAuthResume());

scheduleBootstrap();
