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
            gap: 0;
            white-space: nowrap;
            max-width: none;
        }

        .quran-segment-cluster {
            display: inline-flex;
            align-items: baseline;
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
            transition: opacity 300ms ease;
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

        .quran-page-lines[data-fit-state='fitting'] {
            opacity: 0;
        }

        .quran-page-lines[data-fit-state='ready'] {
            opacity: 1;
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
            /* justify-content: space-between; */
            padding: 0.8rem 1rem 0.5rem;
        }

        .quran-top-actions {
            display: inline-flex;
            align-items: center;
            justify-content: flex-end;
            gap: 0.52rem;
            min-width: 5.8rem;
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
            transform: translateY(-0.04rem);
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
            width: 2.35rem;
            height: 2.35rem;
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
            width: 1.5rem;
            height: 1.5rem;
        }

        #quran-reader-history-toggle .quran-history-toggle-icon path {
            stroke: #fff;
            fill: transparent;
        }

        #quran-reader-history-toggle.quran-history-toggle-button:hover {
            background-color: color-mix(in srgb, var(--primary-500) 68%, var(--primary-400));
        }

        #quran-reader-history-toggle.quran-history-toggle-button:hover .quran-history-toggle-icon {
            animation: quran-history-bell-ring 0.9s both;
        }

        #quran-reader-history-toggle.quran-history-toggle-button:active {
            transform: scale(0.8);
        }

        @keyframes quran-history-bell-ring {

            0%,
            100% {
                transform-origin: top;
            }

            15% {
                transform: rotateZ(10deg);
            }

            30% {
                transform: rotateZ(-10deg);
            }

            45% {
                transform: rotateZ(5deg);
            }

            60% {
                transform: rotateZ(-5deg);
            }

            75% {
                transform: rotateZ(2deg);
            }
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button {
            position: relative;
            overflow: hidden;
            cursor: pointer;
            background-color: color-mix(in srgb, var(--warning-600) 90%, var(--warning-700));
            width: 2.35rem;
            height: 2.35rem;
            border-radius: 0.625rem;
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
            transform: translateY(-0.04rem);
            background-color: color-mix(in srgb, var(--warning-500) 50%, var(--warning-600));
        }

        #quran-reader-bookmark-toggle.quran-bookmark-toggle-button:active {
            transform: scale(0.97);
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
            width: 0.9375rem;
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
            gap: 0.25rem;
            min-height: 2.2rem;
            padding: 0.42rem 2.35rem;
            background: transparent;
            border: 0.14rem solid transparent;
            border-radius: 999px;
            color: color-mix(in srgb, var(--primary-700) 86%, var(--quran-panel-text));
            cursor: pointer;
            overflow: hidden;
            direction: rtl;
            transition:
                box-shadow 0.6s cubic-bezier(0.23, 1, 0.32, 1),
                color 0.6s cubic-bezier(0.23, 1, 0.32, 1),
                transform 0.22s ease;
            box-shadow: 0 0 0 1.6px color-mix(in srgb, var(--primary-500) 72%, transparent);
            font-family: 'Readex Pro', 'IBM Plex Sans Arabic', 'Noto Naskh Arabic', ui-sans-serif, system-ui, sans-serif;
            font-size: 0.95rem;
            font-weight: 700;
            line-height: 1;
            user-select: none;
            -webkit-user-select: none;
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

        .quran-soorah-trigger:hover {
            color: color-mix(in srgb, var(--primary-50) 92%, var(--gray-900));
            box-shadow: 0 0 0 0.75rem transparent;
            transform: translateY(-0.04rem);
        }

        .quran-soorah-trigger:hover .quran-soorah-trigger-icon {
            inset-inline-start: 1.25rem;
            opacity: 1;
            transform: translateX(-0.2rem) scale(1);
        }

        .quran-soorah-trigger:hover .quran-soorah-trigger-text {
            transform: translateX(-0.62rem);
        }

        .quran-soorah-trigger:hover .quran-soorah-trigger-circle {
            transform: translate(-50%, -50%) scale(18);
            opacity: 1;
        }

        .quran-soorah-trigger:active {
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
            /* transform: translateY(-0.04rem); */
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--primary-300) 24%, transparent),
                0 8px 16px color-mix(in srgb, var(--primary-800) 20%, transparent);
        }

        .quran-page-slider-chip:active {
            /* transform: translateY(0); */
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
    class="quran-reader relative grid h-full w-full place-items-center"
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
        historyModalId: @js('quran-reader-history-modal'),
        bookmarksModalId: @js('quran-reader-bookmarks-modal'),
        settings: @js($quranReaderSettings ?? ['enableVisualEnhancements' => true, 'targetWordsByDefault' => false, 'preserveHarakatOnCopy' => true, 'appendSurahAffixOnMultiCopy' => true, 'appendSurahAffixAlwaysOnCopy' => false, 'useWesternNumerals' => true]),
    })"
    x-bind:class="{ 'quran-reader--visual-enhancements-disabled': !doesEnableVisualEnhancements }"
    x-on:control-panel-updated.window="applyControlPanelSettings($event.detail?.controlPanel ?? {})"
    x-on:x-modal-opened.window="handleModalLifecycleEvent('opened', $event)"
    x-on:close-modal.window="handleModalLifecycleEvent('closing', $event)"
    x-on:close-modal-quietly.window="handleModalLifecycleEvent('closing', $event)"
    x-on:x-modal-closed.window="handleModalLifecycleEvent('closed', $event)"
>
    @if (!$ready)
        <section
            class="quran-reader-panel relative flex h-[clamp(28rem,82svh,50rem)] w-[min(94vw,50rem)] min-w-[18rem] flex-col items-center justify-center gap-4 rounded-[1.75rem] border px-6 py-7 text-center"
        >
            <h2 class="font-quran text-3xl leading-[1.9]">قارئ القرآن</h2>
            <p class="text-sm leading-7 opacity-85">
                بيانات المصحف غير متاحة بعد. تأكد من تجهيز جداول القرآن وبياناتها، ثم أعد فتح قسم الكتاب.
            </p>
        </section>
    @else
        <section
            class="quran-reader-panel min-w-75 relative flex h-[clamp(31rem,92svh,62rem)] w-[min(96vw,60rem)] flex-col overflow-hidden rounded-[1.75rem] border 2xl:w-[min(84vw,40rem)]"
            x-bind:style="readerPanelStyle()"
            x-on:pointerdown.passive="onSwipeStart($event)"
            x-on:pointerup.window.passive="onSwipeEnd($event)"
            x-on:pointercancel.window.passive="onSwipeCancel()"
            x-on:touchstart.passive="onSwipeStart($event)"
            x-on:touchend.window.passive="onSwipeEnd($event)"
            x-on:touchcancel.window.passive="onSwipeCancel()"
            x-on:keyup.left.window.prevent="onGlobalArrowNavigate('left', $event)"
            x-on:keyup.right.window.prevent="onGlobalArrowNavigate('right', $event)"
            x-on:quran-go-prev.window="handleRequestedNavigation('prev', $event.detail)"
            x-on:quran-go-next.window="handleRequestedNavigation('next', $event.detail)"
            x-on:quran-go-page.window="handleRequestedNavigation('page', $event.detail)"
            x-on:quran-go-gate.window="$viewNav('quran-app-gate')"
            x-ref="readerPanel"
        >
            <header
                class="quran-top-strip"
                data-no-swipe
            >
                <!-- Credits: uiverse.io/gharsh11032000/loud-chicken-53 -->
                <button
                    class="quran-soorah-trigger w-[13.4rem] shrink-0 outline-none"
                    type="button"
                    dir="rtl"
                    x-on:click="
                        warmSearchIndex();
                        $wire.mountAction('searchQuran');
                        queueSurahDirectoryAutoFocus();
                    "
                    x-bind:aria-label="'ابحث في ' + currentSurahTitle()"
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
                <div class="quran-top-actions">
                    <!-- Credits: https://uiverse.io/vinodjangid07/tricky-bullfrog-41 -->
                    <button
                        class="quran-history-toggle-button outline-none"
                        id="quran-reader-history-toggle"
                        data-quran-open-history
                        type="button"
                        aria-label="سجل التنقل"
                        x-on:click="$wire.mountAction('navigationHistory')"
                    >
                        <x-icon
                            class="quran-history-toggle-icon"
                            :name="'heroicon-o-clock'"
                        />
                    </button>

                    <!-- Credits: https://uiverse.io/vinodjangid07/breezy-goose-71 -->
                    <button
                        class="quran-bookmark-toggle-button outline-none"
                        id="quran-reader-bookmark-toggle"
                        data-quran-bookmark-toggle
                        type="button"
                        x-bind:class="{
                            'quran-bookmark-toggle-button--bookmarked': (typeof isCurrentPageBookmarked === 'function' ?
                                isCurrentPageBookmarked() : false)
                        }"
                        x-bind:aria-pressed="(typeof isCurrentPageBookmarked === 'function' && isCurrentPageBookmarked()) ? 'true' : 'false'"
                        x-bind:aria-label="(typeof isCurrentPageBookmarked === 'function' && isCurrentPageBookmarked()) ?
                        'إزالة علامة الصفحة الحالية' : 'حفظ الصفحة الحالية كعلامة'"
                        x-on:pointerdown="onBookmarkButtonPointerDown($event)"
                        x-on:pointerup="onBookmarkButtonPointerUp($event)"
                        x-on:pointercancel="onBookmarkButtonPointerCancel()"
                        x-on:pointerleave="onBookmarkButtonPointerCancel()"
                        x-on:click.prevent="onBookmarkButtonClick()"
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
                class="my-2 min-h-0 flex-1 overflow-hidden px-3 sm:my-3 sm:px-4"
                x-ref="pageViewport"
            >
                <div
                    class="quran-page-surface h-full rounded-2xl transition-opacity duration-200"
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
                            x-bind:data-fit-state="isFittingPage ? 'fitting' : 'ready'"
                            x-bind:style="pageContentStyle()"
                            x-on:click="clearAyahSelectionOnBackground($event)"
                            x-on:pointerup.window.passive="onWordPointerUp($event)"
                            x-on:pointercancel.window.passive="onWordPointerCancel()"
                            x-on:mouseleave="clearHoveredSegment()"
                            x-ref="pageContent"
                        >
                            <template
                                x-for="line in mushafLines"
                                :key="`quran-line-${pageNumber}-${line.line_number}-${line.line_type}`"
                            >
                                <template x-if="shouldRenderLine(line)">
                                    <div
                                        data-quran-line
                                        x-bind:class="lineAlignmentClass(line)"
                                        x-bind:style="lineEntryStyle(line)"
                                    >
                                        <template x-if="isBasmallahLine(line)">
                                            <div
                                                class="font-quran quran-basmallah-line"
                                                data-quran-line-text
                                                x-bind:style="basmallahLineStyle(line)"
                                            >
                                                <template x-if="isBasmallahLineWithWords(line)">
                                                    <template
                                                        x-for="(word, wordIndex) in line.words"
                                                        :key="`quran-basmallah-word-${pageNumber}-${line.line_number}-${word.word_index ?? wordIndex}`"
                                                    >
                                                        <span
                                                            class="quran-basmallah-word"
                                                            x-text="word.text"
                                                        ></span>
                                                    </template>
                                                </template>
                                                <template x-if="!isBasmallahLineWithWords(line)">
                                                    <span x-text="basmallahDisplayText(line)"></span>
                                                </template>
                                            </div>
                                        </template>
                                        <template x-if="!isBasmallahLine(line) && isAyahLineWithWords(line)">
                                            <div
                                                data-quran-line-text
                                                x-bind:class="ayahLineClass(line)"
                                                x-bind:style="lineFontStyle()"
                                            >
                                                <template
                                                    x-for="(cluster, clusterIndex) in lineWordClusters(line)"
                                                    :key="`quran-cluster-${pageNumber}-${line.line_number}-${cluster.key ?? clusterIndex}`"
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
                                                            :key="`quran-word-${pageNumber}-${line.line_number}-${word.word_index ?? wordIndex}`"
                                                        >
                                                            <span class="inline-flex items-baseline">
                                                                <button
                                                                    class="quran-word-button px-0 outline-none transition"
                                                                    data-quran-word-button
                                                                    type="button"
                                                                    tabindex="-1"
                                                                    x-bind:data-quran-ayah-index="Number(word?.ayah_index ?? 0)"
                                                                    x-bind:data-quran-word-index="Number(word?.word_index ?? 0)"
                                                                    x-bind:data-quran-ayah-number="Number(word?.ayah_number ?? 0)"
                                                                    x-bind:data-quran-surah-number="Number(word?.surah_number ?? line?.surah_number ??
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
                                        <template x-if="!isBasmallahLine(line) && !isAyahLineWithWords(line)">
                                            <template x-if="isSurahHeaderLine(line)">
                                                <div
                                                    class="quran-surah-header-line"
                                                    data-quran-line-text
                                                    x-bind:class="{
                                                        'quran-surah-header-line--fatiha': Number(line?.surah_number ??
                                                            0) === 1
                                                    }"
                                                    x-bind:style="surahHeaderLineStyle(line)"
                                                >
                                                    <span
                                                        class="quran-surah-header-glyph"
                                                        x-text="surahHeaderLineText(line)"
                                                    ></span>
                                                </div>
                                            </template>
                                            <template x-if="!isSurahHeaderLine(line)">
                                                <div
                                                    class="font-quran quran-meta-line"
                                                    data-quran-line-text
                                                    x-bind:style="metaLineStyle(line)"
                                                    x-text="lineText(line)"
                                                ></div>
                                            </template>
                                        </template>
                                    </div>
                                </template>
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
                    aria-label="الصفحة السابقة"
                    x-on:click.stop.prevent="$dispatch('quran-go-prev')"
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
                            aria-label="إدخال رقم صفحة"
                            x-bind:style="`--quran-counter-digit-count: ${pageCounterDigitLength()};`"
                            x-on:click="$wire.mountAction('jumpToPage')"
                        >
                            <span class="quran-page-chip-total me-1.5">
                                <template
                                    x-for="(digit, digitIndex) in pageCounterDisplayDigits(maxPage)"
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
                        aria-label="التنقل بين صفحات المصحف"
                        min="1"
                        x-bind:max="Math.max(1, maxPage)"
                        x-model.number="pageInput"
                        x-on:input="onSliderInput()"
                        x-on:change="onSliderCommit()"
                    />
                </div>
                <button
                    class="quran-swipe-hint quran-swipe-hint-button quran-bottom-strip-nav-next select-none outline-none"
                    type="button"
                    aria-label="الصفحة التالية"
                    x-bind:disabled="isLastNavigationPage()"
                    x-on:click.stop.prevent="$dispatch('quran-go-next')"
                >
                    <span
                        class="quran-swipe-hint-chev quran-swipe-hint-chev-opposite"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': isLastNavigationPage() }"
                    >›</span>
                    <span
                        class="quran-swipe-hint-chev quran-swipe-hint-chev-opposite"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': isLastNavigationPage() }"
                    >›</span>
                    <span
                        class="quran-swipe-hint-chev quran-swipe-hint-chev-opposite"
                        x-bind:class="{ 'quran-swipe-hint-chev-static': isLastNavigationPage() }"
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
                    <span>تم النسخ</span>
                </div>
            </template>
        </section>

        <x-filament-actions::modals />
    @endif
</div>
