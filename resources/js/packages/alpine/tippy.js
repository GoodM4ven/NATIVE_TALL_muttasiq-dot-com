import Tippy, { hideAll } from 'tippy.js';

const tippyInstances = new WeakMap();
const tooltipSuppressionUntil = {
    timestamp: 0,
};
const readerRootSelector = '[data-athkar-app-reader-root]';

const suppressTooltipsFor = (durationInMs = 400) => {
    const duration = Number.isFinite(durationInMs) ? Math.max(0, durationInMs) : 400;
    tooltipSuppressionUntil.timestamp = Date.now() + duration;
};

const areTooltipsSuppressed = () => tooltipSuppressionUntil.timestamp > Date.now();

const normalizeTooltipOptions = (direction = 'top', durationInMs = 2000, options = {}) => {
    if (direction && typeof direction === 'object' && !Array.isArray(direction)) {
        return {
            placement: direction.placement ?? direction.direction ?? 'top',
            durationInMs: direction.durationInMs ?? direction.duration ?? 2000,
            showWhenGuidancePanelsSkipped: Boolean(direction.showWhenGuidancePanelsSkipped),
            offset: direction.offset ?? null,
            disableFlip: Boolean(direction.disableFlip),
        };
    }

    const normalizedOptions =
        options && typeof options === 'object' && !Array.isArray(options) ? options : {};

    return {
        placement: direction,
        durationInMs,
        showWhenGuidancePanelsSkipped: Boolean(normalizedOptions.showWhenGuidancePanelsSkipped),
        offset: normalizedOptions.offset ?? null,
        disableFlip: Boolean(normalizedOptions.disableFlip),
    };
};

const hideTooltipInstance = (instance) => {
    if (!instance) {
        return null;
    }

    instance._clearHideTimer?.();
    instance.hide();

    return null;
};

const resolveReaderState = (el) => {
    const readerRoot = el.closest(readerRootSelector);

    if (!readerRoot || !window.Alpine?.$data) {
        return null;
    }

    return window.Alpine.$data(readerRoot);
};

const areGuidancePanelsSkipped = (el) => {
    const readerState = resolveReaderState(el);

    if (!readerState || typeof readerState.shouldSkipGuidancePanels !== 'function') {
        return false;
    }

    return Boolean(readerState.shouldSkipGuidancePanels());
};

const hideAllTooltips = ({ duration = 0, suppressMs = 0 } = {}) => {
    hideAll({ duration });

    if (suppressMs > 0) {
        suppressTooltipsFor(suppressMs);
    }
};

document.addEventListener('alpine:init', () => {
    window.Alpine.magic('tippy', (el) => {
        const showTooltip = (message, direction = 'top', durationInMs = 2000, options = {}) => {
            const {
                placement,
                durationInMs: resolvedDuration,
                showWhenGuidancePanelsSkipped,
                offset,
                disableFlip,
            } = normalizeTooltipOptions(direction, durationInMs, options);

            const existing = tippyInstances.get(el);
            let instance = existing;

            if (!instance || instance.state.isDestroyed) {
                const instanceOptions = {
                    content: message,
                    placement,
                    trigger: 'manual',
                    theme: window.Alpine.store('colorScheme').isDark ? 'light' : '',
                };

                if (offset !== null) {
                    instanceOptions.offset = offset;
                }

                if (disableFlip) {
                    instanceOptions.popperOptions = {
                        modifiers: [{ name: 'flip', enabled: false }],
                    };
                }

                instance = Tippy(el, instanceOptions);

                instance._hideTimer = null;
                instance._clearHideTimer = () => {
                    if (instance._hideTimer) {
                        clearTimeout(instance._hideTimer);
                        instance._hideTimer = null;
                    }
                };

                tippyInstances.set(el, instance);
            } else {
                const nextProps = {
                    placement,
                    theme: window.Alpine.store('colorScheme').isDark ? 'light' : '',
                };

                if (offset !== null) {
                    nextProps.offset = offset;
                }

                if (disableFlip) {
                    nextProps.popperOptions = {
                        modifiers: [{ name: 'flip', enabled: false }],
                    };
                }

                instance.setContent(message);
                instance.setProps(nextProps);
            }

            instance._clearHideTimer?.();

            if (
                areTooltipsSuppressed() ||
                (!showWhenGuidancePanelsSkipped && areGuidancePanelsSkipped(el))
            ) {
                instance.hide();

                return instance;
            }

            if (!instance.state.isShown) {
                instance.show();
            }

            if (Number.isFinite(resolvedDuration) && resolvedDuration > 0) {
                instance._hideTimer = setTimeout(() => instance.hide(), resolvedDuration);
            }

            return instance;
        };

        showTooltip.hide = () => hideTooltipInstance(tippyInstances.get(el));

        return showTooltip;
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
