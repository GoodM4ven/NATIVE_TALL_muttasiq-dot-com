@assets
    <style>
        .athkar-gate-wrap {
            --split: 50%;
            --tilt: 7%;
            --overlap: 1.4%;
            --divider-width: 1.2%;
            --divider-glow-width: 3.8%;
            --radius: 22px;
            --radius-bottom: 22px;
            --pane-split-duration: 520ms;
            --spill-split-duration: 900ms;
            --ping-color: rgba(226, 232, 240, 0.45);
            --edge-glow: rgba(125, 211, 252, 0.35);
            --split-top: calc(var(--split) - var(--tilt));
            --split-bottom: calc(var(--split) + var(--tilt));
            --spill-split: var(--split);
            --spill-split-top: calc(var(--spill-split) - var(--tilt));
            --spill-split-bottom: calc(var(--spill-split) + var(--tilt));
            --gate-glass-border-radius: calc(var(--radius) - 10px);
        }

        .athkar-gate-wrap:not(.is-enhanced) {
            --pane-split-duration: 250ms;
            --spill-split-duration: 400ms;
            --gate-blur: 0px !important;
            --divider-glow-blur: 0px !important;
            --spill-blur: 0px !important;
        }

        @media (max-width: 639px) {
            .athkar-gate-wrap {
                --tilt: 5%;
                --overlap: 1%;
                --divider-width: 0.9%;
                --divider-glow-width: 2.6%;
                --radius: 18px;
                --radius-bottom: 20px;
            }
        }

        .dark .athkar-gate-wrap {
            --ping-color: rgba(148, 163, 184, 0.45);
            --edge-glow: rgba(56, 189, 248, 0.3);
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate {
            box-shadow:
                0 20px 45px rgba(15, 23, 42, 0.22),
                inset 0 1px 0 rgba(255, 255, 255, 0.2);
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__glass {
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            background: rgba(255, 255, 255, 0.08);
        }

        .dark .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__glass {
            background: rgba(2, 6, 23, 0.45);
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__spill {
            filter: none;
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__spill-image {
            opacity: 0.5;
            transform: scale(1.03);
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__divider {
            filter: none;
            opacity: 0.85;
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__divider,
        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__veil {
            /* mix-blend-mode: normal; */
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__divider-glow,
        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__ping {
            display: none;
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__label {
            backdrop-filter: none;
            -webkit-backdrop-filter: none;
            text-shadow: 0 6px 12px rgba(2, 6, 23, 0.3);
        }

        .athkar-gate {
            position: relative;
            height: 100%;
            width: 100%;
            border-radius: var(--radius) var(--radius) var(--radius-bottom) var(--radius-bottom);
            border: 1px solid transparent;
            background:
                linear-gradient(140deg, rgba(255, 255, 255, 0.2), rgba(255, 255, 255, 0.04)) padding-box,
                linear-gradient(120deg,
                    rgba(148, 163, 184, 0.3),
                    rgba(125, 211, 252, 0.45),
                    rgba(148, 163, 184, 0.2)) border-box;
            box-shadow:
                0 36px 64px rgba(15, 23, 42, 0.26),
                inset 0 1px 0 rgba(255, 255, 255, 0.35),
                inset 0 -20px 40px rgba(15, 23, 42, 0.05);
            backdrop-filter: blur(var(--gate-blur, 20px));
            -webkit-backdrop-filter: blur(var(--gate-blur, 20px));
            overflow: hidden;
            isolation: isolate;
            z-index: 2;
        }

        .dark .athkar-gate {
            background:
                linear-gradient(145deg, rgba(15, 23, 42, 0.9), rgba(2, 6, 23, 0.75)) padding-box,
                linear-gradient(120deg,
                    rgba(148, 163, 184, 0.3),
                    rgba(56, 189, 248, 0.35),
                    rgba(30, 41, 59, 0.4)) border-box;
            box-shadow:
                0 40px 70px rgba(2, 6, 23, 0.65),
                inset 0 1px 0 rgba(226, 232, 240, 0.18);
        }

        @media (max-width: 639px) {
            .athkar-gate {
                background:
                    linear-gradient(150deg, rgba(255, 255, 255, 0.26), rgba(255, 255, 255, 0.07)) padding-box,
                    linear-gradient(120deg,
                        rgba(148, 163, 184, 0.35),
                        rgba(125, 211, 252, 0.5),
                        rgba(148, 163, 184, 0.22)) border-box;
                box-shadow:
                    0 26px 52px rgba(15, 23, 42, 0.22),
                    inset 0 1px 0 rgba(255, 255, 255, 0.3);
            }

            .dark .athkar-gate {
                background:
                    linear-gradient(155deg, rgba(15, 23, 42, 0.92), rgba(2, 6, 23, 0.78)) padding-box,
                    linear-gradient(120deg,
                        rgba(148, 163, 184, 0.28),
                        rgba(56, 189, 248, 0.38),
                        rgba(30, 41, 59, 0.36)) border-box;
                box-shadow:
                    0 30px 56px rgba(2, 6, 23, 0.6),
                    inset 0 1px 0 rgba(226, 232, 240, 0.16);
            }

            .athkar-gate__divider {
                opacity: 0.82;
            }
        }

        .athkar-gate__glass {
            position: absolute;
            inset: 8px;
            border-radius: var(--gate-glass-border-radius);
            overflow: hidden;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.12);
            z-index: 4;
        }

        @media (min-width: 640px) {
            .athkar-gate__glass {
                inset: 12px;
            }
        }

        .dark .athkar-gate__glass {
            background: rgba(2, 6, 23, 0.36);
            border-color: rgba(255, 255, 255, 0.1);
        }

        .athkar-gate__pane {
            position: absolute;
            border-radius: var(--gate-glass-border-radius);
            inset: 0;
            display: block;
            border: 0;
            padding: 0;
            margin: 0;
            cursor: pointer;
            background: transparent;
            z-index: 1;
            backface-visibility: hidden;
            transform: translateZ(0);
            user-select: none;
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
        }

        .athkar-gate__pane--night {
            z-index: 1;
            clip-path: polygon(0 0,
                    calc(var(--split-top) + var(--overlap)) 0,
                    calc(var(--split-bottom) + var(--overlap)) 100%,
                    0 100%);
            transition: clip-path var(--pane-split-duration, 750ms) cubic-bezier(0.16, 0.84, 0.44, 1);
        }

        .athkar-gate__pane--morning {
            z-index: 2;
            clip-path: polygon(calc(var(--split-top) - var(--overlap)) 0,
                    100% 0,
                    100% 100%,
                    calc(var(--split-bottom) - var(--overlap)) 100%);
            transition: clip-path var(--pane-split-duration, 750ms) cubic-bezier(0.16, 0.84, 0.44, 1);
        }

        img.athkar-gate__image-img {
            border-radius: var(--gate-glass-border-radius);
            height: 100%;
            width: 100%;
            object-fit: cover;
            transform: scale(1.02);
            transition: transform 1200ms ease, filter 900ms ease, opacity 700ms ease;
            backface-visibility: hidden;
            user-select: none;
            -webkit-user-drag: none;
            -webkit-touch-callout: none;
            pointer-events: none;
        }

        .athkar-gate-shell.is-night .athkar-gate__pane--night img.athkar-gate__image-img {
            transform: scale(1.06);
            filter: brightness(1.06);
        }

        .athkar-gate-shell.is-morning .athkar-gate__pane--morning img.athkar-gate__image-img {
            transform: scale(1.06);
            filter: brightness(1.06);
        }

        .athkar-gate-shell.is-night .athkar-gate__pane--morning img.athkar-gate__image-img,
        .athkar-gate-shell.is-morning .athkar-gate__pane--night img.athkar-gate__image-img {
            filter: brightness(0.92);
        }

        .athkar-gate__veil {
            position: absolute;
            inset: 0;
            pointer-events: none;
        }

        .athkar-gate__veil--morning {
            background: linear-gradient(140deg, rgba(255, 255, 255, 0.3), rgba(255, 255, 255, 0.08));
            /* mix-blend-mode: screen; */
        }

        .athkar-gate__divider {
            position: absolute;
            top: -8%;
            bottom: -8%;
            left: 0;
            right: 0;
            background: linear-gradient(180deg,
                    rgba(255, 255, 255, 0.95),
                    rgba(255, 255, 255, 0.12),
                    rgba(226, 232, 240, 0.85));
            opacity: 0.95;
            filter: drop-shadow(0 6px 10px rgba(15, 23, 42, 0.16));
            /* mix-blend-mode: screen; */
            clip-path: polygon(calc(var(--split-top) - var(--divider-width)) 0,
                    calc(var(--split-top) + var(--divider-width)) 0,
                    calc(var(--split-bottom) + var(--divider-width)) 100%,
                    calc(var(--split-bottom) - var(--divider-width)) 100%);
            pointer-events: none;
            transition: clip-path 750ms cubic-bezier(0.16, 0.84, 0.44, 1);
            z-index: 6;
        }

        .athkar-gate__divider-glow {
            position: absolute;
            top: -12%;
            bottom: -12%;
            left: 0;
            right: 0;
            background: linear-gradient(180deg,
                    rgba(125, 211, 252, 0.45),
                    rgba(255, 255, 255, 0.05),
                    rgba(186, 230, 253, 0.25));
            opacity: 0.72;
            filter: blur(var(--divider-glow-blur, 10px));
            clip-path: polygon(calc(var(--split-top) - var(--divider-glow-width)) 0,
                    calc(var(--split-top) + var(--divider-glow-width)) 0,
                    calc(var(--split-bottom) + var(--divider-glow-width)) 100%,
                    calc(var(--split-bottom) - var(--divider-glow-width)) 100%);
            pointer-events: none;
            transition: clip-path 750ms cubic-bezier(0.16, 0.84, 0.44, 1);
            z-index: 5;
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__divider-glow {
            opacity: 0.4;
        }

        .athkar-gate__label {
            position: absolute;
            z-index: 60;
            padding: 0.45rem 1.1rem;
            border-radius: 999px;
            border: 1px solid rgba(255, 255, 255, 0.22);
            text-shadow: 0 8px 18px rgba(2, 6, 23, 0.32);
            backdrop-filter: blur(3px);
            -webkit-backdrop-filter: blur(3px);
            transition: transform 500ms ease, opacity 500ms ease, background-color 500ms ease;
            opacity: 0.72;
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__label {
            opacity: 1;
        }

        .athkar-gate__label--night {
            background: color-mix(in srgb, var(--background) 72%, transparent);
            color: var(--foreground);
            left: 1.35rem;
            bottom: 1.35rem;
        }

        .athkar-gate__label--morning {
            background: color-mix(in srgb, var(--background-dark) 72%, transparent);
            color: var(--foreground-dark);
            top: 1.35rem;
            right: 1.35rem;
        }

        .athkar-gate-shell.is-night .athkar-gate__label--night,
        .athkar-gate-shell.is-morning .athkar-gate__label--morning {
            opacity: 1;
            transform: translateY(-3px);
        }

        .athkar-gate-shell.is-morning .athkar-gate__label--morning {
            background: color-mix(in srgb, var(--background-dark) 85%, transparent);
        }

        .athkar-gate-shell.is-night .athkar-gate__label--night {
            background: color-mix(in srgb, var(--background) 90%, transparent);
        }

        .athkar-gate__ping {
            position: absolute;
            inset: 0px;
            border-radius: var(--radius) var(--radius) var(--radius-bottom) var(--radius-bottom);
            pointer-events: none;
            opacity: 0;
            z-index: 15;
        }

        .athkar-gate-shell.is-pinging .athkar-gate__ping {
            animation: athkar-gate-ping 1.4s ease-out;
        }

        .athkar-gate__spill {
            position: absolute;
            inset: var(--spill-inset, -50vmax);
            pointer-events: none;
            z-index: 0;
            opacity: var(--spill-opacity, 0);
            filter: blur(var(--spill-blur, 14px)) saturate(1.02);
            transition: opacity var(--spill-transition, 200ms) ease;
        }

        .athkar-gate-wrap:not(.is-spill-ready) .athkar-gate__spill {
            display: none;
        }

        .athkar-gate__spill-pane {
            position: absolute;
            inset: 0;
            transition: clip-path var(--spill-split-duration, 1200ms) cubic-bezier(0.16, 0.84, 0.44, 1);
        }

        .athkar-gate__spill-pane--night {
            clip-path: polygon(0 0,
                    calc(var(--spill-split-top) + var(--overlap)) 0,
                    calc(var(--spill-split-bottom) + var(--overlap)) 100%,
                    0 100%);
        }

        .athkar-gate__spill-pane--morning {
            clip-path: polygon(calc(var(--spill-split-top) - var(--overlap)) 0,
                    100% 0,
                    100% 100%,
                    calc(var(--spill-split-bottom) - var(--overlap)) 100%);
        }

        .athkar-gate__spill .bg-gray-500 {
            background-color: transparent !important;
        }

        .athkar-gate-wrap:not(.is-enhanced) .athkar-gate__spill {
            display: none;
        }

        .athkar-gate__spill-image {
            height: 100%;
            width: 100%;
            object-fit: cover;
            opacity: 0.55;
            transform: scale(var(--spill-scale, 1.14));
        }

        @keyframes athkar-gate-ping {
            0% {
                box-shadow: 0 0 0 0 var(--ping-color);
                opacity: 1;
            }

            100% {
                box-shadow: 0 0 0 30px rgba(255, 255, 255, 0);
                opacity: 0;
            }
        }
    </style>
@endassets

<div
    class="absolute inset-0 z-20 flex items-center justify-center px-6 py-12"
    x-cloak
    x-show="views['athkar-app-gate'].isOpen && !activeMode && !isCompletionVisible"
    x-bind:style="gateTransitionStyles()"
    x-transition:enter="transition-all ease-out duration-500"
    x-transition:enter-start="opacity-0! blur-[2px] athkar-shift-away"
    x-transition:enter-end="opacity-100 blur-0 athkar-shift-center"
    x-transition:leave="transition-all ease-in duration-200"
    x-transition:leave-start="opacity-100 blur-0 athkar-shift-center"
    x-transition:leave-end="opacity-0! blur-[2px] athkar-shift-away"
>
    <div class="relative flex select-none flex-col items-center">
        <div
            class="athkar-gate-wrap relative w-full max-w-5xl"
            x-data="athkarAppGate"
            x-bind:class="{ 'is-enhanced': isEnhanced, 'is-spill-ready': isSpillReady }"
            x-bind:style="{
                '--split': `${splitValue}%`,
                '--spill-opacity': spillOpacity,
                '--spill-transition': `${spillTransitionMs}ms`,
                '--spill-inset': isEnhanced ? '-28vmax' : '-26vmax',
                '--spill-blur': isEnhanced ? '6px' : '0px',
                '--spill-scale': isEnhanced ? '1.06' : '1.03',
                '--spill-split': `${splitValue}%`,
                '--gate-blur': isEnhanced ? '14px' : '0px',
                '--divider-glow-blur': isEnhanced ? '6px' : '0px',
            }"
            x-effect="syncPerfProfile(); syncSpillState(views['athkar-app-gate'].isOpen);"
            x-on:click.outside="handleOutsideActivation()"
        >
            <div
                class="athkar-gate-shell relative z-10 h-[min(92vw,760px)] max-h-[55svh] w-[min(92vw,920px)] sm:max-h-[75svh]"
                x-ref="gate"
                x-bind:class="{
                    'is-hovering': isHovering,
                    'is-pinging': isPinging,
                    'is-night': activeSide === 'night' || hoverSide === 'night',
                    'is-morning': activeSide === 'morning' || hoverSide === 'morning',
                }"
                x-on:mouseenter="startHover()"
                x-on:mouseleave="endHover(); resetHover()"
                x-on:focusin="startHover()"
                x-on:focusout="endHover()"
            >
                <div class="athkar-gate__ping"></div>

                <!-- Background -->
                <div class="athkar-gate__spill">
                    <div class="athkar-gate__spill-pane athkar-gate__spill-pane--night">
                        <x-goodmaven::blurred-image
                            alt="Athkar night spill"
                            :imagePath="asset('images/background/night-blurred.png')"
                            :thumbnailImagePath="asset('images/background/night-blurred-blur-thumbnail.png')"
                            :isDisplayEnforced="true"
                            containerClasses="overflow-visible bg-transparent"
                            imageClasses="athkar-gate__spill-image"
                        />
                    </div>

                    <div class="athkar-gate__spill-pane athkar-gate__spill-pane--morning">
                        <x-goodmaven::blurred-image
                            alt="Athkar morning spill"
                            :imagePath="asset('images/background/morning-blurred.png')"
                            :thumbnailImagePath="asset('images/background/morning-blurred-blur-thumbnail.png')"
                            :isDisplayEnforced="true"
                            containerClasses="overflow-visible bg-transparent"
                            imageClasses="athkar-gate__spill-image"
                        />
                    </div>
                </div>

                <!-- Buttons -->
                <div class="athkar-gate">
                    <div class="athkar-gate__glass">
                        <button
                            class="athkar-gate__pane athkar-gate__pane--night"
                            type="button"
                            aria-label="أذكار المساء"
                            x-bind:class="{ 'pointer-events-none grayscale opacity-60': isModeLocked('masaa') }"
                            x-bind:aria-disabled="isModeLocked('masaa')"
                            x-on:mouseenter="setHover('night')"
                            x-on:mouseleave="if (hoverSide === 'night') { resetHover(); }"
                            x-on:focus="setHover('night')"
                            x-on:blur="if (hoverSide === 'night') { resetHover(); }"
                            x-on:click="if (!isModeLocked('masaa')) { requestOpenMode('masaa') }"
                        >
                            <x-goodmaven::blurred-image
                                alt="Athkar night"
                                :imagePath="asset('images/background/night.png')"
                                :thumbnailImagePath="asset('images/background/night-blur-thumbnail.png')"
                                :isDisplayEnforced="true"
                                imageClasses="athkar-gate__image-img select-none"
                            />
                            <span
                                class="athkar-gate__label athkar-gate__label--night font-arabic-serif text-base sm:text-xl"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span x-bind:class="isModeComplete('masaa') && 'max-sm:text-[0.55rem]!'">أذكار
                                        المساء</span>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-500/90 px-2 py-0.5 text-[0.75rem] font-semibold text-white shadow"
                                        x-cloak
                                        x-show="isModeComplete('masaa')"
                                    >
                                        <span
                                            aria-hidden="true"
                                            x-bind:class="isModeComplete('masaa') && 'max-sm:text-[0.55rem]!'"
                                        >✓</span>
                                        تمّت بحمد الله
                                    </span>
                                </span>
                            </span>
                        </button>

                        <button
                            class="athkar-gate__pane athkar-gate__pane--morning"
                            type="button"
                            aria-label="أذكار الصباح"
                            x-bind:class="{ 'pointer-events-none grayscale opacity-60': isModeLocked('sabah') }"
                            x-bind:aria-disabled="isModeLocked('sabah')"
                            x-on:mouseenter="setHover('morning')"
                            x-on:mouseleave="if (hoverSide === 'morning') { resetHover(); }"
                            x-on:focus="setHover('morning')"
                            x-on:blur="if (hoverSide === 'morning') { resetHover(); }"
                            x-on:click="if (!isModeLocked('sabah')) { requestOpenMode('sabah') }"
                        >
                            <x-goodmaven::blurred-image
                                alt="Athkar morning"
                                :imagePath="asset('images/background/morning.png')"
                                :thumbnailImagePath="asset('images/background/morning-blur-thumbnail.png')"
                                :isDisplayEnforced="true"
                                imageClasses="athkar-gate__image-img select-none"
                            />
                            <span class="athkar-gate__veil athkar-gate__veil--morning"></span>
                            <span
                                class="athkar-gate__label athkar-gate__label--morning font-arabic-serif text-base sm:text-xl"
                            >
                                <span class="inline-flex items-center gap-2">
                                    <span x-bind:class="isModeComplete('sabah') && 'max-sm:text-[0.55rem]!'">أذكار
                                        الصباح</span>
                                    <span
                                        class="inline-flex items-center gap-1 rounded-full bg-emerald-500/90 px-2 py-0.5 text-[0.75rem] font-semibold text-white shadow"
                                        x-cloak
                                        x-show="isModeComplete('sabah')"
                                    >
                                        <span
                                            aria-hidden="true"
                                            x-bind:class="isModeComplete('sabah') && 'max-sm:text-[0.55rem]!'"
                                        >✓</span>
                                        تمّت بحمد الله
                                    </span>
                                </span>
                            </span>
                        </button>

                        <div
                            class="athkar-gate__divider"
                            aria-hidden="true"
                        ></div>
                        <div
                            class="athkar-gate__divider-glow"
                            aria-hidden="true"
                        ></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
