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
        x-bind:class="!$store.colorScheme.isDarkModeOn && 'opacity-30!'"
    >
        <!-- LIGHT MODE -->
        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="main-menu-bg-light-layer"
            x-bind:class="views[`main-menu`].isOpen && !$store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !
                views[`quran-app-hifth`].isOpen &&
                !views[`quran-app-tadabbur`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Morning background"
                :imagePath="asset('images/background/main-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-morning-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
            />
        </div>

        <!-- DARK MODE -->
        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="main-menu-bg-dark-layer"
            x-bind:class="views[`main-menu`].isOpen && $store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[
                    `quran-app-hifth`].isOpen &&
                !views[`quran-app-tadabbur`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Night background"
                :imagePath="asset('images/background/main-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-night-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
            />
        </div>

        <!-- ATHKAR READER LIGHT MODE -->
        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="athkar-reader-bg-light-layer"
            x-bind:class="views[`athkar-app-gate`].isReaderVisible && !$store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`]
                .isOpen && !views[`quran-app-hifth`].isOpen &&
                !views[`quran-app-tadabbur`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Athkar morning background"
                :imagePath="asset('images/background/athkar-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/athkar-morning-blurred-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <!-- ATHKAR READER DARK MODE -->
        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="athkar-reader-bg-dark-layer"
            x-bind:class="views[`athkar-app-gate`].isReaderVisible && $store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`]
                .isOpen && !views[`quran-app-hifth`].isOpen &&
                !views[`quran-app-tadabbur`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="h-full w-full scale-110 object-cover"
                alt="Athkar night background"
                :imagePath="asset('images/background/athkar-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/athkar-night-blurred-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-tilawa-layer"
            x-bind:class="views[`quran-app-tilawa`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="duration-400 absolute inset-0 h-full w-full scale-110 object-cover transition-opacity"
                alt="Tilawa background"
                :imagePath="asset('images/background/quran/morning/tilawa-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/morning/tilawa-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[50%_62.5%]"
                x-bind:class="!$store.colorScheme.isDarkModeOn ? 'opacity-100!' : 'opacity-0'"
            />
            <x-goodmaven::blurred-image
                class="duration-400 absolute inset-0 h-full w-full scale-110 object-cover transition-opacity"
                alt="Tilawa background"
                :imagePath="asset('images/background/quran/night/tilawa-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/night/tilawa-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[50%_62.5%]"
                x-bind:class="$store.colorScheme.isDarkModeOn ? 'opacity-100!' : 'opacity-0'"
            />
        </div>

        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-hifth-layer"
            x-bind:class="views[`quran-app-hifth`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="duration-400 absolute inset-0 h-full w-full scale-110 object-cover transition-opacity"
                alt="Hifth background"
                :imagePath="asset('images/background/quran/morning/hifth-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/morning/hifth-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[0%_50%]"
                x-bind:class="!$store.colorScheme.isDarkModeOn ? 'opacity-100!' : 'opacity-0'"
            />
            <x-goodmaven::blurred-image
                class="duration-400 absolute inset-0 h-full w-full scale-110 object-cover transition-opacity"
                alt="Hifth background"
                :imagePath="asset('images/background/quran/night/hifth-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/night/hifth-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[0%_50%]"
                x-bind:class="$store.colorScheme.isDarkModeOn ? 'opacity-100!' : 'opacity-0'"
            />
        </div>

        <div
            class="absolute inset-0 opacity-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-tadabbur-layer"
            x-bind:class="views[`quran-app-tadabbur`].isOpen && 'opacity-100!'"
        >
            <x-goodmaven::blurred-image
                class="duration-400 absolute inset-0 h-full w-full scale-110 object-cover transition-opacity"
                alt="Tadabbur background"
                :imagePath="asset('images/background/quran/morning/tadabbur-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/morning/tadabbur-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[100%_50%]"
                x-bind:class="!$store.colorScheme.isDarkModeOn ? 'opacity-100!' : 'opacity-0'"
            />
            <x-goodmaven::blurred-image
                class="duration-400 absolute inset-0 h-full w-full scale-110 object-cover transition-opacity"
                alt="Tadabbur background"
                :imagePath="asset('images/background/quran/night/tadabbur-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/night/tadabbur-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[100%_50%]"
                x-bind:class="$store.colorScheme.isDarkModeOn ? 'opacity-100!' : 'opacity-0'"
            />
        </div>

        <!-- OPAQUE OVERLAY -->
        <div class="absolute inset-0 dark:bg-black/60"></div>
    </div>
</div>
