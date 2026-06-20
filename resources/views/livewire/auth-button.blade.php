@php($isAuthenticated = auth()->check())

<div>
    <div
        data-stack-item
        @class([
            'sm:top-5 md:top-5 lg:top-5' => !is_platform('ios'),
            'sm:top-9 md:top-9 lg:top-9' => is_platform('ios'),
            'sm:inset-s-21! md:inset-s-21! lg:inset-s-21! xl:inset-s-22! 2xl:inset-s-26! 3xl:inset-s-28! 4xl:start-29! xl:top-6.5 fixed top-5 z-30 2xl:top-8',
        ])
        x-data="{ isAuthenticated: @js($isAuthenticated) }"
        x-transition
        x-show="views['main-menu'].isOpen && !isControlPanelOpen && !isAthkarManagerOpen && !isIntroductionVideoOpen"
    >
        <x-action-button
            data-testid="auth-button"
            :useInvertedStyle="true"
            wire:click="mountAction('{{ $isAuthenticated ? 'account' : 'login' }}')"
        >
            <x-slot:icons-slot>
                <x-icon
                    class="text-primary-600 dark:text-primary-50 absolute h-8 w-8 shrink-0 -rotate-45 transition-all will-change-auto"
                    :name="$isAuthenticated ? 'tabler.packages' : 'heroicon-o-arrow-right-end-on-rectangle'"
                    x-bind:class="{
                        'text-white! dark:text-primary-600!': hovered,
                        'top-[0.1rem] sm:top-[0.2rem] left-0 stroke-[2px] sm:stroke-[1.5]': !isAuthenticated,
                        'top-0 left-0 stroke-[1.5px] sm:stroke-[1px]': isAuthenticated,
                    }"
                />
            </x-slot:icons-slot>
        </x-action-button>
    </div>

    @if ($this->freshCredentials)
        {{-- Auto-open the account modal once after first Telegram registration so the user sees their credentials. --}}
        <div x-init="$nextTick(() => $wire.mountAction('account'))"></div>
    @endif

    <x-filament-actions::modals />
</div>
