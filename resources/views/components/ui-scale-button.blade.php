@assets
    <style>
        .ui-scale-overlay {
            position: fixed;
            inset: 0;
            z-index: 70;
            display: grid;
            place-items: center;
            padding: 1rem;
        }

        .ui-scale-overlay__backdrop {
            position: absolute;
            inset: 0;
            background: rgb(255 255 255 / 0.2);
        }

        .dark .ui-scale-overlay__backdrop {
            background: rgb(2 6 23 / 0.54);
        }

        .ui-scale-overlay__panel {
            position: relative;
            display: grid;
            width: min(92vw, 24rem);
            gap: 0.9rem;
            border-radius: 1.05rem;
            border: 1px solid rgb(255 255 255 / 0.82);
            padding: 0.9rem 0.85rem 0.8rem;
            background: color-mix(in srgb, var(--background) 52%, transparent);
            box-shadow: 0 0.85rem 2rem rgb(7 47 58 / 0.18);
            justify-items: center;
        }

        .dark .ui-scale-overlay__panel {
            border-color: color-mix(in srgb, var(--primary-600) 50%, rgb(15 23 42 / 0.72));
            background: color-mix(in srgb, rgb(15 23 42) 84%, transparent);
            box-shadow: 0 0.95rem 2.2rem rgb(2 6 23 / 0.62);
        }

        .ui-scale-overlay__title {
            border: 1px solid color-mix(in srgb, var(--primary-300) 62%, transparent);
            border-radius: 9999px;
            background: color-mix(in srgb, white 88%, transparent);
            color: color-mix(in srgb, var(--primary-900) 84%, var(--gray-900));
            font-weight: 700;
            line-height: 1.2;
            font-size: 0.88rem;
            padding: 0.44rem 0.9rem;
        }

        .dark .ui-scale-overlay__title {
            border-color: color-mix(in srgb, var(--primary-500) 46%, transparent);
            background: color-mix(in srgb, rgb(30 41 59) 76%, transparent);
            color: color-mix(in srgb, var(--primary-100) 84%, white);
        }

        .ui-scale-overlay__value {
            min-width: 3.3rem;
            text-align: center;
            border: 1px solid color-mix(in srgb, var(--primary-300) 55%, transparent);
            color: color-mix(in srgb, var(--primary-900) 84%, var(--gray-900));
        }

        .dark .ui-scale-overlay__value {
            border-color: color-mix(in srgb, var(--primary-500) 46%, transparent);
            color: color-mix(in srgb, var(--primary-100) 84%, white);
        }
    </style>
@endassets

<div
    data-stack-item
    @class([
        'sm:top-5 md:top-5 lg:top-5' => !is_platform('ios'),
        'sm:top-9 md:top-9 lg:top-9' => is_platform('ios'),
        'sm:inset-e-21! md:inset-e-21! lg:inset-e-21! xl:inset-e-22! 2xl:inset-e-26! 3xl:inset-e-28! 4xl:end-29! xl:top-6.5 fixed top-5 z-30 2xl:top-8',
    ])
    x-data="{
        storageKey: 'app-ui-scale-v1',
        rippleStorageKey: 'app-ui-scale-ripple-dismissed-v1',
        min: 0.5,
        max: 1.5,
        step: 0.05,
        defaultScale: 1,
        scale: 1,
        isOverlayOpen: false,
        hasDismissedRipple: false,
        init() {
            try {
                const stored = parseFloat(window.localStorage.getItem(this.storageKey));
                if (!Number.isNaN(stored)) {
                    this.scale = this.clampScale(stored);
                }
            } catch (_) {
                // Ignore storage read failures.
            }
    
            try {
                this.hasDismissedRipple = window.localStorage.getItem(this.rippleStorageKey) === '1';
            } catch (_) {
                this.hasDismissedRipple = false;
            }
    
            this.applyScale();
        },
        clampScale(value) {
            const stepped = Math.round(value / this.step) * this.step;
    
            return Math.min(this.max, Math.max(this.min, Math.round(stepped * 100) / 100));
        },
        applyScale() {
            document.documentElement.style.setProperty('--ui-scale', String(this.scale));
        },
        setScale(value) {
            this.scale = this.clampScale(parseFloat(value));
            this.applyScale();
    
            try {
                window.localStorage.setItem(this.storageKey, String(this.scale));
            } catch (_) {
                // Ignore storage write failures.
            }
        },
        resetScale() {
            this.setScale(this.defaultScale);
        },
        displayValue() {
            return this.scale.toFixed(2);
        },
        shouldShowRipple() {
            return !this.hasDismissedRipple;
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
        openOverlay() {
            this.dismissRipplePermanently();
            this.isOverlayOpen = true;
        },
        closeOverlay() {
            this.isOverlayOpen = false;
        },
    }"
    x-transition
    x-cloak
    x-show="views['main-menu'].isOpen &&
        !isControlPanelOpen &&
        !isAthkarManagerOpen &&
        !isOverlayOpen"
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
            data-testid="ui-scale-button"
            :useInvertedStyle="true"
            :iconName="'css-icons.format-line-height'"
            x-on:click="openOverlay()"
        />
    </div>

    <template x-teleport="body">
        <div
            class="ui-scale-overlay"
            data-no-swipe
            x-cloak
            x-show="isOverlayOpen"
            x-transition.opacity.duration.180ms
            x-on:keydown.escape.window="closeOverlay()"
        >
            <div
                class="ui-scale-overlay__backdrop"
                x-on:click="closeOverlay()"
            ></div>
            <section
                class="ui-scale-overlay__panel flex flex-col items-center gap-3"
                x-on:click.stop
            >
                <p class="ui-scale-overlay__title font-arabic-sans">
                    {{ arabic_text('تحكم في حجم عناصر الواجهة الرئيسة') }}
                </p>

                <button
                    class="quran-page-slider-chip ui-scale-overlay__value select-none rounded-full px-2 py-[0.18rem] text-[0.72rem] font-semibold"
                    type="button"
                    tabindex="-1"
                    x-text="displayValue()"
                    x-on:click="resetScale()"
                ></button>
                <input
                    class="quran-page-slider min-w-42 h-[0.56rem] w-[min(70vw,15rem)] outline-none"
                    type="range"
                    aria-label="{{ arabic_text('حجم العناصر الرئيسة') }}"
                    tabindex="-1"
                    x-bind:min="min"
                    x-bind:max="max"
                    x-bind:step="step"
                    x-bind:value="scale"
                    x-on:input="setScale($event.target.value)"
                />
            </section>
        </div>
    </template>
</div>
