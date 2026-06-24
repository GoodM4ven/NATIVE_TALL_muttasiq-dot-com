<!-- Background -->
<div
    class="pointer-events-none fixed inset-0 z-0 overflow-hidden"
    x-cloak
    x-data="{
        athkarGatePreviewSide: null,
        isAthkarGatePreviewVisualEnhancementsEnabled: true,
        athkarGatePreviewEventName: 'athkar-gate-background-preview',
        athkarReaderMode: null,
        athkarReaderTransitionDirection: 'enter',
        isAthkarGateBackgroundActive: false,
        normalizeBooleanSettingValue(value, fallback = true) {
            if (typeof value === 'boolean') {
                return value;
            }
    
            if (value === 1 || value === '1') {
                return true;
            }
    
            if (value === 0 || value === '0') {
                return false;
            }
    
            if (value === undefined || value === null || value === '') {
                return Boolean(fallback);
            }
    
            const normalizedValue = String(value).trim().toLowerCase();
    
            if (normalizedValue === 'true' || normalizedValue === 'yes' || normalizedValue === 'on') {
                return true;
            }
    
            if (normalizedValue === 'false' || normalizedValue === 'no' || normalizedValue === 'off') {
                return false;
            }
    
            return Boolean(fallback);
        },
        syncAthkarGatePreviewVisualEnhancements(settingValue = undefined) {
            const valueFromControlPanel = settingValue;
    
            if (valueFromControlPanel !== undefined) {
                this.isAthkarGatePreviewVisualEnhancementsEnabled = this.normalizeBooleanSettingValue(
                    valueFromControlPanel,
                    true,
                );
                return;
            }
    
            const storedSettings = window.getAthkarSettingsFromStorage?.() ?? {};
            this.isAthkarGatePreviewVisualEnhancementsEnabled = this.normalizeBooleanSettingValue(
                storedSettings?.enable_visual_enhancements,
                true,
            );
        },
        handleAthkarGateBackgroundPreview(event) {
            const detail = event?.detail ?? {};
            const side = detail?.side;
            const isValidSide = side === 'morning' || side === 'night';
    
            this.syncAthkarGatePreviewVisualEnhancements(
                detail?.isVisualEnhancementsEnabled,
            );
            // ponytail: the side preview/swap is no longer gated on visual-enhancements. When VE is OFF (at any breakpoint) we deliberately want the lightweight base-style background swap instead of the costly sm+ panes/spill, so the swap must drive whenever a side is active.
            this.athkarGatePreviewSide = isValidSide ? side : null;
        },
        athkarGateMorningOpacity() {
            if (!this.athkarGatePreviewSide) {
                return this.$store.colorScheme.isDarkModeOn ? 0 : 1;
            }
    
            return this.athkarGatePreviewSide === 'morning' ? 1 : 0;
        },
        athkarGateNightOpacity() {
            if (!this.athkarGatePreviewSide) {
                return this.$store.colorScheme.isDarkModeOn ? 1 : 0;
            }
    
            return this.athkarGatePreviewSide === 'night' ? 1 : 0;
        },
        syncAthkarGateBackgroundStateFromViews() {
            const gateView = this.views?.['athkar-app-gate'];
            // ponytail: the athkar gate now uses the lightweight background swap at every breakpoint and for both VE states (the costly sm+ panes/spill were dropped), so the swap is active whenever the gate is open.
            this.isAthkarGateBackgroundActive = Boolean(
                gateView?.isOpen && !gateView?.isReaderVisible,
            );
        },
        athkarGateLayerStyle(mode) {
            const baseOpacity = mode === 'morning' ? this.athkarGateMorningOpacity() : this.athkarGateNightOpacity();
            const opacity = this.isAthkarGateBackgroundActive ? baseOpacity : 0;
    
            return `opacity:${opacity};transition:opacity 520ms cubic-bezier(0.22,1,0.36,1);`;
        },
        syncAthkarReaderModeFromViews() {
            if (this.views?.['athkar-app-sabah']?.isOpen) {
                this.athkarReaderMode = 'sabah';
                return;
            }
    
            if (this.views?.['athkar-app-masaa']?.isOpen) {
                this.athkarReaderMode = 'masaa';
            }
        },
        resolveAthkarReaderMode() {
            this.syncAthkarReaderModeFromViews();
            return this.athkarReaderMode;
        },
        athkarReaderLayerStyle(mode) {
            const isReaderVisible = Boolean(this.views?.['athkar-app-gate']?.isReaderVisible);
            const activeMode = this.resolveAthkarReaderMode();
            const isActive = isReaderVisible && activeMode === mode;
            const isLeaveDirection = this.athkarReaderTransitionDirection === 'leave';
            const hiddenTranslate =
                mode === 'sabah' ?
                (isLeaveDirection ? '-4rem' : '4rem') :
                (isLeaveDirection ? '4rem' : '-4rem');
            const opacity = isActive ? 1 : 0;
            const translateX = isActive ? '0rem' : hiddenTranslate;
    
            return `opacity:${opacity};transform:translateX(${translateX});transition:opacity 560ms cubic-bezier(0.22,1,0.36,1),transform 560ms cubic-bezier(0.22,1,0.36,1);`;
        },
        init() {
            this.syncAthkarGatePreviewVisualEnhancements();
            this.syncAthkarReaderModeFromViews();
            this.syncAthkarGateBackgroundStateFromViews();
            window.addEventListener(
                this.athkarGatePreviewEventName,
                (event) => this.handleAthkarGateBackgroundPreview(event),
            );
            window.addEventListener('switch-view', (event) => {
                const nextView = String(event?.detail?.to ?? '');
    
                if (nextView === 'athkar-app-sabah') {
                    this.athkarReaderMode = 'sabah';
                    this.athkarReaderTransitionDirection = 'enter';
                    this.isAthkarGateBackgroundActive = false;
                    return;
                }
    
                if (nextView === 'athkar-app-masaa') {
                    this.athkarReaderMode = 'masaa';
                    this.athkarReaderTransitionDirection = 'enter';
                    this.isAthkarGateBackgroundActive = false;
                    return;
                }
    
                if (nextView === 'athkar-app-gate') {
                    this.athkarReaderTransitionDirection = 'leave';
                    this.isAthkarGateBackgroundActive = true;
                    return;
                }
    
                this.isAthkarGateBackgroundActive = false;
            });
            window.addEventListener('control-panel-updated', (event) => {
                if (event?.detail?.maintenancePulse) {
                    return;
                }
    
                this.syncAthkarGatePreviewVisualEnhancements(
                    event?.detail?.controlPanel?.enable_visual_enhancements,
                );
    
                // ponytail: keep the active-state in sync when VE is toggled while the gate is open, so sm+ flips between the rich panes (VE on) and the lightweight background swap (VE off) immediately.
                this.syncAthkarGateBackgroundStateFromViews();
            });
        },
    }"
    x-transition:enter="transition ease-out duration-220 delay-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-300"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    x-show="views[`main-menu`].isOpen || views[`athkar-app-gate`].isReaderVisible || views[`athkar-app-gate`].isOpen || views[`quran-app-tilawa`].isOpen || views[`quran-app-hifth`].isOpen || views[`quran-app-tadabbur`].isOpen"
>
    <div
        class="duration-400 absolute inset-0 transition-opacity will-change-[opacity] [--bg-athkar-gate-masaa-opacity:1] [--bg-athkar-gate-sabah-opacity:0.7] [--bg-athkar-masaa-opacity:1] [--bg-athkar-sabah-opacity:0.3] [--bg-main-dark-opacity:1] [--bg-main-light-opacity:0.6] [--bg-quran-hifth-dark-opacity:1] [--bg-quran-hifth-light-opacity:1] [--bg-quran-tadabbur-dark-opacity:1] [--bg-quran-tadabbur-light-opacity:1] [--bg-quran-tilawa-dark-opacity:1] [--bg-quran-tilawa-light-opacity:1]"
        x-bind:class="{
            'opacity-10!': ($store.colorScheme.isDarkModeOn && !views[`athkar-app-gate`].isOpen),
            'opacity-40!': ($store.colorScheme.isDarkModeOn && views[`athkar-app-gate`].isOpen),
            'opacity-30!': !$store.colorScheme.isDarkModeOn,
        }"
    >
        <div
            class="absolute inset-0 transition-opacity delay-300 duration-500 will-change-[opacity]"
            data-testid="main-menu-bg-light-layer"
            x-cloak
            x-show="views[`main-menu`].isOpen && !$store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen && !views[`quran-app-tadabbur`].isOpen"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-main-light-opacity) h-full w-full scale-110 object-cover"
                alt="Morning background"
                :imagePath="asset('images/background/main-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-morning-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#main-menu'"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity delay-300 duration-500 will-change-[opacity]"
            data-testid="main-menu-bg-dark-layer"
            x-cloak
            x-show="views[`main-menu`].isOpen && $store.colorScheme.isDarkModeOn && !views[`quran-app-tilawa`].isOpen && !views[`quran-app-hifth`].isOpen && !views[`quran-app-tadabbur`].isOpen"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-main-dark-opacity) h-full w-full scale-110 object-cover"
                alt="Night background"
                :imagePath="asset('images/background/main-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-night-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#main-menu'"
            />
        </div>

        <div
            class="absolute inset-0 will-change-[opacity,transform]"
            data-testid="athkar-reader-bg-sabah-layer"
            x-cloak
            x-show="true"
            x-bind:style="athkarReaderLayerStyle('sabah')"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-athkar-sabah-opacity) h-full w-full scale-110 object-cover"
                alt="Athkar morning background"
                :imagePath="asset('images/background/athkar-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/athkar-morning-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#athkar-app-sabah'"
            />
        </div>

        <div
            class="absolute inset-0 will-change-[opacity,transform]"
            data-testid="athkar-reader-bg-masaa-layer"
            x-cloak
            x-show="true"
            x-bind:style="athkarReaderLayerStyle('masaa')"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-athkar-masaa-opacity) h-full w-full scale-110 object-cover"
                alt="Athkar night background"
                :imagePath="asset('images/background/athkar-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/athkar-night-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#athkar-app-masaa'"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="athkar-gate-bg-light-layer"
            x-cloak
            x-show="true"
            x-bind:style="athkarGateLayerStyle('morning')"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-athkar-gate-sabah-opacity) h-full w-full scale-110 object-cover"
                alt="Athkar gate morning background"
                :imagePath="asset('images/background/main-morning-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-morning-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#athkar-app-gate'"
            />
        </div>

        <div
            class="absolute inset-0 transition-opacity duration-500 will-change-[opacity]"
            data-testid="athkar-gate-bg-dark-layer"
            x-cloak
            x-show="true"
            x-bind:style="athkarGateLayerStyle('night')"
        >
            <x-goodmaven::blurred-image
                class="opacity-(--bg-athkar-gate-masaa-opacity) h-full w-full scale-110 object-cover"
                alt="Athkar gate night background"
                :imagePath="asset('images/background/main-night-blurred.webp')"
                :thumbnailImagePath="asset('images/background/main-night-blurred-blur-thumbnail.webp')"
                isDisplayEnforcedJs="() => window.location.hash === '#athkar-app-gate'"
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
                isDisplayEnforcedJs="() => window.location.hash === '#quran-app-tilawa'"
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
                isDisplayEnforcedJs="() => window.location.hash === '#quran-app-tilawa'"
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
                isDisplayEnforcedJs="() => window.location.hash === '#quran-app-hifth'"
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
                isDisplayEnforcedJs="() => window.location.hash === '#quran-app-hifth'"
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
                isDisplayEnforcedJs="() => window.location.hash === '#quran-app-tadabbur'"
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
                isDisplayEnforcedJs="() => window.location.hash === '#quran-app-tadabbur'"
                imageClasses="object-[100%_50%]"
            />
        </div>

        <!-- OPAQUE OVERLAY -->
        <div
            class="absolute inset-0 dark:bg-black/60"
            x-bind:class="(window.location.hash === '#athkar-app-sabah') && 'dark:opacity-0'"
        ></div>
    </div>
</div>
