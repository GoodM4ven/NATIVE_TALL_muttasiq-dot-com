@props([
    'onClickCallback' => null,
    'buttonClasses' => '',
    'iconClasses' => '',
    'iconName',
    'caption',
])

@php
    $locked = !$onClickCallback;
    $resolvedCaption = arabic_text((string) $caption);
@endphp

@once
    @assets
        <style>
            .item-unlocked-indicator {
                position: relative;
                isolation: isolate;
                overflow: hidden;
            }

            .item-unlocked-indicator::before {
                content: '';
                position: absolute;
                inset: 0;
                pointer-events: none;
                border-radius: 0.75rem;
                box-shadow:
                    0 6px 13px 0 color-mix(in srgb, var(--primary-500) 14%, transparent),
                    0 24px 24px 0 color-mix(in srgb, var(--primary-500) 12%, transparent),
                    0 55px 33px 0 color-mix(in srgb, var(--primary-500) 7%, transparent),
                    0 97px 39px 0 color-mix(in srgb, var(--primary-500) 2%, transparent),
                    0 152px 43px 0 color-mix(in srgb, var(--primary-500) 0%, transparent);
                opacity: 0;
                animation: main-menu-unlock-hover-echo 2.8s ease-in-out infinite;
                animation-delay: -1.1s;
            }

            .item-unlocked-indicator::after {
                content: '';
                position: absolute;
                inset: 1px;
                pointer-events: none;
                border-radius: 0.68rem;
                background: linear-gradient(112deg,
                        transparent 22%,
                        color-mix(in srgb, white 94%, transparent) 50%,
                        transparent 78%);
                transform: translateX(-175%) skewX(-18deg);
                opacity: 0;
                animation: main-menu-unlock-shimmer 2.8s ease-in-out infinite;
                animation-delay: -0.9s;
            }

            .dark .item-unlocked-indicator::before {
                box-shadow:
                    0 6px 13px 0 color-mix(in srgb, var(--primary-100) 14%, transparent),
                    0 24px 24px 0 color-mix(in srgb, var(--primary-100) 12%, transparent),
                    0 55px 33px 0 color-mix(in srgb, var(--primary-100) 7%, transparent),
                    0 97px 39px 0 color-mix(in srgb, var(--primary-100) 2%, transparent),
                    0 152px 43px 0 color-mix(in srgb, var(--primary-100) 0%, transparent);
            }

            .dark .item-unlocked-indicator::after {
                background: linear-gradient(112deg,
                        transparent 22%,
                        color-mix(in srgb, var(--primary-50) 68%, transparent) 50%,
                        transparent 78%);
            }

            .item-unlocked-indicator.fill-primary-500::before {
                animation-duration: 2.1s;
            }

            .item-unlocked-indicator.fill-primary-500::after {
                animation-duration: 2.1s;
            }

            .item-unlocked-indicator-icon {
                filter: saturate(1.06);
            }

            @keyframes main-menu-unlock-hover-echo {

                0%,
                40%,
                100% {
                    opacity: 0;
                }

                54% {
                    opacity: 0.68;
                }

                74% {
                    opacity: 0;
                }
            }

            @keyframes main-menu-unlock-shimmer {

                0%,
                44% {
                    opacity: 0;
                    transform: translateX(-175%) skewX(-18deg);
                }

                58% {
                    opacity: 0.84;
                }

                76% {
                    opacity: 0;
                    transform: translateX(175%) skewX(-18deg);
                }

                100% {
                    opacity: 0;
                    transform: translateX(175%) skewX(-18deg);
                }
            }
        </style>
    @endassets
@endonce

<div
    class="{{ twMerge([
        'item-unlocked-indicator' => !$locked,
        'flex h-20 w-20 cursor-pointer select-none items-center justify-center rounded-xl border border-transparent transition-colors will-change-[border-color,box-shadow]',
        $buttonClasses,
    ]) }}"
    data-main-menu-item
    data-caption="{{ $resolvedCaption }}"
    data-icon-name="{{ $iconName }}"
    data-on-click-callback="{{ $onClickCallback ?? '' }}"
    data-locked="{{ $locked ? 'true' : 'false' }}"
    x-data="{
        hovered: $useHover($el),
        isTouchActive: false,
        isTouching: false,
        isForcedActive: false,
        isLockedActive: false,
        get isActive() {
            return this.isForcedActive;
        },
        shadow: makeBoxShadowFromColor('--primary-500'),
        shadowDark: makeBoxShadowFromColor('--primary-100'),
        caption: @js($resolvedCaption),
        iconName: @js($iconName),
        onClickCallback: @js($onClickCallback),
        locked: @js($locked),
    }"
    x-on:main-menu-touch-state.window="
        isTouching = $event.detail?.isTouching ?? false;
        isTouchActive = $event.detail?.element === $el;
    "
    x-on:main-menu-active-state.window="isForcedActive = $event.detail?.element === $el"
    x-on:main-menu-lock-state.window="
        isLockedActive = $event.detail?.element === $el && $event.detail?.active;
    "
    x-on:mouseenter="
        if (caption) {
            $dispatch('main-menu-item-enter', { caption, iconName, onClickCallback, locked, element: $el, source: 'hover' });
        }
    "
    x-on:mouseleave="$dispatch('main-menu-item-leave')"
    x-on:click="
        if (caption) {
            $dispatch('main-menu-item-enter', { caption, iconName, onClickCallback, locked, element: $el, source: 'click' });
        }
        $dispatch('main-menu-item-click', { caption, iconName, onClickCallback, locked, element: $el });
    "
    x-on:focus="
        if (caption) {
            $dispatch('main-menu-item-enter', { caption, iconName, onClickCallback, locked, element: $el, source: 'focus' });
        }
    "
    x-on:blur="$dispatch('main-menu-item-leave')"
    x-effect="$el.style.boxShadow = isActive ? ($store.colorScheme.isDarkModeOn ? shadowDark : shadow) : 'none'"
    x-bind:class="{ 'fill-primary-500 dark:border-primary-200!': isActive }"
>
    <div class="relative flex h-8 w-8 items-center justify-center">
        <x-icon
            class="{{ twMerge('will-change-[transform,opacity,filter] relative z-10 fill-primary-500 dark:fill-primary-200 h-8 w-8 transform-gpu transition-[opacity,scale,filter]', $iconClasses) }}"
            x-bind:class="{
                'scale-[0.88]! opacity-[0.48] grayscale-[1]': containerHovered && !isActive,
                'opacity-0 scale-[0.92]': isLockedActive,
            }"
            :name="$iconName"
        />

        @if ($locked)
            <span
                class="{{ twMerge([
                    'item-unlocked-indicator-icon' => !$locked,
                    'will-change-[transform,opacity] absolute inset-0 z-0 flex h-8 w-8 items-center justify-center transform-gpu transition-[opacity,transform]',
                    $iconClasses,
                ]) }}"
                x-cloak
                x-data="{ ready: false }"
                x-init="() => setTimeout((ready = true), 100);"
                x-show="ready"
                x-bind:class="{
                    'opacity-100 scale-100': isLockedActive,
                    'opacity-0 scale-[0.85]': !isLockedActive,
                }"
            >
                <x-icon
                    class="fill-primary-500 dark:fill-primary-200 transform-fill h-8 w-8 origin-center"
                    data-lock-icon
                    :name="'entypo.lock'"
                />
            </span>
        @endif
    </div>
</div>
