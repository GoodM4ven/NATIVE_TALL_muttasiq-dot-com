import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const isNativeRuntime = () => document.body?.classList.contains('native-platform') === true;

const bootstrap = () => window.realtimeBootstrap || {};

const authorizeChannel = async (authEndpoint, socketIdValue, channelName) => {
    const response = await fetch(authEndpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
        body: JSON.stringify({
            socket_id: socketIdValue,
            channel_name: channelName,
        }),
    });

    if (!response.ok) {
        throw new Error(`Broadcast auth failed: ${response.status}`);
    }

    return response.json();
};

const beginBlink = () => {
    window.__authReloadInProgress = true;

    const layout = window.Alpine?.$data?.(document.body);

    if (!layout) {
        return 500;
    }

    layout.useFastTransitionDuration = false;
    layout.isBlinkerShown = true;
    layout.isBodyVisible = false;

    return layout.defaultTransitionDurationInMs || 500;
};

const wipeLocalUserBranch = () => {
    const keys = window.muttasiqDataBranch?.SYNCED_KEYS;

    if (!keys?.forEach) {
        return;
    }

    keys.forEach((key) => {
        try {
            window.localStorage.removeItem(key);
        } catch (_) {
            // Keep wiping best-effort; logout still happens below.
        }
    });
};

const localLogout = async () => {
    window.dispatchEvent(new CustomEvent('native-auth-forget'));

    if (isNativeRuntime() && window.nativeSecureStorage?.delete) {
        try {
            await window.nativeSecureStorage.delete('auth.telegram.restore');
        } catch (_) {
            // The event listener above also attempts cleanup; keep logout moving.
        }
    }

    const logoutUrl = String(bootstrap().realtimeLogoutUrl || '').trim();

    if (logoutUrl === '') {
        return;
    }

    try {
        await fetch(logoutUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken(),
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: '{}',
        });
    } catch (_) {
        // The final reload still drops the user if the session was invalidated.
    }
};

const refreshUserBundle = async () => {
    const snapshotUrl = isNativeRuntime()
        ? String(bootstrap().nativeSyncPullUrl || '').trim()
        : String(bootstrap().realtimeSnapshotUrl || '').trim();

    if (snapshotUrl === '') {
        return false;
    }

    const requestOptions = {
        method: isNativeRuntime() ? 'POST' : 'GET',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
            'X-Requested-With': 'XMLHttpRequest',
        },
    };

    if (isNativeRuntime()) {
        requestOptions.headers['Content-Type'] = 'application/json';
        requestOptions.body = '{}';
    }

    const response = await fetch(snapshotUrl, requestOptions);

    if (!response.ok) {
        return false;
    }

    const payload = await response.json();
    const bundle = payload?.synced_data;

    return window.muttasiqDataBranch?.applyUserBundle?.(bundle) === true;
};

const NOTICE_FLAG = 'muttasiq-realtime-notice-pending';
const DATA_SYNC_REFRESH_DEBOUNCE_MS = 240;
const DATA_SYNC_NOTICE_DEBOUNCE_MS = 1200;

let pendingDataSyncRefreshTimer = null;
let pendingDataSyncNoticeTimer = null;
let isDataSyncRefreshInFlight = false;
let shouldEmitDataSyncNotice = false;

// Fire the "changes from another device" Filament notification (rendered by the
// AuthButton Livewire listener). Best-effort: a missed notice is harmless.
const fireOtherDeviceNotice = () => {
    window.Livewire?.dispatch('realtime-other-device-notice');
};

const flagPostReloadNotice = () => {
    try {
        window.localStorage.setItem(NOTICE_FLAG, '1');
    } catch (_) {
        // Storage unavailable; the notice is non-critical.
    }
};

const scheduleOtherDeviceNotice = () => {
    shouldEmitDataSyncNotice = true;

    if (pendingDataSyncNoticeTimer !== null) {
        window.clearTimeout(pendingDataSyncNoticeTimer);
    }

    pendingDataSyncNoticeTimer = window.setTimeout(() => {
        pendingDataSyncNoticeTimer = null;

        if (!shouldEmitDataSyncNotice) {
            return;
        }

        shouldEmitDataSyncNotice = false;
        fireOtherDeviceNotice();
    }, DATA_SYNC_NOTICE_DEBOUNCE_MS);
};

const flushRealtimeDataSync = async () => {
    if (isDataSyncRefreshInFlight) {
        return;
    }

    isDataSyncRefreshInFlight = true;

    try {
        const didChange = await refreshUserBundle();

        if (didChange) {
            scheduleOtherDeviceNotice();
        }
    } finally {
        isDataSyncRefreshInFlight = false;
    }
};

const scheduleRealtimeDataSync = () => {
    if (pendingDataSyncRefreshTimer !== null) {
        window.clearTimeout(pendingDataSyncRefreshTimer);
    }

    pendingDataSyncRefreshTimer = window.setTimeout(() => {
        pendingDataSyncRefreshTimer = null;
        flushRealtimeDataSync();
    }, DATA_SYNC_REFRESH_DEBOUNCE_MS);
};

// Blink FULLY out (await the white-fade transition) before doing anything
// visible — so no content morph / modal flicker is ever seen — then run `action`.
const blinkOutThen = async (action) => {
    const delay = beginBlink();

    await new Promise((resolve) => window.setTimeout(resolve, delay));
    await action();
};

const handleRealtimeEvent = (event) => {
    const type = String(event?.type || '');
    const targetTokenId = Number(event?.target_token_id || 0);
    const targetSessionId = String(event?.target_session_id || '').trim();
    const currentTokenId = Number(bootstrap().nativeTokenId || 0);
    const currentSessionId = String(bootstrap().sessionId || '').trim();
    const isCurrentRevokedNativeDevice =
        type === 'deviceLoggedOut' &&
        isNativeRuntime() &&
        targetTokenId > 0 &&
        targetTokenId === currentTokenId;
    const isCurrentRevokedWebSession =
        type === 'deviceLoggedOut' &&
        !isNativeRuntime() &&
        targetSessionId !== '' &&
        currentSessionId !== '' &&
        targetSessionId === currentSessionId;

    // RELOAD cases (auth side effects): blink fully out, run the side effect,
    // flag the notice to fire after the reload, then hard-reload. No modal
    // closing / morphing — the reload rebuilds the whole app.
    if (type === 'accountDeleted') {
        blinkOutThen(async () => {
            wipeLocalUserBranch();
            await localLogout();
            flagPostReloadNotice();
            window.location.assign('/');
        });

        return;
    }

    if (type === 'passwordChanged' || isCurrentRevokedNativeDevice || isCurrentRevokedWebSession) {
        blinkOutThen(async () => {
            await localLogout();
            flagPostReloadNotice();
            window.location.assign('/');
        });

        return;
    }

    // DATA-SYNC case (settings/bookmarks/progress/position changed elsewhere):
    // merge the new bundle in place — no blink, no modal close, no navigation,
    // no reload — then show a single subtle toast. Forcing navigation here used
    // to make this device re-assert its own state and ping-pong with the peer.
    if (type === 'dataSynced' || type === 'dataOverridden') {
        scheduleRealtimeDataSync();

        return;
    }

    // Another device logged out: refresh the "My Devices" list live (the intended
    // feature). No blink — that would close the very modal being used.
    if (type === 'deviceLoggedOut') {
        window.Livewire?.dispatch('native-devices-refresh');
    }
};

// After a reload-type realtime event, fire the deferred notice once the page
// (and Livewire) is back. Runs regardless of auth state, since auth-change
// reloads land the user as a guest.
const firePendingNotice = () => {
    let pending = false;

    try {
        pending = window.localStorage.getItem(NOTICE_FLAG) === '1';

        if (pending) {
            window.localStorage.removeItem(NOTICE_FLAG);
        }
    } catch (_) {
        return;
    }

    if (!pending) {
        return;
    }

    if (window.Livewire) {
        fireOtherDeviceNotice();
    } else {
        document.addEventListener('livewire:init', fireOtherDeviceNotice, { once: true });
    }
};

const bootRealtime = () => {
    const config = bootstrap();
    const telegramId = Number(config.telegramId || 0);

    // Prefer the server-injected Reverb host (web, points at the dev tunnel);
    // fall back to the build-time VITE_ values (native on-device bundle).
    const reverb = config.reverb || {};
    const key = reverb.key || import.meta.env.VITE_REVERB_APP_KEY;
    const host = reverb.host || import.meta.env.VITE_REVERB_HOST;

    if (!telegramId || !key || !host) {
        return;
    }

    const scheme = reverb.scheme || import.meta.env.VITE_REVERB_SCHEME || 'https';
    const port = Number(
        reverb.port || import.meta.env.VITE_REVERB_PORT || (scheme === 'https' ? 443 : 80),
    );
    const authEndpoint = isNativeRuntime()
        ? String(config.nativeBroadcastAuthUrl || '/native/broadcasting/auth')
        : '/broadcasting/auth';

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'reverb',
        key,
        wsHost: host,
        wsPort: port,
        wssPort: port,
        forceTLS: scheme === 'https',
        enabledTransports: ['ws', 'wss'],
        authorizer: (channel) => ({
            authorize: (socketIdValue, callback) => {
                authorizeChannel(authEndpoint, socketIdValue, channel.name)
                    .then((response) => callback(false, response))
                    .catch((error) => callback(true, error));
            },
        }),
    });

    window.Echo.private(`user.${telegramId}`).listen('.user-realtime', handleRealtimeEvent);
};

const boot = () => {
    firePendingNotice();
    bootRealtime();
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
} else {
    boot();
}
