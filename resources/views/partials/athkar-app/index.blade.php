@assets
    <style>
        .athkar-shift-away {
            transform: translate3d(
                var(--athkar-shift-x, 0px),
                var(--athkar-shift-y, 1.5rem),
                0
            );
        }

        .athkar-shift-center {
            transform: translate3d(0, 0, 0);
        }
    </style>
@endassets

<div
    class="-top-7! sm:top-0! absolute inset-0 flex items-center justify-center"
    x-cloak
    x-show="views['athkar-app-gate'].isOpen || views['athkar-app-sabah'].isOpen || views['athkar-app-masaa'].isOpen"
    x-data="athkarAppReader({
        athkar: @js($athkar),
        athkarSettings: @js($athkarSettings),
    })"
    x-on:close-athkar-mode.window="closeMode()"
    x-on:settings-updated.window="applySettings($event.detail?.settings)"
    x-on:athkar-gate-open="openMode($event.detail?.mode)"
    x-on:keydown.escape.window="if (activeMode && !isCompletionVisible) closeMode()"
    x-transition:enter="transition-all ease-out duration-750 delay-400"
    x-transition:enter-start="opacity-0! translate-y-5 blur-[2px]"
    x-transition:enter-end="opacity-100 translate-y-0 blur-0"
    x-transition:leave="transition-all ease-in duration-350!"
    x-transition:leave-start="opacity-100 translate-y-0 blur-0"
    x-transition:leave-end="opacity-0! blur-[2px]"
>
    <div class="relative flex h-full w-full items-center justify-center">
        @include('partials.athkar-app.gate')
        @include('partials.athkar-app.notice')
        @include('partials.athkar-app.reader')
        @include('partials.athkar-app.congrats')
    </div>
</div>
