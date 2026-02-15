@props([
    'jsShowCondition' => 'true',
    'jsClickCallback' => '',
])

<div
    class="end-26 fixed bottom-11 z-30 sm:bottom-12"
    data-stack-item
    x-transition
    x-cloak
    x-show="{{ $jsShowCondition }} && !isSettingsOpen && !isAthkarManagerOpen"
>
    <x-action-button
        data-testid="return-button"
        :useInvertedStyle="true"
        :iconName="'ikonate.return'"
        x-on:click="{{ $jsClickCallback }}"
    />
</div>
