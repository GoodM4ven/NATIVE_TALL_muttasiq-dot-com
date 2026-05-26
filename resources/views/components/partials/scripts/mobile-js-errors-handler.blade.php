<script>
    (function() {
        const isMobileRuntime = @js(is_platform('mobile'));
        const isNativeRuntime = @js((bool) config('nativephp-internal.running', false));
        const nativePlatform = @js(config('nativephp-internal.platform'));
        const storageKey = 'jsErrorLog';
        const maxEntries = 30;
        const openCooldownInMs = 650;
        const maxMessageLength = 1000;
        const maxSourceLength = 2048;
        const maxStackLength = 20000;
        const maxTimeLength = 50;
        let lastOpenedAt = 0;
        let lastModalMode = 'auto';
        let isModalOpen = false;
        let hasTriggeredReload = false;
        const successfulSubmissionFlag = 'jsErrorReportSubmitted';
        window.__jsErrorReportingInProgress = false;
        const isReportingDisabled = () => {
            return Boolean(window.__disableJsErrorReporting) ||
                document.documentElement?.dataset?.disableJsErrorReporting === 'true';
        };

        const loadEntries = () => {
            try {
                return JSON.parse(localStorage.getItem(storageKey)) || [];
            } catch (error) {
                return [];
            }
        };

        const saveEntries = (entries) => {
            try {
                localStorage.setItem(storageKey, JSON.stringify(entries.slice(-maxEntries)));
            } catch (error) {}
        };

        const trimTo = (value, maxLength) => {
            if (typeof value !== 'string') {
                return null;
            }

            const trimmed = value.trim();
            if (trimmed === '') {
                return null;
            }

            return trimmed.slice(0, maxLength);
        };

        const clipTo = (value, maxLength, tailLength = Math.min(2000, Math.floor(maxLength * 0.35))) => {
            if (typeof value !== 'string') {
                return null;
            }

            const trimmed = value.trim();
            if (trimmed === '') {
                return null;
            }

            if (trimmed.length <= maxLength) {
                return trimmed;
            }

            const normalizedTailLength = Math.min(Math.max(tailLength, 0), Math.floor(maxLength / 2));
            const headLength = Math.max(maxLength - normalizedTailLength - 5, 0);

            return `${trimmed.slice(0, headLength)} ... ${trimmed.slice(-normalizedTailLength)}`;
        };

        const normalizeNoiseMessage = (message) => {
            const normalizedMessage = trimTo(message, maxMessageLength);
            if (!normalizedMessage) {
                return null;
            }

            return normalizedMessage
                .replace(/^\[[^\]]+\]\s*/i, '')
                .replace(/[.!?]+$/g, '')
                .trim();
        };

        const extensionSchemePattern = /^(chrome-extension|moz-extension|safari-web-extension):\/\//i;
        const knownNoiseMessagePatterns = [
            /^ResizeObserver loop limit exceeded$/i,
            /^ResizeObserver loop completed with undelivered notifications$/i,
            /unchecked runtime\.lastError/i,
            /The message port closed before a response was received/i,
        ];
        const externalOnlyNoiseMessagePatterns = [
            /^Script error\.?$/i,
            /^Non-Error promise rejection captured/i,
        ];

        const isLikelyExtensionTrace = (value) => {
            const text = trimTo(value, maxStackLength);
            if (!text) {
                return false;
            }

            return extensionSchemePattern.test(text) || text.includes('extensions::');
        };

        const isLikelyBrowserNoise = (message) => {
            const normalizedMessage = normalizeNoiseMessage(message);
            if (!normalizedMessage) {
                return false;
            }

            return knownNoiseMessagePatterns.some((pattern) => pattern.test(normalizedMessage));
        };

        const isLikelyOpaqueLivewireThrow = (entry) => {
            const normalizedMessage = normalizeNoiseMessage(entry?.message);

            if (!normalizedMessage || normalizedMessage !== 'Uncaught [object Object]') {
                return false;
            }

            const normalizedSource = trimTo(entry?.source, maxSourceLength);
            const normalizedStack = trimTo(entry?.stack, maxStackLength);
            const hasLivewireSource = Boolean(normalizedSource && normalizedSource.includes('/livewire'));
            const hasLivewireStack = Boolean(normalizedStack && normalizedStack.includes('/livewire'));

            return hasLivewireSource || hasLivewireStack;
        };

        const isLikelyExternalOnlyNoise = (message) => {
            const normalizedMessage = normalizeNoiseMessage(message);
            if (!normalizedMessage) {
                return false;
            }

            return externalOnlyNoiseMessagePatterns.some((pattern) => pattern.test(normalizedMessage));
        };

        const isSameOriginSource = (source) => {
            const normalizedSource = trimTo(source, maxSourceLength);
            if (!normalizedSource) {
                return false;
            }

            if (
                normalizedSource.startsWith('/') ||
                normalizedSource.startsWith('./') ||
                normalizedSource.startsWith('../')
            ) {
                return true;
            }

            try {
                const sourceUrl = new URL(normalizedSource, window.location.href);
                return sourceUrl.origin === window.location.origin;
            } catch (error) {
                return false;
            }
        };

        const isLikelyAppOwnedStack = (stack) => {
            const normalizedStack = trimTo(stack, maxStackLength);
            if (!normalizedStack) {
                return false;
            }

            return (
                normalizedStack.includes(window.location.origin) ||
                normalizedStack.includes('/build/') ||
                normalizedStack.includes('/livewire')
            );
        };

        const shouldIgnoreEntry = (entry) => {
            const sameOriginSource = isSameOriginSource(entry.source);
            const hasAppOwnedStack = isLikelyAppOwnedStack(entry.stack);
            const hasAppSignal = sameOriginSource || hasAppOwnedStack;

            if (isLikelyExternalOnlyNoise(entry.message) && !hasAppSignal) {
                return true;
            }

            if (extensionSchemePattern.test(entry.source || '') || isLikelyExtensionTrace(entry.stack)) {
                return true;
            }

            if (entry.source && !sameOriginSource) {
                return true;
            }

            const isKnownBrowserNoise = isLikelyBrowserNoise(entry.message);

            if (isKnownBrowserNoise) {
                return true;
            }

            if (isLikelyOpaqueLivewireThrow(entry)) {
                return true;
            }

            if (!entry.source && !hasAppOwnedStack) {
                return true;
            }

            return false;
        };

        const shouldPreventDefaultErrorEvent = (entry) => {
            if (isLikelyBrowserNoise(entry?.message)) {
                return true;
            }

            return isLikelyOpaqueLivewireThrow(entry);
        };

        const normalizeEntry = (entry) => {
            if (!entry || typeof entry !== 'object') {
                return null;
            }

            const normalized = {
                type: trimTo(entry.type, 20) || 'error',
                time: trimTo(entry.time, maxTimeLength) || new Date().toISOString(),
                message: trimTo(entry.message, maxMessageLength) || 'Unknown error',
                source: trimTo(entry.source, maxSourceLength),
                line: Number.isFinite(Number(entry.line)) ? Math.max(0, Number(entry.line)) : null,
                column: Number.isFinite(Number(entry.column)) ? Math.max(0, Number(entry.column)) : null,
                stack: clipTo(entry.stack, maxStackLength),
            };

            return normalized;
        };

        const readBreakpoint = () => {
            const alpineStore = window.Alpine?.store?.('bp');
            if (alpineStore?.current) {
                return alpineStore.current;
            }

            const cssBreakpoint = getComputedStyle(document.documentElement)
                .getPropertyValue('--breakpoint')
                .trim()
                .replace(/['"]+/g, '');

            return cssBreakpoint || null;
        };

        const buildPlatformLabel = () => {
            const prefix = isNativeRuntime ? 'Native' : 'Web';
            const resolvedNativePlatform = trimTo(nativePlatform, 32);
            const resolvedBrowserPlatform = trimTo(window.navigator?.platform ?? null, 32);
            const resolvedPlatform = resolvedNativePlatform || resolvedBrowserPlatform;

            if (!resolvedPlatform) {
                return prefix;
            }

            return `${prefix} - ${resolvedPlatform}`;
        };

        const collectContext = () => {
            return {
                url: trimTo(window.location?.href ?? null, 2048),
                user_agent: trimTo(window.navigator?.userAgent ?? null, 1000),
                language: trimTo(window.navigator?.language ?? null, 32),
                platform: buildPlatformLabel(),
                breakpoint: isNativeRuntime ? null : trimTo(readBreakpoint(), 8),
            };
        };

        const dispatchModal = ({
            mode = 'auto',
            force = false,
            entries = null
        } = {}) => {
            if (isReportingDisabled()) {
                return;
            }

            if (isModalOpen) {
                return;
            }

            const normalizedMode = mode === 'manual' ? 'manual' : 'auto';
            const payloadEntries = Array.isArray(entries) ? entries : loadEntries();

            if (!force && payloadEntries.length === 0) {
                return;
            }

            const now = Date.now();
            if (!force && now - lastOpenedAt < openCooldownInMs) {
                return;
            }

            lastOpenedAt = now;
            lastModalMode = normalizedMode;

            window.dispatchEvent(
                new CustomEvent('open-js-error-report-modal', {
                    detail: {
                        mode: normalizedMode,
                        errors: normalizedMode === 'manual' ? [] : payloadEntries,
                        context: collectContext(),
                    },
                }),
            );
        };

        const addEntry = (entry) => {
            if (isReportingDisabled()) {
                return;
            }

            const normalizedEntry = normalizeEntry(entry);
            if (!normalizedEntry) {
                return;
            }

            if (shouldIgnoreEntry(normalizedEntry)) {
                return;
            }

            const entries = loadEntries();
            entries.push(normalizedEntry);
            saveEntries(entries);
            window.__jsErrorReportingInProgress = true;
            queueMicrotask(dispatchModal);
        };

        const fastTransitionDurationInMs = () => {
            return (
                window.Alpine?.$data?.(document.body)?.fastTransitionDurationInMs ??
                250
            );
        };

        const reloadApplicationWithBlink = () => {
            if (hasTriggeredReload) {
                return;
            }

            hasTriggeredReload = true;
            window.dispatch('livewire-session-timed-out');

            setTimeout(() => {
                window.location.reload();
            }, fastTransitionDurationInMs());
        };

        window.addEventListener('error', (event) => {
            const candidateEntry = {
                type: 'error',
                time: new Date().toISOString(),
                message: event.message,
                source: event.filename,
                line: event.lineno,
                column: event.colno,
                stack: event.error ? event.error.stack : null,
            };

            if (shouldPreventDefaultErrorEvent(candidateEntry)) {
                event.preventDefault();
            }

            addEntry(candidateEntry);
        });

        window.addEventListener('unhandledrejection', (event) => {
            const reason = event.reason;
            const candidateEntry = {
                type: 'promise',
                time: new Date().toISOString(),
                message: reason && reason.message ? reason.message : String(reason),
                source: null,
                line: null,
                column: null,
                stack: reason && reason.stack ? reason.stack : null,
            };

            if (shouldPreventDefaultErrorEvent(candidateEntry)) {
                event.preventDefault();
            }

            addEntry(candidateEntry);
        });

        window.addEventListener('js-error-report-submitted', () => {
            saveEntries([]);
            window.__jsErrorReportingInProgress = false;

            try {
                if (lastModalMode === 'manual') {
                    sessionStorage.removeItem(successfulSubmissionFlag);

                    queueMicrotask(() => {
                        if (window.Livewire?.dispatchTo) {
                            window.Livewire.dispatchTo('js-error-reporter', 'show-submitted-toast');
                        }
                    });

                    return;
                }

                sessionStorage.setItem(successfulSubmissionFlag, '1');
            } catch (error) {}
        });
        window.addEventListener('js-error-report-modal-opened', () => {
            isModalOpen = true;
            saveEntries([]);
            window.__jsErrorReportingInProgress = true;
        });
        window.addEventListener('js-error-report-modal-closed', () => {
            isModalOpen = false;
            saveEntries([]);
            window.__jsErrorReportingInProgress = false;

            if (lastModalMode === 'manual') {
                lastModalMode = 'auto';
                return;
            }

            reloadApplicationWithBlink();
        });

        window.addEventListener('trigger-js-error-report-modal', () => {
            dispatchModal({
                mode: 'manual',
                force: true,
                entries: [],
            });
        });

        document.addEventListener('livewire:init', () => {
            try {
                if (sessionStorage.getItem(successfulSubmissionFlag) === '1') {
                    sessionStorage.removeItem(successfulSubmissionFlag);

                    queueMicrotask(() => {
                        if (window.Livewire?.dispatchTo) {
                            window.Livewire.dispatchTo('js-error-reporter', 'show-submitted-toast');
                        }
                    });
                }
            } catch (error) {}

            if (!isReportingDisabled() && loadEntries().length > 0) {
                dispatchModal();
            }
        });
    })();
</script>
