<div>
    <div
        class="fixed end-10 top-7 z-30 sm:top-8"
        data-stack-item
        wire:ignore
        x-transition
        x-show="!isSettingsOpen && !isAthkarManagerOpen"
        x-data="{
            settingsModalId: @js('fi-' . $this->getId() . '-action-0'),
        }"
        x-on:open-settings-modal.window="
            $wire.syncClientSettings(window.getAthkarSettingsFromStorage?.() ?? {});
            $wire.mountAction('settings');
        "
        x-on:x-modal-opened.window="if ($event.detail?.id === settingsModalId) isSettingsOpen = true;"
        x-on:close-modal.window="if ($event.detail?.id === settingsModalId) isSettingsOpen = false;"
        x-on:close-modal-quietly.window="if ($event.detail?.id === settingsModalId) isSettingsOpen = false;"
    >
        <x-action-button
            data-testid="settings-button"
            :useInvertedStyle="true"
            :iconName="'heroicon-s-adjustments-horizontal'"
            x-on:click="$hashAction('settings')"
        />
    </div>

    <x-filament-actions::modals />
</div>
