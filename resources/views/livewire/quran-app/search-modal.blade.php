<div
    class="quran-search-shell"
    data-no-swipe
>
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
            class="quran-search-go quran-search-go--ghost"
            type="button"
            x-on:click="requestSearchModalClose()"
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
