<div
    class="sm:inset-e-21! md:inset-e-21! lg:inset-e-21! xl:inset-e-22! 2xl:inset-e-26! 3xl:inset-e-28! 4xl:end-29! xl:top-6.5 fixed top-5 z-30 sm:top-5 md:top-5 lg:top-5 2xl:top-8"
    data-stack-item
    x-transition
    x-cloak
    x-show="!isControlPanelOpen && !isAthkarManagerOpen &&
        !document.body.classList.contains('quran-reader-font-scale-overlay-open') && (
        views['quran-app-tilawa'].isOpen ||
        views['quran-app-hifth'].isOpen ||
        views['quran-app-tadabbur'].isOpen
    )"
>
    <x-action-button
        data-testid="quran-font-scale-button"
        :useInvertedStyle="true"
        :iconName="'css-icons.format-line-height'"
        x-on:click="window.dispatchEvent(new CustomEvent('quran-reader-font-scale-toggle'))"
    />
</div>
