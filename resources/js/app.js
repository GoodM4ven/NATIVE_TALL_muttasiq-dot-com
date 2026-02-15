import './support/dispatch';
import './support/css-variables';
import './support/animate-scroll';

import './packages/alpine/hash-actions';
import './packages/alpine/hooks.js';
import './packages/nativephp/browser.js';
import './packages/color';
import './packages/tippy';
import './packages/fitty';
import './packages/anime';

import './support/alpine/data/layout-manager';
import './support/alpine/data/main-menu';
import './support/alpine/data/athkar-app-gate';
import './support/alpine/data/athkar-app-reader';
import './support/alpine/data/athkar-app-manager';
import './support/alpine/storage/font-manager';
import './support/alpine/storage/color-scheme';
import './support/alpine/storage/breakpointer';
import './support/alpine/directive/image-loaded';
import './support/alpine/directive/top-scroller';
import './support/alpine/magic/clipboard';
import './support/alpine/magic/top-scroller';
import './support/alpine/magic/livewire-lock';

import './overrides/livewire-session-expiry-reload';
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
