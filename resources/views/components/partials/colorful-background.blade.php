<!-- Background -->
<div
    class="pointer-events-none fixed inset-0 z-0 overflow-hidden"
    x-cloak
    x-transition:enter="transition ease-out duration-300 delay-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-show="views[`main-menu`].isOpen || views[`athkar-app-gate`].isReaderVisible || views[`quran-app-tilawa`].isOpen || views[`quran-app-hifth`].isOpen || views[`quran-app-tadabbur`].isOpen"
>
    <div
        class="duration-400 absolute inset-0 opacity-10 transition-opacity will-change-[opacity]"
        x-bind:class="!$store.colorScheme.isDarkModeOn && 'opacity-15! sm:opacity-20!'"
    >
        <!-- LIGHT MODE -->
        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="main-menu-bg-light-layer"
            x-bind:class="!$store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen &&
                !views[`quran-app-tadabbur`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Morning background"
                :imagePath="asset('images/background/morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/morning-blurred-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <!-- DARK MODE -->
        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="main-menu-bg-dark-layer"
            x-bind:class="$store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen &&
                !views[`quran-app-tadabbur`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Night background"
                :imagePath="asset('images/background/night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/night-blurred-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-tilawa-layer"
            x-bind:class="views[`quran-app-tilawa`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Tilawa background"
                :imagePath="asset('images/background/quran/tilawa-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/tilawa-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-hifth-layer"
            x-bind:class="views[`quran-app-hifth`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Hifth background"
                :imagePath="asset('images/background/quran/hifth-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/hifth-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-tadabbur-layer"
            x-bind:class="views[`quran-app-tadabbur`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Tadabbur background"
                :imagePath="asset('images/background/quran/tadabbur-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/tadabbur-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
            />
        </div>

        <!-- OPAQUE OVERLAY -->
        <div class="absolute inset-0 dark:bg-black/60"></div>
    </div>
</div>
