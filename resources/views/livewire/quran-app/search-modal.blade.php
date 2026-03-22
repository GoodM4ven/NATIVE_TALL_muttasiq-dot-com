<div
    class="quran-search-shell"
    data-no-swipe
>
    <div
        class="quran-search-results-shell"
        x-cloak
    >
        <div
            class="quran-search-feedback"
            x-cloak
            x-show="normalizeSearchQuery(search.query).length > 0 && normalizeSearchQuery(search.query).length < search.minQueryLength"
            x-transition.opacity.duration.220ms
        >
            اكتب حرفين أو أكثر ليبدأ البحث.
        </div>

        <div
            class="quran-search-feedback"
            x-cloak
            x-show="normalizeSearchQuery(search.query).length >= search.minQueryLength && search.isLoading"
            x-transition.opacity.duration.220ms
        >جاري البحث...</div>

        <div
            class="quran-search-feedback"
            x-cloak
            x-show="normalizeSearchQuery(search.query).length >= search.minQueryLength && !search.isLoading && search.results.length === 0"
            x-transition.opacity.duration.220ms
        >
            لا توجد نتائج مطابقة.
        </div>

        <div
            class="quran-search-results"
            x-cloak
            x-ref="searchResultsList"
            x-show="search.results.length > 0"
            x-transition:enter="transition duration-260 ease-out"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition duration-200 ease-in"
            x-transition:leave-start="opacity-100 translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1"
        >
            <template
                x-for="(result, resultIndex) in search.results"
                :key="`quran-search-modal-${result.id}`"
            >
                <button
                    class="quran-search-result-btn"
                    type="button"
                    x-bind:tabindex="resultIndex === 0 ? 0 : -1"
                    x-on:click="goToSearchResult(result)"
                >
                    <span
                        class="quran-search-result-meta"
                        x-text="surahLabel(result.surah_number) + ' · آية ' + result.ayah_number + ' · صفحة ' + result.page_number"
                    ></span>
                    <span
                        class="quran-search-result-ayah font-quran"
                        x-text="result.text_uthmani"
                    ></span>
                </button>
            </template>
        </div>
    </div>

    <div class="quran-surah-grid-shell">
        <p class="quran-surah-grid-caption">الانتقال المباشر إلى السور</p>
        <div
            class="quran-surah-grid"
            x-cloak
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
                        class="quran-surah-tile-number"
                        x-text="'#' + entry.surah_number"
                    ></span>
                    <span
                        class="quran-surah-tile-label font-quran"
                        x-text="entry.label"
                    ></span>
                </button>
            </template>
        </div>
    </div>
</div>
