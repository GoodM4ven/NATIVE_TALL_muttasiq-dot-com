@props([
    'jsShowCondition' => "!views['main-menu'].isOpen && !isControlPanelOpen && !isAthkarManagerOpen",
])

<div
    class="inset-e-10 sm:inset-e-4.5 md:inset-e-6 lg:inset-e-6.5 xl:inset-e-8 2xl:inset-e-10 fixed z-30 bottom-7 sm:bottom-6 md:bottom-6 lg:bottom-7 xl:bottom-10 2xl:bottom-12"
    data-stack-item
    x-transition
    x-cloak
    x-show="{{ $jsShowCondition }}"
>
    <x-action-button
        data-testid="control-panel-button"
        :iconName="'material-design.grid-view'"
        x-on:click="$viewNav('main-menu', { force: true })"
    />
</div>
