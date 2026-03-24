<div>
    <div
        class="inset-e-10 fixed top-7 z-30 sm:top-5 md:top-8 xl:[zoom:1.25]"
        data-stack-item
        wire:ignore
        x-transition
        x-show="!isControlPanelOpen && !isAthkarManagerOpen"
        x-data="{
            controlPanelModalId: @js('fi-' . $this->getId() . '-action-0'),
            isReaderMaintenanceInFlight: false,
            hasQueuedReaderMaintenance: false,
            async runReaderMaintenancePulse() {
                if (this.isReaderMaintenanceInFlight) {
                    this.hasQueuedReaderMaintenance = true;
        
                    return;
                }
        
                if (
                    isControlPanelOpen ||
                    isAthkarManagerOpen ||
                    this.$store?.layoutManager?.isActionOpen
                ) {
                    return;
                }
        
                this.isReaderMaintenanceInFlight = true;
        
                try {
                    await $wire.triggerReaderMaintenancePulse();
                } finally {
                    this.isReaderMaintenanceInFlight = false;
        
                    if (this.hasQueuedReaderMaintenance) {
                        this.hasQueuedReaderMaintenance = false;
        
                        queueMicrotask(() => this.runReaderMaintenancePulse());
                    }
                }
            },
        }"
        x-on:open-control-panel-modal.window="$wire.openControlPanelModal(window.getAthkarSettingsFromStorage?.() ?? {}, $event.detail?.tab ?? null)"
        x-on:athkar-reader-maintenance.window="runReaderMaintenancePulse()"
        x-on:x-modal-opened.window="if ($event.detail?.id === controlPanelModalId) isControlPanelOpen = true;"
        x-on:close-modal.window="if ($event.detail?.id === controlPanelModalId) isControlPanelOpen = false;"
        x-on:close-modal-quietly.window="if ($event.detail?.id === controlPanelModalId) isControlPanelOpen = false;"
    >
        <x-action-button
            data-testid="control-panel-button"
            :useInvertedStyle="true"
            :iconName="'heroicon-s-adjustments-horizontal'"
            x-on:click="$hashAction('control-panel')"
        />
    </div>

    <x-filament-actions::modals />
</div>
