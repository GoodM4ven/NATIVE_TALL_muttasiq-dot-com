<div
    x-data="{
        jsErrorReporterModalId: @js('fi-' . $this->getId() . '-action-0'),
        hasDispatchedModalClose: false,
        markModalOpened() {
            this.hasDispatchedModalClose = false;
            window.dispatch('js-error-report-modal-opened');
        },
        markModalClosed() {
            if (this.hasDispatchedModalClose) {
                return;
            }
    
            this.hasDispatchedModalClose = true;
            window.dispatch('js-error-report-modal-closed');
        },
    }"
    x-on:open-js-error-report-modal.window="$wire.openReportModal($event.detail ?? {})"
    x-on:x-modal-opened.window="
        if ($event.detail?.id === jsErrorReporterModalId) {
            markModalOpened();
        }
    "
    x-on:close-modal.window="
        if ($event.detail?.id === jsErrorReporterModalId) {
            markModalClosed();
        }
    "
    x-on:close-modal-quietly.window="
        if ($event.detail?.id === jsErrorReporterModalId) {
            markModalClosed();
        }
    "
>
    <x-filament-actions::modals />
</div>

@assets
    <x-partials.scripts.mobile-js-errors-handler />
@endassets
