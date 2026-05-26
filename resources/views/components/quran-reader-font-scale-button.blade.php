<div
    class="sm:inset-e-21! md:inset-e-21! lg:inset-e-21! xl:inset-e-22! 2xl:inset-e-26! 3xl:inset-e-28! 4xl:end-29! xl:top-6.5 fixed top-5 z-30 sm:top-5 md:top-5 lg:top-5 2xl:top-8"
    data-stack-item
    x-data="{
        rippleStorageKey: 'quran-reader-font-scale-ripple-dismissed-v1',
        hasReaderRenderedOnce: false,
        hasDismissedRipple: false,
        init() {
            try {
                this.hasDismissedRipple = window.localStorage.getItem(this.rippleStorageKey) === '1';
            } catch (_) {
                this.hasDismissedRipple = false;
            }
        },
        shouldShowRipple() {
            return this.hasReaderRenderedOnce && !this.hasDismissedRipple;
        },
        dismissRipplePermanently() {
            if (this.hasDismissedRipple) {
                return;
            }
    
            this.hasDismissedRipple = true;
    
            try {
                window.localStorage.setItem(this.rippleStorageKey, '1');
            } catch (_) {
                // Ignore storage write failures.
            }
        },
    }"
    x-transition
    x-cloak
    x-on:quran-reader-calibration-finished.window="hasReaderRenderedOnce = true"
    x-on:quran-reader-font-scale-overlay-visibility.window="if (Boolean($event.detail?.open)) { dismissRipplePermanently() }"
    x-show="!isControlPanelOpen && !isAthkarManagerOpen &&
        !document.body.classList.contains('quran-reader-font-scale-overlay-open') && (
        views['quran-app-tilawa'].isOpen ||
        views['quran-app-hifth'].isOpen ||
        views['quran-app-tadabbur'].isOpen
    )"
>
    <div class="relative inline-grid place-items-center">
        <span
            class="border-primary-500/65 4xl:block-[3rem] 4xl:inline-[3rem] block-[2.45rem] inline-[2.45rem] sm:block-[2.75rem] sm:inline-[2.75rem] md:block-[2.85rem] md:inline-[2.85rem] lg:block-[2.85rem] lg:inline-[2.85rem] xl:block-[2.8rem] xl:inline-[2.8rem] 2xl:block-[2.9rem] 2xl:inline-[2.9rem] 3xl:block-[2.95rem] 3xl:inline-[2.95rem] pointer-events-none absolute left-1/2 top-1/2 z-0 block origin-center -translate-x-1/2 -translate-y-1/2 animate-ping rounded-full border transition-opacity duration-300 [animation-duration:1.85s] [animation-timing-function:cubic-bezier(0.2,1,0.22,1)]"
            x-cloak
            x-show="shouldShowRipple()"
        ></span>

        <span
            class="border-primary-400/60 4xl:block-[3rem] 4xl:inline-[3rem] block-[2.45rem] inline-[2.45rem] sm:block-[2.75rem] sm:inline-[2.75rem] md:block-[2.85rem] md:inline-[2.85rem] lg:block-[2.85rem] lg:inline-[2.85rem] xl:block-[2.8rem] xl:inline-[2.8rem] 2xl:block-[2.9rem] 2xl:inline-[2.9rem] 3xl:block-[2.95rem] 3xl:inline-[2.95rem] pointer-events-none absolute left-1/2 top-1/2 z-0 block origin-center -translate-x-1/2 -translate-y-1/2 animate-ping rounded-full border opacity-90 transition-opacity duration-300 [animation-delay:0.92s] [animation-duration:1.85s] [animation-timing-function:cubic-bezier(0.2,1,0.22,1)]"
            x-cloak
            x-show="shouldShowRipple()"
        ></span>

        <x-action-button
            class="relative z-10"
            data-testid="quran-font-scale-button"
            :useInvertedStyle="true"
            :iconName="'css-icons.format-line-height'"
            x-on:click="window.dispatchEvent(new CustomEvent('quran-reader-font-scale-toggle'))"
        />
    </div>
</div>
