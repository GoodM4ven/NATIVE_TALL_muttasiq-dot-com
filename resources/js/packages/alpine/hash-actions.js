document.addEventListener('alpine:init', () => {
    const isNativeShell = () => {
        return Boolean(document.body?.classList?.contains('mobile-platform'));
    };

    const normalizeHash = (value) => {
        if (typeof value !== 'string' || value.length === 0) {
            return '#';
        }

        return value.startsWith('#') ? value : `#${value}`;
    };

    const normalizeViewName = (value) => {
        if (typeof value !== 'string' || value.length === 0) {
            return null;
        }

        return value.startsWith('#') ? value.slice(1) : value;
    };

    const buildUrl = (hash) => {
        const url = new URL(window.location.href);

        if (isNativeShell()) {
            url.searchParams.delete('__native_view');
            url.searchParams.delete('__native_stack');
        }

        url.hash = hash === '#' ? '' : hash;

        return `${url.pathname}${url.search}${url.hash}`;
    };

    const dispatchHashChange = (oldUrl, newUrl) => {
        let event;

        try {
            event = new HashChangeEvent('hashchange', {
                oldURL: oldUrl,
                newURL: newUrl,
            });
        } catch (_) {
            event = new Event('hashchange');
        }

        window.dispatchEvent(event);
    };

    const applyHash = (
        nextHash,
        { rememberInHistory = false, rememberInState = rememberInHistory } = {},
    ) => {
        const previousHash = window.location.hash || '#';
        const normalizedHash = normalizeHash(nextHash);
        const oldUrl = window.location.href;
        const nextState = {
            ...(window.history.state ?? {}),
            __hashActionRemember: rememberInState && normalizedHash !== '#',
            __hashActionPrev: previousHash,
        };
        const newUrl = buildUrl(normalizedHash);

        if (oldUrl === newUrl) {
            window.history.replaceState(nextState, document.title, newUrl);
            return;
        }

        if (rememberInHistory && normalizedHash !== '#') {
            window.history.pushState(nextState, document.title, newUrl);
        } else {
            window.history.replaceState(nextState, document.title, newUrl);
        }

        if (oldUrl !== window.location.href) {
            dispatchHashChange(oldUrl, window.location.href);
        }
    };

    const runHashSequence = (hashes, { delayMs = 0, onDone } = {}) => {
        let index = 0;

        const runNext = () => {
            const hash = hashes[index];

            if (!hash) {
                if (typeof onDone === 'function') {
                    onDone();
                }
                return;
            }

            applyHash(hash, { rememberInHistory: true, rememberInState: true });
            index += 1;
            if (hashes[index]) {
                setTimeout(runNext, delayMs);
            } else if (typeof onDone === 'function') {
                setTimeout(onDone, delayMs);
            }
        };

        runNext();
    };

    const resolveAction = (actions, hash) => {
        if (!actions || typeof actions !== 'object') {
            return null;
        }

        return actions[hash] ?? actions[hash.slice(1)] ?? null;
    };

    const waitForHistoryReady = (callback) => {
        const delayMs = isNativeShell() ? 120 : 0;

        if (document.readyState === 'complete') {
            setTimeout(callback, delayMs);
            return;
        }

        window.addEventListener(
            'load',
            () => {
                setTimeout(callback, delayMs);
            },
            { once: true },
        );
    };

    const dispatchSwitchView = (view, { restoring = false } = {}) => {
        if (!view) {
            return;
        }

        window.dispatchEvent(
            new CustomEvent('switch-view', {
                detail: { to: view, restoring },
            }),
        );
    };

    const parseViewTree = (value) => {
        if (!value) {
            return null;
        }

        try {
            return JSON.parse(value);
        } catch (_) {
            return null;
        }
    };

    const buildViewIndex = (tree) => {
        const parentMap = {};
        const viewSet = new Set();
        let rootView = null;

        const walk = (node, parent) => {
            if (!node || typeof node !== 'object') {
                return;
            }

            Object.entries(node).forEach(([view, config]) => {
                if (!rootView) {
                    rootView = view;
                }

                viewSet.add(view);

                if (parent) {
                    parentMap[view] = parent;
                }

                if (config && typeof config === 'object') {
                    const children = config.children ?? config;
                    if (children && typeof children === 'object') {
                        walk(children, view);
                    }
                }
            });
        };

        walk(tree, null);

        return {
            parentMap,
            viewSet,
            rootView,
        };
    };

    const ensureViewIndex = (element) => {
        if (window.__viewTreeIndex) {
            return window.__viewTreeIndex;
        }

        const tree = parseViewTree(element?.dataset?.viewTree);

        if (!tree) {
            return null;
        }

        window.__viewTreeIndex = buildViewIndex(tree);

        return window.__viewTreeIndex;
    };

    const getViewIndex = () => {
        if (window.__viewTreeIndex) {
            return window.__viewTreeIndex;
        }

        const element = document.querySelector('[data-view-tree]');
        return ensureViewIndex(element);
    };

    const pathToRoot = (view, parentMap) => {
        const path = [];
        let current = view;

        while (current) {
            path.push(current);
            current = parentMap[current];
        }

        return path;
    };

    const pathFromRoot = (view, parentMap) => {
        return pathToRoot(view, parentMap).reverse();
    };

    const commonPrefixLength = (left, right) => {
        const limit = Math.min(left.length, right.length);
        let index = 0;

        while (index < limit && left[index] === right[index]) {
            index += 1;
        }

        return index;
    };

    const isViewHash = (hash) => {
        const viewName = normalizeViewName(hash);
        const viewIndex = getViewIndex();

        if (!viewName || !viewIndex) {
            return false;
        }

        return viewIndex.viewSet.has(viewName);
    };

    const getCurrentView = (fallbackView) => {
        const currentHash = normalizeHash(window.location.hash || '#');
        const viewName = normalizeViewName(currentHash);

        return viewName || fallbackView || 'main-menu';
    };

    const navigateView = (target, { force = false } = {}) => {
        const viewIndex = getViewIndex();
        const targetView = normalizeViewName(target);

        if (!viewIndex || !targetView || !viewIndex.viewSet.has(targetView)) {
            applyHash(target, { rememberInHistory: false, rememberInState: false });
            return;
        }

        const currentView = getCurrentView(viewIndex.rootView);

        if (!force && currentView === targetView) {
            return;
        }

        const fromPath = pathFromRoot(currentView, viewIndex.parentMap);
        const toPath = pathFromRoot(targetView, viewIndex.parentMap);
        const lcaIndex = commonPrefixLength(fromPath, toPath);
        const isTargetAncestor = lcaIndex === toPath.length;
        const isCurrentAncestor = lcaIndex === fromPath.length;

        if (isTargetAncestor) {
            applyHash(`#${targetView}`, {
                rememberInHistory: false,
                rememberInState: true,
            });
            return;
        }

        if (!isCurrentAncestor) {
            const lcaView = fromPath[lcaIndex - 1] ?? viewIndex.rootView;
            applyHash(`#${lcaView}`, {
                rememberInHistory: false,
                rememberInState: true,
            });
        }

        const startIndex = isCurrentAncestor ? fromPath.length : lcaIndex;
        const downwardHashes = toPath.slice(startIndex).map((view) => `#${view}`);

        if (!downwardHashes.length) {
            return;
        }

        runHashSequence(downwardHashes, { delayMs: 0 });
    };

    const restoreViewPath = (target, root) => {
        const viewIndex = getViewIndex();
        const targetView = normalizeViewName(target);

        if (!viewIndex || !targetView || !viewIndex.viewSet.has(targetView)) {
            return;
        }

        const rootView = normalizeViewName(root) || viewIndex.rootView || 'main-menu';
        const targetPath = pathFromRoot(targetView, viewIndex.parentMap);
        const rootIndex = targetPath.indexOf(rootView);

        if (rootIndex === -1) {
            return;
        }

        const path = targetPath.slice(rootIndex);

        if (!path.length) {
            return;
        }

        window.__viewNavRestoring = true;
        window.__hashActionBypassLock = true;

        const hashes = path.map((view) => `#${view}`);

        applyHash(hashes[0], {
            rememberInHistory: false,
            rememberInState: true,
        });

        runHashSequence(hashes.slice(1), {
            delayMs: 0,
            onDone: () => {
                window.__hashActionBypassLock = false;
                window.__viewNavRestoring = false;
            },
        });
    };

    const runNativeBack = (targetHash) => {
        window.__hashActionBypassLock = true;
        applyHash(targetHash, { rememberInHistory: false, rememberInState: true });
        setTimeout(() => {
            window.__hashActionBypassLock = false;
        }, 0);
    };

    window.__nativeBackAction = () => {
        const currentHash = normalizeHash(window.location.hash || '#');

        if (currentHash === '#athkar-app-gate') {
            runNativeBack('#main-menu');
            return true;
        }

        if (currentHash === '#athkar-app-sabah' || currentHash === '#athkar-app-masaa') {
            runNativeBack('#athkar-app-gate');
            return true;
        }

        return false;
    };

    window.Alpine.magic('hashAction', () => {
        return (hash, options = {}) => {
            const { reset = '#', force = true, remember = false } = options;
            const targetHash = normalizeHash(hash);

            if (targetHash === '#') {
                return;
            }

            const resetHash = normalizeHash(reset);
            const currentHash = window.location.hash || '#';

            if (force && currentHash === targetHash) {
                applyHash(resetHash, { rememberInHistory: false, rememberInState: false });
                setTimeout(() => {
                    applyHash(targetHash, {
                        rememberInHistory: remember,
                        rememberInState: remember,
                    });
                }, 0);
                return;
            }

            applyHash(targetHash, { rememberInHistory: remember, rememberInState: remember });
        };
    });

    window.Alpine.magic('viewNav', () => {
        return (view, options = {}) => {
            navigateView(view, options);
        };
    });

    window.Alpine.directive('hash-actions', (el, { expression }, { evaluateLater, cleanup }) => {
        const evaluateActions = evaluateLater(expression);
        ensureViewIndex(el);

        const handleHash = (hash) => {
            const normalizedHash = normalizeHash(hash);

            if (normalizedHash === '#') {
                return;
            }

            if (isViewHash(normalizedHash)) {
                dispatchSwitchView(normalizeViewName(normalizedHash), {
                    restoring: window.__viewNavRestoring === true,
                });
                return;
            }

            evaluateActions((actions) => {
                const action = resolveAction(actions, normalizedHash);

                if (typeof action !== 'function') {
                    return;
                }

                const shouldRemember = window.history.state?.__hashActionRemember === true;

                if (!shouldRemember) {
                    window.history.replaceState(
                        {
                            ...(window.history.state ?? {}),
                            __hashActionRemember: false,
                        },
                        document.title,
                        window.location.href,
                    );
                }

                action();

                setTimeout(() => {
                    if (shouldRemember) {
                        return;
                    }

                    if (normalizeHash(window.location.hash || '#') === normalizedHash) {
                        applyHash('#', { rememberInHistory: false, rememberInState: false });
                    }
                }, 0);
            });
        };

        const onHashChange = () => {
            handleHash(window.location.hash || '#');
        };

        window.addEventListener('hashchange', onHashChange);
        waitForHistoryReady(() => {
            const defaultHash = normalizeHash(el.dataset?.hashDefault);
            const restoreHash = normalizeHash(el.dataset?.hashRestore || '');

            if (defaultHash !== '#') {
                applyHash(defaultHash, { rememberInHistory: false, rememberInState: true });

                if (restoreHash !== '#' && restoreHash !== defaultHash) {
                    restoreViewPath(restoreHash, defaultHash);
                    return;
                }

                dispatchSwitchView(defaultHash.slice(1), { restoring: true });
                return;
            }

            onHashChange();
        });

        cleanup(() => {
            window.removeEventListener('hashchange', onHashChange);
        });
    });
});
