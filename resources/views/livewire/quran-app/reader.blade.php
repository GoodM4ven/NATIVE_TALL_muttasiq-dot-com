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
            display: inline-flex;
            direction: rtl;
            align-items: baseline;
            gap: 0;
            white-space: nowrap;
            max-width: none;
        }

        .quran-word-button {
            display: inline-flex;
            align-items: baseline;
            white-space: nowrap;
            line-height: 1.02;
            cursor: default;
            transition: background-color 140ms ease;
        }

        .quran-word-button:hover,
        .quran-ayah-line:hover .quran-word-button {
            background-color: color-mix(in srgb, var(--gray-300) 42%, transparent);
        }

        .quran-ayah-marker {
            font-family: 'IBM Plex Sans Arabic', 'Manrope', ui-sans-serif, system-ui, sans-serif;
            line-height: 1;
        }

        .quran-page-lines {
            transition: opacity 300ms ease;
            opacity: 0;
            user-select: none;
            -webkit-user-select: none;
            cursor: default;
            width: max-content;
            max-width: none;
            direction: rtl;
        }

        .quran-page-lines * {
            user-select: none;
            -webkit-user-select: none;
            cursor: default;
        }

        .quran-page-lines[data-fit-state='fitting'] {
            opacity: 0;
        }

        .quran-page-lines[data-fit-state='ready'] {
            opacity: 1;
        }

        .quran-page-lines[data-fit-state='ready'] [data-quran-line] {
            animation: quran-line-reveal 440ms ease both;
            animation-delay: calc(var(--quran-line-index, 0) * 18ms);
        }

        @keyframes quran-line-reveal {
            from {
                opacity: 0;
                transform: translateY(0.42rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .quran-ayah-line-fit {
            max-width: 100%;
        }

        .quran-ayah-line-run-rect {
            font-size: calc(2.08rem * var(--quran-page-scale));
            line-height: 1.58;
        }

        .quran-ayah-line-run-centered {
            font-size: calc(2.02rem * var(--quran-page-scale));
            line-height: 1.7;
        }

        .quran-meta-line {
            font-size: calc(1.88rem * var(--quran-page-scale));
            line-height: 1.66;
        }

        .quran-page-motion-next {
            animation: quran-page-slide-next 260ms ease-out;
        }

        .quran-page-motion-prev {
            animation: quran-page-slide-prev 260ms ease-out;
        }

        .quran-top-strip {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0.8rem 1rem 0.35rem;
        }

        .quran-soorah-trigger {
            position: relative;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-height: 2.1rem;
            padding: 0.25rem 0.25rem 0.25rem 1.95rem;
            background: transparent;
            border: 0;
            color: var(--quran-panel-text);
            cursor: pointer;
            overflow: hidden;
        }

        .quran-soorah-trigger-label {
            display: inline-flex;
            transition:
                transform 240ms ease,
                opacity 240ms ease;
        }

        .quran-soorah-trigger-icon {
            position: absolute;
            inset-inline-start: 0.25rem;
            width: 1.05rem;
            height: 1.05rem;
            opacity: 0;
            transform: translateX(0.35rem) scale(0.84);
            transition:
                opacity 240ms ease,
                transform 240ms ease;
        }

        .quran-soorah-trigger:hover .quran-soorah-trigger-label {
            transform: translateX(-0.45rem);
        }

        .quran-soorah-trigger:hover .quran-soorah-trigger-icon {
            opacity: 1;
            transform: translateX(0) scale(1);
        }

        .quran-bottom-strip {
            display: grid;
            grid-template-columns: 1fr auto 1fr;
            align-items: center;
            gap: 0.65rem;
            padding: 0.5rem 1rem 0.95rem;
        }

        .quran-page-counter {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.35rem;
            min-height: 2.2rem;
            padding: 0.2rem 0.6rem;
            border-radius: 999px;
            background: color-mix(in srgb, var(--quran-chip-bg) 82%, transparent);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--quran-chip-border) 60%, transparent);
        }

        .quran-page-counter-input {
            width: 4rem;
            border: 0;
            background: transparent;
            text-align: center;
            font-weight: 600;
            color: var(--quran-panel-text);
            outline: none;
        }

        .quran-swipe-hint {
            display: inline-flex;
            align-items: center;
            gap: 0.2rem;
            color: color-mix(in srgb, var(--quran-subtle) 86%, transparent);
            opacity: 0.88;
        }

        .quran-swipe-hint-chev {
            display: inline-block;
            animation: quran-swipe-shimmer 1400ms ease-in-out infinite;
            font-size: 1rem;
            line-height: 1;
        }

        .quran-swipe-hint-chev:nth-child(2) {
            animation-delay: 110ms;
        }

        .quran-swipe-hint-chev:nth-child(3) {
            animation-delay: 220ms;
        }

        @keyframes quran-swipe-shimmer {

            0%,
            100% {
                opacity: 0.22;
                transform: translateY(0);
            }

            50% {
                opacity: 1;
                transform: translateY(-0.1rem);
            }
        }

        .quran-search-overlay {
            position: absolute;
            inset: 0;
            z-index: 60;
            background: color-mix(in srgb, var(--background) 36%, transparent);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
        }

        .quran-search-shell {
            height: 100%;
            width: 100%;
            padding: 1.2rem;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .quran-search-top {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-inline: auto;
            width: min(94vw, 42rem);
            max-width: 100%;
        }

        .quran-search-input {
            flex: 1;
            min-height: 2.55rem;
            border: 0;
            border-radius: 999px;
            background: color-mix(in srgb, var(--quran-chip-bg) 76%, transparent);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--quran-chip-border) 54%, transparent);
            color: var(--quran-panel-text);
            padding: 0 1rem;
            outline: none;
        }

        .quran-search-go {
            min-height: 2.55rem;
            border: 0;
            border-radius: 999px;
            padding: 0 1rem;
            background: color-mix(in srgb, var(--success-500) 32%, transparent);
            color: color-mix(in srgb, var(--success-700) 84%, var(--quran-panel-text));
            cursor: pointer;
            transition:
                transform 180ms ease,
                opacity 180ms ease;
        }

        .quran-search-go:hover {
            transform: translateY(-0.08rem);
        }

        .quran-search-results {
            margin-inline: auto;
            width: min(94vw, 42rem);
            max-width: 100%;
            display: grid;
            gap: 0.35rem;
        }

        .quran-search-result-btn {
            border: 0;
            border-radius: 0.85rem;
            text-align: right;
            padding: 0.7rem 0.85rem;
            background: color-mix(in srgb, var(--quran-chip-bg) 72%, transparent);
            color: var(--quran-panel-text);
            cursor: pointer;
            transition: transform 180ms ease;
        }

        .quran-search-result-btn:hover {
            transform: translateY(-0.08rem);
        }

        .quran-surah-grid {
            margin-top: 0.6rem;
            margin-inline: auto;
            width: min(94vw, 56rem);
            max-width: 100%;
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(8.1rem, 1fr));
            gap: 0.5rem;
            overflow: auto;
            padding-bottom: 1rem;
        }

        .quran-surah-tile {
            border: 0;
            border-radius: 0.95rem;
            background: color-mix(in srgb, var(--quran-chip-bg) 70%, transparent);
            color: var(--quran-panel-text);
            min-height: 5.2rem;
            padding: 0.55rem;
            text-align: center;
            cursor: pointer;
            transition:
                transform 180ms ease,
                box-shadow 180ms ease;
        }

        .quran-surah-tile:hover {
            transform: translateY(-0.1rem);
            box-shadow: inset 0 0 0 1px color-mix(in srgb, var(--quran-chip-border) 66%, transparent);
        }

        @keyframes quran-page-slide-next {
            from {
                opacity: 0.46;
                transform: translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes quran-page-slide-prev {
            from {
                opacity: 0.46;
                transform: translateY(0.5rem);
            }

            to {
                opacity: 1;
                transform: translateY(0);
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
            class="quran-reader-panel relative flex h-[clamp(28rem,82svh,50rem)] w-[min(94vw,50rem)] min-w-[18rem] flex-col items-center justify-center gap-4 rounded-[1.75rem] border px-6 py-7 text-center"
        >
            <h2 class="font-quran text-3xl leading-[1.9]">قارئ القرآن</h2>
            <p class="text-sm leading-7 opacity-85">
                بيانات المصحف غير متاحة بعد. تأكد من تجهيز جداول القرآن وبياناتها، ثم أعد فتح قسم الكتاب.
            </p>
        </section>
    @else
        <section
            class="quran-reader-panel relative flex h-[clamp(30rem,88svh,60rem)] w-[min(96vw,60rem)] min-w-[18.75rem] flex-col overflow-hidden rounded-[1.75rem] border"
            x-bind:style="readerPanelStyle()"
            x-on:pointerdown.passive="onSwipeStart($event)"
            x-on:pointerup.passive="onSwipeEnd($event)"
            x-on:pointercancel.passive="onSwipeCancel()"
            x-on:touchstart.passive="onSwipeStart($event)"
            x-on:touchend.passive="onSwipeEnd($event)"
            x-on:touchcancel.passive="onSwipeCancel()"
            x-ref="readerPanel"
        >
            <header
                class="quran-top-strip"
                data-no-swipe
            >
                <button
                    class="quran-soorah-trigger font-quran text-lg"
                    type="button"
                    x-on:click="openSearchModal()"
                >
                    <svg
                        class="quran-soorah-trigger-icon"
                        viewBox="0 0 24 24"
                        fill="none"
                        xmlns="http://www.w3.org/2000/svg"
                    >
                        <path
                            d="M10.5 4.5a6 6 0 1 1 0 12a6 6 0 0 1 0-12Zm7.5 13.5l-3.2-3.2"
                            stroke="currentColor"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            stroke-width="1.8"
                        />
                    </svg>
                    <span
                        class="quran-soorah-trigger-label"
                        x-text="currentSurahTitle()"
                    ></span>
                </button>
                <div class="min-w-[3.5rem]"></div>
            </header>

            <div
                class="min-h-0 flex-1 overflow-hidden px-3 pb-4 sm:px-4 sm:pb-5"
                x-ref="pageViewport"
            >
                <div
                    class="quran-page-surface h-full rounded-2xl px-3 py-4 transition-opacity duration-200 sm:px-4 sm:py-5"
                    x-bind:class="pageMotionClass"
                    x-ref="pageSurface"
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
                        class="mx-auto grid h-full w-fit max-w-full place-items-center overflow-hidden"
                        x-ref="pageFrame"
                    >
                        <div
                            class="quran-page-lines mx-auto space-y-8"
                            x-bind:data-fit-state="isFittingPage ? 'fitting' : 'ready'"
                            x-bind:style="pageContentStyle()"
                            x-ref="pageContent"
                        >
                            <template
                                x-for="line in mushafLines"
                                :key="`quran-line-${pageNumber}-${line.line_number}-${line.line_type}`"
                            >
                                <div
                                    data-quran-line
                                    x-bind:class="lineAlignmentClass(line)"
                                    x-bind:style="lineEntryStyle(line)"
                                >
                                    <template
                                        x-if="line.line_type === 'ayah' && Array.isArray(line.words) && line.words.length > 0"
                                    >
                                        <div
                                            data-quran-line-text
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
                                            data-quran-line-text
                                            x-bind:style="lineFontStyle()"
                                            x-text="line.text"
                                        ></div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <footer
                class="quran-bottom-strip"
                data-no-swipe
            >
                <div
                    class="quran-swipe-hint justify-self-start"
                    aria-hidden="true"
                >
                    <span class="quran-swipe-hint-chev">›</span>
                    <span class="quran-swipe-hint-chev">›</span>
                    <span class="quran-swipe-hint-chev">›</span>
                </div>
                <div class="quran-page-counter">
                    <input
                        class="quran-page-counter-input tabular-nums"
                        type="number"
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
                <div
                    class="quran-swipe-hint justify-self-end"
                    aria-hidden="true"
                >
                    <span class="quran-swipe-hint-chev">‹</span>
                    <span class="quran-swipe-hint-chev">‹</span>
                    <span class="quran-swipe-hint-chev">‹</span>
                </div>
            </footer>

            <div
                class="quran-search-overlay"
                data-no-swipe
                x-cloak
                x-show="search.modalOpen"
                x-on:keydown.escape.window="closeSearchModal()"
            >
                <div class="quran-search-shell">
                    <div class="quran-search-top">
                        <input
                            class="quran-search-input"
                            type="search"
                            placeholder="ابحث في الآيات..."
                            x-model.debounce.180ms="search.query"
                            x-on:input.debounce.180ms="updateSearchResults()"
                            x-on:keydown.enter.prevent="confirmSearchSelection()"
                            x-ref="searchModalInput"
                        >
                        <button
                            class="quran-search-go"
                            type="button"
                            x-cloak
                            x-show="search.readyResult !== null"
                            x-transition.opacity.duration.180ms
                            x-on:click="confirmSearchSelection()"
                        >اذهب</button>
                        <button
                            class="quran-search-go"
                            type="button"
                            x-on:click="closeSearchModal()"
                        >إغلاق</button>
                    </div>

                    <div
                        class="quran-search-results"
                        x-cloak
                        x-show="search.query.length > 0 && search.results.length > 1"
                        x-transition:enter="transition duration-260 ease-out"
                        x-transition:enter-start="opacity-0 translate-y-2"
                        x-transition:enter-end="opacity-100 translate-y-0"
                    >
                        <template
                            x-for="result in search.results"
                            :key="`quran-search-modal-${result.id}`"
                        >
                            <button
                                class="quran-search-result-btn"
                                type="button"
                                x-on:click="goToSearchResult(result)"
                            >
                                <span
                                    class="block text-xs opacity-80"
                                    x-text="surahLabel(result.surah_number) + ' · آية ' + result.ayah_number + ' · صفحة ' + result.page_number"
                                ></span>
                                <span
                                    class="font-quran block pt-1 text-lg leading-8"
                                    x-text="result.text_uthmani"
                                ></span>
                            </button>
                        </template>
                    </div>

                    <div
                        class="quran-surah-grid"
                        x-cloak
                        x-show="search.query.length > 0 && search.results.length > 1"
                        x-transition:enter="transition duration-260 ease-out"
                        x-transition:enter-start="opacity-0 translate-y-3"
                        x-transition:enter-end="opacity-100 translate-y-0"
                    >
                        <template
                            x-for="entry in search.surahDirectory"
                            :key="`quran-surah-tile-${entry.surah_number}`"
                        >
                            <button
                                class="quran-surah-tile"
                                type="button"
                                x-on:click="goToSurahFromDirectory(entry)"
                            >
                                <span
                                    class="block text-xs opacity-70"
                                    x-text="'#' + entry.surah_number"
                                ></span>
                                <span
                                    class="font-quran block pt-2 text-base leading-7"
                                    x-text="entry.label"
                                ></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>

            <div
                class="pointer-events-none fixed left-[-200vw] top-0 opacity-0"
                aria-hidden="true"
            >
                <div
                    class="space-y-2.5"
                    style="width: max-content;"
                    x-ref="pageThreeProbe"
                >
                    <template
                        x-for="line in panelProbeLines"
                        :key="`quran-probe-line-${line.line_number}-${line.line_type}`"
                    >
                        <div
                            x-bind:class="probeLineAlignmentClass(line)"
                            x-bind:style="lineEntryStyle(line)"
                        >
                            <template
                                x-if="line.line_type === 'ayah' && Array.isArray(line.words) && line.words.length > 0"
                            >
                                <div
                                    data-quran-line-text
                                    x-bind:class="probeAyahLineClass(line)"
                                    x-bind:style="probeLineFontStyle()"
                                >
                                    <template
                                        x-for="(word, wordIndex) in line.words"
                                        :key="`quran-probe-word-${line.line_number}-${word.word_index ?? wordIndex}`"
                                    >
                                        <span class="inline-flex items-baseline">
                                            <span
                                                class="quran-word-button rounded-sm px-0"
                                                x-text="word.text"
                                            ></span>
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
                                    data-quran-line-text
                                    x-bind:style="probeLineFontStyle()"
                                    x-text="line.text"
                                ></div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </section>
    @endif
</div>
