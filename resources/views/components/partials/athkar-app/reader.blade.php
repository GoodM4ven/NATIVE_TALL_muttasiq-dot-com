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
            --athkar-scrollbar-track: color-mix(in srgb, var(--warning-100) 35%, transparent);
            --athkar-scrollbar-thumb: color-mix(in srgb, var(--warning-200) 80%, transparent);
            --athkar-scrollbar-thumb-hover: color-mix(in srgb, var(--warning-300) 80%, transparent);
            --athkar-manager-button-fill-start: color-mix(in srgb, var(--primary-600) 94%, var(--background));
            --athkar-manager-button-fill-end: color-mix(in srgb, var(--primary-500) 88%, var(--background));
            --athkar-manager-button-glow: color-mix(in srgb, var(--primary-400) 42%, transparent);
            --athkar-manager-button-bevel: color-mix(in srgb, var(--gray-900) 8%, transparent);
            --athkar-manager-button-text: var(--foreground-dark);
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
            --athkar-scrollbar-track: color-mix(in srgb, var(--warning-900) 45%, transparent);
            --athkar-scrollbar-thumb: color-mix(in srgb, var(--warning-300) 74%, transparent);
            --athkar-scrollbar-thumb-hover: color-mix(in srgb, var(--warning-200) 72%, transparent);
            --athkar-manager-button-fill-start: color-mix(in srgb, var(--primary-100) 96%, var(--background-dark));
            --athkar-manager-button-fill-end: color-mix(in srgb, var(--primary-50) 94%, var(--background-dark));
            --athkar-manager-button-glow: color-mix(in srgb, var(--primary-500) 34%, transparent);
            --athkar-manager-button-bevel: color-mix(in srgb, var(--gray-950) 40%, transparent);
            --athkar-manager-button-text: var(--primary-600);
        }

        .athkar-panel {
            position: relative;
            border: none;
            background: var(--athkar-panel-bg);
            box-shadow: var(--athkar-panel-shadow);
            overflow: hidden;
            isolation: isolate;
        }

        .athkar-panel-actions {
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
            contain: layout style;
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
            will-change: transform, opacity;
            animation: athkar-count-prev 520ms ease-out both;
        }

        .athkar-count--rolling .athkar-count__next {
            will-change: transform, opacity;
            animation: athkar-count-next 520ms ease-out both;
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
                background-position: -100% 50%, 0 0;
            }

            100% {
                background-position: 100% 50%, 0 0;
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

        /* Credits: https://uiverse.io/mrhyddenn/stale-cheetah-42 */
        .athkar-chip--manager {
            position: relative;
            /* overflow: hidden; */
            isolation: isolate;
            box-shadow:
                inset 0 0 0 1px var(--athkar-manager-button-bevel),
                0 0 0 0 rgba(15, 23, 42, 0);
            transition:
                box-shadow 220ms ease,
                color 220ms ease;
        }

        .athkar-chip--manager>span {
            position: relative;
            z-index: 1;
        }

        .athkar-chip--manager::after {
            content: "";
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(135deg,
                    var(--athkar-manager-button-fill-start),
                    var(--athkar-manager-button-fill-end));
            opacity: 0;
            pointer-events: none;
            transition: opacity 220ms ease;
        }

        .athkar-chip--manager::before {
            content: "";
            position: absolute;
            top: 7%;
            left: 0;
            width: 0;
            height: 86%;
            opacity: 0;
            transform: skewX(-20deg);
            pointer-events: none;
            z-index: 2;
        }

        .athkar-chip--manager:focus-visible,
        .athkar-chip--manager:active {
            color: var(--athkar-manager-button-text);
        }

        .athkar-chip--manager:focus-visible {
            box-shadow:
                inset 0 0 0 1px var(--athkar-manager-button-bevel),
                0 0 30px 5px var(--athkar-manager-button-glow);
        }

        .athkar-chip--manager:focus-visible::after,
        .athkar-chip--manager:active::after {
            opacity: 1;
        }

        .athkar-chip--manager:focus-visible::before,
        .athkar-chip--manager:active::before {
            animation: athkar-manager-sheen 500ms linear;
        }

        .athkar-chip--manager:active {
            box-shadow:
                inset 0 0 0 1px var(--athkar-manager-button-bevel),
                0 0 18px 3px var(--athkar-manager-button-glow);
        }

        @media (hover: hover) and (pointer: fine) {
            .athkar-chip--manager:hover {
                box-shadow:
                    inset 0 0 0 1px var(--athkar-manager-button-bevel),
                    0 0 30px 5px var(--athkar-manager-button-glow);
                color: var(--athkar-manager-button-text);
            }

            .athkar-chip--manager:hover::after {
                opacity: 1;
            }
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

        .athkar-tap.is-origin-active {
            border-color: color-mix(in srgb, var(--warning-400) 65%, transparent);
            background-color: color-mix(in srgb, var(--warning-400) 12%, transparent);
        }

        .dark .athkar-tap.is-origin-active {
            border-color: color-mix(in srgb, var(--warning-400) 30%, transparent);
            background-color: color-mix(in srgb, var(--warning-400) 16%, transparent);
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

        @media (min-width: 640px) {
            .athkar-text {
                font-size: 1.3rem;
                line-height: 2.05;
            }
        }

        .athkar-origin-text .athkar-text {
            line-height: 1.85;
        }

        @media (min-width: 640px) {
            .athkar-origin-text .athkar-text {
                line-height: 2.05;
            }
        }

        .athkar-text.athkar-shimmer {
            position: relative;
            z-index: 0;
            color: var(--athkar-text-base);
            -webkit-text-fill-color: currentColor;
            background-image: none;
            background-size: auto;
            background-position: 0 0;
            background-repeat: no-repeat;
            background-clip: border-box;
            -webkit-background-clip: border-box;
        }

        .athkar-text.athkar-shimmer.is-shimmering {
            color: transparent;
            -webkit-text-fill-color: transparent;
            background-image: linear-gradient(110deg,
                    transparent 0%,
                    transparent 45%,
                    var(--athkar-text-shimmer-strong) 50%,
                    transparent 55%,
                    transparent 100%),
                linear-gradient(var(--athkar-text-base), var(--athkar-text-base));
            background-size: 300% 100%, 100% 100%;
            background-position: -100% 50%, 0 0;
            background-repeat: no-repeat, repeat;
            background-clip: text;
            -webkit-background-clip: text;
            will-change: background-position;
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

        .athkar-text--muted {
            opacity: 0 !important;
        }

        .athkar-text-box--touch-scroll {
            overflow-x: hidden;
            overflow-y: auto;
            overscroll-behavior: contain;
            -webkit-overflow-scrolling: touch;
            touch-action: pan-y;
            scrollbar-width: thin;
            scrollbar-color: var(--athkar-scrollbar-thumb) var(--athkar-scrollbar-track);
            scrollbar-gutter: stable both-edges;
            --athkar-edge-fade-size: 1rem;
            --athkar-edge-fade-top-size: 0px;
            --athkar-edge-fade-bottom-size: 0px;
            -webkit-mask-image: linear-gradient(180deg,
                    transparent 0,
                    black var(--athkar-edge-fade-top-size),
                    black calc(100% - var(--athkar-edge-fade-bottom-size)),
                    transparent 100%);
            mask-image: linear-gradient(180deg,
                    transparent 0,
                    black var(--athkar-edge-fade-top-size),
                    black calc(100% - var(--athkar-edge-fade-bottom-size)),
                    transparent 100%);
            -webkit-mask-repeat: no-repeat;
            mask-repeat: no-repeat;
            -webkit-mask-size: 100% 100%;
            mask-size: 100% 100%;
        }

        .athkar-text-box--touch-scroll {
            justify-content: flex-start !important;
            align-items: stretch;
        }

        .athkar-text-box--touch-scroll::-webkit-scrollbar {
            width: 0.46rem;
        }

        @media (max-width: 639px) {
            .athkar-text-box--touch-scroll {
                inline-size: calc(100% - 3px);
            }
        }

        .athkar-text-box--touch-scroll::-webkit-scrollbar-track {
            border-radius: 999px;
            background: var(--athkar-scrollbar-track);
        }

        .athkar-text-box--touch-scroll::-webkit-scrollbar-thumb {
            border-radius: 999px;
            min-height: 2.2rem;
            border: 1px solid color-mix(in srgb, var(--warning-300) 35%, transparent);
            background: linear-gradient(180deg,
                    var(--athkar-scrollbar-thumb),
                    color-mix(in srgb, var(--athkar-scrollbar-thumb) 78%, var(--background-dark) 22%));
            background-clip: padding-box;
            box-shadow: 0 0 0 1px color-mix(in srgb, var(--warning-300) 22%, transparent);
        }

        .athkar-text-box--touch-scroll::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(180deg,
                    var(--athkar-scrollbar-thumb-hover),
                    color-mix(in srgb, var(--athkar-scrollbar-thumb-hover) 80%, var(--background-dark) 20%));
        }

        .athkar-text-box--touch-scroll.athkar-text-box--origin-scroll .athkar-origin-text {
            position: static;
            inset: auto;
            align-items: flex-start;
            justify-content: center;
            padding-block: 0;
        }

        .athkar-text-box--touch-scroll:not(.athkar-text-box--origin-scroll) .athkar-origin-text {
            display: none;
        }

        .athkar-text-box--touch-scroll.athkar-text-box--origin-scroll .athkar-main-text {
            display: none;
        }

        @media (min-width: 2560px) {

            .athkar-text-box--touch-scroll .athkar-origin-text,
            .athkar-text-box--origin-scroll .athkar-origin-text {
                padding-inline: 0.25rem;
            }
        }

        .athkar-main-text {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-block: inherit;
            padding-inline: inherit;
            opacity: 1;
            transition: opacity 200ms ease;
        }

        .athkar-main-text.is-main-hidden {
            opacity: 0 !important;
            pointer-events: none;
        }

        .athkar-text-box--touch-scroll:not(.athkar-text-box--origin-scroll) .athkar-main-text {
            position: static;
            inset: auto;
            align-items: flex-start;
            justify-content: center;
            padding-block: 0;
        }

        .athkar-origin-text {
            position: absolute;
            inset: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            padding-block: inherit;
            opacity: 0;
            pointer-events: none;
            transition-property: opacity;
            transition-timing-function: ease;
            transition-duration: 200ms;
        }

        .athkar-origin-text__content {
            margin: 0;
            inline-size: 100%;
            text-align: center;
        }

        .athkar-origin-text.is-origin-visible {
            opacity: 1 !important;
            pointer-events: auto;
        }

        .athkar-origin-indicator {
            --athkar-origin-corner-radius: 7px;
            position: relative;
            display: inline-grid;
            place-items: center;
            padding: 0;
            border: none;
            background: transparent;
            color: var(--info-600);
            transition: transform 180ms ease, color 200ms ease;
        }

        .athkar-origin-indicator::before {
            content: "";
            position: absolute;
            inset: var(--athkar-origin-inset);
            transform: rotate(45deg);
            border-radius: var(--athkar-origin-corner-radius);
            border: 1px solid color-mix(in srgb, var(--info-300) 65%, transparent);
            background: color-mix(in srgb, var(--info-50) 92%, transparent);
            box-shadow: 0 6px 14px color-mix(in srgb, var(--gray-900) 12%, transparent);
            transition: border-color 200ms ease, background-color 200ms ease, box-shadow 200ms ease;
        }

        .athkar-origin-indicator>* {
            position: relative;
            z-index: 1;
        }

        .athkar-origin-indicator__icon {
            display: block;
            flex-shrink: 0;
        }

        .athkar-origin-indicator:focus-visible {
            outline: none;
        }

        .athkar-origin-indicator:focus-visible::before {
            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--primary-400) 55%, transparent),
                0 0 0 7px color-mix(in srgb, var(--primary-300) 18%, transparent);
        }

        .athkar-origin-indicator.is-active {
            color: var(--warning-700);
        }

        .athkar-origin-indicator.is-active::before {
            border-color: color-mix(in srgb, var(--warning-500) 55%, transparent);
            background: color-mix(in srgb, var(--warning-100) 88%, transparent);
            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--warning-400) 45%, transparent),
                0 0 0 0 color-mix(in srgb, var(--warning-400) 30%, transparent);
            animation: athkar-origin-indicator-pulse 1.2s ease-in-out infinite;
        }

        .dark .athkar-origin-indicator {
            color: var(--info-300);
        }

        .dark .athkar-origin-indicator::before {
            border-color: color-mix(in srgb, var(--info-500) 55%, transparent);
            background: color-mix(in srgb, var(--info-900) 50%, transparent);
            box-shadow: 0 8px 18px color-mix(in srgb, var(--gray-950) 40%, transparent);
        }

        .dark .athkar-origin-indicator.is-active {
            color: var(--warning-100);
        }

        .dark .athkar-origin-indicator.is-active::before {
            border-color: color-mix(in srgb, var(--warning-400) 60%, transparent);
            background: color-mix(in srgb, var(--warning-500) 20%, transparent);
            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--warning-400) 50%, transparent),
                0 0 0 0 color-mix(in srgb, var(--warning-400) 30%, transparent);
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
            transition: --progress 320ms ease, background 320ms ease, opacity 300ms ease;
            will-change: opacity;
        }

        .dark .athkar-counter-ring {
            background: conic-gradient(var(--athkar-accent) var(--progress, 0%), color-mix(in srgb, var(--gray-800) 70%, transparent) 0);
        }

        .athkar-counter-repel {
            position: absolute;
            inset: -0.5rem;
            border-radius: 9999px;
            border: 2px solid color-mix(in srgb, var(--athkar-accent) 68%, transparent);
            box-shadow:
                0 0 0 0 color-mix(in srgb, var(--athkar-accent) 30%, transparent),
                0 0 24px color-mix(in srgb, var(--athkar-accent) 24%, transparent);
            opacity: 0;
            pointer-events: none;
            will-change: transform, opacity, box-shadow;
        }

        .dark .athkar-counter-repel {
            border-color: color-mix(in srgb, var(--athkar-accent) 74%, transparent);
            box-shadow:
                0 0 0 0 color-mix(in srgb, var(--athkar-accent) 32%, transparent),
                0 0 30px color-mix(in srgb, var(--athkar-accent) 26%, transparent);
        }

        [data-counter-pulse="active"] .athkar-counter-repel {
            animation: athkar-counter-repel 360ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        [data-counter-pulse="active"] .athkar-counter-ring {
            opacity: 0;
        }

        @keyframes athkar-counter-repel {
            0% {
                opacity: 0.88;
                transform: scale(0.94);
                box-shadow:
                    0 0 0 0 color-mix(in srgb, var(--athkar-accent) 40%, transparent),
                    0 0 18px color-mix(in srgb, var(--athkar-accent) 26%, transparent);
            }

            62% {
                opacity: 0.34;
                transform: scale(1.12);
            }

            100% {
                opacity: 0;
                transform: scale(1.28);
                box-shadow:
                    0 0 0 14px color-mix(in srgb, var(--athkar-accent) 0%, transparent),
                    0 0 4px color-mix(in srgb, var(--athkar-accent) 0%, transparent);
            }
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

        .quran-copy-popover {
            position: fixed;
            z-index: 90;
            pointer-events: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.28rem;
            transform: translate(-50%, -132%);
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-500) 54%, transparent);
            background: color-mix(in srgb, var(--background) 88%, transparent);
            color: color-mix(in srgb, var(--primary-900) 92%, var(--primary-950));
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--primary-100) 24%, transparent),
                0 8px 18px color-mix(in srgb, var(--gray-900) 20%, transparent);
            padding: 0.26rem 0.56rem;
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            font-size: 0.74rem;
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            direction: rtl;
        }

        .quran-copy-popover-enter {
            transition:
                opacity 180ms cubic-bezier(0.2, 0.75, 0.25, 1),
                transform 180ms cubic-bezier(0.2, 0.75, 0.25, 1);
        }

        .quran-copy-popover-enter-start {
            opacity: 0;
            transform: translate(-50%, -118%) scale(0.84);
        }

        .quran-copy-popover-enter-end {
            opacity: 1;
            transform: translate(-50%, -132%) scale(1);
        }

        .quran-copy-popover-leave {
            transition:
                opacity 220ms cubic-bezier(0.4, 0, 1, 1),
                transform 220ms cubic-bezier(0.4, 0, 1, 1);
        }

        .quran-copy-popover-leave-start {
            opacity: 1;
            transform: translate(-50%, -132%) scale(1);
        }

        .quran-copy-popover-leave-end {
            opacity: 0;
            transform: translate(-50%, -156%) scale(1);
        }

        @keyframes athkar-nav-flow {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 200% 50%;
            }
        }

        @keyframes athkar-origin-indicator-pulse {

            0%,
            100% {
                box-shadow:
                    0 0 0 1px color-mix(in srgb, var(--warning-400) 45%, transparent),
                    0 0 0 0 color-mix(in srgb, var(--warning-400) 30%, transparent);
            }

            50% {
                box-shadow:
                    0 0 0 1px color-mix(in srgb, var(--warning-400) 55%, transparent),
                    0 0 0 8px color-mix(in srgb, var(--warning-400) 18%, transparent);
            }
        }
    </style>
@endassets

<div
    class="absolute inset-0 z-10 flex select-none items-center justify-center px-4 pt-5 sm:px-3.5 sm:pb-0 sm:pt-0 md:px-5 md:py-0 lg:px-6 lg:py-12 xl:px-5 xl:py-9 2xl:px-6 2xl:py-12"
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
        class="athkar-reader sm:max-w-117 md:max-w-145 lg:max-w-165 xl:max-w-178 2xl:max-w-190 3xl:max-w-212 4xl:max-w-4xl 3xl:gap-6 relative flex h-full min-h-0 w-full max-w-[min(93svw,20rem)] flex-col justify-center gap-3 pb-3 pt-10 sm:h-auto sm:gap-2 sm:py-0 md:gap-[0.6rem] md:py-5 lg:gap-3 lg:py-0 xl:gap-4 2xl:gap-5"
    >
        <div
            class="athkar-panel athkar-panel-actions 3xl:px-4 3xl:py-3 flex flex-wrap items-center gap-2 rounded-[0.55rem] py-1.5 pe-2.5 ps-1.5 sm:flex-nowrap sm:gap-4 sm:rounded-[0.45rem] sm:py-1 sm:pe-2 sm:ps-1 md:rounded-[0.65rem] md:px-[0.45rem] md:py-[0.3rem] lg:px-2 lg:py-[0.3rem] xl:px-[0.58rem] xl:py-[0.34rem] 2xl:px-3 2xl:py-2">
            <button
                class="athkar-chip athkar-chip--manager focus-visible:outline-primary-500 3xl:text-xs 3xl:px-4 3xl:py-3 relative inline-flex cursor-pointer items-center justify-center px-2.5 py-1.5 text-[0.65rem] font-semibold shadow-inner transition focus-visible:outline-2 focus-visible:outline-offset-2 sm:px-2 sm:py-1.5 sm:text-[0.425rem] md:px-2 md:py-[0.4rem] md:text-[0.525rem] lg:px-[0.6rem] lg:py-2 lg:text-[0.575rem] xl:px-[0.7rem] xl:py-[0.580rem] xl:text-[0.6rem] 2xl:px-3 2xl:py-2 2xl:text-[0.7rem]"
                data-athkar-open-manager
                type="button"
                x-on:click="$tippy.hide(); openGateAndManageAthkar()"
                x-on:mouseenter="$tippy(@js(arabic_text('إدارة الأذكار')), 'bottom', 2000, { showWhenGuidancePanelsSkipped: true })"
                x-on:mouseleave="$tippy.hide()"
                x-on:focus="$tippy(@js(arabic_text('إدارة الأذكار')), 'bottom', 2000, { showWhenGuidancePanelsSkipped: true })"
                x-on:blur="$tippy.hide()"
            ><span x-text="activeLabel"></span></button>

            <div
                class="3xl:text-[0.95rem] flex flex-1 items-center gap-0.5 text-[0.8rem] text-gray-600 sm:gap-3 sm:text-[0.6rem] md:text-[0.65rem] lg:text-[0.7rem] xl:text-[0.7rem] 2xl:text-[0.85rem] dark:text-gray-300">
                <span
                    class="text-primary-700 dark:text-primary-200 inline-flex min-w-[4.3rem] items-center justify-center gap-1 text-center tabular-nums sm:min-w-[4.6rem]"
                    dir="ltr"
                >
                    <span x-text="`${totalRequiredCount} /`"></span>
                    <span class="athkar-count">
                        <span
                            class="athkar-count__current"
                            x-show="!(totalPulse.isActive && totalPulse.hasChanges)"
                            x-text="totalCompletedCount"
                        ></span>
                        <span
                            class="athkar-count__current inline-flex items-center gap-0"
                            x-cloak
                            x-show="totalPulse.isActive && totalPulse.hasChanges"
                        >
                            <template
                                x-for="segment in totalPulse.segments"
                                x-bind:key="segment.key"
                            >
                                <span class="inline-flex items-center">
                                    <span
                                        x-show="!segment.changed"
                                        x-text="segment.next"
                                    ></span>
                                    <span
                                        class="athkar-count athkar-count--rolling"
                                        x-show="segment.changed"
                                    >
                                        <span
                                            class="athkar-count__prev"
                                            x-text="segment.prev"
                                        ></span>
                                        <span
                                            class="athkar-count__next"
                                            x-text="segment.next"
                                        ></span>
                                    </span>
                                </span>
                            </template>
                        </span>
                    </span>
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
                    class="text-primary-700 dark:text-primary-200 ms-2"
                    x-text="`${slideProgressPercent}%`"
                ></span>
            </div>
        </div>

        <div
            class="athkar-panel Xoutline-primary-500/80 dark:Xoutline-primary-200/25 Xoutline-offset-[-0.75rem] sm:Xoutline-4 3xl:max-h-[min(42svh,37rem)] 4xl:max-h-[min(39svh,33rem)] 3xl:rounded-4xl relative flex max-h-[min(80svh,24.5rem)] min-h-0 flex-1 touch-pan-y flex-col overflow-hidden rounded-[1.35rem] transition-all focus:outline-none active:outline-none sm:max-h-[min(68svh,17rem)] sm:rounded-[1.2rem] md:max-h-[min(71svh,23rem)] md:rounded-3xl lg:max-h-[min(65svh,25rem)] lg:rounded-[1.8rem] xl:max-h-[min(75svh,26rem)] xl:rounded-[1.75rem] 2xl:max-h-[min(53svh,25rem)] 2xl:rounded-[1.9rem]"
            role="region"
            aria-roledescription="carousel"
            tabindex="0"
            x-bind:aria-label="activeLabel"
            x-bind:class="{
                'is-sliding': slide.isActive,
                'is-tap-pulse': tapPulse.isActive,
                'outline-transparent! dark:outline-transparent!': countPulse.isActive,
            }"
            x-on:click.capture="if (isHintOpen(activeIndex) && !$event.target.closest('[data-hint-allow]')) { closeHint(); $event.stopPropagation(); $event.preventDefault(); }"
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
            <div
                class="pointer-events-none absolute inset-x-0 top-2 z-30 h-10 overflow-visible sm:hidden"
                data-athkar-mobile-top-ui
            >
                <div
                    class="pointer-events-auto absolute left-2 top-0"
                    x-show="hasOrigin(activeIndex)"
                    x-transition.opacity.duration.200ms
                >
                    <button
                        class="athkar-origin-indicator size-10 [--athkar-origin-inset:4px]"
                        type="button"
                        x-bind:class="isOriginVisible(activeIndex) && 'is-active'"
                        x-bind:aria-pressed="isOriginVisible(activeIndex)"
                        x-on:click.stop="toggleOrigin(activeIndex)"
                        x-on:pointerdown.stop
                        x-on:touchstart.stop
                        x-on:mouseenter="$tippy(@js(arabic_text('مأثور')), 'right')"
                        x-on:mouseleave="$tippy.hide()"
                        x-on:focus="$tippy(@js(arabic_text('مأثور')), 'right')"
                        x-on:blur="$tippy.hide()"
                    >
                        <x-icon
                            class="athkar-origin-indicator__icon size-6"
                            name="bootstrap.exclamation-diamond"
                        />
                    </button>
                </div>

                <div
                    class="pointer-events-auto absolute inset-x-0 top-0 h-11 overflow-visible"
                    data-athkar-mobile-counter
                    x-bind:data-counter-pulse="shouldEnableVisualEnhancements() ? sharedCounterPulseState() : 'inactive'"
                    x-show="requiredCount(activeIndex) > 1 || countAt(activeIndex) > requiredCount(activeIndex) || !settingValue('does_automatically_switch_completed_athkar', true) || !settingValue('does_clicking_switch_athkar_too', true)"
                    x-transition.opacity.duration.250ms
                >
                    <div class="group relative h-11">
                        <button
                            class="pointer-events-auto absolute left-1/2 top-0 z-20 flex size-[2.6rem] origin-top -translate-x-1/2 touch-manipulation transition-all duration-200"
                            data-hint-allow
                            type="button"
                            aria-label="{{ arabic_text('العدد') }}"
                            x-bind:class="isHintOpen(activeIndex) ? 'size-16! pointer-events-none' : ''"
                            x-on:click.stop="toggleHint(activeIndex)"
                            x-on:pointerdown.stop
                            x-on:touchstart.stop
                            x-bind:aria-expanded="isHintOpen(activeIndex)"
                        >
                            <div class="athkar-counter-repel"></div>
                            <div
                                class="athkar-counter-ring absolute inset-0 rounded-full"
                                x-bind:style="sharedCounterProgressStyle()"
                            ></div>

                            <div
                                class="bg-(--background) dark:bg-(--background-dark) absolute inset-[4px] rounded-full">
                            </div>

                            <div
                                class="text-primary-800 dark:text-primary-100 absolute inset-0 flex items-center justify-center gap-0.5 whitespace-nowrap text-[0.6rem] font-semibold tabular-nums"
                                x-show="isHintOpen(activeIndex)"
                                x-transition.opacity.duration.200ms
                                dir="ltr"
                            >
                                <span x-text="`${requiredCount(activeIndex)} /`"></span>

                                <span class="athkar-count">
                                    <span
                                        class="athkar-count__current"
                                        x-show="!(countPulse.index === activeIndex && countPulse.isActive && countPulse.hasChanges)"
                                        x-text="countAt(activeIndex)"
                                    ></span>
                                    <span
                                        class="athkar-count__current inline-flex items-center gap-0"
                                        x-cloak
                                        x-show="countPulse.index === activeIndex && countPulse.isActive && countPulse.hasChanges"
                                    >
                                        <template
                                            x-for="segment in countPulse.segments"
                                            x-bind:key="segment.key"
                                        >
                                            <span class="inline-flex items-center">
                                                <span
                                                    x-show="!segment.changed"
                                                    x-text="segment.next"
                                                ></span>
                                                <span
                                                    class="athkar-count athkar-count--rolling"
                                                    x-show="segment.changed"
                                                >
                                                    <span
                                                        class="athkar-count__prev"
                                                        x-text="segment.prev"
                                                    ></span>
                                                    <span
                                                        class="athkar-count__next"
                                                        x-text="segment.next"
                                                    ></span>
                                                </span>
                                            </span>
                                        </template>
                                    </span>
                                </span>
                            </div>
                        </button>

                        <button
                            class="bg-success-500/90 z-9999 absolute inset-x-0 -bottom-2 mx-auto flex h-7 w-7 translate-x-[15px] translate-y-[20px] items-center justify-center rounded-full text-white shadow-lg"
                            data-hint-allow
                            type="button"
                            aria-label="{{ arabic_text('إتمام الذكر') }}"
                            x-show="isHintOpen(activeIndex) && requiredCount(activeIndex) > 1 && countAt(activeIndex) !== requiredCount(activeIndex)"
                            x-transition.opacity.duration.200ms
                            x-on:click.stop="requestSingleThikrCompletion(activeIndex)"
                            x-on:pointerdown.stop
                            x-on:mouseenter="$tippy(@js(arabic_text('إتمام الذكر')), 'right')"
                            x-on:mouseleave="$tippy.hide()"
                            x-on:focus="$tippy(@js(arabic_text('إتمام الذكر')), 'right')"
                            x-on:blur="$tippy.hide()"
                            x-on:touchstart.stop="$tippy(@js(arabic_text('إتمام الذكر')), 'right', 1200)"
                            x-on:touchend.stop="$tippy.hide()"
                        >
                            <x-icon
                                class="h-4 w-4"
                                name="heroicon-o-check"
                            />
                        </button>

                        <div
                            class="pointer-events-none absolute inset-x-0 top-1/2 z-30 mx-auto -mt-[2px] flex -translate-x-[3.2rem] translate-y-[0.3rem] select-none justify-center whitespace-nowrap text-[0.6rem] font-semibold text-gray-600 dark:text-gray-300"
                            x-show="isHintOpen(activeIndex)"
                            x-transition.opacity.duration.200ms
                        >
                            {{ arabic_text('العدد') }}
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="pointer-events-auto absolute top-7 z-40 hidden items-center justify-between gap-4 sm:top-2.5 sm:flex md:top-4 lg:top-5 xl:top-6 2xl:top-7"
                data-athkar-desktop-counter-row
            >
                <div class="w-16"></div>

                <div
                    class="3xl:text-base pointer-events-auto flex items-center justify-center gap-3 text-sm sm:text-[0.5rem] md:text-[0.6rem] lg:text-[0.65rem] xl:text-[0.7rem] 2xl:text-sm"
                    data-athkar-desktop-counter
                    x-bind:data-counter-pulse="shouldEnableVisualEnhancements() ? sharedCounterPulseState() : 'inactive'"
                >
                    <div
                        class="sm:h-15.5 sm:w-15.5 md:h-18 md:w-18 lg:h-19 lg:w-19 xl:w-19.5 2xl:h-22 2xl:w-22 3xl:h-24 3xl:w-24 group relative h-20 w-20 xl:h-20">
                        <div class="athkar-counter-repel"></div>
                        <div
                            class="athkar-counter-ring absolute inset-0 rounded-full"
                            x-bind:style="sharedCounterProgressStyle()"
                        ></div>

                        <div class="bg-(--background) dark:bg-(--background-dark) absolute inset-[6px] rounded-full">
                        </div>

                        <div
                            class="text-primary-800 dark:text-primary-100 absolute inset-0 flex select-none items-center justify-center gap-1 font-semibold tabular-nums"
                            dir="ltr"
                        >
                            <span x-text="`${requiredCount(activeIndex)} /`"></span>
                            <span class="athkar-count">
                                <span
                                    class="athkar-count__current"
                                    x-show="!(countPulse.index === activeIndex && countPulse.isActive && countPulse.hasChanges)"
                                    x-text="countAt(activeIndex)"
                                ></span>
                                <span
                                    class="athkar-count__current inline-flex items-center gap-0"
                                    x-cloak
                                    x-show="countPulse.index === activeIndex && countPulse.isActive && countPulse.hasChanges"
                                >
                                    <template
                                        x-for="segment in countPulse.segments"
                                        x-bind:key="segment.key"
                                    >
                                        <span class="inline-flex items-center">
                                            <span
                                                x-show="!segment.changed"
                                                x-text="segment.next"
                                            ></span>
                                            <span
                                                class="athkar-count athkar-count--rolling"
                                                x-show="segment.changed"
                                            >
                                                <span
                                                    class="athkar-count__prev"
                                                    x-text="segment.prev"
                                                ></span>
                                                <span
                                                    class="athkar-count__next"
                                                    x-text="segment.next"
                                                ></span>
                                            </span>
                                        </span>
                                    </template>
                                </span>
                            </span>
                        </div>

                        <template x-if="requiredCount(activeIndex) > 1">
                            <button
                                class="bg-success-500/90 z-9999 absolute bottom-0 right-0 flex h-8 w-8 items-center justify-center rounded-full text-white shadow-lg transition-all duration-200"
                                type="button"
                                aria-label="{{ arabic_text('إتمام الذكر') }}"
                                x-show="countAt(activeIndex) !== requiredCount(activeIndex)"
                                x-bind:class="completionHack.canHover && $store.bp.is('sm+') ?
                                    'pointer-events-none scale-95 opacity-0 group-hover:pointer-events-auto group-hover:scale-100 group-hover:opacity-100 group-focus-within:pointer-events-auto group-focus-within:scale-100 group-focus-within:opacity-100' :
                                    'pointer-events-auto scale-100 opacity-100'"
                                x-on:click.stop="$tippy.hide(); requestSingleThikrCompletion(activeIndex)"
                                x-on:mouseenter="$tippy(@js(arabic_text('إتمام الذكر')), 'right')"
                                x-on:mouseleave="$tippy.hide()"
                                x-on:focus="$tippy(@js(arabic_text('إتمام الذكر')), 'right')"
                                x-on:blur="$tippy.hide()"
                                x-on:touchstart="$tippy(@js(arabic_text('إتمام الذكر')), 'right', 1200)"
                                x-on:touchend="$tippy.hide()"
                            >
                                <x-icon
                                    class="h-4 w-4"
                                    name="heroicon-o-check"
                                />
                            </button>
                        </template>

                        <span
                            class="3xl:-left-14 absolute -left-10 top-1/2 -translate-y-1/2 select-none text-gray-600 sm:-left-8 md:-left-9 lg:-left-10 xl:-left-11 2xl:-left-12 dark:text-gray-300"
                        >{{ arabic_text('العدد') }}</span>
                    </div>
                </div>

                <div class="pointer-events-auto w-16">
                    <button
                        class="athkar-origin-indicator 3xl:size-13 3xl:[--athkar-origin-inset:7px] 4xl:size-13.5 4xl:[--athkar-origin-inset:8px] md:size-9.5 2xl:size-12.5 left-4 size-7 [--athkar-origin-inset:1px] sm:size-6 sm:[--athkar-origin-inset:1px] md:[--athkar-origin-inset:4.5px] lg:size-10 lg:[--athkar-origin-inset:5px] xl:size-12 xl:[--athkar-origin-inset:6px] 2xl:[--athkar-origin-inset:6px]"
                        type="button"
                        x-show="hasOrigin(activeIndex)"
                        x-transition.opacity.duration.300ms
                        x-bind:class="{
                            'is-active': isOriginVisible(activeIndex),
                        }"
                        x-bind:aria-pressed="isOriginVisible(activeIndex)"
                        x-on:click.stop="toggleOrigin(activeIndex)"
                        x-on:mouseenter="$tippy(@js(arabic_text('مأثور')), 'right')"
                        x-on:mouseleave="$tippy.hide()"
                        x-on:focus="$tippy(@js(arabic_text('مأثور')), 'right')"
                        x-on:blur="$tippy.hide()"
                    >
                        <x-icon
                            class="athkar-origin-indicator__icon 3xl:size-9 4xl:size-10 size-6.5 2xl:size-8.5 md:size-7 lg:size-7 xl:size-8"
                            name="bootstrap.exclamation-diamond"
                        />
                    </button>
                </div>
            </div>

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
                        class="pointer-events-none relative flex h-full min-h-0 w-full shrink-0 flex-col px-3.5 pb-3 pt-4 transition-opacity duration-700 sm:px-4 sm:pb-2.5 sm:pt-5 md:px-4 md:py-3 lg:px-6 lg:pb-5 lg:pt-5 xl:px-7 xl:pb-6 xl:pt-6 2xl:px-10 2xl:pb-8 2xl:pt-7"
                        data-athkar-slide
                        x-bind:class="index === activeIndex ? 'opacity-100' : 'opacity-0'"
                        x-bind:data-index="String(index)"
                        x-bind:data-active="index === activeIndex ? 'true' : 'false'"
                    >
                        <template x-if="isSlideInRenderWindow(index)">
                            <div class="contents">
                                <!-- Content -->
                                <div
                                    class="pointer-events-auto flex min-h-0 flex-1 flex-col gap-3 sm:gap-1 sm:pt-0 md:gap-2 md:pt-2 lg:gap-4 lg:pt-4 xl:gap-4 xl:pt-3 2xl:gap-5 2xl:pt-4"
                                    x-bind:class="{ 'pointer-events-none!': isHintOpen(activeIndex) }"
                                >
                                    <!-- Althikr -->
                                    <button
                                        class="athkar-tap md:px-13 3xl:px-4 4xl:px-5 3xl:py-6 group relative flex min-h-0 w-full flex-1 touch-manipulation flex-col items-center justify-center gap-4 overflow-hidden rounded-sm border border-transparent px-0 py-1.5 text-center transition sm:px-10 sm:py-2.5 md:py-3 lg:px-12 xl:px-3 xl:py-5 2xl:px-3 2xl:py-5"
                                        data-athkar-tap
                                        type="button"
                                        x-on:click="handleTap()"
                                        x-bind:class="{
                                            'opacity-30!': isHintOpen(index),
                                            'athkar-tap--pulse': tapPulse.index === index && tapPulse.isActive,
                                            'is-origin-active': isOriginVisible(index),
                                        }"
                                    >
                                        <div
                                            class="{{ twMerge('relative Xmt-8 flex flex-row w-full min-h-0 flex-1 justify-center gap-3 overflow-hidden px-[0.3rem] Xsm:mt-0 sm:justify-center sm:gap-4 sm:px-0 md:px-0 lg:px-0 xl:px-6 2xl:px-6 3xl:px-14 4xl:px-14.5 transition-opacity') }}"
                                            data-athkar-text-box
                                            data-fitty-box
                                            dir="rtl"
                                            x-on:pointerdown.capture="beginHoldCopy($event, index)"
                                            x-on:pointermove.capture="moveHoldCopy($event)"
                                            x-on:pointerup.capture="endHoldCopy($event)"
                                            x-on:pointercancel.capture="cancelHoldCopy()"
                                            x-on:touchstart.capture="beginHoldCopy($event, index)"
                                            x-on:touchmove.capture="moveHoldCopy($event)"
                                            x-on:touchend.capture="endHoldCopy($event)"
                                            x-on:touchcancel.capture="cancelHoldCopy()"
                                            x-on:pointerdown="beginTextScroll($event);"
                                            x-on:pointermove="moveTextScroll($event);"
                                            x-on:pointerup="endTextScroll()"
                                            x-on:pointercancel="endTextScroll()"
                                            x-on:touchstart="beginTextScroll($event);"
                                            x-on:touchmove="moveTextScroll($event);"
                                            x-on:touchend="endTextScroll()"
                                            x-on:touchcancel="endTextScroll()"
                                            x-on:scroll.passive="syncTextBoxEdgeFadeFromEvent($event)"
                                        >
                                            <div
                                                class="athkar-main-text"
                                                x-bind:class="shouldHideMainTextLayer(index) && 'is-main-hidden'"
                                            >
                                                <p
                                                    class="athkar-text athkar-shimmer font-arabic-serif text-primary-950 dark:text-primary-50 whitespace-break-spaces!"
                                                    data-athkar-text
                                                    data-fitty-target
                                                    data-fitty-enabled="false"
                                                    data-fitty-overflow-active="false"
                                                    data-fitty-step="0.5"
                                                    data-fitty-safe-padding-x="2"
                                                    data-fitty-safe-padding-y="2"
                                                    data-fitty-manage-overflow="true"
                                                    data-fitty-enable-touch-scroll="true"
                                                    data-fitty-overflow-padding-class="py-1"
                                                    data-fitty-overflow-target="text"
                                                    data-athkar-shimmer
                                                    data-shimmer-duration="3000"
                                                    data-shimmer-delay="1000"
                                                    data-shimmer-pause="4000"
                                                    dir="rtl"
                                                    x-bind:data-fitty-enabled="(activeIndex === index).toString()"
                                                    x-bind:data-fitty-overflow-active="(activeIndex === index && !isOriginOverflowVisible(index)).toString
                                                        ()"
                                                    x-text="item.text"
                                                ></p>
                                            </div>
                                            <div
                                                class="athkar-origin-text"
                                                x-bind:class="shouldShowOriginTextLayer(index) && 'is-origin-visible'"
                                            >
                                                <p
                                                    class="athkar-text athkar-shimmer athkar-origin-text__content font-arabic-serif text-primary-950 dark:text-primary-50 whitespace-break-spaces!"
                                                    data-athkar-origin-text
                                                    data-athkar-shimmer
                                                    data-shimmer-duration="3000"
                                                    data-shimmer-delay="1000"
                                                    data-shimmer-pause="4000"
                                                    data-fitty-target
                                                    data-fitty-enabled="false"
                                                    data-fitty-overflow-active="false"
                                                    data-fitty-step="0.5"
                                                    data-fitty-safe-padding-x="2"
                                                    data-fitty-safe-padding-y="2"
                                                    data-fitty-manage-overflow="true"
                                                    data-fitty-enable-touch-scroll="true"
                                                    data-fitty-overflow-padding-class="py-1"
                                                    data-fitty-overflow-target="origin"
                                                    dir="rtl"
                                                    x-bind:data-fitty-enabled="(activeIndex === index).toString()"
                                                    x-bind:data-fitty-overflow-active="(activeIndex === index && isOriginOverflowVisible(index)).toString()"
                                                    x-text="item.origin"
                                                ></p>
                                            </div>
                                        </div>
                                    </button>

                                    <!-- Completion Indicator -->
                                    <div
                                        class="3xl:text-sm relative flex items-center justify-between gap-3 text-[0.675rem] text-gray-600 sm:text-[0.55rem] md:text-[0.6rem] lg:text-[0.685rem] xl:text-[0.7rem] 2xl:text-[0.8rem] dark:text-gray-300">
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
                                            <span class="athkar-count">
                                                <span
                                                    class="athkar-count__current"
                                                    x-show="!(pagePulse.isActive && pagePulse.hasChanges)"
                                                    x-text="activeIndex + 1"
                                                ></span>
                                                <span
                                                    class="athkar-count__current inline-flex items-center gap-0"
                                                    x-cloak
                                                    x-show="pagePulse.isActive && pagePulse.hasChanges"
                                                >
                                                    <template
                                                        x-for="segment in pagePulse.segments"
                                                        x-bind:key="segment.key"
                                                    >
                                                        <span class="inline-flex items-center">
                                                            <span
                                                                x-show="!segment.changed"
                                                                x-text="segment.next"
                                                            ></span>
                                                            <span
                                                                class="athkar-count athkar-count--rolling"
                                                                x-show="segment.changed"
                                                            >
                                                                <span
                                                                    class="athkar-count__prev"
                                                                    x-text="segment.prev"
                                                                ></span>
                                                                <span
                                                                    class="athkar-count__next"
                                                                    x-text="segment.next"
                                                                ></span>
                                                            </span>
                                                        </span>
                                                    </template>
                                                </span>
                                            </span>
                                            <span x-text="`/ ${activeList.length}`"></span>
                                        </span>

                                        <div
                                            class="flex items-center gap-1.5"
                                            x-data="{
                                                isVisible: false,
                                                timer: null,
                                            }"
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
                                                class="athkar-complete-badge px-2.5 py-1 font-semibold opacity-0 transition-opacity duration-300 sm:px-3"
                                                x-bind:class="isVisible && isItemComplete(index) && 'opacity-100!'"
                                            >{{ arabic_text('تم بحمد الله') }}</span>
                                            <span
                                                class="rounded-bl-lg! rounded-sm border border-gray-300 bg-gray-100 px-2 py-0.5 font-semibold text-gray-700 opacity-0 shadow-sm transition-opacity duration-300 sm:px-1.5 sm:py-0.5 md:px-3 md:py-1 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-100"
                                                x-bind:class="isVisible && 'opacity-100!'"
                                                x-text="activeTypeLabel(index)"
                                            ></span>
                                        </div>
                                    </div>
                                </div>
                        </template>
                    </article>
                </template>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button
                class="athkar-nav__arrow sm:h-4.5 sm:w-4.5 lg:h-5.5 lg:w-5.5 inline-flex h-6 w-6 items-center justify-center rounded-sm transition disabled:cursor-not-allowed disabled:opacity-60 md:h-5 md:w-5 xl:h-6 xl:w-6 2xl:h-7 2xl:w-7"
                type="button"
                aria-label="{{ arabic_text('السابق') }}"
                x-bind:disabled="activeIndex === 0"
                x-on:click="prev()"
            >
                <svg
                    class="h-3 w-3 sm:h-2.5 sm:w-2.5 md:h-3 md:w-3 lg:h-3.5 lg:w-3.5 xl:h-4 xl:w-4"
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
                    class="athkar-nav relative h-5 w-full touch-pan-y select-none rounded-sm sm:h-3 md:h-4 lg:h-5 xl:h-5 2xl:h-6"
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
                        x-bind:style="shouldEnableVisualEnhancements() ? null : 'animation: none;'"
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
                class="athkar-nav__arrow sm:h-4.5 sm:w-4.5 lg:h-5.5 lg:w-5.5 inline-flex h-6 w-6 items-center justify-center rounded-sm transition disabled:cursor-not-allowed disabled:opacity-60 md:h-5 md:w-5 xl:h-6 xl:w-6 2xl:h-7 2xl:w-7"
                type="button"
                aria-label="{{ arabic_text('التالي') }}"
                x-bind:disabled="!canAdvance()"
                x-on:click="next()"
            >
                <svg
                    class="h-3 w-3 sm:h-2.5 sm:w-2.5 md:h-3 md:w-3 lg:h-3.5 lg:w-3.5 xl:h-4 xl:w-4"
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

        <template x-teleport="body">
            <div
                class="quran-copy-popover"
                data-athkar-copy-popover
                x-cloak
                x-show="copyFeedback.visible"
                x-bind:style="copyFeedbackStyle()"
                x-transition:enter="quran-copy-popover-enter"
                x-transition:enter-start="quran-copy-popover-enter-start"
                x-transition:enter-end="quran-copy-popover-enter-end"
                x-transition:leave="quran-copy-popover-leave"
                x-transition:leave-start="quran-copy-popover-leave-start"
                x-transition:leave-end="quran-copy-popover-leave-end"
            >
                <x-icon
                    class="h-3.5 w-3.5"
                    :name="'heroicon-o-clipboard'"
                />
                <span>{{ arabic_text('تم النسخ') }}</span>
            </div>
        </template>
    </section>
</div>
