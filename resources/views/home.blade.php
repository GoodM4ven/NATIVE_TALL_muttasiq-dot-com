<x-app>
    <div
        class="flex h-full flex-1 flex-col"
        x-cloak
        x-transition.opacity
        x-show="isBodyVisible"
        x-data="{
            lock: null,
            isSettingsOpen: false,
            isAthkarManagerOpen: false,
            activeView: $persist('main-menu').as('app-active-view'),
            viewTree: {
                'main-menu': {
                    children: {
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
                    title: @js(view_title(\App\Services\Enums\ViewName::MainMenu)),
                    isOpen: true,
                },
                'athkar-app-gate': {
                    title: @js(view_title(\App\Services\Enums\ViewName::AthkarAppGate)),
                    isOpen: false,
                    isReaderVisible: $persist(false).as('athkar-reader-visible'),
                },
                'athkar-app-sabah': {
                    title: @js(view_title(\App\Services\Enums\ViewName::AthkarAppSabah)),
                    isOpen: false,
                },
                'athkar-app-masaa': {
                    title: @js(view_title(\App\Services\Enums\ViewName::AthkarAppMasaa)),
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
            '#settings': () => runHashAction(() => {
                $dispatch('open-settings-modal');
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
        }"
        x-on:switch-view.window="applyViewState($event.detail?.to)"
    >
        <x-buttons-stack x-bind:data-respecting-stack="$store.bp.current === 'base'">
            <livewire:athkar-manager />
            @if (!is_platform('mobile'))
                <x-return-button
                    :jsShowCondition="'views[`athkar-app-gate`].isReaderVisible'"
                    :jsClickCallback="'if (views[`athkar-app-gate`].isReaderVisible) $dispatch(`close-athkar-mode`)'"
                />
                @include('partials.home-button')
            @endif
            <livewire:color-scheme-switcher />
            <livewire:settings />
        </x-buttons-stack>

        @include('partials.colorful-background')

        <main class="fixed inset-0 mt-16 grid place-items-center sm:mt-0 dark:text-white">
            @include('partials.main-menu')
            @include('partials.athkar-app.index')
        </main>
    </div>
</x-app>
