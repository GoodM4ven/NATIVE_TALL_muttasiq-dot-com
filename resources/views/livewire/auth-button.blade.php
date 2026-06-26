@php($isAuthenticated = auth()->check())

<div>
    <div
        data-stack-item
        @class([
            'sm:top-5 md:top-5 lg:top-5' => !is_platform('ios'),
            'sm:top-9 md:top-9 lg:top-9' => is_platform('ios'),
            'sm:inset-s-21! md:inset-s-21! lg:inset-s-21! xl:inset-s-22! 2xl:inset-s-26! 3xl:inset-s-28! 4xl:start-29! xl:top-6.5 fixed top-5 z-30 2xl:top-8',
        ])
        x-data="{
            authModalId: @js('fi-' . $this->getId() . '-action-0'),
            isAuthenticated: @js($isAuthenticated),
            isAuthModalLoading: false,
            authModalLoadingSafetyTimerId: null,
            beginAuthModalLoading() {
                this.isAuthModalLoading = true;
        
                if (this.$store?.layoutManager) {
                    this.$store.layoutManager.isActionOpen = true;
                }
        
                if (this.authModalLoadingSafetyTimerId !== null) {
                    window.clearTimeout(this.authModalLoadingSafetyTimerId);
                }
        
                this.authModalLoadingSafetyTimerId = window.setTimeout(() => {
                    this.isAuthModalLoading = false;
                    this.authModalLoadingSafetyTimerId = null;
                }, 6000);
            },
            endAuthModalLoading() {
                this.isAuthModalLoading = false;
        
                if (this.authModalLoadingSafetyTimerId !== null) {
                    window.clearTimeout(this.authModalLoadingSafetyTimerId);
                    this.authModalLoadingSafetyTimerId = null;
                }
            },
            openAuthModal() {
                this.beginAuthModalLoading();
        
                void $wire.mountAction(this.isAuthenticated ? 'account' : 'login');
            },
        }"
        x-transition
        x-show="views['main-menu'].isOpen && !isControlPanelOpen && !isAthkarManagerOpen && !isIntroductionVideoOpen"
        x-on:x-modal-opened.window="if ($event.detail?.id === authModalId) { endAuthModalLoading(); }"
        x-on:close-modal.window="if ($event.detail?.id === authModalId) { endAuthModalLoading(); }"
        x-on:close-modal-quietly.window="if ($event.detail?.id === authModalId) { endAuthModalLoading(); }"
    >
        <x-action-button
            data-testid="auth-button"
            :useInvertedStyle="true"
            x-on:click="openAuthModal()"
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

        <template x-teleport="body">
            <div
                class="fixed inset-0 z-[2147481999] flex items-center justify-center bg-black/25 backdrop-blur-[10px]"
                x-cloak
                x-show="isAuthModalLoading"
                x-transition.opacity
            >
                <svg
                    class="size-8 animate-spin text-white [animation-direction:reverse] sm:size-10"
                    aria-hidden="true"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24"
                >
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"
                    ></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
                    ></path>
                </svg>
            </div>
        </template>
    </div>

    @if ($this->freshCredentials)
        {{-- Auto-open the account modal once after first Telegram registration so the user sees their credentials. --}}
        <div x-init="$nextTick(() => $wire.mountAction('account'))"></div>
    @endif

    <x-filament-actions::modals />
</div>
