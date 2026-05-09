<div>
    <div
        class="sm:inset-s-16! md:start-17.5! lg:inset-s-21! xl:inset-s-22! 2xl:inset-s-26! 3xl:inset-s-28! 4xl:start-29! xl:top-6.5 fixed top-5 z-30 sm:top-4 md:top-5 lg:top-5 2xl:top-8"
        data-stack-item
        x-data="{
            managerModalId: @js('fi-' . $this->getId() . '-action-0'),
        }"
        x-transition
        x-cloak
        x-show="!isControlPanelOpen && !isAthkarManagerOpen && views['athkar-app-gate'].isOpen"
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
