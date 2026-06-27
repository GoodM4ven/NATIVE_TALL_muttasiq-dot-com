{{-- Signed-in-but-offline indicator: the account works, but changes aren't --}}
{{-- syncing to the cloud until connectivity returns. Minimal, responsive pill. --}}
<div
    class="z-99999 fixed bottom-[calc(env(safe-area-inset-bottom,0px)+0.75rem)] left-1/2 flex -translate-x-1/2 items-center gap-2 rounded-full border border-amber-300/60 bg-amber-50/95 px-3 py-1.5 text-amber-800 shadow-lg backdrop-blur sm:bottom-[calc(env(safe-area-inset-bottom,0px)+1rem)] sm:gap-2.5 sm:px-4 sm:py-2 dark:border-amber-400/30 dark:bg-amber-950/90 dark:text-amber-200"
    role="status"
    aria-live="polite"
    x-cloak
    x-data="{
        isOnline: true,
        isSignedIn: window.dataBranch === 'user',
        async resolveOnlineState() {
            if (window.nativeNetwork?.status) {
                try {
                    const status = await window.nativeNetwork.status();
                    if (typeof status?.connected === 'boolean') {
                        return status.connected;
                    }
                } catch (_) {
                    // Fall back to the browser hint when the bridge is unavailable.
                }
            }
            return typeof navigator === 'undefined' ? true : navigator.onLine;
        },
        async syncConnectivityState() {
            this.isOnline = await this.resolveOnlineState();
        },
    }"
    x-init="syncConnectivityState()"
    x-on:online.window="syncConnectivityState()"
    x-on:offline.window="syncConnectivityState()"
    x-show="isSignedIn && !isOnline"
    x-transition.opacity.duration.300ms
    dir="rtl"
>
    <span class="relative flex h-2 w-2 shrink-0">
        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-amber-400 opacity-75"></span>
        <span class="relative inline-flex h-2 w-2 rounded-full bg-amber-500"></span>
    </span>
    <span class="whitespace-nowrap text-xs font-medium sm:text-sm">
        {{ arabic_text('غير متصل — لا تتم المزامنة') }}
    </span>
</div>
