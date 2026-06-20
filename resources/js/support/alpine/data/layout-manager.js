document.addEventListener('alpine:init', () => {
    window.Alpine.data('layoutManager', (options = {}) => ({
        isFastUiMode: window.__APP_BROWSER_TEST_FAST_UI === true,
        shouldRunStartupSync: options.shouldRunStartupSync === true,
        isStartupSyncPending: false,
        startupSyncFallbackTimeoutId: null,
        isFontReady: false,
        isLayoutSetUp: false,
        isBodyVisible: false,
        isBlinkerShown: true,
        defaultTransitionDurationInMs: 350,
        fastTransitionDurationInMs: 250,
        useFastTransitionDuration: false,
        isActionOpen: false,
        isScrollingDisabled: false,

        completeStartupSync() {
            if (!this.isStartupSyncPending) {
                return;
            }

            this.isStartupSyncPending = false;

            if (this.startupSyncFallbackTimeoutId !== null) {
                window.clearTimeout(this.startupSyncFallbackTimeoutId);
                this.startupSyncFallbackTimeoutId = null;
            }

            window.__startupSyncResolved = true;
            window.dispatchEvent(new CustomEvent('startup-sync-resolved'));
        },

        init() {
            this.isStartupSyncPending = this.shouldRunStartupSync;
            window.__startupSyncResolved = !this.isStartupSyncPending;

            if (this.isStartupSyncPending) {
                window.addEventListener('startup-sync-finished', () => this.completeStartupSync(), {
                    once: true,
                });

                this.startupSyncFallbackTimeoutId = window.setTimeout(() => {
                    this.completeStartupSync();
                }, 3500);
            }

            if (this.isFastUiMode) {
                this.defaultTransitionDurationInMs = 0;
                this.fastTransitionDurationInMs = 0;
                this.useFastTransitionDuration = true;
                this.isFontReady = true;
                this.isLayoutSetUp = true;
                this.isBlinkerShown = false;
                this.isBodyVisible = true;
            } else {
                this.isBlinkerShown = false;
                this.isBodyVisible = true;
            }

            // ? Keep track of Filament action events
            window.addEventListener('open-modal', () => (this.isActionOpen = true));
            window.addEventListener('x-modal-opened', () => (this.isActionOpen = true));
            window.addEventListener(
                'opened-form-component-action-modal',
                () => (this.isActionOpen = true),
            );
            window.addEventListener('close-modal', () => (this.isActionOpen = false));
            window.addEventListener('close-modal-quietly', () => (this.isActionOpen = false));
            window.addEventListener('x-modal-closed', () => (this.isActionOpen = false));
            window.addEventListener(
                'closing-form-component-action-modal',
                () => (this.isActionOpen = false),
            );
            window.addEventListener(
                'closed-form-component-action-modal',
                () => (this.isActionOpen = false),
            );

            // // ? Auto-scroll to the top instantly upon load
            // if ('scrollRestoration' in history) history.scrollRestoration = 'manual';
            // window.addEventListener('beforeunload', () => window.Alpine.$topScroll());
            // window.addEventListener('load', () => window.Alpine.$topScroll());

            // ? Wait for font loading
            this.$store.fontManager.ready(() => (this.isFontReady = true));

            // ? Keep layout state in sync with font readiness
            window.Alpine.effect(() => {
                this.isLayoutSetUp = this.isFontReady;
            });
        },

        blink(
            shouldAwaitLivewire = false,
            shouldKeepWaiting = false,
            useDefaultTransitionDuration = false,
        ) {
            this.useFastTransitionDuration = true;
            this.isBlinkerShown = true;
            this.isBodyVisible = false;

            if (shouldKeepWaiting) {
                // never resolves -> any .then() never runs
                return new Promise(() => {});
            }

            const showBody = () => {
                this.useFastTransitionDuration = false;
                this.isBodyVisible = true;
                this.isBlinkerShown = false;
            };

            const duration = useDefaultTransitionDuration
                ? this.defaultTransitionDurationInMs
                : this.fastTransitionDurationInMs;

            return new Promise((resolve) => {
                const done = () => {
                    showBody();
                    resolve();
                };

                if (shouldAwaitLivewire) {
                    const stop = this.$wire.$hook('morphed', () => {
                        done();
                        stop();
                    });
                } else {
                    setTimeout(done, duration);
                }
            });
        },
    }));
});
