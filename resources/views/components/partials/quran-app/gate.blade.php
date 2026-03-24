@assets
    <style>
        .quran-app-gate-shell {
            --gate-cx: 50%;
            --gate-cy: 53%;
            --quran-gold-1: #fde8ab;
            --quran-gold-2: #efc86b;
            --quran-gold-3: #d79f2f;
            --quran-gold-4: #8b6216;
            isolation: isolate;
            background: #0b0805;
            user-select: none;
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
        }

        .quran-app-gate-caption {
            position: absolute;
            left: 50%;
            top: clamp(0.85rem, 2.2vw, 1.5rem);
            transform: translateX(-50%);
            z-index: 250;
            pointer-events: none;
            border-radius: 999px;
            border: 1px solid rgba(253, 232, 171, 0.42);
            padding: 0.36rem 1.05rem;
            background: linear-gradient(160deg,
                    rgba(24, 14, 7, 0.68) 0%,
                    rgba(11, 6, 3, 0.54) 100%);
            color: rgba(255, 245, 208, 0.96);
            font-family: 'Readex Pro', 'IBM Plex Sans Arabic', ui-sans-serif, system-ui, sans-serif;
            font-size: clamp(0.72rem, 1.45vw, 0.9rem);
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
        }

        .quran-app-sector.is-locked {
            cursor: default;
        }

        .quran-app-sector--tilawa {
            clip-path: polygon(0 0, 100% 0, var(--gate-cx) var(--gate-cy));
        }

        .quran-app-sector--tadabbur {
            clip-path: polygon(0 0, var(--gate-cx) var(--gate-cy), calc(50% + 0.6px) 100%, 0 100%);
        }

        .quran-app-sector--hifth {
            clip-path: polygon(100% 0, var(--gate-cx) var(--gate-cy), calc(50% - 0.6px) 100%, 100% 100%);
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

        .quran-app-sector__veil {
            position: absolute;
            inset: 0;
            z-index: 2;
            background: linear-gradient(170deg,
                    color-mix(in srgb, var(--gray-950) 24%, transparent),
                    color-mix(in srgb, var(--gray-950) 70%, transparent));
            opacity: 0.28;
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
                transform 420ms ease,
                filter 320ms ease,
                opacity var(--tw-duration, 500ms) var(--tw-ease, ease);
            pointer-events: none;
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
            filter: blur(1.55px) brightness(0.56) saturate(0.82);
        }

        .quran-app-sector.is-active .quran-app-sector__veil {
            opacity: 0.08;
        }

        .quran-app-sector__chip {
            position: absolute;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            overflow: visible;
            min-width: clamp(5.6rem, 11vw, 8rem);
            border-radius: 999px;
            border: 1px solid rgba(253, 232, 171, 0.52);
            background: linear-gradient(165deg,
                    rgba(73, 47, 17, 0.52) 0%,
                    rgba(26, 15, 6, 0.38) 48%,
                    rgba(12, 7, 3, 0.56) 100%);
            padding: clamp(0.42rem, 1vw, 0.66rem) clamp(0.96rem, 2.4vw, 1.6rem);
            color: rgba(255, 249, 225, 0.96);
            font-size: clamp(1.5rem, 3.2vw, 2.4rem);
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
            top: 22%;
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
            gap: 0.46rem;
            padding: 0.38rem 0.72rem;
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

        .quran-app-sector__chip-lock-icon {
            width: clamp(1rem, 2vw, 1.7rem);
            height: clamp(1rem, 2vw, 1.7rem);
            color: color-mix(in srgb, var(--quran-gold-1) 92%, white);
            filter: drop-shadow(0 3px 10px rgba(0, 0, 0, 0.44));
        }

        .quran-app-sector__chip-lock-caption {
            color: color-mix(in srgb, var(--quran-gold-1) 90%, white);
            font-size: 1.38rem;
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

        .quran-app-sector.is-active.is-locked .quran-app-sector__chip-text {
            transform: translateY(0.3rem);
            letter-spacing: 0.046em;
            filter: blur(1.8px);
            opacity: 0.48;
        }

        @media (min-width: 1024px) and (max-width: 1279px) {
            .quran-app-sector__chip {
                font-size: clamp(1.28rem, 2.7vw, 2.05rem);
            }

            .quran-app-sector__chip-lock-icon {
                width: clamp(0.92rem, 1.45vw, 1.3rem);
                height: clamp(0.92rem, 1.45vw, 1.3rem);
            }

            .quran-app-sector__chip-lock-caption {
                font-size: 1.16rem;
            }
        }

        .quran-app-gate-geometry {
            position: absolute;
            inset: 0;
            z-index: 190;
            pointer-events: none;
        }

        .quran-app-gate-focal-dim {
            position: absolute;
            left: var(--gate-cx);
            top: var(--gate-cy);
            width: clamp(34rem, 120vw, 1040rem);
            height: clamp(34rem, 120vw, 1040rem);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            background: radial-gradient(circle, rgba(0, 0, 0, 0) 10%, rgba(0, 0, 0, 0.5) 50%, rgba(2, 1, 0, 1) 100%);
            pointer-events: none;
            z-index: 50;
            mix-blend-mode: multiply;
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
                --gate-cy: 54%;
            }

            .quran-app-gate-caption {
                top: 0.66rem;
                padding: 0.3rem 0.8rem;
                font-size: 0.66rem;
            }

            .quran-app-sector__veil {
                opacity: 0.34;
                transition: opacity 200ms ease;
            }

            .quran-app-sector.is-active .quran-app-sector__veil {
                opacity: 0.16;
            }

            img.quran-app-sector__image-img {
                transform: scale(1.045);
                filter: blur(0.52px) brightness(0.72) saturate(0.9);
                transition:
                    transform 240ms ease,
                    filter 220ms ease,
                    opacity var(--tw-duration, 500ms) var(--tw-ease, ease);
            }

            .quran-app-sector.is-muted img.quran-app-sector__image-img {
                filter: blur(1.2px) brightness(0.62) saturate(0.82);
            }

            .quran-app-sector__chip {
                min-width: clamp(4.3rem, 20vw, 6rem);
                padding: 0.35rem 0.9rem;
                font-size: clamp(1.12rem, 5vw, 1.52rem);
            }

            .quran-app-sector__chip--tilawa {
                top: 20%;
            }

            .quran-app-sector__chip--tadabbur,
            .quran-app-sector__chip--hifth {
                top: 72%;
            }

            .quran-app-sector__chip-lock {
                gap: 0.3rem;
                padding: 0.28rem 0.52rem;
            }

            .quran-app-sector__chip-lock-icon {
                width: 0.86rem;
                height: 0.86rem;
            }

            .quran-app-sector__chip-lock-caption {
                font-size: 0.6rem;
            }

            .quran-app-gate-geometry path {
                stroke-width: 0.34;
            }

            .quran-app-gate-anchor {
                width: clamp(7rem, 18vw, 9rem);
                border-width: 1px;
                box-shadow:
                    inset 0 0 0 1px color-mix(in srgb, var(--quran-gold-1) 26%, transparent),
                    0 0 14px color-mix(in srgb, var(--quran-gold-3) 26%, transparent);
            }

            .quran-app-gate-anchor::before {
                inset: 0.38rem;
                opacity: 0.66;
            }

            .quran-app-gate-anchor::after {
                inset: 0.66rem;
                opacity: 0.45;
            }

            .quran-app-gate-core {
                width: clamp(1.55rem, 4vw, 2rem);
                box-shadow:
                    0 0 12px color-mix(in srgb, var(--quran-gold-2) 36%, transparent),
                    0 0 24px color-mix(in srgb, var(--quran-gold-4) 19%, transparent);
            }

            .quran-app-gate-pointer {
                width: 1.26rem;
                height: 1.26rem;
                box-shadow:
                    0 0 0 3px color-mix(in srgb, var(--quran-gold-2) 16%, transparent),
                    0 0 9px color-mix(in srgb, var(--quran-gold-3) 26%, transparent);
            }

            .quran-app-gate-pointer::before {
                /* right: -0.2rem; */
                width: 0.38rem;
                height: 0.38rem;
            }

            .quran-app-gate-pointer::after {
                inset: 0.22rem;
            }

            .quran-app-gate-orbit {
                transition-duration: 0ms;
            }
        }
    </style>
@endassets

<div
    class="absolute inset-0 z-10"
    x-cloak
    x-show="views['quran-app-gate'].isOpen"
    x-transition:enter="transition-all ease-out duration-750 delay-300"
    x-transition:enter-start="opacity-0! blur-[2px]"
    x-transition:enter-end="opacity-100 blur-0"
    x-transition:leave="transition-all ease-in duration-350!"
    x-transition:leave-start="opacity-100 blur-0"
    x-transition:leave-end="opacity-0! blur-[2px]"
>
    <section
        class="quran-app-gate-shell relative h-full w-full"
        x-data="quranAppGate"
        x-ref="shell"
        x-on:pointerdown="handlePointerDown($event)"
        x-on:pointerup="handlePointerUp($event)"
        x-on:pointercancel="handlePointerUp($event)"
        x-on:pointerenter="handlePointerEnter()"
        x-on:pointerleave="handlePointerLeave()"
        x-on:pointermove.passive="handlePointerMove($event)"
    >
        <p class="quran-app-gate-caption">اختر نمط القراءة الذي يناسب مقصدك</p>

        <button
            class="quran-app-sector quran-app-sector--tilawa"
            type="button"
            aria-label="تلاوة القرآن"
            x-bind:class="{
                'is-active': isModeActive('tilawa'),
                'is-muted': currentMode() && !isModeActive('tilawa'),
                'is-locked': isModeLocked('tilawa')
            }"
            x-on:mouseenter="pinMode('tilawa')"
            x-on:mouseleave="unpinMode('tilawa')"
            x-on:focus="pinMode('tilawa')"
            x-on:blur="unpinMode('tilawa')"
            x-on:click="openMode('tilawa')"
        >
            <span class="quran-app-sector__media quran-app-sector__media--tilawa">
                <x-goodmaven::blurred-image
                    class="absolute inset-0"
                    alt="وضع التلاوة"
                    :imagePath="asset('images/background/quran/tilawa.webp')"
                    :thumbnailImagePath="asset('images/background/quran/tilawa-blur-thumbnail.webp')"
                    :isEagerLoaded="true"
                    :isDisplayEnforced="true"
                    :isObjectCentered="false"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img quran-app-sector__image-img--tilawa select-none"
                />
            </span>

            <span class="quran-app-sector__veil"></span>
            <span class="quran-app-sector__chip quran-app-sector__chip--tilawa font-arabic-serif">
                <span class="quran-app-sector__chip-text">تلاوة</span>
            </span>
        </button>

        <button
            class="quran-app-sector quran-app-sector--tadabbur"
            type="button"
            aria-label="تدبّر القرآن"
            x-bind:aria-disabled="isModeLocked('tadabbur') ? 'true' : 'false'"
            x-bind:class="{
                'is-active': isModeActive('tadabbur'),
                'is-muted': currentMode() && !isModeActive('tadabbur'),
                'is-locked': isModeLocked('tadabbur')
            }"
            x-on:mouseenter="pinMode('tadabbur')"
            x-on:mouseleave="unpinMode('tadabbur')"
            x-on:focus="pinMode('tadabbur')"
            x-on:blur="unpinMode('tadabbur')"
            x-on:click="openMode('tadabbur')"
        >
            <span class="quran-app-sector__media quran-app-sector__media--tadabbur">
                <x-goodmaven::blurred-image
                    class="absolute inset-0"
                    alt="وضع التدبّر"
                    :imagePath="asset('images/background/quran/tadabbur.webp')"
                    :thumbnailImagePath="asset('images/background/quran/tadabbur-blur-thumbnail.webp')"
                    :isEagerLoaded="true"
                    :isDisplayEnforced="true"
                    :isObjectCentered="false"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img quran-app-sector__image-img--tadabbur select-none"
                />
            </span>

            <span class="quran-app-sector__veil"></span>
            <span class="quran-app-sector__chip quran-app-sector__chip--tadabbur font-arabic-serif">
                <span class="quran-app-sector__chip-text">تدبّر</span>
                <span class="quran-app-sector__chip-lock">
                    <x-icon
                        class="quran-app-sector__chip-lock-icon"
                        :name="'heroicon-o-lock-closed'"
                    />
                    <span class="quran-app-sector__chip-lock-caption">قريبًا</span>
                </span>
            </span>
        </button>

        <button
            class="quran-app-sector quran-app-sector--hifth"
            type="button"
            aria-label="حفظ القرآن"
            x-bind:aria-disabled="isModeLocked('hifth') ? 'true' : 'false'"
            x-bind:class="{
                'is-active': isModeActive('hifth'),
                'is-muted': currentMode() && !isModeActive('hifth'),
                'is-locked': isModeLocked('hifth')
            }"
            x-on:mouseenter="pinMode('hifth')"
            x-on:mouseleave="unpinMode('hifth')"
            x-on:focus="pinMode('hifth')"
            x-on:blur="unpinMode('hifth')"
            x-on:click="openMode('hifth')"
        >
            <span class="quran-app-sector__media quran-app-sector__media--hifth">
                <x-goodmaven::blurred-image
                    class="absolute inset-0"
                    alt="وضع الحفظ"
                    :imagePath="asset('images/background/quran/hifth.webp')"
                    :thumbnailImagePath="asset('images/background/quran/hifth-blur-thumbnail.webp')"
                    :isEagerLoaded="true"
                    :isDisplayEnforced="true"
                    :isObjectCentered="false"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img quran-app-sector__image-img--hifth select-none"
                />
            </span>

            <span class="quran-app-sector__veil"></span>
            <span class="quran-app-sector__chip quran-app-sector__chip--hifth font-arabic-serif">
                <span class="quran-app-sector__chip-text">حفظ</span>
                <span class="quran-app-sector__chip-lock">
                    <x-icon
                        class="quran-app-sector__chip-lock-icon"
                        :name="'heroicon-o-lock-closed'"
                    />
                    <span class="quran-app-sector__chip-lock-caption">قريبًا</span>
                </span>
            </span>
        </button>

        <div
            class="quran-app-gate-focal-dim"
            aria-hidden="true"
            x-bind:style="focalDimStyle()"
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
