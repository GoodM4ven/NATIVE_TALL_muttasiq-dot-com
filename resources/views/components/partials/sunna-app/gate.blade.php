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
            -webkit-tap-highlight-color: transparent;
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

        /* Noble caption: engraved plaque (double hairline + top sheen + soft accent glow) flanked by small diamonds echoing the gate shapes; shared design with the quran gate caption, different accent palette; all box-shadow so it stays cheap. */
        .sunna-gate-caption {
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            z-index: 40;
            pointer-events: none;
            display: inline-flex;
            align-items: center;
            gap: 0.7em;
            border-radius: 999px;
            font-weight: 600;
            letter-spacing: 0.045em;
            line-height: 1;
            color: color-mix(in srgb, var(--primary-950) 90%, transparent);
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--primary-50) 95%, transparent),
                    color-mix(in srgb, var(--primary-100) 82%, transparent));
            box-shadow:
                inset 0 1px 0 color-mix(in srgb, white 85%, transparent),
                inset 0 0 0 1px color-mix(in srgb, var(--primary-300) 44%, transparent),
                0 0 0 1px color-mix(in srgb, var(--primary-500) 26%, transparent),
                0 8px 22px color-mix(in srgb, var(--primary-900) 18%, transparent),
                0 0 16px color-mix(in srgb, var(--primary-400) 18%, transparent);
            text-shadow: 0 1px 0 color-mix(in srgb, white 50%, transparent);
        }

        .sunna-gate-caption::before,
        .sunna-gate-caption::after {
            content: '';
            flex: none;
            width: 0.46em;
            height: 0.46em;
            border-radius: 1.5px;
            transform: rotate(45deg);
            background: linear-gradient(135deg,
                    color-mix(in srgb, var(--primary-300) 92%, white),
                    color-mix(in srgb, var(--primary-500) 90%, transparent));
            box-shadow:
                inset 0 0 0 0.5px color-mix(in srgb, white 60%, transparent),
                0 0 5px color-mix(in srgb, var(--primary-400) 55%, transparent);
        }

        .dark .sunna-gate-caption {
            color: color-mix(in srgb, var(--primary-50) 94%, transparent);
            background: linear-gradient(180deg,
                    color-mix(in srgb, var(--primary-900) 68%, transparent),
                    color-mix(in srgb, var(--primary-950) 80%, transparent));
            box-shadow:
                inset 0 1px 0 color-mix(in srgb, var(--primary-200) 22%, transparent),
                inset 0 0 0 1px color-mix(in srgb, var(--primary-300) 30%, transparent),
                0 0 0 1px color-mix(in srgb, var(--primary-500) 30%, transparent),
                0 8px 22px color-mix(in srgb, black 34%, transparent),
                0 0 18px color-mix(in srgb, var(--primary-400) 26%, transparent);
            text-shadow: 0 1px 6px color-mix(in srgb, black 45%, transparent);
        }

        .dark .sunna-gate-caption::before,
        .dark .sunna-gate-caption::after {
            background: linear-gradient(135deg,
                    color-mix(in srgb, var(--primary-200) 92%, white),
                    color-mix(in srgb, var(--primary-400) 90%, transparent));
            box-shadow:
                inset 0 0 0 0.5px color-mix(in srgb, white 40%, transparent),
                0 0 6px color-mix(in srgb, var(--primary-300) 55%, transparent);
        }

        @media (max-width: 639px) {
            .sunna-gate-caption {
                gap: 0.5em;
            }

            .sunna-gate-caption::before,
            .sunna-gate-caption::after {
                display: none;
            }
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
            cursor: pointer;
            outline: none;
            user-select: none;
            -webkit-user-select: none;
            -webkit-tap-highlight-color: transparent;
            transform: rotate(45deg);
            /* No border: a primary-shaded ring + soft aura, all box-shadow (cheap, GPU-friendly). */
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 55%, transparent),
                inset 0 -4px 10px color-mix(in srgb, var(--primary-950) 30%, transparent),
                0 0 0 1px color-mix(in srgb, var(--primary-300) 50%, transparent),
                0 10px 24px color-mix(in srgb, var(--primary-950) 20%, transparent),
                0 0 16px color-mix(in srgb, var(--primary-400) 20%, transparent);
            transition: box-shadow 260ms cubic-bezier(0.22, 1, 0.36, 1);
            /* ponytail: dropped `will-change: box-shadow` — box-shadow can't be GPU-composited, so the hint just forces a wasted layer with no payoff while the shadow still repaints. */
        }

        .dark .sunna-shape {
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 32%, transparent),
                inset 0 -4px 10px color-mix(in srgb, var(--primary-950) 46%, transparent),
                0 0 0 1px color-mix(in srgb, var(--primary-300) 42%, transparent),
                0 10px 24px color-mix(in srgb, black 38%, transparent),
                0 0 20px color-mix(in srgb, var(--primary-400) 26%, transparent);
        }

        /* Locked shapes are muted from the start: a neutral ring, NO primary aura layer, so there is no aura spread to flash before settling when they become active. */
        .sunna-shape-slot.is-locked .sunna-shape {
            cursor: default;
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 55%, transparent),
                inset 0 -4px 10px color-mix(in srgb, var(--primary-950) 30%, transparent),
                0 0 0 1px color-mix(in srgb, var(--gray-400) 32%, transparent),
                0 10px 24px color-mix(in srgb, var(--primary-950) 20%, transparent);
        }

        .dark .sunna-shape-slot.is-locked .sunna-shape {
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 30%, transparent),
                inset 0 -4px 10px color-mix(in srgb, var(--primary-950) 46%, transparent),
                0 0 0 1px color-mix(in srgb, var(--gray-400) 26%, transparent),
                0 10px 24px color-mix(in srgb, black 38%, transparent);
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
            user-select: none;
            -webkit-user-drag: none;
            /* img is in a net-zero rotation frame (shape +45 then media -45), so this translate is screen-aligned: the picture drifts toward the cursor like the shapes do, on top of the in-place zoom; the 158% media oversize hides the edges. */
            transform: translate(calc(var(--cam-x) * var(--img-shift, 0.55rem)),
                    calc(var(--cam-y) * var(--img-shift, 0.55rem))) scale(var(--zoom, 1.02));
            transition: transform 240ms cubic-bezier(0.22, 1, 0.36, 1);
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

        /* Active (pointer hover or armed touch) lift for unlocked shapes: a wide primary aura. */
        /* ponytail: same layered aura, intensity nudged down — the two widest glows (was 34px/6px and 72px/16px) are the dominant repaint cost while the hover box-shadow transitions; trimmed their blur radius + spread + opacity so the halo still reads but paints a far smaller area. Inner ring/close glow kept intact so the look holds. */
        .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape {
            --zoom: 1.12;
            box-shadow:
                inset 0 2px 6px color-mix(in srgb, white 68%, transparent),
                inset 0 -5px 12px color-mix(in srgb, var(--primary-950) 26%, transparent),
                0 0 0 2px color-mix(in srgb, var(--primary-400) 85%, transparent),
                0 16px 34px color-mix(in srgb, var(--primary-950) 26%, transparent),
                0 0 14px 1px color-mix(in srgb, var(--primary-300) 64%, transparent),
                0 0 26px 4px color-mix(in srgb, var(--primary-400) 60%, transparent),
                0 0 46px 9px color-mix(in srgb, var(--primary-500) 38%, transparent);
        }

        .dark .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape {
            box-shadow:
                inset 0 2px 6px color-mix(in srgb, white 38%, transparent),
                inset 0 -5px 12px color-mix(in srgb, var(--primary-950) 40%, transparent),
                0 0 0 2px color-mix(in srgb, var(--primary-300) 80%, transparent),
                0 16px 34px color-mix(in srgb, black 44%, transparent),
                0 0 14px 1px color-mix(in srgb, var(--primary-200) 54%, transparent),
                0 0 28px 4px color-mix(in srgb, var(--primary-400) 60%, transparent),
                0 0 46px 10px color-mix(in srgb, var(--primary-500) 42%, transparent);
        }

        .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape__veil {
            opacity: 0.22;
        }

        /* Locked active: only the neutral ring firms up + a small lift; same layer order as the locked resting state above, so box-shadow interpolates cleanly (no aura flash). */
        .sunna-shape-slot.is-locked.is-active .sunna-shape {
            --zoom: 1.06;
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 60%, transparent),
                inset 0 -4px 10px color-mix(in srgb, var(--primary-950) 30%, transparent),
                0 0 0 2px color-mix(in srgb, var(--gray-500) 40%, transparent),
                0 14px 28px color-mix(in srgb, var(--primary-950) 24%, transparent);
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

        /* Caption plate: a rounded rectangle the label sits on, for legibility over the photo. */
        .sunna-shape__plate {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            max-width: 100%;
            border-radius: 0.7rem;
            background: linear-gradient(165deg,
                    color-mix(in srgb, var(--primary-950) 50%, transparent),
                    color-mix(in srgb, var(--primary-950) 70%, transparent));
            box-shadow:
                inset 0 1px 0 color-mix(in srgb, white 24%, transparent),
                inset 0 0 0 1px color-mix(in srgb, var(--primary-300) 26%, transparent),
                0 6px 16px color-mix(in srgb, var(--primary-950) 38%, transparent);
            /* Slow, smooth fades (opacity + background) so the title settles gently, not abruptly. */
            transition:
                box-shadow 320ms ease,
                background 460ms cubic-bezier(0.22, 1, 0.36, 1),
                opacity 520ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .dark .sunna-shape__plate {
            background: linear-gradient(165deg,
                    color-mix(in srgb, var(--primary-950) 62%, transparent),
                    color-mix(in srgb, var(--primary-950) 80%, transparent));
        }

        /* Unlocked + active: the plate goes much more transparent so the title text reads as the hero, while the ring/glow still frame it. */
        .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape__plate {
            background: linear-gradient(165deg,
                    color-mix(in srgb, var(--primary-950) 70%, transparent),
                    color-mix(in srgb, var(--primary-950) 92%, transparent));
            box-shadow:
                inset 0 1px 0 color-mix(in srgb, white 30%, transparent),
                inset 0 0 0 1px color-mix(in srgb, var(--primary-300) 56%, transparent),
                0 8px 20px color-mix(in srgb, var(--primary-950) 36%, transparent),
                0 0 18px color-mix(in srgb, var(--primary-400) 36%, transparent);
        }

        .dark .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape__plate {
            background: linear-gradient(165deg,
                    color-mix(in srgb, var(--primary-950) 78%, transparent),
                    color-mix(in srgb, var(--primary-950) 92%, transparent));
        }

        .sunna-shape__label {
            color: white;
            font-weight: 700;
            line-height: 1.25;
            letter-spacing: 0.01em;
            text-shadow: 0 1px 6px color-mix(in srgb, var(--primary-950) 60%, transparent);
            transition: opacity 240ms ease;
        }

        .sunna-shape-slot.is-locked.is-active .sunna-shape__plate {
            opacity: 0.62;
        }

        .sunna-shape-slot:not(.is-locked).is-active .sunna-shape__plate {
            opacity: 0.95;
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
            transform: translateY(0.5rem);
            transition:
                opacity 220ms ease,
                transform 300ms cubic-bezier(0.22, 1, 0.36, 1);
        }

        .sunna-shape__hint.is-visible {
            opacity: 1;
            transform: translateY(0.35rem);
        }

        /* Base + native WebViews: drop the costly outer glows, keep the motion. */
        @media (max-width: 639px) {
            .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape {
                box-shadow:
                    inset 0 2px 5px color-mix(in srgb, white 58%, transparent),
                    inset 0 -4px 9px color-mix(in srgb, var(--primary-950) 26%, transparent),
                    0 0 0 2px color-mix(in srgb, var(--primary-300) 70%, transparent),
                    0 10px 20px color-mix(in srgb, var(--primary-950) 20%, transparent),
                    0 0 22px 3px color-mix(in srgb, var(--primary-400) 50%, transparent);
            }
        }

        .native-platform .sunna-shape {
            will-change: auto;
        }

        .native-platform .sunna-shape-slot.is-active:not(.is-locked) .sunna-shape {
            box-shadow:
                inset 0 2px 5px color-mix(in srgb, white 58%, transparent),
                inset 0 -4px 9px color-mix(in srgb, var(--primary-950) 26%, transparent),
                0 0 0 2px color-mix(in srgb, var(--primary-300) 70%, transparent),
                0 10px 20px color-mix(in srgb, var(--primary-950) 20%, transparent),
                0 0 22px 3px color-mix(in srgb, var(--primary-400) 50%, transparent);
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
        x-on:click="handleBackgroundTap($event)"
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
                class="h-full w-full scale-110 object-cover opacity-15"
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
                class="h-full w-full scale-110 object-cover opacity-[0.225]"
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
            'sunna-gate-caption font-arabic-serif px-[0.9rem] py-[0.34rem] text-[0.64rem] sm:top-[1.85rem] sm:px-[1.05rem] sm:py-2 sm:text-[0.72rem] md:top-8 md:px-[1.15rem] md:py-2 md:text-[0.92rem] lg:top-6 lg:px-[1.1rem] lg:text-[0.9rem] xl:top-6.5 xl:px-4 xl:py-[0.45rem] xl:text-[0.78rem] 2xl:top-[1.9rem] 2xl:px-[1.05rem] 2xl:text-[0.78rem] 3xl:top-8 3xl:text-[0.95rem] 4xl:top-10 4xl:text-[1.15rem]',
        ])>
            {{ arabic_text('اختر بابًا من أبواب السنن') }}
        </p>

        <div
            class="sunna-gate-stage 2xl:scale-80 md:scale-85 top-18 3xl:scale-100 3xl:top-20 -left-4 scale-95 sm:left-0 sm:scale-95">
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
                    x-data="{ isLocked: @js($shape['locked']) }"
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
                            class="sunna-shape__plate 3xl:px-5 3xl:py-2 4xl:px-6 4xl:py-[0.6rem] px-[0.7rem] py-[0.3rem] sm:px-[0.85rem] sm:py-[0.34rem] md:px-[1.1rem] md:py-[0.42rem] lg:px-[1.1rem] lg:py-[0.42rem] xl:px-4 xl:py-[0.4rem] 2xl:px-4 2xl:py-[0.42rem]"
                            x-bind:class="!isLocked &&
                                'scale-[1.225] sm:scale-[1.3] md:scale-[1.2] lg:scale-[1.25] xl:scale-[1.3] 2xl:scale-[1.2] 3xl:scale-[1.15]'"
                        >
                            <span
                                class="sunna-shape__label font-arabic-serif 3xl:text-[1.4rem] 4xl:text-[1.65rem] text-[1.0rem] sm:text-[1.25rem] md:text-[1.375rem] lg:text-[1.6rem] xl:text-[1.5rem] 2xl:text-[1.5rem]"
                            >{{ arabic_text($shape['label']) }}</span>
                        </span>

                        @if ($shape['locked'])
                            <span
                                class="sunna-shape__lock 3xl:text-[0.92rem] 4xl:text-[1rem] px-2 py-[0.26rem] text-[0.7rem] sm:text-[1.1rem] md:text-[1.2rem] lg:text-[1.3rem] xl:text-[1.4rem] 2xl:text-[1.1rem]"
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
                                class="sunna-shape__hint 3xl:text-[1rem] 4xl:text-[1.1rem] text-[0.9rem] sm:text-[1.2rem] md:text-[1.35rem] lg:text-[1.4rem] xl:text-[1.25rem] 2xl:text-[1.2rem]"
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
