const stopShortcutPropagationWhenSuperKeyPressed = (event) => {
    if (!(event instanceof KeyboardEvent) || !event.metaKey) {
        return;
    }

    event.stopImmediatePropagation();
    event.stopPropagation();
};

window.addEventListener('keydown', stopShortcutPropagationWhenSuperKeyPressed, { capture: true });
window.addEventListener('keyup', stopShortcutPropagationWhenSuperKeyPressed, { capture: true });
