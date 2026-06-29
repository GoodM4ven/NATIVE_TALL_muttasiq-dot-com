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
        authTransitionDurationInMs: 500,
        fastTransitionDurationInMs: 250,
        useFastTransitionDuration: false,
        isActionOpen: false,
        isScrollingDisabled: false,
        isAuthHoldActive: false,
        authStatusMessage: null,

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

        closeOpenModals() {
            document.querySelectorAll('[data-fi-modal-id]').forEach((modal) => {
                const id = String(modal.getAttribute('data-fi-modal-id') || '').trim();

                if (id === '') {
                    return;
                }

                const detail = { id };

                window.dispatchEvent(new CustomEvent('close-modal-quietly', { detail }));
                window.dispatchEvent(new CustomEvent('close-modal', { detail }));
            });
        },

        startAuthHoldTransition() {
            window.__authReloadInProgress = true;
            this.isAuthHoldActive = true;
            this.authStatusMessage = null;
            this.defaultTransitionDurationInMs = this.authTransitionDurationInMs;
            this.useFastTransitionDuration = false;
            this.isBlinkerShown = true;
            this.isActionOpen = false;

            this.closeOpenModals();
        },

        startAuthReloadTransition() {
            window.__authReloadInProgress = true;
            this.isAuthHoldActive = false;
            this.defaultTransitionDurationInMs = 350;
            this.useFastTransitionDuration = false;
            this.isBlinkerShown = true;
            this.isActionOpen = false;

            this.closeOpenModals();

            return 0;
        },

        async runNativeAuthRestart() {
            const payload = window.nativeAuthRestart || {};
            const token = String(payload.token || '').trim();

            try {
                if (token !== '' && window.nativeSecureStorage?.set) {
                    await window.nativeSecureStorage.set('auth.telegram.restore', token);
                }
            } catch (_) {
                // Without persistence the restart would log the user back out,
                // so fall through and just reveal the (already authenticated) app.
            }

            window.dispatchEvent(
                new CustomEvent('auth-blink-reload', {
                    detail: {
                        nativeRestart: true,
                    },
                }),
            );
        },

        revealApp() {
            // Bail only when fully revealed already. During an auth *hold* the body
            // stays visible while the blinker is raised on top, so guarding on
            // `isBodyVisible` alone would leave that white overlay stuck forever
            // (e.g. returning from the Telegram browser without authenticating).
            if (this.isBodyVisible && !this.isBlinkerShown) {
                return;
            }

            window.__authReloadInProgress = false;
            this.isAuthHoldActive = false;
            this.authStatusMessage = null;
            this.defaultTransitionDurationInMs = 350;

            if (!this.isFastUiMode) {
                this.useFastTransitionDuration = false;
            }

            this.isBlinkerShown = false;
            this.isBodyVisible = true;
        },

        init() {
            const isNativePlatform = document.body?.classList.contains('native-platform') === true;

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
            window.addEventListener('native-auth-reveal', () => this.revealApp());
            window.addEventListener('focus', () => {
                if (this.isAuthHoldActive && window.dataBranch !== 'user') {
                    window.dispatchEvent(new CustomEvent('native-auth-reveal'));
                }
            });
            document.addEventListener('visibilitychange', () => {
                if (
                    document.visibilityState === 'visible' &&
                    this.isAuthHoldActive &&
                    window.dataBranch !== 'user'
                ) {
                    window.dispatchEvent(new CustomEvent('native-auth-reveal'));
                }
            });

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

            // Just logged in via native Telegram: hold the blinker, persist the
            // restore token, and restart (Quran-bootstrap UX).
            if (isNativePlatform && window.nativeAuthRestart) {
                void this.runNativeAuthRestart();

                return;
            }

            // Native + logged out: keep the blinker up across the whole auth
            // round-trip (cold-start restore / Telegram handoff / restart) so the
            // guest UI never flashes. native-auth-persistence reloads on a
            // successful restore or fires `native-auth-reveal` when there's nothing
            // to restore; the 8s fallback guarantees we never stay stuck.
            if (isNativePlatform && !this.isFastUiMode && window.dataBranch !== 'user') {
                window.addEventListener('native-auth-reveal', () => this.revealApp(), {
                    once: true,
                });
                window.setTimeout(() => this.revealApp(), 8000);

                return;
            }

            this.revealApp();
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
