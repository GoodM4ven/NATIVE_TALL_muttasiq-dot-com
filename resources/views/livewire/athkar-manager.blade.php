<div>
    <div
        class="start-26 fixed top-7 z-30 sm:top-8"
        x-transition
        x-cloak
        x-show="!isSettingsOpen && views['athkar-app-gate'].isOpen"
    >
        <x-action-button
            data-testid="athkar-manager-button"
            :useInvertedStyle="false"
            :iconName="'boxicons.edit'"
            x-on:click="$wire.openManageAthkar($store.bp.is('base'))"
            x-on:open-athkar-manager.window="$wire.openManageAthkar($store.bp.is('base'))"
        />
    </div>

    <x-filament-actions::modals />
</div>
