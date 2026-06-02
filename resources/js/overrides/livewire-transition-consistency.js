if (typeof window.isRecordSelected !== 'function') {
    window.isRecordSelected = () => false;
}

document.addEventListener('livewire:initialized', () => {
    const matches = (node) =>
        node?.tagName === 'STYLE' &&
        node.textContent.includes('prefers-reduced-motion: reduce') &&
        node.textContent.includes('::view-transition-group');

    function removeExisting() {
        for (const style of document.querySelectorAll('style')) {
            if (matches(style)) style.remove();
        }
    }

    removeExisting();

    new MutationObserver((mutations) => {
        for (const m of mutations) {
            for (const node of m.addedNodes) {
                if (matches(node)) node.remove();
            }
        }
    }).observe(document.head, {
        childList: true,
    });
});
