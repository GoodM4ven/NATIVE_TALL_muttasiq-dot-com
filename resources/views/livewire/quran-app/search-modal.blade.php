<div
    class="quran-search-shell"
    data-no-swipe
>
    <div
        class="quran-search-results-shell"
        x-cloak
        wire:transition
    >
        <template
            x-if="normalizeSearchQuery(search.query).length > 0 && normalizeSearchQuery(search.query).length < search.minQueryLength"
        >
            <div class="quran-search-feedback">
                اكتب حرفين أو أكثر ليبدأ البحث.
            </div>
        </template>

        <template x-if="normalizeSearchQuery(search.query).length >= search.minQueryLength && search.isLoading">
            <div class="quran-search-feedback">جاري البحث...</div>
        </template>

        <template
            x-if="normalizeSearchQuery(search.query).length >= search.minQueryLength && !search.isLoading && search.results.length === 0"
        >
            <div class="quran-search-feedback">لا توجد نتائج مطابقة.</div>
        </template>

        <template x-if="search.results.length > 0">
            <div
                class="quran-search-results"
                wire:transition
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
        </template>
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
