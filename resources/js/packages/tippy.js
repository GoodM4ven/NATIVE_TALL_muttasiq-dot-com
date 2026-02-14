import Tippy, { hideAll } from 'tippy.js';

const tippyInstances = new WeakMap();
const tooltipSuppressionUntil = {
    timestamp: 0,
};

const suppressTooltipsFor = (durationInMs = 400) => {
    const duration = Number.isFinite(durationInMs) ? Math.max(0, durationInMs) : 400;
    tooltipSuppressionUntil.timestamp = Date.now() + duration;
};

const areTooltipsSuppressed = () => tooltipSuppressionUntil.timestamp > Date.now();

const hideAllTooltips = ({ duration = 0, suppressMs = 0 } = {}) => {
    hideAll({ duration });

    if (suppressMs > 0) {
        suppressTooltipsFor(suppressMs);
    }
};

document.addEventListener('alpine:init', () => {
    window.Alpine.magic('tippy', (el) => (message, direction = 'top', durationInMs = 2000) => {
        const existing = tippyInstances.get(el);
        let instance = existing;

        if (!instance || instance.state.isDestroyed) {
            instance = Tippy(el, {
                content: message,
                placement: direction,
                trigger: 'manual',
                theme: window.Alpine.store('colorScheme').isDark ? 'light' : '',
            });

            instance._hideTimer = null;
            instance._clearHideTimer = () => {
                if (instance._hideTimer) {
                    clearTimeout(instance._hideTimer);
                    instance._hideTimer = null;
                }
            };

            tippyInstances.set(el, instance);
        } else {
            instance.setContent(message);
            instance.setProps({
                placement: direction,
                theme: window.Alpine.store('colorScheme').isDark ? 'light' : '',
            });
        }

        instance._clearHideTimer?.();

        if (areTooltipsSuppressed()) {
            instance.hide();

            return instance;
        }

        if (!instance.state.isShown) {
            instance.show();
        }

        if (Number.isFinite(durationInMs) && durationInMs > 0) {
            instance._hideTimer = setTimeout(() => instance.hide(), durationInMs);
        }

        return instance;
    });

    const modalLifecycleEvents = [
        'open-modal',
        'x-modal-opened',
        'close-modal',
        'close-modal-quietly',
        'modal-closed',
        'x-modal-closed',
    ];

    modalLifecycleEvents.forEach((eventName) => {
        window.addEventListener(eventName, () => {
            hideAllTooltips({ duration: 0, suppressMs: 450 });
        });
    });

    window.hideAllTippies = hideAllTooltips;
});
