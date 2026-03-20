@assets
    <style>
        .quran-app-gate-shell {
            isolation: isolate;
            user-select: none;
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
        }

        .quran-app-mode {
            position: absolute;
            display: block;
            overflow: hidden;
            border-radius: 1rem;
            border: 1px solid color-mix(in srgb, var(--primary-500) 32%, transparent);
            background: color-mix(in srgb, var(--background-dark) 58%, transparent);
            box-shadow:
                0 18px 42px color-mix(in srgb, var(--gray-950) 24%, transparent),
                inset 0 1px 0 color-mix(in srgb, white 28%, transparent);
            transition:
                transform 420ms cubic-bezier(0.16, 0.84, 0.44, 1),
                border-color 350ms ease,
                box-shadow 350ms ease,
                filter 350ms ease;
            z-index: 20;
        }

        .quran-app-mode--tilawa {
            top: 3%;
            left: 50%;
            width: min(35%, 25rem);
            height: min(31%, 15rem);
            transform: translateX(-50%);
        }

        .quran-app-mode--tadabbur {
            bottom: 2%;
            left: 4%;
            width: min(36%, 25rem);
            height: min(32%, 16rem);
        }

        .quran-app-mode--hifth {
            right: 4%;
            bottom: 2%;
            width: min(36%, 25rem);
            height: min(32%, 16rem);
        }

        .quran-app-mode__overlay {
            position: absolute;
            inset: 0;
            z-index: 30;
            background: linear-gradient(170deg,
                    color-mix(in srgb, var(--gray-950) 20%, transparent),
                    color-mix(in srgb, var(--gray-950) 66%, transparent));
            transition: opacity 350ms ease;
            pointer-events: none;
            opacity: 0.9;
        }

        .dark .quran-app-mode__overlay {
            background: linear-gradient(170deg,
                    color-mix(in srgb, var(--gray-950) 45%, transparent),
                    color-mix(in srgb, var(--gray-950) 74%, transparent));
        }

        img.quran-app-mode__image-img {
            height: 100%;
            width: 100%;
            object-fit: cover;
            transform: scale(1.08);
            filter: blur(3.2px) brightness(0.72) saturate(0.8);
            transition: transform 560ms ease, filter 420ms ease;
            pointer-events: none;
        }

        .quran-app-mode.is-active {
            border-color: color-mix(in srgb, var(--primary-300) 68%, transparent);
            box-shadow:
                0 20px 46px color-mix(in srgb, var(--gray-950) 28%, transparent),
                0 0 0 1px color-mix(in srgb, var(--primary-300) 32%, transparent),
                inset 0 1px 0 color-mix(in srgb, white 36%, transparent);
        }

        .quran-app-mode.is-active .quran-app-mode__overlay {
            opacity: 0.28;
        }

        .quran-app-mode.is-active img.quran-app-mode__image-img {
            transform: scale(1.03);
            filter: blur(0) brightness(0.98) saturate(1.05);
        }

        .quran-app-mode__label {
            position: absolute;
            z-index: 40;
            right: 0.8rem;
            bottom: 0.7rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, white 32%, transparent);
            background: color-mix(in srgb, var(--gray-950) 50%, transparent);
            padding: 0.3rem 0.9rem;
            color: color-mix(in srgb, white 90%, var(--primary-100));
            font-weight: 600;
            font-size: 0.95rem;
            line-height: 1.2;
            letter-spacing: 0.01em;
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            text-shadow: 0 5px 12px rgba(2, 6, 23, 0.45);
            pointer-events: none;
        }

        .quran-app-mode__soon {
            position: absolute;
            z-index: 40;
            top: 0.75rem;
            left: 0.75rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--amber-300) 52%, transparent);
            background: color-mix(in srgb, var(--amber-500) 28%, transparent);
            padding: 0.2rem 0.55rem;
            color: color-mix(in srgb, var(--amber-100) 85%, white);
            font-size: 0.72rem;
            font-weight: 700;
            pointer-events: none;
            backdrop-filter: blur(2px);
            -webkit-backdrop-filter: blur(2px);
        }

        .quran-app-gate-board {
            position: absolute;
            top: 51%;
            left: 50%;
            width: 58%;
            height: 48%;
            transform: translate(-50%, -50%);
            pointer-events: none;
            z-index: 25;
        }

        .quran-app-gate-sector {
            position: absolute;
            inset: 0;
            opacity: 0;
            transition: opacity 320ms ease;
        }

        .quran-app-gate-sector--tilawa {
            clip-path: polygon(0 0, 100% 0, 50% 50%);
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--success-400) 42%, transparent),
                    color-mix(in srgb, var(--success-500) 8%, transparent));
        }

        .quran-app-gate-sector--hifth {
            clip-path: polygon(100% 0, 50% 50%, 50% 100%, 100% 100%);
            background: linear-gradient(225deg,
                    color-mix(in srgb, var(--amber-300) 38%, transparent),
                    color-mix(in srgb, var(--amber-500) 12%, transparent));
        }

        .quran-app-gate-sector--tadabbur {
            clip-path: polygon(0 0, 50% 50%, 50% 100%, 0 100%);
            background: linear-gradient(135deg,
                    color-mix(in srgb, var(--sky-300) 36%, transparent),
                    color-mix(in srgb, var(--sky-500) 10%, transparent));
        }

        .quran-app-gate-sector.is-active {
            opacity: 0.32;
        }

        .quran-app-gate-outline {
            height: 100%;
            width: 100%;
            display: block;
            filter: drop-shadow(0 8px 16px color-mix(in srgb, var(--gray-950) 26%, transparent));
        }

        .quran-app-gate-anchor {
            position: absolute;
            left: 50%;
            top: 50%;
            width: clamp(4.8rem, 16%, 7.8rem);
            aspect-ratio: 1;
            transform: translate(-50%, -50%);
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-300) 46%, transparent);
            pointer-events: none;
        }

        .quran-app-gate-puck {
            position: absolute;
            z-index: 80;
            width: 0.88rem;
            aspect-ratio: 1;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, white 72%, transparent);
            background: color-mix(in srgb, var(--primary-200) 75%, transparent);
            box-shadow:
                0 0 0 6px color-mix(in srgb, var(--primary-300) 18%, transparent),
                0 8px 18px color-mix(in srgb, var(--gray-950) 42%, transparent);
            transform: translate(-50%, -50%);
            transition: left 120ms linear, top 120ms linear, opacity 220ms ease;
            pointer-events: none;
        }

        @media (max-width: 639px) {
            .quran-app-mode {
                border-radius: 0.8rem;
            }

            .quran-app-mode__label {
                right: 0.55rem;
                bottom: 0.5rem;
                padding: 0.22rem 0.66rem;
                font-size: 0.76rem;
            }

            .quran-app-mode__soon {
                top: 0.5rem;
                left: 0.5rem;
                font-size: 0.61rem;
            }

            .quran-app-gate-board {
                width: 66%;
                height: 44%;
                top: 52%;
            }

            .quran-app-mode--tilawa {
                width: 48%;
                height: 28%;
            }

            .quran-app-mode--hifth,
            .quran-app-mode--tadabbur {
                width: 46%;
                height: 30%;
                bottom: 3%;
            }

            .quran-app-mode--tadabbur {
                left: 2%;
            }

            .quran-app-mode--hifth {
                right: 2%;
            }
        }
    </style>
@endassets

<div
    class="absolute inset-0 z-10 grid place-items-center px-4 py-5 sm:px-6 sm:py-8"
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
        class="quran-app-gate-shell relative h-[min(92vw,760px)] max-h-[79svh] w-[min(96vw,1020px)] max-w-5xl"
        x-data="quranAppGate"
        x-ref="shell"
        x-on:pointerenter="handlePointerEnter()"
        x-on:pointerleave="handlePointerLeave()"
        x-on:pointermove.passive="handlePointerMove($event)"
    >
        <button
            class="quran-app-mode quran-app-mode--tilawa"
            type="button"
            aria-label="تلاوة القرآن"
            x-bind:class="{ 'is-active': isModeActive('tilawa') }"
            x-on:mouseenter="pinMode('tilawa')"
            x-on:mouseleave="unpinMode('tilawa')"
            x-on:focus="pinMode('tilawa')"
            x-on:blur="unpinMode('tilawa')"
            x-on:click="openMode('tilawa')"
        >
            <x-goodmaven::blurred-image
                alt="وضع التلاوة"
                :imagePath="asset('images/background/quran/tilawa.webp')"
                :thumbnailImagePath="asset('images/background/quran/tilawa-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                containerClasses="overflow-hidden bg-black/20"
                imageClasses="quran-app-mode__image-img select-none"
            />

            <span class="quran-app-mode__overlay"></span>
            <span class="quran-app-mode__label font-arabic-serif">تلاوة</span>
        </button>

        <button
            class="quran-app-mode quran-app-mode--tadabbur"
            type="button"
            aria-label="تدبّر القرآن"
            x-bind:class="{ 'is-active': isModeActive('tadabbur') }"
            x-on:mouseenter="pinMode('tadabbur')"
            x-on:mouseleave="unpinMode('tadabbur')"
            x-on:focus="pinMode('tadabbur')"
            x-on:blur="unpinMode('tadabbur')"
            x-on:click="openMode('tadabbur')"
        >
            <x-goodmaven::blurred-image
                alt="وضع التدبّر"
                :imagePath="asset('images/background/quran/tadabbur.webp')"
                :thumbnailImagePath="asset('images/background/quran/tadabbur-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                containerClasses="overflow-hidden bg-black/20"
                imageClasses="quran-app-mode__image-img select-none"
            />

            <span class="quran-app-mode__overlay"></span>
            <span class="quran-app-mode__soon">قريبًا</span>
            <span class="quran-app-mode__label font-arabic-serif">تدبّر</span>
        </button>

        <button
            class="quran-app-mode quran-app-mode--hifth"
            type="button"
            aria-label="حفظ القرآن"
            x-bind:class="{ 'is-active': isModeActive('hifth') }"
            x-on:mouseenter="pinMode('hifth')"
            x-on:mouseleave="unpinMode('hifth')"
            x-on:focus="pinMode('hifth')"
            x-on:blur="unpinMode('hifth')"
            x-on:click="openMode('hifth')"
        >
            <x-goodmaven::blurred-image
                alt="وضع الحفظ"
                :imagePath="asset('images/background/quran/hifth.webp')"
                :thumbnailImagePath="asset('images/background/quran/hifth-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                containerClasses="overflow-hidden bg-black/20"
                imageClasses="quran-app-mode__image-img select-none"
            />

            <span class="quran-app-mode__overlay"></span>
            <span class="quran-app-mode__soon">قريبًا</span>
            <span class="quran-app-mode__label font-arabic-serif">حفظ</span>
        </button>

        <div
            class="quran-app-gate-board"
            aria-hidden="true"
            x-ref="board"
        >
            <span
                class="quran-app-gate-sector quran-app-gate-sector--tilawa"
                x-bind:class="{ 'is-active': isModeActive('tilawa') }"
            ></span>
            <span
                class="quran-app-gate-sector quran-app-gate-sector--tadabbur"
                x-bind:class="{ 'is-active': isModeActive('tadabbur') }"
            ></span>
            <span
                class="quran-app-gate-sector quran-app-gate-sector--hifth"
                x-bind:class="{ 'is-active': isModeActive('hifth') }"
            ></span>

            <svg
                class="quran-app-gate-outline"
                viewBox="0 0 100 66"
                preserveAspectRatio="none"
                fill="none"
                xmlns="http://www.w3.org/2000/svg"
            >
                <rect
                    x="0.6"
                    y="0.6"
                    width="98.8"
                    height="64.8"
                    rx="1"
                    stroke="color-mix(in srgb, var(--rose-300) 58%, transparent)"
                    stroke-width="1.2"
                />
                <path
                    d="M0.6 0.6 L50 33"
                    stroke="color-mix(in srgb, var(--rose-300) 54%, transparent)"
                    stroke-width="1"
                    stroke-linecap="round"
                />
                <path
                    d="M99.4 0.6 L50 33"
                    stroke="color-mix(in srgb, var(--rose-300) 54%, transparent)"
                    stroke-width="1"
                    stroke-linecap="round"
                />
                <path
                    d="M50 33 L50 65.4"
                    stroke="color-mix(in srgb, var(--rose-300) 54%, transparent)"
                    stroke-width="1"
                    stroke-linecap="round"
                />
                <circle
                    cx="50"
                    cy="33"
                    r="18"
                    stroke="color-mix(in srgb, var(--rose-300) 60%, transparent)"
                    stroke-width="1.15"
                />
            </svg>

            <div
                class="quran-app-gate-anchor"
                x-ref="anchorCircle"
            ></div>
            <div
                class="quran-app-gate-puck"
                x-cloak
                x-show="isPointerInside || isModePinned"
                x-bind:style="{ left: `${puckX}%`, top: `${puckY}%` }"
            ></div>
        </div>
    </section>
</div>
