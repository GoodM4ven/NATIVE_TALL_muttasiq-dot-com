@props([
    'jsShowCondition' => 'true',
    'jsClickCallback' => '',
])

<div
    class="sm:inset-e-16.5! md:end-18! lg:inset-e-21! xl:inset-e-22! 2xl:inset-e-26! 3xl:inset-e-28! 4xl:end-29! fixed bottom-7 z-30 sm:bottom-6 md:bottom-6 lg:bottom-7 xl:bottom-10 2xl:bottom-12"
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
