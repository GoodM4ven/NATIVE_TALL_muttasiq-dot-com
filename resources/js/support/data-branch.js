import {
    athkarSettingsStorageKey,
    resolveEffectiveSettings,
    writeAthkarSettingsToStorage,
} from './alpine/athkar-app-overrides';

// Two-branch localStorage namespacing: guest-stuff vs user-stuff.
//
// Branched keys are transparently prefixed with the active branch (`guest::` or
// `user::`), chosen from `window.dataBranch` (set by the server from auth state
// on every page load). Guest data stays intact and inactive while logged in,
// and is reactivated on logout — no per-feature rework, every existing
// localStorage call just lands in the right branch.
//
// A subset of the branched keys (SYNCED_KEYS) also syncs to the server: the
// user branch is seeded from `window.userSyncedData` on load (so the account
// follows across devices) and debounce-pushed back when it changes. First-ever
// login has no server bundle yet, so the branch starts empty and reads the app
// defaults.
//
// NOT branched at all (shared, device-local): render/screen caches like the
// quran page fit adjustments and fit-cache, color scheme, version markers,
// error log. Those are tied to the physical device, not the account.

// `athkar-settings-v1` is intentionally NOT synced: it is a DERIVED, device-
// dependent snapshot (numerals/harakat display + main-text-size clamping) that
// every device re-computes from the synced overrides + server defaults. Syncing
// the snapshot made two devices re-serialize it slightly differently and push it
// back and forth forever. It stays branched + device-local (see BRANCHED_KEYS)
// and is re-derived locally whenever the synced overrides change.
const SYNCED_KEYS = new Set([
    'athkar-settings-user-overrides-v1',
    'athkar-overrides-v1',
    'athkar-progress-v1',
    'athkar-notice-bypass-flags-v1',
    'quran-reader-bookmarks-v1',
    'quran-reader-last-page-v1',
    'quran-reader-navigation-history-v1',
    'quran-reader-wird-day-offset-v1',
    'quran-reader-wird-progress-v1',
]);

// These keys are still account-synced, but they are too "live" to apply over a
// websocket pull while another device is actively reading: each reader quickly
// re-asserts its own cursor/history and the full-bundle sync starts bouncing.
const REALTIME_VOLATILE_KEYS = new Set([
    'quran-reader-last-page-v1',
    'quran-reader-navigation-history-v1',
]);

// `quran-reader-last-page-v1` / `quran-reader-navigation-history-v1` ARE synced:
// reading position follows the account across devices (read on desktop, continue
// on mobile). Cross-device "active usage" is handled by the websocket listener,
// which sends idle devices back to the main menu so they don't fight over state.
//
// NOT synced (genuinely device-local UI state): `app-active-view` — which screen
// is open — stays device-local via Alpine $persist.

// Branched (per guest/user) but not all synced: the derived settings snapshot is
// device-local yet must still live under the active branch so guest and user
// state never bleed into each other.
const BRANCHED_KEYS = new Set([...SYNCED_KEYS, athkarSettingsStorageKey]);

// Native pushes each change to the server over HTTP, so debounce harder there to
// avoid a request per Quran page-turn / progress tick; web just writes locally.
const isNativePlatform = () => document.body?.classList.contains('native-platform') === true;
const pushDebounceMs = () => (isNativePlatform() ? 5000 : 1500);

// After applying a remote bundle, the app reactively re-persists the just-applied
// values — and some are re-serialized into different bytes (per-device numeral /
// harakat / size normalization) or re-asserted to this device's own value (live
// reading position). The byte-compare guard below can't catch a re-normalized
// value, so without this window two devices push those differences back and forth
// forever. Hold off outgoing pushes briefly so the receiver settles silently; a
// genuine user change after the window still syncs.
const remoteSettleMs = () => (isNativePlatform() ? 4000 : 2500);
const isObjectLike = (value) =>
    value !== null && typeof value === 'object' && !Array.isArray(value);

const branchedKey = (branch, key) => `${branch}::${key}`;

const activeBranch = () => (window.dataBranch === 'user' ? 'user' : 'guest');

const normalizePersistedAppActiveView = (storage = window.localStorage) => {
    if (!storage?.getItem || !storage?.setItem) {
        return;
    }

    try {
        const rawValue = storage.getItem('app-active-view');

        if (rawValue === null) {
            return;
        }

        try {
            JSON.parse(rawValue);
        } catch (_) {
            const normalizedValue = rawValue.trim();

            if (normalizedValue === '') {
                storage.removeItem('app-active-view');

                return;
            }

            storage.setItem('app-active-view', JSON.stringify(normalizedValue));
        }
    } catch (_) {
        // Ignore malformed storage; Alpine can still boot on a clean key.
    }
};

const filterRealtimeBundle = (bundle = {}) => {
    if (!isObjectLike(bundle)) {
        return {};
    }

    const filteredBundle = {};

    Object.entries(bundle).forEach(([key, value]) => {
        if (!REALTIME_VOLATILE_KEYS.has(key)) {
            filteredBundle[key] = value;
        }
    });

    return filteredBundle;
};

// Module-scoped, so it resets every page load. It must NOT live on `localStorage`:
// assigning an arbitrary property to a Storage object persists it as a real key,
// which would make the guard read back truthy on the next load and silently skip
// installing the wrapper forever after the first visit.
let dataBranchInstalled = false;

export const installDataBranch = (storage = window.localStorage) => {
    if (!storage || dataBranchInstalled) {
        return;
    }

    dataBranchInstalled = true;

    const original = {
        getItem: storage.getItem.bind(storage),
        setItem: storage.setItem.bind(storage),
        removeItem: storage.removeItem.bind(storage),
    };
    let isApplyingRemoteBundle = false;
    // Timestamp until which outgoing pushes are held after applying a remote
    // bundle (see remoteSettleMs) — breaks the cross-device sync ping-pong.
    let remoteSettleUntil = 0;

    normalizePersistedAppActiveView(storage);

    // Clean up the persisted junk key left by the earlier buggy guard.
    original.removeItem('__dataBranchInstalled');

    const resolve = (key) => (BRANCHED_KEYS.has(key) ? branchedKey(activeBranch(), key) : key);

    // One-time migration: fold any pre-namespacing bare keys into the guest
    // branch so existing users don't lose their data on upgrade.
    BRANCHED_KEYS.forEach((key) => {
        const bare = original.getItem(key);

        if (bare !== null && original.getItem(branchedKey('guest', key)) === null) {
            original.setItem(branchedKey('guest', key), bare);
            original.removeItem(key);
        }
    });

    // Authoritatively reset the user branch from the server bundle on every
    // logged-in load (synchronously, before the app reads storage). The server
    // is the source of truth across devices, so keys absent from the bundle are
    // cleared — a fresh account (no bundle, null) wipes the branch and thus
    // starts from defaults, never inheriting another account's leftover local
    // user-branch data on the same browser.
    if (activeBranch() === 'user') {
        const seed =
            window.userSyncedData && typeof window.userSyncedData === 'object'
                ? window.userSyncedData
                : {};

        SYNCED_KEYS.forEach((key) => {
            if (typeof seed[key] === 'string') {
                original.setItem(branchedKey('user', key), seed[key]);
            } else {
                original.removeItem(branchedKey('user', key));
            }
        });
    }

    // Gather and (debounced) push the synced user-branch keys to the server.
    const collectUserBundle = () => {
        const bundle = {};

        SYNCED_KEYS.forEach((key) => {
            const value = original.getItem(branchedKey('user', key));

            if (value !== null) {
                bundle[key] = value;
            }
        });

        return bundle;
    };

    const dispatchStorageEvent = (key, oldValue, newValue) => {
        if (typeof window.StorageEvent === 'function') {
            window.dispatchEvent(
                new StorageEvent('storage', {
                    key,
                    oldValue,
                    newValue,
                    storageArea: storage,
                    url: window.location.href,
                }),
            );

            return;
        }

        const event = new Event('storage');

        Object.assign(event, {
            key,
            oldValue,
            newValue,
            storageArea: storage,
            url: window.location.href,
        });

        window.dispatchEvent(event);
    };

    const pushUserBundle = (reloadAfter = false, attempt = 0) => {
        const socketId = window.Echo?.socketId?.() || null;

        // A null socket id means Echo hasn't finished connecting; broadcasting
        // without it makes the server echo this change back to us. Defer a few
        // short beats so the id is available (and excludes us). If Echo never
        // connects, fall through and push anyway so the data still syncs.
        if (socketId === null && window.Echo && attempt < 3) {
            window.setTimeout(() => pushUserBundle(reloadAfter, attempt + 1), 600);

            return;
        }

        window.Livewire?.dispatch('push-user-data', {
            data: collectUserBundle(),
            reloadAfter,
            socketId,
        });
    };

    // Re-derive the (unsynced, device-local) athkar-settings-v1 snapshot from the
    // synced overrides + server defaults, into the active (user) branch via the
    // wrapped storage. Never pushes — athkar-settings-v1 isn't a synced key.
    const deriveUserAthkarSettings = () => {
        const defaults = window.athkarSettingsDefaults;

        if (!isObjectLike(defaults) || Object.keys(defaults).length === 0) {
            return;
        }

        try {
            writeAthkarSettingsToStorage(resolveEffectiveSettings(defaults), defaults);
        } catch (_) {
            // Best-effort; the athkar app also re-derives this on its own boot.
        }
    };

    let pushTimer = null;

    const schedulePush = () => {
        if (activeBranch() !== 'user') {
            return;
        }

        window.clearTimeout(pushTimer);
        pushTimer = window.setTimeout(pushUserBundle, pushDebounceMs());
    };

    const applyUserBundle = (bundle = {}) => {
        if (activeBranch() !== 'user' || !isObjectLike(bundle)) {
            return false;
        }

        let didChange = false;
        const normalizedBundle = {};

        SYNCED_KEYS.forEach((key) => {
            if (typeof bundle[key] === 'string') {
                normalizedBundle[key] = bundle[key];
            }
        });

        isApplyingRemoteBundle = true;

        try {
            window.userSyncedData = normalizedBundle;

            SYNCED_KEYS.forEach((key) => {
                const nextValue = Object.prototype.hasOwnProperty.call(normalizedBundle, key)
                    ? normalizedBundle[key]
                    : null;
                const oldValue = original.getItem(branchedKey('user', key));

                if (oldValue === nextValue) {
                    return;
                }

                if (nextValue === null) {
                    original.removeItem(branchedKey('user', key));
                } else {
                    original.setItem(branchedKey('user', key), nextValue);
                }

                didChange = true;
                dispatchStorageEvent(key, oldValue, nextValue);
            });

            // The settings snapshot isn't synced; re-derive it locally from the
            // overrides we just applied so direct readers (main menu, fitty) show
            // the change without waiting for the reader to mount. Still inside the
            // apply guard, so neither this nor any migrate write schedules a push.
            if (didChange) {
                deriveUserAthkarSettings();
            }
        } finally {
            isApplyingRemoteBundle = false;
            // Open the settle window so the reactive re-writes that follow this
            // apply don't bounce back to the server and ping-pong.
            remoteSettleUntil = Date.now() + remoteSettleMs();
        }

        if (didChange) {
            window.dispatchEvent(
                new CustomEvent('muttasiq-user-synced-data-updated', {
                    detail: { bundle: normalizedBundle },
                }),
            );
        }

        return didChange;
    };

    const applyRealtimeBundle = (bundle = {}) => applyUserBundle(filterRealtimeBundle(bundle));

    // ponytail: only the three accessor methods are wrapped (the only ones the
    // app uses). Bracket access / key(i) / length are not branched.
    const afterWrite = (key) => {
        if (isApplyingRemoteBundle || !SYNCED_KEYS.has(key)) {
            return;
        }

        // Echo guard: receiving a remote bundle makes the app reactively
        // re-persist the just-applied values through this wrapped setItem. Those
        // writes are byte-identical to what the server sent (tracked in
        // window.userSyncedData), so skip them — only a real divergence pushes.
        // Deterministic, unlike a timing window: a re-applied value never loops,
        // and a genuine change (or a revert) always syncs.
        const current = original.getItem(branchedKey('user', key));
        const lastSynced = window.userSyncedData?.[key] ?? null;

        if (current === lastSynced) {
            return;
        }

        // Remember this as the new baseline so repeated re-persists of the same
        // value don't each re-arm the debounce (and so a re-asserted value during
        // the settle window below becomes canonical instead of looping).
        if (window.userSyncedData && typeof window.userSyncedData === 'object') {
            window.userSyncedData[key] = current;
        }

        // Within the post-apply settle window, swallow the push: this write is the
        // app reacting to a bundle we just received, not a fresh user change.
        if (Date.now() < remoteSettleUntil) {
            return;
        }

        schedulePush();
    };

    Object.defineProperties(storage, {
        getItem: { value: (key) => original.getItem(resolve(key)), configurable: true },
        setItem: {
            value: (key, value) => {
                original.setItem(resolve(key), value);
                afterWrite(key);
            },
            configurable: true,
        },
        removeItem: {
            value: (key) => {
                original.removeItem(resolve(key));
                afterWrite(key);
            },
            configurable: true,
        },
    });

    // Seed the derived snapshot for the initial logged-in load: the seed block
    // above only restores the *synced* keys, and athkar-settings-v1 is derived.
    // Guard it as a remote apply so a first-run migrate write can't schedule a push.
    if (activeBranch() === 'user') {
        isApplyingRemoteBundle = true;

        try {
            deriveUserAthkarSettings();
        } finally {
            isApplyingRemoteBundle = false;
        }
    }

    // Copy the whole branched bundle from one branch to the other (override).
    // Uses the raw accessors so it never triggers the debounced push itself.
    const copyBranch = (from, to) => {
        if (from === to) {
            return;
        }

        BRANCHED_KEYS.forEach((key) => {
            const value = original.getItem(branchedKey(from, key));

            if (value === null) {
                original.removeItem(branchedKey(to, key));
            } else {
                original.setItem(branchedKey(to, key), value);
            }
        });
    };

    window.muttasiqDataBranch = {
        copyBranch,
        pushUserBundle,
        applyUserBundle,
        applyRealtimeBundle,
        activeBranch,
        BRANCHED_KEYS,
        SYNCED_KEYS,
        REALTIME_VOLATILE_KEYS,
    };
};

installDataBranch();

// Driven by the two data-tab buttons (override guest⇄user). Copies the bundle
// between branches. When the user branch is the target, it also pushes to the
// server and asks pushUserData() to fire the blinker reload *after* that push
// completes (so the native HTTP sync isn't cut off by an early reload). When the
// guest branch is the target it's a local-only copy, so the action reloads itself.
if (typeof window !== 'undefined') {
    document.addEventListener('livewire:init', () => {
        window.Livewire?.on('override-data-branch', (event) => {
            const { fromBranch, toBranch } = Array.isArray(event) ? event[0] : event;

            window.muttasiqDataBranch?.copyBranch(fromBranch, toBranch);

            if (toBranch === 'user') {
                window.muttasiqDataBranch?.pushUserBundle(true);
            }
        });
    });
}
