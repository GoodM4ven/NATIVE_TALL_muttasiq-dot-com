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
    x-show="views[`main-menu`].isOpen || views[`athkar-app-gate`].isReaderVisible || (views[`athkar-app-gate`].isOpen && ($store.bp?.is?.('base') || document.documentElement.classList.contains('native-platform'))) || views[`quran-app-tilawa`].isOpen || views[`quran-app-hifth`].isOpen || views[`quran-app-tadabbur`].isOpen"
>
    <div
        class="duration-400 absolute inset-0 opacity-10 transition-opacity will-change-[opacity] [--bg-athkar-masaa-opacity:1] [--bg-athkar-sabah-opacity:1] [--bg-main-dark-opacity:1] [--bg-main-light-opacity:1] [--bg-quran-hifth-dark-opacity:1] [--bg-quran-hifth-light-opacity:1] [--bg-quran-tadabbur-dark-opacity:1] [--bg-quran-tadabbur-light-opacity:1] [--bg-quran-tilawa-dark-opacity:1] [--bg-quran-tilawa-light-opacity:1]"
        x-bind:class="!$store.colorScheme.isDarkModeOn ? 'opacity-30!' : ''"
    >
        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="main-menu-bg-light-layer"
            x-cloak
            x-show="views[`main-menu`].isOpen && !$store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen && !views[`quran-app-tadabbur`].isOpen"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-main-light-opacity) h-full w-full scale-110 object-cover"
                alt="Morning background"
                :imagePath="asset('images/background/main-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-morning-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="main-menu-bg-dark-layer"
            x-cloak
            x-show="views[`main-menu`].isOpen && $store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen && !views[`quran-app-tadabbur`].isOpen"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-main-dark-opacity) h-full w-full scale-110 object-cover"
                alt="Night background"
                :imagePath="asset('images/background/main-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-night-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="athkar-reader-bg-light-layer"
            x-cloak
            x-show="views[`athkar-app-gate`].isReaderVisible && !$store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen && !views[`quran-app-tadabbur`].isOpen"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-athkar-sabah-opacity) h-full w-full scale-110 object-cover"
                alt="Athkar morning background"
                :imagePath="asset('images/background/athkar-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/athkar-morning-blurred-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="athkar-reader-bg-dark-layer"
            x-cloak
            x-show="views[`athkar-app-gate`].isReaderVisible && $store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen && !views[`quran-app-tadabbur`].isOpen"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-athkar-masaa-opacity) h-full w-full scale-110 object-cover"
                alt="Athkar night background"
                :imagePath="asset('images/background/athkar-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/athkar-night-blurred-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="athkar-gate-bg-light-layer"
            x-cloak
            x-show="views[`athkar-app-gate`].isOpen && !views[`athkar-app-gate`].isReaderVisible && !$store.colorScheme.isDarkModeOn && ($store.bp?.is?.('base') || document.documentElement.classList.contains('native-platform')) && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen && !views[`quran-app-tadabbur`].isOpen"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-athkar-sabah-opacity) h-full w-full scale-110 object-cover"
                alt="Athkar gate morning background"
                :imagePath="asset('images/background/athkar-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/athkar-morning-blurred-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="athkar-gate-bg-dark-layer"
            x-cloak
            x-show="views[`athkar-app-gate`].isOpen && !views[`athkar-app-gate`].isReaderVisible && $store.colorScheme.isDarkModeOn && ($store.bp?.is?.('base') || document.documentElement.classList.contains('native-platform')) && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen && !views[`quran-app-tadabbur`].isOpen"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-athkar-masaa-opacity) h-full w-full scale-110 object-cover"
                alt="Athkar gate night background"
                :imagePath="asset('images/background/athkar-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/athkar-night-blurred-blur-thumbnail.png')"
                :isDisplayEnforced="true"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-tilawa-light-layer"
            x-cloak
            x-show="views[`quran-app-tilawa`].isOpen && !$store.colorScheme.isDarkModeOn"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-quran-tilawa-light-opacity) absolute inset-0 h-full w-full scale-110 object-cover"
                alt="Tilawa background"
                :imagePath="asset('images/background/quran/morning/tilawa-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/morning/tilawa-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[50%_62.5%]"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-tilawa-dark-layer"
            x-cloak
            x-show="views[`quran-app-tilawa`].isOpen && $store.colorScheme.isDarkModeOn"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-quran-tilawa-dark-opacity) absolute inset-0 h-full w-full scale-110 object-cover"
                alt="Tilawa background"
                :imagePath="asset('images/background/quran/night/tilawa-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/night/tilawa-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[50%_62.5%]"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-hifth-light-layer"
            x-cloak
            x-show="views[`quran-app-hifth`].isOpen && !$store.colorScheme.isDarkModeOn"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-quran-hifth-light-opacity) absolute inset-0 h-full w-full scale-110 object-cover"
                alt="Hifth background"
                :imagePath="asset('images/background/quran/morning/hifth-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/morning/hifth-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[0%_50%]"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-hifth-dark-layer"
            x-cloak
            x-show="views[`quran-app-hifth`].isOpen && $store.colorScheme.isDarkModeOn"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-quran-hifth-dark-opacity) absolute inset-0 h-full w-full scale-110 object-cover"
                alt="Hifth background"
                :imagePath="asset('images/background/quran/night/hifth-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/night/hifth-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[0%_50%]"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-tadabbur-light-layer"
            x-cloak
            x-show="views[`quran-app-tadabbur`].isOpen && !$store.colorScheme.isDarkModeOn"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-quran-tadabbur-light-opacity) absolute inset-0 h-full w-full scale-110 object-cover"
                alt="Tadabbur background"
                :imagePath="asset('images/background/quran/morning/tadabbur-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/morning/tadabbur-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[100%_50%]"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="quran-bg-tadabbur-dark-layer"
            x-cloak
            x-show="views[`quran-app-tadabbur`].isOpen && $store.colorScheme.isDarkModeOn"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-quran-tadabbur-dark-opacity) absolute inset-0 h-full w-full scale-110 object-cover"
                alt="Tadabbur background"
                :imagePath="asset('images/background/quran/night/tadabbur-blurred.webp')"
                :thumbnailImagePath="asset('images/background/quran/night/tadabbur-blurred-blur-thumbnail.webp')"
                :isDisplayEnforced="true"
                imageClasses="object-[100%_50%]"
            />
        </div>

        <!-- OPAQUE OVERLAY -->
        <div class="absolute inset-0 dark:bg-black/60"></div>
    </div>
</div>
