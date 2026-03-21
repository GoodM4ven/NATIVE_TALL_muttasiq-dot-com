@assets
    <style>
        @font-face {
            font-family: 'MadinaQuran';
            src: url('/vendor/arabicable/madina.woff2') format('woff2');
            font-display: swap;
        }

        .quran-reader {
            --quran-panel-bg: color-mix(in srgb, var(--background) 92%, transparent);
            --quran-panel-border: color-mix(in srgb, var(--primary-500) 42%, transparent);
            --quran-panel-shadow: 0 22px 40px color-mix(in srgb, var(--gray-900) 18%, transparent);
            --quran-panel-text: var(--primary-950);
            --quran-ink: color-mix(in srgb, var(--primary-950) 94%, var(--gray-900));
            --quran-subtle: color-mix(in srgb, var(--primary-700) 68%, var(--gray-500));
            --quran-chip-bg: color-mix(in srgb, var(--background-dark) 66%, transparent);
            --quran-chip-border: color-mix(in srgb, var(--primary-500) 35%, transparent);
            --quran-chip-hover: color-mix(in srgb, var(--primary-500) 16%, transparent);
            --quran-active-bg: color-mix(in srgb, var(--success-500) 24%, transparent);
            --quran-active-text: color-mix(in srgb, var(--success-600) 82%, var(--primary-900));
            --quran-page-surface: color-mix(in srgb, var(--background) 86%, transparent);
            --quran-page-border: color-mix(in srgb, var(--gray-300) 58%, transparent);
            --quran-page-scale: 1;
        }

        .dark .quran-reader {
            --quran-panel-bg: color-mix(in srgb, var(--primary-200) 24%, transparent);
            --quran-panel-border: color-mix(in srgb, var(--primary-300) 42%, transparent);
            --quran-panel-shadow: 0 24px 44px color-mix(in srgb, var(--gray-950) 62%, transparent);
            --quran-panel-text: var(--primary-50);
            --quran-ink: color-mix(in srgb, var(--primary-50) 94%, white);
            --quran-subtle: color-mix(in srgb, var(--primary-100) 72%, var(--gray-300));
            --quran-chip-bg: color-mix(in srgb, var(--gray-950) 44%, transparent);
            --quran-chip-border: color-mix(in srgb, var(--primary-200) 42%, transparent);
            --quran-chip-hover: color-mix(in srgb, var(--primary-300) 25%, transparent);
            --quran-active-bg: color-mix(in srgb, var(--success-400) 28%, transparent);
            --quran-active-text: color-mix(in srgb, var(--success-200) 82%, white);
            --quran-page-surface: color-mix(in srgb, var(--gray-950) 44%, transparent);
            --quran-page-border: color-mix(in srgb, var(--gray-700) 65%, transparent);
        }

        .quran-reader-panel {
            color: var(--quran-panel-text);
            background: var(--quran-panel-bg);
            border-color: var(--quran-panel-border);
            box-shadow: var(--quran-panel-shadow);
        }

        .quran-page-surface {
            background: var(--quran-page-surface);
            border-color: var(--quran-page-border);
        }

        .font-quran {
            font-family: 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;
        }

        .quran-ayah-line-run {
            display: flex;
            width: 100%;
            direction: rtl;
            align-items: baseline;
            gap: 0;
            white-space: nowrap;
        }

        .quran-ayah-line-run-rect {
            justify-content: flex-start;
        }

        .quran-ayah-line-run-centered {
            justify-content: center;
        }

        .quran-word-button {
            display: inline-flex;
            align-items: baseline;
            white-space: nowrap;
            line-height: 1;
        }

        .quran-ayah-marker {
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            line-height: 1;
        }

        .quran-page-lines {
            container-type: inline-size;
        }

        .quran-ayah-line-fit {
            max-width: 100%;
        }

        .quran-ayah-line-run-rect {
            font-size: calc(min(2.08rem, 6.45cqw) * var(--quran-page-scale));
            line-height: 1.42;
        }

        .quran-ayah-line-run-centered {
            font-size: calc(min(2.02rem, 6.1cqw) * var(--quran-page-scale));
            line-height: 1.72;
        }

        .quran-meta-line {
            font-size: calc(min(1.9rem, 5.5cqw) * var(--quran-page-scale));
            line-height: 1.8;
        }

        .quran-page-motion-next {
            animation: quran-page-slide-next 260ms ease-out;
        }

        .quran-page-motion-prev {
            animation: quran-page-slide-prev 260ms ease-out;
        }

        @keyframes quran-page-slide-next {
            from {
                opacity: 0.46;
                transform: translateX(-1.05rem);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        @keyframes quran-page-slide-prev {
            from {
                opacity: 0.46;
                transform: translateX(1.05rem);
            }

            to {
                opacity: 1;
                transform: translateX(0);
            }
        }
    </style>
@endassets

@php
    $initialReaderPayload = [
        'ready' => $ready,
        'pageNumber' => $pageNumber,
        'maxPage' => $maxPage,
        'activeAyahIndex' => $activeAyahIndex,
        'mushafLines' => $mushafLines,
        'qpcPageFontFamily' => $qpcPageFontFamily,
        'qpcPageFontUrl' => $qpcPageFontUrl,
        'qpcPageFontFormat' => $qpcPageFontFormat,
        'useCenteredAyahLayout' => $useCenteredAyahLayout,
    ];
@endphp

<div
    class="quran-reader relative grid h-full w-full place-items-center"
    dir="rtl"
    x-data="quranAppReader({
        api: {
            pageDataTemplate: @js(url('/quran-reader/pages/__PAGE__.json')),
            searchIndexUrl: @js(url('/quran-reader/search-index.json')),
        },
        initialPayload: @js($initialReaderPayload),
        nativeRuntime: @js(is_platform('native')),
        prewarmPages: @js(is_platform('native') ? 12 : 6),
        prefetchRadius: @js(is_platform('native') ? 3 : 2),
    })"
>
    @if (!$ready)
        <section
            class="quran-reader-panel relative flex h-[68svh] min-h-[26rem] w-full max-w-[34rem] flex-col items-center justify-center gap-4 rounded-[1.75rem] border px-6 py-7 text-center"
        >
            <h2 class="font-quran text-3xl leading-[1.9]">قارئ القرآن</h2>
            <p class="text-sm leading-7 opacity-85">
                بيانات المصحف غير متاحة بعد. تأكد من تجهيز جداول القرآن وبياناتها، ثم أعد فتح قسم الكتاب.
            </p>
        </section>
    @else
        <section
            class="quran-reader-panel relative flex h-[76svh] min-h-[31rem] w-full max-w-[35rem] flex-col overflow-hidden rounded-[1.75rem] border"
        >
            <header class="grid grid-cols-[auto_1fr_auto] items-center gap-2 px-3 py-3 sm:px-4 sm:py-4">
                <div class="flex items-center gap-2">
                    <button
                        class="rounded-xl border px-3 py-2 text-xs font-semibold transition sm:px-4"
                        type="button"
                        style="border-color: var(--quran-chip-border); background: var(--quran-chip-bg);"
                        x-on:click="nextPage()"
                    >
                        التالي
                    </button>
                </div>

                <div class="relative grid place-items-center gap-2 text-center">
                    <p
                        class="text-xs font-semibold"
                        style="color: var(--quran-subtle);"
                    >الصفحة</p>
                    <div class="flex items-center justify-center gap-2">
                        <input
                            class="w-[4.5rem] rounded-lg border bg-transparent px-2 py-1 text-center text-sm tabular-nums outline-none transition focus:ring-2"
                            type="number"
                            style="border-color: var(--quran-chip-border);"
                            x-model.number="pageInput"
                            x-on:change="onPageInputCommit()"
                            x-on:keydown.enter.prevent="onPageInputCommit()"
                            x-bind:max="Math.max(1, maxPage)"
                            min="1"
                        >
                        <span
                            class="text-xs tabular-nums"
                            style="color: var(--quran-subtle);"
                            x-text="'/' + Math.max(1, maxPage)"
                        ></span>
                    </div>

                    <div class="relative w-full max-w-[11rem]">
                        <input
                            class="w-full rounded-lg border bg-transparent px-2 py-1.5 text-right text-xs outline-none transition focus:ring-2 sm:text-sm"
                            type="search"
                            style="border-color: var(--quran-chip-border);"
                            placeholder="بحث في الآيات"
                            x-model.debounce.180ms="search.query"
                            x-on:input.debounce.180ms="updateSearchResults()"
                            x-on:focus="if (search.results.length > 0) search.isOpen = true"
                            x-on:keydown.enter.prevent="if (search.results.length > 0) goToSearchResult(search.results[0])"
                        >
                        <div
                            class="absolute inset-x-0 top-[calc(100%+0.3rem)] z-40 max-h-56 overflow-y-auto rounded-lg border shadow-lg"
                            style="border-color: var(--quran-chip-border); background: var(--quran-panel-bg);"
                            x-cloak
                            x-show="search.isOpen"
                            x-on:click.outside="search.isOpen = false"
                        >
                            <template
                                x-for="result in search.results"
                                :key="`quran-search-${result.id}`"
                            >
                                <button
                                    class="block w-full border-b px-3 py-2 text-right text-xs transition hover:bg-black/5 sm:text-sm"
                                    type="button"
                                    style="border-color: color-mix(in srgb, var(--quran-chip-border) 45%, transparent);"
                                    x-on:click="goToSearchResult(result)"
                                >
                                    <span
                                        class="block font-semibold"
                                        x-text="'سورة ' + result.surah_number + ' · آية ' + result.ayah_number + ' · صفحة ' + result.page_number"
                                    ></span>
                                    <span
                                        class="font-quran block pt-1 leading-8"
                                        x-text="result.text_uthmani"
                                    ></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2">
                    <button
                        class="rounded-xl border px-3 py-2 text-xs font-semibold transition sm:px-4"
                        type="button"
                        style="border-color: var(--quran-chip-border); background: var(--quran-chip-bg);"
                        x-on:click="previousPage()"
                    >
                        السابق
                    </button>
                    <button
                        class="rounded-xl border px-3 py-2 text-xs font-semibold transition sm:px-4"
                        type="button"
                        style="border-color: var(--quran-chip-border); background: var(--quran-chip-bg);"
                        x-data
                        x-on:click="$viewNav('quran-app-gate')"
                    >
                        القائمة
                    </button>
                </div>
            </header>

            <div
                class="min-h-0 flex-1 overflow-hidden px-3 pb-4 sm:px-4 sm:pb-5"
                x-on:pointerdown.passive="onSwipeStart($event)"
                x-on:pointerup.passive="onSwipeEnd($event)"
                x-ref="pageViewport"
            >
                <div
                    class="quran-page-surface rounded-2xl border px-3 py-4 transition-opacity duration-200 sm:px-4 sm:py-5"
                    x-bind:class="pageMotionClass"
                >
                    @if ($qpcPageFontFamily !== null && $qpcPageFontUrl !== null && $qpcPageFontFormat !== null)
                        <style>
                            @font-face {
                                font-family: '{{ $qpcPageFontFamily }}';
                                src: url('{{ $qpcPageFontUrl }}') format('{{ $qpcPageFontFormat }}');
                                font-display: block;
                            }
                        </style>
                    @endif

                    <div
                        class="quran-page-lines mx-auto w-full max-w-full space-y-2"
                        data-fitty-box
                        x-bind:style="pageContentStyle()"
                        x-ref="pageContent"
                    >
                        <template
                            x-for="line in mushafLines"
                            :key="`quran-line-${pageNumber}-${line.line_number}-${line.line_type}`"
                        >
                            <div x-bind:class="lineAlignmentClass(line)">
                                <template
                                    x-if="line.line_type === 'ayah' && Array.isArray(line.words) && line.words.length > 0"
                                >
                                    <div
                                        data-fitty-target
                                        data-fitty-min-size-override="12"
                                        data-fitty-max-size-override="38"
                                        data-fitty-step="0.25"
                                        data-fitty-safe-padding-x="1"
                                        data-fitty-safe-padding-y="0"
                                        x-bind:class="ayahLineClass(line)"
                                        x-bind:style="lineFontStyle()"
                                    >
                                        <template
                                            x-for="(word, wordIndex) in line.words"
                                            :key="`quran-word-${pageNumber}-${line.line_number}-${word.word_index ?? wordIndex}`"
                                        >
                                            <span class="inline-flex items-baseline">
                                                <button
                                                    class="quran-word-button rounded-sm px-0 transition"
                                                    type="button"
                                                    x-bind:class="{ 'quran-segment-active': isWordActive(word) }"
                                                    x-bind:style="wordStyle(word)"
                                                    x-bind:disabled="!(Number(word?.ayah_index ?? 0) > 0)"
                                                    x-on:click="selectAyah(Number(word?.ayah_index ?? 0))"
                                                    x-text="word.text"
                                                ></button>
                                                <template x-if="showAyahMarker(word)">
                                                    <span
                                                        class="quran-ayah-marker mr-0.5 text-[0.92rem]"
                                                        style="color: var(--quran-subtle);"
                                                        x-text="'۝' + word.ayah_number"
                                                    ></span>
                                                </template>
                                            </span>
                                        </template>
                                    </div>
                                </template>
                                <template
                                    x-if="!(line.line_type === 'ayah' && Array.isArray(line.words) && line.words.length > 0)"
                                >
                                    <div
                                        class="font-quran quran-meta-line"
                                        data-fitty-target
                                        data-fitty-min-size-override="18"
                                        data-fitty-max-size-override="42"
                                        data-fitty-step="0.25"
                                        x-bind:style="lineFontStyle()"
                                        x-text="line.text"
                                    ></div>
                                </template>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </section>
    @endif
</div>
