document.addEventListener('alpine:init', () => {
    // ponytail: ViewerJS auto-binds every <img> inside the element, so one directive on a
    // container is enough — no per-image handlers. Lazy-imported so the ~30KB lib + CSS
    // only load when something actually renders x-viewer (e.g. the changelog modal).
    window.Alpine.directive('viewer', (el, _meta, { cleanup }) => {
        let instance = null;
        let destroyed = false;

        Promise.all([import('viewerjs'), import('viewerjs/dist/viewer.min.css')])
            .then(([{ default: Viewer }]) => {
                if (destroyed) return;

                instance = new Viewer(el, {
                    // Filament modals sit at a high stacking layer; lift the viewer above them.
                    zIndex: 2147483647,
                    navbar: false,
                    title: false,
                    toolbar: {
                        zoomIn: true,
                        zoomOut: true,
                        reset: true,
                        prev: true,
                        next: true,
                        rotateLeft: true,
                        rotateRight: true,
                    },
                    // While the viewer owns the keyboard, flag it so the app's own global key
                    // handlers (e.g. the Quran reader's window-capture arrow nav) stand down.
                    // ViewerJS keeps its built-in keys; the app just yields.
                    shown() {
                        window.__viewerKeyboardActive = true;
                    },
                    hidden() {
                        window.__viewerKeyboardActive = false;
                    },
                });
            })
            .catch(() => {});

        cleanup(() => {
            destroyed = true;
            instance?.destroy();
        });
    });
});
