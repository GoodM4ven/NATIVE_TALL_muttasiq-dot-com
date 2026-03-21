const defaultPagePayload = Object.freeze({
    ready: false,
    pageNumber: 1,
    maxPage: 0,
    activeAyahIndex: 0,
    mushafLines: [],
    qpcPageFontFamily: null,
    qpcPageFontUrl: null,
    qpcPageFontFormat: null,
    useCenteredAyahLayout: true,
});

const normalizePayload = (payload = {}) => ({
    ready: Boolean(payload?.ready),
    pageNumber: Number(payload?.pageNumber ?? defaultPagePayload.pageNumber),
    maxPage: Number(payload?.maxPage ?? defaultPagePayload.maxPage),
    activeAyahIndex: Number(payload?.activeAyahIndex ?? defaultPagePayload.activeAyahIndex),
    mushafLines: Array.isArray(payload?.mushafLines) ? payload.mushafLines : [],
    qpcPageFontFamily: payload?.qpcPageFontFamily ?? null,
    qpcPageFontUrl: payload?.qpcPageFontUrl ?? null,
    qpcPageFontFormat: payload?.qpcPageFontFormat ?? null,
    useCenteredAyahLayout: Boolean(payload?.useCenteredAyahLayout),
});

const clampPage = (value, maxPage) => {
    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return 1;
    }

    const rounded = Math.trunc(numeric);

    if (maxPage > 0) {
        return Math.max(1, Math.min(maxPage, rounded));
    }

    return Math.max(1, rounded);
};

const openCacheSafely = async (cacheName) => {
    if (!cacheName || typeof window === 'undefined' || typeof window.caches === 'undefined') {
        return null;
    }

    try {
        return await window.caches.open(cacheName);
    } catch (_) {
        return null;
    }
};

const fetchJsonWithCache = async ({ url, cacheName, preferCache = true, forceNetwork = false }) => {
    const cache = await openCacheSafely(cacheName);

    if (cache && preferCache && !forceNetwork) {
        const cached = await cache.match(url);

        if (cached) {
            return await cached.json();
        }
    }

    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) {
            throw new Error(`Unexpected response ${response.status} for ${url}`);
        }

        if (cache) {
            await cache.put(url, response.clone());
        }

        return await response.json();
    } catch (error) {
        if (cache) {
            const stale = await cache.match(url);

            if (stale) {
                return await stale.json();
            }
        }

        throw error;
    }
};

const cacheAssetResponse = async ({ url, cacheName }) => {
    if (!url) {
        return;
    }

    const cache = await openCacheSafely(cacheName);

    if (!cache) {
        return;
    }

    const cached = await cache.match(url);

    if (cached) {
        return;
    }

    try {
        const response = await fetch(url, {
            credentials: 'same-origin',
            cache: 'no-store',
        });

        if (!response.ok) {
            return;
        }

        await cache.put(url, response);
    } catch (_) {
        // Ignore cache misses in offline / flaky network states.
    }
};

document.addEventListener('alpine:init', () => {
    window.Alpine.data('quranAppReader', (config = {}) => ({
        api: {
            pageDataTemplate: String(config?.api?.pageDataTemplate ?? ''),
            searchIndexUrl: String(config?.api?.searchIndexUrl ?? ''),
        },
        cacheNames: {
            pages: 'quran-reader-pages-v1',
            fonts: 'quran-reader-fonts-v1',
            search: 'quran-reader-search-v1',
        },
        initialPayload: normalizePayload(config?.initialPayload),
        nativeRuntime: Boolean(config?.nativeRuntime ?? false),
        prewarmPages: Math.max(1, Number(config?.prewarmPages ?? 6)),
        prefetchRadius: Math.max(1, Number(config?.prefetchRadius ?? 2)),

        ready: false,
        pageNumber: window.Alpine.$persist(1).as('quran-reader-page-number-v1'),
        pageInput: 1,
        maxPage: 0,
        activeAyahIndex: 0,
        mushafLines: [],
        qpcPageFontFamily: null,
        qpcPageFontUrl: null,
        qpcPageFontFormat: null,
        useCenteredAyahLayout: true,
        isLoadingPage: false,
        pageMotionClass: '',
        pageMotionTimer: null,
        pageScale: 1,
        swipe: {
            active: false,
            startX: 0,
            startY: 0,
            pointerId: null,
        },
        storage: {
            isPersisted: false,
            persistRequested: false,
        },
        search: {
            query: '',
            index: [],
            results: [],
            isLoading: false,
            isReady: false,
            isOpen: false,
        },
        _pendingPageLoads: new Map(),
        _pagePayloadByPage: new Map(),
        _searchIndexPromise: null,
        _fitRaf: null,
        _fitTimeout: null,
        _fitStabilizeTimers: [],
        _viewportResizeObserver: null,
        _onWindowViewportChange: null,
        _onVisualViewportChange: null,
        _onFittyRefitComplete: null,

        init() {
            this.applyPayload(this.initialPayload, { setPageNumber: true });

            const restoredPage = clampPage(
                this.pageNumber,
                this.maxPage || this.initialPayload.maxPage,
            );

            this.pageNumber = restoredPage;
            this.pageInput = restoredPage;

            if (restoredPage !== this.initialPayload.pageNumber && this.ready) {
                this.goToPage(restoredPage, {
                    direction: restoredPage > this.initialPayload.pageNumber ? 'next' : 'prev',
                    animate: false,
                });
            }

            this._onWindowViewportChange = () => {
                this.refitQuranText();
                this.schedulePageFit();
            };
            window.addEventListener('resize', this._onWindowViewportChange, { passive: true });
            window.addEventListener('orientationchange', this._onWindowViewportChange, {
                passive: true,
            });
            this._onVisualViewportChange = () => {
                this.refitQuranText();
                this.schedulePageFit();
            };

            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', this._onVisualViewportChange, {
                    passive: true,
                });
            }

            this._onFittyRefitComplete = () => {
                this.schedulePageFit();
            };
            window.addEventListener('fitty-refit-complete', this._onFittyRefitComplete);
            window.addEventListener('switch-view', (event) => {
                const to = String(event?.detail?.to ?? '');

                if (!['quran-app-tilawa', 'quran-app-hifth', 'quran-app-tadabbur'].includes(to)) {
                    return;
                }

                this.afterPagePaint();
            });

            this.bootstrap();
        },

        destroy() {
            if (this._onWindowViewportChange) {
                window.removeEventListener('resize', this._onWindowViewportChange);
                window.removeEventListener('orientationchange', this._onWindowViewportChange);
            }

            if (this._onVisualViewportChange && window.visualViewport) {
                window.visualViewport.removeEventListener('resize', this._onVisualViewportChange);
            }

            if (this._onFittyRefitComplete) {
                window.removeEventListener('fitty-refit-complete', this._onFittyRefitComplete);
            }

            if (this._fitRaf !== null) {
                cancelAnimationFrame(this._fitRaf);
                this._fitRaf = null;
            }

            if (this._fitTimeout !== null) {
                clearTimeout(this._fitTimeout);
                this._fitTimeout = null;
            }

            this.clearStabilizeTimers();

            if (this._viewportResizeObserver) {
                this._viewportResizeObserver.disconnect();
                this._viewportResizeObserver = null;
            }
        },

        async bootstrap() {
            await this.ensurePersistentStorage();
            await this.ensureCurrentPageLoaded();
            await this.afterPagePaint();
            this.queueStartupPreload();
            this.warmSearchIndex();
        },

        async ensurePersistentStorage() {
            if (typeof navigator === 'undefined' || !navigator.storage) {
                return;
            }

            try {
                this.storage.isPersisted = Boolean(await navigator.storage.persisted());

                if (!this.storage.isPersisted) {
                    this.storage.persistRequested = true;
                    this.storage.isPersisted = Boolean(await navigator.storage.persist());
                }
            } catch (_) {
                this.storage.isPersisted = false;
            }
        },

        pageDataUrl(pageNumber) {
            return this.api.pageDataTemplate.replace('__PAGE__', String(pageNumber));
        },

        async getPagePayload(pageNumber, { preferCache = true, forceNetwork = false } = {}) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);

            if (this._pagePayloadByPage.has(normalizedPage) && !forceNetwork) {
                return this._pagePayloadByPage.get(normalizedPage);
            }

            const pendingLoad = this._pendingPageLoads.get(normalizedPage);

            if (pendingLoad) {
                return await pendingLoad;
            }

            const url = this.pageDataUrl(normalizedPage);
            const loadPromise = (async () => {
                const payload = normalizePayload(
                    await fetchJsonWithCache({
                        url,
                        cacheName: this.cacheNames.pages,
                        preferCache,
                        forceNetwork,
                    }),
                );

                this._pagePayloadByPage.set(normalizedPage, payload);
                await this.prefetchFontAsset(payload);

                return payload;
            })();

            this._pendingPageLoads.set(normalizedPage, loadPromise);

            try {
                return await loadPromise;
            } finally {
                this._pendingPageLoads.delete(normalizedPage);
            }
        },

        async ensureCurrentPageLoaded() {
            const normalizedPage = clampPage(this.pageNumber, this.maxPage);

            if (normalizedPage === this.initialPayload.pageNumber && this.ready) {
                return;
            }

            await this.goToPage(normalizedPage, { animate: false });
        },

        async nextPage() {
            await this.goToPage(this.pageNumber + 1, { direction: 'next' });
        },

        async previousPage() {
            await this.goToPage(this.pageNumber - 1, { direction: 'prev' });
        },

        async goToPage(pageNumber, { direction = 'next', animate = true } = {}) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);

            if (normalizedPage === this.pageNumber && this.mushafLines.length > 0) {
                this.pageInput = normalizedPage;

                return;
            }

            this.isLoadingPage = true;

            try {
                const payload = await this.getPagePayload(normalizedPage);
                this.applyPayload(payload, { setPageNumber: true });

                if (animate) {
                    this.playPageMotion(direction);
                }

                this.prefetchNeighborPages(normalizedPage);
                await this.afterPagePaint();
            } finally {
                this.isLoadingPage = false;
            }
        },

        async onPageInputCommit() {
            const targetPage = clampPage(this.pageInput, this.maxPage);
            const direction = targetPage >= this.pageNumber ? 'next' : 'prev';

            this.pageInput = targetPage;
            await this.goToPage(targetPage, { direction, animate: true });
        },

        applyPayload(payload, { setPageNumber = false } = {}) {
            const normalizedPayload = normalizePayload(payload);

            this.ready = normalizedPayload.ready;
            this.maxPage = normalizedPayload.maxPage;
            this.mushafLines = normalizedPayload.mushafLines;
            this.useCenteredAyahLayout = normalizedPayload.useCenteredAyahLayout;
            this.qpcPageFontFamily = normalizedPayload.qpcPageFontFamily;
            this.qpcPageFontUrl = normalizedPayload.qpcPageFontUrl;
            this.qpcPageFontFormat = normalizedPayload.qpcPageFontFormat;
            this.activeAyahIndex = normalizedPayload.activeAyahIndex;

            if (setPageNumber) {
                this.pageNumber = clampPage(
                    normalizedPayload.pageNumber,
                    normalizedPayload.maxPage,
                );
            }

            this.pageInput = this.pageNumber;
            this.syncPageFontFace();
        },

        async nextTickAsync() {
            await new Promise((resolve) => this.$nextTick(resolve));
        },

        async waitForPageFontReady() {
            const family = String(this.qpcPageFontFamily ?? '').trim();

            if (!family || !document.fonts?.load) {
                return;
            }

            try {
                await document.fonts.load(`32px '${family}'`, 'الحمد لله');
                await document.fonts.ready;
            } catch (_) {
                // Ignore font loading failures and continue with fallback glyphs.
            }
        },

        async afterPagePaint() {
            await this.nextTickAsync();
            await this.waitForPageFontReady();
            this.ensureViewportObserver();
            this.refitQuranText();
            this.schedulePageFit();

            if (this._fitTimeout !== null) {
                clearTimeout(this._fitTimeout);
            }

            this._fitTimeout = window.setTimeout(() => {
                this.refitQuranText();
                this.schedulePageFit();
                this._fitTimeout = null;
            }, 36);

            this.scheduleStabilizedFitPasses();
        },

        syncPageFontFace() {
            const family = String(this.qpcPageFontFamily ?? '').trim();
            const url = String(this.qpcPageFontUrl ?? '').trim();
            const format = String(this.qpcPageFontFormat ?? 'woff2').trim() || 'woff2';
            const styleId = 'quran-reader-dynamic-page-font';
            let styleTag = document.getElementById(styleId);

            if (!family || !url) {
                if (styleTag) {
                    styleTag.remove();
                }

                return;
            }

            if (!styleTag) {
                styleTag = document.createElement('style');
                styleTag.id = styleId;
                document.head.appendChild(styleTag);
            }

            styleTag.textContent = `@font-face { font-family: '${family}'; src: url('${url}') format('${format}'); font-display: block; }`;
        },

        schedulePageFit(immediate = false) {
            if (this._fitRaf !== null) {
                cancelAnimationFrame(this._fitRaf);
            }

            const runFit = () => {
                this._fitRaf = null;
                this.fitPageToViewport();
            };

            if (immediate) {
                runFit();

                return;
            }

            this._fitRaf = requestAnimationFrame(runFit);
        },

        ensureViewportObserver() {
            if (typeof ResizeObserver === 'undefined') {
                return;
            }

            const viewportElement = this.$refs.pageViewport;

            if (!viewportElement) {
                return;
            }

            if (this._viewportResizeObserver) {
                this._viewportResizeObserver.disconnect();
            }

            this._viewportResizeObserver = new ResizeObserver(() => {
                this.refitQuranText();
                this.schedulePageFit();
            });
            this._viewportResizeObserver.observe(viewportElement);
        },

        clearStabilizeTimers() {
            this._fitStabilizeTimers.forEach((timer) => {
                clearTimeout(timer);
            });
            this._fitStabilizeTimers = [];
        },

        scheduleStabilizedFitPasses() {
            this.clearStabilizeTimers();

            [32, 90, 180, 300].forEach((delay) => {
                const timer = window.setTimeout(() => {
                    this.refitQuranText();
                    this.schedulePageFit();
                }, delay);

                this._fitStabilizeTimers.push(timer);
            });
        },

        fitPageToViewport() {
            const rootElement = this.$el;
            const viewportElement = this.$refs.pageViewport;
            const contentElement = this.$refs.pageContent;

            if (!rootElement || !viewportElement || !contentElement) {
                return;
            }

            rootElement.style.setProperty('--quran-page-scale', '1');

            const availableHeight = Math.max(1, viewportElement.clientHeight - 4);
            const availableWidth = Math.max(1, viewportElement.clientWidth - 4);
            const contentHeight = Math.max(1, contentElement.scrollHeight);
            const contentWidth = Math.max(1, contentElement.scrollWidth);
            const fittedScale = Math.min(
                1,
                availableHeight / contentHeight,
                availableWidth / contentWidth,
            );
            const normalizedScale = Math.max(0.5, Math.min(1, fittedScale));

            this.pageScale = Number(normalizedScale.toFixed(4));
            rootElement.style.setProperty('--quran-page-scale', String(this.pageScale));
        },

        async prefetchFontAsset(payload) {
            const fontUrl = String(payload?.qpcPageFontUrl ?? '').trim();

            if (!fontUrl) {
                return;
            }

            await cacheAssetResponse({
                url: fontUrl,
                cacheName: this.cacheNames.fonts,
            });
        },

        queueStartupPreload() {
            const pages = [];

            for (let page = 1; page <= this.prewarmPages; page += 1) {
                pages.push(page);
            }

            for (let offset = 1; offset <= this.prefetchRadius; offset += 1) {
                pages.push(this.pageNumber + offset, this.pageNumber - offset);
            }

            const uniquePages = Array.from(
                new Set(
                    pages
                        .map((page) => clampPage(page, this.maxPage))
                        .filter((page) => page >= 1 && (this.maxPage < 1 || page <= this.maxPage)),
                ),
            );

            window.setTimeout(() => {
                uniquePages.forEach((page) => {
                    this.prefetchPage(page);
                });
            }, 40);
        },

        prefetchNeighborPages(pageNumber) {
            for (let offset = 1; offset <= this.prefetchRadius; offset += 1) {
                this.prefetchPage(pageNumber + offset);
                this.prefetchPage(pageNumber - offset);
            }
        },

        async prefetchPage(pageNumber) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);

            if (normalizedPage < 1 || (this.maxPage > 0 && normalizedPage > this.maxPage)) {
                return;
            }

            try {
                await this.getPagePayload(normalizedPage);
            } catch (_) {
                // Ignore background prefetch failures.
            }
        },

        playPageMotion(direction) {
            const nextClass =
                direction === 'prev' ? 'quran-page-motion-prev' : 'quran-page-motion-next';

            if (this.pageMotionTimer !== null) {
                clearTimeout(this.pageMotionTimer);
            }

            this.pageMotionClass = nextClass;
            this.pageMotionTimer = window.setTimeout(() => {
                this.pageMotionClass = '';
                this.pageMotionTimer = null;
            }, 260);
        },

        onSwipeStart(event) {
            if (event.pointerType !== 'touch') {
                return;
            }

            this.swipe.active = true;
            this.swipe.startX = Number(event.clientX ?? 0);
            this.swipe.startY = Number(event.clientY ?? 0);
            this.swipe.pointerId = Number(event.pointerId ?? 0);
        },

        async onSwipeEnd(event) {
            if (!this.swipe.active || event.pointerType !== 'touch') {
                return;
            }

            if (
                this.swipe.pointerId !== null &&
                Number(event.pointerId ?? -1) !== this.swipe.pointerId
            ) {
                return;
            }

            const endX = Number(event.clientX ?? 0);
            const endY = Number(event.clientY ?? 0);
            const deltaX = endX - this.swipe.startX;
            const deltaY = endY - this.swipe.startY;

            this.swipe.active = false;
            this.swipe.pointerId = null;

            if (Math.abs(deltaX) < 52 || Math.abs(deltaX) < Math.abs(deltaY) * 1.2) {
                return;
            }

            if (deltaX < 0) {
                await this.nextPage();

                return;
            }

            await this.previousPage();
        },

        refitQuranText() {
            window.dispatchEvent(new CustomEvent('fitty-refit'));
        },

        pageContentStyle() {
            const maxWidth = this.useCenteredAyahLayout ? '920px' : 'min(32rem, 100%)';

            return `max-width: ${maxWidth};`;
        },

        selectAyah(ayahIndex) {
            const normalizedAyahIndex = Number(ayahIndex);

            if (!Number.isFinite(normalizedAyahIndex) || normalizedAyahIndex < 1) {
                return;
            }

            this.activeAyahIndex = Math.trunc(normalizedAyahIndex);
        },

        isRectangularAyahLine(line) {
            return line?.line_type === 'ayah' && !this.useCenteredAyahLayout;
        },

        lineAlignmentClass(line) {
            if (this.isRectangularAyahLine(line)) {
                return 'text-right';
            }

            if (Boolean(line?.is_centered)) {
                return 'text-center';
            }

            return '';
        },

        ayahLineClass(line) {
            if (this.isRectangularAyahLine(line)) {
                return 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-rect quran-ayah-line-fit font-quran';
            }

            return 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-centered quran-ayah-line-fit font-quran';
        },

        lineFontStyle() {
            const family = String(this.qpcPageFontFamily ?? '').trim();

            if (!family) {
                return 'color: var(--quran-ink);';
            }

            return `font-family: '${family}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif; color: var(--quran-ink);`;
        },

        isWordActive(word) {
            const ayahIndex = Number(word?.ayah_index ?? 0);

            return ayahIndex > 0 && ayahIndex === this.activeAyahIndex;
        },

        wordStyle(word) {
            if (!this.isWordActive(word)) {
                return null;
            }

            return 'background: var(--quran-active-bg); color: var(--quran-active-text);';
        },

        showAyahMarker(word) {
            return Boolean(word?.ends_ayah) && !Boolean(word?.is_glyph);
        },

        normalizeSearchQuery(value) {
            return String(value ?? '')
                .trim()
                .replace(/\s+/g, ' ')
                .toLowerCase();
        },

        async warmSearchIndex() {
            if (this.search.isReady || this.search.isLoading || !this.api.searchIndexUrl) {
                return;
            }

            if (this._searchIndexPromise) {
                await this._searchIndexPromise;

                return;
            }

            this.search.isLoading = true;

            this._searchIndexPromise = (async () => {
                try {
                    const payload = await fetchJsonWithCache({
                        url: this.api.searchIndexUrl,
                        cacheName: this.cacheNames.search,
                        preferCache: true,
                    });

                    this.search.index = Array.isArray(payload?.items) ? payload.items : [];
                    this.search.isReady = true;
                } catch (_) {
                    this.search.index = [];
                    this.search.isReady = false;
                } finally {
                    this.search.isLoading = false;
                    this._searchIndexPromise = null;
                }
            })();

            await this._searchIndexPromise;
        },

        async updateSearchResults() {
            const normalizedQuery = this.normalizeSearchQuery(this.search.query);

            if (!normalizedQuery) {
                this.search.results = [];
                this.search.isOpen = false;

                return;
            }

            if (!this.search.isReady) {
                await this.warmSearchIndex();
            }

            if (!this.search.isReady) {
                this.search.results = [];
                this.search.isOpen = false;

                return;
            }

            const results = [];

            for (const item of this.search.index) {
                const typed = this.normalizeSearchQuery(item?.text_searchable_typed ?? '');
                const plain = this.normalizeSearchQuery(item?.text_uthmani ?? '');

                if (!typed.includes(normalizedQuery) && !plain.includes(normalizedQuery)) {
                    continue;
                }

                results.push(item);

                if (results.length >= 24) {
                    break;
                }
            }

            this.search.results = results;
            this.search.isOpen = results.length > 0;

            if (results.length === 1) {
                await this.goToSearchResult(results[0]);
            }
        },

        async goToSearchResult(result) {
            const targetPage = clampPage(Number(result?.page_number ?? 1), this.maxPage);
            const ayahIndex = Math.max(0, Math.trunc(Number(result?.ayah_index ?? 0)));
            const direction = targetPage >= this.pageNumber ? 'next' : 'prev';

            this.activeAyahIndex = ayahIndex;
            this.search.query = '';
            this.search.results = [];
            this.search.isOpen = false;

            await this.goToPage(targetPage, { direction, animate: true });

            if (ayahIndex > 0) {
                this.activeAyahIndex = ayahIndex;
            }
        },
    }));
});
