<div
    class="z-999999 fixed bottom-[calc(var(--inset-bottom,0px)*-1)] left-[calc(var(--inset-left,0px)*-1)] right-[calc(var(--inset-right,0px)*-1)] top-[calc(var(--inset-top,0px)*-1)] overflow-hidden transition-[opacity,background-color] ease-in will-change-[opacity,background-color]"
    x-ref="blinker"
    x-on:livewire-session-timed-out.window="blink(false, true)"
    x-bind:style="{
        {{-- During an auth hold the body stays visible underneath, so go transparent --}}
        {{-- and let the overlay's backdrop-blur blur the real (main-menu) view instead --}}
        {{-- of a flat gray fill. --}}
        backgroundColor: isAuthHoldActive ? 'transparent' : $store.colorScheme.bodyBackgroundColor,
            transitionDuration: (defaultTransitionDurationInMs + 'ms'),
    }"
    x-bind:class="{
        'opacity-0 pointer-events-none': !isBlinkerShown,
    }"
>
    {{-- Auth loading overlay: the same dark-blur + spinner shown when the control --}}
    {{-- panel modal opens, raised over the blinker for the whole Telegram round-trip --}}
    {{-- (login tap → browser → return → restart/close) so the user always sees the --}}
    {{-- app is working out the login, instead of a delayed toast. --}}
    <div
        class="absolute inset-0 flex items-center justify-center bg-black/25 backdrop-blur-[10px]"
        x-cloak
        x-show="isBlinkerShown && isAuthHoldActive"
        x-transition.opacity
    >
        <svg
            class="size-8 animate-spin text-white [animation-direction:reverse] sm:size-10"
            aria-hidden="true"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle
                class="opacity-25"
                cx="12"
                cy="12"
                r="10"
                stroke="currentColor"
                stroke-width="4"
            ></circle>
            <path
                class="opacity-75"
                fill="currentColor"
                d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"
            ></path>
        </svg>
    </div>
</div>
