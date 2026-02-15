<div>
    <div
        class="sm:start-26 fixed start-10 top-7 z-30 sm:top-8"
        x-data="{
            managerModalId: @js('fi-' . $this->getId() . '-action-0'),
        }"
        x-transition
        x-cloak
        x-show="!isSettingsOpen && !isAthkarManagerOpen && views['athkar-app-gate'].isOpen"
        x-on:x-modal-opened.window="if ($event.detail?.id === managerModalId) isAthkarManagerOpen = true;"
        x-on:close-modal.window="if ($event.detail?.id === managerModalId) isAthkarManagerOpen = false;"
        x-on:close-modal-quietly.window="if ($event.detail?.id === managerModalId) isAthkarManagerOpen = false;"
    >
        <x-action-button
            data-testid="athkar-manager-button"
            :useInvertedStyle="false"
            :iconName="'boxicons.edit'"
            x-on:click="$wire.openManageAthkar(!$store.bp.is('sm+'))"
            x-on:open-athkar-manager.window="$wire.openManageAthkar(!$store.bp.is('sm+'))"
        />
    </div>

    <x-filament-actions::modals />
</div>
