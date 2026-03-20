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

        .quran-app-sector__wash,
        .quran-app-sector__streaks {
            position: absolute;
            inset: 0;
            pointer-events: none;
            transition: opacity 260ms ease;
        }

        .quran-app-sector__wash {
            opacity: 0.12;
        }

        .quran-app-sector__streaks {
            opacity: 0.1;
        }

        .quran-app-sector--tilawa .quran-app-sector__wash {
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--success-300) 42%, transparent),
                    color-mix(in srgb, var(--success-600) 10%, transparent));
        }

        .quran-app-sector--tadabbur .quran-app-sector__wash {
            background: linear-gradient(145deg,
                    color-mix(in srgb, var(--sky-300) 42%, transparent),
                    color-mix(in srgb, var(--sky-600) 10%, transparent));
        }

        .quran-app-sector--hifth .quran-app-sector__wash {
            background: linear-gradient(215deg,
                    color-mix(in srgb, var(--amber-300) 42%, transparent),
                    color-mix(in srgb, var(--amber-600) 10%, transparent));
        }

        .quran-app-sector__streaks {
            background-image: repeating-linear-gradient(155deg,
                    transparent 0 5.8rem,
                    color-mix(in srgb, var(--rose-300) 22%, transparent) 5.8rem 6.55rem);
        }

        .quran-app-sector.is-active .quran-app-sector__wash {
            opacity: 0.28;
        }

        .quran-app-sector.is-active .quran-app-sector__streaks {
            opacity: 0.2;
        }

        .quran-app-card {
            position: absolute;
            width: min(36vw, 16.6rem);
            height: min(23vw, 10.6rem);
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid color-mix(in srgb, var(--primary-400) 36%, transparent);
            background: color-mix(in srgb, var(--background-dark) 55%, transparent);
            box-shadow:
                0 20px 44px color-mix(in srgb, var(--gray-950) 22%, transparent),
                inset 0 1px 0 color-mix(in srgb, white 30%, transparent);
            transition: border-color 260ms ease, box-shadow 260ms ease, transform 260ms ease;
            z-index: 25;
        }

        .quran-app-card--tilawa {
            left: 50%;
            top: clamp(2rem, 8.2vh, 5.5rem);
            transform: translateX(-50%);
        }

        .quran-app-card--tadabbur {
            left: clamp(0.8rem, 8vw, 6rem);
            bottom: clamp(5rem, 11vh, 8rem);
        }

        .quran-app-card--hifth {
            right: clamp(0.8rem, 8vw, 6rem);
            bottom: clamp(5rem, 11vh, 8rem);
        }

        img.quran-app-card__image-img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transform: scale(1.07);
            filter: blur(3.2px) brightness(0.68) saturate(0.82);
            transition: transform 420ms ease, filter 320ms ease;
            pointer-events: none;
        }

        .quran-app-sector.is-active img.quran-app-card__image-img {
            transform: scale(1.02);
            filter: blur(0) brightness(0.98) saturate(1.04);
        }

        .quran-app-sector.is-active .quran-app-card {
            border-color: color-mix(in srgb, var(--primary-300) 70%, transparent);
            box-shadow:
                0 22px 48px color-mix(in srgb, var(--gray-950) 28%, transparent),
                0 0 0 1px color-mix(in srgb, var(--primary-200) 28%, transparent),
                inset 0 1px 0 color-mix(in srgb, white 38%, transparent);
        }

        .quran-app-card__label {
            position: absolute;
            z-index: 10;
            right: 0.6rem;
            bottom: 0.55rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, white 36%, transparent);
            background: color-mix(in srgb, var(--gray-950) 62%, transparent);
            padding: 0.2rem 0.72rem;
            color: color-mix(in srgb, white 90%, var(--primary-100));
            font-size: 0.84rem;
            font-weight: 700;
            line-height: 1.2;
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            pointer-events: none;
        }

        .quran-app-card__soon {
            position: absolute;
            z-index: 10;
            top: 0.55rem;
            left: 0.55rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--amber-300) 52%, transparent);
            background: color-mix(in srgb, var(--amber-500) 28%, transparent);
            padding: 0.17rem 0.45rem;
            color: color-mix(in srgb, var(--amber-100) 86%, white);
            font-size: 0.67rem;
            font-weight: 700;
            line-height: 1;
            pointer-events: none;
        }

        .quran-app-gate-geometry {
            position: absolute;
            inset: 0;
            z-index: 40;
            pointer-events: none;
        }

        .quran-app-gate-geometry path {
            stroke: color-mix(in srgb, var(--rose-300) 64%, transparent);
            stroke-width: 0.42;
            stroke-linecap: round;
            filter: drop-shadow(0 2px 5px color-mix(in srgb, var(--rose-500) 18%, transparent));
        }

        .quran-app-gate-anchor {
            position: absolute;
            left: var(--gate-cx);
            top: var(--gate-cy);
            width: clamp(7.6rem, 14vw, 10.5rem);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            border: 2px solid color-mix(in srgb, var(--amber-300) 70%, transparent);
            background: radial-gradient(circle,
                    color-mix(in srgb, var(--amber-200) 16%, transparent) 0,
                    transparent 72%);
            box-shadow:
                inset 0 0 0 1px color-mix(in srgb, var(--amber-50) 38%, transparent),
                0 0 0 1px color-mix(in srgb, var(--amber-500) 26%, transparent),
                0 0 24px color-mix(in srgb, var(--amber-400) 30%, transparent);
            pointer-events: none;
            z-index: 60;
        }

        .quran-app-gate-core {
            position: absolute;
            left: 50%;
            top: 50%;
            width: clamp(1.5rem, 3.4vw, 2.3rem);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            background: radial-gradient(circle,
                    color-mix(in srgb, white 92%, var(--amber-100)) 0,
                    color-mix(in srgb, var(--amber-200) 92%, var(--amber-400)) 36%,
                    color-mix(in srgb, var(--amber-500) 90%, transparent) 72%);
            box-shadow:
                0 0 0 1px color-mix(in srgb, var(--amber-100) 35%, transparent),
                0 0 20px color-mix(in srgb, var(--amber-300) 62%, transparent),
                0 0 40px color-mix(in srgb, var(--amber-500) 34%, transparent);
            opacity: 0.92;
        }

        .quran-app-gate-puck {
            position: absolute;
            width: 1.05rem;
            aspect-ratio: 1;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--amber-100) 72%, transparent);
            background: radial-gradient(circle,
                    color-mix(in srgb, white 92%, var(--amber-100)),
                    color-mix(in srgb, var(--amber-300) 82%, transparent));
            box-shadow:
                0 0 0 6px color-mix(in srgb, var(--amber-300) 18%, transparent),
                0 0 22px color-mix(in srgb, var(--amber-400) 34%, transparent);
            transform: translate(-50%, -50%);
            transition: left 120ms linear, top 120ms linear, opacity 220ms ease;
            pointer-events: none;
            z-index: 70;
        }

        @media (max-width: 639px) {
            .quran-app-gate-shell {
                --gate-cy: 54%;
            }

            .quran-app-sector__wash {
                opacity: 0.08;
            }

            .quran-app-sector__streaks {
                opacity: 0.04;
            }

            .quran-app-sector.is-active .quran-app-sector__wash {
                opacity: 0.16;
            }

            .quran-app-sector.is-active .quran-app-sector__streaks {
                opacity: 0.1;
            }

            .quran-app-card {
                width: min(47vw, 12rem);
                height: min(29vw, 7.2rem);
                border-radius: 0.72rem;
                box-shadow: 0 10px 22px color-mix(in srgb, var(--gray-950) 24%, transparent);
                transition: border-color 200ms ease, transform 200ms ease;
            }

            .quran-app-card--tilawa {
                top: clamp(2.5rem, 8.6vh, 4.8rem);
            }

            .quran-app-card--tadabbur {
                left: 1rem;
                bottom: clamp(4.2rem, 9.2vh, 6.1rem);
            }

            .quran-app-card--hifth {
                right: 1rem;
                bottom: clamp(4.2rem, 9.2vh, 6.1rem);
            }

            img.quran-app-card__image-img {
                transform: scale(1.04);
                filter: blur(2.2px) brightness(0.72) saturate(0.86);
                transition: transform 240ms ease, filter 220ms ease;
            }

            .quran-app-card__label {
                right: 0.4rem;
                bottom: 0.35rem;
                padding: 0.17rem 0.52rem;
                font-size: 0.66rem;
            }

            .quran-app-card__soon {
                top: 0.34rem;
                left: 0.34rem;
                font-size: 0.57rem;
            }

            .quran-app-gate-geometry path {
                stroke-width: 0.34;
            }

            .quran-app-gate-anchor {
                width: clamp(6.2rem, 18vw, 8rem);
                border-width: 1px;
                box-shadow:
                    inset 0 0 0 1px color-mix(in srgb, var(--amber-50) 18%, transparent),
                    0 0 12px color-mix(in srgb, var(--amber-400) 24%, transparent);
            }

            .quran-app-gate-core {
                width: clamp(1.2rem, 3.8vw, 1.7rem);
                box-shadow:
                    0 0 10px color-mix(in srgb, var(--amber-300) 32%, transparent),
                    0 0 20px color-mix(in srgb, var(--amber-500) 16%, transparent);
            }

            .quran-app-gate-puck {
                width: 0.78rem;
                box-shadow:
                    0 0 0 3px color-mix(in srgb, var(--amber-300) 16%, transparent),
                    0 0 8px color-mix(in srgb, var(--amber-400) 24%, transparent);
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
            <span class="quran-app-sector__wash"></span>
            <span class="quran-app-sector__streaks"></span>

            <span class="quran-app-card quran-app-card--tilawa">
                <x-goodmaven::blurred-image
                    alt="وضع التلاوة"
                    :imagePath="asset('images/background/quran/tilawa.webp')"
                    :thumbnailImagePath="asset('images/background/quran/tilawa-blur-thumbnail.webp')"
                    :isDisplayEnforced="true"
                    containerClasses="overflow-hidden bg-black/20"
                    imageClasses="quran-app-card__image-img select-none"
                />

                <span class="quran-app-card__label font-arabic-serif">تلاوة</span>
            </span>
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
            <span class="quran-app-sector__wash"></span>
            <span class="quran-app-sector__streaks"></span>

            <span class="quran-app-card quran-app-card--tadabbur">
                <x-goodmaven::blurred-image
                    alt="وضع التدبّر"
                    :imagePath="asset('images/background/quran/tadabbur.webp')"
                    :thumbnailImagePath="asset('images/background/quran/tadabbur-blur-thumbnail.webp')"
                    :isDisplayEnforced="true"
                    containerClasses="overflow-hidden bg-black/20"
                    imageClasses="quran-app-card__image-img select-none"
                />

                <span class="quran-app-card__soon">قريبًا</span>
                <span class="quran-app-card__label font-arabic-serif">تدبّر</span>
            </span>
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
            <span class="quran-app-sector__wash"></span>
            <span class="quran-app-sector__streaks"></span>

            <span class="quran-app-card quran-app-card--hifth">
                <x-goodmaven::blurred-image
                    alt="وضع الحفظ"
                    :imagePath="asset('images/background/quran/hifth.webp')"
                    :thumbnailImagePath="asset('images/background/quran/hifth-blur-thumbnail.webp')"
                    :isDisplayEnforced="true"
                    containerClasses="overflow-hidden bg-black/20"
                    imageClasses="quran-app-card__image-img select-none"
                />

                <span class="quran-app-card__soon">قريبًا</span>
                <span class="quran-app-card__label font-arabic-serif">حفظ</span>
            </span>
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
