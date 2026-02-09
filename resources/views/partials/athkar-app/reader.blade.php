@assets
    <style>
        .athkar-reader {
            --athkar-accent: var(--warning-500);
            --athkar-accent-soft: color-mix(in srgb, var(--warning-500) 18%, transparent);
            --athkar-nav-active: var(--success-500);
            --athkar-nav-complete: color-mix(in srgb, var(--success-500) 90%, transparent);
            --athkar-nav-available: color-mix(in srgb, var(--success-500) 70%, transparent);
            --athkar-nav-pending: color-mix(in srgb, var(--gray-400) 45%, transparent);
            --athkar-nav-track: color-mix(in srgb, var(--background-dark) 65%, transparent);
            --athkar-panel-outline: color-mix(in srgb, var(--primary-500) 45%, transparent);
            --athkar-panel-pulse: color-mix(in srgb, var(--primary-400) 40%, transparent);
            --athkar-tap-pulse: color-mix(in srgb, var(--warning-400) 32%, transparent);
            --athkar-nav-active-fill: linear-gradient(90deg,
                    color-mix(in srgb, var(--success-500) 95%, transparent),
                    color-mix(in srgb, var(--success-400) 95%, transparent));
            --athkar-nav-preview-fill: linear-gradient(90deg,
                    color-mix(in srgb, var(--primary-400) 70%, transparent),
                    color-mix(in srgb, var(--success-400) 70%, transparent));
            --athkar-nav-flow: linear-gradient(90deg,
                    transparent 0%,
                    color-mix(in srgb, var(--primary-400) 35%, transparent) 35%,
                    color-mix(in srgb, var(--primary-500) 45%, transparent) 50%,
                    color-mix(in srgb, var(--success-400) 35%, transparent) 65%,
                    transparent 100%);
            --athkar-panel-bg: color-mix(in srgb, var(--background) 92%, transparent);
            --athkar-panel-border: color-mix(in srgb, var(--gray-200) 70%, transparent);
            --athkar-panel-shadow: 0 18px 32px color-mix(in srgb, var(--gray-900) 16%, transparent);
            --athkar-panel-inset: inset 0 0 0 1px color-mix(in srgb, var(--gray-900) 12%, transparent);
            --athkar-progress-track: color-mix(in srgb, var(--background-dark) 50%, transparent);
            --athkar-progress-border: color-mix(in srgb, var(--gray-300) 45%, transparent);
            --athkar-text-shimmer: white;
            --athkar-text-shimmer-strong: white;
            --athkar-text-base: var(--primary-950);
        }

        .dark .athkar-reader {
            --athkar-accent: var(--warning-400);
            --athkar-accent-soft: color-mix(in srgb, var(--warning-400) 22%, transparent);
            --athkar-nav-active: var(--success-400);
            --athkar-nav-complete: color-mix(in srgb, var(--success-400) 90%, transparent);
            --athkar-nav-available: color-mix(in srgb, var(--success-400) 70%, transparent);
            --athkar-nav-pending: color-mix(in srgb, var(--gray-700) 80%, transparent);
            --athkar-nav-track: color-mix(in srgb, var(--background-dark) 92%, transparent);
            --athkar-panel-outline: color-mix(in srgb, var(--primary-300) 55%, transparent);
            --athkar-panel-pulse: color-mix(in srgb, var(--primary-400) 50%, transparent);
            --athkar-tap-pulse: color-mix(in srgb, var(--warning-300) 35%, transparent);
            --athkar-nav-active-fill: linear-gradient(90deg,
                    color-mix(in srgb, var(--success-400) 92%, transparent),
                    color-mix(in srgb, var(--success-500) 92%, transparent));
            --athkar-nav-preview-fill: linear-gradient(90deg,
                    color-mix(in srgb, var(--primary-300) 60%, transparent),
                    color-mix(in srgb, var(--success-400) 60%, transparent));
            --athkar-nav-flow: linear-gradient(90deg,
                    transparent 0%,
                    color-mix(in srgb, var(--primary-500) 35%, transparent) 35%,
                    color-mix(in srgb, var(--primary-400) 50%, transparent) 50%,
                    color-mix(in srgb, var(--success-400) 35%, transparent) 65%,
                    transparent 100%);
            --athkar-panel-bg: color-mix(in srgb, var(--primary-200) 32%, transparent);
            --athkar-panel-border: color-mix(in srgb, var(--gray-800) 80%, transparent);
            --athkar-panel-shadow: 0 20px 34px color-mix(in srgb, var(--gray-950) 55%, transparent);
            --athkar-panel-inset: inset 0 0 0 1px color-mix(in srgb, var(--gray-950) 45%, transparent);
            --athkar-progress-track: color-mix(in srgb, var(--background-dark) 80%, transparent);
            --athkar-progress-border: color-mix(in srgb, var(--primary-200) 40%, transparent);
            --athkar-text-shimmer: white;
            --athkar-text-shimmer-strong: white;
            --athkar-text-base: var(--primary-50);
        }

        .athkar-panel {
            position: relative;
            border-radius: 2rem;
            border: none;
            background: var(--athkar-panel-bg);
            box-shadow: var(--athkar-panel-shadow);
            overflow: hidden;
            isolation: isolate;
        }

        .athkar-panel-actions {
            border-radius: 0.65rem;
            border: none;
            isolation: auto;
            overflow: visible;
            z-index: 30;
        }

        .athkar-panel::before {
            content: "";
            position: absolute;
            inset: 0;
            opacity: 0.55;
            pointer-events: none;
        }

        .athkar-panel::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            box-shadow:
                0 0 0 1px var(--athkar-panel-outline),
                0 0 0 0 color-mix(in srgb, var(--athkar-panel-outline) 70%, transparent);
            opacity: 0;
            pointer-events: none;
            transition: opacity 420ms ease;
        }

        .athkar-panel.is-sliding::after {
            opacity: 1;
        }

        .athkar-panel>* {
            position: relative;
            z-index: 1;
        }

        .athkar-panel__pulse {
            position: absolute;
            inset: 0;
            border-radius: inherit;
            pointer-events: none;
            opacity: 0;
            z-index: 0;
        }

        .athkar-panel.is-sliding .athkar-panel__pulse {
            animation: athkar-panel-pulse 900ms ease-out;
        }

        .athkar-panel.is-tap-pulse .athkar-panel__pulse {
            animation: athkar-panel-tap 520ms ease-out;
        }

        .athkar-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            will-change: transform, opacity;
            white-space: nowrap;
        }

        .athkar-count--rolling {
            display: grid;
            place-items: center;
        }

        .athkar-count__current,
        .athkar-count__prev,
        .athkar-count__next {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            white-space: nowrap;
            grid-area: 1 / 1;
        }

        .athkar-count--rolling .athkar-count__prev {
            animation: athkar-count-prev 520ms ease;
        }

        .athkar-count--rolling .athkar-count__next {
            animation: athkar-count-next 520ms ease;
        }

        .athkar-tap--pulse {
            animation: athkar-tap-pulse 520ms ease;
        }

        @keyframes athkar-panel-pulse {
            0% {
                opacity: 0.9;
                box-shadow: 0 0 0 0 var(--athkar-panel-pulse);
            }

            100% {
                opacity: 0;
                box-shadow: 0 0 0 28px rgba(15, 23, 42, 0);
            }
        }

        @keyframes athkar-panel-tap {
            0% {
                opacity: 0.75;
                box-shadow: 0 0 0 0 var(--athkar-tap-pulse);
            }

            100% {
                opacity: 0;
                box-shadow: 0 0 0 22px rgba(15, 23, 42, 0);
            }
        }

        @keyframes athkar-count-prev {
            0% {
                opacity: 1;
                transform: translateY(0);
            }

            100% {
                opacity: 0;
                transform: translateY(-8px);
            }
        }

        @keyframes athkar-count-next {
            0% {
                opacity: 0;
                transform: translateY(8px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes athkar-tap-pulse {
            0% {
                box-shadow: 0 0 0 0 var(--athkar-tap-pulse);
            }

            50% {
                box-shadow: 0 0 0 12px color-mix(in srgb, var(--athkar-tap-pulse) 65%, transparent);
            }

            100% {
                box-shadow: 0 0 0 0 rgba(15, 23, 42, 0);
            }
        }

        @keyframes athkar-text-shimmer {
            0% {
                background-position: 120% 50%, 0 0;
            }

            100% {
                background-position: -120% 50%, 0 0;
            }
        }

        .athkar-chip {
            border-radius: 0.325rem;
            border: 1px solid color-mix(in srgb, var(--gray-200) 70%, transparent);
            background: linear-gradient(135deg,
                    color-mix(in srgb, var(--background) 96%, transparent),
                    color-mix(in srgb, var(--background) 82%, transparent));
            color: var(--primary-700);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--gray-900) 8%, transparent);
        }

        .dark .athkar-chip {
            border-color: color-mix(in srgb, var(--primary-200) 40%, transparent);
            background: linear-gradient(135deg,
                    color-mix(in srgb, var(--background-dark) 92%, transparent),
                    color-mix(in srgb, var(--background-dark) 80%, transparent));
            color: var(--primary-100);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--gray-950) 40%, transparent);
        }

        .athkar-progress {
            height: 0.55rem;
            border-radius: 0;
            background: var(--athkar-progress-track);
            border: 1px solid var(--athkar-progress-border);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--gray-950) 18%, transparent);
            overflow: hidden;
        }

        .athkar-progress__fill {
            height: 100%;
            border-radius: inherit;
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--athkar-accent) 95%, transparent),
                    color-mix(in srgb, var(--athkar-accent) 75%, transparent));
            box-shadow: 0 0 10px color-mix(in srgb, var(--athkar-accent) 35%, transparent);
        }

        .athkar-tap {
            border-radius: 1.5rem;
            transition: border-color 200ms ease, background-color 200ms ease, box-shadow 200ms ease;
            -webkit-tap-highlight-color: transparent;
        }

        @media (hover: hover) and (pointer: fine) {
            .athkar-tap:hover {
                border-color: color-mix(in srgb, var(--warning-400) 65%, transparent);
                background-color: color-mix(in srgb, var(--warning-400) 12%, transparent);
            }

            .dark .athkar-tap:hover {
                border-color: color-mix(in srgb, var(--warning-400) 30%, transparent);
                background-color: color-mix(in srgb, var(--warning-400) 16%, transparent);
            }
        }

        .athkar-tap:focus-visible {
            outline: 2px solid color-mix(in srgb, var(--warning-400) 70%, transparent);
            outline-offset: 2px;
        }

        .athkar-text {
            display: inline-block;
            font-size: 1.125rem;
            line-height: 2;
            max-width: 100%;
            opacity: 0;
            transition: opacity 250ms ease;
        }

        .athkar-text.athkar-shimmer {
            position: relative;
            z-index: 0;
            color: transparent;
            -webkit-text-fill-color: transparent;
            background-image: linear-gradient(110deg,
                    transparent 0%,
                    transparent 45%,
                    var(--athkar-text-shimmer-strong) 50%,
                    transparent 55%,
                    transparent 100%),
                linear-gradient(var(--athkar-text-base), var(--athkar-text-base));
            background-size: 200% 100%, 100% 100%;
            background-position: 120% 50%, 0 0;
            background-repeat: no-repeat;
            background-clip: text;
            -webkit-background-clip: text;
            will-change: background-position;
        }

        .athkar-text.athkar-shimmer.is-shimmering {
            animation: athkar-text-shimmer var(--shimmer-duration, 1000ms) linear 1 forwards;
        }

        @supports not ((-webkit-background-clip: text) or (background-clip: text)) {
            .athkar-text.athkar-shimmer {
                -webkit-text-fill-color: var(--athkar-text-base, currentColor);
                color: var(--athkar-text-base, currentColor);
            }
        }

        .athkar-text.is-fit {
            opacity: 1;
        }

        @media (min-width: 640px) {
            .athkar-text {
                font-size: 1.3rem;
                line-height: 2.05;
            }
        }

        .athkar-complete-badge {
            border-radius: 0.45rem;
            /* border: 1px solid color-mix(in srgb, var(--success-500) 35%, transparent); */
            background: color-mix(in srgb, var(--success-500) 16%, transparent);
            color: var(--success-700);
        }

        .dark .athkar-complete-badge {
            border-color: color-mix(in srgb, var(--success-400) 35%, transparent);
            background: color-mix(in srgb, var(--success-400) 20%, transparent);
            color: var(--success-200);
        }

        @property --progress {
            syntax: "<percentage>";
            inherits: true;
            initial-value: 0%;
        }

        .athkar-counter-ring {
            background: conic-gradient(from 0deg,
                    color-mix(in srgb, var(--athkar-accent) 95%, transparent) 0%,
                    color-mix(in srgb, var(--athkar-accent) 75%, transparent) calc(var(--progress) * 0.6),
                    color-mix(in srgb, var(--athkar-accent) 95%, transparent) var(--progress),
                    color-mix(in srgb, var(--gray-300) 35%, transparent) 0);
            transition: --progress 320ms ease, background 320ms ease;
        }

        .dark .athkar-counter-ring {
            background: conic-gradient(var(--athkar-accent) var(--progress, 0%), color-mix(in srgb, var(--gray-800) 70%, transparent) 0);
        }

        .athkar-nav {
            background: var(--athkar-nav-track);
            border: 1px solid color-mix(in srgb, var(--gray-400) 35%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-950) 30%, transparent),
                0 10px 25px color-mix(in srgb, var(--gray-950) 25%, transparent);
            /* overflow: hidden; */
            isolation: isolate;
            border-radius: 0.125rem;
        }

        .dark .athkar-nav {
            border-color: color-mix(in srgb, var(--gray-800) 75%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-950) 70%, transparent),
                0 18px 30px color-mix(in srgb, var(--gray-950) 45%, transparent);
        }

        .athkar-nav__segments {
            position: absolute;
            inset: 1px;
            border-radius: inherit;
            filter: saturate(1.1);
            pointer-events: none;
        }

        .athkar-nav__flow {
            position: absolute;
            inset: 2px;
            border-radius: inherit;
            background-image: var(--athkar-nav-flow);
            background-size: 200% 100%;
            mix-blend-mode: screen;
            opacity: 0.75;
            animation: athkar-nav-flow 7.5s linear infinite;
            pointer-events: none;
        }

        .athkar-nav__highlight {
            position: absolute;
            top: 0;
            bottom: 0;
            border-radius: 0;
            z-index: 4;
            pointer-events: none;
            background: var(--athkar-nav-active-fill);
            filter: saturate(1.15);
            transition: left 220ms ease, width 220ms ease, background 240ms ease, box-shadow 240ms ease, opacity 200ms ease, filter 220ms ease;
            will-change: left, width;
        }

        .athkar-nav__segments,
        .athkar-nav__flow {
            transition: opacity 220ms ease;
        }

        .athkar-nav__arrow {
            border: 1px solid color-mix(in srgb, var(--gray-600) 40%, transparent);
            color: color-mix(in srgb, var(--gray-600) 85%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-300) 20%, transparent),
                0 10px 20px color-mix(in srgb, var(--gray-950) 25%, transparent);
        }

        .dark .athkar-nav__arrow {
            border-color: color-mix(in srgb, var(--gray-800) 75%, transparent);
            background:
                linear-gradient(135deg,
                    color-mix(in srgb, var(--background-dark) 95%, transparent),
                    color-mix(in srgb, var(--background-dark) 75%, transparent));
            color: color-mix(in srgb, var(--gray-200) 85%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-950) 55%, transparent),
                0 12px 22px color-mix(in srgb, var(--gray-950) 50%, transparent);
        }

        .athkar-nav__arrow:not(:disabled) {
            border-color: color-mix(in srgb, var(--primary-800) 60%, transparent);
            background: color-mix(in srgb, var(--primary-100) 30%, var(--background) 70%);
            color: color-mix(in srgb, var(--primary-950) 92%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--primary-500) 35%, transparent),
                0 10px 20px color-mix(in srgb, var(--primary-500) 25%, transparent);
        }

        .dark .athkar-nav__arrow:not(:disabled) {
            border-color: color-mix(in srgb, var(--primary-400) 70%, transparent);
            background:
                linear-gradient(135deg,
                    color-mix(in srgb, var(--primary-400) 30%, var(--background-dark) 70%),
                    color-mix(in srgb, var(--success-400) 40%, var(--background-dark) 60%));
            color: color-mix(in srgb, var(--primary-50) 92%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--primary-400) 45%, transparent),
                0 12px 22px color-mix(in srgb, var(--primary-500) 45%, transparent);
        }

        .athkar-nav__arrow:not(:disabled):hover {
            transform: translateY(-1px);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--primary-500) 45%, transparent),
                0 12px 24px color-mix(in srgb, var(--primary-500) 35%, transparent);
        }

        .athkar-nav__arrow:focus-visible {
            outline: 2px solid color-mix(in srgb, var(--primary-400) 70%, transparent);
            outline-offset: 2px;
        }

        @keyframes athkar-nav-flow {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 200% 50%;
            }
        }
    </style>
@endassets

<div
    class="absolute inset-0 z-10 flex select-none items-center justify-center px-4 py-5 sm:px-6 sm:py-12"
    x-cloak
    x-show="views[`athkar-app-gate`].isReaderVisible && !isCompletionVisible"
    x-bind:class="hintIndex !== null && 'z-30!'"
    x-bind:style="transitionStyles()"
    x-transition:enter="transition-all ease-out duration-700 delay-350"
    x-transition:enter-start="opacity-0! blur-[2px] athkar-shift-away"
    x-transition:enter-end="opacity-100 blur-0 athkar-shift-center"
    x-transition:leave="transition-all ease-in duration-300"
    x-transition:leave-start="opacity-100 blur-0 athkar-shift-center"
    x-transition:leave-end="opacity-0! blur-[2px] athkar-shift-away"
>
    <section
        class="athkar-reader relative flex h-full min-h-0 w-full max-w-5xl flex-col justify-center gap-4 sm:h-auto sm:gap-6"
    >
        <div
            class="athkar-panel athkar-panel-actions flex flex-wrap items-center gap-2 px-3 py-2 sm:flex-nowrap sm:gap-4 sm:px-4 sm:py-3">
            <span
                class="athkar-chip shadow-inner! relative inline-flex items-center justify-center px-3 py-2 text-xs font-semibold transition sm:px-4 sm:py-3"
                x-text="activeLabel"
            ></span>

            <div class="flex flex-1 items-center gap-0.5 text-xs text-gray-600 sm:gap-3 dark:text-gray-300">
                <span
                    class="text-primary-700 dark:text-primary-200 inline-flex min-w-[4.3rem] items-center justify-center gap-1 text-center text-[0.85rem] tabular-nums sm:min-w-[4.6rem] sm:text-[0.95rem]"
                >
                    <span
                        class="athkar-count"
                        x-bind:class="totalPulse.isActive && 'athkar-count--rolling'"
                    >
                        <span
                            class="athkar-count__current"
                            x-show="!totalPulse.isActive"
                            x-text="totalCompletedCount"
                        ></span>
                        <span
                            class="athkar-count__prev"
                            x-cloak
                            x-show="totalPulse.isActive"
                            x-text="totalPulse.prev"
                        ></span>
                        <span
                            class="athkar-count__next"
                            x-cloak
                            x-show="totalPulse.isActive"
                            x-text="totalPulse.next"
                        ></span>
                    </span>
                    <span x-text="`/ ${totalRequiredCount}`"></span>
                </span>
                <div
                    class="relative flex-1"
                    data-athkar-completion-toggle
                    x-on:mouseenter="showCompletionHack()"
                    x-on:mouseleave="hideCompletionHack()"
                    x-on:click="toggleCompletionHack()"
                    x-on:click.outside="hideCompletionHack({ force: true })"
                >
                    <div class="athkar-progress w-full">
                        <div
                            class="athkar-progress__fill transition-all duration-500"
                            x-bind:style="`width: ${slideProgressPercent}%;`"
                        ></div>
                    </div>
                    <livewire:athkar-app.hidden-completion-button />
                </div>
                <span
                    class="text-primary-700 dark:text-primary-200 ms-2 text-[0.85rem] sm:text-[0.95rem]"
                    x-text="`${slideProgressPercent}%`"
                ></span>
            </div>
        </div>

        <div
            class="athkar-panel Xoutline-primary-500/80 dark:Xoutline-primary-200/25 Xoutline-offset-[-0.75rem] sm:Xoutline-4 relative flex max-h-[60svh] min-h-0 flex-1 touch-pan-y flex-col overflow-hidden transition-all focus:outline-none active:outline-none sm:max-h-[60svh]"
            role="region"
            aria-roledescription="carousel"
            tabindex="0"
            x-bind:aria-label="activeLabel"
            x-bind:class="{
                'is-sliding': slide.isActive,
                'is-tap-pulse': tapPulse.isActive,
                'outline-transparent! dark:outline-transparent!': countPulse.isActive,
            }"
            x-on:click.capture="if (hintIndex !== null && !$event.target.closest('[data-hint-allow]')) { closeHint(); $event.stopPropagation(); $event.preventDefault(); }"
            x-on:pointerdown="swipeStart($event)"
            x-on:pointerup="swipeEnd($event)"
            x-on:pointercancel="swipeCancel()"
            x-on:touchstart="swipeStart($event)"
            x-on:touchend="swipeEnd($event)"
            x-on:touchcancel="swipeCancel()"
            x-on:keydown.arrow-left.prevent="next()"
            x-on:keydown.arrow-right.prevent="prev()"
            x-on:keydown.home.prevent="setActiveIndex(0)"
            x-on:keydown.end.prevent="setActiveIndex(activeList.length - 1)"
            x-on:keydown.escape.window="if (hintIndex !== null) { closeHint(); }"
        >
            <div
                class="athkar-panel__pulse"
                aria-hidden="true"
            ></div>
            <!-- Athkar -->
            <div
                class="flex h-full min-h-0 w-full flex-1 transition-transform duration-700 ease-out"
                x-bind:style="`transform: translateX(${activeIndex * 100}%);`"
            >
                <template
                    x-for="(item, index) in activeList"
                    x-bind:key="itemKey(item, index)"
                >
                    <article
                        class="relative flex h-full min-h-0 w-full shrink-0 flex-col px-3.5 pb-4 pt-4 transition-opacity duration-700 sm:px-10 sm:pb-8 sm:pt-7"
                        data-athkar-slide
                        x-bind:class="index === activeIndex ? 'opacity-100' : 'opacity-0'"
                        x-bind:data-active="index === activeIndex ? 'true' : 'false'"
                    >
                        <!-- Floating Mobile Counter (togglable) -->
                        <div
                            class="delay-250 pointer-events-none absolute right-2 top-2 z-30 overflow-visible opacity-0 transition-opacity sm:hidden"
                            data-athkar-mobile-counter
                            x-bind:class="{
                                /* overall visibility condition */
                                'opacity-100! pointer-events-auto!': (requiredCount(index) > 1 || countAt(index) >
                                        requiredCount(index)) &&
                                    (countAt(index) !== requiredCount(index)),
                            }"
                        >
                            <div class="group relative">
                                <!-- Top Right Counter -->
                                <button
                                    class="relative z-30 size-9 touch-manipulation transition-all"
                                    data-hint-allow
                                    type="button"
                                    aria-label="العدد"
                                    x-bind:class="isHintOpen(index) && 'size-16!'"
                                    x-on:click.stop="toggleHint(index)"
                                    x-bind:aria-expanded="isHintOpen(index)"
                                >
                                    <!-- Ring -->
                                    <div
                                        class="athkar-counter-ring absolute inset-0 rounded-full"
                                        x-bind:style="`--progress: ${countAt(index)&&requiredCount(index)?Math.min(100, (countAt(index) / requiredCount(index)) * 100):0}%`"
                                    ></div>

                                    <!-- Background -->
                                    <div
                                        class="bg-(--background) dark:bg-(--background-dark) absolute inset-[4px] rounded-full">
                                    </div>

                                    <!-- Counter text -->
                                    <div
                                        class="text-primary-800 dark:text-primary-100 absolute inset-0 flex items-center justify-center gap-0.5 whitespace-nowrap text-[0.6rem] font-semibold tabular-nums opacity-0 transition-opacity"
                                        x-bind:class="{ 'opacity-100!': isHintOpen(index) }"
                                        dir="ltr"
                                    >
                                        <span x-text="`${requiredCount(index)} /`"></span>

                                        <span
                                            class="athkar-count"
                                            x-bind:class="countPulse.index === index &&
                                                countPulse.isActive &&
                                                'athkar-count--rolling'"
                                        >
                                            <span
                                                class="athkar-count__current"
                                                x-show="!(countPulse.index === index && countPulse.isActive)"
                                                x-text="countAt(index)"
                                            ></span>
                                            <span
                                                class="athkar-count__prev"
                                                x-cloak
                                                x-show="countPulse.index === index && countPulse.isActive"
                                                x-text="countPulse.prev"
                                            ></span>
                                            <span
                                                class="athkar-count__next"
                                                x-cloak
                                                x-show="countPulse.index === index && countPulse.isActive"
                                                x-text="countPulse.next"
                                            ></span>
                                        </span>
                                    </div>
                                </button>

                                <!-- Completion button -->
                                <button
                                    class="bg-success-500/90 pointer-events-none absolute -bottom-2 right-0 z-30 flex h-7 w-7 scale-95 items-center justify-center rounded-full text-white opacity-0 shadow-lg transition-all duration-200"
                                    data-hint-allow
                                    type="button"
                                    aria-label="إتمام الذكر"
                                    x-bind:class="{
                                        'opacity-100! scale-100 pointer-events-auto!': isHintOpen(index),
                                    }"
                                    x-on:click.stop="completeThikr(index)"
                                >
                                    <x-icon
                                        class="h-4 w-4"
                                        name="heroicon-o-check"
                                    />
                                </button>
                            </div>

                            <!-- Label -->
                            <div
                                class="pointer-events-none absolute -left-12 top-1/2 z-30 -translate-y-1/2 select-none whitespace-nowrap text-[0.6rem] font-semibold text-gray-600 opacity-0 transition-opacity dark:text-gray-300"
                                x-bind:class="isHintOpen(index) && 'opacity-100!'"
                            >
                                العدد
                            </div>
                        </div>

                        <!-- Content -->
                        <div class="flex min-h-0 flex-1 flex-col gap-3 sm:gap-5">
                            <!-- Top Counter -->
                            <div class="hidden items-center justify-center gap-4 sm:flex">
                                <div class="flex items-center gap-3">
                                    <div class="group relative h-20 w-20 sm:h-24 sm:w-24">
                                        <!-- The Circle -->
                                        <div
                                            class="athkar-counter-ring absolute inset-0 rounded-full"
                                            x-bind:style="`--progress: ${countAt(index) && requiredCount(index) ? Math.min(100, (countAt(index) / requiredCount(index)) * 100) : 0}%`"
                                        ></div>

                                        <!-- Background -->
                                        <div
                                            class="bg-(--background) dark:bg-(--background-dark) absolute inset-[6px] rounded-full">
                                        </div>

                                        <!-- The Counter -->
                                        <div
                                            class="text-primary-800 dark:text-primary-100 absolute inset-0 flex select-none items-center justify-center gap-1 text-xs font-semibold tabular-nums sm:text-base"
                                            dir="ltr"
                                        >
                                            <span x-text="`${requiredCount(index)} /`"></span>
                                            <span
                                                class="athkar-count"
                                                x-bind:class="countPulse.index === index && countPulse.isActive &&
                                                    'athkar-count--rolling'"
                                            >
                                                <span
                                                    class="athkar-count__current"
                                                    x-show="!(countPulse.index === index && countPulse.isActive)"
                                                    x-text="countAt(index)"
                                                ></span>
                                                <span
                                                    class="athkar-count__prev"
                                                    x-cloak
                                                    x-show="countPulse.index === index && countPulse.isActive"
                                                    x-text="countPulse.prev"
                                                ></span>
                                                <span
                                                    class="athkar-count__next"
                                                    x-cloak
                                                    x-show="countPulse.index === index && countPulse.isActive"
                                                    x-text="countPulse.next"
                                                ></span>
                                            </span>
                                        </div>

                                        <!-- Hidden completion button -->
                                        <template x-if="requiredCount(index) > 1">
                                            <button
                                                class="bg-success-500/90 pointer-events-none absolute -bottom-2 right-1 flex h-8 w-8 scale-95 items-center justify-center rounded-full text-white opacity-0 shadow-lg transition-all duration-200 focus-visible:pointer-events-auto focus-visible:scale-100 focus-visible:opacity-100 group-hover:pointer-events-auto group-hover:scale-100 group-hover:opacity-100"
                                                type="button"
                                                aria-label="إتمام الذكر"
                                                x-show="countAt(index) !== requiredCount(index)"
                                                x-data="{ tip: null }"
                                                x-on:click.stop="tip?._clearHideTimer?.(); tip?.hide(); completeThikr(index)"
                                                x-on:mouseenter="tip = $tippy('إتمام الذكر', 'right')"
                                                x-on:mouseleave="tip?._clearHideTimer?.(); tip?.hide()"
                                                x-on:focus="tip = $tippy('إتمام الذكر', 'right')"
                                                x-on:blur="tip?._clearHideTimer?.(); tip?.hide()"
                                            >
                                                <x-icon
                                                    class="h-4 w-4"
                                                    name="heroicon-o-check"
                                                />
                                            </button>
                                        </template>

                                        <!-- Label -->
                                        <span
                                            class="absolute -left-10 top-1/2 -translate-y-1/2 select-none text-sm text-gray-600 sm:-left-14 sm:text-base dark:text-gray-300"
                                        >العدد</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Althikr -->
                            <button
                                class="athkar-tap group relative flex min-h-0 w-full flex-1 touch-manipulation flex-col items-center justify-center gap-4 overflow-hidden rounded-sm border border-transparent px-2 py-3 text-center transition sm:px-4 sm:py-6"
                                data-athkar-tap
                                type="button"
                                x-on:click="handleTap()"
                                x-bind:class="{
                                    'opacity-30!': isHintOpen(index),
                                    'athkar-tap--pulse': tapPulse.index === index && tapPulse.isActive,
                                }"
                            >
                                <div
                                    class="{{ twMerge('flex w-full min-h-0 flex-1 flex-col gap-3 overflow-visible sm:gap-4 sm:px-10 justify-center transition-opacity') }}"
                                    data-athkar-text-box
                                    dir="rtl"
                                >
                                    <p
                                        class="athkar-text athkar-shimmer font-arabic-serif text-primary-950 dark:text-primary-50 whitespace-break-spaces!"
                                        data-athkar-text
                                        data-athkar-shimmer
                                        data-shimmer-duration="3000"
                                        data-shimmer-delay="1000"
                                        data-shimmer-pause="4000"
                                        dir="rtl"
                                        x-text="item.text"
                                    ></p>
                                </div>
                            </button>

                            <!-- Completion Indicator -->
                            <div
                                class="flex items-center justify-between gap-3 text-sm text-gray-600 sm:text-base dark:text-gray-300">
                                <span
                                    class="athkar-complete-badge px-2.5 py-1 text-[0.65rem] font-semibold opacity-0 transition-opacity duration-150 sm:px-3 sm:text-sm"
                                    x-bind:class="isItemComplete(index) && 'opacity-100!'"
                                >تم بحمد الله</span>
                                <span
                                    class="text-primary-700 dark:text-primary-200 inline-flex min-w-[4.4rem] items-center justify-center gap-1 text-center tabular-nums opacity-0 transition-opacity duration-300"
                                    x-data="{
                                        isVisible: false,
                                        timer: null,
                                    }"
                                    x-bind:class="isVisible && 'opacity-100!'"
                                    x-effect="
                                                if (slide.isActive) {
                                                    clearTimeout(timer);
                                                    isVisible = false;
                                                } else {
                                                    timer = setTimeout(() => (isVisible = true), 300);
                                                }
                                            "
                                >
                                    <span
                                        class="athkar-count"
                                        x-bind:class="pagePulse.isActive && 'athkar-count--rolling'"
                                    >
                                        <span
                                            class="athkar-count__current"
                                            x-show="!pagePulse.isActive"
                                            x-text="activeIndex + 1"
                                        ></span>
                                        <span
                                            class="athkar-count__prev"
                                            x-cloak
                                            x-show="pagePulse.isActive"
                                            x-text="pagePulse.prev"
                                        ></span>
                                        <span
                                            class="athkar-count__next"
                                            x-cloak
                                            x-show="pagePulse.isActive"
                                            x-text="pagePulse.next"
                                        ></span>
                                    </span>
                                    <span x-text="`/ ${activeList.length}`"></span>
                                </span>
                            </div>
                        </div>
                    </article>
                </template>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button
                class="athkar-nav__arrow inline-flex h-7 w-7 items-center justify-center rounded-sm transition disabled:cursor-not-allowed disabled:opacity-60"
                type="button"
                aria-label="السابق"
                x-bind:disabled="activeIndex === 0"
                x-on:click="prev()"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="m8.25 4.5 7.5 7.5-7.5 7.5"
                    />
                </svg>
            </button>

            <div class="flex-1">
                <div
                    class="athkar-nav relative h-6 w-full touch-pan-y select-none rounded-sm"
                    role="slider"
                    dir="rtl"
                    x-ref="athkarNav"
                    x-bind:aria-valuemin="1"
                    x-bind:aria-valuemax="activeList.length"
                    x-bind:aria-valuenow="activeIndex + 1"
                    x-bind:aria-valuetext="`${activeList.length} / ${activeIndex + 1}`"
                    x-on:pointerdown.prevent="navStart($event)"
                    x-on:pointermove="navMove($event)"
                    x-on:pointerenter="navEnter()"
                    x-on:pointerup="navEnd($event)"
                    x-on:pointerleave="navLeave()"
                    x-on:pointercancel="navCancel()"
                >
                    <div
                        class="athkar-nav__segments"
                        x-bind:style="`background-image: ${navGradient};`"
                    ></div>
                    <div
                        class="athkar-nav__flow"
                        aria-hidden="true"
                    ></div>
                    <div
                        class="athkar-nav__highlight rounded-[1px]!"
                        aria-hidden="true"
                        x-bind:style="`left: ${segmentLeftPercent(navPreviewIndex ?? activeIndex)}; width: ${segmentWidthPercent()}%; background: ${navPreviewIndex !== null ? 'var(--athkar-nav-preview-fill)' : 'var(--athkar-nav-active-fill)'}; box-shadow: ${navPreviewIndex !== null ? '0 0 0 1px color-mix(in srgb, var(--primary-400) 55%, transparent), 0 0 10px color-mix(in srgb, var(--primary-400) 45%, transparent)' : '0 0 0 1px color-mix(in srgb, var(--success-500) 65%, transparent), 0 0 16px color-mix(in srgb, var(--success-500) 55%, transparent)'};`"
                    ></div>
                    <div
                        class="pointer-events-none absolute -top-8"
                        x-bind:style="`left: ${segmentCenterPercent(navPreviewIndex ?? 0)}; transform: translateX(-50%);`"
                    >
                        <div
                            class="bg-(--background) text-primary-700 dark:bg-(--background-dark) dark:text-primary-100 rounded-sm border border-gray-200 px-2 py-0.5 text-[0.65rem] font-semibold shadow-sm dark:border-gray-700"
                            x-bind:style="{
                                opacity: (navPreviewIndex !== null && nav.hasInteracted && nav.isHovering) ? 1 : 0,
                            }"
                            x-text="Number(navPreviewIndex ?? 0) + 1"
                        ></div>
                    </div>
                </div>
            </div>

            <button
                class="athkar-nav__arrow inline-flex h-7 w-7 items-center justify-center rounded-sm transition disabled:cursor-not-allowed disabled:opacity-60"
                type="button"
                aria-label="التالي"
                x-bind:disabled="!canAdvance()"
                x-on:click="next()"
            >
                <svg
                    class="h-4 w-4"
                    fill="none"
                    viewBox="0 0 24 24"
                    stroke="currentColor"
                    stroke-width="1.5"
                >
                    <path
                        stroke-linecap="round"
                        stroke-linejoin="round"
                        d="M15.75 19.5 8.25 12l7.5-7.5"
                    />
                </svg>
            </button>
        </div>
    </section>
</div>
