import fitty from 'fitty';

window.fitty = fitty;

const resolveMaxTextSize = (textElement, boxElement, baseSize, maxScale) => {
    if (!Number.isFinite(maxScale) || maxScale <= 1) {
        return baseSize;
    }

    textElement.style.fontSize = `${baseSize}px`;
    const baseHeight = textElement.scrollHeight;
    const baseWidth = textElement.scrollWidth;

    if (!baseHeight) {
        return baseSize;
    }

    const heightScale = boxElement.clientHeight / baseHeight;
    const widthScale = baseWidth ? boxElement.clientWidth / baseWidth : heightScale;

    if (heightScale <= 1 && widthScale <= 1) {
        return baseSize;
    }

    const allowedScale = Math.min(maxScale, Math.max(heightScale, widthScale));

    return Math.max(baseSize, baseSize * allowedScale);
};

const ensureFittyInstance = (textElement, minSize, maxSize) => {
    const storedMin = Number.parseFloat(textElement.dataset.fittyMinSize ?? '0');
    const storedMax = Number.parseFloat(textElement.dataset.fittyMaxSize ?? '0');

    if (textElement._fittyInstance && storedMin === minSize && storedMax === maxSize) {
        return textElement._fittyInstance;
    }

    if (textElement._fittyInstance?.unsubscribe) {
        textElement._fittyInstance.unsubscribe();
    }

    const instance = window.fitty(textElement, {
        minSize,
        maxSize,
        multiLine: true,
        observeMutations: false,
        observeWindow: false,
    });

    textElement._fittyInstance = instance;
    textElement.dataset.fittyMinSize = String(minSize);
    textElement.dataset.fittyMaxSize = String(maxSize);

    return instance;
};

const fitTextToBox = (textElement, boxElement, minSize, maxSize, step) => {
    let size = Number.parseFloat(getComputedStyle(textElement).fontSize);

    if (!Number.isFinite(size)) {
        return;
    }

    const fits = () => {
        return (
            textElement.scrollHeight <= boxElement.clientHeight &&
            textElement.scrollWidth <= boxElement.clientWidth
        );
    };

    if (!fits()) {
        size = Math.max(minSize, Math.min(maxSize, size));
    }

    let low = minSize;
    let high = maxSize;
    let best = Math.max(minSize, Math.min(size, maxSize));

    for (let i = 0; i < 14; i += 1) {
        const mid = (low + high) / 2;
        textElement.style.fontSize = `${mid}px`;

        if (fits()) {
            best = mid;
            low = mid;
        } else {
            high = mid;
        }
    }

    const rounded = Math.max(minSize, Math.min(maxSize, best));
    const snapped = step > 0 ? Math.round(rounded / step) * step : rounded;

    textElement.style.fontSize = `${snapped}px`;
};

const fitTextInBox = ({
    textElement,
    boxElement,
    minSize = 16,
    maxScale = 1.2,
    step = 0.5,
    shouldApplyFittyClass = true,
}) => {
    if (!textElement || !boxElement) {
        return;
    }

    if (!window.fitty || !boxElement.clientWidth || !boxElement.clientHeight) {
        if (shouldApplyFittyClass) {
            textElement.classList.add('is-fit');
        }

        return;
    }

    textElement.style.fontSize = '';
    const baseSize = Number.parseFloat(getComputedStyle(textElement).fontSize);

    if (!Number.isFinite(baseSize)) {
        return;
    }

    const maxSize = resolveMaxTextSize(textElement, boxElement, baseSize, maxScale);
    textElement.style.fontSize = `${baseSize}px`;

    const instance = ensureFittyInstance(textElement, minSize, maxSize);

    if (instance?.fit) {
        instance.fit();
    }

    fitTextToBox(textElement, boxElement, minSize, maxSize, step);

    if (shouldApplyFittyClass) {
        requestAnimationFrame(() => {
            textElement.classList.add('is-fit');
        });
    }
};

export { fitTextInBox };
