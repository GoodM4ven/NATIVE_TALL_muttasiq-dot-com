import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content || '';

const isNativeRuntime = () => document.body?.classList.contains('native-platform') === true;

const bootstrap = () => window.realtimeBootstrap || {};

const normalizeSocketId = (value) => {
    const socketId = String(value || '').trim();

    return socketId === '' || socketId === 'undefined' || socketId === 'null' ? null : socketId;
};

const socketId = () => normalizeSocketId(window.Echo?.socketId?.());

const sameOriginUrl = (resource) => {
    try {
        const url =
            typeof resource === 'string'
                ? new URL(resource, window.location.href)
                : new URL(resource?.url || '', window.location.href);

        return url.origin === window.location.origin;
    } catch (_) {
        return false;
    }
};

const installSocketHeaderForwarding = () => {
    if (window.__muttasiqRealtimeFetchPatched === true || typeof window.fetch !== 'function') {
        // Keep going: XHR may still need the socket header even when fetch is unavailable.
    } else {
        const originalFetch = window.fetch.bind(window);
        window.__muttasiqRealtimeFetchPatched = true;

        window.fetch = (resource, options = {}) => {
            const currentSocketId = socketId();

            if (!currentSocketId || !sameOriginUrl(resource)) {
                return originalFetch(resource, options);
            }

            const headers = new Headers(options.headers || resource?.headers || {});
            headers.set('X-Socket-ID', currentSocketId);

            return originalFetch(resource, {
                ...options,
                headers,
            });
        };
    }

    if (
        window.__muttasiqRealtimeXhrPatched !== true &&
        typeof window.XMLHttpRequest === 'function'
    ) {
        const originalOpen = window.XMLHttpRequest.prototype.open;
        const originalSend = window.XMLHttpRequest.prototype.send;
        const originalSetRequestHeader = window.XMLHttpRequest.prototype.setRequestHeader;

        window.__muttasiqRealtimeXhrPatched = true;

        window.XMLHttpRequest.prototype.open = function (method, url, ...rest) {
            this.__muttasiqRealtimeUrl = url;

            return originalOpen.call(this, method, url, ...rest);
        };

        window.XMLHttpRequest.prototype.send = function (body) {
            const currentSocketId = socketId();

            if (currentSocketId && sameOriginUrl(this.__muttasiqRealtimeUrl || '')) {
                try {
                    originalSetRequestHeader.call(this, 'X-Socket-ID', currentSocketId);
                } catch (_) {
                    // Ignore header injection failures; the request can still proceed.
                }
            }

            return originalSend.call(this, body);
        };
    }
};

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

const revealBlinker = () => {
    const layout = window.Alpine?.$data?.(document.body);

    if (layout?.revealApp) {
        layout.revealApp();
    } else {
        window.dispatchEvent(new CustomEvent('native-auth-reveal'));
    }

    window.__authReloadInProgress = false;
};

const forceMainMenu = () => {
    try {
        window.localStorage.setItem('app-active-view', 'main-menu');
    } catch (_) {
        // Ignore unavailable storage; the reload still fetches the latest bundle.
    }

    window.dispatchEvent(
        new CustomEvent('switch-view', {
            detail: { to: 'main-menu' },
        }),
    );
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

const blinkThen = (callback, { reload = false, forceMenu = true } = {}) => {
    const delay = beginBlink();

    window.setTimeout(async () => {
        if (forceMenu) {
            forceMainMenu();
        }

        try {
            await callback();
        } finally {
            if (reload) {
                window.location.assign('/');
                return;
            }

            revealBlinker();
        }
    }, delay);
};

const handleRealtimeEvent = (event) => {
    const type = String(event?.type || '');
    const targetTokenId = Number(event?.target_token_id || 0);
    const currentTokenId = Number(bootstrap().nativeTokenId || 0);
    const isCurrentRevokedNativeDevice =
        type === 'deviceLoggedOut' &&
        isNativeRuntime() &&
        targetTokenId > 0 &&
        targetTokenId === currentTokenId;

    if (type === 'accountDeleted') {
        blinkThen(
            async () => {
                wipeLocalUserBranch();
                await localLogout();
            },
            { reload: true },
        );

        return;
    }

    if (type === 'passwordChanged' || isCurrentRevokedNativeDevice) {
        blinkThen(localLogout, { reload: true });

        return;
    }

    if (['dataSynced', 'dataOverridden'].includes(type)) {
        blinkThen(async () => {
            await refreshUserBundle();
        });

        return;
    }

    if (type === 'deviceLoggedOut') {
        blinkThen(
            async () => {
                if (isCurrentRevokedNativeDevice) {
                    await localLogout();
                } else {
                    window.Livewire?.dispatch('native-devices-refresh');
                }
            },
            {
                reload: isCurrentRevokedNativeDevice,
                forceMenu: isCurrentRevokedNativeDevice,
            },
        );
    }
};

const bootRealtime = () => {
    const config = bootstrap();
    const telegramId = Number(config.telegramId || 0);
    const key = import.meta.env.VITE_REVERB_APP_KEY;
    const host = import.meta.env.VITE_REVERB_HOST;

    if (!telegramId || !key || !host) {
        return;
    }

    const scheme = import.meta.env.VITE_REVERB_SCHEME || 'https';
    const port = Number(import.meta.env.VITE_REVERB_PORT || (scheme === 'https' ? 443 : 80));
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

    installSocketHeaderForwarding();

    window.Echo.private(`user.${telegramId}`).listen('.user-realtime', handleRealtimeEvent);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', bootRealtime, { once: true });
} else {
    bootRealtime();
}
