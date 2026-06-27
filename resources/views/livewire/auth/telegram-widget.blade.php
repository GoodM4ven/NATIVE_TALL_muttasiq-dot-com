@php
    $nativeTelegramLauncherUrl = '';
    $nativeTelegramPath = route('auth.telegram.native', [], false);
    $nativeSettingsEndpoint = trim((string) config('app.custom.native_end_points.settings', ''));

    if (is_platform('native') && preg_match('/^https?:\/\//i', $nativeSettingsEndpoint) === 1) {
        $settingsScheme = parse_url($nativeSettingsEndpoint, PHP_URL_SCHEME);
        $settingsHost = parse_url($nativeSettingsEndpoint, PHP_URL_HOST);
        $settingsPort = parse_url($nativeSettingsEndpoint, PHP_URL_PORT);

        if (is_string($settingsScheme) && is_string($settingsHost) && $settingsScheme !== '' && $settingsHost !== '') {
            $nativeTelegramLauncherUrl =
                $settingsScheme .
                '://' .
                $settingsHost .
                ($settingsPort ? ':' . $settingsPort : '') .
                $nativeTelegramPath;
        }
    }

    if ($nativeTelegramLauncherUrl === '') {
        $nativeTelegramLauncherUrl = trim((string) config('app.custom.native_end_points.telegram_auth', ''));
    }

    if ($nativeTelegramLauncherUrl === '') {
        $applicationUrl = rtrim((string) config('app.url'), '/');
        $applicationUrlScheme = parse_url($applicationUrl, PHP_URL_SCHEME);

        if (is_string($applicationUrlScheme) && in_array(strtolower($applicationUrlScheme), ['http', 'https'], true)) {
            $nativeTelegramLauncherUrl = $applicationUrl . $nativeTelegramPath;
        }
    }
@endphp

@if (is_platform('native'))
    {{-- min-h reserves the button's space so the modal doesn't jolt when the --}}
    {{-- (initially x-cloaked) button appears after the async connectivity check. --}}
    <div
        class="mx-auto flex min-h-16 w-full max-w-full flex-col items-center justify-center gap-3 py-2"
        x-data="{
            launcherUrl: @js($nativeTelegramLauncherUrl),
            isOnline: true,
            async resolveOnlineState() {
                if (window.nativeNetwork?.status) {
                    try {
                        const status = await window.nativeNetwork.status();
        
                        if (typeof status?.connected === 'boolean') {
                            return status.connected;
                        }
                    } catch (_) {
                        // Fall back to the browser hint when the native bridge is temporarily unavailable.
                    }
                }
        
                return typeof navigator === 'undefined' ? true : navigator.onLine;
            },
            async syncConnectivityState() {
                this.isOnline = await this.resolveOnlineState();
            },
            async openNativeTelegramAuth() {
                // Don't gate on isOnline: the native connectivity probe misreports
                // on some networks, which would silently block a working connection.
                // If genuinely offline, the launcher simply fails to load.
                if (!this.launcherUrl) {
                    return;
                }
        
                // Mark the Telegram return as pending so the home shell holds the
                // blinker over the whole round-trip (cold-start, deeplink handoff,
                // restart, restore) instead of flashing the guest login UI.
                try {
                    window.localStorage.setItem('auth.telegram.pending', String(Date.now()));
                } catch (_) {
                    // Non-fatal: the flow still works, just without the early blinker.
                }
        
                if (window.browser?.auth) {
                    await window.browser.auth(this.launcherUrl);
        
                    return;
                }
        
                window.open(this.launcherUrl, `_blank`, `noopener`);
            },
        }"
        x-init="syncConnectivityState()"
        x-on:online.window="syncConnectivityState()"
        x-on:offline.window="syncConnectivityState()"
    >
        <button
            class="inline-flex min-h-12 items-center justify-center rounded-2xl bg-sky-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-sky-600 disabled:cursor-not-allowed disabled:opacity-50"
            type="button"
            x-cloak
            x-show="launcherUrl"
            x-on:click="openNativeTelegramAuth()"
        >
            {{ arabic_text('تسجيل الدخول عبر تيليجرام') }}
        </button>

        <p
            class="text-center text-xs text-gray-500 dark:text-gray-400"
            x-cloak
            x-show="launcherUrl && !isOnline"
        >
            {{ arabic_text('يتطلب تسجيل الدخول عبر تيليجرام اتصالًا بالإنترنت.') }}
        </p>

        <p
            class="text-center text-xs text-gray-500 dark:text-gray-400"
            x-cloak
            x-show="!launcherUrl"
        >
            {{ arabic_text('تسجيل الدخول عبر تيليجرام غير متاح في هذا الإصدار حاليًا.') }}
        </p>
    </div>
@else
    {{-- Renders Telegram's real login button inline via the official widget script. --}}
    {{-- wire:ignore keeps Livewire morphs (e.g. invalid-credentials re-render) from wiping the injected iframe. --}}
    <div
        class="relative mx-auto flex min-h-[64px] w-[178px] max-w-full items-center justify-center self-center overflow-visible py-2"
        wire:ignore
        x-data="{
            loaded: false,
            injectTelegramWidget() {
                // On Telegram auth success: fade the blinker in first, then navigate to
                // our callback (with the signed user params) — never an instant reload.
                window.onTelegramAuth = (user) => {
                    const params = new URLSearchParams();
                    Object.entries(user).forEach(([key, value]) => {
                        if (value !== undefined && value !== null) {
                            params.append(key, value);
                        }
                    });
        
                    window.dispatchEvent(new CustomEvent('auth-blink-reload', {
                        detail: { url: @js(route('auth.telegram.callback')) + '?' + params.toString() },
                    }));
                };
        
                if (this.$refs.slot.querySelector('iframe')) {
                    setTimeout(() => (this.loaded = true), 600);
                    return;
                }
        
                const script = document.createElement('script');
                script.async = true;
                script.src = 'https://telegram.org/js/telegram-widget.js?22';
                script.setAttribute('data-telegram-login', @js(config('services.telegram.bot')));
                script.setAttribute('data-size', 'large');
                script.setAttribute('data-userpic', 'false');
                script.setAttribute('data-onauth', 'onTelegramAuth(user)');
                script.setAttribute('data-request-access', 'write');
                this.$refs.slot.appendChild(script);
        
                // Lift the placeholder blur once Telegram's button iframe is mounted.
                const observer = new MutationObserver(() => {
                    if (this.$refs.slot.querySelector('iframe')) {
                        setTimeout(() => (this.loaded = true), 600);
                        observer.disconnect();
                    }
                });
                observer.observe(this.$refs.slot, { childList: true, subtree: true });
            },
        }"
        x-init="injectTelegramWidget()"
    >
        <div
            class="flex w-full items-center justify-center transition duration-300"
            x-ref="slot"
            x-bind:class="{ 'opacity-0': !loaded }"
        ></div>

        <div
            class="blur-xs pointer-events-none absolute h-14 w-[232px] animate-pulse rounded-[20px] bg-gray-200 dark:bg-gray-700"
            x-show="!loaded"
            x-transition.opacity.duration.300ms
        ></div>
    </div>
@endif
