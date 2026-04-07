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

        .main-menu-layout-shell {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: transform 460ms cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform;
        }

        .main-menu-layout-shell--insights-open {
            transform: translate3d(0, -1.45rem, 0);
        }

        .main-menu-insights-zone {
            width: min(100%, 21rem);
            margin-top: 0.88rem;
            padding: 0.45rem 0.4rem 0.8rem;
        }

        .main-menu-insights-trigger-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 1.85rem;
        }

        .main-menu-insights-trigger {
            position: relative;
            display: inline-flex;
            width: min(12.8rem, 86%);
            height: 0.48rem;
            border-radius: 9999px;
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--primary-200) 86%, transparent) 0%,
                    color-mix(in srgb, var(--primary-400) 64%, transparent) 48%,
                    color-mix(in srgb, var(--primary-200) 86%, transparent) 100%);
            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--primary-200) 74%, transparent),
                0 0 14px color-mix(in srgb, var(--primary-400) 40%, transparent);
            transition:
                transform 280ms cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 320ms ease,
                opacity 260ms ease;
            opacity: 0.92;
            outline: none;
            cursor: pointer;
        }

        .main-menu-insights-trigger::before {
            content: '';
            position: absolute;
            inset: -0.2rem;
            border-radius: inherit;
            background: radial-gradient(circle at 50% 50%,
                    color-mix(in srgb, var(--primary-300) 52%, transparent) 0%,
                    transparent 72%);
            opacity: 0;
            transition: opacity 280ms ease;
        }

        .main-menu-insights-trigger::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(105deg,
                    transparent 0%,
                    color-mix(in srgb, white 68%, transparent) 45%,
                    transparent 70%);
            opacity: 0;
            transform: translateX(-30%) skewX(-16deg);
            transition:
                opacity 260ms ease,
                transform 380ms ease;
        }

        .main-menu-insights-trigger:hover,
        .main-menu-insights-trigger:focus-visible,
        .main-menu-insights-trigger[data-expanded='true'] {
            opacity: 1;
            transform: translateY(-1px) scaleX(1.02);
            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--primary-300) 78%, transparent),
                0 0 20px color-mix(in srgb, var(--primary-400) 54%, transparent);
        }

        .main-menu-insights-trigger:hover::before,
        .main-menu-insights-trigger:focus-visible::before,
        .main-menu-insights-trigger[data-expanded='true']::before {
            opacity: 1;
        }

        .main-menu-insights-trigger:hover::after,
        .main-menu-insights-trigger:focus-visible::after,
        .main-menu-insights-trigger[data-expanded='true']::after {
            opacity: 0.7;
            transform: translateX(30%) skewX(-16deg);
        }

        .main-menu-insights-panel {
            width: 100%;
            border-radius: 1.15rem;
            border: 1px solid color-mix(in srgb, var(--primary-200) 66%, transparent);
            background: linear-gradient(165deg,
                    color-mix(in srgb, var(--gray-50) 88%, transparent) 0%,
                    color-mix(in srgb, var(--primary-50) 76%, transparent) 100%);
            backdrop-filter: blur(8px);
            box-shadow:
                0 18px 38px color-mix(in srgb, var(--primary-900) 13%, transparent),
                inset 0 1px 0 color-mix(in srgb, white 62%, transparent);
            padding: 0.76rem 0.86rem 0.82rem;
        }

        .dark .main-menu-insights-panel {
            border-color: color-mix(in srgb, var(--primary-200) 38%, transparent);
            background: linear-gradient(160deg,
                    color-mix(in srgb, var(--background-dark) 84%, transparent) 0%,
                    color-mix(in srgb, var(--gray-800) 74%, transparent) 100%);
            box-shadow:
                0 22px 38px color-mix(in srgb, black 38%, transparent),
                inset 0 1px 0 color-mix(in srgb, var(--primary-200) 24%, transparent);
        }

        .main-menu-insights-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            align-items: center;
            gap: 0.55rem 0.7rem;
        }

        .main-menu-insights-row+.main-menu-insights-row {
            margin-top: 0.52rem;
        }

        .main-menu-insights-title {
            color: color-mix(in srgb, var(--primary-900) 86%, transparent);
        }

        .dark .main-menu-insights-title {
            color: color-mix(in srgb, var(--primary-100) 84%, transparent);
        }

        .main-menu-insights-meta {
            display: inline-flex;
            align-items: center;
            gap: 0.36rem;
            font-size: 0.62rem;
            font-weight: 700;
            letter-spacing: 0.01em;
            color: color-mix(in srgb, var(--gray-700) 76%, transparent);
        }

        .dark .main-menu-insights-meta {
            color: color-mix(in srgb, var(--primary-100) 74%, transparent);
        }

        .main-menu-insights-state-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 1.08rem;
            border-radius: 9999px;
            padding: 0.12rem 0.45rem;
            font-size: 0.57rem;
            line-height: 1;
            font-weight: 700;
            border: 1px solid transparent;
            white-space: nowrap;
            transition: background-color 220ms ease, border-color 220ms ease, color 220ms ease;
        }

        .main-menu-insights-state-pill--complete {
            background: color-mix(in srgb, var(--success-100) 88%, transparent);
            border-color: color-mix(in srgb, var(--success-400) 45%, transparent);
            color: color-mix(in srgb, var(--success-700) 84%, transparent);
        }

        .dark .main-menu-insights-state-pill--complete {
            background: color-mix(in srgb, var(--success-700) 28%, transparent);
            border-color: color-mix(in srgb, var(--success-300) 50%, transparent);
            color: color-mix(in srgb, var(--success-100) 84%, transparent);
        }

        .main-menu-insights-state-pill--pending {
            background: color-mix(in srgb, var(--primary-100) 70%, transparent);
            border-color: color-mix(in srgb, var(--primary-300) 52%, transparent);
            color: color-mix(in srgb, var(--primary-700) 78%, transparent);
        }

        .dark .main-menu-insights-state-pill--pending {
            background: color-mix(in srgb, var(--primary-700) 28%, transparent);
            border-color: color-mix(in srgb, var(--primary-300) 38%, transparent);
            color: color-mix(in srgb, var(--primary-100) 82%, transparent);
        }

        .main-menu-insights-track {
            grid-column: 1 / -1;
            position: relative;
            height: 0.48rem;
            border-radius: 9999px;
            background: color-mix(in srgb, var(--background-dark) 14%, transparent);
            border: 1px solid color-mix(in srgb, var(--primary-200) 46%, transparent);
            overflow: hidden;
        }

        .dark .main-menu-insights-track {
            background: color-mix(in srgb, black 30%, transparent);
            border-color: color-mix(in srgb, var(--primary-200) 34%, transparent);
        }

        .main-menu-insights-fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--primary-400) 92%, transparent) 0%,
                    color-mix(in srgb, var(--primary-600) 95%, transparent) 100%);
            box-shadow:
                0 0 10px color-mix(in srgb, var(--primary-500) 52%, transparent),
                inset 0 -1px 0 color-mix(in srgb, black 26%, transparent);
            transition: width 520ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .main-menu-insights-fill--complete {
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--success-400) 88%, transparent) 0%,
                    color-mix(in srgb, var(--success-600) 94%, transparent) 100%);
            box-shadow:
                0 0 11px color-mix(in srgb, var(--success-500) 55%, transparent),
                inset 0 -1px 0 color-mix(in srgb, black 24%, transparent);
        }
    </style>
@endassets

<div
    {{ $attributes->merge(['class' => 'relative flex flex-col items-center will-change-[opacity]']) }}
    x-data="mainMenu($el, {
        progressLabels: {
            sabah: @js(arabic_text('أذكار الصباح')),
            wird: @js(arabic_text('الوِرد اليومي')),
            masaa: @js(arabic_text('أذكار المساء')),
        },
        progressStateLabels: {
            completed: @js(arabic_text('مكتمل')),
            inProgress: @js(arabic_text('قيد التقدّم')),
            notStarted: @js(arabic_text('لم يبدأ')),
        },
    })"
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

    <div
        class="main-menu-layout-shell"
        x-bind:class="{ 'main-menu-layout-shell--insights-open': isInsightsExpanded }"
    >
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

        <div
            class="main-menu-insights-zone"
            x-ref="insightsZone"
            x-on:mouseenter="handleInsightsHoverEnter()"
            x-on:mouseleave="handleInsightsHoverLeave()"
            x-on:focusin="handleInsightsFocusIn()"
            x-on:focusout="handleInsightsFocusOut($event)"
            x-on:touchstart.passive="handleInsightsTouchStart()"
        >
            <div class="main-menu-insights-trigger-wrap">
                <button
                    class="main-menu-insights-trigger"
                    data-testid="main-menu-insights-trigger"
                    type="button"
                    x-bind:data-expanded="isInsightsExpanded ? 'true' : 'false'"
                    x-bind:aria-expanded="isInsightsExpanded ? 'true' : 'false'"
                    x-bind:aria-label="isInsightsExpanded ? @js(arabic_text('إخفاء لوحة التقدّم اليومية')) : @js(arabic_text('إظهار لوحة التقدّم اليومية'))"
                    x-on:click.prevent="toggleInsightsPanel()"
                ></button>
            </div>

            <section
                class="main-menu-insights-panel"
                data-testid="main-menu-insights-panel"
                x-cloak
                x-show="isInsightsExpanded"
                x-transition:enter="transition-[opacity,transform] duration-420 ease-out"
                x-transition:enter-start="opacity-0 translate-y-3 scale-[0.97]"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition-[opacity,transform] duration-320 ease-in"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-[0.985]"
            >
                <div class="main-menu-insights-row">
                    <p
                        class="main-menu-insights-title text-[0.72rem] font-semibold leading-none sm:text-[0.76rem]"
                        x-text="progressLabels.sabah"
                    ></p>
                    <span class="main-menu-insights-meta">
                        <span x-text="`${dailyProgress.sabah.percent}%`"></span>
                    </span>
                    <div class="main-menu-insights-track">
                        <div
                            class="main-menu-insights-fill"
                            x-bind:class="{ 'main-menu-insights-fill--complete': dailyProgress.sabah.isComplete }"
                            x-bind:style="`width: ${dailyProgress.sabah.percent}%;`"
                        ></div>
                    </div>
                </div>

                <div class="main-menu-insights-row">
                    <p
                        class="main-menu-insights-title text-[0.72rem] font-semibold leading-none sm:text-[0.76rem]"
                        x-text="progressLabels.wird"
                    ></p>
                    <span class="main-menu-insights-meta">
                        <span x-text="`${dailyProgress.wird.percent}%`"></span>
                    </span>
                    <div class="main-menu-insights-track">
                        <div
                            class="main-menu-insights-fill"
                            x-bind:class="{ 'main-menu-insights-fill--complete': dailyProgress.wird.isComplete }"
                            x-bind:style="`width: ${dailyProgress.wird.percent}%;`"
                        ></div>
                    </div>
                </div>

                <div class="main-menu-insights-row">
                    <p
                        class="main-menu-insights-title text-[0.72rem] font-semibold leading-none sm:text-[0.76rem]"
                        x-text="progressLabels.masaa"
                    ></p>
                    <span class="main-menu-insights-meta">
                        <span x-text="`${dailyProgress.masaa.percent}%`"></span>
                    </span>
                    <div class="main-menu-insights-track">
                        <div
                            class="main-menu-insights-fill"
                            x-bind:class="{ 'main-menu-insights-fill--complete': dailyProgress.masaa.isComplete }"
                            x-bind:style="`width: ${dailyProgress.masaa.percent}%;`"
                        ></div>
                    </div>
                </div>
            </section>
        </div>
    </div>
</div>
