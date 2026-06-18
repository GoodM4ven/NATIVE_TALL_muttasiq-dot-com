@assets
    <style>
        .quran-app-gate-shell {
            --gate-cx: 50%;
            --gate-cy: 53%;
            --gate-top-overshoot: 0%;
            --quran-gold-1: #fde8ab;
            --quran-gold-2: #efc86b;
            --quran-gold-3: #d79f2f;
            --quran-gold-4: #8b6216;
            isolation: isolate;
            background: #0b0805;
            overflow: hidden;
            user-select: none;
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
            touch-action: none;
            transform-origin:
                var(--quran-gate-launch-origin-x, var(--gate-cx)) var(--quran-gate-launch-origin-y, var(--gate-cy));
            will-change: transform, opacity;
            contain: layout paint;
        }

        .quran-app-gate-shell.quran-app-gate-shell--base-perf .quran-app-sector__media {
            animation: none;
            opacity: 1;
        }

        .quran-app-gate-shell.quran-app-gate-shell--base-perf img.quran-app-sector__image-img {
            filter: none !important;
            transform: none !important;
            opacity: 1;
            transition:
                transform 130ms cubic-bezier(0.2, 0.9, 0.25, 1),
                opacity 130ms ease;
            will-change: transform;
        }

        .quran-app-gate-shell.quran-app-gate-shell--base-perf .quran-app-sector.is-active img.quran-app-sector__image-img {
            transform: scale(1.015) translateZ(0) !important;
        }

        .quran-app-gate-shell.quran-app-gate-shell--base-perf .quran-app-sector.is-muted img.quran-app-sector__image-img {
            transform: scale(1) !important;
        }

        .quran-app-gate-shell.quran-app-gate-shell--base-perf .quran-app-sector__chip,
        .quran-app-gate-shell.quran-app-gate-shell--base-perf .quran-app-gate-anchor,
        .quran-app-gate-shell.quran-app-gate-shell--base-perf .quran-app-gate-pointer {
            -webkit-backdrop-filter: none !important;
            backdrop-filter: none !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18) !important;
            transition:
                transform 200ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 150ms ease !important;
        }

        .quran-app-gate-shell.quran-app-gate-shell--native-media-recovering .quran-app-sector__media,
        .quran-app-gate-shell.quran-app-gate-shell--native-media-recovering img.quran-app-sector__image-img {
            opacity: 1 !important;
            visibility: visible !important;
            transform: translateZ(0) !important;
            -webkit-transform: translateZ(0) !important;
            backface-visibility: hidden;
            -webkit-backface-visibility: hidden;
        }

        .quran-app-gate-caption {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            z-index: 250;
            pointer-events: none;
            border-radius: 999px;
            border: 1px solid rgba(253, 232, 171, 0.42);
            background: linear-gradient(160deg,
                    rgba(24, 14, 7, 0.68) 0%,
                    rgba(11, 6, 3, 0.54) 100%);
            color: rgba(255, 245, 208, 0.96);
            font-family: 'Readex Pro', 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;
            font-weight: 700;
            letter-spacing: 0.02em;
            line-height: 1;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.48);
            box-shadow:
                inset 0 0 0 1px rgba(253, 232, 171, 0.12),
                0 8px 24px rgba(0, 0, 0, 0.34);
        }

        .quran-app-sector {
            position: absolute;
            inset: 0;
            display: block;
            margin: 0;
            border: 0;
            padding: 0;
            overflow: hidden;
            background: transparent;
            cursor: pointer;
            z-index: 15;
            transition:
                opacity 180ms ease,
                transform 240ms cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform, opacity;
        }

        .quran-app-sector.is-locked {
            cursor: default;
        }

        .quran-app-sector--tilawa {
            clip-path: polygon(calc(0% - var(--gate-top-overshoot)) 0, calc(100% + var(--gate-top-overshoot)) 0, var(--gate-cx) var(--gate-cy));
        }

        .quran-app-sector--tadabbur {
            clip-path: polygon(calc(0% - var(--gate-top-overshoot)) 0, var(--gate-cx) var(--gate-cy), calc(50% + 0.6px) 100%, 0 100%);
        }

        .quran-app-sector--hifth {
            clip-path: polygon(calc(100% + var(--gate-top-overshoot)) 0, var(--gate-cx) var(--gate-cy), calc(50% - 0.6px) 100%, 100% 100%);
        }

        .quran-app-sector__media {
            position: absolute;
            inset: 0;
            z-index: 1;
            opacity: 0;
            animation: quran-app-media-fade 760ms ease-out forwards;
        }

        .quran-app-sector__media--tilawa {
            inset: 0 0 calc(100% - var(--gate-cy)) 0;
            animation-delay: 40ms;
        }

        .quran-app-sector__media--tadabbur {
            inset: 0 calc(100% - var(--gate-cx)) 0 0;
            animation-delay: 120ms;
        }

        .quran-app-sector__media--hifth {
            inset: 0 0 0 var(--gate-cx);
            animation-delay: 200ms;
        }

        .quran-app-sector__media--morning {
            display: block;
        }

        .quran-app-sector__media--night {
            display: none;
        }

        .dark .quran-app-sector__media--morning {
            display: none;
        }

        .dark .quran-app-sector__media--night {
            display: block;
        }

        .quran-app-sector__veil {
            position: absolute;
            inset: 0;
            z-index: 2;
            background: linear-gradient(170deg,
                    color-mix(in srgb, var(--gray-950) 24%, transparent),
                    color-mix(in srgb, var(--gray-950) 70%, transparent));
            opacity: 0.1;
            transition: opacity 260ms ease;
            pointer-events: none;
        }

        .dark .quran-app-sector__veil {
            opacity: 0.4;
        }

        img.quran-app-sector__image-img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transform: scale(1.065);
            filter: blur(4px) brightness(0.66) saturate(0.9);
            transition:
                transform 280ms cubic-bezier(0.22, 1, 0.36, 1),
                filter 180ms ease,
                opacity var(--tw-duration, 500ms) var(--tw-ease, ease);
            pointer-events: none;
            will-change: transform;
            backface-visibility: hidden;
        }

        img.quran-app-sector__image-img--tilawa {
            object-position: 50% 62.5% !important;
        }

        img.quran-app-sector__image-img--hifth {
            object-position: 0% 50% !important;
        }

        img.quran-app-sector__image-img--tadabbur {
            object-position: 100% 50% !important;
        }

        .quran-app-sector.is-active img.quran-app-sector__image-img {
            transform: scale(1.018);
            filter: blur(0) brightness(0.96) saturate(1.04);
        }

        .quran-app-sector.is-muted img.quran-app-sector__image-img {
            filter: blur(1.2px) brightness(0.68) saturate(0.9);
        }

        .dark .quran-app-sector.is-muted img.quran-app-sector__image-img {
            filter: blur(1.55px) brightness(0.56) saturate(0.82);
        }

        .quran-app-sector.is-active .quran-app-sector__veil {
            opacity: 0.04;
        }

        .quran-app-sector__chip {
            position: absolute;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
            border-radius: 999px;
            border: 1px solid rgba(253, 232, 171, 0.52);
            background: linear-gradient(165deg,
                    rgba(73, 47, 17, 0.52) 0%,
                    rgba(26, 15, 6, 0.38) 48%,
                    rgba(12, 7, 3, 0.56) 100%);
            color: rgba(255, 249, 225, 0.96);
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: 0.04em;
            backdrop-filter: blur(8px) saturate(1.04);
            -webkit-backdrop-filter: blur(8px) saturate(1.04);
            box-shadow:
                inset 0 1px 0 rgba(255, 234, 183, 0.24),
                inset 0 -1px 0 rgba(44, 28, 10, 0.34),
                0 10px 24px rgba(8, 4, 2, 0.36);
            text-shadow:
                0 3px 18px rgba(10, 8, 4, 0.72),
                0 1px 0 rgba(0, 0, 0, 0.9);
            pointer-events: none;
            transition:
                transform 340ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 260ms ease,
                box-shadow 260ms ease;
        }

        .quran-app-sector__chip-text {
            display: inline-block;
            transition:
                transform 340ms cubic-bezier(0.22, 1, 0.36, 1),
                letter-spacing 260ms ease,
                filter 320ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 260ms ease;
            will-change: transform, filter, opacity;
        }

        .quran-app-sector.is-active .quran-app-sector__chip {
            box-shadow:
                inset 0 1px 0 rgba(255, 234, 183, 0.34),
                inset 0 -1px 0 rgba(44, 28, 10, 0.44),
                0 14px 30px rgba(8, 4, 2, 0.52);
        }

        .quran-app-sector.is-active .quran-app-sector__chip-text {
            transform: scale(1.04);
            letter-spacing: 0.05em;
        }

        .quran-app-sector__chip--tilawa {
            left: 50%;
            top: 30%;
            transform: translate(-50%, -50%);
        }

        .quran-app-sector__chip--tadabbur {
            left: 26%;
            top: 73%;
            transform: translate(-50%, -50%);
        }

        .quran-app-sector__chip--hifth {
            left: 74%;
            top: 73%;
            transform: translate(-50%, -50%);
        }

        .quran-app-sector__chip-lock {
            position: absolute;
            left: 50%;
            top: 50%;
            display: inline-flex;
            align-items: center;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--quran-gold-2) 76%, transparent);
            background: linear-gradient(165deg,
                    rgba(92, 60, 18, 0.92) 0%,
                    rgba(38, 22, 8, 0.9) 100%);
            box-shadow:
                inset 0 1px 0 rgba(255, 240, 197, 0.24),
                0 12px 28px rgba(8, 4, 2, 0.46);
            opacity: 0;
            transform: translate(-50%, -86%);
            transform-origin: center;
            filter: blur(6px);
            transition:
                opacity 260ms ease,
                transform 380ms cubic-bezier(0.22, 1, 0.36, 1),
                filter 300ms cubic-bezier(0.22, 1, 0.36, 1);
            pointer-events: none;
        }

        .quran-app-sector__chip-lock--text-only {
            justify-content: center;
        }

        .quran-app-sector__chip-lock--touch-prompt {
            top: 50%;
            z-index: 6;
            transform: translate(-50%, -136%);
        }

        .quran-app-sector--tilawa .quran-app-sector__chip-lock--touch-prompt {
            left: 50%;
            top: 30%;
        }

        .quran-app-sector__chip-lock-icon {
            color: color-mix(in srgb, var(--quran-gold-1) 92%, white);
            filter: drop-shadow(0 3px 10px rgba(0, 0, 0, 0.44));
        }

        .quran-app-sector__chip-lock-caption {
            color: color-mix(in srgb, var(--quran-gold-1) 90%, white);
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.42);
        }

        .quran-app-sector.is-active.is-locked .quran-app-sector__chip-lock {
            opacity: 1;
            transform: translate(-50%, -136%);
            filter: blur(0);
        }

        .quran-app-sector__chip-lock--text-only.is-touch-visible {
            opacity: 1;
            transform: translate(-50%, -136%);
            filter: blur(0);
        }

        .quran-app-sector__chip-lock--touch-prompt.is-touch-visible {
            opacity: 1;
            transform: translate(-50%, -136%);
            filter: blur(0);
        }

        .quran-app-sector.is-active.is-locked .quran-app-sector__chip-text {
            transform: translateY(0.3rem);
            letter-spacing: 0.046em;
            filter: blur(1.8px);
            opacity: 0.48;
        }

        .quran-app-gate-geometry {
            position: absolute;
            inset: 0;
            z-index: 190;
            pointer-events: none;
        }

        .quran-app-gate-focal-dim {
            position: absolute;
            inset: 0;
            background: radial-gradient(circle farthest-corner at var(--gate-cx) var(--gate-cy),
                    rgba(0, 0, 0, 0) 10%,
                    rgba(0, 0, 0, 0.5) 50%,
                    rgba(2, 1, 0, 1) 100%);
            pointer-events: none;
            z-index: 50;
            mix-blend-mode: multiply;
            opacity: 0;
        }

        .dark .quran-app-gate-focal-dim {
            opacity: 1;
        }

        .quran-app-gate-geometry path {
            stroke: rgba(232, 183, 92, 0.58);
            stroke-width: 0.44;
            stroke-linecap: round;
            filter: drop-shadow(0 2px 5px rgba(174, 117, 38, 0.32));
        }

        .quran-app-gate-anchor {
            position: absolute;
            left: var(--gate-cx);
            top: var(--gate-cy);
            width: clamp(8.4rem, 16vw, 12rem);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            border: 2px solid rgba(239, 200, 107, 0.9);
            background: radial-gradient(circle, rgba(239, 200, 107, 0.3) 0, rgba(0, 0, 0, 0) 74%);
            box-shadow:
                inset 0 0 0 1px rgba(253, 232, 171, 0.56),
                0 0 0 1px rgba(139, 98, 22, 0.46),
                0 0 26px rgba(215, 159, 47, 0.48);
            pointer-events: none;
            z-index: 220;
        }

        .quran-app-gate-anchor::before {
            content: '';
            position: absolute;
            inset: 0.45rem;
            border-radius: 999px;
            border: 1px solid rgba(253, 232, 171, 0.62);
            border-top-color: rgba(255, 248, 219, 0.96);
            border-right-color: rgba(239, 200, 107, 0.84);
            animation: quran-app-gate-spin 9s linear infinite;
            opacity: 0.92;
        }

        .quran-app-gate-anchor::after {
            content: '';
            position: absolute;
            inset: 0.85rem;
            border-radius: 999px;
            border: 1px solid rgba(239, 200, 107, 0.52);
            opacity: 0.72;
        }

        .quran-app-gate-core {
            position: absolute;
            left: 50%;
            top: 50%;
            width: clamp(0.9rem, 0.5vw, 0rem);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            background: radial-gradient(circle,
                    rgba(255, 249, 225, 0.98) 0,
                    rgba(253, 232, 171, 0.96) 38%,
                    rgba(215, 159, 47, 0.96) 76%);
            box-shadow:
                0 0 0 1px rgba(253, 232, 171, 0.58),
                0 0 24px rgba(239, 200, 107, 0.72),
                0 0 46px rgba(139, 98, 22, 0.52);
            opacity: 0.98;
        }

        .quran-app-gate-orbit {
            position: absolute;
            inset: 0;
            border-radius: 999px;
            transform: rotate(0deg);
            transition: opacity 220ms ease;
            will-change: transform;
            pointer-events: none;
            z-index: 230;
        }

        .quran-app-gate-pointer {
            position: absolute;
            left: 50%;
            top: 0;
            width: 1.9rem;
            height: 1.9rem;
            border-radius: 999px;
            border: 1.6px solid rgba(253, 232, 171, 0.9);
            background: radial-gradient(circle,
                    rgba(255, 249, 223, 0.18) 0,
                    rgba(215, 159, 47, 0.06) 50%,
                    rgba(0, 0, 0, 0) 72%);
            box-shadow:
                inset 0 0 0 1px rgba(255, 241, 198, 0.55),
                0 0 0 4px rgba(239, 200, 107, 0.14),
                0 0 24px rgba(215, 159, 47, 0.48);
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 230;
        }

        .quran-app-gate-pointer::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0.5rem;
            width: 0.72rem;
            height: 0.72rem;
            border-radius: 0.1rem;
            transform: translateY(-50%) rotate(45deg);
            border-top: 1px solid rgba(253, 232, 171, 0.92);
            border-right: 1px solid rgba(253, 232, 171, 0.92);
            background: linear-gradient(160deg,
                    rgba(255, 249, 223, 0.98),
                    rgba(215, 159, 47, 0.9));
            box-shadow: 0 0 11px rgba(215, 159, 47, 0.54);
        }

        .quran-app-gate-pointer::after {
            content: '';
            position: absolute;
            inset: 0.34rem;
            border-radius: 999px;
            border: 1px solid rgba(253, 232, 171, 0.56);
            opacity: 0.84;
        }

        [data-quran-app-shell].quran-app-shell--reader-launching .quran-app-gate-shell {
            animation: quran-gate-reader-launch 320ms cubic-bezier(0.18, 0.92, 0.28, 1) both;
        }

        [data-quran-app-shell].quran-app-shell--gate-returning .quran-app-gate-shell {
            animation: quran-gate-reader-return 340ms cubic-bezier(0.16, 1, 0.3, 1) both;
        }

        [data-quran-app-shell].quran-app-shell--reader-launching .quran-app-sector:not(.is-launch-target) {
            opacity: 0.44;
        }

        [data-quran-app-shell].quran-app-shell--gate-returning .quran-app-sector:not(.is-launch-target) {
            opacity: 0.64;
        }

        [data-quran-app-shell].quran-app-shell--reader-launching .quran-app-sector.is-launch-target {
            transform: scale(1.03);
        }

        [data-quran-app-shell].quran-app-shell--gate-returning .quran-app-sector.is-launch-target {
            transform: scale(1.01);
        }

        [data-quran-app-shell].quran-app-shell--reader-launching .quran-app-sector.is-launch-target img.quran-app-sector__image-img {
            transform: scale(1.04);
            filter: blur(0.2px) brightness(0.96) saturate(1.04);
        }

        [data-quran-app-shell].quran-app-shell--gate-returning .quran-app-sector.is-launch-target img.quran-app-sector__image-img {
            transform: scale(1.025);
            filter: blur(0.25px) brightness(0.94) saturate(1.04);
        }

        @keyframes quran-app-gate-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @keyframes quran-app-media-fade {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
            }
        }

        @keyframes quran-gate-reader-launch {
            from {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
            }

            to {
                opacity: 0.08;
                transform: translate3d(0, 0, 0) scale(1.24);
            }
        }

        @keyframes quran-gate-reader-return {
            from {
                opacity: 0.06;
                transform: translate3d(0, 0, 0) scale(1.24);
            }

            to {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
            }
        }

        @keyframes quran-gate-reader-launch-base {
            from {
                opacity: 1;
                transform: translate3d(0, 0, 0) scale(1);
            }

            to {
                opacity: 0;
                transform: translate3d(0, -1.6%, 0) scale(1.08);
            }
        }

        @keyframes quran-app-lock-pulse {
            0% {
                transform: scale(0.92);
                opacity: 0.82;
            }

            100% {
                transform: scale(1);
                opacity: 1;
            }
        }

        @media (max-width: 639px) {
            .quran-app-gate-shell {
                --gate-cy: 58%;
                --gate-top-overshoot: 20%;
                background-image: url('{{ asset('images/background/quran/night/tilawa.webp') }}');
                background-size: cover;
                background-position: center top;
            }

            .quran-app-sector__media--morning {
                display: none;
            }

            .quran-app-sector__media--night {
                display: block;
            }

            .quran-app-gate-caption {
                left: auto;
                right: 0.7rem;
                transform: none;
                line-height: 1.2;
                text-align: right;
            }

            .quran-app-sector__veil {
                opacity: 0.22;
                transition: opacity 130ms ease;
            }

            .quran-app-sector {
                transition:
                    opacity 110ms ease,
                    transform 130ms cubic-bezier(0.2, 0.9, 0.25, 1);
                will-change: auto;
            }

            .quran-app-sector.is-active {
                transform: scale(1.004);
            }

            .quran-app-sector.is-active .quran-app-sector__veil {
                opacity: 0.06;
            }

            img.quran-app-sector__image-img {
                transform: none !important;
                filter: none !important;
                opacity: 1;
                transition: transform 140ms cubic-bezier(0.2, 0.9, 0.25, 1);
                will-change: auto;
            }

            .quran-app-sector.is-active img.quran-app-sector__image-img {
                transform: scale(1.016) translateZ(0) !important;
            }

            .quran-app-sector.is-muted img.quran-app-sector__image-img {
                transform: scale(1) !important;
            }

            .dark .quran-app-sector__veil {
                opacity: 0.38;
            }

            .dark .quran-app-sector.is-active .quran-app-sector__veil {
                opacity: 0.2;
            }

            .quran-app-sector.is-active.is-locked .quran-app-sector__chip-text {
                filter: none;
                opacity: 0.58;
            }

            .quran-app-sector__chip {
                -webkit-backdrop-filter: none;
                backdrop-filter: none;
                text-shadow: 0 1px 4px rgba(0, 0, 0, 0.28);
                box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18);
                transition:
                    transform 200ms cubic-bezier(0.22, 1, 0.36, 1),
                    opacity 150ms ease;
            }

            .quran-app-sector.is-active .quran-app-sector__chip {
                box-shadow: 0 5px 12px rgba(0, 0, 0, 0.2);
            }

            .quran-app-sector__chip-text {
                opacity: 0.98;
                text-shadow: none;
                will-change: auto;
            }

            .quran-app-gate-focal-dim,
            .quran-app-gate-geometry {
                display: none;
            }

            .quran-app-gate-geometry path {
                filter: none;
            }

            .quran-app-sector__chip--tilawa {
                top: 37.5%;
            }

            .quran-app-sector__chip--tadabbur,
            .quran-app-sector__chip--hifth {
                top: 72%;
            }

            .quran-app-sector--tilawa .quran-app-sector__chip-lock--touch-prompt {
                top: 30%;
            }

            .quran-app-gate-geometry path {
                stroke-width: 0.34;
            }

            .quran-app-gate-anchor {
                width: clamp(7rem, 18vw, 9rem);
                border-width: 1px;
                box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--quran-gold-1) 14%, transparent);
            }

            .quran-app-gate-anchor::before {
                inset: 0.38rem;
                opacity: 0.66;
                animation: none;
            }

            .quran-app-gate-anchor::after {
                inset: 0.66rem;
                opacity: 0.45;
            }

            .quran-app-gate-core {
                width: clamp(1.55rem, 4vw, 2rem);
                box-shadow: 0 0 5px color-mix(in srgb, var(--quran-gold-2) 18%, transparent);
            }

            .quran-app-gate-pointer {
                width: 1.26rem;
                height: 1.26rem;
                box-shadow:
                    0 0 0 2px color-mix(in srgb, var(--quran-gold-2) 10%, transparent),
                    0 2px 5px rgba(0, 0, 0, 0.1);
            }

            .quran-app-gate-pointer::before {
                /* right: -0.2rem; */
                width: 0.38rem;
                height: 0.38rem;
                left: 0.4rem;
                box-shadow: 0 1px 4px rgba(215, 159, 47, 0.2);
            }

            .quran-app-gate-pointer::after {
                inset: 0.22rem;
            }

            .quran-app-gate-orbit {
                transition-duration: 0ms;
                will-change: auto;
            }

            .quran-app-sector__media {
                animation: none;
                opacity: 1;
            }

            [data-quran-app-shell].quran-app-shell--reader-launching .quran-app-gate-shell,
            [data-quran-app-shell].quran-app-shell--gate-returning .quran-app-gate-shell,
            [data-quran-app-shell].quran-app-shell--reader-launching .quran-app-sector,
            [data-quran-app-shell].quran-app-shell--gate-returning .quran-app-sector {
                animation: none !important;
                transform: none !important;
                opacity: 1 !important;
            }

            [data-quran-app-shell].quran-app-shell--reader-launching .quran-app-sector.is-launch-target img.quran-app-sector__image-img,
            [data-quran-app-shell].quran-app-shell--gate-returning .quran-app-sector.is-launch-target img.quran-app-sector__image-img {
                transform: none !important;
                filter: none !important;
                opacity: 1;
            }

            [data-quran-app-shell].quran-app-shell--reader-launching-base .quran-app-gate-shell {
                animation: quran-gate-reader-launch-base 190ms cubic-bezier(0.16, 1, 0.3, 1) both;
            }

            [data-quran-app-shell].quran-app-shell--reader-launching-base .quran-app-sector:not(.is-launch-target) {
                opacity: 0.72;
            }

            .quran-app-sector.is-launch-target {
                transform: scale(1.01);
            }

            .quran-app-sector.is-launch-target img.quran-app-sector__image-img {
                transform: scale(1.024) translateZ(0) !important;
                opacity: 1;
            }
        }

        /* Native (iOS/Android) is a WebView where performance is the priority, so the same perf reductions as the web base breakpoint apply at ALL native widths (not gated behind max-width: 639px, which would skip tablets / wide WebViews). */
        .native-platform .quran-app-gate-shell {
            will-change: auto;
        }

        .native-platform img.quran-app-sector__image-img {
            filter: none !important;
            transform: none !important;
            transition: transform 130ms cubic-bezier(0.2, 0.9, 0.25, 1) !important;
            will-change: auto;
        }

        .native-platform .quran-app-sector.is-active img.quran-app-sector__image-img {
            opacity: 1;
            transform: scale(1.015) translateZ(0) !important;
        }

        .native-platform .quran-app-sector.is-launch-target img.quran-app-sector__image-img {
            transform: scale(1.024) translateZ(0) !important;
        }

        .native-platform .quran-app-sector__chip,
        .native-platform .quran-app-gate-anchor,
        .native-platform .quran-app-gate-pointer {
            -webkit-backdrop-filter: none !important;
            backdrop-filter: none !important;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.18) !important;
            transition:
                transform 200ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 150ms ease !important;
        }

        .native-platform .quran-app-sector__chip {
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.28);
        }

        .native-platform .quran-app-sector__chip-text {
            text-shadow: none;
            will-change: auto;
        }

        .native-platform .quran-app-gate-core {
            box-shadow: 0 0 5px color-mix(in srgb, var(--quran-gold-2) 18%, transparent) !important;
        }

        .native-platform .quran-app-gate-pointer::before {
            box-shadow: 0 1px 4px rgba(215, 159, 47, 0.2);
        }

        /* Costly focal dim + SVG geometry never paint on native, at any width. */
        .native-platform .quran-app-gate-focal-dim,
        .native-platform .quran-app-gate-geometry {
            display: none !important;
        }

        .native-platform .quran-app-gate-anchor::before {
            animation: none !important;
        }

        .native-platform .quran-app-sector__veil,
        .native-platform .quran-app-gate-orbit {
            transition-duration: 180ms !important;
        }

        .native-platform .quran-app-gate-orbit {
            will-change: auto;
        }
    </style>
@endassets

<div
    @class([
        'absolute inset-x-0 bottom-0 z-10 sm:inset-0',
        '-top-22 sm:top-0' => is_platform('ios'),
        '-top-16 sm:top-0' => !is_platform('ios'),
    ])
    x-cloak
    x-show="views['quran-app-gate'].isOpen"
    x-transition:enter="transition-[opacity,transform] ease-out duration-380"
    x-transition:enter-start="opacity-0! scale-[0.985]"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition-opacity ease-in duration-180!"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0!"
>
    <section
        data-native-mobile-runtime="{{ is_platform('mobile') ? 'true' : 'false' }}"
        @class([
            'quran-app-gate-shell relative h-full w-full',
            'quran-app-gate-shell--base-perf' => is_platform('mobile'),
        ])
        x-data="quranAppGate"
        x-ref="shell"
        x-bind:class="{ 'quran-app-gate-shell--base-perf': shouldUseMobileBasePerfMode() }"
        x-on:pointerdown="handlePointerDown($event)"
        x-on:pointerup="handlePointerUp($event)"
        x-on:pointercancel="handlePointerUp($event)"
        x-on:pointerenter="handlePointerEnter()"
        x-on:pointerleave="handlePointerLeave()"
        x-on:pointermove="handlePointerMove($event)"
        x-on:touchstart.passive="handleTouchStart($event)"
        x-on:touchmove="handleTouchMove($event)"
        x-on:touchend="handleTouchEnd($event)"
        x-on:touchcancel="handleTouchEnd($event)"
    >
        <p @class([
            'top-[3.6rem]' => is_platform('ios'),
            'top-[1.76rem]' => !is_platform('ios'),
            'quran-app-gate-caption 3xl:top-8 3xl:px-[0.95rem] 3xl:py-2 3xl:text-[0.9rem] 4xl:top-10 4xl:px-[0.95rem] 4xl:py-[0.55rem] 4xl:text-[1.1rem] xl:top-6.5 px-[0.72rem] py-[0.26rem] text-[0.62rem] sm:top-[1.85rem] sm:px-[0.85rem] sm:py-2 sm:text-[0.7rem] md:top-8 md:px-4 md:py-2 md:text-[0.9rem] lg:top-6 lg:px-[0.925rem] lg:py-2 lg:text-[0.875rem] xl:px-[0.85rem] xl:py-[0.45rem] xl:text-[0.75rem] 2xl:top-[1.9rem] 2xl:px-3 2xl:py-2 2xl:text-[0.75rem]',
        ])>
            {{ arabic_text('اختر نمط القراءة الذي يناسب مقصدك') }}</p>

        <button
            class="quran-app-sector quran-app-sector--tilawa"
            type="button"
            aria-label="{{ arabic_text('تلاوة القرآن') }}"
            x-bind:class="{
                'is-active': isModeActive('tilawa'),
                'is-muted': currentMode() && !isModeActive('tilawa'),
                'is-locked': isModeLocked('tilawa'),
                'is-launch-target': isLaunchTransitioning && launchMode === 'tilawa'
            }"
            x-on:focus="pinMode('tilawa')"
            x-on:blur="unpinMode('tilawa')"
            x-on:click="openMode('tilawa', $event)"
        >
            @if (is_platform('mobile'))
                <span class="quran-app-sector__media quran-app-sector__media--tilawa">
                    <img
                        class="quran-app-sector__image-img quran-app-sector__image-img--tilawa select-none"
                        src="{{ asset('images/background/quran/night/tilawa.webp') }}"
                        alt="{{ arabic_text('وضع التلاوة') }}"
                        loading="eager"
                        decoding="async"
                        draggable="false"
                    >
                </span>
            @else
                <span class="quran-app-sector__media quran-app-sector__media--tilawa quran-app-sector__media--morning">
                    <x-goodmaven::blurred-image
                        class="absolute inset-0"
                        alt="{{ arabic_text('وضع التلاوة') }}"
                        :imagePath="asset('images/background/quran/morning/tilawa.webp')"
                        :thumbnailImagePath="asset('images/background/quran/morning/tilawa-blur-thumbnail.webp')"
                        :isEagerLoaded="true"
                        :isObjectCentered="false"
                        isDisplayEnforcedJs="() => window.location.hash === '#quran-app-gate'"
                        containerClasses="absolute inset-0 overflow-hidden bg-black/10 dark:bg-black/25"
                        imageClasses="quran-app-sector__image-img quran-app-sector__image-img--tilawa select-none"
                    />
                </span>
            @endif

            @if (!is_platform('mobile'))
                <span class="quran-app-sector__media quran-app-sector__media--tilawa quran-app-sector__media--night">
                    <x-goodmaven::blurred-image
                        class="absolute inset-0"
                        alt="{{ arabic_text('وضع التلاوة') }}"
                        :imagePath="asset('images/background/quran/night/tilawa.webp')"
                        :thumbnailImagePath="asset('images/background/quran/night/tilawa-blur-thumbnail.webp')"
                        :isEagerLoaded="true"
                        :isObjectCentered="false"
                        isDisplayEnforcedJs="() => window.location.hash === '#quran-app-gate'"
                        containerClasses="absolute inset-0 overflow-hidden bg-black/10 dark:bg-black/25"
                        imageClasses="quran-app-sector__image-img quran-app-sector__image-img--tilawa select-none"
                    />
                </span>
            @endif

            <span class="quran-app-sector__veil"></span>
            <span
                class="quran-app-sector__chip quran-app-sector__chip--tilawa font-arabic-serif 3xl:text-[2.2rem] 4xl:text-[2.6rem] 3xl:py-3 3xl:px-7 4xl:py-[0.9rem] 4xl:px-8 px-[0.9rem] py-[0.35rem] text-[1.25rem] sm:px-[1.15rem] sm:py-[0.52rem] sm:text-[1.5rem] md:px-[1.35rem] md:py-[0.6rem] md:text-[1.9rem] lg:px-[1.35rem] lg:py-[0.56rem] lg:text-[1.85rem] xl:px-[1.525rem] xl:py-[0.6rem] xl:text-[1.7rem] 2xl:px-[1.35rem] 2xl:py-[0.72rem] 2xl:text-[1.65rem]"
            >
                <span class="quran-app-sector__chip-text">{{ arabic_text('تلاوة') }}</span>
            </span>
            <span
                class="quran-app-sector__chip-lock quran-app-sector__chip-lock--text-only quran-app-sector__chip-lock--touch-prompt 3xl:gap-[0.46rem] 3xl:px-[0.72rem] 3xl:py-[0.38rem] 4xl:gap-[0.46rem] 4xl:px-[0.72rem] 4xl:py-[0.38rem] justify-center gap-[0.3rem] px-[0.52rem] py-[0.28rem] sm:gap-[0.46rem] sm:px-[0.72rem] sm:py-[0.38rem] md:gap-[0.46rem] md:px-[0.72rem] md:py-[0.38rem] lg:gap-[0.46rem] lg:px-[0.72rem] lg:py-[0.38rem] xl:gap-[0.46rem] xl:px-[0.72rem] xl:py-[0.38rem] 2xl:gap-[0.46rem] 2xl:px-[0.72rem] 2xl:py-[0.38rem]"
                data-quran-app-sector-touch-callout
                aria-hidden="true"
                x-cloak
                x-bind:class="{
                    'is-touch-visible': shouldShowAvailableModePrompt('tilawa'),
                }"
            >
                <span
                    class="quran-app-sector__chip-lock-caption 3xl:text-[1.06rem] 4xl:text-[1.12rem] text-[0.78rem] sm:text-[0.84rem] md:text-[0.92rem] lg:text-[0.95rem] xl:text-[0.78rem] 2xl:text-[0.84rem]"
                >{{ arabic_text('انقر') }}</span>
            </span>
        </button>

        <button
            class="quran-app-sector quran-app-sector--tadabbur"
            type="button"
            aria-label="{{ arabic_text('تدبّر القرآن') }}"
            x-bind:aria-disabled="isModeLocked('tadabbur') ? 'true' : 'false'"
            x-bind:class="{
                'is-active': isModeActive('tadabbur'),
                'is-muted': currentMode() && !isModeActive('tadabbur'),
                'is-locked': isModeLocked('tadabbur'),
                'is-launch-target': isLaunchTransitioning && launchMode === 'tadabbur'
            }"
            x-on:focus="pinMode('tadabbur')"
            x-on:blur="unpinMode('tadabbur')"
            x-on:click="openMode('tadabbur', $event)"
        >
            @if (is_platform('mobile'))
                <span class="quran-app-sector__media quran-app-sector__media--tadabbur">
                    <img
                        class="quran-app-sector__image-img quran-app-sector__image-img--tadabbur select-none"
                        src="{{ asset('images/background/quran/night/tadabbur.webp') }}"
                        alt="{{ arabic_text('وضع التدبّر') }}"
                        loading="eager"
                        decoding="async"
                        draggable="false"
                    >
                </span>
            @else
                <span
                    class="quran-app-sector__media quran-app-sector__media--tadabbur quran-app-sector__media--morning">
                    <x-goodmaven::blurred-image
                        class="absolute inset-0"
                        alt="{{ arabic_text('وضع التدبّر') }}"
                        :imagePath="asset('images/background/quran/morning/tadabbur.webp')"
                        :thumbnailImagePath="asset('images/background/quran/morning/tadabbur-blur-thumbnail.webp')"
                        :isEagerLoaded="true"
                        :isObjectCentered="false"
                        isDisplayEnforcedJs="() => window.location.hash === '#quran-app-gate'"
                        containerClasses="absolute inset-0 overflow-hidden bg-black/10 dark:bg-black/25"
                        imageClasses="quran-app-sector__image-img quran-app-sector__image-img--tadabbur select-none"
                    />
                </span>
            @endif

            @if (!is_platform('mobile'))
                <span class="quran-app-sector__media quran-app-sector__media--tadabbur quran-app-sector__media--night">
                    <x-goodmaven::blurred-image
                        class="absolute inset-0"
                        alt="{{ arabic_text('وضع التدبّر') }}"
                        :imagePath="asset('images/background/quran/night/tadabbur.webp')"
                        :thumbnailImagePath="asset('images/background/quran/night/tadabbur-blur-thumbnail.webp')"
                        :isEagerLoaded="true"
                        :isObjectCentered="false"
                        isDisplayEnforcedJs="() => window.location.hash === '#quran-app-gate'"
                        containerClasses="absolute inset-0 overflow-hidden bg-black/10 dark:bg-black/25"
                        imageClasses="quran-app-sector__image-img quran-app-sector__image-img--tadabbur select-none"
                    />
                </span>
            @endif

            <span class="quran-app-sector__veil"></span>
            <span
                class="quran-app-sector__chip quran-app-sector__chip--tadabbur font-arabic-serif 3xl:text-[2.2rem] 4xl:text-[2.6rem] 3xl:py-3 3xl:px-7 4xl:py-[0.9rem] 4xl:px-8 px-[0.9rem] py-[0.35rem] text-[1.25rem] sm:px-[1.15rem] sm:py-[0.52rem] sm:text-[1.5rem] md:px-[1.35rem] md:py-[0.6rem] md:text-[1.9rem] lg:px-[1.35rem] lg:py-[0.56rem] lg:text-[1.85rem] xl:px-[1.525rem] xl:py-[0.6rem] xl:text-[1.7rem] 2xl:px-[1.35rem] 2xl:py-[0.72rem] 2xl:text-[1.65rem]"
            >
                <span class="quran-app-sector__chip-text">{{ arabic_text('تدبّر') }}</span>
                <span
                    class="quran-app-sector__chip-lock 3xl:gap-[0.46rem] 3xl:px-[0.72rem] 3xl:py-[0.38rem] 4xl:gap-[0.46rem] 4xl:px-[0.72rem] 4xl:py-[0.38rem] gap-[0.3rem] px-[0.52rem] py-[0.28rem] sm:gap-[0.46rem] sm:px-[0.72rem] sm:py-[0.38rem] md:gap-[0.46rem] md:px-[0.72rem] md:py-[0.38rem] lg:gap-[0.46rem] lg:px-[0.72rem] lg:py-[0.38rem] xl:gap-[0.46rem] xl:px-[0.72rem] xl:py-[0.38rem] 2xl:gap-[0.46rem] 2xl:px-[0.72rem] 2xl:py-[0.38rem]"
                >
                    <x-icon
                        class="quran-app-sector__chip-lock-icon 3xl:w-[1.55rem] 3xl:h-[1.55rem] 4xl:w-[1.7rem] 4xl:h-[1.7rem] h-[1.2rem] w-[1.2rem] sm:h-4 sm:w-4 md:h-4 md:w-4 lg:h-[0.93rem] lg:w-[0.93rem] xl:h-[1.1rem] xl:w-[1.1rem] 2xl:h-[1.05rem] 2xl:w-[1.05rem]"
                        :name="'heroicon-o-lock-closed'"
                    />
                    <span
                        class="quran-app-sector__chip-lock-caption 3xl:text-[1.2rem] 4xl:text-[1.38rem] text-[0.9rem] sm:text-[1rem] md:text-[1.12rem] lg:text-[1.16rem] xl:text-[0.9rem] 2xl:text-[1rem]"
                    >{{ arabic_text('قريبًا') }}</span>
                </span>
            </span>
        </button>

        <button
            class="quran-app-sector quran-app-sector--hifth"
            type="button"
            aria-label="{{ arabic_text('حفظ القرآن') }}"
            x-bind:aria-disabled="isModeLocked('hifth') ? 'true' : 'false'"
            x-bind:class="{
                'is-active': isModeActive('hifth'),
                'is-muted': currentMode() && !isModeActive('hifth'),
                'is-locked': isModeLocked('hifth'),
                'is-launch-target': isLaunchTransitioning && launchMode === 'hifth'
            }"
            x-on:focus="pinMode('hifth')"
            x-on:blur="unpinMode('hifth')"
            x-on:click="openMode('hifth', $event)"
        >
            @if (is_platform('mobile'))
                <span class="quran-app-sector__media quran-app-sector__media--hifth">
                    <img
                        class="quran-app-sector__image-img quran-app-sector__image-img--hifth select-none"
                        src="{{ asset('images/background/quran/night/hifth.webp') }}"
                        alt="{{ arabic_text('وضع الحفظ') }}"
                        loading="eager"
                        decoding="async"
                        draggable="false"
                    >
                </span>
            @else
                <span class="quran-app-sector__media quran-app-sector__media--hifth quran-app-sector__media--morning">
                    <x-goodmaven::blurred-image
                        class="absolute inset-0"
                        alt="{{ arabic_text('وضع الحفظ') }}"
                        :imagePath="asset('images/background/quran/morning/hifth.webp')"
                        :thumbnailImagePath="asset('images/background/quran/morning/hifth-blur-thumbnail.webp')"
                        :isEagerLoaded="true"
                        :isObjectCentered="false"
                        isDisplayEnforcedJs="() => window.location.hash === '#quran-app-gate'"
                        containerClasses="absolute inset-0 overflow-hidden bg-black/10 dark:bg-black/25"
                        imageClasses="quran-app-sector__image-img quran-app-sector__image-img--hifth select-none"
                    />
                </span>
            @endif

            @if (!is_platform('mobile'))
                <span class="quran-app-sector__media quran-app-sector__media--hifth quran-app-sector__media--night">
                    <x-goodmaven::blurred-image
                        class="absolute inset-0"
                        alt="{{ arabic_text('وضع الحفظ') }}"
                        :imagePath="asset('images/background/quran/night/hifth.webp')"
                        :thumbnailImagePath="asset('images/background/quran/night/hifth-blur-thumbnail.webp')"
                        :isEagerLoaded="true"
                        :isObjectCentered="false"
                        isDisplayEnforcedJs="() => window.location.hash === '#quran-app-gate'"
                        containerClasses="absolute inset-0 overflow-hidden bg-black/10 dark:bg-black/25"
                        imageClasses="quran-app-sector__image-img quran-app-sector__image-img--hifth select-none"
                    />
                </span>
            @endif

            <span class="quran-app-sector__veil"></span>
            <span
                class="quran-app-sector__chip quran-app-sector__chip--hifth font-arabic-serif 3xl:text-[2.2rem] 4xl:text-[2.6rem] 3xl:py-3 3xl:px-7 4xl:py-[0.9rem] 4xl:px-8 px-[0.9rem] py-[0.35rem] text-[1.25rem] sm:px-[1.15rem] sm:py-[0.52rem] sm:text-[1.5rem] md:px-[1.35rem] md:py-[0.6rem] md:text-[1.9rem] lg:px-[1.35rem] lg:py-[0.56rem] lg:text-[1.85rem] xl:px-[1.525rem] xl:py-[0.6rem] xl:text-[1.7rem] 2xl:px-[1.35rem] 2xl:py-[0.72rem] 2xl:text-[1.65rem]"
            >
                <span class="quran-app-sector__chip-text">{{ arabic_text('حفظ') }}</span>
                <span
                    class="quran-app-sector__chip-lock 3xl:gap-[0.46rem] 3xl:px-[0.72rem] 3xl:py-[0.38rem] 4xl:gap-[0.46rem] 4xl:px-[0.72rem] 4xl:py-[0.38rem] gap-[0.3rem] px-[0.52rem] py-[0.28rem] sm:gap-[0.46rem] sm:px-[0.72rem] sm:py-[0.38rem] md:gap-[0.46rem] md:px-[0.72rem] md:py-[0.38rem] lg:gap-[0.46rem] lg:px-[0.72rem] lg:py-[0.38rem] xl:gap-[0.46rem] xl:px-[0.72rem] xl:py-[0.38rem] 2xl:gap-[0.46rem] 2xl:px-[0.72rem] 2xl:py-[0.38rem]"
                >
                    <x-icon
                        class="quran-app-sector__chip-lock-icon 3xl:w-[1.7rem] 3xl:h-[1.7rem] 4xl:w-[1.7rem] 4xl:h-[1.7rem] h-[1.2rem] w-[1.2rem] sm:h-4 sm:w-4 md:h-4 md:w-4 lg:h-[0.93rem] lg:w-[0.93rem] xl:h-[1.6rem] xl:w-[1.6rem] 2xl:h-[1.7rem] 2xl:w-[1.7rem]"
                        :name="'heroicon-o-lock-closed'"
                    />
                    <span
                        class="quran-app-sector__chip-lock-caption 3xl:text-[1.38rem] 4xl:text-[1.38rem] text-[0.9rem] sm:text-[1rem] md:text-[1.12rem] lg:text-[1.16rem] xl:text-[1.38rem] 2xl:text-[1.38rem]"
                    >{{ arabic_text('قريبًا') }}</span>
                </span>
            </span>
        </button>

        <div
            class="quran-app-gate-focal-dim"
            aria-hidden="true"
        ></div>

        <div
            class="quran-app-gate-anchor"
            aria-hidden="true"
            x-ref="anchorCircle"
        >
            <span
                class="quran-app-gate-orbit"
                x-bind:style="{ transform: `rotate(${orbitRenderAngleDeg}deg)` }"
            >
                <span class="quran-app-gate-pointer"></span>
            </span>
            <span class="quran-app-gate-core"></span>
        </div>
    </section>
</div>
