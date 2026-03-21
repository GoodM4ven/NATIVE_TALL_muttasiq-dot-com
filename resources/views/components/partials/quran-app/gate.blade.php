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
        }

        .quran-app-sector__media--tilawa {
            inset: 0 0 calc(100% - var(--gate-cy)) 0;
        }

        .quran-app-sector__media--tadabbur {
            inset: 0 calc(100% - var(--gate-cx)) 0 0;
        }

        .quran-app-sector__media--hifth {
            inset: 0 0 0 var(--gate-cx);
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
            filter: blur(3.4px) brightness(0.62) saturate(0.84);
            transition: transform 420ms ease, filter 320ms ease;
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

        .quran-app-sector.is-active .quran-app-sector__veil {
            opacity: 0.08;
        }

        .quran-app-sector__chip {
            position: absolute;
            z-index: 5;
            display: inline-flex;
            align-items: center;
            justify-content: center;
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

        .quran-app-sector__soon {
            position: absolute;
            z-index: 6;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--quran-gold-2) 72%, transparent);
            background: color-mix(in srgb, var(--quran-gold-4) 46%, transparent);
            padding: 0.16rem 0.44rem;
            color: color-mix(in srgb, var(--quran-gold-1) 90%, white);
            font-size: 0.64rem;
            font-weight: 700;
            line-height: 1;
            pointer-events: none;
        }

        .quran-app-sector__soon--tadabbur {
            left: 26%;
            top: 78%;
            transform: translate(-50%, -50%);
        }

        .quran-app-sector__soon--hifth {
            left: 74%;
            top: 78%;
            transform: translate(-50%, -50%);
        }

        .quran-app-gate-geometry {
            position: absolute;
            inset: 0;
            z-index: 190;
            pointer-events: none;
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
            transition: transform 300ms ease-out, opacity 220ms ease;
            pointer-events: none;
            z-index: 230;
        }

        .quran-app-gate-puck {
            position: absolute;
            left: 50%;
            top: 0;
            width: 3.25rem;
            aspect-ratio: 1;
            border-radius: 999px;
            border: 1px solid rgba(253, 232, 171, 0.9);
            background: radial-gradient(circle,
                    rgba(255, 252, 236, 1),
                    rgba(215, 159, 47, 0.86));
            box-shadow:
                0 0 0 7px rgba(239, 200, 107, 0.3),
                0 0 24px rgba(215, 159, 47, 0.58);
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 230;
        }

        @keyframes quran-app-gate-spin {
            from {
                transform: rotate(0deg);
            }

            to {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 639px) {
            .quran-app-gate-shell {
                --gate-cy: 54%;
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
                filter: blur(2.2px) brightness(0.7) saturate(0.86);
                transition: transform 240ms ease, filter 220ms ease;
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

            .quran-app-sector__soon {
                font-size: 0.57rem;
            }

            .quran-app-sector__soon--tadabbur,
            .quran-app-sector__soon--hifth {
                top: 77%;
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

            .quran-app-gate-puck {
                width: 0.92rem;
                box-shadow:
                    0 0 0 3px color-mix(in srgb, var(--quran-gold-2) 16%, transparent),
                    0 0 9px color-mix(in srgb, var(--quran-gold-3) 26%, transparent);
            }

            .quran-app-gate-orbit {
                transition-duration: 440ms;
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
        <button
            class="quran-app-sector quran-app-sector--tilawa"
            type="button"
            aria-label="تلاوة القرآن"
            x-bind:class="{ 'is-active': isModeActive('tilawa') }"
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
                    :isDisplayEnforced="true"
                    :isObjectCentered="false"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img quran-app-sector__image-img--tilawa select-none"
                />
            </span>

            <span class="quran-app-sector__veil"></span>
            <span class="quran-app-sector__chip quran-app-sector__chip--tilawa font-arabic-serif">تلاوة</span>
        </button>

        <button
            class="quran-app-sector quran-app-sector--tadabbur"
            type="button"
            aria-label="تدبّر القرآن"
            x-bind:class="{ 'is-active': isModeActive('tadabbur') }"
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
                    :isDisplayEnforced="true"
                    :isObjectCentered="false"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img quran-app-sector__image-img--tadabbur select-none"
                />
            </span>

            <span class="quran-app-sector__veil"></span>
            <span class="quran-app-sector__soon quran-app-sector__soon--tadabbur">قريبًا</span>
            <span class="quran-app-sector__chip quran-app-sector__chip--tadabbur font-arabic-serif">تدبّر</span>
        </button>

        <button
            class="quran-app-sector quran-app-sector--hifth"
            type="button"
            aria-label="حفظ القرآن"
            x-bind:class="{ 'is-active': isModeActive('hifth') }"
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
                    :isDisplayEnforced="true"
                    :isObjectCentered="false"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img quran-app-sector__image-img--hifth select-none"
                />
            </span>

            <span class="quran-app-sector__veil"></span>
            <span class="quran-app-sector__soon quran-app-sector__soon--hifth">قريبًا</span>
            <span class="quran-app-sector__chip quran-app-sector__chip--hifth font-arabic-serif">حفظ</span>
        </button>

        <div
            class="quran-app-gate-anchor"
            aria-hidden="true"
            x-ref="anchorCircle"
        >
            <span
                class="quran-app-gate-orbit"
                x-bind:style="{ transform: `rotate(${orbitAngleDeg}deg)` }"
            >
                <span class="quran-app-gate-puck"></span>
            </span>
            <span class="quran-app-gate-core"></span>
        </div>
    </section>
</div>
