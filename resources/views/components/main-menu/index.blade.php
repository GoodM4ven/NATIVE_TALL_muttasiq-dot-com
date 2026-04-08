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
                opacity 196ms cubic-bezier(0.22, 1, 0.36, 1),
                transform 238ms cubic-bezier(0.22, 1, 0.36, 1),
                filter 210ms ease;
            transition-delay: calc(var(--main-menu-item-index) * 24ms);
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
            transition-delay: calc((8 - var(--main-menu-item-index)) * 14ms + 84ms);
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
        }

        .main-menu-grid-shell {
            position: relative;
            isolation: isolate;
            width: min(100%, 20rem);
        }

        .main-menu-grid-pattern-wrap {
            position: absolute;
            inset: -5.2rem -6.4rem -5rem;
            z-index: -1;
            opacity: 0.2;
            pointer-events: none;
            -webkit-mask-image: radial-gradient(circle at center,
                    rgba(0, 0, 0, 1) 15%,
                    rgba(0, 0, 0, 0.72) 35%,
                    rgba(0, 0, 0, 0.40) 55%,
                    rgba(0, 0, 0, 0.25) 77.5%,
                    rgba(0, 0, 0, 0) 100%);
            mask-image: radial-gradient(circle at center,
                    rgba(0, 0, 0, 1) 15%,
                    rgba(0, 0, 0, 0.72) 35%,
                    rgba(0, 0, 0, 0.40) 55%,
                    rgba(0, 0, 0, 0.25) 77.5%,
                    rgba(0, 0, 0, 0) 100%);
        }

        .main-menu-grid-pattern {
            position: absolute;
            inset: 0;
            border-radius: 9999px;
        }

        .main-menu-insights-zone {
            --main-menu-grid-reference-width: 20rem;
            --main-menu-insights-panel-gap: 0.68rem;
            width: min(100%, calc(var(--main-menu-grid-reference-width) * 1.1));
            padding: 0.4rem 0.15rem 0.15rem;
            user-select: none;
            -webkit-user-select: none;
        }

        .main-menu-insights-zone,
        .main-menu-insights-zone * {
            cursor: default !important;
            user-select: none;
            -webkit-user-select: none;
        }

        .main-menu-insights-trigger-wrap {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            min-height: 1.85rem;
            position: relative;
            z-index: 10;
        }

        .main-menu-insights-trigger {
            position: relative;
            display: inline-flex;
            overflow: hidden;
            width: 100%;
            height: 0.64rem;
            border-radius: 9999px;
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--primary-300) 78%, white 5%) 0%,
                    color-mix(in srgb, var(--primary-400) 86%, white 5%) 52%,
                    color-mix(in srgb, var(--primary-300) 78%, white 5%) 100%);
            border: 1px solid color-mix(in srgb, var(--primary-500) 30%, white 38%);
            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--primary-100) 50%, transparent),
                0 0 16px color-mix(in srgb, var(--primary-300) 20%, transparent),
                0 0 32px color-mix(in srgb, var(--primary-400) 0%, transparent);
            transition:
                width 525ms cubic-bezier(0.22, 1, 0.36, 1),
                height 420ms cubic-bezier(0.22, 1, 0.36, 1),
                transform 525ms cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 315ms ease,
                opacity 238ms ease;
            opacity: 1;
            outline: none;
            transform: translateY(0);
        }

        .main-menu-insights-trigger::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: radial-gradient(ellipse at center,
                    color-mix(in srgb, white 60%, transparent) 10%,
                    color-mix(in srgb, var(--primary-200) 10%, transparent) 70%,
                    transparent 50%);
            opacity: 0;
            transition: opacity 280ms ease;
            filter: blur(2px);
        }

        .main-menu-insights-trigger::after {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(105deg,
                    transparent 0%,
                    color-mix(in srgb, white 90%, transparent) 50%,
                    transparent 70%);
            opacity: 0.42;
            transform: translateX(-120%) skewX(-18deg);
            transition:
                opacity 280ms ease,
                transform 280ms ease;
            animation: main-menu-insights-line-shimmer 1.75s linear infinite;
            pointer-events: none;
        }

        .main-menu-insights-trigger:hover,
        .main-menu-insights-trigger:focus-visible {
            width: 110%;
            height: 0.42rem;
            opacity: 1;
            transform: translateY(-1px);
            box-shadow:
                0 0 0 1px color-mix(in srgb, white 70%, var(--primary-200) 40%),
                0 0 22px color-mix(in srgb, white 40%, transparent),
                0 0 44px color-mix(in srgb, var(--primary-300) 50%, transparent),
                0 0 66px color-mix(in srgb, var(--primary-400) 70%, transparent);
        }

        .main-menu-insights-trigger[data-expanded='true'] {
            width: 92%;
            height: 0.42rem;
            opacity: 1;
            transform: translateY(-1px);
            box-shadow:
                0 0 0 1px color-mix(in srgb, white 96%, transparent),
                0 0 34px color-mix(in srgb, white 92%, transparent),
                0 0 62px color-mix(in srgb, white 72%, transparent),
                0 0 96px color-mix(in srgb, var(--primary-400) 52%, transparent),
                0 0 138px color-mix(in srgb, var(--primary-500) 34%, transparent);
            animation: main-menu-insights-trigger-power 0.98s ease-in-out infinite alternate;
        }

        .main-menu-insights-trigger:hover::before,
        .main-menu-insights-trigger:focus-visible::before {
            opacity: 1;
        }

        .main-menu-insights-trigger:hover::after,
        .main-menu-insights-trigger:focus-visible::after {
            opacity: 0.7;
        }

        .main-menu-insights-trigger[data-expanded='true']::before {
            opacity: 0.96;
        }

        .main-menu-insights-trigger[data-expanded='true']::after {
            opacity: 0.92;
        }

        .main-menu-insights-reveal {
            position: relative;
            z-index: 2;
            width: 100%;
            max-height: 0;
            opacity: 0;
            transform: translateY(-0.32rem);
            pointer-events: none;
            transition:
                max-height 630ms cubic-bezier(0.22, 1, 0.36, 1),
                transform 315ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 280ms ease;
            will-change: max-height, opacity, transform;
        }

        .main-menu-insights-reveal[data-fast-close='true'] {
            transition:
                max-height var(--main-menu-insights-fast-close-duration, 450ms) cubic-bezier(0.4, 0, 1, 1),
                transform var(--main-menu-insights-fast-close-duration, 450ms) cubic-bezier(0.4, 0, 1, 1),
                opacity var(--main-menu-insights-fast-close-opacity-duration, 360ms) ease;
        }

        .main-menu-insights-reveal[data-expanded='true'] {
            opacity: 1;
            transform: translateY(0);
            pointer-events: auto;
        }

        .main-menu-insights-panel {
            position: relative;
            top: calc(var(--main-menu-insights-panel-gap) - 2.9rem);
            isolation: isolate;
            width: 100%;
            border-radius: 1.25rem;
            border: 1px solid color-mix(in srgb, white 56%, var(--primary-300) 14%);
            background: linear-gradient(160deg,
                    color-mix(in srgb, white 12%, transparent) 0%,
                    color-mix(in srgb, var(--primary-100) 7%, transparent) 52%,
                    color-mix(in srgb, var(--primary-200) 5%, transparent) 100%);
            backdrop-filter: blur(28px) saturate(180%);
            box-shadow:
                0 20px 52px color-mix(in srgb, var(--primary-900) 16%, transparent),
                0 8px 20px color-mix(in srgb, var(--primary-400) 14%, transparent),
                0 0 46px color-mix(in srgb, white 32%, transparent);
            padding: 1.05rem 0.92rem 0.95rem;
            transform: scale(0.972);
            transform-origin: top center;
            opacity: 0;
            transition:
                top 315ms cubic-bezier(0.22, 1, 0.36, 1),
                transform 315ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 315ms ease;
        }

        .main-menu-insights-panel[data-fast-close='true'] {
            transition:
                top var(--main-menu-insights-fast-close-duration, 450ms) cubic-bezier(0.4, 0, 1, 1),
                transform var(--main-menu-insights-fast-close-duration, 450ms) cubic-bezier(0.4, 0, 1, 1),
                opacity var(--main-menu-insights-fast-close-opacity-duration, 360ms) ease;
        }

        .main-menu-insights-panel::before {
            content: '';
            position: absolute;
            inset: -24% -18%;
            z-index: -1;
            background: radial-gradient(84% 58% at 50% 8%,
                    color-mix(in srgb, white 26%, transparent) 0%,
                    color-mix(in srgb, var(--primary-200) 8%, transparent) 58%,
                    transparent 100%);
            filter: blur(22px);
            opacity: 0.78;
            pointer-events: none;
        }

        .main-menu-insights-panel::after {
            content: '';
            position: absolute;
            inset: 0;
            pointer-events: none;
            border-radius: inherit;
            background:
                radial-gradient(120% 100% at 14% 0%,
                    color-mix(in srgb, white 22%, transparent) 0%,
                    transparent 56%),
                linear-gradient(112deg,
                    transparent 0%,
                    color-mix(in srgb, white 18%, transparent) 48%,
                    transparent 74%);
            opacity: 0.72;
        }

        .main-menu-insights-reveal[data-expanded='true'] .main-menu-insights-panel {
            top: var(--main-menu-insights-panel-gap);
            transform: scale(1);
            opacity: 1;
        }

        .dark .main-menu-insights-panel {
            border-color: color-mix(in srgb, var(--primary-100) 32%, transparent);
            background: linear-gradient(160deg,
                    color-mix(in srgb, var(--background-dark) 22%, transparent) 0%,
                    color-mix(in srgb, var(--primary-900) 24%, transparent) 100%);
            box-shadow:
                0 24px 58px color-mix(in srgb, black 38%, transparent),
                0 10px 24px color-mix(in srgb, var(--primary-500) 22%, transparent),
                0 0 44px color-mix(in srgb, var(--primary-200) 18%, transparent);
        }

        .main-menu-insights-powered {
            position: absolute;
            z-index: 3;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.5rem;
            font-weight: 800;
            letter-spacing: 0.2em;
            line-height: 1;
            text-transform: uppercase;
            color: color-mix(in srgb, white 90%, var(--primary-300) 10%);
            text-shadow:
                0 0 10px color-mix(in srgb, white 72%, transparent),
                0 0 24px color-mix(in srgb, var(--primary-300) 46%, transparent);
            opacity: 0.52;
            pointer-events: none;
        }

        .main-menu-insights-powered--tl {
            top: 0.36rem;
            left: 0.64rem;
            transform: rotate(-5deg);
        }

        .main-menu-insights-powered--tr {
            top: 0.36rem;
            right: 0.64rem;
            transform: rotate(5deg);
        }

        .main-menu-insights-powered--bl {
            bottom: 0.42rem;
            left: 0.64rem;
            transform: rotate(5deg);
        }

        .main-menu-insights-powered--br {
            bottom: 0.42rem;
            right: 0.64rem;
            transform: rotate(-5deg);
        }

        .main-menu-insights-row {
            display: grid;
            grid-template-columns: minmax(0, 1fr) auto;
            grid-template-rows: 1.25rem 0.56rem;
            align-items: center;
            gap: 0.55rem 0.7rem;
            position: relative;
            height: 4.3rem;
            min-height: 4.3rem;
            max-height: 4.3rem;
            padding: 0.62rem 0.68rem 0.64rem;
            border-radius: 0.95rem;
            border: 1px solid color-mix(in srgb, white 52%, var(--primary-200) 16%);
            background: linear-gradient(160deg,
                    color-mix(in srgb, white 14%, transparent) 0%,
                    color-mix(in srgb, var(--primary-100) 8%, transparent) 100%);
            box-shadow:
                0 10px 22px color-mix(in srgb, var(--primary-900) 10%, transparent),
                0 0 26px color-mix(in srgb, white 22%, transparent);
            backdrop-filter: blur(20px) saturate(165%);
            overflow: clip;
        }

        .main-menu-insights-row--button {
            width: 100%;
            text-align: start;
            appearance: none;
            outline: none;
            border-radius: inherit;
            cursor: pointer !important;
            touch-action: manipulation;
            -webkit-tap-highlight-color: transparent;
            transition:
                background 180ms ease,
                border-color 180ms ease,
                box-shadow 180ms ease,
                transform 154ms ease;
        }

        .main-menu-insights-row--button * {
            cursor: pointer !important;
        }

        .main-menu-insights-row--button:hover,
        .main-menu-insights-row--button:focus-visible {
            transform: translateY(0);
            border-color: color-mix(in srgb, var(--primary-500) 44%, white 56%);
            background: linear-gradient(160deg,
                    color-mix(in srgb, var(--primary-500) 22%, transparent) 0%,
                    color-mix(in srgb, var(--primary-600) 28%, transparent) 100%);
            box-shadow:
                0 12px 28px color-mix(in srgb, var(--primary-800) 20%, transparent),
                0 0 28px color-mix(in srgb, var(--primary-400) 22%, transparent);
        }

        .main-menu-insights-row--button:hover .main-menu-insights-title,
        .main-menu-insights-row--button:focus-visible .main-menu-insights-title,
        .main-menu-insights-row--button:hover .main-menu-insights-meta,
        .main-menu-insights-row--button:focus-visible .main-menu-insights-meta {
            color: color-mix(in srgb, white 88%, var(--primary-100) 12%);
            text-shadow: 0 0 14px color-mix(in srgb, var(--primary-200) 42%, transparent);
        }

        .main-menu-insights-row--button:hover .main-menu-insights-track,
        .main-menu-insights-row--button:focus-visible .main-menu-insights-track {
            border-color: color-mix(in srgb, white 62%, var(--primary-300) 38%);
            background: color-mix(in srgb, var(--primary-700) 14%, transparent);
        }

        .main-menu-insights-row--button:hover .main-menu-insights-fill,
        .main-menu-insights-row--button:focus-visible .main-menu-insights-fill {
            background: linear-gradient(270deg,
                    color-mix(in srgb, var(--primary-200) 74%, white 26%) 0%,
                    color-mix(in srgb, var(--primary-300) 84%, white 16%) 48%,
                    color-mix(in srgb, var(--primary-500) 92%, white 8%) 100%);
            filter: brightness(1.08) saturate(1.08);
        }

        .dark .main-menu-insights-row {
            border-color: color-mix(in srgb, var(--primary-200) 30%, transparent);
            background: linear-gradient(160deg,
                    color-mix(in srgb, var(--primary-900) 14%, transparent) 0%,
                    color-mix(in srgb, var(--gray-800) 24%, transparent) 100%);
            box-shadow:
                0 12px 28px color-mix(in srgb, black 26%, transparent),
                0 0 26px color-mix(in srgb, var(--primary-200) 14%, transparent);
        }

        .main-menu-insights-row+.main-menu-insights-row {
            margin-top: 0.62rem;
        }

        .main-menu-insights-title {
            display: inline-flex;
            align-items: center;
            min-height: 1.25rem;
            height: 1.25rem;
            max-height: 1.25rem;
            line-height: 1;
            color: color-mix(in srgb, var(--primary-900) 84%, transparent);
            transition: color 180ms ease, text-shadow 180ms ease, opacity 180ms ease;
        }

        .dark .main-menu-insights-title {
            color: color-mix(in srgb, var(--primary-100) 84%, transparent);
        }

        .main-menu-insights-meta {
            display: inline-flex;
            align-items: center;
            justify-self: start;
            gap: 0.36rem;
            min-height: 1.25rem;
            height: 1.25rem;
            max-height: 1.25rem;
            font-size: 0.72rem;
            font-weight: 800;
            line-height: 1.25rem;
            letter-spacing: 0.01em;
            color: color-mix(in srgb, var(--primary-800) 82%, transparent);
            text-shadow: 0 0 12px color-mix(in srgb, white 32%, transparent);
            font-variant-numeric: tabular-nums;
            transition: color 180ms ease, text-shadow 180ms ease, opacity 180ms ease;
        }

        .dark .main-menu-insights-meta {
            color: color-mix(in srgb, white 90%, var(--primary-100) 10%);
        }

        .main-menu-insights-track {
            grid-column: 1 / -1;
            position: relative;
            direction: ltr;
            unicode-bidi: isolate;
            height: 0.56rem;
            border-radius: 9999px;
            background: color-mix(in srgb, white 8%, transparent);
            border: 1px solid color-mix(in srgb, white 40%, var(--primary-200) 22%);
            overflow: hidden;
            box-shadow: none;
            backdrop-filter: blur(6px);
            transition:
                background 180ms ease,
                border-color 180ms ease,
                box-shadow 180ms ease,
                opacity 180ms ease;
        }

        .dark .main-menu-insights-track {
            background: color-mix(in srgb, black 20%, transparent);
            border-color: color-mix(in srgb, var(--primary-200) 34%, transparent);
        }

        .main-menu-insights-fill {
            position: absolute;
            display: block;
            right: 0;
            top: 0;
            bottom: 0;
            width: 0%;
            border-radius: inherit;
            overflow: hidden;
            background: linear-gradient(to left,
                    color-mix(in srgb, var(--primary-500) 88%, white 12%) 0%,
                    color-mix(in srgb, var(--primary-400) 90%, white 10%) 48%,
                    color-mix(in srgb, var(--primary-300) 94%, white 6%) 100%);
            box-shadow:
                0 0 8px color-mix(in srgb, var(--primary-400) 44%, transparent),
                0 0 14px color-mix(in srgb, var(--primary-500) 26%, transparent);
            transition:
                width 364ms cubic-bezier(0.22, 1, 0.36, 1),
                background 180ms ease,
                filter 180ms ease,
                box-shadow 180ms ease;
        }

        .main-menu-insights-fill::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(180deg,
                    color-mix(in srgb, white 16%, transparent) 0%,
                    transparent 72%);
            opacity: 0.32;
            pointer-events: none;
        }

        .main-menu-insights-fill::after {
            content: '';
            position: absolute;
            top: 0;
            bottom: 0;
            width: 55%;
            right: -60%;
            border-radius: inherit;
            background-image: linear-gradient(to left,
                    transparent 0%,
                    color-mix(in srgb, white 10%, transparent) 15%,
                    color-mix(in srgb, white 55%, transparent) 40%,
                    color-mix(in srgb, white 80%, transparent) 55%,
                    color-mix(in srgb, white 50%, transparent) 70%,
                    color-mix(in srgb, white 10%, transparent) 88%,
                    transparent 100%);
            filter: blur(3px);
            mix-blend-mode: overlay;
            opacity: 0.75;
            animation: main-menu-insights-flow-rtl 2.35s ease-in-out infinite;
            pointer-events: none;
        }

        .main-menu-insights-fill--complete {
            background: linear-gradient(to left,
                    color-mix(in srgb, var(--success-300) 82%, white 18%) 0%,
                    color-mix(in srgb, var(--success-500) 88%, white 12%) 52%,
                    color-mix(in srgb, var(--success-600) 92%, white 8%) 100%);
            box-shadow:
                0 0 18px color-mix(in srgb, var(--success-400) 74%, transparent),
                0 0 34px color-mix(in srgb, var(--success-500) 52%, transparent);
        }

        .main-menu-insights-fill--complete::after {
            background-image: linear-gradient(to left,
                    transparent 0%,
                    color-mix(in srgb, var(--success-200) 44%, transparent) 40%,
                    color-mix(in srgb, var(--success-100) 68%, transparent) 54%,
                    color-mix(in srgb, var(--success-300) 52%, transparent) 68%,
                    transparent 100%);
            opacity: 0.46;
        }

        @media (hover: none),
        (pointer: coarse) {

            .main-menu-insights-row--button:hover,
            .main-menu-insights-row--button:focus-visible {
                border-color: color-mix(in srgb, white 52%, var(--primary-200) 16%);
                background: linear-gradient(160deg,
                        color-mix(in srgb, white 14%, transparent) 0%,
                        color-mix(in srgb, var(--primary-100) 8%, transparent) 100%);
                box-shadow:
                    0 10px 22px color-mix(in srgb, var(--primary-900) 10%, transparent),
                    0 0 26px color-mix(in srgb, white 22%, transparent);
            }

            .main-menu-insights-row--button:hover .main-menu-insights-title,
            .main-menu-insights-row--button:focus-visible .main-menu-insights-title,
            .main-menu-insights-row--button:hover .main-menu-insights-meta,
            .main-menu-insights-row--button:focus-visible .main-menu-insights-meta {
                color: color-mix(in srgb, var(--primary-900) 84%, transparent);
                text-shadow: 0 0 12px color-mix(in srgb, white 32%, transparent);
            }

            .main-menu-insights-row--button:hover .main-menu-insights-track,
            .main-menu-insights-row--button:focus-visible .main-menu-insights-track {
                border-color: color-mix(in srgb, white 40%, var(--primary-200) 22%);
                background: color-mix(in srgb, white 8%, transparent);
            }

            .main-menu-insights-row--button:hover .main-menu-insights-fill,
            .main-menu-insights-row--button:focus-visible .main-menu-insights-fill {
                filter: none;
            }

            .main-menu-insights-row--button:active {
                border-color: color-mix(in srgb, var(--primary-500) 44%, white 56%);
                background: linear-gradient(160deg,
                        color-mix(in srgb, var(--primary-500) 22%, transparent) 0%,
                        color-mix(in srgb, var(--primary-600) 28%, transparent) 100%);
                box-shadow:
                    0 12px 28px color-mix(in srgb, var(--primary-800) 20%, transparent),
                    0 0 28px color-mix(in srgb, var(--primary-400) 22%, transparent);
            }

            .main-menu-insights-row--button:active .main-menu-insights-title,
            .main-menu-insights-row--button:active .main-menu-insights-meta {
                color: color-mix(in srgb, white 88%, var(--primary-100) 12%);
                text-shadow: 0 0 14px color-mix(in srgb, var(--primary-200) 42%, transparent);
            }

            .main-menu-insights-row--button:active .main-menu-insights-track {
                border-color: color-mix(in srgb, white 62%, var(--primary-300) 38%);
                background: color-mix(in srgb, var(--primary-700) 14%, transparent);
            }

            .main-menu-insights-row--button:active .main-menu-insights-fill {
                background: linear-gradient(to left,
                        color-mix(in srgb, var(--primary-500) 88%, white 12%) 0%,
                        color-mix(in srgb, var(--primary-400) 90%, white 10%) 48%,
                        color-mix(in srgb, var(--primary-300) 94%, white 6%) 100%);
                filter: brightness(1.08) saturate(1.08);
            }
        }

        @keyframes main-menu-insights-trigger-power {
            0% {
                box-shadow:
                    0 0 0 1px color-mix(in srgb, white 94%, transparent),
                    0 0 28px color-mix(in srgb, white 86%, transparent),
                    0 0 54px color-mix(in srgb, var(--primary-300) 48%, transparent),
                    0 0 96px color-mix(in srgb, var(--primary-500) 28%, transparent);
            }

            100% {
                box-shadow:
                    0 0 0 1px color-mix(in srgb, white 98%, transparent),
                    0 0 40px color-mix(in srgb, white 94%, transparent),
                    0 0 72px color-mix(in srgb, var(--primary-400) 58%, transparent),
                    0 0 126px color-mix(in srgb, var(--primary-500) 42%, transparent);
            }
        }

        @keyframes main-menu-insights-line-shimmer {
            0% {
                transform: translateX(120%) skewX(-18deg);
            }

            100% {
                transform: translateX(-120%) skewX(-18deg);
            }
        }

        @keyframes main-menu-insights-flow-rtl {
            0% {
                transform: translateX(0);
            }

            100% {
                transform: translateX(-340%);
            }
        }
    </style>
@endassets

<div
    {{ $attributes->merge(['class' => 'relative flex flex-col items-center will-change-[opacity]']) }}
    x-data="mainMenu($el, {
        progressLabels: {
            sabah: @js(arabic_text('أذكار الصَّباح')),
            wird: @js(arabic_text('وِرد القرآن')),
            masaa: @js(arabic_text('أذكار المَساء')),
        },
    })"
    x-on:main-menu-item-enter="handleItemEnter($event.detail)"
    x-on:main-menu-item-leave="handleItemLeave()"
    x-on:main-menu-item-click="handleItemClick($event.detail)"
    x-on:click.outside="handleRootOutsideClick()"
>
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

    <div class="main-menu-layout-shell">
        <!-- Items -->
        <div class="main-menu-grid-shell">
            <!-- Pattern (items-only; follows grid and does not belong to insights line/panel) -->
            <span
                class="main-menu-grid-pattern-wrap"
                aria-hidden="true"
            >
                <span
                    class="main-menu-grid-pattern"
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

        <div
            class="main-menu-insights-zone"
            x-ref="insightsZone"
            x-on:pointerenter="if ($event.pointerType !== 'touch') { handleInsightsHoverEnter() }"
            x-on:pointerleave="if ($event.pointerType !== 'touch') { handleInsightsHoverLeave() }"
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

            <div
                class="main-menu-insights-reveal"
                x-ref="insightsReveal"
                x-bind:data-expanded="isInsightsExpanded ? 'true' : 'false'"
                x-bind:data-fast-close="isInsightsFastClosing ? 'true' : 'false'"
                x-bind:style="`--main-menu-insights-fast-close-duration: ${insightsFastCloseDurationMs}ms; --main-menu-insights-fast-close-opacity-duration: ${Math.max(220, Math.round(insightsFastCloseDurationMs * 0.8))}ms; max-height: ${isInsightsExpanded ? insightsPanelHeight : 0}px;`"
            >
                <section
                    class="main-menu-insights-panel"
                    data-testid="main-menu-insights-panel"
                    x-ref="insightsPanelBody"
                    x-bind:data-fast-close="isInsightsFastClosing ? 'true' : 'false'"
                >
                    <template
                        x-for="row in sortedInsightsRows()"
                        :key="`main-menu-insights-row-${row.key}`"
                    >
                        <button
                            class="main-menu-insights-row main-menu-insights-row--button"
                            type="button"
                            x-on:click.stop.prevent="handleInsightsRowClick(row.key)"
                            x-bind:aria-label="`${row.label} ${row.percent}%`"
                        >
                            <p
                                class="main-menu-insights-title text-[0.72rem] font-semibold leading-none sm:text-[0.76rem]"
                                x-text="row.label"
                            ></p>
                            <span class="main-menu-insights-meta">
                                <span x-text="`${row.percent}%`"></span>
                            </span>
                            <div class="main-menu-insights-track">
                                <div
                                    class="main-menu-insights-fill"
                                    x-bind:class="{ 'main-menu-insights-fill--complete': row.isComplete }"
                                    x-bind:style="`width: ${row.percent}%;`"
                                ></div>
                            </div>
                        </button>
                    </template>
                </section>
            </div>
        </div>
    </div>
</div>
