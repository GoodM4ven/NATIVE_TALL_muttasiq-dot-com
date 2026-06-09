<div
    x-data="{
        introductionVideoModalId: @js('fi-' . $this->getId() . '-action-0'),
        isIntroductionVideoOpen: false,
    }"
    x-on:switch-view.window="isIntroductionVideoOpen = false"
    x-on:x-modal-opened.window="
        if ($event.detail?.id === introductionVideoModalId) {
            isIntroductionVideoOpen = true;
            window.dispatchEvent(new CustomEvent('introduction-video-modal-opened'));
        }
    "
    x-on:close-modal.window="
        if ($event.detail?.id === introductionVideoModalId) {
            isIntroductionVideoOpen = false;
            window.dispatchEvent(new CustomEvent('introduction-video-modal-closed'));
        }
    "
    x-on:close-modal-quietly.window="
        if ($event.detail?.id === introductionVideoModalId) {
            isIntroductionVideoOpen = false;
            window.dispatchEvent(new CustomEvent('introduction-video-modal-closed'));
        }
    "
>
    <div
        class="inset-s-10 sm:inset-s-6.5 md:inset-s-6.5 lg:inset-s-6.5 xl:inset-s-8 2xl:inset-s-10 2xl:bottom-13 fixed bottom-8 z-30 sm:bottom-8 md:bottom-8 lg:bottom-8 xl:bottom-11"
        data-stack-item
        x-transition
        x-cloak
        x-show="views['main-menu'].isOpen && !isControlPanelOpen && !isAthkarManagerOpen && !isIntroductionVideoOpen"
    >
        <x-action-button
            data-testid="introduction-video-button"
            :useInvertedStyle="true"
            :iconName="'heroicon-o-play-circle'"
            :iconClasses="'!h-6 !w-6 sm:!h-6.5 sm:!w-6.5 md:!h-6.5 md:!w-6.5 lg:!h-6.5 lg:!w-6.5 xl:!h-7 xl:!w-7'"
            :extraAttributes="[
                'title' => arabic_text('ما هو متسق؟'),
                'aria-label' => arabic_text('ما هو متسق؟'),
            ]"
            x-on:click.stop.prevent="
                if ($store?.layoutManager) {
                    $store.layoutManager.isActionOpen = true;
                }

                void $wire.mountAction('openIntroductionVideo')
            "
        />
    </div>

    <x-filament-actions::modals />
</div>
