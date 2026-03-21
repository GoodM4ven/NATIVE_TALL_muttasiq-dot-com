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

const nextAnimationFrame = async () => {
    await new Promise((resolve) => {
        requestAnimationFrame(() => {
            resolve();
        });
    });
};

const wait = async (durationMs) => {
    await new Promise((resolve) => {
        window.setTimeout(resolve, durationMs);
    });
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
        panelProbeLines: [],
        panelProbeUseCenteredAyahLayout: true,
        panelWidthPx: null,
        isLoadingPage: false,
        isFittingPage: true,
        pageMotionClass: '',
        pageMotionTimer: null,
        pageScale: 1,
        swipe: {
            active: false,
            startX: 0,
            startY: 0,
            pointerId: null,
            pointerType: null,
            source: null,
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
        _layoutToken: 0,
        _layoutRaf: null,
        _revealTimer: null,
        _viewportChangeDebounceTimer: null,
        _canonicalWidthPromise: null,
        _onWindowViewportChange: null,
        _onVisualViewportChange: null,
        _onSwitchView: null,

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
                this.handleViewportChange();
            };
            window.addEventListener('resize', this._onWindowViewportChange, { passive: true });
            window.addEventListener('orientationchange', this._onWindowViewportChange, {
                passive: true,
            });

            this._onVisualViewportChange = () => {
                this.handleViewportChange();
            };

            if (window.visualViewport) {
                window.visualViewport.addEventListener('resize', this._onVisualViewportChange, {
                    passive: true,
                });
            }

            this._onSwitchView = (event) => {
                const to = String(event?.detail?.to ?? '');

                if (!['quran-app-tilawa', 'quran-app-hifth', 'quran-app-tadabbur'].includes(to)) {
                    return;
                }

                this.scheduleLayout({ revealDelayMs: 200 });
            };

            window.addEventListener('switch-view', this._onSwitchView);
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

            if (this._onSwitchView) {
                window.removeEventListener('switch-view', this._onSwitchView);
            }

            if (this._layoutRaf !== null) {
                cancelAnimationFrame(this._layoutRaf);
                this._layoutRaf = null;
            }

            if (this._revealTimer !== null) {
                clearTimeout(this._revealTimer);
                this._revealTimer = null;
            }

            if (this._viewportChangeDebounceTimer !== null) {
                clearTimeout(this._viewportChangeDebounceTimer);
                this._viewportChangeDebounceTimer = null;
            }
        },

        async bootstrap() {
            await this.ensurePersistentStorage();
            await this.ensureCurrentPageLoaded();
            await this.ensureCanonicalPanelWidth();
            await this.layoutPage({ revealDelayMs: 240 });
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
                const payloadPromise = this.getPagePayload(normalizedPage);

                if (this.mushafLines.length > 0) {
                    this.isFittingPage = true;
                    await this.nextTickAsync();
                    await wait(180);
                }

                const payload = await payloadPromise;
                this.applyPayload(payload, { setPageNumber: true });

                if (animate) {
                    this.playPageMotion(direction);
                }

                this.prefetchNeighborPages(normalizedPage);
                await this.ensureCanonicalPanelWidth();
                await this.layoutPage({ revealDelayMs: 220 });
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

        clearLayoutTimers() {
            if (this._layoutRaf !== null) {
                cancelAnimationFrame(this._layoutRaf);
                this._layoutRaf = null;
            }

            if (this._revealTimer !== null) {
                clearTimeout(this._revealTimer);
                this._revealTimer = null;
            }
        },

        beginLayoutCycle() {
            this._layoutToken += 1;
            this.isFittingPage = true;

            return this._layoutToken;
        },

        queuePageReveal(layoutToken, delayMs = 180) {
            this._revealTimer = window.setTimeout(() => {
                if (layoutToken !== this._layoutToken) {
                    return;
                }

                this.isFittingPage = false;
                this._revealTimer = null;
            }, delayMs);
        },

        handleViewportChange() {
            if (this._viewportChangeDebounceTimer !== null) {
                clearTimeout(this._viewportChangeDebounceTimer);
            }

            this._viewportChangeDebounceTimer = window.setTimeout(async () => {
                this._viewportChangeDebounceTimer = null;
                await this.ensureCanonicalPanelWidth();
                this.scheduleLayout({ revealDelayMs: 150 });
            }, 90);
        },

        async ensureCanonicalPanelWidth() {
            if (this._canonicalWidthPromise) {
                await this._canonicalWidthPromise;

                return;
            }

            this._canonicalWidthPromise = (async () => {
                const probePage = this.maxPage >= 3 ? 3 : this.pageNumber;
                let payload = null;

                if (probePage === this.pageNumber && this.mushafLines.length > 0) {
                    payload = {
                        mushafLines: this.mushafLines,
                        useCenteredAyahLayout: this.useCenteredAyahLayout,
                    };
                } else {
                    try {
                        payload = await this.getPagePayload(probePage);
                    } catch (_) {
                        payload = null;
                    }
                }

                if (!payload) {
                    return;
                }

                this.panelProbeLines = Array.isArray(payload?.mushafLines)
                    ? payload.mushafLines
                    : [];
                this.panelProbeUseCenteredAyahLayout = Boolean(
                    payload?.useCenteredAyahLayout ?? this.useCenteredAyahLayout,
                );

                if (this.panelProbeLines.length < 1) {
                    return;
                }

                await this.nextTickAsync();
                await this.waitForPageFontReady();
                await nextAnimationFrame();

                const rootElement = this.$el;
                const panelElement = this.$refs.readerPanel;
                const viewportElement = this.$refs.pageViewport;
                const surfaceElement = this.$refs.pageSurface;
                const frameElement = this.$refs.pageFrame;
                const probeElement = this.$refs.pageThreeProbe;

                if (
                    !rootElement ||
                    !panelElement ||
                    !viewportElement ||
                    !surfaceElement ||
                    !frameElement ||
                    !probeElement
                ) {
                    return;
                }

                const previousScale = this.pageScale;
                rootElement.style.setProperty('--quran-page-scale', '1');

                const probeSize = this.measureRenderedBounds(probeElement);
                const availableHeight = Math.max(1, frameElement.clientHeight);
                const pageScaleByHeight = Math.min(
                    1,
                    availableHeight / Math.max(1, probeSize.height),
                );
                const contentTargetWidth = Math.max(1, probeSize.width * pageScaleByHeight);
                const viewportStyles = window.getComputedStyle(viewportElement);
                const surfaceStyles = window.getComputedStyle(surfaceElement);
                const panelStyles = window.getComputedStyle(panelElement);
                const panelChromeWidth =
                    Number.parseFloat(viewportStyles.paddingLeft || '0') +
                    Number.parseFloat(viewportStyles.paddingRight || '0') +
                    Number.parseFloat(surfaceStyles.paddingLeft || '0') +
                    Number.parseFloat(surfaceStyles.paddingRight || '0') +
                    Number.parseFloat(panelStyles.borderLeftWidth || '0') +
                    Number.parseFloat(panelStyles.borderRightWidth || '0');
                const desiredPanelWidth = Math.ceil(contentTargetWidth + panelChromeWidth + 6);
                const viewportMaxWidth = Math.max(320, Math.floor(window.innerWidth * 0.96));
                const clampedPanelWidth = Math.max(
                    300,
                    Math.min(viewportMaxWidth, desiredPanelWidth),
                );

                if (
                    this.panelWidthPx === null ||
                    Math.abs(Number(this.panelWidthPx) - clampedPanelWidth) >= 1
                ) {
                    this.panelWidthPx = clampedPanelWidth;
                }

                rootElement.style.setProperty('--quran-page-scale', String(previousScale || 1));
            })();

            try {
                await this._canonicalWidthPromise;
            } finally {
                this._canonicalWidthPromise = null;
            }
        },

        scheduleLayout({ revealDelayMs = 180 } = {}) {
            const layoutToken = this.beginLayoutCycle();

            this.clearLayoutTimers();

            this._layoutRaf = requestAnimationFrame(() => {
                this._layoutRaf = null;
                this.fitPageToViewport();
                this.queuePageReveal(layoutToken, revealDelayMs);
            });
        },

        async layoutPage({ revealDelayMs = 180 } = {}) {
            const layoutToken = this.beginLayoutCycle();

            await this.nextTickAsync();
            await this.waitForPageFontReady();
            await nextAnimationFrame();
            await nextAnimationFrame();

            this.fitPageToViewport();
            this.queuePageReveal(layoutToken, revealDelayMs);
        },

        measureRenderedBounds(contentElement) {
            const lineTargets = Array.from(
                contentElement.querySelectorAll('[data-quran-line-text]'),
            );
            const targets = lineTargets.length > 0 ? lineTargets : [contentElement];

            let minLeft = Number.POSITIVE_INFINITY;
            let minTop = Number.POSITIVE_INFINITY;
            let maxRight = Number.NEGATIVE_INFINITY;
            let maxBottom = Number.NEGATIVE_INFINITY;

            targets.forEach((target) => {
                const rect = target.getBoundingClientRect();

                if (rect.width <= 0 || rect.height <= 0) {
                    return;
                }

                minLeft = Math.min(minLeft, rect.left);
                minTop = Math.min(minTop, rect.top);
                maxRight = Math.max(maxRight, rect.right);
                maxBottom = Math.max(maxBottom, rect.bottom);
            });

            if (!Number.isFinite(minLeft) || !Number.isFinite(maxRight)) {
                const fallbackRect = contentElement.getBoundingClientRect();

                return {
                    width: Math.max(1, Number(fallbackRect.width ?? 1)),
                    height: Math.max(1, Number(fallbackRect.height ?? 1)),
                };
            }

            return {
                width: Math.max(1, maxRight - minLeft),
                height: Math.max(1, maxBottom - minTop),
            };
        },

        fitPageToViewport() {
            const rootElement = this.$el;
            const frameElement = this.$refs.pageFrame;
            const contentElement = this.$refs.pageContent;

            if (!rootElement || !frameElement || !contentElement) {
                return;
            }

            rootElement.style.setProperty('--quran-page-scale', '1');

            const availableWidth = Math.max(
                1,
                frameElement.parentElement?.clientWidth ?? frameElement.clientWidth,
            );
            const availableHeight = Math.max(1, frameElement.clientHeight);
            const naturalSize = this.measureRenderedBounds(contentElement);
            const initialScale = Math.min(
                availableWidth / Math.max(1, naturalSize.width),
                availableHeight / Math.max(1, naturalSize.height),
                1,
            );

            let normalizedScale = Number.isFinite(initialScale)
                ? Math.max(0.2, Math.min(1, initialScale))
                : 1;

            for (let attempt = 0; attempt < 3; attempt += 1) {
                rootElement.style.setProperty('--quran-page-scale', String(normalizedScale));

                const measured = this.measureRenderedBounds(contentElement);
                const adjustScale = Math.min(
                    availableWidth / Math.max(1, measured.width),
                    availableHeight / Math.max(1, measured.height),
                    1,
                );

                if (adjustScale >= 0.999) {
                    break;
                }

                normalizedScale = Math.max(0.2, Number((normalizedScale * adjustScale).toFixed(4)));
            }

            rootElement.style.setProperty('--quran-page-scale', String(normalizedScale));
            this.pageScale = normalizedScale;
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

        swipePoint(event) {
            if (event?.touches?.length) {
                const touch = event.touches[0];

                return {
                    x: touch.clientX,
                    y: touch.clientY,
                    pointerType: 'touch',
                    pointerId: null,
                };
            }

            if (event?.changedTouches?.length) {
                const touch = event.changedTouches[0];

                return {
                    x: touch.clientX,
                    y: touch.clientY,
                    pointerType: 'touch',
                    pointerId: null,
                };
            }

            if (Number.isFinite(event?.clientX) && Number.isFinite(event?.clientY)) {
                return {
                    x: event.clientX,
                    y: event.clientY,
                    pointerType: event.pointerType ?? 'mouse',
                    pointerId: event.pointerId ?? null,
                };
            }

            return null;
        },

        onSwipeStart(event) {
            if (event.target?.closest?.('[data-no-swipe]')) {
                return;
            }

            if (event.target?.closest?.('input, textarea, select, [contenteditable="true"]')) {
                return;
            }

            const source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';

            if (this.swipe.source && this.swipe.source !== source) {
                return;
            }

            if (event.pointerType === 'mouse' && event.button !== 0) {
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            this.swipe.active = true;
            this.swipe.source = source;
            this.swipe.startX = point.x;
            this.swipe.startY = point.y;
            this.swipe.pointerId = point.pointerId;
            this.swipe.pointerType = point.pointerType;
        },

        async onSwipeEnd(event) {
            if (!this.swipe.active) {
                return;
            }

            const source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';

            if (this.swipe.source && this.swipe.source !== source) {
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            if (this.swipe.pointerId !== null && point.pointerId !== this.swipe.pointerId) {
                return;
            }

            const deltaX = point.x - this.swipe.startX;
            const deltaY = point.y - this.swipe.startY;
            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);

            this.swipe.active = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;

            if (absX < 40 || absX < absY) {
                return;
            }

            if (deltaX > 0) {
                await this.nextPage();

                return;
            }

            await this.previousPage();
        },

        onSwipeCancel() {
            this.swipe.active = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;
        },

        pageContentStyle() {
            return 'width: max-content;';
        },

        readerPanelStyle() {
            if (!Number.isFinite(Number(this.panelWidthPx))) {
                return 'touch-action: pan-y;';
            }

            const width = Math.max(300, Math.round(Number(this.panelWidthPx)));

            return `touch-action: pan-y; width: min(96vw, ${width}px);`;
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

        lineEntryStyle(line) {
            const lineNumber = Math.max(0, Number(line?.line_number ?? 0));

            return `--quran-line-index: ${lineNumber};`;
        },

        ayahLineClass(line) {
            if (this.isRectangularAyahLine(line)) {
                return 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-rect font-quran';
            }

            return 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-centered font-quran';
        },

        probeLineAlignmentClass(line) {
            if (line?.line_type === 'ayah' && !this.panelProbeUseCenteredAyahLayout) {
                return 'text-right';
            }

            if (Boolean(line?.is_centered)) {
                return 'text-center';
            }

            return '';
        },

        probeAyahLineClass(line) {
            if (line?.line_type === 'ayah' && !this.panelProbeUseCenteredAyahLayout) {
                return 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-rect font-quran';
            }

            return 'quran-ayah-line quran-ayah-line-run quran-ayah-line-run-centered font-quran';
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
