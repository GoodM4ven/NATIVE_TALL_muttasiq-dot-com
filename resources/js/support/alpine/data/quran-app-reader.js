const defaultPagePayload = Object.freeze({
    ready: false,
    pageNumber: 1,
    maxPage: 0,
    activeAyahIndex: 0,
    mushafLines: [],
    qpcPageFontFamily: null,
    qpcPageFontUrl: null,
    qpcPageFontFormat: null,
    basmallahFontFamily: null,
    basmallahFontUrl: null,
    basmallahFontFormat: null,
    basmallahText: null,
    surahHeaderFontFamily: null,
    surahHeaderFontUrl: null,
    surahHeaderFontFormat: null,
    surahNames: null,
    surahDirectory: null,
    useCenteredAyahLayout: true,
});

const controlPanelSettingKeys = Object.freeze({
    enableVisualEnhancements: 'enable_visual_enhancements',
    targetWordsByDefault: 'does_quran_target_words_by_default',
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
    basmallahFontFamily: payload?.basmallahFontFamily ?? null,
    basmallahFontUrl: payload?.basmallahFontUrl ?? null,
    basmallahFontFormat: payload?.basmallahFontFormat ?? null,
    basmallahText: payload?.basmallahText ?? null,
    surahHeaderFontFamily: payload?.surahHeaderFontFamily ?? null,
    surahHeaderFontUrl: payload?.surahHeaderFontUrl ?? null,
    surahHeaderFontFormat: payload?.surahHeaderFontFormat ?? null,
    surahNames:
        payload?.surahNames && typeof payload.surahNames === 'object' ? payload.surahNames : null,
    surahDirectory: Array.isArray(payload?.surahDirectory) ? payload.surahDirectory : null,
    useCenteredAyahLayout: Boolean(payload?.useCenteredAyahLayout),
});

const normalizeNumerals = (rawValue) =>
    String(rawValue ?? '')
        .replace(/[٠-٩]/g, (digit) => String(digit.charCodeAt(0) - 0x660))
        .replace(/[۰-۹]/g, (digit) => String(digit.charCodeAt(0) - 0x6f0))
        .trim();

const clampPage = (value, maxPage) => {
    const numeric = typeof value === 'string' ? Number(normalizeNumerals(value)) : Number(value);

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

const wordPressHoldDelayMs = 750;
const navigationSettleDelayMs = 140;
const navigationRevealLockDurationMs = 420;

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
            pages: 'quran-reader-pages-v11',
            fonts: 'quran-reader-fonts-v4',
            search: 'quran-reader-search-v3',
        },
        initialPayload: normalizePayload(config?.initialPayload),
        nativeRuntime: Boolean(config?.nativeRuntime ?? false),
        prewarmPages: Math.max(1, Number(config?.prewarmPages ?? 6)),
        prefetchRadius: Math.max(1, Number(config?.prefetchRadius ?? 2)),
        searchModalId: String(config?.searchModalId ?? ''),
        searchModalDomId: String(config?.searchModalDomId ?? ''),
        searchActionModalId: String(config?.searchActionModalId ?? ''),
        initialSettings:
            config?.settings && typeof config.settings === 'object' ? config.settings : {},

        ready: false,
        pageNumber: window.Alpine.$persist(1).as('quran-reader-page-number-v1'),
        pageInput: 1,
        maxPage: 0,
        activeAyahIndex: 0,
        activeWordIndex: 0,
        mushafLines: [],
        qpcPageFontFamily: null,
        qpcPageFontUrl: null,
        qpcPageFontFormat: null,
        basmallahFontFamily: null,
        basmallahFontUrl: null,
        basmallahFontFormat: null,
        basmallahText: null,
        surahHeaderFontFamily: null,
        surahHeaderFontUrl: null,
        surahHeaderFontFormat: null,
        useCenteredAyahLayout: true,
        hoveredAyahIndex: 0,
        hoveredWordIndex: 0,
        doesEnableVisualEnhancements: true,
        doesTargetWordsByDefault: false,
        panelProbeLines: [],
        panelProbeUseCenteredAyahLayout: true,
        panelProbeFontFamily: null,
        panelProbeFontUrl: null,
        panelProbeFontFormat: null,
        panelWidthPx: null,
        isLoadingPage: false,
        isFittingPage: true,
        pageMotionClass: '',
        surahTriggerCaption: '',
        surahTriggerCaptionAnimClass: '',
        surahTriggerSurahNumber: 1,
        pageMotionTimer: null,
        pageScale: 1,
        pageCounterPulse: {
            isActive: false,
            hasChanges: false,
            segments: [],
            timer: null,
        },
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
            minQueryLength: 5,
            results: [],
            isLoading: false,
            isReady: false,
            isOpen: false,
            modalOpen: false,
            readyResult: null,
            surahNames: {},
            surahDirectory: [],
            activeSurahNumber: 1,
        },

        _pendingPageLoads: new Map(),
        _pagePayloadByPage: new Map(),
        _searchIndexPromise: null,
        _layoutToken: 0,
        _layoutRaf: null,
        _revealTimer: null,
        _layoutMutationObserver: null,
        _layoutResizeObserver: null,
        _viewportChangeDebounceTimer: null,
        _canonicalWidthPromise: null,
        _panelWidthBucket: null,
        _onWindowViewportChange: null,
        _onVisualViewportChange: null,
        _onSwitchView: null,
        _surahTriggerTimer: null,
        _surahTriggerCleanupTimer: null,
        _pendingNavigationRequest: null,
        _navigationDebounceTimer: null,
        _navigationRevealUnlockTimer: null,
        _navigationRevealLocked: false,
        _fitRunCounter: 0,
        _lastFittedPageNumber: 0,
        _pageInputCommitTimer: null,
        _searchRequestSerial: 0,
        _searchStreamObserver: null,
        _lastSearchStreamPayloadRaw: '',
        _stopLivewireMorphedHook: null,
        _searchResultsAutoAnimateStop: null,
        _searchModalCloseDebounceTimer: null,
        _surahDirectoryAutoFocusToken: 0,
        _surahDirectoryAutoFocusTimer: null,
        _surahDirectoryAutoFocusRaf: null,
        _modalLayoutResumeTimer: null,
        _activeModalIds: new Set(),
        _isModalLifecycleSettling: false,
        _wordPressHoldTimer: null,
        _suppressNextWordClick: false,
        _skipNextSearchModalCloseLayout: false,
        _lastKnownModalOpenState: false,
        _lastPageInputCommitPage: 0,
        _lastPageInputCommitAt: 0,
        _lastPageInputVisualValue: 1,
        _lastWordHoldAt: 0,
        wordPress: {
            active: false,
            pointerId: null,
            startX: 0,
            startY: 0,
            holdTriggered: false,
            word: null,
        },

        init() {
            this.applyPayload(this.initialPayload, { setPageNumber: true });
            this.applyControlPanelSettings(this.initialSettings);
            this.buildSurahDirectory(
                Array.isArray(this.initialPayload.surahDirectory) &&
                    this.initialPayload.surahDirectory.length > 0
                    ? this.initialPayload.surahDirectory
                    : this.search.surahDirectory,
            );
            this.refreshSurahTriggerCaption(false);
            this.syncSearchActiveSurahNumber();

            const restoredPage = clampPage(
                this.pageNumber,
                this.maxPage || this.initialPayload.maxPage,
            );

            this.pageNumber = restoredPage;
            this.pageInput = restoredPage;
            this._lastPageInputVisualValue = restoredPage;

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

            if (this.$wire?.$hook) {
                this._stopLivewireMorphedHook = this.$wire.$hook('morphed', () => {
                    if (!this.ready || this.mushafLines.length === 0) {
                        return;
                    }

                    this.$nextTick(() => {
                        this.initializeLayoutObservers();
                    });
                    this.scheduleLayout({ revealDelayMs: 170 });
                });
            }

            this.$nextTick(() => {
                this.initializeLayoutObservers();
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

            this.teardownLayoutObservers();

            if (this._viewportChangeDebounceTimer !== null) {
                clearTimeout(this._viewportChangeDebounceTimer);
                this._viewportChangeDebounceTimer = null;
            }

            if (this._surahTriggerTimer !== null) {
                clearTimeout(this._surahTriggerTimer);
                this._surahTriggerTimer = null;
            }

            if (this._surahTriggerCleanupTimer !== null) {
                clearTimeout(this._surahTriggerCleanupTimer);
                this._surahTriggerCleanupTimer = null;
            }

            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }

            if (this._searchModalCloseDebounceTimer !== null) {
                clearTimeout(this._searchModalCloseDebounceTimer);
                this._searchModalCloseDebounceTimer = null;
            }

            if (this._modalLayoutResumeTimer !== null) {
                clearTimeout(this._modalLayoutResumeTimer);
                this._modalLayoutResumeTimer = null;
            }

            this._activeModalIds.clear();
            this._isModalLifecycleSettling = false;

            if (this._wordPressHoldTimer !== null) {
                clearTimeout(this._wordPressHoldTimer);
                this._wordPressHoldTimer = null;
            }

            if (this.pageCounterPulse.timer !== null) {
                clearTimeout(this.pageCounterPulse.timer);
                this.pageCounterPulse.timer = null;
            }

            this.teardownSearchStreamObserver();
            this._lastSearchStreamPayloadRaw = '';

            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                this._searchResultsAutoAnimateStop();
                this._searchResultsAutoAnimateStop = null;
            }

            if (typeof this._stopLivewireMorphedHook === 'function') {
                this._stopLivewireMorphedHook();
                this._stopLivewireMorphedHook = null;
            }

            this._pendingNavigationRequest = null;
            this._navigationRevealLocked = false;
        },

        initializeLayoutObservers() {
            this.teardownLayoutObservers();

            const contentElement = this.$refs.pageContent;
            const frameElement = this.$refs.pageFrame;

            if (contentElement instanceof Element && typeof MutationObserver !== 'undefined') {
                this._layoutMutationObserver = new MutationObserver(() => {
                    if (!this.ready || this.mushafLines.length === 0 || this.isLoadingPage) {
                        return;
                    }

                    this.scheduleLayout({ revealDelayMs: 170 });
                });

                this._layoutMutationObserver.observe(contentElement, {
                    childList: true,
                    subtree: true,
                });
            }

            if (frameElement instanceof Element && typeof ResizeObserver !== 'undefined') {
                this._layoutResizeObserver = new ResizeObserver(() => {
                    if (!this.ready || this.mushafLines.length === 0 || this.isLoadingPage) {
                        return;
                    }

                    this.scheduleLayout({ revealDelayMs: 160 });
                });

                this._layoutResizeObserver.observe(frameElement);
            }
        },

        teardownLayoutObservers() {
            if (this._layoutMutationObserver) {
                this._layoutMutationObserver.disconnect();
                this._layoutMutationObserver = null;
            }

            if (this._layoutResizeObserver) {
                this._layoutResizeObserver.disconnect();
                this._layoutResizeObserver = null;
            }
        },

        async bootstrap() {
            await this.ensurePersistentStorage();
            await this.ensureCurrentPageLoaded();
            await this.ensureCanonicalPanelWidth();
            await this.layoutPageGuaranteed({ revealDelayMs: 240 });
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

        buildDigitMorphSegments(previousValue, nextValue) {
            const previous = String(clampPage(previousValue, this.maxPage));
            const next = String(clampPage(nextValue, this.maxPage));
            const length = this.pageCounterDigitLength();
            const previousChars = previous.padStart(length, ' ').split('');
            const nextChars = next.padStart(length, ' ').split('');

            const segments = nextChars.map((nextChar, index) => {
                const previousChar = previousChars[index] ?? '';
                const prev = previousChar === ' ' ? '' : previousChar;
                const nextValueChar = nextChar === ' ' ? '' : nextChar;

                return {
                    key: `${index}:${prev}->${nextValueChar}`,
                    prev,
                    next: nextValueChar,
                    changed: prev !== nextValueChar,
                };
            });

            return {
                segments,
                hasChanges: segments.some((segment) => segment.changed),
            };
        },

        pageCounterDigitLength() {
            return Math.max(3, String(clampPage(this.maxPage, this.maxPage)).length);
        },

        pageCounterDisplayDigits(value) {
            return String(clampPage(value, this.maxPage))
                .padStart(this.pageCounterDigitLength(), ' ')
                .split('')
                .map((digit) => (digit === ' ' ? '' : digit));
        },

        triggerPageCounterPulse(previousValue, nextValue) {
            if (this.pageCounterPulse.timer !== null) {
                clearTimeout(this.pageCounterPulse.timer);
                this.pageCounterPulse.timer = null;
            }

            const morph = this.buildDigitMorphSegments(previousValue, nextValue);

            this.pageCounterPulse.isActive = false;
            this.pageCounterPulse.segments = morph.segments;
            this.pageCounterPulse.hasChanges = morph.hasChanges;

            if (!morph.hasChanges) {
                return;
            }

            requestAnimationFrame(() => {
                this.pageCounterPulse.isActive = true;
            });

            this.pageCounterPulse.timer = window.setTimeout(() => {
                this.pageCounterPulse.isActive = false;
                this.pageCounterPulse.timer = null;
            }, 540);
        },

        navigationBasePage() {
            const pendingTargetPage = Number(this._pendingNavigationRequest?.targetPage ?? 0);

            if (pendingTargetPage > 0) {
                return pendingTargetPage;
            }

            const visualPage = clampPage(
                Number(this.pageInput ?? this._lastPageInputVisualValue ?? this.pageNumber),
                this.maxPage,
            );

            if (visualPage > 0) {
                return visualPage;
            }

            return this.pageNumber;
        },

        resolveNavigationDirection(targetPage, direction = null) {
            if (direction === 'prev' || direction === 'next') {
                return direction;
            }

            const basePage = this.navigationBasePage();

            return targetPage >= basePage ? 'next' : 'prev';
        },

        schedulePendingNavigationCommit(delayMs = navigationSettleDelayMs) {
            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            this._navigationDebounceTimer = window.setTimeout(
                () => {
                    this._navigationDebounceTimer = null;
                    void this.commitPendingNavigation();
                },
                Math.max(0, Math.trunc(Number(delayMs) || navigationSettleDelayMs)),
            );
        },

        setNavigationRevealLock(durationMs = navigationRevealLockDurationMs) {
            this._navigationRevealLocked = true;

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            this._navigationRevealUnlockTimer = window.setTimeout(
                () => {
                    this._navigationRevealUnlockTimer = null;
                    this._navigationRevealLocked = false;

                    if (this._pendingNavigationRequest !== null) {
                        this.schedulePendingNavigationCommit(navigationSettleDelayMs);
                    }
                },
                Math.max(120, Math.trunc(Number(durationMs) || navigationRevealLockDurationMs)),
            );
        },

        async commitPendingNavigation() {
            if (this._pendingNavigationRequest === null) {
                return;
            }

            if (this._navigationRevealLocked || this.isLoadingPage) {
                return;
            }

            const request = this._pendingNavigationRequest;
            this._pendingNavigationRequest = null;

            await this.goToPage(request.targetPage, {
                direction: request.direction,
                animate: request.animate,
                activeAyahIndex: request.activeAyahIndex,
                forceRefit: request.forceRefit,
            });

            if (request.animate) {
                this.setNavigationRevealLock();
            }
        },

        async navigateToPage(
            targetPage,
            {
                direction = 'next',
                animate = true,
                activeAyahIndex = null,
                source = 'generic',
                forceRefit = false,
                commitNow = false,
                settleDelayMs = navigationSettleDelayMs,
            } = {},
        ) {
            const normalizedTargetPage = clampPage(targetPage, this.maxPage);
            const resolvedDirection = this.resolveNavigationDirection(
                normalizedTargetPage,
                direction,
            );
            const previousInputPage = clampPage(this.pageInput, this.maxPage);

            if (previousInputPage !== normalizedTargetPage) {
                this.triggerPageCounterPulse(previousInputPage, normalizedTargetPage);
            }

            if (animate) {
                this.beginLayoutCycle();
            }

            this.pageInput = normalizedTargetPage;
            this._lastPageInputVisualValue = normalizedTargetPage;

            this._pendingNavigationRequest = {
                targetPage: normalizedTargetPage,
                direction: resolvedDirection,
                animate: Boolean(animate),
                activeAyahIndex,
                source,
                forceRefit: Boolean(forceRefit),
            };

            if (this._navigationRevealLocked || this.isLoadingPage) {
                return;
            }

            if (commitNow) {
                await this.commitPendingNavigation();

                return;
            }

            this.schedulePendingNavigationCommit(settleDelayMs);
        },

        async nextPage(source = 'generic') {
            const basePage = this.navigationBasePage();

            await this.navigateToPage(basePage + 1, {
                direction: 'next',
                source,
            });
        },

        async previousPage(source = 'generic') {
            const basePage = this.navigationBasePage();

            if (basePage <= 1) {
                this.requestReaderGateNavigation(source);

                return;
            }

            await this.navigateToPage(basePage - 1, {
                direction: 'prev',
                source,
            });
        },

        isFirstNavigationPage() {
            return this.navigationBasePage() <= 1;
        },

        isLastNavigationPage() {
            return this.maxPage > 0 && this.navigationBasePage() >= this.maxPage;
        },

        async goNextFromChevron() {
            if (this.isLastNavigationPage()) {
                return;
            }

            await this.nextPage('chevron');
        },

        async goPreviousFromChevron() {
            await this.previousPage('chevron');
        },

        async handleRequestedNavigation(kind, detail = {}) {
            this.resetSwipeState();

            if (kind === 'next') {
                await this.goNextFromChevron();

                return;
            }

            if (kind === 'prev') {
                await this.goPreviousFromChevron();

                return;
            }

            if (kind === 'page') {
                const requestedPage = clampPage(detail?.page ?? this.pageInput, this.maxPage);
                await this.navigateToPage(requestedPage, {
                    direction: this.resolveNavigationDirection(requestedPage),
                    animate: true,
                    source: 'page-event',
                    forceRefit: true,
                });
            }
        },

        resetNavigationQueueForPriorityJump() {
            if (this._navigationDebounceTimer !== null) {
                clearTimeout(this._navigationDebounceTimer);
                this._navigationDebounceTimer = null;
            }

            if (this._navigationRevealUnlockTimer !== null) {
                clearTimeout(this._navigationRevealUnlockTimer);
                this._navigationRevealUnlockTimer = null;
            }

            this._pendingNavigationRequest = null;
            this._navigationRevealLocked = false;
        },

        async onGlobalArrowNavigate(direction, event = null) {
            if (this.search.modalOpen) {
                return;
            }

            if (
                event?.target?.closest?.(
                    'input:not(.quran-page-counter-input), textarea, select, [contenteditable="true"]',
                )
            ) {
                return;
            }

            if (direction === 'left') {
                await this.nextPage('keyboard');

                return;
            }

            if (direction === 'right') {
                await this.previousPage('keyboard');
            }
        },

        dispatchPageNavigationRequest(targetPage, source = 'generic') {
            window.dispatchEvent(
                new CustomEvent('quran-go-page', {
                    detail: {
                        page: clampPage(targetPage, this.maxPage),
                        source,
                    },
                }),
            );
        },

        async goToPage(
            pageNumber,
            { direction = 'next', animate = true, activeAyahIndex = null, forceRefit = false } = {},
        ) {
            const normalizedPage = clampPage(pageNumber, this.maxPage);
            this.clearWordPressState();
            this.hoveredAyahIndex = 0;
            this.hoveredWordIndex = 0;

            if (normalizedPage === this.pageNumber && this.mushafLines.length > 0) {
                if (this.pageInput !== normalizedPage) {
                    this.triggerPageCounterPulse(this.pageInput, normalizedPage);
                }

                this.pageInput = normalizedPage;
                this._lastPageInputVisualValue = normalizedPage;

                if (forceRefit) {
                    await this.layoutPageGuaranteed({ revealDelayMs: 200 });
                }

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
                this.refreshSurahTriggerCaption(animate);
                this.syncSearchActiveSurahNumber();
                this.activeAyahIndex =
                    Number.isFinite(Number(activeAyahIndex)) && Number(activeAyahIndex) > 0
                        ? Math.trunc(Number(activeAyahIndex))
                        : 0;
                this.activeWordIndex = 0;

                if (animate) {
                    this.playPageMotion(direction);
                }

                this.prefetchNeighborPages(normalizedPage);
                await this.layoutPageGuaranteed({ revealDelayMs: 220 });
            } finally {
                this.isLoadingPage = false;
            }
        },

        onPageInputInput() {
            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }

            const normalizedInputPage = clampPage(this.pageInput, this.maxPage);
            const previousVisualPage = clampPage(this._lastPageInputVisualValue, this.maxPage);

            if (normalizedInputPage !== previousVisualPage) {
                this.triggerPageCounterPulse(previousVisualPage, normalizedInputPage);
            }

            this._lastPageInputVisualValue = normalizedInputPage;
        },

        async onSliderInput() {
            this.onPageInputInput();
        },

        async onSliderCommit() {
            const targetPage = clampPage(this.pageInput, this.maxPage);
            this.pageInput = targetPage;
            this._lastPageInputVisualValue = targetPage;
            this.dispatchPageNavigationRequest(targetPage, 'page-slider-commit');
        },

        async onPageInputBlur() {
            const now = Date.now();
            const targetPage = clampPage(this.pageInput, this.maxPage);

            if (
                targetPage === this._lastPageInputCommitPage &&
                now - this._lastPageInputCommitAt < 420
            ) {
                return;
            }

            await this.onPageInputCommit({
                force: true,
                commitNow: true,
                source: 'page-input-blur',
            });
        },

        async onPageInputCommit({ force = false, commitNow = false, source = 'page-input' } = {}) {
            if (this._pageInputCommitTimer !== null) {
                clearTimeout(this._pageInputCommitTimer);
                this._pageInputCommitTimer = null;
            }

            const targetPage = clampPage(this.pageInput, this.maxPage);
            const direction = this.resolveNavigationDirection(targetPage);
            this._lastPageInputCommitPage = targetPage;
            this._lastPageInputCommitAt = Date.now();

            this.pageInput = targetPage;
            this._lastPageInputVisualValue = targetPage;

            if (!force && targetPage === this.pageNumber) {
                return;
            }

            await this.navigateToPage(targetPage, {
                direction,
                animate: true,
                source,
                forceRefit: true,
                commitNow: Boolean(commitNow),
            });
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
            this.basmallahFontFamily = normalizedPayload.basmallahFontFamily;
            this.basmallahFontUrl = normalizedPayload.basmallahFontUrl;
            this.basmallahFontFormat = normalizedPayload.basmallahFontFormat;
            this.basmallahText = normalizedPayload.basmallahText;
            this.surahHeaderFontFamily =
                normalizedPayload.surahHeaderFontFamily ?? this.surahHeaderFontFamily;
            this.surahHeaderFontUrl =
                normalizedPayload.surahHeaderFontUrl ?? this.surahHeaderFontUrl;
            this.surahHeaderFontFormat =
                normalizedPayload.surahHeaderFontFormat ?? this.surahHeaderFontFormat;

            if (
                normalizedPayload.surahNames &&
                Object.keys(normalizedPayload.surahNames).length > 0
            ) {
                this.search.surahNames = normalizedPayload.surahNames;
            }

            const hasIncomingSurahDirectory =
                Array.isArray(normalizedPayload.surahDirectory) &&
                normalizedPayload.surahDirectory.length > 0;

            this.buildSurahDirectory(
                hasIncomingSurahDirectory
                    ? normalizedPayload.surahDirectory
                    : this.search.surahDirectory,
            );

            if (setPageNumber) {
                this.pageNumber = clampPage(
                    normalizedPayload.pageNumber,
                    normalizedPayload.maxPage,
                );
            }

            if (this.pageInput !== this.pageNumber) {
                this.triggerPageCounterPulse(this.pageInput, this.pageNumber);
            }

            this.pageInput = this.pageNumber;
            this._lastPageInputVisualValue = this.pageNumber;
            this.syncPageFontFace();
            this.syncBasmallahFontFace();
            this.syncSurahHeaderFontFace();
        },

        async nextTickAsync() {
            await new Promise((resolve) => this.$nextTick(resolve));
        },

        async waitForPageFontReady() {
            const family = String(this.qpcPageFontFamily ?? '').trim();
            const basmallahFamily = String(this.basmallahFontFamily ?? '').trim();
            const surahHeaderFamily = String(this.surahHeaderFontFamily ?? '').trim();

            if ((!family && !basmallahFamily && !surahHeaderFamily) || !document.fonts?.load) {
                return;
            }

            try {
                if (family) {
                    await document.fonts.load(`32px '${family}'`, 'الحمد لله');
                }

                if (basmallahFamily) {
                    await document.fonts.load(
                        `32px '${basmallahFamily}'`,
                        this.preferredBasmallahText(),
                    );
                }

                if (surahHeaderFamily) {
                    await document.fonts.load(`32px '${surahHeaderFamily}'`, 'الفاتحة');
                }

                await document.fonts.ready;
            } catch (_) {
                // Ignore font loading failures and continue with fallback glyphs.
            }
        },

        async waitForFontReady(family) {
            const normalizedFamily = String(family ?? '').trim();

            if (!normalizedFamily || !document.fonts?.load) {
                return;
            }

            try {
                await document.fonts.load(`32px '${normalizedFamily}'`, 'الحمد لله');
                await document.fonts.ready;
            } catch (_) {
                // Ignore font loading failures and continue with fallback glyphs.
            }
        },

        syncDynamicFontFace({ styleId, family, url, format = 'woff2' }) {
            let styleTag = document.getElementById(styleId);
            const normalizedFamily = String(family ?? '').trim();
            const normalizedUrl = String(url ?? '').trim();
            const normalizedFormat = String(format ?? 'woff2').trim() || 'woff2';

            if (!normalizedFamily || !normalizedUrl) {
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

            styleTag.textContent = `@font-face { font-family: '${normalizedFamily}'; src: url('${normalizedUrl}') format('${normalizedFormat}'); font-display: block; }`;
        },

        syncPageFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-page-font',
                family: this.qpcPageFontFamily,
                url: this.qpcPageFontUrl,
                format: this.qpcPageFontFormat,
            });
        },

        syncBasmallahFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-basmallah-font',
                family: this.basmallahFontFamily,
                url: this.basmallahFontUrl,
                format: this.basmallahFontFormat,
            });
        },

        syncSurahHeaderFontFace() {
            this.syncDynamicFontFace({
                styleId: 'quran-reader-dynamic-surah-header-font',
                family: this.surahHeaderFontFamily,
                url: this.surahHeaderFontUrl,
                format: this.surahHeaderFontFormat,
            });
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

        openModalCount() {
            return Array.from(document.querySelectorAll('.fi-modal')).filter((modalElement) =>
                modalElement.classList.contains('fi-modal-open'),
            ).length;
        },

        beginLayoutCycle() {
            this._layoutToken += 1;
            this.isFittingPage = true;

            return this._layoutToken;
        },

        hasRenderablePage() {
            return this.ready && this.mushafLines.length > 0;
        },

        holdPageHiddenForModalLifecycle() {
            if (!this.hasRenderablePage()) {
                return;
            }

            this._isModalLifecycleSettling = true;
            this.clearLayoutTimers();
            this.beginLayoutCycle();
        },

        scheduleLayoutAfterModalLifecycle(delayMs = 220) {
            if (!this.hasRenderablePage()) {
                return;
            }

            if (this._modalLayoutResumeTimer !== null) {
                clearTimeout(this._modalLayoutResumeTimer);
                this._modalLayoutResumeTimer = null;
            }

            this._modalLayoutResumeTimer = window.setTimeout(
                () => {
                    this._modalLayoutResumeTimer = null;
                    this._isModalLifecycleSettling = false;
                    this.scheduleLayout({ revealDelayMs: 240 });
                },
                Math.max(0, Math.trunc(Number(delayMs) || 220)),
            );
        },

        resumeLayoutWhenNoOpenModals(attempt = 0) {
            if (!this.hasRenderablePage()) {
                this._isModalLifecycleSettling = false;

                return;
            }

            const normalizedAttempt = Math.max(0, Math.trunc(Number(attempt) || 0));
            const remainingModalCount = this.openModalCount();

            if (remainingModalCount <= 0) {
                this._activeModalIds.clear();
                this.scheduleLayoutAfterModalLifecycle(220);

                return;
            }

            this._isModalLifecycleSettling = true;

            if (normalizedAttempt >= 24) {
                this._activeModalIds.clear();
                this._isModalLifecycleSettling = false;
                this.scheduleLayout({ revealDelayMs: 240 });

                return;
            }

            window.setTimeout(() => {
                this.resumeLayoutWhenNoOpenModals(normalizedAttempt + 1);
            }, 40);
        },

        trackModalLifecycle(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const openModalCount = this.openModalCount();

            if (kind === 'opened') {
                if (modalId !== '') {
                    this._activeModalIds.add(modalId);
                }

                this.holdPageHiddenForModalLifecycle();

                return;
            }

            if (kind === 'closing') {
                if (modalId === '' || this._activeModalIds.has(modalId) || openModalCount > 0) {
                    this.holdPageHiddenForModalLifecycle();
                    this.resumeLayoutWhenNoOpenModals();
                }

                return;
            }

            if (kind === 'closed') {
                if (modalId !== '') {
                    this._activeModalIds.delete(modalId);
                }

                this.holdPageHiddenForModalLifecycle();

                window.setTimeout(() => {
                    this.resumeLayoutWhenNoOpenModals();
                }, 24);
            }
        },

        queuePageReveal(layoutToken, delayMs = 180) {
            this._revealTimer = window.setTimeout(() => {
                if (layoutToken !== this._layoutToken) {
                    return;
                }

                if (this._isModalLifecycleSettling || this._activeModalIds.size > 0) {
                    this.isFittingPage = true;
                    this.queuePageReveal(layoutToken, 120);

                    return;
                }

                if (
                    this._navigationRevealLocked ||
                    this._pendingNavigationRequest !== null ||
                    this.isLoadingPage
                ) {
                    this.isFittingPage = true;
                    this.queuePageReveal(layoutToken, 90);

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
                const currentBucket = this.viewportWidthBucket();

                if (this._panelWidthBucket !== currentBucket) {
                    this.panelWidthPx = null;
                    await this.ensureCanonicalPanelWidth();
                }

                this.scheduleLayout({ revealDelayMs: 150 });
            }, 90);
        },

        viewportWidthBucket() {
            const width = Number(window.innerWidth ?? 0);

            if (width >= 1536) {
                return '2xl';
            }

            if (width >= 1280) {
                return 'xl';
            }

            if (width >= 1024) {
                return 'lg';
            }

            if (width >= 768) {
                return 'md';
            }

            if (width >= 640) {
                return 'sm';
            }

            return 'base';
        },

        async ensureCanonicalPanelWidth() {
            const currentBucket = this.viewportWidthBucket();

            if (
                Number.isFinite(Number(this.panelWidthPx)) &&
                Number(this.panelWidthPx) > 0 &&
                this._panelWidthBucket === currentBucket
            ) {
                return;
            }

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
                this.panelProbeFontFamily = payload?.qpcPageFontFamily ?? null;
                this.panelProbeFontUrl = payload?.qpcPageFontUrl ?? null;
                this.panelProbeFontFormat = payload?.qpcPageFontFormat ?? null;

                if (this.panelProbeLines.length < 1) {
                    return;
                }

                this.syncDynamicFontFace({
                    styleId: 'quran-reader-dynamic-probe-font',
                    family: this.panelProbeFontFamily,
                    url: this.panelProbeFontUrl,
                    format: this.panelProbeFontFormat,
                });

                await this.nextTickAsync();
                await this.waitForFontReady(this.panelProbeFontFamily);
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
                const contentTargetWidth = Math.max(1, probeSize.width);
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
                this._panelWidthBucket = currentBucket;

                rootElement.style.setProperty('--quran-page-scale', String(previousScale || 1));
            })();

            try {
                await this._canonicalWidthPromise;
            } finally {
                this._canonicalWidthPromise = null;
            }
        },

        scheduleLayout({ revealDelayMs = 180 } = {}) {
            if (this._isModalLifecycleSettling || this._activeModalIds.size > 0) {
                this.holdPageHiddenForModalLifecycle();

                return;
            }

            this.clearLayoutTimers();

            this._layoutRaf = requestAnimationFrame(() => {
                this._layoutRaf = null;
                void this.layoutPageGuaranteed({
                    revealDelayMs,
                    maxAttempts: 4,
                });
            });
        },

        async waitForStableRenderedText(maxFrames = 8) {
            const contentElement = this.$refs.pageContent;

            if (!(contentElement instanceof Element)) {
                await nextAnimationFrame();

                return;
            }

            let previousWidth = 0;
            let previousHeight = 0;
            let stableFrames = 0;
            const frames = Math.max(2, Math.trunc(Number(maxFrames) || 8));

            for (let frame = 0; frame < frames; frame += 1) {
                await nextAnimationFrame();

                const { width, height } = this.measureRenderedBounds(contentElement);

                if (width <= 1 || height <= 1) {
                    stableFrames = 0;
                    previousWidth = width;
                    previousHeight = height;

                    continue;
                }

                const widthDelta = Math.abs(width - previousWidth);
                const heightDelta = Math.abs(height - previousHeight);

                if (widthDelta < 0.6 && heightDelta < 0.6) {
                    stableFrames += 1;
                } else {
                    stableFrames = 0;
                }

                previousWidth = width;
                previousHeight = height;

                if (stableFrames >= 2) {
                    return;
                }
            }
        },

        async layoutPage({ revealDelayMs = 180 } = {}) {
            const layoutToken = this.beginLayoutCycle();

            await this.nextTickAsync();
            await this.waitForPageFontReady();
            await nextAnimationFrame();
            await this.waitForStableRenderedText(10);

            this.fitPageToViewport();
            this.queuePageReveal(layoutToken, revealDelayMs);
        },

        async layoutPageGuaranteed({ revealDelayMs = 180, maxAttempts = 4 } = {}) {
            const attempts = Math.max(2, Math.trunc(Number(maxAttempts) || 4));

            for (let attempt = 0; attempt < attempts; attempt += 1) {
                const fitRunsBeforeAttempt = this._fitRunCounter;
                await this.layoutPage({
                    revealDelayMs: attempt === 0 ? revealDelayMs : 160,
                });

                if (
                    this._fitRunCounter > fitRunsBeforeAttempt &&
                    this._lastFittedPageNumber === this.pageNumber
                ) {
                    return;
                }

                await wait(55);
            }
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
            const computedRootStyles = window.getComputedStyle(rootElement);
            const fitHeightRatio = Math.min(
                1,
                Math.max(
                    0.5,
                    Number.parseFloat(
                        computedRootStyles.getPropertyValue('--quran-fit-height-ratio'),
                    ) || 1,
                ),
            );
            const availableHeight = Math.max(1, frameElement.clientHeight * fitHeightRatio);
            const minScale = Math.max(
                0.05,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-min-page-scale')) ||
                    0.1,
            );
            const maxScale = Math.max(
                minScale,
                Number.parseFloat(computedRootStyles.getPropertyValue('--quran-max-page-scale')) ||
                    1,
            );
            const naturalSize = this.measureRenderedBounds(contentElement);
            const initialScale = Math.min(
                availableWidth / Math.max(1, naturalSize.width),
                availableHeight / Math.max(1, naturalSize.height),
                maxScale,
            );

            let normalizedScale = Number.isFinite(initialScale)
                ? Math.max(minScale, Math.min(maxScale, initialScale))
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

                normalizedScale = Math.max(
                    minScale,
                    Number((normalizedScale * adjustScale).toFixed(4)),
                );
            }

            rootElement.style.setProperty('--quran-page-scale', String(normalizedScale));
            this.pageScale = normalizedScale;
            this._fitRunCounter += 1;
            this._lastFittedPageNumber = this.pageNumber;
        },

        async prefetchFontAsset(payload) {
            const fontUrl = String(payload?.qpcPageFontUrl ?? '').trim();
            const surahHeaderFontUrl = String(payload?.surahHeaderFontUrl ?? '').trim();

            if (fontUrl) {
                await cacheAssetResponse({
                    url: fontUrl,
                    cacheName: this.cacheNames.fonts,
                });
            }

            if (surahHeaderFontUrl) {
                await cacheAssetResponse({
                    url: surahHeaderFontUrl,
                    cacheName: this.cacheNames.fonts,
                });
            }
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
                this.resetSwipeState();

                return;
            }

            if (event.target?.closest?.('input, textarea, select, [contenteditable="true"]')) {
                this.resetSwipeState();

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
            this.hoveredAyahIndex = 0;
            this.hoveredWordIndex = 0;
        },

        resetSwipeState() {
            this.swipe.active = false;
            this.swipe.pointerId = null;
            this.swipe.pointerType = null;
            this.swipe.source = null;
        },

        async onSwipeEnd(event) {
            if (!this.swipe.active) {
                return;
            }

            if (event?.target?.closest?.('[data-no-swipe]')) {
                this.resetSwipeState();

                return;
            }

            if (event?.target?.closest?.('input, textarea, select, [contenteditable="true"]')) {
                this.resetSwipeState();

                return;
            }

            const source = event?.type?.startsWith('touch') ? 'touch' : 'pointer';

            if (this.swipe.source && this.swipe.source !== source) {
                this.resetSwipeState();

                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                this.resetSwipeState();

                return;
            }

            if (this.swipe.pointerId !== null && point.pointerId !== this.swipe.pointerId) {
                this.resetSwipeState();

                return;
            }

            const deltaX = point.x - this.swipe.startX;
            const deltaY = point.y - this.swipe.startY;
            const absX = Math.abs(deltaX);
            const absY = Math.abs(deltaY);

            this.resetSwipeState();

            if (Date.now() - this._lastWordHoldAt < 360) {
                return;
            }

            if (absX < 40 || absX < absY) {
                return;
            }

            if (deltaX > 0) {
                await this.nextPage('swipe');

                return;
            }

            await this.previousPage('swipe');
        },

        onSwipeCancel() {
            this.resetSwipeState();
            this.clearWordPressState();
        },

        pageContentStyle() {
            return 'width: max-content;';
        },

        readerPanelStyle() {
            return 'touch-action: pan-y;';
        },

        normalizeBooleanFlag(value, fallback = false) {
            if (typeof value === 'boolean') {
                return value;
            }

            if (value === 1 || value === '1') {
                return true;
            }

            if (value === 0 || value === '0') {
                return false;
            }

            if (value === null || value === undefined || value === '') {
                return Boolean(fallback);
            }

            const normalized = String(value).trim().toLowerCase();

            if (['true', 'yes', 'on'].includes(normalized)) {
                return true;
            }

            if (['false', 'no', 'off'].includes(normalized)) {
                return false;
            }

            return Boolean(fallback);
        },

        applyControlPanelSettings(controlPanel = {}) {
            const input =
                controlPanel && typeof controlPanel === 'object' && !Array.isArray(controlPanel)
                    ? controlPanel
                    : {};
            const hasVisualEnhancements = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.enableVisualEnhancements,
            );
            const hasWordTargeting = Object.prototype.hasOwnProperty.call(
                input,
                controlPanelSettingKeys.targetWordsByDefault,
            );
            const defaultVisualEnhancements = this.normalizeBooleanFlag(
                this.initialSettings?.enableVisualEnhancements,
                true,
            );
            const defaultWordTargeting = this.normalizeBooleanFlag(
                this.initialSettings?.targetWordsByDefault,
                false,
            );

            this.doesEnableVisualEnhancements = this.normalizeBooleanFlag(
                hasVisualEnhancements
                    ? input[controlPanelSettingKeys.enableVisualEnhancements]
                    : defaultVisualEnhancements,
                true,
            );
            this.doesTargetWordsByDefault = this.normalizeBooleanFlag(
                hasWordTargeting
                    ? input[controlPanelSettingKeys.targetWordsByDefault]
                    : defaultWordTargeting,
                false,
            );
        },

        interactionTargetsWords() {
            return Boolean(this.doesTargetWordsByDefault);
        },

        isSelectableWord(word) {
            const ayahIndex = Number(word?.ayah_index ?? 0);
            const wordIndex = Number(word?.word_index ?? 0);

            if (this.interactionTargetsWords()) {
                return Number.isFinite(wordIndex) && wordIndex > 0;
            }

            return Number.isFinite(ayahIndex) && ayahIndex > 0;
        },

        selectAyah(ayahIndex) {
            const normalizedAyahIndex = Number(ayahIndex);

            if (!Number.isFinite(normalizedAyahIndex) || normalizedAyahIndex < 1) {
                return;
            }
            const normalized = Math.trunc(normalizedAyahIndex);

            if (this.activeAyahIndex === normalized) {
                this.activeAyahIndex = 0;
                this.hoveredAyahIndex = 0;
                this.activeWordIndex = 0;
                this.hoveredWordIndex = 0;

                return;
            }

            this.activeAyahIndex = normalized;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;
        },

        selectWord(wordIndex) {
            const normalizedWordIndex = Number(wordIndex);

            if (!Number.isFinite(normalizedWordIndex) || normalizedWordIndex < 1) {
                return;
            }

            const normalized = Math.trunc(normalizedWordIndex);

            if (this.activeWordIndex === normalized) {
                this.activeWordIndex = 0;
                this.hoveredWordIndex = 0;
                this.activeAyahIndex = 0;
                this.hoveredAyahIndex = 0;

                return;
            }

            this.activeWordIndex = normalized;
            this.hoveredWordIndex = 0;
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
        },

        selectDefaultSegment(word) {
            if (this.interactionTargetsWords()) {
                this.selectWord(Number(word?.word_index ?? 0));

                return;
            }

            this.selectAyah(Number(word?.ayah_index ?? 0));
        },

        selectHoldSegment(word) {
            if (this.interactionTargetsWords()) {
                this.selectAyah(Number(word?.ayah_index ?? 0));

                return;
            }

            this.selectWord(Number(word?.word_index ?? 0));
        },

        clearWordPressState() {
            if (this._wordPressHoldTimer !== null) {
                clearTimeout(this._wordPressHoldTimer);
                this._wordPressHoldTimer = null;
            }

            this.wordPress.active = false;
            this.wordPress.pointerId = null;
            this.wordPress.startX = 0;
            this.wordPress.startY = 0;
            this.wordPress.holdTriggered = false;
            this.wordPress.word = null;
        },

        onWordPointerDown(event, word) {
            if (!this.isSelectableWord(word)) {
                this.clearWordPressState();

                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                this.clearWordPressState();

                return;
            }

            this.clearWordPressState();
            this.wordPress.active = true;
            this.wordPress.pointerId = point.pointerId;
            this.wordPress.startX = point.x;
            this.wordPress.startY = point.y;
            this.wordPress.holdTriggered = false;
            this.wordPress.word = word;
            this._wordPressHoldTimer = window.setTimeout(() => {
                if (!this.wordPress.active || !this.wordPress.word) {
                    return;
                }

                this.wordPress.holdTriggered = true;
                this._suppressNextWordClick = true;
                this._lastWordHoldAt = Date.now();
                this.selectHoldSegment(this.wordPress.word);
            }, wordPressHoldDelayMs);
        },

        onWordPointerMove(event) {
            if (!this.wordPress.active) {
                return;
            }

            const point = this.swipePoint(event);

            if (!point) {
                return;
            }

            if (
                this.wordPress.pointerId !== null &&
                point.pointerId !== null &&
                this.wordPress.pointerId !== point.pointerId
            ) {
                return;
            }

            const deltaX = Math.abs(point.x - this.wordPress.startX);
            const deltaY = Math.abs(point.y - this.wordPress.startY);

            if (deltaX > 14 || deltaY > 14) {
                this.clearWordPressState();
            }
        },

        onWordPointerUp() {
            this.clearWordPressState();
        },

        onWordPointerCancel() {
            this.clearWordPressState();
        },

        onWordPointerLeave(word) {
            this.clearWordPressState();
            this.clearHoveredSegment(word);
        },

        onWordClick(event, word) {
            if (this._suppressNextWordClick) {
                event?.preventDefault?.();
                this._suppressNextWordClick = false;

                return;
            }

            this.selectDefaultSegment(word);
        },

        setHoveredSegment(word) {
            if (this.interactionTargetsWords()) {
                const wordIndex = Number(word?.word_index ?? 0);

                if (Number.isFinite(wordIndex) && wordIndex > 0) {
                    this.hoveredWordIndex = Math.trunc(wordIndex);
                    this.hoveredAyahIndex = 0;
                }

                return;
            }

            this.setHoveredAyah(Number(word?.ayah_index ?? 0));
            this.hoveredWordIndex = 0;
        },

        clearHoveredSegment(word = null) {
            if (word === null) {
                this.hoveredAyahIndex = 0;
                this.hoveredWordIndex = 0;

                return;
            }

            if (this.interactionTargetsWords()) {
                const wordIndex = Number(word?.word_index ?? 0);

                if (Number.isFinite(wordIndex) && this.hoveredWordIndex === Math.trunc(wordIndex)) {
                    this.hoveredWordIndex = 0;
                }

                return;
            }

            this.clearHoveredAyah(Number(word?.ayah_index ?? 0));
        },

        clearAyahSelectionOnBackground(event) {
            if (event?.target?.closest?.('.quran-word-button')) {
                return;
            }

            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;
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
            const marginBlockStart = this.lineMarginBlockStart(line);
            const marginBlockEnd = this.lineMarginBlockEnd(line);

            return `--quran-line-index: ${lineNumber}; margin-block-start: ${marginBlockStart}; margin-block-end: ${marginBlockEnd};`;
        },

        isAyahLineWithWords(line) {
            return (
                String(line?.line_type ?? '') === 'ayah' &&
                Array.isArray(line?.words) &&
                line.words.length > 0
            );
        },

        lineText(line) {
            return String(line?.text ?? '').trim();
        },

        isBasmallahLine(line) {
            return String(line?.line_type ?? '') === 'basmallah';
        },

        preferredBasmallahText() {
            return String(this.basmallahText ?? '').trim();
        },

        shouldRenderConfiguredBasmallah() {
            const configuredText = this.preferredBasmallahText();

            if (configuredText === '') {
                return false;
            }

            const hasPrivateUseGlyphs = /[\uE000-\uF8FF]/u.test(configuredText);
            const configuredFamily = String(this.basmallahFontFamily ?? '').trim();

            if (hasPrivateUseGlyphs && configuredFamily === '') {
                return false;
            }

            return true;
        },

        isBasmallahLineWithWords(line) {
            return (
                this.isBasmallahLine(line) &&
                !this.shouldRenderConfiguredBasmallah() &&
                Array.isArray(line?.words) &&
                line.words.length > 0
            );
        },

        lineByNumber(lineNumber) {
            const normalizedLineNumber = Math.max(0, Math.trunc(Number(lineNumber) || 0));

            if (normalizedLineNumber < 1 || !Array.isArray(this.mushafLines)) {
                return null;
            }

            return (
                this.mushafLines.find(
                    (entry) =>
                        Math.max(0, Math.trunc(Number(entry?.line_number ?? 0))) ===
                        normalizedLineNumber,
                ) ?? null
            );
        },

        nextLineType(line) {
            const lineNumber = Math.max(0, Math.trunc(Number(line?.line_number ?? 0)));
            const nextLine = this.lineByNumber(lineNumber + 1);

            return String(nextLine?.line_type ?? '');
        },

        lineMarginBlockStart(line) {
            if (this.isSurahHeaderLine(line)) {
                return 'calc(var(--quran-line-gap) * var(--quran-gap-scale) * 0.28)';
            }

            if (this.isBasmallahLine(line)) {
                return 'calc(var(--quran-line-gap) * var(--quran-gap-scale) * 0.12)';
            }

            return '0px';
        },

        lineMarginBlockEnd(line) {
            if (this.isSurahHeaderLine(line)) {
                if (this.nextLineType(line) === 'basmallah') {
                    return 'calc(var(--quran-line-gap) * var(--quran-gap-scale) * -0.44)';
                }

                return 'calc(var(--quran-line-gap) * var(--quran-gap-scale) * -0.1)';
            }

            if (this.isBasmallahLine(line)) {
                return 'calc(var(--quran-line-gap) * var(--quran-gap-scale) * var(--quran-basmallah-bottom-gap-scale, 0.04))';
            }

            return '0px';
        },

        shouldRenderLine(line) {
            if (this.isAyahLineWithWords(line)) {
                return true;
            }

            if (this.isBasmallahLine(line)) {
                return true;
            }

            if (this.isSurahHeaderLine(line)) {
                return this.surahHeaderLineText(line) !== '';
            }

            return this.lineText(line) !== '';
        },

        metaLineStyle(line) {
            if (this.isBasmallahLine(line)) {
                return "font-family: 'Amiri', 'Traditional Arabic', serif; color: var(--quran-ink);";
            }

            return this.lineFontStyle();
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

        lineWordClusters(line) {
            if (!Array.isArray(line?.words) || line.words.length < 1) {
                return [];
            }

            const clusters = [];
            let currentCluster = null;

            line.words.forEach((word, wordIndex) => {
                const ayahIndex = Math.max(0, Math.trunc(Number(word?.ayah_index ?? 0)));
                const wordIndexValue = Math.max(
                    0,
                    Math.trunc(Number(word?.word_index ?? wordIndex + 1)),
                );

                if (!currentCluster || currentCluster.ayahIndex !== ayahIndex) {
                    currentCluster = {
                        key: `${line?.line_number ?? 0}-${ayahIndex}-${wordIndexValue}`,
                        ayahIndex,
                        words: [],
                    };
                    clusters.push(currentCluster);
                }

                currentCluster.words.push(word);
            });

            return clusters;
        },

        isAyahClusterActive(cluster) {
            const ayahIndex = Math.max(0, Math.trunc(Number(cluster?.ayahIndex ?? 0)));

            return this.activeAyahIndex > 0 && ayahIndex > 0 && this.activeAyahIndex === ayahIndex;
        },

        isAyahClusterHovered(cluster) {
            const ayahIndex = Math.max(0, Math.trunc(Number(cluster?.ayahIndex ?? 0)));

            return (
                this.hoveredAyahIndex > 0 && ayahIndex > 0 && this.hoveredAyahIndex === ayahIndex
            );
        },

        lineFontStyle() {
            const family = String(this.qpcPageFontFamily ?? '').trim();

            if (!family) {
                return 'color: var(--quran-ink);';
            }

            return `font-family: '${family}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif; color: var(--quran-ink);`;
        },

        basmallahLineStyle(line) {
            const family = String(this.basmallahFontFamily ?? '').trim();

            if (!family) {
                return "font-family: 'Scheherazade New', 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif; color: var(--quran-ink); font-feature-settings: 'liga' 1, 'calt' 1;";
            }

            return `font-family: '${family}', 'Scheherazade New', 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif; color: var(--quran-ink); font-feature-settings: 'liga' 1, 'calt' 1;`;
        },

        probeBasmallahLineStyle(line) {
            const family = String(this.basmallahFontFamily ?? '').trim();

            if (!family) {
                return "font-family: 'Scheherazade New', 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif; color: var(--quran-ink); font-feature-settings: 'liga' 1, 'calt' 1;";
            }

            return `font-family: '${family}', 'Scheherazade New', 'Amiri', 'Noto Naskh Arabic', 'Traditional Arabic', serif; color: var(--quran-ink); font-feature-settings: 'liga' 1, 'calt' 1;`;
        },

        basmallahDisplayText(line) {
            const configuredText = this.preferredBasmallahText();
            const text = this.lineText(line);
            const hasPrivateUseGlyphs = /[\uE000-\uF8FF]/u.test(text);

            const fallbackText = 'بِسْمِ اللَّهِ الرَّحْمَنِ الرَّحِيمِ';

            if (this.shouldRenderConfiguredBasmallah()) {
                return configuredText;
            }

            if (text !== '' && !hasPrivateUseGlyphs) {
                return text;
            }

            return fallbackText;
        },

        surahHeaderLineStyle() {
            const family = String(this.surahHeaderFontFamily ?? '').trim();

            if (!family) {
                return '';
            }

            return `font-family: '${family}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;`;
        },

        probeLineFontStyle() {
            const family = String(this.panelProbeFontFamily ?? '').trim();

            if (!family) {
                return 'color: var(--quran-ink);';
            }

            return `font-family: '${family}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif; color: var(--quran-ink);`;
        },

        isWordActive(word) {
            const wordIndex = Number(word?.word_index ?? 0);

            if (this.activeWordIndex > 0) {
                return wordIndex > 0 && wordIndex === this.activeWordIndex;
            }

            return false;
        },

        isWordHovered(word) {
            const wordIndex = Number(word?.word_index ?? 0);

            if (this.hoveredWordIndex > 0) {
                return wordIndex > 0 && wordIndex === this.hoveredWordIndex;
            }

            return false;
        },

        setHoveredAyah(ayahIndex) {
            const normalizedAyahIndex = Number(ayahIndex);

            if (!Number.isFinite(normalizedAyahIndex) || normalizedAyahIndex < 1) {
                return;
            }

            this.hoveredAyahIndex = Math.trunc(normalizedAyahIndex);
        },

        clearHoveredAyah(ayahIndex = null) {
            if (ayahIndex === null) {
                this.hoveredAyahIndex = 0;

                return;
            }

            const normalizedAyahIndex = Number(ayahIndex);

            if (
                Number.isFinite(normalizedAyahIndex) &&
                this.hoveredAyahIndex === Math.trunc(normalizedAyahIndex)
            ) {
                this.hoveredAyahIndex = 0;
            }
        },

        showAyahMarker(word) {
            return Boolean(word?.ends_ayah) && !Boolean(word?.is_glyph);
        },

        normalizeSearchQuery(value) {
            return String(value ?? '')
                .replace(/[\u200b-\u200f\u061c\u2066-\u2069\ufeff]/g, '')
                .replace(/\u0640/g, '')
                .replace(/[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED]/g, '')
                .replace(/[أإآٱ]/g, 'ا')
                .replace(/ى/g, 'ي')
                .replace(/ؤ/g, 'و')
                .replace(/ئ/g, 'ي')
                .trim()
                .replace(/\s+/g, ' ')
                .toLowerCase();
        },

        searchResultAyahText(result) {
            const uthmaniText = String(result?.text_uthmani ?? '').trim();

            if (uthmaniText !== '') {
                return uthmaniText
                    .replace(/\u0640/g, '')
                    .replace(/[\u0610-\u061A\u064B-\u065F\u0670\u06D6-\u06ED]/g, '')
                    .replace(/\s+/g, ' ')
                    .trim();
            }

            return String(result?.text_searchable_typed ?? '')
                .replace(/\s+/g, ' ')
                .trim();
        },

        searchMatchTone(result) {
            const tone = String(result?.match_tone ?? '')
                .trim()
                .toLowerCase();

            if (['success', 'warning', 'info', 'danger'].includes(tone)) {
                return tone;
            }

            return 'warning';
        },

        searchMatchLabel(result) {
            const label = String(result?.match_label ?? '').trim();

            if (label !== '') {
                return label;
            }

            const strategy = String(result?.match_strategy ?? '').trim();

            if (strategy === 'exact_phrase') {
                return 'مطابقة تامة';
            }

            if (strategy === 'exact_tokens') {
                return 'مطابقة كلمات';
            }

            if (strategy === 'stem_tokens') {
                return 'مطابقة صرفية';
            }

            if (strategy === 'root_tokens') {
                return 'مطابقة جذرية';
            }

            if (strategy === 'word_prefix') {
                return 'مطابقة تقريبية';
            }

            return 'مطابقة';
        },

        isSurahHeaderLine(line) {
            return String(line?.line_type ?? '') === 'surah_name';
        },

        cleanSurahHeaderText(value) {
            const normalized = String(value ?? '').trim();

            if (normalized === '') {
                return '';
            }

            return normalized
                .replace(/^سورة\s+/u, '')
                .replace(/^سور[ةه]\s+/u, '')
                .replace(/\(\s*\d+\s*\)\s*$/u, '')
                .replace(/^\(\s*\d+\s*\)\s*-\s*/u, '')
                .trim();
        },

        surahHeaderGlyph(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 0)));

            if (
                !Number.isFinite(normalizedSurahNumber) ||
                normalizedSurahNumber < 1 ||
                normalizedSurahNumber > 114
            ) {
                return '';
            }

            try {
                return String.fromCodePoint(0xe001 + normalizedSurahNumber - 1);
            } catch {
                return '';
            }
        },

        surahHeaderLineText(line) {
            const surahNumber = Math.max(
                1,
                Math.trunc(Number(line?.surah_number ?? this.currentSurahNumber())),
            );
            const glyph = this.surahHeaderGlyph(surahNumber);
            const hasSurahHeaderFont = String(this.surahHeaderFontFamily ?? '').trim() !== '';

            if (glyph !== '' && hasSurahHeaderFont) {
                return glyph;
            }

            const lineText = this.cleanSurahHeaderText(line?.text ?? '');

            if (lineText !== '') {
                return lineText;
            }

            const mappedName = this.surahNameOnly(surahNumber);

            if (mappedName !== '') {
                return mappedName;
            }

            return `(${surahNumber})`;
        },

        hasSurahHeaderFont() {
            return String(this.surahHeaderFontFamily ?? '').trim() !== '';
        },

        surahTileUsesGlyph(entry) {
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));
            const glyph = this.surahHeaderGlyph(surahNumber);

            return glyph !== '' && this.hasSurahHeaderFont();
        },

        surahTileLabel(entry) {
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));

            if (this.surahTileUsesGlyph(entry)) {
                return this.surahHeaderGlyph(surahNumber);
            }

            const name = this.surahNameOnly(surahNumber);

            if (name !== '') {
                return name;
            }

            return String(surahNumber);
        },

        surahTileLabelStyle(entry) {
            if (!this.surahTileUsesGlyph(entry)) {
                return '';
            }

            const family = String(this.surahHeaderFontFamily ?? '').trim();

            if (!family) {
                return '';
            }

            return `font-family: '${family}', 'MadinaQuran', 'Amiri', 'Traditional Arabic', serif;`;
        },

        directoryActiveSurahNumber() {
            const searchSurahNumber = Math.max(
                0,
                Math.trunc(Number(this.search?.activeSurahNumber ?? 0)),
            );

            if (searchSurahNumber > 0) {
                return searchSurahNumber;
            }

            const triggerSurahNumber = Math.max(
                0,
                Math.trunc(Number(this.surahTriggerSurahNumber ?? 0)),
            );

            if (triggerSurahNumber > 0) {
                return triggerSurahNumber;
            }

            return this.currentSurahNumber();
        },

        syncSearchActiveSurahNumber() {
            const activeSurahNumber = Math.max(
                1,
                Math.trunc(Number(this.currentSurahNumber() ?? 1)),
            );
            this.search.activeSurahNumber = activeSurahNumber;
        },

        isSurahDirectoryEntryActive(entry) {
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));

            return surahNumber === this.directoryActiveSurahNumber();
        },

        resolveSurahDirectoryGridElement() {
            if (this.$refs.surahDirectoryGrid instanceof Element) {
                return this.$refs.surahDirectoryGrid;
            }

            const modalWindow = this.searchModalWindowElement();

            if (!(modalWindow instanceof Element)) {
                return null;
            }

            return modalWindow.querySelector('[data-quran-surah-grid]');
        },

        scrollSurahDirectoryToActive({ behavior = 'smooth' } = {}) {
            const gridElement = this.resolveSurahDirectoryGridElement();

            if (!(gridElement instanceof Element)) {
                return false;
            }

            if (gridElement.clientHeight < 16) {
                return false;
            }

            const activeSurahNumber = this.directoryActiveSurahNumber();
            const activeTile = gridElement.querySelector(
                `[data-surah-number="${activeSurahNumber}"]`,
            );

            if (!(activeTile instanceof Element)) {
                return false;
            }

            const tileTop = activeTile.offsetTop;
            const tileHeight = activeTile.clientHeight;
            const maxScrollTop = Math.max(0, gridElement.scrollHeight - gridElement.clientHeight);
            const targetScrollTop = tileTop - tileHeight * 3;

            const normalizedScrollTop = Math.max(
                0,
                Math.min(maxScrollTop, Math.trunc(targetScrollTop)),
            );

            if ('scrollTo' in gridElement) {
                gridElement.scrollTo({ top: normalizedScrollTop, behavior });
            } else {
                gridElement.scrollTop = normalizedScrollTop;
            }

            return true;
        },

        cancelSurahDirectoryAutoFocus() {
            this._surahDirectoryAutoFocusToken += 1;

            if (this._surahDirectoryAutoFocusTimer !== null) {
                clearTimeout(this._surahDirectoryAutoFocusTimer);
                this._surahDirectoryAutoFocusTimer = null;
            }

            if (this._surahDirectoryAutoFocusRaf !== null) {
                cancelAnimationFrame(this._surahDirectoryAutoFocusRaf);
                this._surahDirectoryAutoFocusRaf = null;
            }
        },

        resolveActiveSurahDirectoryTile(gridElement = null) {
            const resolvedGridElement =
                gridElement instanceof Element
                    ? gridElement
                    : this.resolveSurahDirectoryGridElement();

            if (!(resolvedGridElement instanceof Element)) {
                return null;
            }

            const activeSurahNumber = this.directoryActiveSurahNumber();
            const activeTile = resolvedGridElement.querySelector(
                `[data-surah-number="${activeSurahNumber}"]`,
            );

            return activeTile instanceof HTMLElement ? activeTile : null;
        },

        queueSurahDirectoryAutoFocus() {
            this.cancelSurahDirectoryAutoFocus();

            const token = this._surahDirectoryAutoFocusToken;
            const attemptAutoFocus = (attempt = 0) => {
                const normalizedAttempt = Math.max(0, Math.trunc(Number(attempt) || 0));

                if (token !== this._surahDirectoryAutoFocusToken || !this.search.modalOpen) {
                    return;
                }

                const gridElement = this.resolveSurahDirectoryGridElement();
                const activeTile = this.resolveActiveSurahDirectoryTile(gridElement);
                const isGridReady =
                    gridElement instanceof HTMLElement &&
                    activeTile instanceof HTMLElement &&
                    this.isSearchModalWindowVisible() &&
                    gridElement.clientHeight > 16 &&
                    activeTile.getClientRects().length > 0;

                if (isGridReady) {
                    this.scrollSurahDirectoryToActive({
                        behavior: normalizedAttempt === 0 ? 'auto' : 'smooth',
                    });
                    activeTile.focus({ preventScroll: true });

                    if (normalizedAttempt < 2) {
                        this._surahDirectoryAutoFocusTimer = window.setTimeout(
                            () => {
                                attemptAutoFocus(normalizedAttempt + 1);
                            },
                            normalizedAttempt === 0 ? 140 : 240,
                        );
                    }

                    return;
                }

                if (normalizedAttempt >= 28) {
                    return;
                }

                this._surahDirectoryAutoFocusRaf = requestAnimationFrame(() => {
                    this._surahDirectoryAutoFocusRaf = null;
                    this._surahDirectoryAutoFocusTimer = window.setTimeout(
                        () => {
                            attemptAutoFocus(normalizedAttempt + 1);
                        },
                        normalizedAttempt < 8 ? 36 : 72,
                    );
                });
            };

            attemptAutoFocus(0);
        },

        buildSurahDirectory(entries = null) {
            const sourceEntries = Array.isArray(entries) ? entries : this.search.surahDirectory;
            const firstPageBySurah = new Map();

            if (Array.isArray(sourceEntries) && sourceEntries.length > 0) {
                sourceEntries.forEach((entry) => {
                    const surahNumber = Number(entry?.surah_number ?? 0);
                    const pageNumber = Number(entry?.page_number ?? 0);

                    if (surahNumber < 1 || surahNumber > 114 || pageNumber < 1) {
                        return;
                    }

                    if (firstPageBySurah.has(surahNumber)) {
                        return;
                    }

                    firstPageBySurah.set(surahNumber, pageNumber);
                });
            }

            this.search.surahDirectory = Array.from({ length: 114 }, (_, index) => {
                const surahNumber = index + 1;

                return {
                    surah_number: surahNumber,
                    page_number: firstPageBySurah.get(surahNumber) ?? 1,
                };
            });

            if (this.search.modalOpen) {
                this.queueSurahDirectoryAutoFocus();
            }
        },

        deriveSurahDirectoryFromItems(items = []) {
            if (!Array.isArray(items) || items.length === 0) {
                return [];
            }

            const firstPageBySurah = new Map();

            items.forEach((item) => {
                const surahNumber = Number(item?.surah_number ?? 0);
                const pageNumber = Number(item?.page_number ?? item?.mushaf_page ?? 0);

                if (surahNumber < 1 || surahNumber > 114 || pageNumber < 1) {
                    return;
                }

                const normalizedSurahNumber = Math.trunc(surahNumber);
                const normalizedPageNumber = Math.trunc(pageNumber);
                const knownPage = firstPageBySurah.get(normalizedSurahNumber);

                if (knownPage === undefined || normalizedPageNumber < knownPage) {
                    firstPageBySurah.set(normalizedSurahNumber, normalizedPageNumber);
                }
            });

            return Array.from(firstPageBySurah.entries()).map(([surahNumber, pageNumber]) => ({
                surah_number: surahNumber,
                page_number: pageNumber,
            }));
        },

        surahLabel(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            const name = this.surahNameOnly(normalizedSurahNumber);

            if (name !== '') {
                return `سورة ${name}`;
            }

            return `سورة ${normalizedSurahNumber}`;
        },

        surahNameOnly(surahNumber) {
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            const names =
                Object.keys(this.search.surahNames ?? {}).length > 0
                    ? this.search.surahNames
                    : (this.initialPayload.surahNames ?? {});
            const rawName = String(names?.[normalizedSurahNumber] ?? '').trim();

            if (rawName !== '') {
                return rawName
                    .replace(/^سورة\s+/u, '')
                    .replace(/^سور[ةه]\s+/u, '')
                    .trim();
            }

            const headerLine = this.mushafLines.find((line) => {
                const lineSurahNumber = Number(line?.surah_number ?? 0);
                const lineType = String(line?.line_type ?? '');

                return lineSurahNumber === normalizedSurahNumber && lineType === 'surah_name';
            });
            const headerText = String(headerLine?.text ?? '').trim();

            if (headerText === '') {
                return '';
            }

            return headerText
                .replace(/^سورة\s+/u, '')
                .replace(/^سور[ةه]\s+/u, '')
                .replace(/\(\s*\d+\s*\)\s*$/u, '')
                .trim();
        },

        currentSurahNumber() {
            const firstAyahSurahNumber = this.firstAyahSurahNumberInPage();

            if (firstAyahSurahNumber > 0) {
                return firstAyahSurahNumber;
            }

            for (const line of this.mushafLines) {
                const lineSurahNumber = Number(line?.surah_number ?? 0);

                if (lineSurahNumber > 0) {
                    return lineSurahNumber;
                }
            }

            return 1;
        },

        firstAyahSurahNumberInPage() {
            for (const line of this.mushafLines) {
                if (String(line?.line_type ?? '') !== 'ayah') {
                    continue;
                }

                const lineSurahNumber = Number(line?.surah_number ?? 0);

                if (lineSurahNumber > 0) {
                    return lineSurahNumber;
                }

                if (!Array.isArray(line?.words)) {
                    continue;
                }

                for (const word of line.words) {
                    const wordAyahIndex = Number(word?.ayah_index ?? 0);
                    const wordSurahNumber = Number(word?.surah_number ?? 0);

                    if (wordAyahIndex > 0 && wordSurahNumber > 0) {
                        return wordSurahNumber;
                    }
                }
            }

            return 0;
        },

        currentSurahTitle() {
            return this.surahLabel(this.currentSurahNumber());
        },

        currentSurahTriggerLabel() {
            if (this.surahTriggerCaption !== '') {
                return this.surahTriggerCaption;
            }

            return this.resolveCurrentSurahTriggerLabel();
        },

        resolveCurrentSurahTriggerLabel() {
            const surahNumber = this.currentSurahNumber();
            const normalizedSurahNumber = Math.max(1, Math.trunc(Number(surahNumber ?? 1)));
            const surahName = this.surahNameOnly(normalizedSurahNumber);

            if (surahName !== '') {
                return `(${normalizedSurahNumber}) - ${surahName}`;
            }

            return `(${normalizedSurahNumber})`;
        },

        refreshSurahTriggerCaption(animate = true) {
            const nextCaption = this.resolveCurrentSurahTriggerLabel();
            const nextSurahNumber = Math.max(1, Math.trunc(Number(this.currentSurahNumber() ?? 1)));

            if (
                nextCaption === this.surahTriggerCaption &&
                this.surahTriggerCaption !== '' &&
                nextSurahNumber === this.surahTriggerSurahNumber
            ) {
                return;
            }

            if (this._surahTriggerTimer !== null) {
                clearTimeout(this._surahTriggerTimer);
                this._surahTriggerTimer = null;
            }

            if (this._surahTriggerCleanupTimer !== null) {
                clearTimeout(this._surahTriggerCleanupTimer);
                this._surahTriggerCleanupTimer = null;
            }

            if (!animate || this.surahTriggerCaption === '') {
                this.surahTriggerCaption = nextCaption;
                this.surahTriggerSurahNumber = nextSurahNumber;
                this.surahTriggerCaptionAnimClass = '';

                return;
            }

            const isForward = nextSurahNumber >= this.surahTriggerSurahNumber;
            const leaveClass = isForward
                ? 'quran-caption-leave-forward'
                : 'quran-caption-leave-backward';
            const enterClass = isForward
                ? 'quran-caption-enter-forward'
                : 'quran-caption-enter-backward';

            this.surahTriggerCaptionAnimClass = leaveClass;
            this._surahTriggerTimer = window.setTimeout(() => {
                this.surahTriggerCaption = nextCaption;
                this.surahTriggerSurahNumber = nextSurahNumber;
                this.surahTriggerCaptionAnimClass = enterClass;
                this._surahTriggerTimer = null;

                this._surahTriggerCleanupTimer = window.setTimeout(() => {
                    this.surahTriggerCaptionAnimClass = '';
                    this._surahTriggerCleanupTimer = null;
                }, 180);
            }, 140);
        },

        searchModalWindowElement() {
            const modalWindowIds = [this.searchModalDomId, this.searchModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            for (const modalWindowId of modalWindowIds) {
                const element = document.getElementById(modalWindowId);

                if (element instanceof Element) {
                    return element;
                }
            }

            const actionModalIds = [this.searchActionModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');

            for (const actionModalId of actionModalIds) {
                const modalElement =
                    document.getElementById(actionModalId) ??
                    document.querySelector(`[data-fi-modal-id="${actionModalId}"]`);

                if (!(modalElement instanceof Element)) {
                    continue;
                }

                const modalWindowElement = modalElement.querySelector('.fi-modal-window');

                if (modalWindowElement instanceof Element) {
                    return modalWindowElement;
                }
            }

            return null;
        },

        searchStreamTargetElement() {
            if (this.$refs.searchResultsStream instanceof Element) {
                return this.$refs.searchResultsStream;
            }

            const modalWindow = this.searchModalWindowElement();

            if (!(modalWindow instanceof Element)) {
                return null;
            }

            return modalWindow.querySelector('[data-quran-search-stream-target]');
        },

        clearSearchStreamTarget() {
            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element)) {
                this._lastSearchStreamPayloadRaw = '';

                return;
            }

            target.textContent = '';
            this._lastSearchStreamPayloadRaw = '';
        },

        teardownSearchStreamObserver() {
            if (this._searchStreamObserver) {
                this._searchStreamObserver.disconnect();
                this._searchStreamObserver = null;
            }
        },

        setupSearchStreamObserver() {
            this.teardownSearchStreamObserver();

            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element) || typeof MutationObserver === 'undefined') {
                return;
            }

            this._searchStreamObserver = new MutationObserver(() => {
                this.consumeSearchStreamPayload();
            });

            this._searchStreamObserver.observe(target, {
                childList: true,
                subtree: true,
                characterData: true,
            });
        },

        consumeSearchStreamPayload() {
            const target = this.searchStreamTargetElement();

            if (!(target instanceof Element)) {
                return;
            }

            const rawPayload = String(target.textContent ?? '').trim();

            if (rawPayload === '' || rawPayload === this._lastSearchStreamPayloadRaw) {
                return;
            }

            this._lastSearchStreamPayloadRaw = rawPayload;

            let payload = null;

            try {
                payload = JSON.parse(rawPayload);
            } catch (_) {
                return;
            }

            this.applySearchStreamPayload(payload);
        },

        applySearchStreamPayload(payload) {
            const requestSerial = Math.max(0, Math.trunc(Number(payload?.request_serial ?? 0)));

            if (requestSerial !== this._searchRequestSerial) {
                return;
            }

            const results = Array.isArray(payload?.items) ? payload.items.slice(0, 24) : [];

            this.search.results = results;
            this.search.isOpen = results.length > 0;
            this.search.readyResult = results.length === 1 ? results[0] : null;

            if (typeof payload?.is_loading === 'boolean') {
                this.search.isLoading = payload.is_loading;
            }

            this.$nextTick(() => {
                this.ensureSearchResultAnimations();
            });
        },

        isSearchModalWindowVisible() {
            const modalWindowElement = this.searchModalWindowElement();

            if (!(modalWindowElement instanceof Element)) {
                return false;
            }

            const modalElement = modalWindowElement.closest('.fi-modal');

            if (modalElement && !modalElement.classList.contains('fi-modal-open')) {
                return false;
            }

            const styles = window.getComputedStyle(modalWindowElement);

            return styles.display !== 'none' && styles.visibility !== 'hidden';
        },

        isSearchModalEvent(kind, event) {
            const modalId = String(event?.detail?.id ?? '').trim();
            const knownIds = [this.searchModalId, this.searchModalDomId, this.searchActionModalId]
                .map((value) => String(value ?? '').trim())
                .filter((value) => value !== '');
            const matchedKnownId = modalId !== '' && knownIds.includes(modalId);

            if (kind === 'opened') {
                if (matchedKnownId) {
                    this.searchActionModalId = modalId;

                    return true;
                }

                const isVisible = this.isSearchModalWindowVisible();

                if (isVisible && modalId !== '') {
                    this.searchActionModalId = modalId;
                }

                return isVisible;
            }

            if (matchedKnownId) {
                return true;
            }

            if (modalId === '') {
                return this.search.modalOpen || this._lastKnownModalOpenState;
            }

            return this.search.modalOpen || this._lastKnownModalOpenState;
        },

        handleModalLifecycleEvent(kind, event) {
            this.trackModalLifecycle(kind, event);

            if (!this.isSearchModalEvent(kind, event)) {
                return;
            }

            if (kind === 'opened') {
                this.handleSearchModalOpened();

                return;
            }

            if (kind === 'closing' || kind === 'closed') {
                this.handleSearchModalClosed();
            }
        },

        ensureSearchResultAnimations() {
            if (typeof window.autoAnimate !== 'function') {
                return;
            }

            if (typeof this._searchResultsAutoAnimateStop === 'function') {
                return;
            }

            const resultsContainer = this.$refs.searchResultsList;

            if (!(resultsContainer instanceof Element)) {
                return;
            }

            this._searchResultsAutoAnimateStop = window.autoAnimate(resultsContainer, {
                duration: 230,
                easing: 'cubic-bezier(0.22, 1, 0.36, 1)',
                disrespectUserMotionPreference: true,
            });
        },

        async handleSearchModalOpened() {
            await this.warmSearchIndex();
            this.search.modalOpen = true;
            this._lastKnownModalOpenState = true;
            this._skipNextSearchModalCloseLayout = false;
            this.refreshSurahTriggerCaption(false);
            this.syncSearchActiveSurahNumber();
            this.search.query = '';
            this.search.results = [];
            this.search.readyResult = null;
            this.search.isOpen = false;
            this.activeAyahIndex = 0;
            this.hoveredAyahIndex = 0;
            this.activeWordIndex = 0;
            this.hoveredWordIndex = 0;

            await this.nextTickAsync();
            this.setupSearchStreamObserver();
            this.clearSearchStreamTarget();
            this.ensureSearchResultAnimations();
            this.$refs.searchModalInput?.focus?.();
            this.queueSurahDirectoryAutoFocus();
        },

        handleSearchModalClosed() {
            this.cancelSurahDirectoryAutoFocus();
            this.teardownSearchStreamObserver();
            this.clearSearchStreamTarget();
            this.search.modalOpen = false;
            this._lastKnownModalOpenState = false;
            this.search.query = '';
            this.search.results = [];
            this.search.readyResult = null;
            this.search.isOpen = false;

            if (this._searchModalCloseDebounceTimer !== null) {
                clearTimeout(this._searchModalCloseDebounceTimer);
                this._searchModalCloseDebounceTimer = null;
            }

            if (this._skipNextSearchModalCloseLayout) {
                this._skipNextSearchModalCloseLayout = false;

                return;
            }
        },

        async requestSearchModalClose({ skipLayout = false } = {}) {
            const modalId = [this.searchActionModalId, this.searchModalId, this.searchModalDomId]
                .map((value) => String(value ?? '').trim())
                .find((value) => value !== '');

            if (skipLayout) {
                this._skipNextSearchModalCloseLayout = true;
            }

            if (typeof this.$wire?.unmountAction === 'function') {
                try {
                    await this.$wire.unmountAction(false);

                    return;
                } catch (_) {
                    //
                }
            }

            if (!modalId) {
                this.handleSearchModalClosed();

                return;
            }

            window.dispatchEvent(
                new CustomEvent('close-modal', {
                    detail: {
                        id: modalId,
                    },
                }),
            );
        },

        requestReaderGateNavigation(_source = 'generic') {
            this.resetSwipeState();
            this.clearWordPressState();
            void this.requestSearchModalClose({ skipLayout: true });
            window.dispatchEvent(new CustomEvent('quran-go-gate'));
        },

        async confirmSearchSelection() {
            if (!this.search.readyResult) {
                return;
            }

            await this.goToSearchResult(this.search.readyResult);
        },

        async goToSurahFromDirectory(entry) {
            const pageNumber = clampPage(Number(entry?.page_number ?? 1), this.maxPage);
            const direction = this.resolveNavigationDirection(pageNumber);
            const surahNumber = Math.max(1, Math.trunc(Number(entry?.surah_number ?? 1)));

            this.search.activeSurahNumber = surahNumber;

            this.resetNavigationQueueForPriorityJump();
            await this.requestSearchModalClose();
            this.activeAyahIndex = 0;
            this.activeWordIndex = 0;
            await this.navigateToPage(pageNumber, {
                direction,
                animate: true,
                activeAyahIndex: 0,
                forceRefit: true,
                source: 'surah-directory',
                commitNow: true,
                settleDelayMs: 0,
            });
            await this.layoutPageGuaranteed({ revealDelayMs: 220, maxAttempts: 5 });
            this.activeAyahIndex = 0;
            this.activeWordIndex = 0;
        },

        searchRequestUrl(query = '') {
            const baseUrl = String(this.api.searchIndexUrl ?? '').trim();

            if (!baseUrl) {
                return '';
            }

            if (!query) {
                return baseUrl;
            }

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('q', query);

            return url.toString();
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
                        url: this.searchRequestUrl(),
                        cacheName: this.cacheNames.search,
                        preferCache: true,
                    });

                    if (
                        payload &&
                        typeof payload === 'object' &&
                        payload.surah_names &&
                        Object.keys(payload.surah_names).length > 0
                    ) {
                        this.search.surahNames = payload.surah_names;
                    }

                    let surahDirectory = Array.isArray(payload?.surah_directory)
                        ? payload.surah_directory
                        : [];

                    if (
                        surahDirectory.length === 0 &&
                        Array.isArray(payload?.items) &&
                        payload.items.length > 0
                    ) {
                        surahDirectory = this.deriveSurahDirectoryFromItems(payload.items);
                    }

                    this.buildSurahDirectory(surahDirectory);
                    this.refreshSurahTriggerCaption(false);
                    this.search.isReady = true;
                } catch (_) {
                    if (
                        !Array.isArray(this.search.surahDirectory) ||
                        this.search.surahDirectory.length === 0
                    ) {
                        this.buildSurahDirectory([]);
                    }

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
                this.search.readyResult = null;
                this.search.isLoading = false;
                this._searchRequestSerial += 1;
                this.clearSearchStreamTarget();

                return;
            }

            if (normalizedQuery.length < this.search.minQueryLength) {
                this.search.results = [];
                this.search.isOpen = false;
                this.search.readyResult = null;
                this.search.isLoading = false;
                this._searchRequestSerial += 1;
                this.clearSearchStreamTarget();

                return;
            }

            if (!this.search.isReady) {
                await this.warmSearchIndex();
            }

            if (!this.search.isReady) {
                this.search.results = [];
                this.search.isOpen = false;
                this.search.readyResult = null;
                this.search.isLoading = false;
                this._searchRequestSerial += 1;
                this.clearSearchStreamTarget();

                return;
            }

            const requestSerial = ++this._searchRequestSerial;
            this.search.isLoading = true;
            this.clearSearchStreamTarget();

            try {
                const livewireResults = await this.$wire.streamSearch(
                    normalizedQuery,
                    requestSerial,
                );
                const results = Array.isArray(livewireResults) ? livewireResults.slice(0, 24) : [];

                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                this.search.results = results;
                this.search.isOpen = results.length > 0;
                this.search.readyResult = results.length === 1 ? results[0] : null;
                this.$nextTick(() => {
                    this.ensureSearchResultAnimations();
                });
            } catch (error) {
                if (requestSerial !== this._searchRequestSerial) {
                    return;
                }

                this.search.results = [];
                this.search.isOpen = false;
                this.search.readyResult = null;
            } finally {
                if (requestSerial === this._searchRequestSerial) {
                    this.search.isLoading = false;
                }
            }
        },

        async goToSearchResult(result) {
            const targetPage = clampPage(Number(result?.page_number ?? 1), this.maxPage);
            const ayahIndex = Math.max(0, Math.trunc(Number(result?.ayah_index ?? 0)));
            const direction = this.resolveNavigationDirection(targetPage);

            this.resetNavigationQueueForPriorityJump();
            await this.requestSearchModalClose();
            await this.navigateToPage(targetPage, {
                direction,
                animate: true,
                activeAyahIndex: ayahIndex,
                forceRefit: true,
                source: 'search-result',
                commitNow: true,
                settleDelayMs: 0,
            });
            await this.layoutPageGuaranteed({ revealDelayMs: 220, maxAttempts: 5 });
            this.activeWordIndex = 0;
        },
    }));
});
