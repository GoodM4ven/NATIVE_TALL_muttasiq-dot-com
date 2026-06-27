import 'laravel-hot-refresh';
import './support/data-branch';
import './support/dispatch';
import './support/css-variables';
import './support/animate-scroll';
import './support/keyboard-super-key-guard';
import './support/screen-awake';

import './packages/alpine/hash-actions';
import './packages/alpine/hooks.js';
import './packages/alpine/tippy';
import './packages/color';
import './packages/day';
import './packages/fitty';
import './packages/anime';
import './packages/auto-animate';
import './packages/ldrs';
import './packages/nativephp/browser';

import './support/app-version-routing';
import './support/native-auth-persistence';
import './support/alpine/data/layout-manager';
import './support/alpine/data/main-menu';
import './support/alpine/data/athkar-app-gate';
import './support/alpine/data/athkar-app-reader/index';
import './support/alpine/data/athkar-app-manager';
import './support/alpine/data/quran-app-gate';
import './support/alpine/data/quran-app-reader/index';
import './support/alpine/data/sunna-app-gate';
import './support/alpine/storage/font-manager';
import './support/alpine/storage/color-scheme';
import './support/alpine/storage/breakpointer';
import './support/alpine/storage/locator.js';
import './support/alpine/directive/image-loaded';
import './support/alpine/directive/viewer';
import './support/alpine/directive/top-scroller';
import './support/alpine/magic/clipboard';
import './support/alpine/magic/top-scroller';
import './support/alpine/magic/livewire-lock';

import './overrides/livewire-session-expiry-reload';
import './overrides/livewire-dialog-show-modal-guard';
import './overrides/livewire-transition-consistency';

import './support/debugging/alpine-transition-debugger';

import './initialize-color-scheme';

const loadLazyBundle = () => {
    import('./app-lazy.js').catch(() => {});
};

if ('requestIdleCallback' in window) {
    requestIdleCallback(loadLazyBundle);
} else {
    setTimeout(loadLazyBundle, 0);
}
