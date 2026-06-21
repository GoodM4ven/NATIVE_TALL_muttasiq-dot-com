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
