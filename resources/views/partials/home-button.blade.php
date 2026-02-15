<div
    class="fixed bottom-11 end-10 z-30 sm:bottom-12"
    data-stack-item
    x-transition
    x-cloak
    x-show="!views['main-menu'].isOpen && !isSettingsOpen && !isAthkarManagerOpen"
>
    <x-action-button
        data-testid="settings-button"
        :iconName="'material-design.grid-view'"
        x-on:click="$viewNav('main-menu')"
    />
</div>
