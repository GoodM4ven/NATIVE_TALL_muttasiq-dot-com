document.addEventListener('livewire:initialized', () => {
    if (typeof window.HTMLDialogElement === 'undefined') {
        return;
    }

    const dialogPrototype = window.HTMLDialogElement.prototype;

    if (dialogPrototype.__muttasiqShowModalGuardPatched === true) {
        return;
    }

    const nativeShowModal = dialogPrototype.showModal;
    const nativeClose = dialogPrototype.close;

    if (typeof nativeShowModal !== 'function' || typeof nativeClose !== 'function') {
        return;
    }

    const closeIfOpen = (dialog) => {
        if (!dialog.open) {
            return;
        }

        try {
            nativeClose.call(dialog);
        } catch {
            // Intentionally ignore: we only need best-effort state recovery.
        }
    };

    dialogPrototype.showModal = function showModalWithStateGuard(...args) {
        if (this.open) {
            if (typeof this.matches === 'function' && this.matches(':modal')) {
                return;
            }

            closeIfOpen(this);
        }

        try {
            return nativeShowModal.apply(this, args);
        } catch (error) {
            const isInvalidStateError =
                error instanceof DOMException && error.name === 'InvalidStateError';

            if (!isInvalidStateError) {
                throw error;
            }

            closeIfOpen(this);

            return nativeShowModal.apply(this, args);
        }
    };

    Object.defineProperty(dialogPrototype, '__muttasiqShowModalGuardPatched', {
        configurable: false,
        enumerable: false,
        value: true,
        writable: false,
    });
});
