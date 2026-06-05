<div
    x-data="{ lastView: null }"
    x-on:switch-view.window="
        const nextView = $event.detail?.to ?? null;

        if (typeof nextView !== 'string' || nextView === lastView) {
            return;
        }

        lastView = nextView;
        $wire.trackAppView(nextView);
    "
></div>
