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
            isNativeRuntime: @js(is_platform('native')),
            activeView: $persist('main-menu').as('app-active-view'),
            actionStatePulseToken: 0,
            quranBootstrap: {
                isPreparing: false,
                errorMessage: null,
                progressPercent: null,
                statusMessage: null,
            },
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
            openQuranEntry() {
                if (!this.isNativeRuntime) {
                    this.$viewNav('quran-app-gate');
                    return;
                }
        
                if (this.quranBootstrap.isPreparing) {
                    return;
                }
        
                window.dispatchEvent(new CustomEvent('quran-bootstrap-request', {
                    detail: {
                        openGateOnSuccess: true,
                    },
                }));
            },
            handleQuranBootstrapStarted() {
                this.quranBootstrap.isPreparing = true;
                this.quranBootstrap.errorMessage = null;
                this.quranBootstrap.progressPercent = 0;
                this.quranBootstrap.statusMessage = String(
                    @js(arabic_text('يجري تنزيل بيانات القرآن لأول مرة...')),
                );
            },
            handleQuranBootstrapProgress(detail = {}) {
                if (!this.quranBootstrap.isPreparing) {
                    return;
                }
        
                const rawProgressPercent = Number(detail?.progressPercent ?? NaN);
        
                if (Number.isFinite(rawProgressPercent)) {
                    this.quranBootstrap.progressPercent = Math.max(0, Math.min(100, Math.trunc(rawProgressPercent)));
                }
        
                const statusMessage = String(detail?.message ?? '').trim();
        
                if (statusMessage !== '') {
                    this.quranBootstrap.statusMessage = statusMessage;
                }
            },
            handleQuranBootstrapFinished(detail = {}) {
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.errorMessage = null;
                this.quranBootstrap.progressPercent = null;
                this.quranBootstrap.statusMessage = null;
        
                if (detail?.openGateOnSuccess === false) {
                    return;
                }
        
                this.$viewNav('quran-app-gate');
            },
            handleQuranBootstrapFailed(detail = {}) {
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.errorMessage =
                    String(detail?.message ?? @js(arabic_text('تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.')));
                this.quranBootstrap.progressPercent = null;
                this.quranBootstrap.statusMessage = null;
            },
            dismissQuranBootstrapState() {
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.errorMessage = null;
                this.quranBootstrap.progressPercent = null;
                this.quranBootstrap.statusMessage = null;
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
        x-on:quran-bootstrap-started.window="handleQuranBootstrapStarted()"
        x-on:quran-bootstrap-progress.window="handleQuranBootstrapProgress($event.detail ?? {})"
        x-on:quran-bootstrap-finished.window="handleQuranBootstrapFinished($event.detail ?? {})"
        x-on:quran-bootstrap-failed.window="handleQuranBootstrapFailed($event.detail ?? {})"
    >
        @php
            $quranReaderViewsCondition =
                'views[`quran-app-tilawa`].isOpen || views[`quran-app-hifth`].isOpen || views[`quran-app-tadabbur`].isOpen';
            $returnButtonShowCondition = is_platform('mobile')
                ? $quranReaderViewsCondition
                : 'views[`athkar-app-gate`].isReaderVisible || ' . $quranReaderViewsCondition;
            $returnButtonClickCallback = is_platform('mobile')
                ? 'if (' .
                    $quranReaderViewsCondition .
                    ') { window.dispatchEvent(new CustomEvent(`quran-reader-go-gate`)); }'
                : 'if (views[`athkar-app-gate`].isReaderVisible) { $dispatch(`close-athkar-mode`); return; } if (' .
                    $quranReaderViewsCondition .
                    ') { window.dispatchEvent(new CustomEvent(`quran-reader-go-gate`)); }';
            $homeButtonShowCondition = is_platform('mobile')
                ? '(views[`quran-app-gate`].isOpen || ' .
                    $quranReaderViewsCondition .
                    ') && !isControlPanelOpen && !isAthkarManagerOpen'
                : "!views['main-menu'].isOpen && !isControlPanelOpen && !isAthkarManagerOpen";
        @endphp
        <x-buttons-stack
            x-bind:data-respecting-stack="$store.bp.current === 'base'"
            @class(['mt-8' => is_platform('ios')])
        >
            <livewire:athkar-manager />
            <x-return-button
                :jsShowCondition="$returnButtonShowCondition"
                :jsClickCallback="$returnButtonClickCallback"
            />
            <x-partials.home-button :jsShowCondition="$homeButtonShowCondition" />
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

        <div
            class="z-60 fixed inset-0 grid place-items-center px-5"
            x-cloak
            x-show="quranBootstrap.isPreparing || quranBootstrap.errorMessage"
            x-transition.opacity
        >
            <div
                class="absolute inset-0 bg-slate-950/35 backdrop-blur-[2px]"
                x-on:click="if (!quranBootstrap.isPreparing) { dismissQuranBootstrapState() }"
            ></div>

            <section
                class="border-primary-300/35 bg-white/92 shadow-slate-950/18 dark:bg-slate-950/88 relative w-[min(92vw,24rem)] rounded-[1.8rem] border px-6 py-5 text-center shadow-2xl"
            >
                <template x-if="quranBootstrap.isPreparing">
                    <div class="space-y-4">
                        <div
                            class="border-3 border-primary-200 border-t-primary-600 mx-auto h-10 w-10 animate-spin rounded-full">
                        </div>
                        <h2 class="text-primary-950 dark:text-primary-50 text-base font-semibold">
                            {{ arabic_text('جار تجهيز بيانات القرآن') }}</h2>
                        <p
                            class="text-primary-900/78 dark:text-primary-100/82 text-sm leading-7"
                            x-text="quranBootstrap.statusMessage ?? @js(arabic_text('يتم الآن تنزيل بيانات المصحف لأول مرة على هذا الجهاز...'))"
                        ></p>
                        <div class="space-y-2">
                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-700/60">
                                <div
                                    class="from-primary-500 to-primary-700 h-full rounded-full bg-gradient-to-r transition-all duration-300"
                                    x-bind:style="`width: ${Math.max(0, Math.min(100, Number(quranBootstrap.progressPercent ?? 0)))}%`"
                                ></div>
                            </div>
                            <p class="text-primary-900/70 dark:text-primary-100/70 text-xs font-semibold">
                                <span
                                    x-text="`${Math.max(0, Math.min(100, Number(quranBootstrap.progressPercent ?? 0)))}%`"
                                ></span>
                            </p>
                        </div>
                    </div>
                </template>

                <template x-if="!quranBootstrap.isPreparing && quranBootstrap.errorMessage">
                    <div class="space-y-4">
                        <h2 class="text-danger-700 dark:text-danger-300 text-base font-semibold">
                            {{ arabic_text('تعذر تجهيز بيانات القرآن') }}</h2>
                        <p
                            class="text-sm leading-7 text-slate-700 dark:text-slate-200"
                            x-text="quranBootstrap.errorMessage"
                        ></p>
                        <div class="flex items-center justify-center gap-3">
                            <button
                                class="bg-primary-600 hover:bg-primary-700 rounded-xl px-4 py-2 text-sm font-semibold text-white transition"
                                type="button"
                                x-on:click="dismissQuranBootstrapState(); openQuranEntry();"
                            >
                                {{ arabic_text('إعادة المحاولة') }}
                            </button>
                            <button
                                class="rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 dark:border-slate-700 dark:text-slate-200 dark:hover:bg-slate-800"
                                type="button"
                                x-on:click="dismissQuranBootstrapState()"
                            >
                                {{ arabic_text('إغلاق') }}
                            </button>
                        </div>
                    </div>
                </template>
            </section>
        </div>

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
