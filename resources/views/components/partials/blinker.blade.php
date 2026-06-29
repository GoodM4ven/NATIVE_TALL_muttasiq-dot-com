<div
    class="z-999999 fixed bottom-[calc(var(--inset-bottom,0px)*-1)] left-[calc(var(--inset-left,0px)*-1)] right-[calc(var(--inset-right,0px)*-1)] top-[calc(var(--inset-top,0px)*-1)] overflow-hidden transition-[opacity,background-color] ease-in will-change-[opacity,background-color]"
    x-ref="blinker"
    x-on:livewire-session-timed-out.window="blink(false, true)"
    x-bind:style="{
        backgroundColor: $store.colorScheme.bodyBackgroundColor,
        transitionDuration: (defaultTransitionDurationInMs + 'ms'),
    }"
    x-bind:class="{
        'opacity-0 pointer-events-none': !isBlinkerShown,
    }"
>
    <div
        class="pointer-events-none absolute inset-0 flex items-center justify-center px-6 text-center text-lg font-semibold text-slate-700 dark:text-slate-200"
        x-cloak
        x-show="isBlinkerShown && authStatusMessage"
        x-transition.opacity
        x-text="authStatusMessage"
    ></div>
</div>
