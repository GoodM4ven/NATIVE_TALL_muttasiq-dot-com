// IMPORTANT:
// Text fitting requires measurable layout dimensions. Do not pair fit targets with display:none
// containers (e.g. raw x-show=false while hidden) if you need immediate refits.
// Prefer opacity/visibility transitions for hidden states, then trigger `fitty-refit`.

const athkarSettingsStorageKey = 'athkar-settings-v1';
const minimumMainTextSizeKey = 'minimum_main_text_size';
const maximumMainTextSizeKey = 'maximum_main_text_size';
const fallbackMainTextSizeLimits = Object.freeze({
    [minimumMainTextSizeKey]: Object.freeze({
        min: 10,
        max: 24,
        default: 16,
    }),
    [maximumMainTextSizeKey]: Object.freeze({
        min: 10,
        max: 24,
        default: 24,
    }),
});
const fittyTargetSelector = '[data-fitty-target]';
const fittyBoxSelector = '[data-fitty-box]';
const fittyRefitEventName = 'fitty-refit';
const fittyRefitCompleteEventName = 'fitty-refit-complete';
const deferredRetryDelayMs = 64;
const maxDeferredRetries = 2;
const postFitRetryDelayMs = 72;
const maxPostFitRetries = 2;
const transitionRefitDelayMs = 32;
const layoutAffectingTransitionProperties = new Set([
    'width',
    'height',
    'inline-size',
    'block-size',
    'max-width',
    'max-height',
    'padding',
    'padding-top',
    'padding-bottom',
    'padding-left',
    'padding-right',
    'padding-inline-start',
    'padding-inline-end',
    'font-size',
    'line-height',
    'letter-spacing',
]);

const fitQueue = new Map();
const deferredRetryTimers = new WeakMap();
const deferredRetryCounts = new WeakMap();
const postFitRetryTimers = new WeakMap();
const postFitRetryCounts = new WeakMap();
const transitionRefitTimers = new WeakMap();
let fitQueueTimer = null;
let latestSettingsOverride = null;

const isTruthyValue = (value, fallback = false) => {
    if (value === undefined || value === null || value === '') {
        return fallback;
    }

    if (typeof value === 'boolean') {
        return value;
    }

    const normalized = String(value).trim().toLowerCase();

    if (
        normalized === '1' ||
        normalized === 'true' ||
        normalized === 'yes' ||
        normalized === 'on'
    ) {
        return true;
    }

    if (
        normalized === '0' ||
        normalized === 'false' ||
        normalized === 'no' ||
        normalized === 'off'
    ) {
        return false;
    }

    return fallback;
};

const parseNumber = (value, fallback = null) => {
    const parsed = Number.parseFloat(String(value ?? ''));

    return Number.isFinite(parsed) ? parsed : fallback;
};

const toFiniteInteger = (value, fallback) => {
    const numeric = Number(value);

    if (!Number.isFinite(numeric)) {
        return fallback;
    }

    return Math.trunc(numeric);
};

const normalizeMainTextSizeLimits = (value, fallback) => {
    const minimum = toFiniteInteger(value?.min, fallback.min);
    const maximumSeed = toFiniteInteger(value?.max, fallback.max);
    const maximum = Math.max(minimum, maximumSeed);
    const defaultSeed = toFiniteInteger(value?.default, fallback.default);

    return {
        min: minimum,
        max: maximum,
        default: Math.max(minimum, Math.min(maximum, defaultSeed)),
    };
};

const resolveMainTextSizeLimits = () => {
    const limits = window.athkarMainTextSizeLimits;

    if (!limits || typeof limits !== 'object') {
        return fallbackMainTextSizeLimits;
    }

    return {
        [minimumMainTextSizeKey]: normalizeMainTextSizeLimits(
            limits?.[minimumMainTextSizeKey],
            fallbackMainTextSizeLimits[minimumMainTextSizeKey],
        ),
        [maximumMainTextSizeKey]: normalizeMainTextSizeLimits(
            limits?.[maximumMainTextSizeKey],
            fallbackMainTextSizeLimits[maximumMainTextSizeKey],
        ),
    };
};

const normalizeMainTextSize = (value, fallback, limits) => {
    const numeric = Number.isFinite(Number(value)) ? Number(value) : Number(fallback);
    const rounded = Number.isFinite(numeric) ? Math.trunc(numeric) : Number(fallback);

    return Math.min(limits.max, Math.max(limits.min, rounded));
};

const readStoredSettings = () => {
    if (typeof localStorage === 'undefined') {
        return {};
    }

    try {
        return JSON.parse(localStorage.getItem(athkarSettingsStorageKey) ?? '{}') ?? {};
    } catch (_) {
        return {};
    }
};

const resolveMainTextSizeSettings = () => {
    const defaults = window.athkarSettingsDefaults ?? {};
    const mainTextSizeLimits = resolveMainTextSizeLimits();
    const minimumLimits = mainTextSizeLimits[minimumMainTextSizeKey];
    const maximumLimits = mainTextSizeLimits[maximumMainTextSizeKey];
    const stored = readStoredSettings();
    const source =
        latestSettingsOverride && typeof latestSettingsOverride === 'object'
            ? latestSettingsOverride
            : stored;
    const minimum = normalizeMainTextSize(
        source?.[minimumMainTextSizeKey] ?? defaults?.[minimumMainTextSizeKey],
        minimumLimits.default,
        minimumLimits,
    );
    const maximum = normalizeMainTextSize(
        source?.[maximumMainTextSizeKey] ?? defaults?.[maximumMainTextSizeKey],
        maximumLimits.default,
        maximumLimits,
    );

    return {
        minimum: Math.min(minimum, maximum),
        maximum: Math.max(minimum, maximum),
        minimumLimits,
        maximumLimits,
    };
};

const resolveFittyBox = (textElement) => {
    const closestSelector = String(textElement.dataset.fittyBoxClosest ?? '').trim();

    if (closestSelector) {
        const scopedMatch = textElement.closest(closestSelector);

        if (scopedMatch) {
            return scopedMatch;
        }

        return document.querySelector(closestSelector);
    }

    return textElement.closest(fittyBoxSelector);
};

const resolveAvailableSpace = (boxElement, safePaddingX, safePaddingY) => {
    const styles = getComputedStyle(boxElement);
    const paddingInline =
        (Number.parseFloat(styles.paddingInlineStart) || 0) +
        (Number.parseFloat(styles.paddingInlineEnd) || 0);
    const paddingBlock =
        (Number.parseFloat(styles.paddingTop) || 0) +
        (Number.parseFloat(styles.paddingBottom) || 0);
    const width = Math.max(0, boxElement.clientWidth - paddingInline - safePaddingX);
    const height = Math.max(0, boxElement.clientHeight - paddingBlock - safePaddingY);

    return { width, height };
};

const isMeasurable = (textElement, boxElement) => {
    if (
        !textElement ||
        !boxElement ||
        !document.contains(textElement) ||
        !document.contains(boxElement)
    ) {
        return false;
    }

    return boxElement.clientWidth > 0 && boxElement.clientHeight > 0;
};

const destroyFittyInstance = (textElement) => {
    if (!textElement?._fittyInstance?.unsubscribe) {
        return;
    }

    try {
        textElement._fittyInstance.unsubscribe();
    } catch (_) {
        // Ignore teardown errors from detached nodes.
    }

    delete textElement._fittyInstance;
    delete textElement.dataset.fittyMinSize;
    delete textElement.dataset.fittyMaxSize;
};

const fitToHeight = ({ textElement, minSize, maxSize, availableWidth, availableHeight, step }) => {
    if (!availableWidth || !availableHeight) {
        return;
    }

    const normalizedStep = step > 0 ? step : 0.5;
    const fits = () => {
        return (
            textElement.scrollHeight <= availableHeight + 1 &&
            textElement.scrollWidth <= availableWidth + 1
        );
    };

    let low = minSize;
    let high = maxSize;
    let best = Math.max(
        minSize,
        Math.min(maxSize, Number.parseFloat(getComputedStyle(textElement).fontSize)),
    );

    if (!Number.isFinite(best)) {
        best = minSize;
    }

    for (let attempt = 0; attempt < 10; attempt += 1) {
        const mid = (low + high) / 2;
        textElement.style.fontSize = `${mid}px`;

        if (fits()) {
            best = mid;
            low = mid;
        } else {
            high = mid;
        }
    }

    let snapped = Math.round(best / normalizedStep) * normalizedStep;
    snapped = Math.max(minSize, Math.min(maxSize, snapped));
    textElement.style.fontSize = `${snapped}px`;

    while (!fits() && snapped > minSize) {
        snapped = Math.max(minSize, snapped - normalizedStep);
        textElement.style.fontSize = `${snapped}px`;
    }
};

const resolveOverflowState = ({
    textElement,
    boxElement,
    availableWidth,
    availableHeight,
    tolerance = 1,
}) => {
    if (!textElement || !boxElement || !availableWidth || !availableHeight) {
        return {
            overflowX: false,
            overflowY: false,
            isOverflowing: false,
        };
    }

    const scrollOverflowX = textElement.scrollWidth > availableWidth + tolerance;
    const scrollOverflowY = textElement.scrollHeight > availableHeight + tolerance;
    const textRect = textElement.getBoundingClientRect();
    const boxRect = boxElement.getBoundingClientRect();
    const boxStyles = getComputedStyle(boxElement);
    const paddingTop = Number.parseFloat(boxStyles.paddingTop) || 0;
    const paddingBottom = Number.parseFloat(boxStyles.paddingBottom) || 0;
    const paddingRight = Number.parseFloat(boxStyles.paddingRight) || 0;
    const paddingLeft = Number.parseFloat(boxStyles.paddingLeft) || 0;
    const contentTop = boxRect.top + paddingTop;
    const contentBottom = boxRect.bottom - paddingBottom;
    const contentLeft = boxRect.left + paddingLeft;
    const contentRight = boxRect.right - paddingRight;
    const rectOverflowX =
        textRect.left < contentLeft - tolerance || textRect.right > contentRight + tolerance;
    const rectOverflowY =
        textRect.top < contentTop - tolerance || textRect.bottom > contentBottom + tolerance;
    const sizeOverflowX = textRect.width > availableWidth + tolerance;
    const sizeOverflowY = textRect.height > availableHeight + tolerance;
    const elementOffsetOverflowX = textElement.offsetWidth > availableWidth + tolerance;
    const elementOffsetOverflowY = textElement.offsetHeight > availableHeight + tolerance;

    return {
        overflowX: scrollOverflowX || rectOverflowX || sizeOverflowX || elementOffsetOverflowX,
        overflowY: scrollOverflowY || rectOverflowY || sizeOverflowY || elementOffsetOverflowY,
        isOverflowing:
            scrollOverflowX ||
            scrollOverflowY ||
            rectOverflowX ||
            rectOverflowY ||
            sizeOverflowX ||
            sizeOverflowY ||
            elementOffsetOverflowX ||
            elementOffsetOverflowY,
    };
};

const clearTouchOverflowState = (boxElement, paddingClass) => {
    boxElement.dataset.athkarTouchScroll = 'false';
    boxElement.dataset.athkarTouchOverflow = 'false';
    boxElement.dataset.athkarScrollTarget = '';
    boxElement.scrollTop = 0;
    boxElement.classList.remove('athkar-text-box--touch-scroll');
    boxElement.classList.remove('athkar-text-box--origin-scroll');

    if (paddingClass) {
        boxElement.classList.remove(paddingClass);
    }
};

const applyOverflowState = ({ textElement, boxElement, overflowState, overflowTarget }) => {
    const manageOverflow = isTruthyValue(textElement.dataset.fittyManageOverflow, false);

    if (!manageOverflow) {
        return;
    }

    const activeForOverflow = isTruthyValue(textElement.dataset.fittyOverflowActive, true);
    const enableTouchScroll = isTruthyValue(textElement.dataset.fittyEnableTouchScroll, true);
    const paddingClass = String(textElement.dataset.fittyOverflowPaddingClass ?? 'py-2').trim();

    if (overflowTarget === 'origin') {
        boxElement.dataset.athkarOriginOverflow = overflowState.overflowY ? 'true' : 'false';
    } else {
        boxElement.dataset.athkarTextOverflow = overflowState.overflowY ? 'true' : 'false';
    }

    if (!activeForOverflow) {
        return;
    }

    const previousTarget = boxElement.dataset.athkarScrollTarget ?? '';
    const previousTouchScroll = boxElement.dataset.athkarTouchScroll ?? 'false';
    const shouldEnableTouchScroll = enableTouchScroll && overflowState.overflowY;

    boxElement.dataset.athkarScrollTarget = overflowTarget;
    boxElement.dataset.athkarTouchScroll = shouldEnableTouchScroll ? 'true' : 'false';
    boxElement.dataset.athkarTouchOverflow = shouldEnableTouchScroll ? 'true' : 'false';
    boxElement.classList.toggle('athkar-text-box--touch-scroll', shouldEnableTouchScroll);
    boxElement.classList.toggle(
        'athkar-text-box--origin-scroll',
        shouldEnableTouchScroll && overflowTarget === 'origin',
    );

    if (!shouldEnableTouchScroll && previousTouchScroll === 'true') {
        boxElement.scrollTop = 0;
    }

    if (paddingClass) {
        boxElement.classList.toggle(paddingClass, shouldEnableTouchScroll);
    }

    if (
        shouldEnableTouchScroll &&
        (previousTarget !== overflowTarget || previousTouchScroll !== 'true')
    ) {
        boxElement.scrollTop = 0;
    }
};

const resolveElementConfig = (textElement) => {
    const enabled = isTruthyValue(textElement.dataset.fittyEnabled, true);

    if (!enabled) {
        return null;
    }

    const boxElement = resolveFittyBox(textElement);

    if (!boxElement) {
        return null;
    }

    const settings = resolveMainTextSizeSettings();
    const minFromDataset = parseNumber(textElement.dataset.fittyMinSizeOverride);
    const maxFromDataset = parseNumber(textElement.dataset.fittyMaxSizeOverride);
    const minSize = Number.isFinite(minFromDataset)
        ? normalizeMainTextSize(minFromDataset, settings.minimum, settings.minimumLimits)
        : settings.minimum;
    const maxSizeSeed = Number.isFinite(maxFromDataset)
        ? normalizeMainTextSize(maxFromDataset, settings.maximum, settings.maximumLimits)
        : settings.maximum;
    const maxSize = Math.max(minSize, maxSizeSeed);
    const step = parseNumber(textElement.dataset.fittyStep, 0.5);
    const safePaddingX = Math.max(0, parseNumber(textElement.dataset.fittySafePaddingX, 6) ?? 6);
    const safePaddingY = Math.max(0, parseNumber(textElement.dataset.fittySafePaddingY, 4) ?? 4);

    return {
        textElement,
        boxElement,
        minSize,
        maxSize,
        step,
        safePaddingX,
        safePaddingY,
        overflowTarget: String(textElement.dataset.fittyOverflowTarget ?? 'text').trim() || 'text',
    };
};

const queueElement = (textElement) => {
    if (!textElement || !document.contains(textElement)) {
        fitQueue.delete(textElement);
        return;
    }

    fitQueue.set(textElement, true);
    scheduleFitQueue();
};

const queueDeferredRetry = (textElement) => {
    if (!textElement || deferredRetryTimers.has(textElement)) {
        return;
    }

    const retryCount = deferredRetryCounts.get(textElement) ?? 0;

    if (retryCount >= maxDeferredRetries) {
        return;
    }

    deferredRetryCounts.set(textElement, retryCount + 1);

    const timer = window.setTimeout(() => {
        deferredRetryTimers.delete(textElement);
        queueElement(textElement);
    }, deferredRetryDelayMs);

    deferredRetryTimers.set(textElement, timer);
};

const queuePostFitRetry = (textElement) => {
    if (!textElement || postFitRetryTimers.has(textElement)) {
        return;
    }

    const retryCount = postFitRetryCounts.get(textElement) ?? 0;

    if (retryCount >= maxPostFitRetries) {
        return;
    }

    postFitRetryCounts.set(textElement, retryCount + 1);

    const timer = window.setTimeout(() => {
        postFitRetryTimers.delete(textElement);
        queueElement(textElement);
    }, postFitRetryDelayMs);

    postFitRetryTimers.set(textElement, timer);
};

const clearPostFitRetry = (textElement) => {
    const timer = postFitRetryTimers.get(textElement);

    if (timer) {
        clearTimeout(timer);
        postFitRetryTimers.delete(textElement);
    }

    postFitRetryCounts.delete(textElement);
};

const processTextElement = (textElement) => {
    if (!textElement || !document.contains(textElement)) {
        destroyFittyInstance(textElement);
        fitQueue.delete(textElement);
        return null;
    }

    const config = resolveElementConfig(textElement);

    if (!config) {
        return null;
    }

    const {
        textElement: target,
        boxElement,
        minSize,
        maxSize,
        step,
        safePaddingX,
        safePaddingY,
        overflowTarget,
    } = config;

    const activeForOverflow = isTruthyValue(target.dataset.fittyOverflowActive, true);

    if (activeForOverflow) {
        const previousTarget = boxElement.dataset.athkarScrollTarget ?? '';

        if (previousTarget && previousTarget !== overflowTarget) {
            const paddingClass = String(target.dataset.fittyOverflowPaddingClass ?? 'py-2').trim();
            clearTouchOverflowState(boxElement, paddingClass);
        }
    }

    if (!isMeasurable(target, boxElement)) {
        queueDeferredRetry(textElement);

        return null;
    }

    const { width: availableWidth, height: availableHeight } = resolveAvailableSpace(
        boxElement,
        safePaddingX,
        safePaddingY,
    );

    if (!availableWidth || !availableHeight) {
        queueDeferredRetry(textElement);

        return null;
    }

    deferredRetryCounts.delete(textElement);
    target.style.maxWidth = `${availableWidth}px`;

    destroyFittyInstance(target);

    // Keep multiline wrapping deterministic before manual binary fitting.
    target.style.whiteSpace = 'break-spaces';

    fitToHeight({
        textElement: target,
        minSize,
        maxSize,
        availableWidth,
        availableHeight,
        step,
    });

    target.classList.add('is-fit');

    const overflowState = resolveOverflowState({
        textElement: target,
        boxElement,
        availableWidth,
        availableHeight,
    });

    applyOverflowState({
        textElement: target,
        boxElement,
        overflowState,
        overflowTarget,
    });

    const manageOverflow = isTruthyValue(target.dataset.fittyManageOverflow, false);

    if (manageOverflow && activeForOverflow && !overflowState.overflowY) {
        queuePostFitRetry(textElement);
    } else {
        clearPostFitRetry(textElement);
    }

    return overflowState;
};

const runFitQueue = () => {
    fitQueueTimer = null;

    const next = fitQueue.entries().next();

    if (next.done) {
        return;
    }

    const [textElement] = next.value;
    fitQueue.delete(textElement);
    processTextElement(textElement);

    if (fitQueue.size > 0) {
        scheduleFitQueue();
        return;
    }

    window.dispatchEvent(new CustomEvent(fittyRefitCompleteEventName));
};

const scheduleFitQueue = () => {
    if (fitQueueTimer !== null) {
        return;
    }

    fitQueueTimer = window.setTimeout(runFitQueue, 0);
};

const queueAll = () => {
    document.querySelectorAll(fittyTargetSelector).forEach((textElement) => {
        if (!isTruthyValue(textElement.dataset.fittyEnabled, true)) {
            return;
        }

        queueElement(textElement);
    });
};

const refitTargets = (targets = null) => {
    if (Array.isArray(targets) && targets.length > 0) {
        targets.forEach((target) => {
            if (target instanceof Element) {
                queueElement(target);
            }
        });

        return;
    }

    queueAll();
};

const scheduleTransitionRefit = (boxElement) => {
    if (!boxElement || transitionRefitTimers.has(boxElement)) {
        return;
    }

    const timer = window.setTimeout(() => {
        transitionRefitTimers.delete(boxElement);

        const targets = Array.from(boxElement.querySelectorAll(fittyTargetSelector)).filter(
            (target) => target instanceof Element,
        );

        refitTargets(targets);
    }, transitionRefitDelayMs);

    transitionRefitTimers.set(boxElement, timer);
};

const shouldScheduleTransitionRefit = (event) => {
    const propertyName = String(event?.propertyName ?? '')
        .trim()
        .toLowerCase();

    if (!propertyName) {
        return true;
    }

    return layoutAffectingTransitionProperties.has(propertyName);
};

const isFittyTransitionSource = (source, boxElement) => {
    if (!source || !boxElement) {
        return false;
    }

    if (source === boxElement) {
        return true;
    }

    if (source.matches?.(fittyTargetSelector)) {
        return true;
    }

    return Boolean(source.closest?.(fittyTargetSelector));
};

window.addEventListener(fittyRefitEventName, (event) => {
    const targets = Array.isArray(event?.detail?.targets) ? event.detail.targets : null;
    refitTargets(targets);
});
window.addEventListener('control-panel-updated', (event) => {
    latestSettingsOverride = event?.detail?.controlPanel ?? null;
    refitTargets();
});
window.addEventListener(
    'resize',
    () => {
        refitTargets();
    },
    { passive: true },
);
window.addEventListener(
    'orientationchange',
    () => {
        refitTargets();
    },
    { passive: true },
);
window.addEventListener(
    'transitionend',
    (event) => {
        if (!shouldScheduleTransitionRefit(event)) {
            return;
        }

        const source = event.target;

        if (!(source instanceof Element)) {
            return;
        }

        const boxElement = source.matches(fittyBoxSelector)
            ? source
            : source.closest(fittyBoxSelector);

        if (!boxElement) {
            return;
        }

        if (!isFittyTransitionSource(source, boxElement)) {
            return;
        }

        scheduleTransitionRefit(boxElement);
    },
    true,
);
window.addEventListener('pageshow', () => {
    refitTargets();
});
window.addEventListener(
    'load',
    () => {
        refitTargets();
        window.setTimeout(() => {
            refitTargets();
        }, 96);
    },
    { once: true },
);

if (document.fonts?.ready) {
    document.fonts.ready.then(() => {
        refitTargets();
    });
}

window.requestFittyRefit = refitTargets;

export { refitTargets as requestFittyRefit };
