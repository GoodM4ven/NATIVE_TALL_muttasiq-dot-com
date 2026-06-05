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
            isQuranReaderCalibrating: false,
            isQuranReaderFontScaleOverlayOpen: false,
            isAthkarReaderFontScaleOverlayOpen: false,
            isNativeRuntime: @js(is_platform('native')),
            activeView: $persist('main-menu').as('app-active-view'),
            currentAppVersion: @js(\App\Models\Setting::appVersion()),
            actionStatePulseToken: 0,
            controlPanelGateReturnTimerId: null,
            quranBootstrap: {
                isVisible: false,
                isPreparing: false,
                isFinishing: false,
                requiresRestart: false,
                isRestarting: false,
                didStartDownloadFlow: false,
                errorMessage: null,
                progressPercent: 0,
                displayProgressPercent: 0,
                statusMessage: null,
                stage: 'preparing',
                closeTimeoutId: null,
                restartRevealTimeoutId: null,
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
            syncStartupVersionState() {
                const versionState = window.appVersionRouting?.syncStoredAppVersion(
                    this.currentAppVersion,
                );
        
                this.applyViewState('main-menu', {
                    persist: versionState?.shouldResetStartupView === true,
                });
        
                return versionState;
            },
            init() {
                this.syncStartupVersionState();
            },
            handleAppVersionMajorMinorReset() {
                this.applyViewState('main-menu', { persist: true });
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
            clearQuranBootstrapRestartRevealTimeout() {
                if (this.quranBootstrap.restartRevealTimeoutId === null) {
                    return;
                }
        
                window.clearTimeout(this.quranBootstrap.restartRevealTimeoutId);
                this.quranBootstrap.restartRevealTimeoutId = null;
            },
            stopQuranBootstrapProgressAnimation() {
                if (this.quranBootstrap.progressAnimationFrameId === null) {
                    return;
                }
        
                window.cancelAnimationFrame(this.quranBootstrap.progressAnimationFrameId);
                this.quranBootstrap.progressAnimationFrameId = null;
            },
            setQuranBootstrapStage(stage = 'preparing') {
                this.quranBootstrap.stage = String(stage ?? 'preparing').trim() || 'preparing';
            },
            isQuranBootstrapStage(stage) {
                return this.quranBootstrap.stage === stage;
            },
            quranBootstrapStageClasses(stage) {
                return this.isQuranBootstrapStage(stage) ?
                    'opacity-100 translate-y-0 scale-100 blur-0' :
                    'pointer-events-none opacity-0 translate-y-2 scale-[0.985] blur-[2px]';
            },
            quranBootstrapStageStyle(stage) {
                return this.isQuranBootstrapStage(stage) ? 'transition-delay: 70ms;' : 'transition-delay: 0ms;';
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
                this.clearQuranBootstrapRestartRevealTimeout();
                this.stopQuranBootstrapProgressAnimation();
                this.quranBootstrap.isVisible = true;
                this.quranBootstrap.isPreparing = true;
                this.quranBootstrap.isFinishing = false;
                this.quranBootstrap.requiresRestart = false;
                this.quranBootstrap.isRestarting = false;
                this.quranBootstrap.didStartDownloadFlow = true;
                this.quranBootstrap.errorMessage = null;
                this.quranBootstrap.progressPercent = 0;
                this.quranBootstrap.displayProgressPercent = 0;
                this.quranBootstrap.statusMessage = String(
                    @js(arabic_text('يجري تنزيل بيانات القرآن لأول مرة...')),
                );
                this.setQuranBootstrapStage('preparing');
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
                this.clearQuranBootstrapRestartRevealTimeout();
                this.setQuranBootstrapProgress(100, { allowDecrease: true });
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.isFinishing = false;
                this.quranBootstrap.errorMessage = null;
                const shouldOpenGate = detail?.openGateOnSuccess !== false;
                this.setQuranBootstrapStage('success');
        
                if (this.quranBootstrap.didStartDownloadFlow) {
                    this.stopQuranBootstrapProgressAnimation();
                    this.quranBootstrap.displayProgressPercent = 100;
                    this.quranBootstrap.requiresRestart = false;
                    this.quranBootstrap.isRestarting = false;
                    this.quranBootstrap.statusMessage = String(
                        @js(arabic_text('اكتمل تنزيل بيانات القرآن بنجاح. يلزم إعادة تشغيل المنصة الآن.')),
                    );
        
                    this.quranBootstrap.restartRevealTimeoutId = window.setTimeout(() => {
                        this.quranBootstrap.requiresRestart = true;
                        this.quranBootstrap.restartRevealTimeoutId = null;
                        this.setQuranBootstrapStage('restart');
                    }, 360);
        
                    return;
                }
        
                this.quranBootstrap.isFinishing = true;
                const holdAtHundredMs = 280;
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
            handleQuranBootstrapOverlayClick() {
                if (this.quranBootstrap.requiresRestart) {
                    this.restartNativeAppAfterQuranBootstrap('overlay-click');
        
                    return;
                }
        
                if (!this.quranBootstrap.didStartDownloadFlow) {
                    this.dismissQuranBootstrapState();
                }
            },
            restartNativeAppAfterQuranBootstrap(source = 'unknown') {
                if (!this.quranBootstrap.requiresRestart || this.quranBootstrap.isRestarting) {
                    return;
                }
        
                this.quranBootstrap.isRestarting = true;
                this.quranBootstrap.statusMessage = String(
                    @js(arabic_text('جاري إعادة تشغيل المنصة...')),
                );
        
                if (
                    this.isNativeRuntime &&
                    typeof window.AndroidBridge === 'object' &&
                    window.AndroidBridge !== null &&
                    typeof window.AndroidBridge.restartApplication === 'function'
                ) {
                    window.AndroidBridge.restartApplication();
                    return;
                }
        
                this.quranBootstrap.isRestarting = false;
                this.quranBootstrap.errorMessage = String(
                    @js(arabic_text('تعذر إرسال أمر إعادة التشغيل. أعد بناء المنصة ثم حاول مرة أخرى.')),
                );
                this.quranBootstrap.requiresRestart = false;
                this.quranBootstrap.statusMessage = String(
                    @js(arabic_text('يلزم إعادة تشغيل المنصة يدويًا.')),
                );
                this.setQuranBootstrapStage('error');
                window.dispatchEvent(
                    new CustomEvent('quran-bootstrap-restart-unavailable', {
                        detail: {
                            source: String(source ?? '').trim() || 'unknown',
                        },
                    }),
                );
            },
            handleQuranBootstrapFailed(detail = {}) {
                this.clearQuranBootstrapCloseTimeout();
                this.clearQuranBootstrapRestartRevealTimeout();
                this.stopQuranBootstrapProgressAnimation();
                this.quranBootstrap.isVisible = true;
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.isFinishing = false;
                this.quranBootstrap.requiresRestart = false;
                this.quranBootstrap.isRestarting = false;
                this.quranBootstrap.errorMessage =
                    String(detail?.message ?? @js(arabic_text('تعذر تجهيز بيانات القرآن الآن. حاول مرة أخرى بعد قليل.')));
                this.setQuranBootstrapStage('error');
            },
            dismissQuranBootstrapState() {
                this.clearQuranBootstrapCloseTimeout();
                this.clearQuranBootstrapRestartRevealTimeout();
                this.stopQuranBootstrapProgressAnimation();
                this.quranBootstrap.isVisible = false;
                this.quranBootstrap.isPreparing = false;
                this.quranBootstrap.isFinishing = false;
                this.quranBootstrap.requiresRestart = false;
                this.quranBootstrap.isRestarting = false;
                this.quranBootstrap.didStartDownloadFlow = false;
                this.quranBootstrap.errorMessage = null;
                this.quranBootstrap.progressPercent = 0;
                this.quranBootstrap.displayProgressPercent = 0;
                this.quranBootstrap.statusMessage = null;
                this.setQuranBootstrapStage('preparing');
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
            requestControlPanelOpenFromAnywhere(detail = {}) {
                const tab = String(detail?.tab ?? '').trim();
                const payload = tab !== '' ? { tab } : {};
                const dispatchOpenEvent = () => {
                    window.dispatchEvent(
                        new CustomEvent('open-control-panel-modal', {
                            detail: payload,
                        }),
                    );
                };
        
                const isQuranReaderOpen = Boolean(
                    this.views?.['quran-app-tilawa']?.isOpen ||
                    this.views?.['quran-app-hifth']?.isOpen ||
                    this.views?.['quran-app-tadabbur']?.isOpen,
                );
        
                if (!isQuranReaderOpen) {
                    dispatchOpenEvent();
        
                    return;
                }
        
                let hasOpenedControlPanel = false;
                const openControlPanelOnce = () => {
                    if (hasOpenedControlPanel) {
                        return;
                    }
        
                    hasOpenedControlPanel = true;
                    dispatchOpenEvent();
                };
                const switchViewListener = (event) => {
                    if (String(event?.detail?.to ?? '').trim() !== 'quran-app-gate') {
                        return;
                    }
        
                    window.removeEventListener('switch-view', switchViewListener);
                    openControlPanelOnce();
                };
        
                window.addEventListener('switch-view', switchViewListener);
                window.dispatchEvent(new CustomEvent('quran-reader-go-gate'));
        
                window.setTimeout(() => {
                    window.removeEventListener('switch-view', switchViewListener);
                    openControlPanelOnce();
                }, this.isNativeRuntime ? 620 : 420);
            },
            clearControlPanelGateReturnTimer() {
                if (this.controlPanelGateReturnTimerId === null) {
                    return;
                }
        
                window.clearTimeout(this.controlPanelGateReturnTimerId);
                this.controlPanelGateReturnTimerId = null;
            },
            handleControlPanelSaveGateReturn(detail = {}) {
                if (!Boolean(detail?.returnToGate)) {
                    return;
                }
        
                const currentView = String(this.activeView ?? '').trim();
                let targetView = null;
        
                if (currentView === 'athkar-app-sabah' || currentView === 'athkar-app-masaa') {
                    targetView = 'athkar-app-gate';
                } else if (
                    currentView === 'quran-app-tilawa' ||
                    currentView === 'quran-app-hifth' ||
                    currentView === 'quran-app-tadabbur'
                ) {
                    targetView = 'quran-app-gate';
                }
        
                if (targetView === null) {
                    return;
                }
        
                this.clearControlPanelGateReturnTimer();
        
                const runGateReturn = (attempt = 0) => {
                    if (this.isControlPanelOpen && attempt < 10) {
                        this.controlPanelGateReturnTimerId = window.setTimeout(() => {
                            runGateReturn(attempt + 1);
                        }, 40);
        
                        return;
                    }
        
                    this.controlPanelGateReturnTimerId = null;
        
                    if (targetView === 'athkar-app-gate') {
                        this.$viewNav('athkar-app-gate', { force: true });
        
                        return;
                    }
        
                    window.dispatchEvent(new CustomEvent('quran-reader-go-gate'));
                };
        
                this.controlPanelGateReturnTimerId = window.setTimeout(() => {
                    runGateReturn(0);
                }, 60);
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
                const isQuranReaderView = ['quran-app-tilawa', 'quran-app-hifth', 'quran-app-tadabbur'].includes(view);
                const layoutManager = this.$store?.layoutManager;
        
                Object.keys(this.views).forEach((key) => {
                    this.views[key].isOpen = key === view;
                });
                if (persist) {
                    this.activeView = view;
                }
        
                if (layoutManager?.isActionOpen) {
                    layoutManager.isActionOpen = false;
                }
        
                if (!isQuranReaderView) {
                    this.isQuranReaderCalibrating = false;
        
                    if (document?.body instanceof HTMLElement) {
                        document.body.classList.remove(
                            'quran-reader-immersive-active',
                            'quran-reader-immersive-chrome-visible',
                            'quran-reader-calibrating',
                            'quran-reader-font-scale-overlay-open',
                        );
                    }
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
                $dispatch('request-open-control-panel-modal');
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
        x-on:muttasiq-app-version-major-minor-reset.window="handleAppVersionMajorMinorReset($event.detail ?? {})"
        x-on:request-open-control-panel-modal.window="requestControlPanelOpenFromAnywhere($event.detail ?? {})"
        x-on:control-panel-updated.window="handleControlPanelSaveGateReturn($event.detail ?? {})"
        x-on:athkar-action-state-pulse.window="pulseActionState($event.detail ?? {})"
        x-on:quran-bootstrap-started.window="handleQuranBootstrapStarted()"
        x-on:quran-bootstrap-progress.window="handleQuranBootstrapProgress($event.detail ?? {})"
        x-on:quran-bootstrap-finished.window="handleQuranBootstrapFinished($event.detail ?? {})"
        x-on:quran-bootstrap-failed.window="handleQuranBootstrapFailed($event.detail ?? {})"
        x-on:quran-reader-calibration-started.window="isQuranReaderCalibrating = true"
        x-on:quran-reader-calibration-finished.window="isQuranReaderCalibrating = false"
        x-on:quran-reader-font-scale-overlay-visibility.window="isQuranReaderFontScaleOverlayOpen = Boolean($event.detail?.open)"
        x-on:athkar-reader-font-scale-overlay-visibility.window="isAthkarReaderFontScaleOverlayOpen = Boolean($event.detail?.open)"
    >
        @php
            $quranReaderViewsCondition =
                'views[`quran-app-tilawa`].isOpen || views[`quran-app-hifth`].isOpen || views[`quran-app-tadabbur`].isOpen';
            $returnButtonShowCondition = is_platform('mobile')
                ? 'false'
                : 'views[`athkar-app-gate`].isReaderVisible || ' . $quranReaderViewsCondition;
            $returnButtonClickCallback = is_platform('mobile')
                ? 'if (' .
                    $quranReaderViewsCondition .
                    ') { window.dispatchEvent(new CustomEvent(`quran-reader-go-gate`)); }'
                : 'if (views[`athkar-app-gate`].isReaderVisible) { $dispatch(`close-athkar-mode`); return; } if (' .
                    $quranReaderViewsCondition .
                    ') { window.dispatchEvent(new CustomEvent(`quran-reader-go-gate`)); }';
            $homeButtonShowCondition = is_platform('mobile')
                ? 'false'
                : "!views['main-menu'].isOpen && !isControlPanelOpen && !isAthkarManagerOpen";
        @endphp
        <x-buttons-stack
            x-bind:data-respecting-stack="$store.bp.current === 'base'"
            stack-top-offset="0.4rem"
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
            <x-athkar-reader-font-scale-button />
            <x-quran-reader-font-scale-button />
            @if (!is_platform('native'))
                <x-partials.download-stack-button />
            @endif
        </x-buttons-stack>

        <x-partials.colorful-background />

        <main @class([
            'fixed inset-0 grid place-items-center sm:mt-0 dark:text-white',
            'mt-22' => is_platform('ios'),
            'mt-15' => !is_platform('ios'),
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
                x-on:click="handleQuranBootstrapOverlayClick()"
            ></div>

            <section
                class="border-primary-300/35 bg-white/92 shadow-slate-950/18 dark:bg-slate-950/88 min-h-76 sm:min-h-73 relative flex w-[min(92vw,28rem)] items-center rounded-[1.8rem] border px-6 py-5 text-center shadow-2xl transition-[transform,opacity] sm:w-[min(90vw,31rem)]"
                x-transition:enter="transition-[opacity,transform] duration-220 ease-out"
                x-transition:enter-start="opacity-0 scale-[0.97]"
                x-transition:enter-end="opacity-100 scale-100"
                x-transition:leave="transition-[opacity,transform] duration-220 ease-in"
                x-transition:leave-start="opacity-100 scale-100"
                x-transition:leave-end="opacity-0 scale-[0.97]"
            >
                <div class="max-w-73 relative mx-auto w-full">
                    <div
                        class="pointer-events-none invisible"
                        aria-hidden="true"
                    >
                        <div
                            class="min-h-53 grid w-full grid-rows-[auto_auto_minmax(5.6rem,1fr)_auto] items-center gap-4">
                            <div class="mx-auto h-10 w-10 rounded-full"></div>
                            <div class="min-h-[1.85rem] w-full text-base/8 sm:text-lg/9">
                                {{ arabic_text('تعذر تجهيز بيانات القرآن') }}
                            </div>
                            <div class="w-full text-sm/8 sm:text-base/9">
                                {{ arabic_text('اكتمل تنزيل بيانات القرآن بنجاح. يلزم إعادة تشغيل المنصة الآن. تعذر إرسال أمر إعادة التشغيل. أعد بناء المنصة ثم حاول مرة أخرى.') }}
                            </div>
                            <div class="flex flex-wrap items-center justify-center gap-3">
                                <span class="rounded-xl px-4 py-2 text-sm font-semibold">
                                    {{ arabic_text('إعادة المحاولة') }}
                                </span>
                                <span class="rounded-xl px-4 py-2 text-sm font-semibold">
                                    {{ arabic_text('إغلاق') }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="absolute inset-0">
                        <div
                            class="min-h-53 duration-260 absolute inset-0 grid w-full grid-rows-[auto_auto_minmax(5.6rem,1fr)_auto] items-center gap-4 transition-[opacity,transform,filter] ease-[cubic-bezier(0.22,1,0.36,1)]"
                            x-bind:aria-hidden="!isQuranBootstrapStage('preparing')"
                            x-bind:inert="!isQuranBootstrapStage('preparing')"
                            x-bind:class="quranBootstrapStageClasses('preparing')"
                            x-bind:style="quranBootstrapStageStyle('preparing')"
                        >
                            <div class="relative mx-auto h-10 w-10">
                                <div
                                    class="border-3 border-primary-200 border-t-primary-600 absolute inset-0 animate-spin rounded-full"
                                    style="animation-direction: reverse;"
                                ></div>
                            </div>
                            <h2
                                class="text-primary-950 dark:text-primary-50 min-h-[1.85rem] w-full text-base/8 font-semibold sm:text-lg/9">
                                {{ arabic_text('تحميل بيانات المصحف') }}
                            </h2>
                            <div
                                class="text-primary-900/78 dark:text-primary-100/82 flex w-full flex-col justify-center gap-2 text-sm/8 sm:text-base/9">
                                <p>{{ arabic_text('يتم تجهيز المصحف بشكل أنيق ومحرك اللغة العربية لبحث متقدم...') }}</p>
                                <p
                                    class="text-primary-800/70 dark:text-primary-100/65 text-xs/6 font-medium sm:text-sm/7"
                                    x-show="Boolean(quranBootstrap.statusMessage)"
                                    x-transition.opacity.duration.220ms
                                    x-text="quranBootstrap.statusMessage"
                                ></p>
                            </div>
                            <div class="space-y-2">
                                <div
                                    class="h-2.5 w-full overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-700/60">
                                    <div
                                        class="from-primary-500 to-primary-700 bg-linear-to-r h-full rounded-full transition-[width] duration-150"
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
                            class="min-h-53 duration-260 absolute inset-0 grid w-full grid-rows-[auto_auto_minmax(5.6rem,1fr)_auto] items-center gap-4 transition-[opacity,transform,filter] ease-[cubic-bezier(0.22,1,0.36,1)]"
                            x-bind:aria-hidden="!isQuranBootstrapStage('success')"
                            x-bind:inert="!isQuranBootstrapStage('success')"
                            x-bind:class="quranBootstrapStageClasses('success')"
                            x-bind:style="quranBootstrapStageStyle('success')"
                        >
                            <div
                                class="dark:bg-emerald-500/18 mx-auto grid h-10 w-10 place-items-center rounded-full border border-emerald-300/70 bg-emerald-100/80 text-emerald-700 dark:border-emerald-400/60 dark:text-emerald-300">
                                <svg
                                    class="h-6 w-6"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                            </div>
                            <h2
                                class="min-h-[1.85rem] w-full text-base/8 font-semibold text-emerald-700 sm:text-lg/9 dark:text-emerald-300">
                                {{ arabic_text('تم بحمد الله') }}
                            </h2>
                            <div
                                class="text-primary-900/78 dark:text-primary-100/82 flex w-full flex-col justify-center gap-2 text-sm/8 sm:text-base/9">
                                <p>{{ arabic_text('اكتمل تنزيل بيانات القرآن بنجاح.') }}</p>
                                <p
                                    class="text-xs/6 font-semibold text-emerald-700/80 sm:text-sm/7 dark:text-emerald-300/80">
                                    {{ arabic_text('يتم تهيئة الخطوة الأخيرة...') }}
                                </p>
                            </div>
                            <div class="space-y-2">
                                <div
                                    class="h-2.5 w-full overflow-hidden rounded-full bg-emerald-100/80 dark:bg-emerald-950/45">
                                    <div
                                        class="bg-linear-to-r h-full w-full rounded-full from-emerald-400 via-emerald-500 to-emerald-600">
                                    </div>
                                </div>
                                <p class="text-xs font-semibold text-emerald-700/80 dark:text-emerald-300/80">100%</p>
                            </div>
                        </div>

                        <div
                            class="min-h-53 duration-260 absolute inset-0 grid w-full grid-rows-[auto_auto_minmax(5.6rem,1fr)_auto] items-center gap-4 transition-[opacity,transform,filter] ease-[cubic-bezier(0.22,1,0.36,1)]"
                            x-bind:aria-hidden="!isQuranBootstrapStage('restart')"
                            x-bind:inert="!isQuranBootstrapStage('restart')"
                            x-bind:class="quranBootstrapStageClasses('restart')"
                            x-bind:style="quranBootstrapStageStyle('restart')"
                        >
                            <div
                                class="dark:bg-emerald-500/18 mx-auto grid h-10 w-10 place-items-center rounded-full border border-emerald-300/70 bg-emerald-100/80 text-emerald-700 dark:border-emerald-400/60 dark:text-emerald-300">
                                <svg
                                    class="h-6 w-6"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M20 6L9 17l-5-5"></path>
                                </svg>
                            </div>
                            <h2
                                class="min-h-[1.85rem] w-full text-base/8 font-semibold text-emerald-700 sm:text-lg/9 dark:text-emerald-300">
                                {{ arabic_text('تم بحمد الله') }}
                            </h2>
                            <div
                                class="text-primary-900/78 dark:text-primary-100/82 flex w-full flex-col justify-center gap-2 text-sm/8 sm:text-base/9">
                                <p>{{ arabic_text('يرجى إعادة تشغيل المنصة ليتمّ اعتماد البيانات.') }}</p>
                                <p
                                    class="text-primary-800/70 dark:text-primary-100/65 text-xs/6 font-medium sm:text-sm/7"
                                    x-show="Boolean(quranBootstrap.statusMessage)"
                                    x-transition.opacity.duration.220ms
                                    x-text="quranBootstrap.statusMessage"
                                ></p>
                            </div>
                            <div class="flex items-center justify-center">
                                <button
                                    class="bg-primary-600 hover:bg-primary-700 disabled:bg-primary-300 min-w-50 relative rounded-xl px-4 py-2 text-sm font-semibold text-white transition disabled:cursor-not-allowed"
                                    type="button"
                                    x-bind:disabled="quranBootstrap.isRestarting"
                                    x-on:click="restartNativeAppAfterQuranBootstrap('success-button')"
                                >
                                    <span
                                        class="duration-220 block transition-[opacity,transform]"
                                        x-bind:class="quranBootstrap.isRestarting ? 'opacity-0 translate-y-1' :
                                            'opacity-100 translate-y-0'"
                                    >{{ arabic_text('إعادة تشغيل المنصة الآن') }}</span>
                                    <span
                                        class="duration-220 absolute inset-0 grid place-items-center px-4 transition-[opacity,transform]"
                                        x-bind:class="quranBootstrap.isRestarting ? 'opacity-100 translate-y-0' :
                                            'pointer-events-none opacity-0 -translate-y-1'"
                                    >{{ arabic_text('جاري إعادة التشغيل...') }}</span>
                                </button>
                            </div>
                        </div>

                        <div
                            class="min-h-53 duration-260 absolute inset-0 grid w-full grid-rows-[auto_auto_minmax(5.6rem,1fr)_auto] items-center gap-4 transition-[opacity,transform,filter] ease-[cubic-bezier(0.22,1,0.36,1)]"
                            x-bind:aria-hidden="!isQuranBootstrapStage('error')"
                            x-bind:inert="!isQuranBootstrapStage('error')"
                            x-bind:class="quranBootstrapStageClasses('error')"
                            x-bind:style="quranBootstrapStageStyle('error')"
                        >
                            <div
                                class="dark:bg-danger-500/18 border-danger-300/75 bg-danger-100/80 text-danger-700 dark:border-danger-400/55 dark:text-danger-300 mx-auto grid h-10 w-10 place-items-center rounded-full border">
                                <svg
                                    class="h-6 w-6"
                                    aria-hidden="true"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2.2"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <path d="M12 8v4"></path>
                                    <path d="M12 16h.01"></path>
                                    <path
                                        d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"
                                    ></path>
                                </svg>
                            </div>
                            <h2
                                class="text-danger-700 dark:text-danger-300 min-h-[1.85rem] w-full text-base/8 font-semibold sm:text-lg/9">
                                {{ arabic_text('تعذر تجهيز بيانات القرآن') }}
                            </h2>
                            <p
                                class="flex w-full items-center justify-center text-sm/8 text-slate-700 sm:text-base/9 dark:text-slate-200"
                                x-text="quranBootstrap.errorMessage"
                            ></p>
                            <div class="flex flex-wrap items-center justify-center gap-3">
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
                                    x-bind:class="quranBootstrap.didStartDownloadFlow ? 'pointer-events-none opacity-0' :
                                        'opacity-100'"
                                    x-bind:inert="quranBootstrap.didStartDownloadFlow"
                                    x-on:click="dismissQuranBootstrapState()"
                                >
                                    {{ arabic_text('إغلاق') }}
                                </button>
                            </div>
                        </div>
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
