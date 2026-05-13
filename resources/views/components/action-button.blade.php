@props([
    'hoverClasses' => '',
    'unhoverClasses' => '',
    'withoutHover' => false,
    'useInvertedStyle' => false,
    'iconName' => null,
    'iconClasses' => null,
    'iconsSlot' => null,
    'buttonTestId' => null,
    'extraAttributes' => null,
])

@php
    $baseButtonClasses =
        'relative grid h-8 w-8 rotate-45 place-items-center overflow-hidden Xrounded-lg border-2 shadow transition-all outline-none rounded-md focus:ring-2 will-change-auto in-data-loading:opacity-0! sm:h-10 sm:w-10 md:h-10 md:w-10 lg:h-10 lg:w-10 sm:[zoom:0.9] md:[zoom:1] lg:[zoom:1] xl:[zoom:0.9] 2xl:[zoom:1.05] 3xl:[zoom:1.2] 4xl:[zoom:1.25]';
    $defaultButtonClasses = $useInvertedStyle
        ? 'border-primary-500 shadow-primary-500/30 dark:border-primary-100/60 dark:active:border-primary-400 dark:shadow-primary-500/30 bg-[var(--background)] dark:bg-[var(--background-dark)] focus:ring-primary-300 focus:dark:ring-primary-50 active:ring-primary-300 active:dark:ring-primary-300'
        : 'border-primary-500 shadow-primary-500/30 dark:border-primary-400 dark:shadow-primary-500/30 bg-primary-600 dark:bg-primary-50 focus:ring-primary-200 focus:dark:ring-primary-50 active:ring-primary-300 active:dark:ring-primary-200';
    $hoveredButtonClasses = trim(
        ($useInvertedStyle
            ? 'scale-110 bg-primary-600! dark:bg-primary-100! focus:ring-[var(--background)] focus:dark:ring-[var(--background-dark)] active:ring-[var(--background)] active:dark:ring-[var(--background-dark)]'
            : 'scale-110 bg-white!') .
            ' ' .
            $hoverClasses,
    );
    $defaultIconClasses = $useInvertedStyle
        ? 'text-primary-600 dark:text-primary-50'
        : 'text-white dark:text-primary-600';
    $hoveredIconClasses = $useInvertedStyle ? 'text-white! dark:text-primary-600!' : 'text-primary-600!';
    $anchoredIconClasses =
        'absolute top-1/2 left-1/2 h-6 w-6 -translate-x-1/2 -translate-y-1/2 -rotate-45 shrink-0 transition-all will-change-auto sm:h-8 sm:w-8 md:h-8 md:w-8 lg:h-8 lg:w-8';
@endphp

<button
    {{ $attributes->class([$baseButtonClasses, $defaultButtonClasses])->merge(array_merge(['data-testid' => $buttonTestId], $extraAttributes ?? [])) }}
    @if (!$withoutHover) x-data="{ hovered: false }"
        x-on:mouseenter="hovered = true"
        x-on:mouseleave="hovered = false"
        @if ($useInvertedStyle)
            x-on:focus="hovered = true"
            x-on:blur="hovered = false" @endif
    @endif x-bind:class="{
        '{{ $hoveredButtonClasses }}': hovered,
        '{{ $unhoverClasses }}': !hovered,
    }">
    @if ($iconsSlot)
        {{ $iconsSlot }}
    @else
        <x-icon
            name="{{ $iconName }}"
            @class([
                $iconClasses => $iconClasses,
                $defaultIconClasses => !$iconClasses,
                $anchoredIconClasses,
            ])
            x-bind:class="{
                '{{ $hoveredIconClasses }}': hovered,
            }"
        />
    @endif
</button>
