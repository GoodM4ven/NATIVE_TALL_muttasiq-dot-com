<div
    class="{{ \Illuminate\Support\Arr::toCssClasses([
        'bottom-3' => !is_platform('mobile'),
        'bottom-7' => is_platform('mobile'),
        'fixed inset-x-0 z-30 flex w-full max-w-full justify-center overflow-hidden px-4 opacity-0 transition-opacity sm:w-auto sm:max-w-none sm:px-6 pointer-events-none xl:[zoom:1.25]',
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
        hasStartedRevealLoop: false,
        startupSyncResolvedListener: null,
        appVersion: @js(\App\Models\Setting::appVersion()),
        waitDuration: 3000,
        visibleDuration: 3000,
        waitTimeoutId: null,
        hideTimeoutId: null,
        startRevealLoop() {
            if (this.hasStartedRevealLoop) {
                return;
            }
    
            this.hasStartedRevealLoop = true;
            this.queueNextReveal();
        },
        setAppVersion(version) {
            if (typeof version !== 'string') {
                return;
            }
    
            const normalizedVersion = version.trim();
    
            if (!normalizedVersion) {
                return;
            }
    
            this.appVersion = normalizedVersion;
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
        queueNextReveal(delay = this.waitDuration) {
            this.clearLoopTimers();
            if (this.isHovering || this.isTouching) {
                this.isVisible = true;
                return;
            }
            this.waitTimeoutId = setTimeout(() => {
                this.isVisible = true;
                this.hideTimeoutId = setTimeout(() => {
                    if (this.isHovering || this.isTouching) {
                        return;
                    }
                    this.isVisible = false;
                    this.queueNextReveal();
                }, this.visibleDuration);
            }, delay);
        },
        holdVisible() {
            this.clearLoopTimers();
            this.isVisible = true;
        },
        releaseVisible() {
            this.isVisible = false;
            this.queueNextReveal();
        },
        releaseVisibleAfter(delay) {
            this.clearLoopTimers();
            this.isVisible = true;
            this.hideTimeoutId = setTimeout(() => {
                if (this.isHovering || this.isTouching) {
                    return;
                }
                this.releaseVisible();
            }, delay);
        },
        handleMouseEnter() {
            if (this.isTouchDevice()) {
                return;
            }
            this.isHovering = true;
            this.holdVisible();
        },
        handleMouseLeave() {
            if (this.isTouchDevice()) {
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
        },
        handleTouchEnd() {
            if (!this.isTouchDevice()) {
                return;
            }
            this.isTouching = false;
            this.releaseVisibleAfter(this.visibleDuration);
        },
        init() {
            if (!this.shouldWaitForStartupSync || window.__startupSyncResolved === true) {
                this.startRevealLoop();
                return;
            }
    
            this.startupSyncResolvedListener = () => {
                this.startupSyncResolvedListener = null;
                this.startRevealLoop();
            };
    
            window.addEventListener('startup-sync-resolved', this.startupSyncResolvedListener, {
                once: true,
            });
        },
        destroy() {
            this.clearLoopTimers();
    
            if (this.startupSyncResolvedListener) {
                window.removeEventListener('startup-sync-resolved', this.startupSyncResolvedListener);
                this.startupSyncResolvedListener = null;
            }
        },
    }"
    x-on:mouseenter="handleMouseEnter()"
    x-on:mouseleave="handleMouseLeave()"
    x-on:touchstart.passive="handleTouchStart()"
    x-on:touchend.passive="handleTouchEnd()"
    x-on:touchcancel.passive="handleTouchEnd()"
    x-on:app-version-updated.window="setAppVersion($event.detail?.version)"
>
    <div
        class="relative w-fit max-w-[90vw] rounded-2xl border border-white/70 bg-gray-100/30 px-4 py-3 text-[clamp(0.6rem,2.65vw,0.8rem)] text-gray-600 opacity-0 ring-1 ring-gray-200/70 transition-all duration-500 ease-out sm:max-w-none sm:px-6 sm:py-4 sm:text-[1rem] dark:border-white/10 dark:bg-gray-900/20 dark:text-gray-300 dark:ring-white/10"
        data-testid="copyright-version-panel"
        x-bind:class="{
            'opacity-100!': isVisible,
            'bg-gray-100/80! text-gray-700!': views['quran-app-gate'].isOpen,
        }"
    >
        <p class="whitespace-normal text-center leading-tight">
            جميع الحقوق محفوظة •
            متسق @ <span x-text="window.dayjs().calendar('hijri').format('YYYY')"></span> هـ
            • النسخة
            <button
                class="inline whitespace-nowrap rounded-sm font-semibold text-gray-800 underline decoration-gray-400/80 underline-offset-4 transition-colors hover:text-gray-950 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-gray-400/60 dark:text-gray-100 dark:decoration-gray-400/60 dark:hover:text-white dark:focus-visible:ring-gray-200/40"
                data-testid="copyright-version-button"
                type="button"
                x-bind:class="isVisible && (views['main-menu'].isOpen || views['athkar-app-gate'].isOpen) && 'pointer-events-auto!'"
                x-on:click="$dispatch('open-control-panel-modal', { tab: 'updates' })"
            >
                <span x-text="`v${appVersion}`">v{{ \App\Models\Setting::appVersion() }}</span>
            </button>
        </p>
    </div>
</div>
