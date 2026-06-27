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

// Every branched key is also synced. `athkar-settings-v1` is the full settings
// snapshot the control-panel form reads from, so it must sync alongside its
// overrides delta — otherwise the form would show stale/default values on
// another device while behavior used the synced overrides.
const SYNCED_KEYS = new Set([
    'athkar-settings-v1',
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

const BRANCHED_KEYS = SYNCED_KEYS;

// Native pushes each change to the server over HTTP, so debounce harder there to
// avoid a request per Quran page-turn / progress tick; web just writes locally.
const isNativePlatform = () => document.body?.classList.contains('native-platform') === true;
const pushDebounceMs = () => (isNativePlatform() ? 5000 : 1500);

const branchedKey = (branch, key) => `${branch}::${key}`;

const activeBranch = () => (window.dataBranch === 'user' ? 'user' : 'guest');

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

    const pushUserBundle = (reloadAfter = false) => {
        window.Livewire?.dispatch('push-user-data', {
            data: collectUserBundle(),
            reloadAfter,
        });
    };

    let pushTimer = null;

    const schedulePush = () => {
        if (activeBranch() !== 'user') {
            return;
        }

        window.clearTimeout(pushTimer);
        pushTimer = window.setTimeout(pushUserBundle, pushDebounceMs());
    };

    // ponytail: only the three accessor methods are wrapped (the only ones the
    // app uses). Bracket access / key(i) / length are not branched.
    const afterWrite = (key) => {
        if (SYNCED_KEYS.has(key)) {
            schedulePush();
        }
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
        activeBranch,
        BRANCHED_KEYS,
        SYNCED_KEYS,
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
