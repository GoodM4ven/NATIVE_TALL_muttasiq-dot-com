@props([
    'jsShowCondition' => "!views['main-menu'].isOpen && !isControlPanelOpen && !isAthkarManagerOpen",
])

<div
    class="inset-e-10 fixed bottom-7 z-30 sm:bottom-7 md:bottom-12 xl:[zoom:1.25]"
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
