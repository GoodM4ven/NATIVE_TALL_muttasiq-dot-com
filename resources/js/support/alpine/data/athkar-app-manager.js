import {
    readAthkarOverridesFromStorage,
    writeAthkarOverridesToStorage,
} from '../athkar-app-overrides';

document.addEventListener('alpine:init', () => {
    window.Alpine.data('athkarAppManager', (config) => ({
        componentId: String(config.componentId ?? ''),
        init() {
            this.hydrateOverridesFromStorage();
            this.registerOverridesPersistenceListener();
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
    }));
});
