<div
    class="quran-search-shell"
    data-no-swipe
>
    <div
        class="quran-search-results-shell mt-4"
        x-cloak
    >
        <div
            class="sr-only hidden"
            data-quran-search-stream-target
            wire:stream="quran-search-results-stream"
            x-ref="searchResultsStream"
        ></div>

        <div
            class="quran-search-feedback mt-2"
            x-cloak
            x-show="normalizeSearchQuery(search.query).length > 0 && normalizeSearchQuery(search.query).length < search.minQueryLength"
            x-transition.opacity.duration.220ms
            x-text="`${search.minQueryLength} {{ arabic_text('أحرف أو أكثر ليبدأ البحث.') }}`"
        ></div>

        <div
            class="quran-search-feedback mt-2"
            x-cloak
            x-show="shouldShowSearchNoResults()"
            x-transition.opacity.duration.220ms
        >
            {{ arabic_text('لا توجد نتائج مطابقة.') }}
        </div>

        <div
            class="quran-search-results"
            x-cloak
            x-ref="searchResultsList"
            x-bind:class="{
                'quran-search-results--active': search.results.length > 0,
                'quran-search-results--empty': search.results.length === 0,
            }"
        >
            <template
                x-for="(result, resultIndex) in search.results"
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

        <div
            class="quran-search-feedback mt-2 flex items-center justify-center"
            x-cloak
            x-show="normalizeSearchQuery(search.query).length >= search.minQueryLength && search.isLoading"
            x-transition.opacity.duration.220ms
        >
            <span
                class="inline-block h-5 w-5 animate-spin rounded-full border-2 border-amber-300/80 border-t-transparent"
                aria-hidden="true"
            ></span>
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
