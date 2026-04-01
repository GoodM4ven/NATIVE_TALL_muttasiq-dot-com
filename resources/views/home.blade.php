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
                isVisible: false,
                isPreparing: false,
                isFinishing: false,
                errorMessage: null,
                progressPercent: 0,
                displayProgressPercent: 0,
                statusMessage: null,
                closeTimeoutId: null,
                progressAnimationFrameId: null,
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
        
                if (this.quranBootstrap.isPreparing || this.quranBootstrap.isFinishing) {
                    return;
                }
        
                window.dispatchEvent(new CustomEvent('quran-bootstrap-request', {
                    detail: {
                        openGateOnSuccess: true,
                    },
                }));
            },
            normalizeQuranBootstrapProgressPercent(value) {
                const rawPercent = Number(value ?? NaN);
        
                if (!Number.isFinite(rawPercent)) {
                    return null;
                }
        
                return Math.max(0, Math.min(100, Math.trunc(rawPercent)));
            },
            clearQuranBootstrapCloseTimeout() {
                if (this.quranBootstrap.closeTimeoutId === null) {
                    return;
                }
        
                window.clearTimeout(this.quranBootstrap.closeTimeoutId);
                this.quranBootstrap.closeTimeoutId = null;
            },
            stopQuranBootstrapProgressAnimation() {
                if (this.quranBootstrap.progressAnimationFrameId === null) {
                    return;
                }
        
                window.cancelAnimationFrame(this.quranBootstrap.progressAnimationFrameId);
                this.quranBootstrap.progressAnimationFrameId = null;
            },
            animateQuranBootstrapProgress() {
                const targetPercent = this.normalizeQuranBootstrapProgressPercent(
                    this.quranBootstrap.progressPercent,
                );
        
                if (targetPercent === null) {
                    this.quranBootstrap.progressAnimationFrameId = null;
                    return;
                }
        
                const currentPercent = Number(this.quranBootstrap.displayProgressPercent ?? 0);
                const delta = targetPercent - currentPercent;
        
                if (Math.abs(delta) <= 0.2) {
                    this.quranBootstrap.displayProgressPercent = targetPercent;
                    this.quranBootstrap.progressAnimationFrameId = null;
                    return;
                }
        
                const step = Math.max(0.5, Math.abs(delta) * 0.18);
                this.quranBootstrap.displayProgressPercent =
                    currentPercent + Math.sign(delta) * step;
        
                this.quranBootstrap.progressAnimationFrameId = window.requestAnimationFrame(() => {
                    this.animateQuranBootstrapProgress();
                });
            },
            setQuranBootstrapProgress(value, { allowDecrease = false } = {}) {
                const normalizedPercent = this.normalizeQuranBootstrapProgressPercent(value);
        
                if (normalizedPercent === null) {
                    return;
                }
        
                const currentTarget = this.normalizeQuranBootstrapProgressPercent(
                    this.quranBootstrap.progressPercent,
                );
                this.quranBootstrap.progressPercent =
                    allowDecrease || currentTarget === null ?
                    normalizedPercent :
                    Math.max(currentTarget, normalizedPercent);
        
                if (this.quranBootstrap.progressAnimationFrameId === null) {
                    this.quranBootstrap.progressAnimationFrameId = window.requestAnimationFrame(
                        () => {
                            this.animateQuranBootstrapProgress();
                        },
                    );
                }
            },
            handleQuranBootstrapStarted() {
                this.clearQuranBootstrapCloseTimeout();
                this.stopQuranBootstrapProgressAnimation();
                this.quranBootstrap.isVisible = true;
                this.quranBootstrap.isPreparing = true;
                this.quranBootstrap.isFinishing = false;
                this.quranBootstrap.errorMessage = null;
                this.quranBootstrap.progressPercent = 0;
                this.quranBootstrap.displayProgressPercent = 0;
                this.quranBootstrap.statusMessage = String(
                    @js(arabic_text('يجري تنزيل بيانات القرآن لأول مرة...')),
                );
            },
            handleQuranBootstrapProgress(detail = {}) {
                if (!this.quranBootstrap.isVisible || this.quranBootstrap.errorMessage !== null) {
                    return;
                }
        
                this.setQuranBootstrapProgress(detail?.progressPercent);
        
                const statusMessage = String(detail?.message ?? '').trim();
        
                if (statusMessage !== '') {
                    this.quranBootstrap.statusMessage = statusMessage;
                }
            },
            handleQuranBootstrapFinished(detail = {}) {
                this.clearQuranBootstrapCloseTimeout();
                this.setQuranBootstrapProgress(100, { allowDecrease: true });
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.isFinishing = true;
                this.quranBootstrap.errorMessage = null;
                const shouldOpenGate = detail?.openGateOnSuccess !== false;
        
                const holdAtHundredMs = 100;
                const closeAnimationDurationMs = 220;
        
                this.quranBootstrap.closeTimeoutId = window.setTimeout(() => {
                    this.stopQuranBootstrapProgressAnimation();
                    this.quranBootstrap.isVisible = false;
                    this.quranBootstrap.closeTimeoutId = null;
        
                    window.setTimeout(() => {
                        this.dismissQuranBootstrapState();
        
                        if (!shouldOpenGate) {
                            return;
                        }
        
                        this.$viewNav('quran-app-gate');
                    }, closeAnimationDurationMs);
                }, holdAtHundredMs);
            },
            handleQuranBootstrapFailed(detail = {}) {
                this.clearQuranBootstrapCloseTimeout();
                this.stopQuranBootstrapProgressAnimation();
                this.quranBootstrap.isVisible = true;
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.isFinishing = false;
                this.quranBootstrap.errorMessage =
                    String(detail?.message ?? @js(arabic_text('تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.')));
            },
            dismissQuranBootstrapState() {
                this.clearQuranBootstrapCloseTimeout();
                this.stopQuranBootstrapProgressAnimation();
                this.quranBootstrap.isVisible = false;
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.isFinishing = false;
                this.quranBootstrap.errorMessage = null;
                this.quranBootstrap.progressPercent = 0;
                this.quranBootstrap.displayProgressPercent = 0;
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
            x-show="quranBootstrap.isVisible"
            x-transition.opacity.duration.220ms
        >
            <div
                class="absolute inset-0 bg-slate-950/35 backdrop-blur-[2px]"
                x-transition.opacity.duration.220ms
                x-on:click="if (!quranBootstrap.isPreparing && !quranBootstrap.isFinishing) { dismissQuranBootstrapState() }"
            ></div>

            <section
                class="border-primary-300/35 bg-white/92 shadow-slate-950/18 dark:bg-slate-950/88 relative w-[min(92vw,24rem)] rounded-[1.8rem] border px-6 py-5 text-center shadow-2xl transition-[transform,opacity]"
                x-transition:enter="transition-[opacity,transform] duration-220 ease-out"
                x-transition:enter-start="opacity-0 scale-[0.97]"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition-[opacity,transform] duration-220 ease-in"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.97]"
            >
                <div
                    class="space-y-4"
                    x-show="!quranBootstrap.errorMessage"
                >
                    <div
                        class="border-3 border-primary-200 border-t-primary-600 mx-auto h-10 w-10 animate-spin rounded-full">
                    </div>
                    <h2 class="text-primary-950 dark:text-primary-50 text-base font-semibold">
                        {{ arabic_text('جار تجهيز بيانات القرآن') }}</h2>
                    <p class="text-primary-900/78 dark:text-primary-100/82 text-sm leading-7">
                        {{ arabic_text('يتم الآن تنزيل بيانات المصحف لأول مرة على هذا الجهاز...') }}
                    </p>
                    <div class="space-y-2">
                        <div class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-700/60">
                            <div
                                class="from-primary-500 to-primary-700 h-full rounded-full bg-gradient-to-r transition-[width] duration-150"
                                x-bind:style="`width: ${Math.max(0, Math.min(100, Number(quranBootstrap.displayProgressPercent ?? 0)))}%`"
                            ></div>
                        </div>
                        <p class="text-primary-900/70 dark:text-primary-100/70 text-xs font-semibold">
                            <span
                                x-text="`${Math.max(0, Math.min(100, Math.round(Number(quranBootstrap.displayProgressPercent ?? 0))))}%`"
                            ></span>
                        </p>
                    </div>
                </div>

                <div
                    class="space-y-4"
                    x-show="Boolean(quranBootstrap.errorMessage)"
                >
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
