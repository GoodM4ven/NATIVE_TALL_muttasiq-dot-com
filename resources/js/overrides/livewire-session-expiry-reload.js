document.addEventListener('livewire:init', () => {
    if (document.documentElement?.dataset?.disableLivewireSessionReload === 'true') {
        return;
    }

    const hasPendingJsErrorReport = () => {
        if (window.__jsErrorReportingInProgress === true) {
            return true;
        }

        try {
            const entries = JSON.parse(localStorage.getItem('jsErrorLog') ?? '[]');

            return Array.isArray(entries) && entries.length > 0;
        } catch (error) {
            return false;
        }
    };

    window.Livewire.hook('request', ({ fail }) => {
        fail(({ status, preventDefault }) => {
            if (status === 419) {
                preventDefault();

                // An intentional login/logout/delete is already driving its own
                // blinker fade + reload; don't let the session-expiry fast reload
                // race ahead of it.
                if (window.__authReloadInProgress === true) {
                    return;
                }

                if (hasPendingJsErrorReport()) {
                    return;
                }

                window.dispatch('livewire-session-timed-out');
                setTimeout(
                    () => window.location.reload(),
                    window.Alpine.$data(document.body).fastTransitionDurationInMs,
                );
            }
        });
    });
});
