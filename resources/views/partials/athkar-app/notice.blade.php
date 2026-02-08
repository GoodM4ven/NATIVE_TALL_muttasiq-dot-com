@assets
    <style>
        .athkar-notice {
            position: relative;
            width: min(94vw, 780px);
            min-height: min(72svh, 560px);
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
            inset: 12px;
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
            padding: 1.5rem 1.75rem 2.5rem;
            padding-bottom: 3rem;
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

        .athkar-notice__subtitle {
            font-size: 0.85rem;
            color: color-mix(in srgb, var(--foreground) 65%, transparent);
        }

        .dark .athkar-notice__title {
            color: color-mix(in srgb, var(--foreground-dark) 98%, transparent);
        }

        .dark .athkar-notice__subtitle {
            color: color-mix(in srgb, var(--foreground-dark) 72%, transparent);
        }

        .athkar-notice__divider {
            height: 1px;
            width: min(80%, 360px);
            margin: 0.75rem auto 0;
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
            gap: 0.85rem;
            padding: 0.35rem 0.85rem;
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
            height: 44px;
            width: 44px;
            border-radius: 999px;
            object-fit: cover;
            border: 2px solid color-mix(in srgb, var(--primary-400) 40%, transparent);
        }

        .athkar-notice__body {
            font-size: 1rem;
            line-height: 2;
            color: color-mix(in srgb, var(--foreground) 88%, transparent);
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
    class="absolute inset-0 z-10 flex touch-pan-y select-none items-center justify-center px-4 py-6 sm:px-6"
    x-cloak
    x-show="isNoticeVisible && !isCompletionVisible && !shouldSkipNoticePanels()"
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
        class="athkar-notice relative z-10 flex max-h-[75svh] flex-col gap-4 overflow-hidden text-center sm:gap-6"
        role="dialog"
        aria-live="polite"
    >
        <div class="athkar-notice__paper">
            <div class="athkar-notice__stack flex flex-1 flex-col justify-center gap-5 sm:gap-6">
                <header class="flex flex-col items-center gap-1">
                    <span
                        class="athkar-notice__title font-arabic-serif relative -top-2 text-[1.35rem] max-sm:text-base">تنبيه</span>
                    <div
                        class="athkar-notice__divider"
                        aria-hidden="true"
                    ></div>
                </header>

                <div
                    class="athkar-notice__body font-arabic-serif"
                    {{-- data-athkar-notice-box --}}
                >
                    <p
                        class="px-5 text-[1.45rem] leading-loose max-sm:mb-0 max-sm:text-[0.875rem] sm:mt-5"
                        {{-- data-athkar-notice-text --}}
                    >
                        هذه الآيات لم يرد عن النبي صلى الله عليه وسلم أنه قالها، ولكن ورد عنه
                        أنه كان يستفتح الدعاء بالثناء، وخير الثناء ثناء الله على نفسه ولذا جمعناه
                        ووضعناه في المقدمة، لتستجاب أدعية الأذكار أتم الإجابة وليقوى حصنك وتوفيقك
                        وتيسير أمورك بإذن الله...
                    </p>
                </div>

                <div class="flex justify-center">
                    <button
                        class="athkar-notice__seal whitespace-nowrap"
                        type="button"
                        x-on:click="{{ is_platform('mobile')
                            ? 'await browser.open(`https://t.me/Ruqyah011/4730`)'
                            : 'window.open(`https://t.me/Ruqyah011/4730`, `_blank`, `noopener`)' }}"
                    >
                        <img
                            src="{{ asset('images/references/alruqya-alshariyya.jpg') }}"
                            alt="قناة الرقية الشرعية"
                            loading="lazy"
                        />
                        <div class="text-start">
                            <p class="text-xs font-semibold text-slate-900 sm:text-sm dark:text-white">
                                قناة الرقية الشرعية
                            </p>
                            <p class="text-xs text-slate-500 dark:text-slate-400">
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
                        <span class="athkar-notice__cta-text text-[1rem] max-sm:text-[0.8rem]">اضغط
                            للمتابعة</span>
                        <span class="athkar-notice__cta-subtext text-[0.75rem] max-sm:text-[0.65rem]">أو اسحب
                            للأمام
                            للبدء</span>
                    </button>
                </div>
            </div>
        </div>
    </section>
</div>
