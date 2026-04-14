@props([
    'jsShowCondition' => 'true',
    'jsClickCallback' => '',
])

<div
    class="fixed z-30 sm:end-13! md:end-15! lg:inset-e-17! xl:inset-e-22! 2xl:inset-e-26! 3xl:inset-e-28! 4xl:end-29! bottom-7 sm:bottom-6 md:bottom-6 lg:bottom-7 xl:bottom-10 2xl:bottom-12"
    data-stack-item
    x-transition
    x-cloak
    x-show="{{ $jsShowCondition }} && !isControlPanelOpen && !isAthkarManagerOpen"
>
    <x-action-button
        data-testid="return-button"
        :useInvertedStyle="true"
        :iconName="'ikonate.return'"
        x-on:click="{{ $jsClickCallback }}"
    />
</div>
