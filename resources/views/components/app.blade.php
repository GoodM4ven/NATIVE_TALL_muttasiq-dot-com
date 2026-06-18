<!DOCTYPE html>
<html
    x-data
    lang="ar"
    dir="rtl"
    @if (app()->environment('testing')) data-disable-js-error-reporting="true"
        data-disable-livewire-session-reload="true" @endif
>

<head>
    <x-partials.meta />
    <x-partials.favicon />
    @stack('meta')

    <!-- Fonts -->
    @php($scheherazadeFont = Vite::asset('resources/fonts/scheherazade-new/ScheherazadeNew-Regular.ttf'))
    <link
        type="font/ttf"
        href="{{ $scheherazadeFont }}"
        rel="preload"
        as="font"
        crossorigin
    >
    @stack('fonts')

    <!-- Styles -->
    <style>
        [x-cloak] {
            display: none !important;
        }
    </style>
    <x-partials.fast-testing-hacks />
    <x-partials.filament-overrides />
    @filamentStyles
    @lazyCss(['resources/css/core/filament/components.css', 'resources/css/app-lazy.css'])
    @vite('resources/css/app.css')
    @stack('styles')

    <!-- Head Scripts --> {{-- ! Loads only once --}}
    @vite('resources/js/app.js')
    @stack('head-scripts')
</head>

@php($shouldRunStartupSync = is_platform('mobile'))

<body
    class="{{ twMerge([
        'mobile-platform' => is_platform('mobile'),
        'native-platform' => is_platform('native'),
        'ios-platform' => is_platform('ios'),
        'android-platform' => is_platform('android'),
        'nativephp-safe-area font-arabic-sans relative h-full min-h-dvh antialiased transition-[color,background-color,border-color,text-decoration-color,fill,stroke] ease-in will-change-[color,background-color,border-color,text-decoration-color,fill,stroke]',
        'test-fast-ui' => config('app.browser_test_fast_mode'),
    ]) }}"
    x-bind:class="{
        'overflow-hidden': isScrollingDisabled,
        'ease-out!': useFastTransitionDuration,
    }"
    x-on:toggle-scroller.window="isScrollingDisabled = $event.detail?.disabled ?? !isScrollingDisabled"
    x-bind:style="{
        backgroundColor: $store.colorScheme.bodyBackgroundColor,
        transitionDuration: (fastTransitionDurationInMs + 'ms'),
    }"
    x-data="layoutManager({
        shouldRunStartupSync: @js($shouldRunStartupSync),
    })"
>
    <x-partials.blinker />

    <div class="flex min-h-[calc(100dvh-var(--inset-top,0)-var(--inset-bottom,0))] flex-col">
        {{ $slot }}
    </div>

    @livewire('notifications')

    <!-- Body Scripts --> {{-- ! Reloads on every refresh --}}
    @ineresh
    @filamentScripts
    @stack('body-scripts')
</body>

</html>
