<x-app>
    <div
        class="flex h-full flex-1 flex-col"
        x-cloak
        x-transition.opacity
        x-show="isBodyVisible"
        x-init="lock = $livewireLock(null, 350, true)"
        x-data="{
            lock: null,
            isControlPanelOpen: false,
            isAthkarManagerOpen: false,
            activeView: $persist('main-menu').as('app-active-view'),
            actionStatePulseToken: 0,
            viewTree: {
                'main-menu': {
                    children: {
                        'quran-app-gate': {
                            children: {
                                'quran-app-tilawa': {},
                                'quran-app-tadabbur': {},
                                'quran-app-hifth': {},
                            },
                        },
                        'athkar-app-gate': {
                            children: {
                                'athkar-app-sabah': {},
                                'athkar-app-masaa': {},
                            },
                        },
                    },
                },
            },
            views: {
                'main-menu': {
                    title: @js(view_title(\App\Services\Support\Enums\ViewName::MainMenu)),
                    isOpen: true,
                },
                'athkar-app-gate': {
                    title: @js(view_title(\App\Services\Support\Enums\ViewName::AthkarAppGate)),
                    isOpen: false,
                    isReaderVisible: $persist(false).as('athkar-reader-visible'),
                },
                'athkar-app-sabah': {
                    title: @js(view_title(\App\Services\Support\Enums\ViewName::AthkarAppSabah)),
                    isOpen: false,
                },
                'athkar-app-masaa': {
                    title: @js(view_title(\App\Services\Support\Enums\ViewName::AthkarAppMasaa)),
                    isOpen: false,
                },
                'quran-app-gate': {
                    title: @js(view_title(\App\Services\Support\Enums\ViewName::QuranAppGate)),
                    isOpen: false,
                },
                'quran-app-tilawa': {
                    title: @js(view_title(\App\Services\Support\Enums\ViewName::QuranAppTilawa)),
                    isOpen: false,
                },
                'quran-app-hifth': {
                    title: @js(view_title(\App\Services\Support\Enums\ViewName::QuranAppHifth)),
                    isOpen: false,
                },
                'quran-app-tadabbur': {
                    title: @js(view_title(\App\Services\Support\Enums\ViewName::QuranAppTadabbur)),
                    isOpen: false,
                },
            },
            init() {
                this.applyViewState('main-menu', { persist: false });
            },
            runHashAction(callback) {
                if (window.__hashActionBypassLock) {
                    if (typeof callback === 'function') {
                        callback();
                    }
                    return;
                }
        
                if (this.lock?.run) {
                    this.lock.run(callback);
                    return;
                }
        
                if (typeof callback === 'function') {
                    callback();
                }
            },
            pulseActionState(options = {}) {
                if (this.isControlPanelOpen || this.isAthkarManagerOpen) {
                    return;
                }
        
                const layoutManager = this.$store?.layoutManager;
        
                if (!layoutManager || layoutManager.isActionOpen) {
                    return;
                }
        
                const requestedDuration = Number(options?.durationMs ?? 34);
                const durationMs = Number.isFinite(requestedDuration) ?
                    Math.max(0, Math.trunc(requestedDuration)) :
                    34;
                const token = this.actionStatePulseToken + 1;
        
                this.actionStatePulseToken = token;
                this.isControlPanelOpen = true;
                layoutManager.isActionOpen = true;
        
                window.setTimeout(() => {
                    if (this.actionStatePulseToken !== token) {
                        return;
                    }
        
                    this.isControlPanelOpen = false;
                    layoutManager.isActionOpen = false;
                }, durationMs);
            },
            applyViewState(nextView, { persist = true } = {}) {
                const view = this.views?.[nextView] ? nextView : 'main-menu';
        
                Object.keys(this.views).forEach((key) => {
                    this.views[key].isOpen = key === view;
                });
                if (persist) {
                    this.activeView = view;
                }
        
                if (this.views[view]) {
                    document.title = this.views[view].title;
                }
            },
        }"
        x-bind:data-view-tree="JSON.stringify(viewTree)"
        x-bind:data-hash-default="'main-menu'"
        x-bind:data-hash-restore="activeView"
        x-hash-actions="{
            '#main-menu': () => runHashAction(() => {
                $dispatch('switch-view', { to: 'main-menu' });
            }),
            '#toggle-color-scheme': () => runHashAction(() => {
                $store.colorScheme.toggle();
            }),
            '#control-panel': () => runHashAction(() => {
                $dispatch('open-control-panel-modal');
            }),
            '#athkar-app-gate': () => runHashAction(() => {
                $dispatch('switch-view', { to: 'athkar-app-gate' });
            }),
            '#athkar-app-sabah': () => runHashAction(() => {
                $dispatch('switch-view', { to: 'athkar-app-sabah' });
            }),
            '#athkar-app-masaa': () => runHashAction(() => {
                $dispatch('switch-view', { to: 'athkar-app-masaa' });
            }),
            '#quran-app-gate': () => runHashAction(() => {
                $dispatch('switch-view', { to: 'quran-app-gate' });
            }),
            '#quran-app-tilawa': () => runHashAction(() => {
                $dispatch('switch-view', { to: 'quran-app-tilawa' });
            }),
            '#quran-app-hifth': () => runHashAction(() => {
                $dispatch('switch-view', { to: 'quran-app-hifth' });
            }),
            '#quran-app-tadabbur': () => runHashAction(() => {
                $dispatch('switch-view', { to: 'quran-app-tadabbur' });
            }),
        }"
        x-on:switch-view.window="applyViewState($event.detail?.to)"
        x-on:athkar-action-state-pulse.window="pulseActionState($event.detail ?? {})"
    >
        <x-buttons-stack
            x-bind:data-respecting-stack="$store.bp.current === 'base'"
            @class(['mt-8' => is_platform('ios')])
        >
            <livewire:athkar-manager />
            @if (!is_platform('mobile'))
                <x-return-button
                    :jsShowCondition="'views[`athkar-app-gate`].isReaderVisible || views[`quran-app-tilawa`].isOpen || views[`quran-app-hifth`].isOpen || views[`quran-app-tadabbur`].isOpen'"
                    :jsClickCallback="'if (views[`athkar-app-gate`].isReaderVisible) { $dispatch(`close-athkar-mode`); return; } if (views[`quran-app-tilawa`].isOpen || views[`quran-app-hifth`].isOpen || views[`quran-app-tadabbur`].isOpen) { $viewNav(`quran-app-gate`); }'"
                />
                <x-partials.home-button />
            @endif
            <livewire:color-scheme-switcher />
            <livewire:control-panel />
        </x-buttons-stack>

        <x-partials.colorful-background />

        <main @class([
            'fixed inset-0 grid place-items-center sm:mt-0 dark:text-white',
            'mt-22' => is_platform('ios'),
            'mt-16' => !is_platform('ios'),
        ])>
            <x-partials.main-menu />
            <x-partials.athkar-app.index
                :athkar="$athkar"
                :athkar-settings="$athkarSettings"
                :athkar-main-text-size-limits="$athkarMainTextSizeLimits"
            />
            <x-partials.quran-app.index />
        </main>

        <x-partials.copyright-and-version />

        @if (is_platform('mobile'))
            <livewire:startup-sync defer />
        @endif

        @if ((bool) config('app.custom.security.web_home_metrics.enabled', false) && is_platform('web'))
            <livewire:web-home-view-tracker />
        @endif

        <livewire:js-error-reporter />
    </div>
</x-app>
