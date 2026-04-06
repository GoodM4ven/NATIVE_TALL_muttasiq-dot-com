@assets
    <style>
        .main-menu-caption__ripples {
            position: absolute;
            inset: -8px;
            pointer-events: none;
            border-radius: inherit;
            z-index: -10;
            opacity: 0.7;
        }

        .main-menu-caption__ripple {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            border: 1px solid currentColor;
            opacity: 0;
            animation-delay: var(--ripple-delay, 0ms);
            animation-duration: var(--ripple-duration, 420ms);
            animation-timing-function: ease-out;
            animation-fill-mode: forwards;
            will-change: transform, opacity;
        }

        .main-menu-caption__burst {
            position: absolute;
            pointer-events: none;
            border-radius: 20px;
            opacity: 0;
            z-index: -20;
        }

        .main-menu-caption__burst {
            inset: -10px;
            border: 1px solid currentColor;
        }

        .main-menu-caption__shine {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            opacity: 0;
            z-index: 4;
            background: linear-gradient(110deg,
                    transparent 0%,
                    rgba(255, 255, 255, 0.7) 45%,
                    transparent 60%);
            pointer-events: none;
        }

        .main-menu-pattern {
            -webkit-mask-image: radial-gradient(circle at center,
                    #000 0%,
                    #000 25%,
                    transparent 80%,
                    transparent 100%);
            mask-image: radial-gradient(circle at center,
                    #000 0%,
                    #000 25%,
                    transparent 80%,
                    transparent 100%);
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-size: 100% 100%;
            mask-size: 100% 100%;
        }

        [data-main-menu-item] {
            --main-menu-item-index: 0;
            transition:
                opacity 280ms cubic-bezier(0.22, 1, 0.36, 1),
                transform 340ms cubic-bezier(0.22, 1, 0.36, 1),
                filter 300ms ease;
            transition-delay: calc(var(--main-menu-item-index) * 34ms);
            transform-origin: center;
            will-change: transform, opacity, filter;
        }

        [data-main-menu-item]:nth-child(1) {
            --main-menu-item-index: 0;
        }

        [data-main-menu-item]:nth-child(2) {
            --main-menu-item-index: 1;
        }

        [data-main-menu-item]:nth-child(3) {
            --main-menu-item-index: 2;
        }

        [data-main-menu-item]:nth-child(4) {
            --main-menu-item-index: 3;
        }

        [data-main-menu-item]:nth-child(5) {
            --main-menu-item-index: 4;
        }

        [data-main-menu-item]:nth-child(6) {
            --main-menu-item-index: 5;
        }

        [data-main-menu-item]:nth-child(7) {
            --main-menu-item-index: 6;
        }

        [data-main-menu-item]:nth-child(8) {
            --main-menu-item-index: 7;
        }

        [data-main-menu-item]:nth-child(9) {
            --main-menu-item-index: 8;
        }

        [data-main-menu-exiting='true'] [data-main-menu-item] {
            opacity: 0;
            transform: scale(0.72);
            filter: blur(1.3px);
        }

        [data-main-menu-exiting='false'] [data-main-menu-item] {
            opacity: 1;
            transform: scale(1);
            filter: blur(0);
            transition-delay: calc((8 - var(--main-menu-item-index)) * 20ms + 120ms);
        }

        .main-menu-caption--burst .main-menu-caption__burst {
            animation: main-menu-burst 900ms ease-out;
            will-change: transform, opacity;
        }

        .main-menu-caption--burst .main-menu-caption__ripple {
            animation-name: main-menu-ripple;
        }

        .main-menu-caption--burst .main-menu-caption__shine {
            animation: main-menu-shine 620ms ease-out;
        }

        @keyframes main-menu-ripple {
            0% {
                opacity: 0.4;
                transform: scale(var(--ripple-from, 0.92));
            }

            55% {
                opacity: 0.18;
            }

            100% {
                opacity: 0;
                transform: scale(var(--ripple-to, 1.25));
            }
        }

        @keyframes main-menu-burst {
            0% {
                opacity: 0.6;
                transform: scale(0.35);
            }

            60% {
                opacity: 0.2;
            }

            100% {
                opacity: 0;
                transform: scale(1.25);
            }
        }

        @keyframes main-menu-shine {
            0% {
                opacity: 0;
                transform: translateX(-35%) skewX(-12deg);
            }

            30% {
                opacity: 0.5;
            }

            100% {
                opacity: 0;
                transform: translateX(35%) skewX(-12deg);
            }
        }

        .dark .main-menu-caption__shine {
            background: linear-gradient(110deg, transparent 0%, rgba(226, 232, 240, 0.18) 45%, transparent 60%);
        }
    </style>
@endassets

<div
    {{ $attributes->merge(['class' => 'relative flex flex-col items-center will-change-[opacity]']) }}
    x-data="mainMenu($el)"
    x-on:main-menu-item-enter="handleItemEnter($event.detail)"
    x-on:main-menu-item-leave="handleItemLeave()"
    x-on:main-menu-item-click="handleItemClick($event.detail)"
    x-on:click.outside="handleOutside(true)"
>
    <!-- Pattern -->
    <span
        class="pointer-events-none absolute -inset-20 -z-10 opacity-20"
        aria-hidden="true"
    >
        <!-- Pattern layer (fills the whole span) -->
        <!-- Credits: https://heropatterns.com -->
        <span
            class="main-menu-pattern absolute inset-0 rounded-full"
            x-data='{
                get fill() {
                    return $store.colorScheme.isDarkModeOn
                        ? window.cssVar("--primary-100")
                        : window.cssVar("--primary-500");
                },
                get bgStyle() {
                    const svg = `
                        <svg xmlns="http://www.w3.org/2000/svg" width="152" height="152" viewBox="0 0 152 152">
                            <g fill-rule="evenodd">
                                <g id="masjid">
                                <path fill="${this.fill}" fill-opacity="0.2"
                                    d="M152 150v2H0v-2h28v-8H8v-20H0v-2h8V80h42v20h20v42H30v8h90v-8H80v-42h20V80h42v40h8V30h-8v40h-42V50H80V8h40V0h2v8h20v20h8V0h2v150zm-2 0v-28h-8v20h-20v8h28zM82 30v18h18V30H82zm20 18h20v20h18V30h-20V10H82v18h20v20zm0 2v18h18V50h-18zm20-22h18V10h-18v18zm-54 92v-18H50v18h18zm-20-18H28V82H10v38h20v20h38v-18H48v-20zm0-2V82H30v18h18zm-20 22H10v18h18v-18zm54 0v18h38v-20h20V82h-18v20h-20v20H82zm18-20H82v18h18v-18zm2-2h18V82h-18v18zm20 40v-18h18v18h-18zM30 0h-2v8H8v20H0v2h8v40h42V50h20V8H30V0zm20 48h18V30H50v18zm18-20H48v20H28v20H10V30h20V10h38v18zM30 50h18v18H30V50zm-2-40H10v18h18V10z"/>
                                </g>
                            </g>
                        </svg>
                    `.trim();

                    const encoded = encodeURIComponent(svg);

                    return {
                        backgroundImage: `url("data:image/svg+xml,${encoded}")`,
                        backgroundRepeat: "repeat",
                        backgroundSize: "152px 152px",
                        backgroundPosition: "center center",
                    };
                }
            }'
            x-bind:style="bgStyle"
        ></span>
    </span>

    <!-- Selected Item Caption -->
    <div
        class="pointer-events-none absolute inset-x-0 top-0 z-20 -mt-10 flex -translate-y-full select-none items-center justify-center overflow-visible">
        <div
            class="text-primary-800 dark:border-primary-100 dark:text-primary-100 text-shadow-sm dark:text-shadow-sm ring-primary-500/20 dark:ring-primary-200/30 pointer-events-none relative isolate inline-flex max-w-full items-center justify-center overflow-visible rounded-2xl border border-transparent px-10 py-4 text-2xl font-normal leading-relaxed opacity-0 ring-1 will-change-[transform,opacity] dark:backdrop-blur-sm"
            x-ref="captionWrap"
            x-bind:style="{
                boxShadow: ($store.colorScheme.isDarkModeOn ? captionShadowDark : captionShadow),
            }"
            x-bind:class="{
                'main-menu-caption--active': !isHidden,
            }"
        >
            <!-- Effects -->
            <span
                class="main-menu-caption__ripples will-change-[transform,opacity]"
                aria-hidden="true"
            >
                <span
                    class="main-menu-caption__ripple will-change-[transform,opacity]"
                    style="--ripple-delay: 150ms; --ripple-from: 0.99; --ripple-to: 1.18;"
                ></span>
            </span>
            <span
                class="main-menu-caption__burst will-change-[transform,opacity]"
                aria-hidden="true"
            ></span>

            <!-- Text -->
            <span
                class="font-arabic-serif z-30 whitespace-nowrap will-change-[transform,opacity]"
                x-ref="captionText"
            ></span>
        </div>
    </div>

    <!-- Items -->
    <div
        x-on:click.self="idleCaption()"
        x-ref="itemsGrid"
        x-on:touchstart="handleTouchStart($event)"
        x-on:touchmove.prevent="handleTouchMove($event)"
        x-on:touchend="handleTouchEnd($event)"
        x-on:touchcancel="handleTouchEnd($event)"
        {{ $attributes->twMerge(['grid grid-cols-3 place-items-center w-full gap-2 max-w-xs']) }}
    >
        <!-- Credits: https://uiverse.io/gharsh11032000/new-squid-17 -->
        {{ $slot }}
    </div>
</div>
