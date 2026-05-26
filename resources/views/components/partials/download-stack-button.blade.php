<div
    class="inset-s-10 sm:inset-s-6.5 md:inset-s-6.5 lg:inset-s-6.5 xl:inset-s-8 2xl:inset-s-10 fixed bottom-7 z-30 sm:bottom-7 md:bottom-7 lg:bottom-7 xl:bottom-10 2xl:bottom-12"
    data-stack-item
    x-data="{ isExpanded: false }"
    x-transition
    x-cloak
    x-show="views['main-menu'].isOpen && !isControlPanelOpen && !isAthkarManagerOpen"
    x-on:click.outside="isExpanded = false"
    x-on:switch-view.window="isExpanded = false"
    x-on:keydown.escape.window="isExpanded = false"
>
    <div class="relative inline-grid place-items-center">
        <div x-bind:class="{ 'scale-110': isExpanded }">
            <x-action-button
                data-testid="download-button"
                :useInvertedStyle="true"
                :iconName="'feather-icons.download'"
                :iconClasses="'text-primary-600 dark:text-primary-50 !h-4 !w-4 sm:!h-5.5 sm:!w-5.5 md:!h-5.5 md:!w-5.5 lg:!h-5.5 lg:!w-5.5 xl:!h-6 xl:!w-6'"
                :extraAttributes="[
                    'title' => arabic_text('تحميل'),
                    'aria-label' => arabic_text('تحميل'),
                ]"
                x-on:click.stop="isExpanded = !isExpanded"
            />
        </div>

        <div
            class="absolute left-1/2 top-[calc(100%+0.6rem)] z-40 flex -translate-x-1/2 flex-col items-center gap-[0.6rem] sm:bottom-[calc(100%+0.6rem)] sm:left-1/2 sm:right-auto sm:top-auto sm:-translate-x-1/2 sm:items-center"
            x-cloak
            x-show="isExpanded"
            x-transition:enter="transition-[opacity,transform] duration-180 ease-out"
            x-transition:enter-start="opacity-0 -translate-y-1/2"
            x-transition:enter-end="opacity-100 -translate-y-0"
            x-transition:leave="transition-[opacity,transform] duration-140 ease-in"
            x-transition:leave-start="opacity-100 -translate-y-0"
            x-transition:leave-end="opacity-0 -translate-y-1/2"
        >
            <x-action-button
                data-testid="download-android-button"
                :useInvertedStyle="true"
                :iconName="'ion-icons.playstore'"
                :iconClasses="'!h-4 !w-4 sm:!h-5 sm:!w-5 md:!h-5 md:!w-5 lg:!h-5 lg:!w-5 xl:!h-5.5 xl:!w-5.5'"
                :extraAttributes="[
                    'title' => arabic_text('تحميل أندرويد'),
                    'aria-label' => arabic_text('تحميل أندرويد'),
                ]"
                x-on:click.stop.prevent="window.open('https://play.google.com/store/apps/details?id=dev.goodm4ven.muttasiq', '_blank', 'noopener,noreferrer'); isExpanded = false;"
            />

            <x-action-button
                data-testid="download-ios-button"
                :useInvertedStyle="true"
                :iconName="'simple-icons.appstore'"
                :iconClasses="'!h-4 !w-4 sm:!h-5 sm:!w-5 md:!h-5 md:!w-5 lg:!h-5 lg:!w-5 xl:!h-5.5 xl:!w-5.5'"
                :extraAttributes="[
                    'title' => arabic_text('تحميل آيفون'),
                    'aria-label' => arabic_text('تحميل آيفون'),
                ]"
                x-on:click.stop.prevent="window.open('https://apps.apple.com/us/app/%D9%85%D8%AA%D8%B3%D9%82/id6759810123', '_blank', 'noopener,noreferrer'); isExpanded = false;"
            />
        </div>
    </div>
</div>
