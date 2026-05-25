@assets
    <style>
        .athkar-notice {
            position: relative;
            border-radius: 26px;
            padding: 0;
            box-shadow: none;
            text-align: center;
            isolation: isolate;
            color: var(--foreground);
        }

        .dark .athkar-notice {
            border-color: color-mix(in srgb, var(--primary-400) 35%, transparent);
            box-shadow: none;
            color: color-mix(in srgb, var(--foreground-dark) 92%, transparent);
        }

        .athkar-notice::before {
            content: "";
            position: absolute;
            border-radius: 20px;
            border: 1px solid color-mix(in srgb, var(--primary-400) 25%, transparent);
            background:
                linear-gradient(140deg,
                    color-mix(in srgb, var(--background) 92%, transparent),
                    color-mix(in srgb, var(--background-dark) 14%, transparent));
            opacity: 0.85;
            z-index: 0;
        }

        .dark .athkar-notice::before {
            border-color: color-mix(in srgb, var(--primary-300) 30%, transparent);
            background:
                linear-gradient(140deg,
                    color-mix(in srgb, var(--background-dark) 30%, transparent),
                    color-mix(in srgb, var(--background) 10%, transparent));
            opacity: 0.7;
        }

        .athkar-notice__paper {
            position: relative;
            z-index: 1;
            border-radius: 18px;
            background:
                linear-gradient(180deg,
                    color-mix(in srgb, var(--background-dark) 3%, transparent),
                    color-mix(in srgb, var(--background) 10%, transparent)),
                repeating-linear-gradient(135deg,
                    transparent 0,
                    transparent 16px,
                    color-mix(in srgb, var(--primary-200) 12%, transparent) 16px,
                    color-mix(in srgb, var(--primary-200) 12%, transparent) 18px);
            box-shadow: none;
            min-height: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            flex-grow: 1;
        }

        .dark .athkar-notice__paper {
            background:
                linear-gradient(180deg,
                    color-mix(in srgb, var(--background) 20%, transparent),
                    color-mix(in srgb, var(--background-dark) 26%, transparent)),
                repeating-linear-gradient(135deg,
                    transparent 0,
                    transparent 16px,
                    color-mix(in srgb, var(--primary-600) 18%, transparent) 16px,
                    color-mix(in srgb, var(--primary-600) 18%, transparent) 18px);
            box-shadow: none;
        }

        .athkar-notice__title {
            font-weight: 700;
            color: var(--foreground);
            letter-spacing: 0.02em;
        }

        .dark .athkar-notice__title {
            color: color-mix(in srgb, var(--foreground-dark) 98%, transparent);
        }

        .athkar-notice__divider {
            height: 1px;
            width: min(80%, 360px);
            background: linear-gradient(90deg,
                    transparent,
                    color-mix(in srgb, var(--primary-500) 35%, transparent),
                    transparent);
        }

        .dark .athkar-notice__divider {
            background: linear-gradient(90deg,
                    transparent,
                    color-mix(in srgb, var(--primary-200) 35%, transparent),
                    transparent);
        }

        .athkar-notice__seal {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            border-radius: 999px;
            /* border-bottom-left-radius: 1px; */
            /* border-top-right-radius: 1rem; */
            /* border-bottom-right-radius: 1rem; */
            border: 1px solid color-mix(in srgb, var(--primary-500) 25%, transparent);
            background: color-mix(in srgb, var(--background) 88%, transparent);
            color: inherit;
            text-decoration: none;
            transition: transform 300ms ease, box-shadow 300ms ease;
            max-width: 320px;
            position: relative;
        }

        .dark .athkar-notice__seal {
            border: 1px solid color-mix(in srgb, var(--primary-200) 25%, transparent);
        }

        .athkar-notice__seal:hover {
            transform: translateY(-1px);
            box-shadow: 0 14px 24px color-mix(in srgb, var(--primary-500) 18%, transparent);
        }

        .athkar-notice__seal::before,
        .athkar-notice__seal::after {
            content: "";
            flex: 1;
            height: 1px;
            min-width: 24px;
            background: linear-gradient(90deg,
                    transparent,
                    color-mix(in srgb, var(--primary-400) 40%, transparent),
                    transparent);
            opacity: 0.6;
        }

        .dark .athkar-notice__seal {
            border-color: color-mix(in srgb, var(--primary-300) 40%, transparent);
            background: color-mix(in srgb, var(--background-dark) 75%, transparent);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--primary-300) 18%, transparent);
        }

        .athkar-notice__seal img {
            border-radius: 999px;
            object-fit: cover;
            border: 2px solid color-mix(in srgb, var(--primary-400) 40%, transparent);
        }

        .athkar-notice__body {
            position: relative;
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            overflow: hidden;
            align-items: center;
            justify-content: center;
            padding-inline: 1.25rem;
            color: color-mix(in srgb, var(--foreground) 88%, transparent);
            direction: rtl;
        }

        .athkar-notice__body-copy {
            width: 100%;
            margin: 0;
            line-height: 2;
            white-space: break-spaces;
        }

        .dark .athkar-notice__body {
            color: color-mix(in srgb, var(--foreground-dark) 85%, transparent);
        }

        .athkar-notice__footer {
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .athkar-notice__cta {
            display: inline-flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            border: none;
            background: transparent;
            color: inherit;
            cursor: pointer;
        }

        .athkar-notice__cta-text {
            font-weight: 700;
            color: var(--primary-600);
            animation: athkar-notice-blink 1.25s ease-in-out infinite;
        }

        .dark .athkar-notice__cta-text {
            color: var(--primary-200);
        }

        .athkar-notice__cta-subtext {
            color: color-mix(in srgb, var(--foreground) 65%, transparent);
        }

        .dark .athkar-notice__cta-subtext {
            color: color-mix(in srgb, var(--foreground-dark) 65%, transparent);
        }

        @keyframes athkar-notice-blink {

            0%,
            100% {
                opacity: 0.4;
                transform: translateY(1px);
            }

            50% {
                opacity: 1;
                transform: translateY(0);
            }
        }
    </style>
@endassets

<div
    class="absolute inset-0 z-10 flex touch-pan-y select-none items-center justify-center px-2 py-5 sm:px-6"
    x-cloak
    x-show="isNoticeVisible && !isCompletionVisible && !shouldSkipGuidancePanels()"
    x-transition:enter="transition-all ease-out duration-600 delay-150"
    x-transition:enter-start="opacity-0! blur-[2px]"
    x-transition:enter-end="opacity-100 blur-0"
    x-transition:leave="transition-all ease-in duration-300"
    x-transition:leave-start="opacity-100 blur-0"
    x-transition:leave-end="opacity-0! blur-[2px]"
    x-on:pointerdown="swipeStart($event)"
    x-on:pointerup="swipeEnd($event)"
    x-on:pointercancel="swipeCancel()"
    x-on:touchstart="swipeStart($event)"
    x-on:touchend="swipeEnd($event)"
    x-on:touchcancel="swipeCancel()"
    x-on:transitionend.self="if (isNoticeVisible) { queueTextFit() }"
>
    <!-- Background Pattern -->
    <!-- Credits: https://heropatterns.com -->
    <div
        class="pointer-events-none fixed inset-0 z-0 animate-pulse"
        style="
                    background-image:url('data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2728%27 height=%2749%27 viewBox=%270 0 28 49%27%3E%3Cg fill-rule=%27evenodd%27%3E%3Cg id=%27hexagons%27 fill=%27%239C92AC%27 fill-opacity=%270.05%27 fill-rule=%27nonzero%27%3E%3Cpath d=%27M13.99 9.25l13 7.5v15l-13 7.5L1 31.75v-15l12.99-7.5zM3 17.9v12.7l10.99 6.34 11-6.35V17.9l-11-6.34L3 17.9zM0 15l12.98-7.5V0h-2v6.35L0 12.69v2.3zm0 18.5L12.98 41v8h-2v-6.85L0 35.81v-2.3zM15 0v7.5L27.99 15H28v-2.31h-.01L17 6.35V0h-2zm0 49v-8l12.99-7.5H28v2.31h-.01L17 42.15V49h-2z%27/%3E%3C/g%3E%3C/g%3E%3C/svg%3E');
                "
    ></div>

    <!-- Panel -->
    <section
        class="athkar-notice 3xl:max-w-[min(65vw,45rem)] 4xl:max-w-[min(70vw,50rem)] 3xl:max-h-[min(70svh,30rem)] 4xl:max-h-[min(84svh,42rem)] sm:zoom-[1.5] md:zoom-[1.0] relative z-10 mt-4 flex w-full max-w-[min(92vw,36rem)] flex-col gap-4 overflow-hidden text-center before:inset-[12px] sm:mt-0 sm:max-h-[min(63svh,33rem)] sm:max-w-[min(53vw,32rem)] sm:gap-5 sm:before:inset-[6px] md:max-h-[min(60svh,30rem)] md:max-w-[min(54vw,33rem)] md:before:inset-[8px] lg:max-h-[min(50svh,30rem)] lg:max-w-[min(50vw,33rem)] lg:before:inset-[10px] xl:max-h-[min(50svh,18.75rem)] xl:max-w-[min(50vw,31rem)] xl:before:inset-[12px] 2xl:max-h-[min(60svh,24rem)] 2xl:max-w-[min(65vw,37rem)]"
        role="dialog"
        aria-live="polite"
    >
        <div
            class="athkar-notice__paper lg:pb-7.5 px-4 pb-9 pt-6 sm:px-4 sm:pb-6 sm:pt-6 md:px-5 md:pb-7 md:pt-6 lg:px-6 lg:pt-8 xl:px-5 xl:pb-7 xl:pt-6 2xl:px-7 2xl:pb-12 2xl:pt-6">
            <div
                class="athkar-notice__stack grid min-h-0 flex-1 grid-rows-[auto_minmax(0,1fr)_auto_auto] gap-3 sm:gap-1.5 md:gap-2 lg:gap-2 xl:gap-2 2xl:gap-4">
                <header class="flex flex-col items-center gap-1">
                    <span
                        class="athkar-notice__title font-arabic-serif 3xl:text-[1.3rem] 4xl:text-[1.45rem] relative -top-2 text-[0.9rem] sm:text-[0.7rem] md:text-[0.75rem] lg:text-[0.75rem] xl:text-[0.85rem] 2xl:pt-2 2xl:text-[1rem]"
                    >تنبيه</span>
                    <div
                        class="athkar-notice__divider mx-auto mt-1 sm:mt-0 md:mt-0 lg:mt-0 xl:mt-0 2xl:mt-3"
                        aria-hidden="true"
                    ></div>
                </header>

                <div
                    class="athkar-notice__body font-arabic-serif py-2"
                    data-fitty-box
                >
                    {{-- blade-formatter-disable --}}
                    <p
                        class="athkar-notice__body-copy max-sm:text-[0.8rem]! sm:max-md:text-[0.61rem]! md:max-lg:text-[0.725rem]! my-auto"
                        data-fitty-target
                        data-fitty-enabled="false"
                        data-fitty-step="0.5"
                        data-fitty-safe-padding-x="2"
                        data-fitty-safe-padding-y="2"
                        x-bind:data-fitty-enabled="(isNoticeVisible && !isCompletionVisible && !shouldSkipGuidancePanels() && $store.bp?.is?.('md+')).toString()"
                        x-bind:data-fitty-safe-padding-y="$store.bp?.is?.('md+') ? 24 : 2"
                        x-bind:data-fitty-min-size-override="$store.bp?.is?.('4xl') ? 23 : $store.bp?.is?.('3xl') ? 18 : $store.bp?.is?.('2xl') ? 15 : $store.bp?.is?.('xl') ? 4 : $store.bp?.is?.('lg') ? 9 : $store.bp?.is?.('md') ? 7 : $store.bp?.is?.('sm') ? 15 : 14"
                        x-bind:data-fitty-max-size-override="$store.bp?.is?.('4xl') ? 27 : $store.bp?.is?.('3xl') ? 20 : $store.bp?.is?.('2xl') ? 19 : $store.bp?.is?.('xl') ? 11 : $store.bp?.is?.('lg') ? 18 : $store.bp?.is?.('md') ? 14 : $store.bp?.is?.('sm') ? 27 : 24"
                    >{{ arabic_text('معظمُ الآياتِ هذه في البدايةِ لم يردْ عن النبيِّ صلى الله عليه وسلم أنه قالها — كأذكارٍ للصباحِ والمساء — ولكن ورد عنه أنه كان يستفتح الدعاءَ بالثناء، وخيرُ الثناءِ ثناءُ اللهِ على نفسه، ولذا جمعناه ووضعناه في المقدمة، لتُستجابَ أدعيةُ الأذكارِ أتمَّ الإجابة، وليقوى حصنك وتوفيقك وتيسيرُ أمورك بإذن الله...') }}</p>
                    {{-- blade-formatter-enable --}}
                </div>

                <div class="flex justify-center">
                    <button
                        class="athkar-notice__seal 3xl:py-[0.35rem] gap-[0.4rem] whitespace-nowrap px-[0.2rem] py-[0.3rem] sm:gap-[0.225rem] sm:px-[0.225rem] sm:py-[0.2rem] md:gap-[0.2rem] md:px-[0.3rem] md:py-[0.225rem] lg:gap-[0.35rem] lg:px-[0.4rem] lg:py-[0.15rem] xl:gap-[0.85rem] xl:px-[0.65rem] xl:py-[0.35rem] 2xl:px-[0.85rem] 2xl:py-1"
                        type="button"
                        x-on:click="{{ open_link_native_aware('https://t.me/Ruqyah011/4730') }}"
                    >
                        <img
                            class="3xl:h-[44px] h-[28px] sm:h-[22px] md:h-[25px] lg:h-[27px] xl:h-[30px] 2xl:h-[36px]"
                            src="{{ asset('images/references/alruqya-alshariyya.jpg') }}"
                            alt="قناة الرقية الشرعية"
                            loading="lazy"
                        />
                        <div class="text-start">
                            <p
                                class="3xl:text-[0.9rem] text-[0.55rem] font-semibold text-slate-900 sm:text-[0.4rem] md:text-[0.45rem] lg:text-[0.5rem] xl:text-[0.625rem] 2xl:text-[0.7rem] dark:text-white">
                                قناة الرقية الشرعية
                            </p>
                            <p
                                class="3xl:text-[0.82rem] text-[0.45rem] text-slate-500 sm:text-[0.35rem] md:text-[0.4rem] lg:text-[0.45rem] xl:text-[0.575rem] 2xl:text-[0.65rem] dark:text-slate-400">
                                t.me/Ruqyah011
                            </p>
                        </div>
                    </button>
                </div>

                <div class="athkar-notice__footer">
                    <button
                        class="athkar-notice__cta"
                        type="button"
                        x-on:click="confirmNotice()"
                    >
                        <span
                            class="athkar-notice__cta-text 3xl:text-[0.9rem] 4xl:text-[1.08rem] text-[0.7rem] sm:text-[0.45rem] md:text-[0.5rem] lg:text-[0.55rem] xl:text-[0.65rem] 2xl:text-[0.7rem]"
                        >اضغط
                            للمتابعة</span>
                        <span
                            class="athkar-notice__cta-subtext 3xl:text-[0.7rem] 4xl:text-[0.82rem] inline-flex flex-wrap items-center justify-center gap-1 text-[0.65rem] sm:text-[0.4rem] md:text-[0.45rem] lg:text-[0.45rem] xl:text-[0.55rem] 2xl:text-[0.65rem]"
                        >
                            <span>{{ arabic_text('أو اسحب للأمام للبدء') }}</span>
                            <span
                                class="decoration-current/70 inline-flex cursor-pointer items-center justify-center underline underline-offset-2 transition hover:opacity-85"
                                role="button"
                                tabindex="0"
                                x-on:click.stop="confirmNoticeAndBypassFutureDisplay()"
                                x-on:keydown.enter.stop.prevent="confirmNoticeAndBypassFutureDisplay()"
                                x-on:keydown.space.stop.prevent="confirmNoticeAndBypassFutureDisplay()"
                            >{{ arabic_text('أو لا تظهر هذا مجدّدًا.') }}</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>
