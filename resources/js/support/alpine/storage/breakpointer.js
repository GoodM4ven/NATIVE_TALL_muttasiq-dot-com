document.addEventListener('alpine:init', function () {
    const breakpoints = ['base', 'sm', 'md', 'lg', 'xl', '2xl'];
    const readBreakpoint = () =>
        getComputedStyle(document.documentElement)
            .getPropertyValue('--breakpoint')
            .trim()
            .replace(/['"]+/g, '');
    const mediaMatches = (query) =>
        typeof window.matchMedia === 'function' && window.matchMedia(query).matches;
    const detectTouchInput = () =>
        'ontouchstart' in window ||
        Number(window.navigator?.maxTouchPoints ?? 0) > 0 ||
        mediaMatches('(pointer: coarse)') ||
        mediaMatches('(any-pointer: coarse)') ||
        mediaMatches('(hover: none)');
    const applyTouchClass = (hasTouch) => {
        document.documentElement.classList.toggle('has-touch', hasTouch);
    };

    const is = (q) => {
        if (!q) return false;
        const m = /(\+|-)$/.exec(q);
        const name = m ? q.slice(0, -1) : q;
        const cur = readBreakpoint();
        const idx = breakpoints.indexOf(name);
        const curIdx = breakpoints.indexOf(cur);
        if (idx < 0 || curIdx < 0) return false;
        if (m?.[0] === '+') return curIdx >= idx;
        if (m?.[0] === '-') return curIdx <= idx;
        return cur === name;
    };
    const isTablet = () => detectTouchInput() && is('sm+') && is('xl-');
    const shouldUseSortHandles = () => detectTouchInput() && (is('base') || isTablet());

    const initialHasTouch = detectTouchInput();
    applyTouchClass(initialHasTouch);

    window.Alpine.store('bp', {
        current: readBreakpoint(),
        hasTouch: initialHasTouch,
        is: (q) => is(q),
        isTouch: () => detectTouchInput(),
        isTablet: () => isTablet(),
        shouldUseSortHandles: () => shouldUseSortHandles(),
    });

    const syncBreakpoints = () => {
        const store = window.Alpine.store('bp');
        const nextBreakpoint = readBreakpoint();

        if (nextBreakpoint !== store.current) {
            store.current = nextBreakpoint;
        }
    };

    const syncTouchState = () => {
        const store = window.Alpine.store('bp');
        const nextHasTouch = detectTouchInput();

        if (nextHasTouch !== store.hasTouch) {
            store.hasTouch = nextHasTouch;
            applyTouchClass(nextHasTouch);
        }
    };

    let syncFrameId = null;
    const scheduleSync = () => {
        if (syncFrameId !== null) {
            return;
        }

        syncFrameId = window.requestAnimationFrame(() => {
            syncFrameId = null;
            syncBreakpoints();
            syncTouchState();
        });
    };

    const shouldUseResizeObserver =
        typeof window.ResizeObserver === 'function' && typeof window.AndroidBridge === 'undefined';

    if (shouldUseResizeObserver) {
        const resizeObserver = new ResizeObserver(() => {
            scheduleSync();
        });

        resizeObserver.observe(document.documentElement);
    }

    window.addEventListener(
        'resize',
        () => {
            scheduleSync();
        },
        { passive: true },
    );
    window.addEventListener(
        'orientationchange',
        () => {
            scheduleSync();
        },
        { passive: true },
    );
});
