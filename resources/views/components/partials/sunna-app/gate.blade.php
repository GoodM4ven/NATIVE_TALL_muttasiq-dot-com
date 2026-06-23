@assets
    <style>
        /* ponytail: lean diamond gate; only transform/opacity/box-shadow move, so the parallax camera and hover stay cheap on mobile WebViews. */
        .sunna-gate-shell {
            --cam-x: 0;
            --cam-y: 0;
            position: relative;
            isolation: isolate;
            overflow: hidden;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
            touch-action: none;
            contain: layout paint;
        }

        .sunna-gate-bg {
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
        }

        .sunna-gate-bg-scrim {
            position: absolute;
            inset: 0;
            z-index: 1;
            pointer-events: none;
            background: radial-gradient(circle farthest-side at 50% 46%,
                    transparent 30%,
                    color-mix(in srgb, var(--primary-950) 10%, transparent) 100%);
        }

        .dark .sunna-gate-bg-scrim {
            background: radial-gradient(circle farthest-side at 50% 46%,
                    transparent 24%,
                    color-mix(in srgb, var(--primary-950) 55%, transparent) 100%);
        }

        .sunna-gate-caption {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            z-index: 40;
            pointer-events: none;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--primary-500) 34%, transparent);
            background: color-mix(in srgb, var(--primary-50) 80%, transparent);
            color: color-mix(in srgb, var(--primary-950) 88%, transparent);
            font-weight: 700;
            line-height: 1;
            box-shadow: 0 6px 18px color-mix(in srgb, var(--primary-900) 16%, transparent);
        }

        .dark .sunna-gate-caption {
            border-color: color-mix(in srgb, var(--primary-200) 30%, transparent);
            background: color-mix(in srgb, var(--primary-950) 66%, transparent);
            color: color-mix(in srgb, var(--primary-50) 92%, transparent);
        }

        .sunna-gate-stage {
            position: relative;
            z-index: 10;
            aspect-ratio: 1;
            width: clamp(17rem, 72vmin, 37rem);
        }

        /* ---- Shape slot: non-rotated, carries parallax + the active/locked state ---- */
        .sunna-shape-slot {
            position: absolute;
            width: var(--w);
            height: var(--h);
            transform: translate(calc(-50% + (var(--cam-x) * var(--depth))),
                    calc(-50% + (var(--cam-y) * var(--depth))));
            transition: transform 120ms linear;
            will-change: transform;
        }

        /* Geometry tuned to ref.png: big near-square top, wider rectangle bottom-left, smallest square right, gaps between all three. */
        .sunna-shape-slot--aamal {
            --w: 70%;
            --h: 63%;
            --depth: 0.9rem;
            left: 51%;
            top: 9%;
            z-index: 20;
        }

        .sunna-shape-slot--sayd {
            --w: 60%;
            --h: 44%;
            --depth: 1.2rem;
            left: 40%;
            top: 80%;
            z-index: 20;
        }

        .sunna-shape-slot--istiham {
            --w: 35%;
            --h: 42%;
            --depth: 1.5rem;
            left: 85%;
            top: 56%;
            z-index: 30;
        }

        /* ---- The shape itself: a rounded rectangle rotated 45deg ---- */
        .sunna-shape {
            position: absolute;
            inset: 0;
            display: block;
            margin: 0;
            padding: 0;
            border-radius: 7%;
            overflow: hidden;
            border: 1px solid color-mix(in srgb, var(--primary-100) 10%, #ffffff0d);
            cursor: pointer;
            transform: rotate(45deg);
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 60%, transparent),
                inset 0 -4px 10px color-mix(in srgb, var(--primary-950) 30%, transparent),
                0 12px 26px color-mix(in srgb, var(--primary-950) 18%, transparent);
            transition:
                box-shadow 240ms cubic-bezier(0.22, 1, 0.36, 1),
                border-color 240ms ease;
            will-change: box-shadow;
        }

        .sunna-shape-slot.is-locked .sunna-shape {
            cursor: default;
        }

        /* Square media (aspect-ratio 1) sized off shape width so the upright counter-rotated image always covers a rotated rectangle without bald corners. */
        .sunna-shape__media {
            position: absolute;
            top: 50%;
            left: 50%;
            width: 158%;
            aspect-ratio: 1;
            transform: translate(-50%, -50%) rotate(-45deg);
            pointer-events: none;
        }

        .sunna-shape__media img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
            transform: scale(var(--zoom, 1.02));
            transition: transform 320ms cubic-bezier(0.22, 1, 0.36, 1);
            will-change: transform;
            backface-visibility: hidden;
        }

        .sunna-shape__veil {
            position: absolute;
            inset: 0;
            pointer-events: none;
            background: linear-gradient(191deg, 
                color-mix(in srgb, var(--primary-950) 5%, transparent), 
                color-mix(in srgb, var(--primary-950) 50%, transparent));
            transition: opacity 240ms ease;
        }

        /* Active (pointer hover or armed touch) lift for unlocked shapes. */
        .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape {
            --zoom: 1.12;
            border-color: color-mix(in srgb, var(--primary-300) 80%, white);
            box-shadow:
                inset 0 2px 6px color-mix(in srgb, white 70%, transparent),
                inset 0 -5px 12px color-mix(in srgb, var(--primary-950) 26%, transparent),
                0 16px 34px color-mix(in srgb, var(--primary-950) 24%, transparent),
                0 0 0 3px color-mix(in srgb, var(--primary-400) 40%, transparent),
                0 0 26px 4px color-mix(in srgb, var(--primary-400) 45%, transparent);
        }

        .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape__veil {
            opacity: 0.22;
        }

        /* Locked shapes still react, but with a muted aura. */
        .sunna-shape-slot.is-locked.is-active .sunna-shape {
            --zoom: 1.06;
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 60%, transparent),
                inset 0 -4px 10px color-mix(in srgb, var(--primary-950) 30%, transparent),
                0 14px 28px color-mix(in srgb, var(--primary-950) 22%, transparent),
                0 0 0 2px color-mix(in srgb, var(--gray-500) 34%, transparent);
        }

        /* ---- Upright overlay (label / lock / hint), never rotated ---- */
        .sunna-shape__face {
            position: absolute;
            inset: 0;
            z-index: 5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            padding: 0 8%;
            pointer-events: none;
            text-align: center;
        }

        .sunna-shape__label {
            color: white;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: 0.01em;
            text-shadow:
                0 2px 10px color-mix(in srgb, var(--primary-950) 72%, transparent),
                0 1px 0 color-mix(in srgb, black 55%, transparent);
            transition: opacity 240ms ease;
        }

        .sunna-shape-slot.is-locked.is-active .sunna-shape__label {
            opacity: 0.6;
        }

        .sunna-shape__lock {
            display: inline-flex;
            align-items: center;
            gap: 0.3rem;
            border-radius: 999px;
            border: 1px solid color-mix(in srgb, var(--gray-200) 70%, white);
            background: color-mix(in srgb, var(--primary-950) 72%, transparent);
            color: color-mix(in srgb, var(--primary-50) 92%, white);
            font-weight: 700;
            line-height: 1;
            white-space: nowrap;
            opacity: 0;
            transform: translateY(0.4rem);
            transition:
                opacity 240ms ease,
                transform 320ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .sunna-shape-slot.is-locked.is-active .sunna-shape__lock {
            opacity: 1;
            transform: translateY(0);
        }

        .sunna-shape__hint {
            color: white;
            font-weight: 700;
            line-height: 1;
            letter-spacing: 0.06em;
            text-shadow: 0 2px 8px color-mix(in srgb, var(--primary-950) 72%, transparent);
            opacity: 0;
            transform: translateY(0.35rem);
            transition:
                opacity 220ms ease,
                transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .sunna-shape__hint.is-visible {
            opacity: 1;
            transform: translateY(0);
        }

        /* Base + native WebViews: drop the costly outer glows, keep the motion. */
        @media (max-width: 639px) {
            .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape {
                box-shadow:
                    inset 0 2px 5px color-mix(in srgb, white 60%, transparent),
                    inset 0 -4px 9px color-mix(in srgb, var(--primary-950) 26%, transparent),
                    0 10px 20px color-mix(in srgb, var(--primary-950) 20%, transparent),
                    0 0 0 2px color-mix(in srgb, var(--primary-400) 38%, transparent);
            }
        }

        .native-platform .sunna-shape {
            will-change: auto;
        }

        .native-platform .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape {
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 60%, transparent),
                inset 0 -4px 9px color-mix(in srgb, var(--primary-950) 26%, transparent),
                0 10px 20px color-mix(in srgb, var(--primary-950) 20%, transparent),
                0 0 0 2px color-mix(in srgb, var(--primary-400) 38%, transparent);
        }
    </style>
@endassets

<div
    @class([
        'absolute inset-x-0 bottom-0 z-10 flex items-center justify-center sm:inset-0',
        '-top-16 sm:top-0' => !is_platform('ios'),
        '-top-22 sm:top-0' => is_platform('ios'),
    ])
    x-cloak
    x-show="views['sunna-gate'].isOpen"
    x-transition:enter="transition-opacity ease-out duration-380"
    x-transition:enter-start="opacity-0!"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition-opacity ease-in duration-180!"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0!"
>
    <section
        class="sunna-gate-shell flex h-full w-full items-center justify-center"
        x-data="sunnaAppGate"
        x-ref="shell"
        x-on:pointermove="handlePointerMove($event)"
        x-on:pointerleave="handlePointerLeave()"
        x-on:touchstart.passive="handleTouchStart($event)"
        x-on:touchmove="handleTouchMove($event)"
        x-on:touchend="handleTouchEnd()"
        x-on:touchcancel="handleTouchEnd()"
    >
        <div
            class="sunna-gate-bg"
            x-cloak
            x-show="!$store.colorScheme.isDarkModeOn"
            x-transition:enter="transition-opacity duration-500 delay-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover opacity-20"
                alt="{{ arabic_text('خلفية السنن') }}"
                :imagePath="asset('images/background/sunna/desert-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/sunna/desert-morning-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#sunna-gate'"
            />
        </div>

        <div
            class="sunna-gate-bg"
            x-cloak
            x-show="$store.colorScheme.isDarkModeOn"
            x-transition:enter="transition-opacity duration-500 delay-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover opacity-25"
                alt="{{ arabic_text('خلفية السنن') }}"
                :imagePath="asset('images/background/sunna/desert-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/sunna/desert-night-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#sunna-gate'"
            />
        </div>

        <div class="sunna-gate-bg-scrim"></div>

        <p @class([
            'top-[1.76rem]' => !is_platform('ios'),
            'top-[3.6rem]' => is_platform('ios'),
            'sunna-gate-caption px-[0.72rem] py-[0.3rem] text-[0.62rem] sm:top-[1.85rem] sm:px-[0.85rem] sm:py-2 sm:text-[0.7rem] md:top-8 md:px-4 md:py-2 md:text-[0.9rem] lg:top-6 lg:px-[0.925rem] lg:text-[0.875rem] xl:top-6.5 xl:px-[0.85rem] xl:py-[0.45rem] xl:text-[0.75rem] 2xl:top-[1.9rem] 2xl:px-3 2xl:text-[0.75rem] 3xl:top-8 3xl:text-[0.9rem] 4xl:top-10 4xl:text-[1.1rem]',
        ])>
            {{ arabic_text('اختر بابًا من أبواب السنن') }}
        </p>

        <div class="sunna-gate-stage">
            @php
                $shapes = [
                    ['mode' => 'aamal', 'label' => 'أعمال اليوم والليلة', 'image' => 'aamal.webp', 'locked' => true],
                    ['mode' => 'sayd', 'label' => 'صيد السنن', 'image' => 'sayd.webp', 'locked' => true],
                    ['mode' => 'istiham', 'label' => 'الاستهام', 'image' => 'istiham.webp', 'locked' => false],
                ];
            @endphp

            @foreach ($shapes as $shape)
                <div
                    @class([
                        'sunna-shape-slot sunna-shape-slot--' . $shape['mode'],
                        'is-locked' => $shape['locked'],
                    ])
                    x-bind:class="{ 'is-active': isModeActive('{{ $shape['mode'] }}') }"
                >
                    <button
                        class="sunna-shape"
                        type="button"
                        aria-label="{{ arabic_text($shape['label']) }}"
                        @if ($shape['locked']) aria-disabled="true" @endif
                        x-on:mouseenter="hoverMode('{{ $shape['mode'] }}')"
                        x-on:mouseleave="unhoverMode('{{ $shape['mode'] }}')"
                        x-on:click="activate('{{ $shape['mode'] }}', $event)"
                    >
                        <span class="sunna-shape__media">
                            <img
                                src="{{ asset('images/background/sunna/' . $shape['image']) }}"
                                alt="{{ arabic_text($shape['label']) }}"
                                loading="eager"
                                decoding="async"
                                draggable="false"
                            >
                        </span>
                        <span class="sunna-shape__veil"></span>
                    </button>

                    <div class="sunna-shape__face">
                        <span
                            class="sunna-shape__label font-arabic-serif 3xl:text-[1.4rem] 4xl:text-[1.65rem] text-[0.82rem] sm:text-[0.95rem] md:text-[1.2rem] lg:text-[1.2rem] xl:text-[1.1rem] 2xl:text-[1.15rem]"
                        >{{ arabic_text($shape['label']) }}</span>

                        @if ($shape['locked'])
                            <span
                                class="sunna-shape__lock 3xl:text-[0.92rem] 4xl:text-[1rem] px-2 py-[0.26rem] text-[0.7rem] sm:text-[0.76rem] md:text-[0.86rem] lg:text-[0.84rem] xl:text-[0.74rem] 2xl:text-[0.78rem]"
                                aria-hidden="true"
                            >
                                <x-icon
                                    class="h-[0.9rem] w-[0.9rem] sm:h-[0.95rem] sm:w-[0.95rem] md:h-4 md:w-4"
                                    :name="'heroicon-o-lock-closed'"
                                />
                                {{ arabic_text('قريبًا') }}
                            </span>
                        @else
                            <span
                                class="sunna-shape__hint 3xl:text-[1rem] 4xl:text-[1.1rem] text-[0.74rem] sm:text-[0.8rem] md:text-[0.9rem] lg:text-[0.9rem] xl:text-[0.78rem] 2xl:text-[0.82rem]"
                                aria-hidden="true"
                                x-cloak
                                x-bind:class="{ 'is-visible': shouldShowEnterHint('{{ $shape['mode'] }}') }"
                            >{{ arabic_text('انقر') }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>
    </section>
</div>
