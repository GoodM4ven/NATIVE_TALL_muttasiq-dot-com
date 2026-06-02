<div
    x-data="{ lastView: null }"
    x-on:switch-view.window="
        const nextView = $event.detail?.to ?? null;

        if (typeof nextView !== 'string' || nextView === lastView) {
            return;
        }

        lastView = nextView;

        if (nextView !== 'athkar-app-gate' && nextView !== 'quran-app-gate') {
            return;
        }

        $wire.trackGateView(nextView);
    "
></div>
