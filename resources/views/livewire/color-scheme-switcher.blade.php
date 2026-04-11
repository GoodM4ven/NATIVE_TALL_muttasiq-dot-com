<div
    class="inset-s-10 sm:inset-s-4.5 md:inset-s-6 lg:inset-s-6.5 xl:inset-s-8 2xl:inset-s-10 fixed top-5 z-30 sm:top-4 md:top-5 lg:top-5 xl:top-6.5 2xl:top-8"
    data-stack-item
    wire:ignore
    x-transition
    x-show="!isControlPanelOpen && !isAthkarManagerOpen"
    x-init="() => (lock = $livewireLock($wire, defaultTransitionDurationInMs, true))"
>
    <x-action-button
        data-testid="color-scheme-switch-button"
        x-on:click="$hashAction('toggle-color-scheme')"
        :useInvertedStyle="true"
    >
        <x-slot:icons-slot>
            <x-icon
                class="text-primary-600 dark:text-primary-100 absolute left-1/2 top-1/2 h-7 w-7 shrink-0 -translate-x-1/2 -translate-y-1/2 -rotate-45 transition will-change-[color] sm:h-7 sm:w-7 md:h-6 md:w-6 lg:h-8 lg:w-8"
                name="heroicon-s-moon"
                x-bind:class="{ 'text-primary-100! dark:text-primary-600!': hovered }"
                x-show="!$store.colorScheme.isDarkModeOn"
            />
            <x-icon
                class="text-primary-600 dark:text-primary-100 absolute left-1/2 top-1/2 h-7 w-7 shrink-0 -translate-x-1/2 -translate-y-1/2 -rotate-45 transition will-change-[color] sm:h-7 sm:w-7 md:h-6 md:w-6 lg:h-8 lg:w-8"
                name="heroicon-s-sun"
                x-bind:class="{ 'text-primary-600!': hovered }"
                x-cloak
                x-show="$store.colorScheme.isDarkModeOn"
            />
        </x-slot:icons-slot>
    </x-action-button>
</div>
