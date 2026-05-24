<div
    class="quran-search-shell select-none"
    data-no-swipe
>
    <div class="quran-search-results-shell mt-2 sm:mt-3">
        <label
            class="quran-search-field-wrapper flex flex-col gap-2"
            for="quran-reader-search-input"
        >
            <span class="sr-only">{{ arabic_text('ابحث في القرآن') }}</span>

            <div class="quran-search-input-shell relative">
                <input
                    class="quran-search-input block w-full rounded-2xl border border-transparent bg-transparent px-4 py-[0.9rem] text-right text-[0.95rem] leading-7 text-(--quran-panel-text) outline-none sm:px-5 sm:py-4 sm:text-[1rem]"
                    id="quran-reader-search-input"
                    type="text"
                    x-ref="searchModalInput"
                    x-model="search.query"
                    dir="rtl"
                    inputmode="search"
                    autocomplete="off"
                    autocapitalize="off"
                    spellcheck="false"
                    placeholder="{{ arabic_text('اسم سورة أو جزء من آية...') }}"
                    x-on:keydown.enter.prevent="void confirmSearchSelection()"
                >

                <button
                    class="quran-search-clear-btn absolute inset-y-0 left-2 my-auto inline-flex h-9 w-9 items-center justify-center rounded-full text-[0.85rem] font-bold"
                    type="button"
                    aria-label="{{ arabic_text('مسح البحث') }}"
                    x-cloak
                    x-show="String(search.query ?? '').trim() !== ''"
                    x-on:click="search.query = ''; queueSearchResultsUpdate(0); $nextTick(() => searchModalInputElement()?.focus?.())"
                >x</button>
            </div>
        </label>

        <div
            class="sr-only hidden"
            data-quran-search-stream-target
            wire:stream="quran-search-results-stream"
            x-ref="searchResultsStream"
        ></div>

        <p
            class="quran-search-feedback mt-2"
            x-cloak
            x-show="normalizeSearchQuery(search.query).length > 0 && normalizeSearchQuery(search.query).length < search.minQueryLength"
            x-transition.opacity.duration.220ms
            x-text="`${search.minQueryLength} {{ arabic_text('أحرف أو أكثر ليبدأ البحث.') }}`"
        ></p>

        <p
            class="quran-search-feedback mt-2"
            x-cloak
            x-show="shouldShowSearchNoResults()"
            x-transition.opacity.duration.220ms
        >
            {{ arabic_text('لا توجد نتائج مطابقة.') }}
        </p>

        <div
            class="quran-search-chunks"
            x-cloak
            x-ref="searchResultsList"
            x-show="search.results.length > 0"
            x-transition.opacity.duration.220ms
        >
            <template
                x-for="chunk in searchResultChunks()"
                x-bind:key="`quran-search-chunk-${chunk.key}`"
            >
                <section class="quran-search-chunk">
                    <header class="quran-search-chunk__header">
                        <p
                            class="quran-search-chunk__title"
                            x-text="chunk.label"
                        ></p>
                        <p
                            class="quran-search-chunk__count"
                            dir="ltr"
                            x-text="String(chunk.results.length)"
                        ></p>
                    </header>

                    <div class="quran-search-results">
                        <template
                            x-for="(result, resultIndex) in chunk.results"
                            x-bind:key="result.__key ||
                                `quran-search-modal-${result.id || [result.surah_number, result.ayah_number, result.page_number, result.match_rank].join('-')}`"
                        >
                            <button
                                class="quran-search-result-btn"
                                type="button"
                                x-bind:data-match-tone="searchMatchTone(result)"
                                x-bind:data-result-key="result.__key || ''"
                                x-bind:tabindex="searchResultIsLeaving(result) ? -1 : (resultIndex === 0 ? 0 : -1)"
                                x-bind:class="{
                                    'quran-search-result-btn--active': !searchResultIsLeaving(result),
                                    'quran-search-result-btn--leaving': searchResultIsLeaving(result),
                                    'quran-search-result-btn--surah-name': isSurahNameSearchResult(result),
                                }"
                                x-on:click="if (!searchResultIsLeaving(result)) { goToSearchResult(result) }"
                            >
                                <span
                                    class="quran-search-result-meta"
                                    x-text="searchResultMetaLabel(result)"
                                ></span>
                                <span
                                    class="quran-search-result-ayah font-quran"
                                    x-text="searchResultAyahText(result)"
                                ></span>
                                <span
                                    class="quran-search-result-match-badge"
                                    x-bind:data-match-tone="searchMatchTone(result)"
                                    x-bind:class="{ 'quran-search-result-match-badge--surah-name': isSurahNameSearchResult(result) }"
                                    x-text="searchMatchLabel(result)"
                                ></span>
                            </button>
                        </template>
                    </div>
                </section>
            </template>
        </div>

        <div
            class="quran-search-feedback mt-2 flex items-center justify-center gap-3"
            x-cloak
            x-show="normalizeSearchQuery(search.query).length >= search.minQueryLength && search.isLoading"
            x-transition.opacity.duration.220ms
        >
            <span
                class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-amber-300/80 border-t-transparent"
                aria-hidden="true"
            ></span>
            <span>{{ arabic_text('تتوسع النتائج تباعًا...') }}</span>
        </div>
    </div>

    <div class="quran-surah-grid-shell">
        <p class="quran-surah-grid-caption">{{ arabic_text('فهرس السُّوَر') }}</p>
        <div
            class="quran-surah-grid"
            data-quran-surah-grid
            x-cloak
            x-ref="surahDirectoryGrid"
        >
            <template
                x-for="entry in search.surahDirectory"
                x-bind:key="`quran-surah-tile-${entry.surah_number}`"
            >
                <button
                    class="quran-surah-tile"
                    type="button"
                    x-bind:class="{ 'quran-surah-tile--active': isSurahDirectoryEntryActive(entry) }"
                    x-bind:data-surah-number="entry.surah_number"
                    x-on:click="goToSurahFromDirectory(entry)"
                >
                    <span
                        class="quran-surah-tile-label"
                        x-bind:class="{ 'quran-surah-tile-label--glyph': surahTileUsesGlyph(entry) }"
                        x-bind:style="surahTileLabelStyle(entry)"
                        x-text="surahTileLabel(entry)"
                    ></span>
                </button>
            </template>
        </div>
    </div>
</div>
