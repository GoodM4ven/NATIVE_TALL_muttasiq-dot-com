<div
    class="{{ \Illuminate\Support\Arr::toCssClasses([
        'bottom-4 sm:bottom-8 md:bottom-8 lg:bottom-8.5 xl:bottom-10 2xl:bottom-8 3xl:bottom-10' => !is_platform('mobile'),
        'bottom-7' => is_platform('mobile'),
        'fixed inset-x-0 z-30 flex w-full max-w-full justify-center px-4 opacity-0 transition-opacity sm:w-auto sm:max-w-none sm:px-5 md:px-5 lg:px-6 pointer-events-none sm:zoom-[0.95] md:zoom-[1] lg:zoom-[1] xl:zoom-[0.8] 2xl:zoom-[0.95] 3xl:zoom-[1.05] 4xl:zoom-[1.25]',
    ]) }}"
    data-testid="copyright-version-shell"
    x-bind:class="{
        'opacity-100!': views['main-menu'].isOpen || views['athkar-app-gate'].isOpen || views['quran-app-gate'].isOpen,
    }"
    x-data="{
        isVisible: false,
        isHovering: false,
        isTouching: false,
        shouldWaitForStartupSync: @js(is_platform('mobile')),
        isStartupReady: false,
        lastRevealEligibility: null,
        startupSyncResolvedListener: null,
        appVersion: @js(\App\Models\Setting::appVersion()),
        waitDuration: 3000,
        visibleDuration: 3000,
        touchHoldIncrementDuration: 2000,
        waitTimeoutId: null,
        hideTimeoutId: null,
        touchHoldTimeoutId: null,
        touchHoldExpiresAt: 0,
        isRevealEligible() {
            return Boolean(
                this.views?.['main-menu']?.isOpen ||
                this.views?.['athkar-app-gate']?.isOpen ||
                this.views?.['quran-app-gate']?.isOpen,
            );
        },
        isQuranReaderOpen() {
            return Boolean(
                this.views?.['quran-app-gate']?.isOpen ||
                this.views?.['quran-app-tilawa']?.isOpen ||
                this.views?.['quran-app-hifth']?.isOpen ||
                this.views?.['quran-app-tadabbur']?.isOpen,
            );
        },
        isQuranReaderDarkModeOpen() {
            const darkModeStore =
                this.$store?.colorScheme ??
                window.Alpine?.store?.('colorScheme');
    
            return Boolean(darkModeStore?.isDarkModeOn && this.isQuranReaderOpen());
        },
        isRevealLoopReady() {
            return this.isStartupReady && this.isRevealEligible();
        },
        syncRevealLoop() {
            const isEligible = this.isRevealLoopReady();
    
            if (!isEligible) {
                if (this.lastRevealEligibility !== false) {
                    this.clearLoopTimers();
                    this.clearTouchHoldTimer();
                    this.isVisible = false;
                    this.isHovering = false;
                    this.isTouching = false;
                }
    
                this.lastRevealEligibility = false;
                return;
            }
    
            const becameEligible = this.lastRevealEligibility !== true;
            this.lastRevealEligibility = true;
    
            if (becameEligible) {
                this.isVisible = false;
                this.queueNextReveal(this.waitDuration);
                return;
            }
    
            if (!this.isVisible && this.waitTimeoutId === null && this.hideTimeoutId === null) {
                this.queueNextReveal(this.waitDuration);
            }
        },
        setAppVersion(version) {
            if (typeof version !== 'string') {
                return;
            }
    
            const normalizedVersion = version.trim();
    
            if (!normalizedVersion) {
                return;
            }
    
            const versionState = window.appVersionRouting?.syncStoredAppVersion(normalizedVersion);
    
            this.appVersion = normalizedVersion;
    
            if (versionState?.shouldResetStartupView) {
                window.dispatchEvent(
                    new CustomEvent(
                        window.appVersionRouting?.appVersionMajorMinorResetEventName ??
                        'muttasiq-app-version-major-minor-reset', {
                            detail: versionState,
                        },
                    ),
                );
            }
        },
        isTouchDevice() {
            if (!$store.bp) {
                return false;
            }
    
            if (typeof $store.bp.isTouch === 'function') {
                return $store.bp.isTouch();
            }
    
            return Boolean($store.bp.hasTouch);
        },
        isHoverRevealEnabled() {
            if (this.isTouchDevice()) {
                return false;
            }
    
            if (!window.matchMedia) {
                return true;
            }
    
            return window.matchMedia('(hover: hover) and (pointer: fine)').matches;
        },
        clearLoopTimers() {
            if (this.waitTimeoutId) {
                clearTimeout(this.waitTimeoutId);
                this.waitTimeoutId = null;
            }
            if (this.hideTimeoutId) {
                clearTimeout(this.hideTimeoutId);
                this.hideTimeoutId = null;
            }
        },
        clearTouchHoldTimer() {
            if (this.touchHoldTimeoutId) {
                clearTimeout(this.touchHoldTimeoutId);
                this.touchHoldTimeoutId = null;
            }
        },
        scheduleTouchHoldRelease() {
            this.clearTouchHoldTimer();
    
            if (!this.isTouching) {
                this.touchHoldExpiresAt = 0;
                return;
            }
    
            const remainingMs = Math.max(0, this.touchHoldExpiresAt - Date.now());
    
            if (remainingMs === 0) {
                this.finishTouchHold();
                return;
            }
    
            this.touchHoldTimeoutId = setTimeout(() => {
                this.finishTouchHold();
            }, remainingMs);
        },
        extendTouchHoldTimer() {
            const now = Date.now();
            const nextBase = Math.max(this.touchHoldExpiresAt, now);
    
            this.touchHoldExpiresAt = nextBase + this.touchHoldIncrementDuration;
            this.scheduleTouchHoldRelease();
        },
        finishTouchHold() {
            this.clearTouchHoldTimer();
            this.touchHoldExpiresAt = 0;
    
            if (!this.isTouching) {
                return;
            }
    
            this.isTouching = false;
            this.releaseVisible();
        },
        queueNextReveal(delay = this.waitDuration) {
            this.clearLoopTimers();
    
            if (!this.isRevealLoopReady()) {
                this.isVisible = false;
                return;
            }
    
            if (this.isHovering || this.isTouching) {
                this.isVisible = true;
                return;
            }
            this.waitTimeoutId = setTimeout(() => {
                if (!this.isRevealLoopReady()) {
                    this.isVisible = false;
                    return;
                }
    
                this.isVisible = true;
                this.hideTimeoutId = setTimeout(() => {
                    if (!this.isRevealLoopReady()) {
                        this.isVisible = false;
                        return;
                    }
    
                    if (this.isHovering || this.isTouching) {
                        return;
                    }
                    this.isVisible = false;
                    this.queueNextReveal();
                }, this.visibleDuration);
            }, delay);
        },
        holdVisible() {
            if (!this.isRevealLoopReady()) {
                return;
            }
    
            this.clearLoopTimers();
            this.isVisible = true;
        },
        releaseVisible() {
            if (!this.isRevealLoopReady()) {
                this.clearLoopTimers();
                this.isVisible = false;
                return;
            }
    
            this.isVisible = false;
            this.queueNextReveal();
        },
        handleMouseEnter() {
            if (!this.isHoverRevealEnabled()) {
                return;
            }
    
            this.isHovering = true;
            this.holdVisible();
        },
        handleMouseLeave() {
            if (!this.isHoverRevealEnabled()) {
                return;
            }
    
            this.isHovering = false;
            this.releaseVisible();
        },
        handleTouchStart() {
            if (!this.isTouchDevice()) {
                return;
            }
    
            this.isTouching = true;
            this.holdVisible();
            this.extendTouchHoldTimer();
        },
        init() {
            if (!this.shouldWaitForStartupSync || window.__startupSyncResolved === true) {
                this.isStartupReady = true;
                this.syncRevealLoop();
                return;
            }
    
            this.startupSyncResolvedListener = () => {
                this.startupSyncResolvedListener = null;
                this.isStartupReady = true;
                this.syncRevealLoop();
            };
    
            window.addEventListener('startup-sync-resolved', this.startupSyncResolvedListener, {
                once: true,
            });
        },
        destroy() {
            this.clearLoopTimers();
            this.clearTouchHoldTimer();
    
            if (this.startupSyncResolvedListener) {
                window.removeEventListener('startup-sync-resolved', this.startupSyncResolvedListener);
                this.startupSyncResolvedListener = null;
            }
        },
    }"
    x-on:app-version-updated.window="setAppVersion($event.detail?.version)"
    x-effect="syncRevealLoop()"
>
    <div
        class="3xl:text-[1rem] relative w-fit max-w-[90vw] cursor-default select-none rounded-lg border border-white/70 bg-gray-100/40 px-2.5 py-2 text-[clamp(0.4rem,2.5vw,0.8rem)] text-gray-600 opacity-0 shadow-2xl ring-1 ring-gray-200/70 transition-all duration-500 ease-out sm:max-w-none sm:rounded-2xl sm:px-4 sm:py-3 sm:text-[0.82rem] md:px-5 md:py-3 md:text-[0.88rem] lg:px-4 lg:py-3 lg:text-[0.85rem] xl:px-6 xl:py-4 xl:text-[1rem] 2xl:text-[0.9rem] dark:border-white/10 dark:bg-gray-900/20 dark:text-gray-300 dark:ring-white/10 zoom-(--ui-scale,1)"
        data-testid="copyright-version-panel"
        x-bind:class="{
            'opacity-100!': isVisible,
            'pointer-events-auto!': (isHoverRevealEnabled() || isVisible || isTouching) && (views['main-menu'].isOpen ||
                views['athkar-app-gate'].isOpen || views['quran-app-gate'].isOpen),
            'bg-gray-100/70! text-gray-800!': views['quran-app-gate'].isOpen,
        }"
        x-on:mouseenter="handleMouseEnter()"
        x-on:mouseleave="handleMouseLeave()"
        x-on:touchstart.passive="handleTouchStart()"
        x-on:selectstart.prevent
    >
        <p
            class="inline-flex max-w-full items-center justify-center gap-1 overflow-hidden whitespace-nowrap text-center leading-arabic [text-wrap:nowrap]">
            <span class="min-w-0 shrink overflow-hidden text-ellipsis whitespace-nowrap">
                {{ arabic_text('جميع الحقوق محفوظة') }} • {{ arabic_text('متسق') }} @ <span
                    x-text="window.dayjs().calendar('hijri').format('YYYY')"
                ></span> {{ arabic_text('هـ') }} •
            </span>
            <button
                class="inline shrink-0 whitespace-nowrap rounded-sm font-semibold text-gray-800 underline decoration-gray-400/80 underline-offset-4 transition-colors hover:text-gray-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400/60 dark:text-gray-100 dark:decoration-gray-400/60 dark:hover:text-white dark:focus-visible:ring-gray-200/40"
                data-testid="copyright-version-button"
                type="button"
                x-bind:class="{
                    'pointer-events-auto!': isVisible && (views['main-menu'].isOpen || views['athkar-app-gate']
                        .isOpen || views['quran-app-gate'].isOpen),
                    'text-primary-700! decoration-primary-500/70! hover:text-primary-900! focus-visible:ring-primary-500/35!': isQuranReaderDarkModeOpen(),
                }"
                x-on:click="$dispatch('request-open-control-panel-modal', { tab: 'updates' })"
            >
                <span x-text="`v${appVersion}`">v{{ \App\Models\Setting::appVersion() }}</span>
            </button>
        </p>
    </div>
</div>
