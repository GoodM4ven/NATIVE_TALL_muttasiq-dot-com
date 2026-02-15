document.addEventListener('alpine:init', function () {
    const list = ['base', 'sm', 'md', 'lg', 'xl', '2xl']; // ? matching CSS
    const read = () =>
        getComputedStyle(document.documentElement)
            .getPropertyValue('--breakpoint')
            .trim()
            .replace(/['"]+/g, '');
    const mediaMatches = (query) =>
        typeof window.matchMedia === 'function' && window.matchMedia(query).matches;
    const hasTouchInput = () =>
        Number(window.navigator?.maxTouchPoints ?? 0) > 0 ||
        mediaMatches('(any-pointer: coarse)') ||
        mediaMatches('(hover: none)');

    const is = (q) => {
        if (!q) return false;
        const m = /(\+|-)$/.exec(q);
        const name = m ? q.slice(0, -1) : q;
        const cur = read();
        const idx = list.indexOf(name);
        const curIdx = list.indexOf(cur);
        if (idx < 0 || curIdx < 0) return false;
        if (m?.[0] === '+') return curIdx >= idx;
        if (m?.[0] === '-') return curIdx <= idx;
        return cur === name;
    };
    const isTablet = () => hasTouchInput() && is('sm+') && is('xl-');
    const shouldUseSortHandles = () => hasTouchInput() && (is('base') || isTablet());

    window.Alpine.store('bp', {
        current: read(),
        is: (q) => is(q),
        isTouch: () => hasTouchInput(),
        isTablet: () => isTablet(),
        shouldUseSortHandles: () => shouldUseSortHandles(),
    });

    // ? Reactive updates when the CSS var flips at breakpoints (no debounce)
    const ro = new ResizeObserver(() => {
        const next = read();
        if (next !== window.Alpine.store('bp').current) {
            window.Alpine.store('bp').current = next;
        }
    });

    ro.observe(document.documentElement);
});
