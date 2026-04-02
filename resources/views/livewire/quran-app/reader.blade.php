@assets
    <style>
        @font-face {
            font-family: 'MadinaQuran';
            src: url('/vendor/arabicable/madina.woff2') format('woff2');
            font-display: swap;
        }

        .quran-reader {
            --quran-panel-bg: color-mix(in srgb, hsl(38.18 64.71% 98.87%) 80%, transparent);
            --quran-panel-border: color-mix(in srgb, var(--warning-50) 38%, transparent);
            --quran-panel-shadow: 0 22px 40px color-mix(in srgb, var(--gray-900) 18%, transparent);
            --quran-panel-text: var(--primary-950);
            --quran-ink: color-mix(in srgb, var(--primary-950) 94%, var(--gray-900));
            --quran-subtle: color-mix(in srgb, var(--primary-700) 68%, var(--gray-500));
            --quran-chip-bg: color-mix(in srgb, var(--background-dark) 66%, transparent);
            --quran-chip-border: color-mix(in srgb, var(--primary-500) 35%, transparent);
            --quran-chip-hover: color-mix(in srgb, var(--primary-500) 16%, transparent);
            --quran-active-bg: color-mix(in srgb, var(--success-300) 30%, transparent);
            --quran-active-text: color-mix(in srgb, var(--success-500) 78%, var(--primary-900));
            --quran-page-surface: color-mix(in srgb, var(--background) 86%, transparent);
            --quran-page-border: color-mix(in srgb, var(--warning-300) 58%, transparent);
            --quran-page-scale: 1;
            --quran-min-page-scale: 0.1;
            --quran-max-page-scale: 1.85;
            --quran-type-scale: 1;
            --quran-leading-scale: 1;
            --quran-gap-scale: 1;
            --quran-page-type-scale: 1;
            --quran-page-leading-multiplier: 1;
            --quran-page-gap-multiplier: 1;
            --quran-page-surah-header-scale: 1;
            --quran-fit-height-ratio: 0.95;
            --quran-line-gap: 2.3rem;
            --quran-basmallah-bottom-gap-scale: -0.18;
            --quran-font-size-rect: 2.08rem;
            --quran-font-size-center: 2.02rem;
            --quran-font-size-meta: 1.88rem;
            --quran-line-height-rect: 1.58;
            --quran-line-height-center: 1.7;
            --quran-line-height-meta: 1.66;
        }

        @media (max-width: 639px) {
            .quran-reader {
                --quran-type-scale: 0.94;
                --quran-leading-scale: 1.02;
                --quran-gap-scale: 0.92;
                --quran-fit-height-ratio: 0.92;
            }
        }

        @media (min-width: 640px) and (max-width: 767px) {
            .quran-reader {
                --quran-type-scale: 0.965;
                --quran-leading-scale: 1.01;
                --quran-gap-scale: 0.96;
                --quran-fit-height-ratio: 0.93;
            }
        }

        @media (min-width: 768px) and (max-width: 1023px) {
            .quran-reader {
                --quran-type-scale: 0.985;
                --quran-leading-scale: 1;
                --quran-gap-scale: 0.99;
                --quran-fit-height-ratio: 0.94;
            }
        }

        @media (min-width: 1024px) and (max-width: 1279px) {
            .quran-reader {
                --quran-type-scale: 1;
                --quran-leading-scale: 1;
                --quran-gap-scale: 1;
                --quran-fit-height-ratio: 0.95;
            }
        }

        @media (min-width: 1280px) and (max-width: 1535px) {
            .quran-reader {
                --quran-type-scale: 1.03;
                --quran-leading-scale: 1.02;
                --quran-gap-scale: 1.08;
                --quran-fit-height-ratio: 0.96;
            }
        }

        @media (min-width: 1536px) {
            .quran-reader {
                --quran-type-scale: 2.2;
                --quran-leading-scale: 1;
                --quran-gap-scale: 1.35;
                --quran-fit-height-ratio: 0.97;
            }
        }

        .dark .quran-reader {
            --quran-panel-bg: color-mix(in srgb, var(--primary-200) 24%, transparent);
            --quran-panel-border: color-mix(in srgb, var(--primary-300) 42%, transparent);
            --quran-panel-shadow: 0 24px 44px color-mix(in srgb, var(--gray-950) 62%, transparent);
            --quran-panel-text: var(--primary-50);
            --quran-ink: color-mix(in srgb, var(--primary-50) 94%, white);
            --quran-subtle: color-mix(in srgb, var(--primary-100) 72%, var(--gray-300));
            --quran-chip-bg: color-mix(in srgb, var(--gray-950) 44%, transparent);
            --quran-chip-border: color-mix(in srgb, var(--primary-200) 42%, transparent);
            --quran-chip-hover: color-mix(in srgb, var(--primary-300) 25%, transparent);
            --quran-active-bg: color-mix(in srgb, var(--success-300) 24%, transparent);
            --quran-active-text: color-mix(in srgb, var(--success-100) 90%, white);
            --quran-page-surface: color-mix(in srgb, var(--gray-950) 44%, transparent);
            --quran-page-border: color-mix(in srgb, var(--gray-700) 65%, transparent);
        }

        .quran-reader-panel {
            color: var(--quran-panel-text);
            background: var(--quran-panel-bg);
            border-color: var(--quran-panel-border);
            box-shadow: var(--quran-panel-shadow);
        }

        .quran-page-surface {
            /* background: var(--quran-page-surface); */
            border-color: var(--quran-page-border);
        }

        .font-quran {
            font-family: 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;
        }

        .quran-ayah-line-run {
            display: inline-flex;
            direction: rtl;
            align-items: baseline;
            gap: var(--quran-word-gap-extra, 0em);
            white-space: nowrap;
            max-width: none;
        }

        .quran-segment-cluster {
            display: inline-flex;
            align-items: baseline;
            gap: var(--quran-word-gap-extra, 0em);
            border-radius: 0.56em;
            padding-inline: 0.14em;
            margin-inline: -0.02em;
            transition:
                background-color 460ms cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 460ms cubic-bezier(0.22, 1, 0.36, 1),
                color 360ms cubic-bezier(0.22, 1, 0.36, 1);
            will-change: background-color, box-shadow;
        }

        .quran-segment-cluster.quran-segment-cluster-hovered {
            background-color: color-mix(in srgb, var(--gray-300) 18%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-400) 10%, transparent),
                0 2px 8px color-mix(in srgb, var(--gray-700) 8%, transparent);
        }

        .quran-segment-cluster.quran-segment-cluster-active {
            background: var(--quran-active-bg);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--success-300) 20%, transparent),
                0 2px 10px color-mix(in srgb, var(--success-400) 12%, transparent);
        }

        .quran-segment-cluster.quran-segment-cluster-copied {
            background: color-mix(in srgb, var(--warning-200) 60%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--warning-500) 26%, transparent),
                0 2px 12px color-mix(in srgb, var(--warning-700) 18%, transparent);
            animation: quran-copy-highlight-enter 220ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        .quran-word-button {
            display: inline-flex;
            align-items: baseline;
            white-space: nowrap;
            line-height: 1.02;
            border-radius: 0;
            padding-inline: 0;
            cursor: default;
            transition:
                background-color 440ms cubic-bezier(0.22, 1, 0.36, 1),
                color 360ms cubic-bezier(0.22, 1, 0.36, 1),
                box-shadow 440ms cubic-bezier(0.22, 1, 0.36, 1);
            will-change: background-color, color, box-shadow;
        }

        .quran-word-button.quran-segment-hovered {
            background-color: color-mix(in srgb, var(--gray-300) 18%, transparent);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--gray-400) 8%, transparent);
            border-radius: 0.52em;
        }

        .quran-word-button.quran-segment-active {
            background: var(--quran-active-bg);
            color: var(--quran-active-text);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--success-300) 20%, transparent),
                0 2px 10px color-mix(in srgb, var(--success-400) 12%, transparent);
            border-radius: 0.52em;
        }

        .quran-word-button.quran-segment-copied {
            background: color-mix(in srgb, var(--warning-200) 64%, transparent);
            color: color-mix(in srgb, var(--warning-900) 70%, var(--quran-ink));
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--warning-500) 30%, transparent),
                0 2px 12px color-mix(in srgb, var(--warning-700) 16%, transparent);
            border-radius: 0.52em;
            animation: quran-copy-highlight-enter 220ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        @keyframes quran-copy-highlight-enter {
            from {
                opacity: 0;
                transform: translateY(0.08rem) scale(0.98);
            }

            to {
                opacity: 1;
                transform: translateY(0) scale(1);
            }
        }

        .quran-ayah-marker {
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            line-height: 1;
        }

        .quran-surah-header-line {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 100%;
            padding: 0.42rem 0;
            font-size: calc(var(--quran-font-size-meta) * 1.5 * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-surah-header-scale) * var(--quran-page-scale));
            line-height: 1;
            color: color-mix(in srgb, var(--primary-600) 86%, var(--quran-ink));
            background: transparent;
            box-shadow: none;
            letter-spacing: normal;
            text-wrap: nowrap;
        }

        .quran-surah-header-line--fatiha {
            margin-block-end: 1rem;
        }

        .quran-surah-header-line::before,
        .quran-surah-header-line::after {
            content: '';
            position: absolute;
            inset-inline: 0;
            height: 1px;
            background: linear-gradient(to right,
                    transparent 0%,
                    color-mix(in srgb, var(--quran-subtle) 22%, transparent) 14%,
                    color-mix(in srgb, var(--quran-subtle) 54%, transparent) 50%,
                    color-mix(in srgb, var(--quran-subtle) 22%, transparent) 86%,
                    transparent 100%);
        }

        .quran-surah-header-line::before {
            inset-block-start: 0;
        }

        .quran-surah-header-line::after {
            inset-block-end: 0;
        }

        .quran-surah-header-glyph {
            display: inline-block;
            position: relative;
            z-index: 1;
            padding-inline: 0.8rem;
            line-height: 1;
        }

        .quran-page-lines {
            transition: opacity 180ms ease;
            opacity: 0;
            user-select: none;
            -webkit-user-select: none;
            cursor: default;
            width: max-content;
            max-width: none;
            direction: rtl;
            display: flex;
            flex-direction: column;
            gap: calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-page-gap-multiplier));
        }

        .quran-page-lines * {
            user-select: none;
            -webkit-user-select: none;
            cursor: default;
        }

        .quran-page-lines[data-fit-state='fading-out'] {
            opacity: 0;
            visibility: visible;
            transition: opacity 120ms ease-out;
        }

        .quran-page-lines[data-fit-state='fitting'] {
            opacity: 0;
            visibility: hidden;
            transition: none;
        }

        .quran-page-lines[data-fit-state='ready'] {
            opacity: 1;
            visibility: visible;
            transition: opacity 180ms ease-in;
        }

        .quran-page-lines[data-fit-state='ready'] [data-quran-line] {
            animation: quran-line-reveal 440ms ease both;
            animation-delay: calc(var(--quran-line-index, 0) * 18ms);
        }

        @keyframes quran-line-reveal {
            from {
                opacity: 0;
                transform: translateY(0.42rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .quran-ayah-line-fit {
            max-width: 100%;
        }

        .quran-ayah-line-run-rect {
            font-size: calc(var(--quran-font-size-rect) * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-scale));
            line-height: calc(var(--quran-line-height-rect) * var(--quran-leading-scale) * var(--quran-page-leading-multiplier));
        }

        .quran-ayah-line-run-centered {
            font-size: calc(var(--quran-font-size-center) * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-scale));
            line-height: calc(var(--quran-line-height-center) * var(--quran-leading-scale) * var(--quran-page-leading-multiplier));
        }

        .quran-meta-line {
            font-size: calc(var(--quran-font-size-meta) * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-scale));
            line-height: calc(var(--quran-line-height-meta) * var(--quran-leading-scale) * var(--quran-page-leading-multiplier));
        }

        .quran-basmallah-line {
            display: inline-flex;
            align-items: baseline;
            justify-content: center;
            gap: 0.22ch;
            white-space: nowrap;
            font-size: calc(var(--quran-font-size-center) * var(--quran-type-scale) * var(--quran-page-type-scale) * var(--quran-page-scale));
            line-height: calc(var(--quran-line-height-center) * var(--quran-leading-scale) * var(--quran-page-leading-multiplier));
        }

        .quran-basmallah-word {
            display: inline-block;
        }

        .quran-page-motion-next {
            animation: quran-page-slide-next 260ms ease-out;
        }

        .quran-page-motion-prev {
            animation: quran-page-slide-prev 260ms ease-out;
        }

        .quran-top-strip {
            display: flex;
            align-items: center;
            gap: var(--quran-top-strip-gap, 0.65rem);
            padding: var(--quran-top-strip-pad-top, 0.8rem) var(--quran-top-strip-pad-inline, 1rem) var(--quran-top-strip-pad-bottom, 0.5rem);
        }

        .quran-top-actions {
            display: flex;
            flex: 1 1 auto;
            align-items: center;
            justify-content: flex-end;
            gap: var(--quran-top-actions-gap, 0.52rem);
            min-width: 0;
        }

        .quran-top-actions.quran-top-actions--wird-active {
            gap: 0;
        }

        .quran-top-actions-secondary {
            flex: 0 0 var(--quran-top-secondary-size-local, var(--quran-top-secondary-size, 2.35rem));
            inline-size: var(--quran-top-secondary-size-local, var(--quran-top-secondary-size, 2.35rem));
            block-size: var(--quran-top-secondary-size-local, var(--quran-top-secondary-size, 2.35rem));
            max-width: var(--quran-top-secondary-size-local, var(--quran-top-secondary-size, 2.35rem));
            opacity: 1;
            transform: translateX(0) scale(1);
            overflow: hidden;
            transition:
                opacity 260ms ease,
                transform 320ms cubic-bezier(0.2, 1, 0.36, 1),
                inline-size 320ms cubic-bezier(0.2, 1, 0.36, 1),
                max-width 320ms ease,
                margin 320ms ease;
        }

        .quran-top-actions.quran-top-actions--wird-active .quran-top-actions-secondary {
            inline-size: 0;
            max-width: 0;
            opacity: 0;
            margin: 0;
            pointer-events: none;
            overflow: hidden;
        }

        .quran-top-actions.quran-top-actions--wird-active #quran-reader-history-toggle.quran-top-actions-secondary {
            transform: translateX(0.6rem) scale(0.84);
        }

        .quran-top-actions.quran-top-actions--wird-active #quran-reader-bookmark-toggle.quran-top-actions-secondary {
            transform: translateX(-0.6rem) scale(0.84);
        }

        /* Credits: https://dribbble.com/shots/26540115-Progress-Bar */
        .quran-wird-progress-button {
            --quran-wird-progress-percent: 0%;
            --quran-wird-progress-browse-percent: 0%;
            --quran-wird-frame-cut: 0.82rem;
            --quran-wird-frame-cut-inner: 0.7rem;
            position: relative;
            display: inline-flex;
            flex: 1 1 auto;
            align-items: center;
            justify-content: stretch;
            min-width: var(--quran-top-progress-min-width, min(13rem, 50vw));
            min-height: var(--quran-top-progress-min-height, 2.5rem);
            padding: 0.16rem;
            border-radius: 999px;
            border: 0;
            background: transparent;
            color: color-mix(in srgb, var(--gray-900) 82%, var(--quran-panel-text));
            overflow: visible;
            isolation: isolate;
            transition:
                box-shadow 260ms ease,
                min-width 320ms cubic-bezier(0.2, 1, 0.36, 1);
        }

        .quran-top-actions.quran-top-actions--wird-active .quran-wird-progress-button {
            min-width: 100%;
        }

        .quran-wird-progress-button::before {
            content: '';
            position: absolute;
            inset: 0;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut) 0,
                    calc(100% - var(--quran-wird-frame-cut)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut)) 100%,
                    var(--quran-wird-frame-cut) 100%,
                    0 50%);
            /* box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--success-500) 50%, transparent); */
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--gray-100) 88%, white 12%),
                    color-mix(in srgb, var(--gray-200) 66%, transparent));
            opacity: 1;
            pointer-events: none;
            z-index: 0;
        }

        .quran-wird-progress-button::after {
            content: '';
            position: absolute;
            inset: 0.16rem;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            background: repeating-linear-gradient(115deg,
                    color-mix(in srgb, var(--gray-300) 24%, transparent) 0 0.62rem,
                    color-mix(in srgb, var(--gray-100) 14%, transparent) 0.62rem 1.04rem);
            pointer-events: none;
            z-index: 3;
            opacity: 0.48;
        }

        .quran-wird-progress-button:hover {}

        .quran-wird-progress-button:active {}

        .quran-wird-progress-aura-water,
        .quran-wird-progress-aura-reflect {
            position: absolute;
            inset: 0.16rem;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            pointer-events: none;
            z-index: 1;
        }

        .quran-wird-progress-aura-water {
            background:
                radial-gradient(70% 84% at 18% 28%, rgb(56 189 248 / 44%) 0%, transparent 68%),
                radial-gradient(66% 88% at 82% 74%, rgb(192 132 252 / 40%) 0%, transparent 72%),
                radial-gradient(58% 64% at 54% 34%, rgb(251 191 36 / 28%) 0%, transparent 64%);
            mix-blend-mode: screen;
            opacity: 0.66;
        }

        .quran-wird-progress-aura-reflect {
            background:
                linear-gradient(115deg,
                    rgb(255 255 255 / 0%) 12%,
                    rgb(255 255 255 / 32%) 28%,
                    rgb(255 255 255 / 0%) 44%,
                    rgb(236 72 153 / 18%) 62%,
                    rgb(255 255 255 / 0%) 80%),
                repeating-linear-gradient(125deg,
                    rgb(255 255 255 / 0%) 0 0.9rem,
                    rgb(255 255 255 / 12%) 0.9rem 1.22rem,
                    rgb(255 255 255 / 0%) 1.22rem 1.68rem);
            background-size: 190% 180%, 170% 170%;
            background-position: 12% 48%, 18% 60%;
            mix-blend-mode: plus-lighter;
            opacity: 0.42;
            filter: saturate(1.2);
        }

        /* Credits: https://codepen.io/pugson/pen/eYNXvyN */
        .quran-wird-progress-aura-rainbow {
            position: absolute;
            -webkit-mask-image: radial-gradient(ellipse at center, black 60%, transparent 100%);
            mask-image: radial-gradient(ellipse at center, black 60%, transparent 100%);
            inset: -0.07rem;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            opacity: 0;
            pointer-events: none;
            z-index: -1;
        }

        .quran-wird-progress-aura-rainbow::before,
        .quran-wird-progress-aura-rainbow::after {
            position: absolute;
            content: '';
            inset: 0;
            border-radius: inherit;
            background: linear-gradient(90deg,
                    var(--success-300),
                    var(--success-400),
                    var(--success-500),
                    var(--success-400),
                    var(--success-300));
            background-size: 200% 200%;
            animation: quran-wird-rainbow-glow 10s linear infinite;
        }

        .quran-wird-progress-aura-rainbow::before {
            filter: blur(12px);
            transform: scale(1.08);
            opacity: 0.8;
        }

        .quran-wird-progress-aura-rainbow::after {
            filter: blur(18px);
            transform: scale(1.14);
            opacity: 0.52;
        }

        .quran-wird-progress-button--active-aura .quran-wird-progress-aura-rainbow {
            opacity: 1;
        }

        .quran-wird-progress-button:hover .quran-wird-progress-aura-water,
        .quran-wird-progress-button:focus-visible .quran-wird-progress-aura-water {
            opacity: 0.82;
        }

        .quran-wird-progress-hover-shimmer {
            position: absolute;
            inset: 0.16rem;
            border-radius: 999px;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            overflow: hidden;
            pointer-events: none;
            z-index: 3;
            opacity: 0;
        }

        .quran-wird-progress-hover-shimmer::before {
            content: '';
            position: absolute;
            inset-block: 0;
            inset-inline-start: -46%;
            width: 38%;
            background: linear-gradient(112deg,
                    rgb(255 255 255 / 0%) 0%,
                    rgb(255 255 255 / 16%) 28%,
                    rgb(255 255 255 / 65%) 52%,
                    rgb(255 255 255 / 16%) 74%,
                    rgb(255 255 255 / 0%) 100%);
            filter: blur(0.5px);
        }

        .quran-wird-progress-button--shimmer-running .quran-wird-progress-hover-shimmer {
            opacity: 1;
        }

        .quran-wird-progress-button--shimmer-running .quran-wird-progress-hover-shimmer::before {
            animation: quran-wird-hover-shimmer-pass 1.18s cubic-bezier(0.24, 0.75, 0.3, 1) 1;
        }

        .quran-wird-progress-fill {
            position: absolute;
            inset-block: 0.16rem;
            inset-inline-start: 0;
            clip-path: polygon(var(--quran-wird-frame-cut-inner) 0,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 0,
                    100% 50%,
                    calc(100% - var(--quran-wird-frame-cut-inner)) 100%,
                    var(--quran-wird-frame-cut-inner) 100%,
                    0 50%);
            transition: width 420ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .quran-wird-progress-fill--committed {
            width: var(--quran-wird-progress-percent);
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--success-600) 74%, transparent),
                    color-mix(in srgb, var(--success-400) 58%, transparent));
            z-index: 1;
        }

        .quran-wird-progress-fill--browse {
            width: var(--quran-wird-progress-browse-percent);
            inset-block: 0.16rem;
            background: linear-gradient(90deg,
                    color-mix(in srgb, white 86%, var(--gray-100) 14%),
                    color-mix(in srgb, var(--gray-200) 56%, transparent));
            opacity: 0.34;
            z-index: 2;
        }

        .quran-wird-progress-button.quran-wird-progress-button--completed {
            box-shadow: 0 10px 20px color-mix(in srgb, var(--success-900) 16%, transparent);
        }

        .quran-reader--visual-enhancements-disabled .quran-wird-progress-aura-water,
        .quran-reader--visual-enhancements-disabled .quran-wird-progress-aura-reflect,
        .quran-reader--visual-enhancements-disabled .quran-wird-progress-aura-rainbow,
        .quran-reader--visual-enhancements-disabled .quran-wird-progress-hover-shimmer {
            display: none;
        }

        @keyframes quran-wird-aura-water-flow {
            0% {
                background-position: 12% 28%, 86% 74%, 54% 38%;
                opacity: 1;
            }

            50% {
                background-position: 34% 76%, 22% 22%, 66% 64%;
                opacity: 0;
            }

            100% {
                background-position: 12% 28%, 86% 74%, 54% 38%;
                opacity: 1;
            }
        }

        @keyframes quran-wird-aura-water-breathe {
            0% {
                filter: saturate(1.1) brightness(0.98);
                opacity: 1;
            }

            55% {
                filter: saturate(1.34) brightness(1.05);
                opacity: 0;
            }

            100% {
                filter: saturate(1.1) brightness(0.98);
                opacity: 1;
            }
        }

        @keyframes quran-wird-rainbow-glow {
            0% {
                background-position: 0% 50%;
            }

            100% {
                background-position: 200% 50%;
            }
        }

        @keyframes quran-wird-hover-shimmer-pass {
            0% {
                inset-inline-start: -46%;
                opacity: 0;
            }

            16% {
                opacity: 1;
            }

            100% {
                inset-inline-start: 112%;
                opacity: 0;
            }
        }

        .quran-wird-progress-content {
            position: relative;
            z-index: 4;
            display: flex;
            width: 100%;
            align-items: center;
            justify-content: space-between;
            gap: 0.52rem;
            padding-inline: var(--quran-top-progress-pad-inline, 0.98rem);
            line-height: 1;
            font-family: 'IBM Plex Sans Arabic', 'Readex Pro', ui-sans-serif, system-ui, sans-serif;
            direction: ltr;
        }

        .quran-wird-progress-percent {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.54rem;
            min-height: 1.56rem;
            padding-inline: 0.52rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--success-800) 42%, transparent);
            background: color-mix(in srgb, var(--success-100) 56%, transparent);
            color: color-mix(in srgb, var(--success-900) 84%, var(--quran-panel-text));
            font-size: 0.68rem;
            font-weight: 800;
            letter-spacing: 0.02em;
            white-space: nowrap;
        }

        .quran-wird-progress-count {
            font-size: 0.78rem;
            font-weight: 900;
            letter-spacing: 0.01em;
            font-variant-numeric: tabular-nums;
            white-space: nowrap;
        }

        .quran-top-action {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 2.15rem;
            height: 2.15rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-500) 52%, transparent);
            background: color-mix(in srgb, var(--quran-chip-bg) 90%, transparent);
            color: color-mix(in srgb, var(--quran-panel-text) 88%, var(--primary-500));
            transition:
                transform 160ms ease,
                border-color 220ms ease,
                background-color 220ms ease,
                box-shadow 220ms ease;
            box-shadow: 0 8px 18px color-mix(in srgb, var(--gray-900) 16%, transparent);
        }

        .quran-top-action:hover {
            border-color: color-mix(in srgb, var(--primary-500) 72%, transparent);
            background: color-mix(in srgb, var(--quran-chip-hover) 70%, transparent);
        }

        .quran-top-action:active {
            transform: scale(0.96);
        }

        .quran-top-action.quran-top-action--active {
            border-color: color-mix(in srgb, var(--warning-500) 72%, transparent);
            background: color-mix(in srgb, var(--warning-300) 28%, transparent);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--warning-200) 74%, transparent),
                0 10px 20px color-mix(in srgb, var(--warning-700) 18%, transparent);
            color: color-mix(in srgb, var(--warning-800) 92%, var(--quran-panel-text));
        }

        #quran-reader-history-toggle.quran-history-toggle-button {
            width: var(--quran-top-secondary-size-local,
                    var(--quran-history-action-size, var(--quran-top-secondary-size, 2.35rem)));
            height: var(--quran-top-secondary-size-local,
                    var(--quran-history-action-size, var(--quran-top-secondary-size, 2.35rem)));
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            background-color: color-mix(in srgb, var(--primary-600) 74%, var(--primary-500));
            border-radius: 999px;
            cursor: pointer;
            transition-duration: 0.3s;
            box-shadow: 2px 2px 10px rgb(0 0 0 / 13%);
            border: none;
            color: #fff;
        }

        #quran-reader-history-toggle .quran-history-toggle-icon {
            width: var(--quran-history-icon-size, 1.5rem);
            height: var(--quran-history-icon-size, 1.5rem);
            transform: rotate(0deg);
            transition: transform 460ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        #quran-reader-history-toggle .quran-history-toggle-icon path {
            stroke: #fff;
            fill: transparent;
        }

        #quran-reader-history-toggle.quran-history-toggle-button:hover {
            background-color: color-mix(in srgb, var(--primary-500) 68%, var(--primary-400));
        }

        #quran-reader-history-toggle.quran-history-toggle-button:hover .quran-history-toggle-icon {
            transform: rotate(360deg);
        }

        #quran-reader-history-toggle.quran-history-toggle-button:active {
            transform: scale(0.8);
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button {
            position: relative;
            overflow: hidden;
            cursor: pointer;
            background-color: color-mix(in srgb, var(--warning-600) 90%, var(--warning-700));
            width: var(--quran-top-secondary-size-local,
                    var(--quran-bookmark-action-size, var(--quran-top-secondary-size, 2.35rem)));
            height: var(--quran-top-secondary-size-local,
                    var(--quran-bookmark-action-size, var(--quran-top-secondary-size, 2.35rem)));
            border-radius: var(--quran-bookmark-action-radius, 0.625rem);
            display: inline-flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            border: 0;
            transition:
                transform 160ms ease,
                background-color 220ms ease,
                box-shadow 220ms ease;
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:hover {
            background-color: color-mix(in srgb, var(--warning-500) 50%, var(--warning-600));
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:focus-visible {
            box-shadow:
                0 0 0 2px color-mix(in srgb, var(--quran-page-bg) 76%, transparent),
                0 0 0 4px color-mix(in srgb, var(--warning-800) 80%, transparent);
        }

        #quran-reader-bookmark-toggle .quran-bookmark-toggle-fill {
            position: absolute;
            width: 0.5rem;
            height: 0.5rem;
            background: rgb(255 255 255 / 25%);
            border-radius: 999px;
            transform: scale(0);
            pointer-events: none;
            z-index: 1;
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:active .quran-bookmark-toggle-fill {
            animation: quran-bookmark-fill-spread 2s linear forwards;
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:not(:active) .quran-bookmark-toggle-fill {
            animation: quran-bookmark-fill-retract 0.3s ease forwards;
        }

        #quran-reader-bookmark-toggle .quran-bookmark-toggle-icon {
            position: relative;
            z-index: 2;
            width: var(--quran-bookmark-icon-size, 0.9375rem);
            height: auto;
        }

        #quran-reader-bookmark-toggle .quran-bookmark-toggle-icon path {
            stroke-dasharray: 200 0;
            stroke-dashoffset: 0;
            stroke: #fff;
            fill: transparent;
            transition: 0.5s;
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button.quran-bookmark-toggle-button--bookmarked .quran-bookmark-toggle-icon path {
            fill: #fff;
            animation: quran-bookmark-draw 0.5s linear;
            transition-delay: 0.5s;
        }

        @keyframes quran-bookmark-fill-spread {
            from {
                transform: scale(0);
                opacity: 0.6;
            }

            to {
                transform: scale(15);
                opacity: 1;
            }
        }

        @keyframes quran-bookmark-fill-retract {
            from {
                transform: scale(15);
                opacity: 1;
            }

            to {
                transform: scale(0);
                opacity: 0;
            }
        }

        @keyframes quran-bookmark-draw {
            0% {
                stroke-dasharray: 0 200;
                stroke-dashoffset: 80;
            }

            100% {
                stroke-dasharray: 200 0;
            }
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
            color: color-mix(in srgb, var(--primary-900) 92%, var(--quran-panel-text));
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

        .quran-soorah-trigger {
            position: relative;
            display: flex;
            align-items: center;
            justify-content: center;
            inline-size: var(--quran-top-trigger-width, 13.4rem);
            gap: 0.25rem;
            padding: 0.42rem 2.35rem;
            background: transparent;
            border: 0.14rem solid transparent;
            border-radius: 999px;
            color: color-mix(in srgb, var(--primary-700) 86%, var(--quran-panel-text));
            cursor: pointer;
            overflow: hidden;
            direction: rtl;
            font-size: var(--quran-top-trigger-font-size, 0.95rem);
            transition:
                box-shadow 0.6s cubic-bezier(0.23, 1, 0.32, 1),
                color 0.6s cubic-bezier(0.23, 1, 0.32, 1),
                transform 0.22s ease;
            box-shadow: 0 0 0 1.6px color-mix(in srgb, var(--primary-500) 72%, transparent);
            font-family: 'Readex Pro', 'IBM Plex Sans Arabic', 'Noto Naskh Arabic', ui-sans-serif, system-ui, sans-serif;
            font-weight: 700;
            user-select: none;
            -webkit-user-select: none;
        }

        .quran-soorah-trigger.quran-soorah-trigger--disabled {
            opacity: 0.55;
            cursor: not-allowed;
            box-shadow: 0 0 0 1.4px color-mix(in srgb, var(--gray-500) 52%, transparent);
            transform: none;
        }

        .quran-soorah-trigger.quran-soorah-trigger--disabled .quran-soorah-trigger-icon {
            opacity: 0.44;
            /* transform: translateX(0) scale(0.92); */
        }

        .quran-soorah-trigger.quran-soorah-trigger--disabled .quran-soorah-trigger-circle {
            opacity: 0;
            /* transform: translate(-50%, -50%) scale(0); */
        }

        .quran-soorah-trigger-icon {
            position: absolute;
            inset-inline-start: 0.82rem;
            width: 1rem;
            height: 1rem;
            z-index: 3;
            stroke: currentColor;
            stroke-width: 3;
            fill: none;
            opacity: 0;
            transform: translateX(0.4rem) scale(0.86);
            transition:
                inset-inline-start 0.8s cubic-bezier(0.23, 1, 0.32, 1),
                transform 0.8s cubic-bezier(0.23, 1, 0.32, 1),
                opacity 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .quran-soorah-trigger-circle {
            position: absolute;
            inset-block-start: 50%;
            inset-inline-start: 50%;
            transform: translate(-50%, -50%) scale(0);
            width: 1rem;
            height: 1rem;
            border-radius: 999px;
            opacity: 0.25;
            z-index: 1;
            background: color-mix(in srgb, var(--primary-600) 92%, var(--primary-400));
            transition:
                transform 0.7s cubic-bezier(0.23, 1, 0.32, 1),
                opacity 0.7s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .quran-soorah-trigger-text {
            position: relative;
            z-index: 2;
            transform: translateX(0.2rem);
            transition:
                transform 0.8s cubic-bezier(0.23, 1, 0.32, 1),
                color 0.8s cubic-bezier(0.23, 1, 0.32, 1);
        }

        .quran-soorah-trigger-text-inner {
            display: inline-block;
            white-space: nowrap;
        }

        .quran-soorah-trigger-text-inner.quran-caption-leave-forward {
            animation: quran-caption-leave-forward 140ms ease both;
        }

        .quran-soorah-trigger-text-inner.quran-caption-leave-backward {
            animation: quran-caption-leave-backward 140ms ease both;
        }

        .quran-soorah-trigger-text-inner.quran-caption-enter-forward {
            animation: quran-caption-enter-forward 180ms ease both;
        }

        .quran-soorah-trigger-text-inner.quran-caption-enter-backward {
            animation: quran-caption-enter-backward 180ms ease both;
        }

        .quran-reader--wird-active .quran-soorah-trigger,
        .quran-reader--wird-active .quran-soorah-trigger-icon,
        .quran-reader--wird-active .quran-soorah-trigger-circle,
        .quran-reader--wird-active .quran-soorah-trigger-text,
        .quran-reader--wird-active .quran-soorah-trigger-text-inner {
            transition: none;
            animation: none;
        }

        @keyframes quran-caption-leave-forward {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(0.5rem);
            }
        }

        @keyframes quran-caption-leave-backward {
            from {
                opacity: 1;
                transform: translateX(0);
            }

            to {
                opacity: 0;
                transform: translateX(-0.5rem);
            }
        }

        @keyframes quran-caption-enter-forward {
            from {
                opacity: 0;
                transform: translateX(-0.5rem);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes quran-caption-enter-backward {
            from {
                opacity: 0;
                transform: translateX(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover {
            color: color-mix(in srgb, var(--primary-50) 92%, var(--gray-900));
            box-shadow: 0 0 0 0.75rem transparent;
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover .quran-soorah-trigger-icon {
            inset-inline-start: 1.25rem;
            opacity: 1;
            transform: translateX(-0.2rem) scale(1);
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover .quran-soorah-trigger-text {
            transform: translateX(-0.62rem);
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:hover .quran-soorah-trigger-circle {
            transform: translate(-50%, -50%) scale(18);
            opacity: 1;
        }

        .quran-top-strip:not(.quran-top-strip--wird-active) .quran-soorah-trigger:active {
            transform: scale(0.97);
            box-shadow: 0 0 0 0.2rem color-mix(in srgb, var(--primary-500) 58%, transparent);
        }

        .quran-bottom-strip {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            grid-template-rows: auto auto;
            align-items: center;
            column-gap: 0.65rem;
            row-gap: 0.42rem;
            padding: 0.45rem 1rem 0.74rem;
            min-height: 3.65rem;
        }

        .quran-bottom-strip-nav-prev {
            grid-column: 1;
            grid-row: 1 / span 2;
            justify-self: center;
            align-self: center;
        }

        .quran-bottom-strip-nav-next {
            grid-column: 3;
            grid-row: 1 / span 2;
            justify-self: center;
            align-self: center;
        }

        .quran-bottom-strip-center {
            grid-column: 2;
            grid-row: 1;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .quran-bottom-strip-slider {
            grid-column: 2;
            grid-row: 2;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .quran-page-counter {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0;
            min-height: 2.4rem;
        }

        .quran-page-slider {
            appearance: none;
            -webkit-appearance: none;
            width: min(42vw, 13.2rem);
            min-width: 8rem;
            height: 0.56rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--gray-500) 50%, transparent);
            background: linear-gradient(90deg,
                    color-mix(in srgb, var(--primary-500) 62%, transparent),
                    color-mix(in srgb, var(--primary-300) 44%, transparent));
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-500) 18%, transparent),
                0 5px 12px color-mix(in srgb, var(--gray-900) 16%, transparent);
            cursor: pointer;
        }

        .quran-page-slider:disabled {
            cursor: not-allowed;
            filter: saturate(0.2);
            opacity: 0.62;
        }

        .quran-page-slider::-webkit-slider-thumb {
            appearance: none;
            -webkit-appearance: none;
            width: 0.95rem;
            height: 0.95rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-600) 72%, transparent);
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--primary-100) 92%, white),
                    color-mix(in srgb, var(--primary-200) 80%, var(--primary-50)));
            box-shadow: 0 4px 10px color-mix(in srgb, var(--primary-800) 20%, transparent);
        }

        .quran-page-slider:disabled::-webkit-slider-thumb,
        .quran-page-slider:disabled::-moz-range-thumb {
            border-color: color-mix(in srgb, var(--gray-500) 55%, transparent);
            box-shadow: none;
        }

        .quran-page-slider::-moz-range-thumb {
            width: 0.95rem;
            height: 0.95rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-600) 72%, transparent);
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--primary-100) 92%, white),
                    color-mix(in srgb, var(--primary-200) 80%, var(--primary-50)));
            box-shadow: 0 4px 10px color-mix(in srgb, var(--primary-800) 20%, transparent);
        }

        .quran-page-slider-chip {
            position: relative;
            --quran-counter-digit-width: 1ch;
            --quran-counter-digit-count: 3;
            --quran-counter-track-width: calc(var(--quran-counter-digit-width) * var(--quran-counter-digit-count));
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.12rem;
            min-width: 5.8rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--gray-500) 52%, transparent);
            background: linear-gradient(176deg,
                    color-mix(in srgb, var(--gray-200) 70%, transparent),
                    color-mix(in srgb, var(--gray-300) 58%, transparent));
            padding: 0.28rem 0.56rem;
            cursor: pointer;
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            font-size: 0.84rem;
            font-weight: 600;
            color: var(--quran-panel-text);
            line-height: 1;
            font-variant-numeric: tabular-nums;
            direction: ltr;
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-500) 12%, transparent),
                0 6px 14px color-mix(in srgb, var(--gray-800) 16%, transparent);
            transition:
                transform 140ms ease,
                box-shadow 180ms ease,
                color 180ms ease;
        }

        .quran-page-slider-chip:hover {
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--primary-300) 24%, transparent),
                0 8px 16px color-mix(in srgb, var(--primary-800) 20%, transparent);
        }

        .quran-page-slider-chip:active {}

        .quran-page-slider-chip.quran-page-slider-chip--disabled {
            opacity: 0.68;
            cursor: not-allowed;
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--gray-500) 8%, transparent),
                0 4px 10px color-mix(in srgb, var(--gray-900) 12%, transparent);
        }

        .quran-page-chip-current-wrap {
            position: relative;
            display: inline-grid;
            place-items: center;
            min-width: var(--quran-counter-track-width);
        }

        .quran-page-chip-current {
            display: inline-grid;
            grid-auto-flow: column;
            grid-auto-columns: var(--quran-counter-digit-width);
            align-items: center;
            justify-content: center;
            min-width: var(--quran-counter-track-width);
            width: var(--quran-counter-track-width);
        }

        .quran-page-chip-total {
            display: inline-grid;
            grid-auto-flow: column;
            grid-auto-columns: var(--quran-counter-digit-width);
            align-items: center;
            justify-content: center;
            min-width: var(--quran-counter-track-width);
            width: var(--quran-counter-track-width);
            opacity: 0.88;
        }

        .quran-page-chip-separator {
            opacity: 0.65;
        }

        .quran-page-counter-morph {
            position: absolute;
            inset: 0;
            display: inline-grid;
            grid-auto-flow: column;
            grid-auto-columns: var(--quran-counter-digit-width);
            align-items: center;
            justify-content: center;
            min-width: var(--quran-counter-track-width);
            width: var(--quran-counter-track-width);
            pointer-events: none;
            z-index: 2;
        }

        .quran-page-slider-chip,
        .quran-page-chip-current,
        .quran-page-chip-total,
        .quran-page-counter-morph,
        .quran-counter-cell,
        .quran-counter-roll__prev,
        .quran-counter-roll__next {
            font-variant-numeric: tabular-nums lining-nums;
            font-feature-settings: 'tnum' 1, 'lnum' 1;
            font-kerning: none;
            letter-spacing: 0;
            white-space: nowrap;
        }

        .quran-page-counter.quran-page-counter--morphing .quran-page-chip-current {
            color: transparent;
        }

        .quran-counter-roll {
            display: grid;
            place-items: center;
            contain: layout style;
            width: 100%;
        }

        .quran-counter-cell {
            display: inline-grid;
            place-items: center;
            width: var(--quran-counter-digit-width);
        }

        .quran-counter-static {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
        }

        .quran-counter-roll__prev,
        .quran-counter-roll__next {
            grid-area: 1 / 1;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            will-change: transform, opacity;
        }

        .quran-counter-roll__prev {
            animation: quran-counter-prev 520ms ease-out both;
        }

        .quran-counter-roll__next {
            animation: quran-counter-next 520ms ease-out both;
        }

        .quran-swipe-hint {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.2rem;
            min-height: 2.2rem;
            align-self: center;
            color: color-mix(in srgb, var(--quran-subtle) 86%, transparent);
            opacity: 0.88;
        }

        .quran-swipe-hint-button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border: 0;
            background: transparent;
            padding: 0.1rem 0.25rem;
            min-height: 2.2rem;
            min-width: 4.2rem;
            cursor: pointer;
            transition:
                opacity 160ms ease,
                transform 160ms ease,
                color 160ms ease;
        }

        .quran-swipe-hint-button:hover {
            opacity: 1;
            transform: translateY(-0.04rem);
            color: color-mix(in srgb, var(--quran-panel-text) 92%, var(--quran-subtle));
        }

        .quran-swipe-hint-button:active {
            transform: translateY(0);
        }

        .quran-swipe-hint-button:disabled {
            cursor: default;
            opacity: 0.48;
            transform: none;
            pointer-events: none;
        }

        .quran-swipe-hint-chev {
            color: color-mix(in srgb, var(--warning-400) 70%, var(--warning-600) 80%);
            display: inline-block;
            animation: quran-swipe-shimmer 1400ms ease-in-out infinite;
            font-size: 2rem;
            line-height: 1;
            vertical-align: middle;
            position: relative;
            top: 0;
            transition: color 200ms ease;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-static {
            animation: none !important;
            transform: none !important;
        }

        .quran-swipe-hint-button:disabled .quran-swipe-hint-chev,
        .quran-swipe-hint-button:disabled .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite {
            color: color-mix(in srgb, var(--gray-500) 82%, var(--gray-400));
        }

        .quran-swipe-hint-chev:nth-child(1) {
            animation-delay: 330ms;
        }

        .quran-swipe-hint-chev:nth-child(2) {
            animation-delay: 220ms;
        }

        .quran-swipe-hint-chev:nth-child(3) {
            animation-delay: 110ms;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite {
            animation: quran-swipe-shimmer-opposite 1400ms ease-in-out infinite;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite:nth-child(1) {
            animation-delay: 710ms;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite:nth-child(2) {
            animation-delay: 820ms;
        }

        .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite:nth-child(3) {
            animation-delay: 930ms;
        }

        .quran-reader--visual-enhancements-disabled .quran-swipe-hint-chev,
        .quran-reader--visual-enhancements-disabled .quran-swipe-hint-chev.quran-swipe-hint-chev-opposite {
            animation: none !important;
            opacity: 0.7;
            transform: none;
        }

        @keyframes quran-swipe-shimmer {

            0%,
            100% {
                opacity: 0.22;
                transform: translateX(0);
            }

            50% {
                opacity: 1;
                transform: translateX(0.1rem);
            }
        }

        @keyframes quran-swipe-shimmer-opposite {

            0%,
            100% {
                opacity: 1;
                transform: translateX(-0.1rem);
            }

            50% {
                opacity: 0.22;
                transform: translateX(0);
            }
        }

        @keyframes quran-page-slide-next {
            from {
                opacity: 0.46;
                transform: translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes quran-page-slide-prev {
            from {
                opacity: 0.46;
                transform: translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes quran-counter-prev {
            0% {
                opacity: 1;
                transform: translateY(0);
            }

            100% {
                opacity: 0;
                transform: translateY(-8px);
            }
        }

        @keyframes quran-counter-next {
            0% {
                opacity: 0;
                transform: translateY(8px);
            }

            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endassets

@php
    $initialReaderPayload = [
        'ready' => $ready,
        'pageNumber' => $pageNumber,
        'maxPage' => $maxPage,
        'activeAyahIndex' => $activeAyahIndex,
        'mushafLines' => $mushafLines,
        'qpcPageFontFamily' => $qpcPageFontFamily,
        'qpcPageFontUrl' => $qpcPageFontUrl,
        'qpcPageFontFormat' => $qpcPageFontFormat,
        'basmallahFontFamily' => $basmallahFontFamily,
        'basmallahFontUrl' => $basmallahFontUrl,
        'basmallahFontFormat' => $basmallahFontFormat,
        'basmallahText' => $basmallahText,
        'surahHeaderFontFamily' => $surahHeaderFontFamily,
        'surahHeaderFontUrl' => $surahHeaderFontUrl,
        'surahHeaderFontFormat' => $surahHeaderFontFormat,
        'surahNames' => $surahNames ?? [],
        'surahDirectory' => $surahDirectory ?? [],
        'useCenteredAyahLayout' => $useCenteredAyahLayout,
    ];
@endphp

<div
    class="quran-reader relative grid h-full w-full place-items-center items-center"
    dir="rtl"
    x-data="quranAppReader({
        api: {
            pageDataTemplate: @js(url('/quran-reader/pages/__PAGE__.json')),
            searchIndexUrl: @js(url('/quran-reader/search-index.json')),
        },
        initialPayload: @js($initialReaderPayload),
        nativeRuntime: @js(is_platform('native')),
        prewarmPages: @js(is_platform('native') ? 12 : 6),
        prefetchRadius: @js(is_platform('native') ? 3 : 2),
        searchModalId: @js('quran-reader-search-modal'),
        searchModalDomId: @js('quran-reader-search-modal'),
        searchActionModalId: @js(''),
        jumpPageModalId: @js('quran-reader-jump-page-modal'),
        historyModalId: @js('quran-reader-history-modal'),
        bookmarksModalId: @js('quran-reader-bookmarks-modal'),
        settings: @js($quranReaderSettings ?? ['enableVisualEnhancements' => true, 'targetWordsByDefault' => false, 'preserveHarakatOnCopy' => true, 'appendSurahAffixOnMultiCopy' => true, 'appendSurahAffixAlwaysOnCopy' => false, 'useVolumeButtonsNavigation' => true, 'useWesternNumerals' => true, 'wirdFrequencyMode' => 0, 'wirdKhatmatTarget' => 1]),
    })"
    x-bind:class="{
        'quran-reader--visual-enhancements-disabled': !doesEnableVisualEnhancements,
        'quran-reader--wird-active': wirdModeActive,
    }"
    x-on:control-panel-updated.window="applyControlPanelSettings($event.detail?.controlPanel ?? {})"
    x-on:switch-view.window="$nextTick(() => syncNativeVolumeNavigation())"
    x-on:quran-bootstrap-request.window="prepareQuranFromMainMenu($event.detail ?? {})"
    x-on:open-modal.window="handleModalLifecycleEvent('opened', $event)"
    x-on:x-modal-opened.window="handleModalLifecycleEvent('opened', $event)"
    x-on:close-modal.window="handleModalLifecycleEvent('closing', $event)"
    x-on:close-modal-quietly.window="handleModalLifecycleEvent('closing', $event)"
    x-on:x-modal-closed.window="handleModalLifecycleEvent('closed', $event)"
    x-on:opened-form-component-action-modal.window="handleModalLifecycleEvent('opened', $event)"
    x-on:closing-form-component-action-modal.window="handleModalLifecycleEvent('closing', $event)"
    x-on:closed-form-component-action-modal.window="handleModalLifecycleEvent('closed', $event)"
    x-on:support-unlock-updated.window="applySupportUnlockDecision($event.detail?.mode ?? null)"
>
    @if (!$ready)
        <section
            class="quran-reader-panel relative flex h-[clamp(28rem,82svh,50rem)] w-[min(94vw,50rem)] min-w-[18rem] flex-col items-center justify-center gap-4 rounded-[1.75rem] border px-6 py-7 text-center"
        >
            <h2 class="font-quran text-3xl leading-[1.9]">{{ arabic_text('قارئ القرآن') }}</h2>
            <p class="text-sm leading-7 opacity-85">
                {{ arabic_text('بيانات المصحف غير متاحة بعد. تأكد من تجهيز جداول القرآن وبياناتها، ثم أعد فتح قسم الكتاب.') }}
            </p>
        </section>
    @else
        <section
            class="quran-reader-panel min-w-75 aspect-8/11 relative flex h-[min(82svh,38rem)] flex-col overflow-hidden rounded-[1.75rem] border xl:h-[min(73.5svh,46rem)] 2xl:top-[0.3rem] 2xl:h-[min(73.5svh,62rem)]"
            x-bind:style="readerPanelStyle()"
            x-on:pointerdown.passive="onSwipeStart($event)"
            x-on:pointermove.window.passive="onSwipeMove($event)"
            x-on:pointerup.window.passive="onSwipeEnd($event)"
            x-on:pointercancel.window.passive="onSwipeCancel()"
            x-on:touchstart.passive="onSwipeStart($event)"
            x-on:touchmove.window.passive="onSwipeMove($event)"
            x-on:touchend.window.passive="onSwipeEnd($event)"
            x-on:touchcancel.window.passive="onSwipeCancel()"
            x-on:keydown.left.window.prevent="onGlobalArrowNavigate('left', $event)"
            x-on:keydown.right.window.prevent="onGlobalArrowNavigate('right', $event)"
            x-on:quran-go-prev.window="handleRequestedNavigation('prev', $event.detail)"
            x-on:quran-go-next.window="handleRequestedNavigation('next', $event.detail)"
            x-on:quran-go-page.window="handleRequestedNavigation('page', $event.detail)"
            x-on:quran-go-gate.window="window.dispatchEvent(new CustomEvent('quran-reader-go-gate'))"
            x-ref="readerPanel"
        >
            <header
                class="quran-top-strip min-h-[2.2rem] [--quran-bookmark-action-radius:0.5rem] [--quran-bookmark-action-size:1.82rem] [--quran-bookmark-icon-size:0.72rem] [--quran-history-action-size:1.82rem] [--quran-history-icon-size:1.04rem] [--quran-top-actions-gap:0.32rem] [--quran-top-progress-min-height:1.95rem] [--quran-top-progress-min-width:min(5.6rem,38vw)] [--quran-top-progress-pad-inline:0.56rem] [--quran-top-secondary-size:1.82rem] [--quran-top-strip-gap:0.36rem] [--quran-top-strip-pad-bottom:0.3rem] [--quran-top-strip-pad-inline:0.5rem] [--quran-top-strip-pad-top:0.48rem] [--quran-top-trigger-font-size:0.68rem] [--quran-top-trigger-width:8.5rem] sm:[--quran-bookmark-action-radius:0.56rem] sm:[--quran-bookmark-action-size:2rem] sm:[--quran-bookmark-icon-size:0.82rem] sm:[--quran-history-action-size:2rem] sm:[--quran-history-icon-size:1.18rem] sm:[--quran-top-actions-gap:0.4rem] sm:[--quran-top-progress-min-height:2.12rem] sm:[--quran-top-progress-min-width:min(7rem,42vw)] sm:[--quran-top-progress-pad-inline:0.7rem] sm:[--quran-top-secondary-size:2rem] sm:[--quran-top-strip-gap:0.46rem] sm:[--quran-top-strip-pad-bottom:0.38rem] sm:[--quran-top-strip-pad-inline:0.72rem] sm:[--quran-top-strip-pad-top:0.58rem] sm:[--quran-top-trigger-font-size:0.76rem] sm:[--quran-top-trigger-width:9.8rem] xl:min-h-[1.95rem] xl:[--quran-bookmark-action-radius:0.625rem] xl:[--quran-bookmark-action-size:2.2rem] xl:[--quran-bookmark-icon-size:0.9rem] xl:[--quran-history-action-size:2.21rem] xl:[--quran-history-icon-size:1.45rem] xl:[--quran-top-actions-gap:0.5rem] xl:[--quran-top-progress-min-height:2.4rem] xl:[--quran-top-progress-min-width:min(12rem,50vw)] xl:[--quran-top-progress-pad-inline:0.92rem] xl:[--quran-top-secondary-size:2.35rem] xl:[--quran-top-strip-gap:0.6rem] xl:[--quran-top-strip-pad-bottom:0.46rem] xl:[--quran-top-strip-pad-inline:0.9rem] xl:[--quran-top-strip-pad-top:0.72rem] xl:max-2xl:[--quran-top-trigger-font-size:0.75rem] xl:[--quran-top-trigger-width:10.5rem] 2xl:min-h-8 2xl:[--quran-bookmark-action-radius:0.625rem] 2xl:[--quran-bookmark-action-size:2.35rem] 2xl:[--quran-bookmark-icon-size:0.9375rem] 2xl:[--quran-history-action-size:2.35rem] 2xl:[--quran-history-icon-size:1.5rem] 2xl:[--quran-top-actions-gap:0.52rem] 2xl:[--quran-top-progress-min-height:2.5rem] 2xl:[--quran-top-progress-min-width:min(13rem,50vw)] 2xl:[--quran-top-progress-pad-inline:0.98rem] 2xl:[--quran-top-secondary-size:2.35rem] 2xl:[--quran-top-strip-gap:0.65rem] 2xl:[--quran-top-strip-pad-bottom:0.5rem] 2xl:[--quran-top-strip-pad-inline:1rem] 2xl:[--quran-top-strip-pad-top:0.8rem] 2xl:[--quran-top-trigger-font-size:0.95rem] 2xl:[--quran-top-trigger-width:13.4rem]"
                data-no-swipe
                x-bind:class="{ 'quran-top-strip--wird-active': wirdModeActive }"
            >
                <!-- Credits: uiverse.io/gharsh11032000/loud-chicken-53 -->
                <button
                    class="quran-soorah-trigger shrink-0 outline-none"
                    type="button"
                    dir="rtl"
                    x-bind:disabled="wirdModeActive"
                    x-bind:class="{ 'quran-soorah-trigger--disabled': wirdModeActive }"
                    x-on:click="
                        if (wirdModeActive) {
                            return;
                        }
                        warmSearchIndex();
                        $wire.mountAction('searchQuran');
                        queueSurahDirectoryAutoFocus();
                    "
                    x-bind:aria-label="@js(arabic_text('ابحث في ')) + currentSurahTitle()"
                >
                    <x-icon
                        class="quran-soorah-trigger-icon"
                        :name="'heroicon-o-magnifying-glass'"
                    />
                    <span class="quran-soorah-trigger-text">
                        <span
                            class="quran-soorah-trigger-text-inner"
                            x-bind:class="surahTriggerCaptionAnimClass"
                            x-text="currentSurahTriggerLabel()"
                        ></span>
                    </span>
                    <span class="quran-soorah-trigger-circle"></span>
                </button>
                <div
                    class="quran-top-actions h-full"
                    x-bind:class="{ 'quran-top-actions--wird-active': wirdModeActive }"
                >
                    <!-- Credits: https://uiverse.io/vinodjangid07/tricky-bullfrog-41 -->
                    <button
                        class="quran-history-toggle-button quran-top-actions-secondary outline-none [--quran-top-secondary-size-local:var(--quran-history-action-size)]"
                        id="quran-reader-history-toggle"
                        data-quran-open-history
                        type="button"
                        aria-label="{{ arabic_text('سجل التنقل') }}"
                        x-on:click="if (!wirdModeActive) { $wire.mountAction('navigationHistory') }"
                    >
                        <x-icon
                            class="quran-history-toggle-icon"
                            :name="'heroicon-o-clock'"
                        />
                    </button>

                    <button
                        class="quran-support-lock-target quran-wird-progress-button outline-none"
                        data-quran-wird-toggle
                        data-support-lock-target="wird-progress"
                        type="button"
                        x-data="{ hovered: $useHover($el) }"
                        x-bind:style="wirdProgressBarStyle()"
                        x-bind:class="{
                            'quran-wird-progress-button--completed': ensureWirdDailyRecord()?.completed,
                            'quran-wird-progress-button--shimmer-running': wirdHoverShimmerRunning,
                            'quran-wird-progress-button--active-aura': wirdModeActive && !isSupportLockActive(),
                        }"
                        x-bind:aria-pressed="wirdModeActive ? 'true' : 'false'"
                        x-bind:aria-label="wirdModeActive ? @js(arabic_text('إيقاف وضع الوِرد والعودة للقراءة الحرة')) : @js(arabic_text('تشغيل وضع الوِرد اليومي'))"
                        x-on:click="toggleWirdMode()"
                        x-on:mouseenter="startWirdHoverEffects()"
                        x-on:mouseleave="endWirdHoverEffects()"
                        x-on:focusin="startWirdHoverEffects()"
                        x-on:focusout="endWirdHoverEffects()"
                    >
                        <span
                            class="quran-wird-progress-aura-water"
                            aria-hidden="true"
                        ></span>
                        <span
                            class="quran-wird-progress-aura-reflect"
                            aria-hidden="true"
                            x-show="doesEnableVisualEnhancements"
                        ></span>
                        <span
                            class="quran-wird-progress-aura-rainbow"
                            aria-hidden="true"
                            x-show="doesEnableVisualEnhancements && wirdModeActive && !isSupportLockActive()"
                        ></span>
                        <span
                            class="quran-wird-progress-hover-shimmer"
                            aria-hidden="true"
                        ></span>
                        <span
                            class="quran-wird-progress-fill quran-wird-progress-fill--committed"
                            aria-hidden="true"
                        ></span>
                        <span
                            class="quran-wird-progress-fill quran-wird-progress-fill--browse"
                            aria-hidden="true"
                        ></span>
                        <span class="quran-wird-progress-content">
                            <span
                                class="quran-wird-progress-percent"
                                x-text="wirdProgressPercentLabel()"
                            ></span>
                            <span
                                class="text-primary-700 translate-y-1.5 text-xs font-bold opacity-0 transition-all duration-500"
                                x-bind:class="{
                                    'opacity-100! -translate-y-0.25!': (hovered || wirdModeActive) && !
                                        isSupportLockActive(),
                                    'font-normal!': wirdModeActive,
                                }"
                            >{{ arabic_text('الورد اليومي') }}</span>
                            <span
                                class="quran-wird-progress-count"
                                x-text="wirdProgressCounterLabel()"
                            ></span>
                        </span>
                    </button>

                    <!-- Credits: https://uiverse.io/vinodjangid07/breezy-goose-71 -->
                    <button
                        class="quran-bookmark-toggle-button quran-top-actions-secondary outline-none [--quran-top-secondary-size-local:var(--quran-bookmark-action-size)]"
                        id="quran-reader-bookmark-toggle"
                        data-quran-bookmark-toggle
                        type="button"
                        x-bind:class="{ 'quran-bookmark-toggle-button--bookmarked': isCurrentPageBookmarked() }"
                        x-bind:aria-pressed="isCurrentPageBookmarked() ? 'true' : 'false'"
                        x-bind:aria-label="isCurrentPageBookmarked() ? @js(arabic_text('إزالة علامة الصفحة الحالية')) : @js(arabic_text('حفظ الصفحة الحالية كعلامة'))"
                        x-on:pointerdown="onBookmarkButtonPointerDown($event)"
                        x-on:pointerup="onBookmarkButtonPointerUp($event)"
                        x-on:pointercancel="onBookmarkButtonPointerCancel()"
                        x-on:pointerleave="onBookmarkButtonPointerCancel()"
                        x-on:click.prevent="if (!wirdModeActive) { onBookmarkButtonClick() }"
                    >
                        <span
                            class="quran-bookmark-toggle-fill"
                            aria-hidden="true"
                        ></span>
                        <svg
                            class="quran-bookmark-toggle-icon"
                            aria-hidden="true"
                            width="15"
                            viewBox="0 0 50 70"
                            fill="none"
                            xmlns="http://www.w3.org/2000/svg"
                        >
                            <path
                                d="M46 62.0085L46 3.88139L3.99609 3.88139L3.99609 62.0085L24.5 45.5L46 62.0085Z"
                                stroke="#fff"
                                stroke-width="7"
                            ></path>
                        </svg>
                    </button>
                </div>
            </header>

            <div
                class="my-2 min-h-0 flex-1 overflow-hidden px-3 sm:my-3 sm:px-4 xl:my-1.5 xl:px-8 2xl:my-3 2xl:px-4"
                x-ref="pageViewport"
            >
                <div
                    class="quran-page-surface h-full rounded-2xl pt-2.5 transition-opacity duration-200"
                    x-bind:class="pageMotionClass"
                    x-on:click="clearAyahSelectionOnBackground($event)"
                    x-ref="pageSurface"
                >
                    @if ($qpcPageFontFamily !== null && $qpcPageFontUrl !== null && $qpcPageFontFormat !== null)
                        <style>
                            @font-face {
                                font-family: '{{ $qpcPageFontFamily }}';
                                src: url('{{ $qpcPageFontUrl }}') format('{{ $qpcPageFontFormat }}');
                                font-display: block;
                            }
                        </style>
                    @endif

                    @if ($surahHeaderFontFamily !== null && $surahHeaderFontUrl !== null && $surahHeaderFontFormat !== null)
                        <style>
                            @font-face {
                                font-family: '{{ $surahHeaderFontFamily }}';
                                src: url('{{ $surahHeaderFontUrl }}') format('{{ $surahHeaderFontFormat }}');
                                font-display: swap;
                            }
                        </style>
                    @endif

                    @if ($basmallahFontFamily !== null && $basmallahFontUrl !== null && $basmallahFontFormat !== null)
                        <style>
                            @font-face {
                                font-family: '{{ $basmallahFontFamily }}';
                                src: url('{{ $basmallahFontUrl }}') format('{{ $basmallahFontFormat }}');
                                font-display: swap;
                            }
                        </style>
                    @endif

                    <div
                        class="mx-auto grid h-full w-fit max-w-full place-items-center overflow-hidden"
                        x-ref="pageFrame"
                    >
                        <div
                            class="quran-page-lines mx-auto"
                            x-bind:data-fit-state="typeof pageFitState === 'function' ? pageFitState() : (isFittingPage ? 'fitting' : 'ready')"
                            x-bind:style="pageContentStyle()"
                            x-on:click="clearAyahSelectionOnBackground($event)"
                            x-on:pointerup.window.passive="onWordPointerUp($event)"
                            x-on:pointercancel.window.passive="onWordPointerCancel()"
                            x-on:mouseleave="clearHoveredSegment()"
                            x-ref="pageContent"
                        >
                            <template
                                x-for="lineEntry in mushafLines"
                                :key="`quran-line-${pageNumber}-${lineEntry.line_number}-${lineEntry.line_type}`"
                            >
                                <div
                                    data-quran-line
                                    x-show="shouldRenderLine(lineEntry)"
                                    x-bind:class="lineAlignmentClass(lineEntry)"
                                    x-bind:data-quran-line-number="Number(lineEntry?.line_number ?? 0)"
                                    x-bind:data-quran-line-type="String(lineEntry?.line_type ?? '')"
                                    x-bind:style="lineEntryStyle(lineEntry)"
                                >
                                    <template x-if="isBasmallahLine(lineEntry)">
                                        <div
                                            class="font-quran quran-basmallah-line"
                                            data-quran-line-text
                                            x-bind:style="basmallahLineStyle(lineEntry)"
                                        >
                                            <template x-if="isBasmallahLineWithWords(lineEntry)">
                                                <template
                                                    x-for="(word, wordIndex) in lineEntry.words"
                                                    :key="`quran-basmallah-word-${pageNumber}-${lineEntry.line_number}-${word.word_index ?? wordIndex}`"
                                                >
                                                    <span
                                                        class="quran-basmallah-word"
                                                        x-text="word.text"
                                                    ></span>
                                                </template>
                                            </template>
                                            <template x-if="!isBasmallahLineWithWords(lineEntry)">
                                                <span x-text="basmallahDisplayText(lineEntry)"></span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!isBasmallahLine(lineEntry) && isAyahLineWithWords(lineEntry)">
                                        <div
                                            data-quran-line-text
                                            x-bind:class="ayahLineClass(lineEntry)"
                                            x-bind:style="lineFontStyle()"
                                        >
                                            <template
                                                x-for="(cluster, clusterIndex) in lineWordClusters(lineEntry)"
                                                :key="`quran-cluster-${pageNumber}-${lineEntry.line_number}-${cluster.key ?? clusterIndex}`"
                                            >
                                                <span
                                                    class="quran-segment-cluster"
                                                    x-bind:class="{
                                                        'quran-segment-cluster-active': isAyahClusterActive(
                                                            cluster),
                                                        'quran-segment-cluster-hovered': isAyahClusterHovered(
                                                            cluster),
                                                        'quran-segment-cluster-copied': isAyahClusterCopied(
                                                            cluster),
                                                    }"
                                                >
                                                    <template
                                                        x-for="(word, wordIndex) in cluster.words"
                                                        :key="`quran-word-${pageNumber}-${lineEntry.line_number}-${word.word_index ?? wordIndex}`"
                                                    >
                                                        <span class="quran-word-slot inline-flex items-baseline">
                                                            <button
                                                                class="quran-word-button px-0 outline-none transition"
                                                                data-quran-word-button
                                                                type="button"
                                                                tabindex="-1"
                                                                x-bind:data-quran-ayah-index="Number(word?.ayah_index ?? 0)"
                                                                x-bind:data-quran-word-index="Number(word?.word_index ?? 0)"
                                                                x-bind:data-quran-ayah-number="Number(word?.ayah_number ?? 0)"
                                                                x-bind:data-quran-surah-number="Number(word?.surah_number ?? lineEntry?.surah_number ??
                                                                    0)"
                                                                x-bind:class="{
                                                                    'quran-segment-active': isWordActive(word),
                                                                    'quran-segment-hovered': isWordHovered(word),
                                                                    'quran-segment-copied': isWordCopied(word),
                                                                }"
                                                                x-bind:disabled="!isSelectableWord(word)"
                                                                x-on:pointerdown="onWordPointerDown($event, word)"
                                                                x-on:pointermove="onWordPointerMove($event)"
                                                                x-on:pointerup="onWordPointerUp($event)"
                                                                x-on:pointercancel="onWordPointerCancel()"
                                                                x-on:mouseleave="onWordPointerLeave(word)"
                                                                x-on:click.stop="onWordClick($event, word)"
                                                                x-on:mouseenter="setHoveredSegment(word)"
                                                                x-on:focus="setHoveredSegment(word)"
                                                                x-on:blur="clearHoveredSegment(word)"
                                                                x-text="word.text"
                                                            ></button>
                                                            <template x-if="showAyahMarker(word)">
                                                                <span
                                                                    class="quran-ayah-marker mr-0.5 text-[0.92rem]"
                                                                    style="color: var(--quran-subtle);"
                                                                    x-text="'۝' + word.ayah_number"
                                                                ></span>
                                                            </template>
                                                        </span>
                                                    </template>
                                                </span>
                                            </template>
                                        </div>
                                    </template>
                                    <template x-if="!isBasmallahLine(lineEntry) && !isAyahLineWithWords(lineEntry)">
                                        <template x-if="isSurahHeaderLine(lineEntry)">
                                            <div
                                                class="quran-surah-header-line"
                                                data-quran-line-text
                                                x-bind:class="{
                                                    'quran-surah-header-line--fatiha': Number(lineEntry?.surah_number ??
                                                        0) === 1
                                                }"
                                                x-bind:style="surahHeaderLineStyle(lineEntry)"
                                            >
                                                <span
                                                    class="quran-surah-header-glyph"
                                                    x-text="surahHeaderLineText(lineEntry)"
                                                ></span>
                                            </div>
                                        </template>
                                        <template x-if="!isSurahHeaderLine(lineEntry)">
                                            <div
                                                class="font-quran quran-meta-line"
                                                data-quran-line-text
                                                x-bind:style="metaLineStyle(lineEntry)"
                                                x-text="lineText(lineEntry)"
                                            ></div>
                                        </template>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <footer
                class="quran-bottom-strip"
                data-no-swipe
            >
                <button
                    class="quran-swipe-hint quran-swipe-hint-button quran-bottom-strip-nav-prev select-none outline-none"
                    type="button"
                    aria-label="{{ arabic_text('الصفحة السابقة') }}"
                    x-ref="prevChevronButton"
                    x-on:click.stop.prevent="goPreviousFromChevron()"
                >
                    <span
                        class="quran-swipe-hint-chev"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': isFirstNavigationPage() }"
                    >‹</span>
                    <span
                        class="quran-swipe-hint-chev"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': isFirstNavigationPage() }"
                    >‹</span>
                    <span
                        class="quran-swipe-hint-chev"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': isFirstNavigationPage() }"
                    >‹</span>
                </button>
                <div class="quran-bottom-strip-center">
                    <div
                        class="quran-page-counter"
                        x-bind:class="{ 'quran-page-counter--morphing': pageCounterPulse.isActive && pageCounterPulse.hasChanges }"
                    >
                        <button
                            class="quran-page-slider-chip outline-none"
                            type="button"
                            x-bind:aria-label="wirdModeActive ? @js(arabic_text('وضع الوِرد اليومي مفعل')) : @js(arabic_text('إدخال رقم صفحة'))"
                            x-bind:style="`--quran-counter-digit-count: ${pageCounterDigitLength()};`"
                            x-bind:disabled="wirdModeActive"
                            x-bind:class="{ 'quran-page-slider-chip--disabled': wirdModeActive }"
                            x-on:click="if (!wirdModeActive) { $wire.mountAction('jumpToPage') }"
                        >
                            <span class="quran-page-chip-total me-1.5">
                                <template
                                    x-for="(digit, digitIndex) in pageCounterDisplayDigits(pageCounterMaxDisplayValue())"
                                    :key="`quran-page-max-digit-${digitIndex}`"
                                >
                                    <span class="quran-counter-cell">
                                        <span
                                            class="quran-counter-static"
                                            x-text="digit"
                                        ></span>
                                    </span>
                                </template>
                            </span>
                            <span class="quran-page-chip-separator me-1.5">/</span>
                            <span class="quran-page-chip-current-wrap">
                                <span class="quran-page-chip-current">
                                    <template
                                        x-for="(digit, digitIndex) in pageCounterDisplayDigits(pageInput)"
                                        :key="`quran-page-current-digit-${digitIndex}`"
                                    >
                                        <span class="quran-counter-cell">
                                            <span
                                                class="quran-counter-static"
                                                x-text="digit"
                                            ></span>
                                        </span>
                                    </template>
                                </span>
                                <span
                                    class="quran-page-counter-morph"
                                    aria-hidden="true"
                                    x-cloak
                                    x-show="pageCounterPulse.isActive && pageCounterPulse.hasChanges"
                                >
                                    <template
                                        x-for="segment in pageCounterPulse.segments"
                                        :key="segment.key"
                                    >
                                        <span class="quran-counter-cell">
                                            <span
                                                x-show="!segment.changed"
                                                x-text="segment.next"
                                            ></span>
                                            <span
                                                class="quran-counter-roll"
                                                x-show="segment.changed"
                                            >
                                                <span
                                                    class="quran-counter-roll__prev"
                                                    x-text="segment.prev"
                                                ></span>
                                                <span
                                                    class="quran-counter-roll__next"
                                                    x-text="segment.next"
                                                ></span>
                                            </span>
                                        </span>
                                    </template>
                                </span>
                            </span>
                        </button>
                    </div>
                </div>
                <div class="quran-bottom-strip-slider">
                    <input
                        class="quran-page-slider outline-none"
                        type="range"
                        aria-label="{{ arabic_text('التنقل بين صفحات المصحف') }}"
                        x-bind:min="sliderMin()"
                        x-bind:max="sliderMax()"
                        x-bind:step="1"
                        x-bind:value="sliderValue()"
                        x-on:input="onSliderInput($event)"
                        x-on:change="onSliderCommit($event)"
                        x-on:pointerup="onSliderPointerRelease($event)"
                        x-on:mouseup="onSliderPointerRelease($event)"
                        x-on:touchend="onSliderPointerRelease($event)"
                        x-on:pointercancel="onSliderPointerRelease($event)"
                    />
                </div>
                <button
                    class="quran-swipe-hint quran-swipe-hint-button quran-bottom-strip-nav-next select-none outline-none"
                    type="button"
                    aria-label="{{ arabic_text('الصفحة التالية') }}"
                    x-ref="nextChevronButton"
                    x-bind:disabled="!wirdModeActive && isLastNavigationPage()"
                    x-on:click.stop.prevent="goNextFromChevron()"
                >
                    <span
                        class="quran-swipe-hint-chev quran-swipe-hint-chev-opposite"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': !wirdModeActive && isLastNavigationPage() }"
                    >›</span>
                    <span
                        class="quran-swipe-hint-chev quran-swipe-hint-chev-opposite"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': !wirdModeActive && isLastNavigationPage() }"
                    >›</span>
                    <span
                        class="quran-swipe-hint-chev quran-swipe-hint-chev-opposite"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': !wirdModeActive && isLastNavigationPage() }"
                    >›</span>
                </button>
            </footer>

            <template x-teleport="body">
                <div
                    class="quran-copy-popover"
                    data-quran-copy-popover
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

        <x-filament-actions::modals />
    @endif
</div>
