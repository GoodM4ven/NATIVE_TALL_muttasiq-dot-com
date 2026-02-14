import {
    readAthkarOverridesFromStorage,
    writeAthkarOverridesToStorage,
} from '../athkar-app-overrides';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('athkarAppManager', (config) => ({
        componentId: String(config.componentId ?? ''),
        managerScrollTop: null,
        init() {
            this.hydrateOverridesFromStorage();
            this.registerOverridesPersistenceListener();
            this.registerModalClosedListener();
        },
        hydrateOverridesFromStorage() {
            const overrides = readAthkarOverridesFromStorage();

            if (typeof this.$wire?.syncAthkarOverrides === 'function') {
                this.$wire.syncAthkarOverrides(overrides);
            }
        },
        registerOverridesPersistenceListener() {
            window.addEventListener('athkar-manager-overrides-persisted', (event) => {
                const detail = event?.detail ?? {};
                const eventComponentId = String(detail?.componentId ?? '');

                if (eventComponentId !== this.componentId) {
                    return;
                }

                const overrides = Array.isArray(detail?.overrides) ? detail.overrides : [];
                const normalizedOverrides = writeAthkarOverridesToStorage(overrides);

                window.dispatchEvent(
                    new CustomEvent('athkar-overrides-updated', {
                        detail: { overrides: normalizedOverrides },
                    }),
                );
            });
        },
        registerModalClosedListener() {
            window.addEventListener('modal-closed', (event) => {
                const closedModalId = String(event?.detail?.id ?? '');
                const componentPrefix = `fi-${this.componentId}-action-`;

                if (!closedModalId.startsWith(componentPrefix)) {
                    return;
                }

                if (closedModalId === `${componentPrefix}0`) {
                    return;
                }

                this.restoreManagerScroll();
            });
        },
        rememberManagerScroll(event = null) {
            const modalContent = this.$root?.closest?.('.fi-modal-content');

            if (!modalContent) {
                return;
            }

            this.managerScrollTop = modalContent.scrollTop;

            const triggerElement = event?.currentTarget;

            if (triggerElement instanceof HTMLElement) {
                triggerElement.blur();
            }

            const activeElement = document.activeElement;

            if (activeElement instanceof HTMLElement) {
                activeElement.blur();
            }
        },
        restoreManagerScroll() {
            if (typeof this.managerScrollTop !== 'number') {
                return;
            }

            const modalContent = this.$root?.closest?.('.fi-modal-content');

            if (!modalContent) {
                return;
            }

            const restoreScroll = () => {
                modalContent.scrollTop = this.managerScrollTop;
            };

            requestAnimationFrame(() => {
                restoreScroll();

                requestAnimationFrame(() => {
                    restoreScroll();
                });
            });

            window.setTimeout(() => {
                restoreScroll();
            }, 80);

            window.setTimeout(() => {
                const activeElement = document.activeElement;

                if (
                    activeElement instanceof HTMLElement &&
                    activeElement.closest('.athkar-manager-card')
                ) {
                    activeElement.blur();
                }

                restoreScroll();
            }, 160);
        },
    }));
});
