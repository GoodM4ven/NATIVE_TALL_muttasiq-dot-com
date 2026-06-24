<div>
    <div
        data-stack-item
        @class([
            'sm:top-5 md:top-5 lg:top-5' => !is_platform('ios'),
            'sm:top-9 md:top-9 lg:top-9' => is_platform('ios'),
            'sm:inset-s-21! md:inset-s-21! lg:inset-s-21! xl:inset-s-22! 2xl:inset-s-26! 3xl:inset-s-28! 4xl:start-29! xl:top-6.5 fixed top-5 z-30 2xl:top-8',
        ])
        x-data="{
            managerModalId: @js('fi-' . $this->getId() . '-action-0'),
            isAthkarManagerLoading: false,
            athkarManagerLoadingSafetyTimerId: null,
            beginAthkarManagerLoading() {
                isAthkarManagerOpen = true;
                this.isAthkarManagerLoading = true;
        
                if (this.athkarManagerLoadingSafetyTimerId !== null) {
                    window.clearTimeout(this.athkarManagerLoadingSafetyTimerId);
                }
        
                this.athkarManagerLoadingSafetyTimerId = window.setTimeout(() => {
                    this.isAthkarManagerLoading = false;
                    this.athkarManagerLoadingSafetyTimerId = null;
                }, 6000);
            },
            endAthkarManagerLoading() {
                this.isAthkarManagerLoading = false;
        
                if (this.athkarManagerLoadingSafetyTimerId !== null) {
                    window.clearTimeout(this.athkarManagerLoadingSafetyTimerId);
                    this.athkarManagerLoadingSafetyTimerId = null;
                }
            },
        }"
        x-transition
        x-cloak
        x-show="!isControlPanelOpen && !isAthkarManagerOpen && views['athkar-app-gate'].isOpen"
        x-on:x-modal-opened.window="if ($event.detail?.id === managerModalId) { isAthkarManagerOpen = true; endAthkarManagerLoading(); }"
        x-on:close-modal.window="if ($event.detail?.id === managerModalId) { isAthkarManagerOpen = false; endAthkarManagerLoading(); }"
        x-on:close-modal-quietly.window="if ($event.detail?.id === managerModalId) { isAthkarManagerOpen = false; endAthkarManagerLoading(); }"
    >
        <x-action-button
            data-testid="athkar-manager-button"
            :useInvertedStyle="false"
            :iconName="'boxicons.edit'"
            x-on:click="beginAthkarManagerLoading(); $wire.openManageAthkar(!$store.bp.is('sm+'))"
            x-on:open-athkar-manager.window="beginAthkarManagerLoading(); $wire.openManageAthkar(!$store.bp.is('sm+'))"
        />

        <template x-teleport="body">
            <div
                class="fixed inset-0 z-[2147481999] flex items-center justify-center bg-black/25 backdrop-blur-[10px]"
                x-cloak
                x-show="isAthkarManagerLoading"
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

    <x-filament-actions::modals />
</div>
