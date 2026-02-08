<!-- Background -->
<div
    class="pointer-events-none fixed inset-0 -z-20 overflow-hidden"
    x-cloak
    x-transition:enter="transition ease-out duration-300 delay-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-show="$store.bp.is('sm+') && (views[`main-menu`].isOpen || views[`athkar-app-gate`].isReaderVisible)"
>
    <div
        class="absolute inset-0 opacity-10 transition-opacity duration-1000 will-change-[opacity]"
        x-bind:class="!$store.colorScheme.isDarkModeOn && 'opacity-20!'"
    >
        <!-- LIGHT MODE -->
        <div
            class="duration-2000 absolute inset-0 opacity-0 transition-opacity will-change-[opacity]"
            x-bind:class="!$store.colorScheme.isDarkModeOn && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover blur-md"
                alt="Morning background"
                :imagePath="asset('images/morning.png')"
                :thumbnailImagePath="asset('images/morning-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <!-- DARK MODE -->
        <div
            class="duration-2000 absolute inset-0 opacity-0 transition-opacity will-change-[opacity]"
            x-bind:class="$store.colorScheme.isDarkModeOn && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover blur-md"
                alt="Night background"
                :imagePath="asset('images/night.png')"
                :thumbnailImagePath="asset('images/night-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <!-- OPAQUE OVERLAY -->
        <div class="absolute inset-0 dark:bg-black/60"></div>
    </div>
</div>
