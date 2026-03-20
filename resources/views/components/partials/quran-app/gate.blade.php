@assets
    <style>
        .quran-app-gate-shell {
            --gate-cx: 50%;
            --gate-cy: 53%;
            isolation: isolate;
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
            clip-path: polygon(0 0, var(--gate-cx) var(--gate-cy), 50% 100%, 0 100%);
        }

        .quran-app-sector--hifth {
            clip-path: polygon(100% 0, var(--gate-cx) var(--gate-cy), 50% 100%, 100% 100%);
        }

        .quran-app-sector__media {
            position: absolute;
            inset: 0;
            z-index: 1;
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
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, white 36%, transparent);
            background: color-mix(in srgb, var(--gray-950) 60%, transparent);
            padding: 0.22rem 0.76rem;
            color: color-mix(in srgb, white 90%, var(--primary-100));
            font-size: 0.82rem;
            font-weight: 700;
            line-height: 1.2;
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            pointer-events: none;
        }

        .quran-app-sector__chip--tilawa {
            right: 50%;
            top: clamp(7.8rem, 20vh, 12.4rem);
            transform: translateX(7.6rem);
        }

        .quran-app-sector__chip--tadabbur {
            left: clamp(0.9rem, 7vw, 4.8rem);
            bottom: clamp(4.8rem, 10vh, 7.2rem);
        }

        .quran-app-sector__chip--hifth {
            right: clamp(0.9rem, 7vw, 4.8rem);
            bottom: clamp(4.8rem, 10vh, 7.2rem);
        }

        .quran-app-sector__soon {
            position: absolute;
            z-index: 6;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--amber-300) 52%, transparent);
            background: color-mix(in srgb, var(--amber-500) 28%, transparent);
            padding: 0.16rem 0.44rem;
            color: color-mix(in srgb, var(--amber-100) 88%, white);
            font-size: 0.64rem;
            font-weight: 700;
            line-height: 1;
            pointer-events: none;
        }

        .quran-app-sector__soon--tadabbur {
            left: clamp(0.9rem, 7vw, 4.8rem);
            bottom: clamp(7.1rem, 14vh, 10.4rem);
        }

        .quran-app-sector__soon--hifth {
            right: clamp(0.9rem, 7vw, 4.8rem);
            bottom: clamp(7.1rem, 14vh, 10.4rem);
        }

        .quran-app-gate-geometry {
            position: absolute;
            inset: 0;
            z-index: 85;
            pointer-events: none;
        }

        .quran-app-gate-geometry path {
            stroke: color-mix(in srgb, var(--rose-300) 60%, transparent);
            stroke-width: 0.44;
            stroke-linecap: round;
            filter: drop-shadow(0 2px 5px color-mix(in srgb, var(--rose-500) 18%, transparent));
        }

        .quran-app-gate-anchor {
            position: absolute;
            left: var(--gate-cx);
            top: var(--gate-cy);
            width: clamp(8.4rem, 16vw, 12rem);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            border: 2px solid color-mix(in srgb, var(--amber-300) 74%, transparent);
            background: radial-gradient(circle,
                    color-mix(in srgb, var(--amber-200) 26%, transparent) 0,
                    transparent 74%);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--amber-50) 42%, transparent),
                0 0 0 1px color-mix(in srgb, var(--amber-500) 34%, transparent),
                0 0 26px color-mix(in srgb, var(--amber-400) 34%, transparent);
            pointer-events: none;
            z-index: 120;
        }

        .quran-app-gate-core {
            position: absolute;
            left: 50%;
            top: 50%;
            width: clamp(2rem, 4.2vw, 2.8rem);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            background: radial-gradient(circle,
                    color-mix(in srgb, white 95%, var(--amber-100)) 0,
                    color-mix(in srgb, var(--amber-100) 98%, var(--amber-300)) 38%,
                    color-mix(in srgb, var(--amber-500) 95%, transparent) 76%);
            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--amber-100) 40%, transparent),
                0 0 24px color-mix(in srgb, var(--amber-300) 64%, transparent),
                0 0 46px color-mix(in srgb, var(--amber-500) 38%, transparent);
            opacity: 0.95;
        }

        .quran-app-gate-puck {
            position: absolute;
            width: 1.18rem;
            aspect-ratio: 1;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--amber-100) 74%, transparent);
            background: radial-gradient(circle,
                    color-mix(in srgb, white 95%, var(--amber-100)),
                    color-mix(in srgb, var(--amber-300) 84%, transparent));
            box-shadow:
                0 0 0 7px color-mix(in srgb, var(--amber-300) 18%, transparent),
                0 0 24px color-mix(in srgb, var(--amber-400) 38%, transparent);
            transform: translate(-50%, -50%);
            transition: left 120ms linear, top 120ms linear, opacity 220ms ease;
            pointer-events: none;
            z-index: 135;
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
                padding: 0.17rem 0.54rem;
                font-size: 0.66rem;
            }

            .quran-app-sector__chip--tilawa {
                top: clamp(7rem, 18vh, 10.4rem);
                transform: translateX(5.5rem);
            }

            .quran-app-sector__chip--tadabbur,
            .quran-app-sector__chip--hifth {
                bottom: clamp(4.1rem, 9vh, 5.8rem);
            }

            .quran-app-sector__soon {
                font-size: 0.57rem;
            }

            .quran-app-sector__soon--tadabbur,
            .quran-app-sector__soon--hifth {
                bottom: clamp(5.9rem, 12vh, 8.1rem);
            }

            .quran-app-gate-geometry path {
                stroke-width: 0.34;
            }

            .quran-app-gate-anchor {
                width: clamp(7rem, 18vw, 9rem);
                border-width: 1px;
                box-shadow:
                    inset 0 0 0 1px color-mix(in srgb, var(--amber-50) 22%, transparent),
                    0 0 14px color-mix(in srgb, var(--amber-400) 24%, transparent);
            }

            .quran-app-gate-core {
                width: clamp(1.55rem, 4vw, 2rem);
                box-shadow:
                    0 0 12px color-mix(in srgb, var(--amber-300) 34%, transparent),
                    0 0 24px color-mix(in srgb, var(--amber-500) 17%, transparent);
            }

            .quran-app-gate-puck {
                width: 0.84rem;
                box-shadow:
                    0 0 0 3px color-mix(in srgb, var(--amber-300) 16%, transparent),
                    0 0 9px color-mix(in srgb, var(--amber-400) 24%, transparent);
            }
        }
    </style>
@endassets

<div
    class="absolute inset-0 z-10"
    x-cloak
    x-show="views['quran-app-gate'].isOpen"
    x-transition:enter="transition-all ease-out duration-750 delay-300"
    x-transition:enter-start="opacity-0! translate-y-5 blur-[2px]"
    x-transition:enter-end="opacity-100 translate-y-0 blur-0"
    x-transition:leave="transition-all ease-in duration-350!"
    x-transition:leave-start="opacity-100 translate-y-0 blur-0"
    x-transition:leave-end="opacity-0! blur-[2px]"
>
    <section
        class="quran-app-gate-shell relative h-full w-full"
        x-data="quranAppGate"
        x-ref="shell"
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
            <span class="quran-app-sector__media">
                <x-goodmaven::blurred-image
                    class="absolute inset-0"
                    alt="وضع التلاوة"
                    :imagePath="asset('images/background/quran/tilawa.webp')"
                    :thumbnailImagePath="asset('images/background/quran/tilawa-blur-thumbnail.webp')"
                    :isDisplayEnforced="true"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img select-none"
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
            <span class="quran-app-sector__media">
                <x-goodmaven::blurred-image
                    class="absolute inset-0"
                    alt="وضع التدبّر"
                    :imagePath="asset('images/background/quran/tadabbur.webp')"
                    :thumbnailImagePath="asset('images/background/quran/tadabbur-blur-thumbnail.webp')"
                    :isDisplayEnforced="true"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img select-none"
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
            <span class="quran-app-sector__media">
                <x-goodmaven::blurred-image
                    class="absolute inset-0"
                    alt="وضع الحفظ"
                    :imagePath="asset('images/background/quran/hifth.webp')"
                    :thumbnailImagePath="asset('images/background/quran/hifth-blur-thumbnail.webp')"
                    :isDisplayEnforced="true"
                    containerClasses="absolute inset-0 overflow-hidden bg-black/25"
                    imageClasses="quran-app-sector__image-img select-none"
                />
            </span>

            <span class="quran-app-sector__veil"></span>
            <span class="quran-app-sector__soon quran-app-sector__soon--hifth">قريبًا</span>
            <span class="quran-app-sector__chip quran-app-sector__chip--hifth font-arabic-serif">حفظ</span>
        </button>

        <svg
            class="quran-app-gate-geometry"
            aria-hidden="true"
            viewBox="0 0 100 100"
            preserveAspectRatio="none"
            fill="none"
        >
            <path d="M0 0 L50 53" />
            <path d="M100 0 L50 53" />
            <path d="M50 53 L50 100" />
        </svg>

        <div
            class="quran-app-gate-anchor"
            aria-hidden="true"
            x-ref="anchorCircle"
        >
            <span class="quran-app-gate-core"></span>
        </div>

        <div
            class="quran-app-gate-puck"
            x-cloak
            x-show="isPointerInside || isModePinned"
            x-bind:style="{ left: `${puckX}%`, top: `${puckY}%` }"
        ></div>
    </section>
</div>
