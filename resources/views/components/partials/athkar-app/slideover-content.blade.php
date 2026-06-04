@props(['componentId', 'cards' => [], 'searchQuery' => '', 'isMobile' => false])

@php
    $isMobile = (bool) ($isMobile ?? false);
    $searchQuery = trim((string) ($searchQuery ?? ''));
    $hasActiveSearch = $searchQuery !== '';
@endphp

@assets
    <style>
        .athkar-manager-card {
            position: relative;
            transform: translateZ(0);
            overflow: hidden;
            backface-visibility: hidden;
            will-change: transform;
            display: flex;
            min-height: 27rem;
            cursor: pointer;
            border-radius: 1rem;
            border: 1px solid color-mix(in srgb, var(--primary-300) 45%, transparent);
            background: color-mix(in srgb, white 92%, transparent);
            box-shadow: 0 8px 20px color-mix(in srgb, var(--gray-900) 8%, transparent);
            transition: transform 180ms ease, box-shadow 180ms ease, border-color 180ms ease;
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
            -webkit-user-drag: none;
            touch-action: pan-y;
        }

        .athkar-manager-card:hover {
            box-shadow: 0 12px 24px color-mix(in srgb, var(--gray-900) 12%, transparent);
        }

        .athkar-manager--handle-only .athkar-manager-card {
            transition: box-shadow 120ms ease, border-color 120ms ease;
        }

        .athkar-manager--handle-only .athkar-manager-card:hover {
            transform: none;
            box-shadow: 0 8px 20px color-mix(in srgb, var(--gray-900) 8%, transparent);
        }

        .athkar-manager-card::after {
            content: '';
            position: absolute;
            left: var(--athkar-repel-x, 50%);
            top: var(--athkar-repel-y, 50%);
            width: 1rem;
            height: 1rem;
            pointer-events: none;
            border-radius: 9999px;
            opacity: 0;
            background:
                radial-gradient(circle,
                    color-mix(in srgb, var(--primary-500) 28%, transparent) 0%,
                    color-mix(in srgb, var(--primary-500) 16%, transparent) 48%,
                    transparent 72%);
            transform: translate(-50%, -50%) scale(0.1);
            will-change: transform, opacity;
        }

        .athkar-manager-card[data-athkar-press='hold']::after {
            animation: athkar-manager-card-repel-hold var(--athkar-repel-hold-duration, 700ms) linear forwards;
        }

        .athkar-manager-card[data-athkar-press='release']::after {
            animation: athkar-manager-card-repel-release var(--athkar-repel-release-duration, 220ms) cubic-bezier(0.1, 0.65, 0.2, 1) forwards;
        }

        @keyframes athkar-manager-card-repel-hold {
            0% {
                opacity: 0.34;
                transform: translate(-50%, -50%) scale(0.08);
            }

            72% {
                opacity: 0.24;
                transform: translate(-50%, -50%) scale(calc(var(--athkar-repel-scale, 24) * 0.52));
            }

            92% {
                opacity: 0.08;
                transform: translate(-50%, -50%) scale(calc(var(--athkar-repel-scale, 24) * 0.6));
            }

            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(calc(var(--athkar-repel-scale, 24) * 0.62));
            }
        }

        @keyframes athkar-manager-card-repel-release {
            0% {
                opacity: var(--athkar-repel-release-start-opacity, 0.3);
                transform: translate(-50%, -50%) scale(var(--athkar-repel-release-start-scale, 0.16));
            }

            78% {
                opacity: 0.08;
                transform: translate(-50%, -50%) scale(calc(var(--athkar-repel-scale, 24) * 0.92));
            }

            100% {
                opacity: 0;
                transform: translate(-50%, -50%) scale(var(--athkar-repel-scale, 24));
            }
        }

        .dark .athkar-manager-card {
            border-color: color-mix(in srgb, var(--primary-400) 45%, transparent);
            background: color-mix(in srgb, #2b2a30 74%, transparent);
        }

        .dark .athkar-manager-card::after {
            background:
                radial-gradient(circle,
                    color-mix(in srgb, var(--primary-300) 30%, transparent) 0%,
                    color-mix(in srgb, var(--primary-300) 18%, transparent) 48%,
                    transparent 72%);
        }

        .athkar-manager-card__click {
            display: flex;
            min-height: 27rem;
            width: 100%;
            flex-direction: column;
            gap: 0.75rem;
            padding: 1rem;
            text-align: start;
            outline: none;
        }

        .athkar-manager-card__click:focus-visible {
            box-shadow: inset 0 0 0 2px color-mix(in srgb, var(--primary-500) 45%, transparent);
        }

        .athkar-manager-card__text {
            display: flex;
            flex: 1 1 auto;
            min-height: 0;
            align-items: center;
            margin-top: -1rem;
            justify-content: center;
            padding-inline: 0.25rem;
            text-align: center;
            font-size: 1.08rem;
            line-height: 2.05;
            color: var(--gray-800);
        }

        @media (max-width: 639px) {
            .athkar-manager-card__text {
                display: flex;
                padding-bottom: 1rem;
                align-items: center;
                justify-content: center;
                height: 100%;
                line-height: 1.8;
            }
        }

        .dark .athkar-manager-card__text {
            color: var(--gray-100);
        }

        .athkar-manager-card__badge {
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            border-radius: 0.55rem;
            border: 1px solid transparent;
            padding: 0.25rem 0.5rem;
            font-size: 0.72rem;
            line-height: 1;
            font-weight: 600;
            white-space: nowrap;
        }

        .athkar-manager-card__badge--order {
            border-color: color-mix(in srgb, var(--gray-400) 50%, transparent);
            background: color-mix(in srgb, var(--gray-300) 16%, transparent);
            color: var(--gray-700);
        }

        .dark .athkar-manager-card__badge--order {
            border-color: color-mix(in srgb, var(--gray-600) 65%, transparent);
            background: color-mix(in srgb, var(--gray-700) 26%, transparent);
            color: var(--gray-100);
        }

        .athkar-manager-card__badge--time {
            border-color: color-mix(in srgb, var(--primary-500) 35%, transparent);
            background: color-mix(in srgb, var(--primary-500) 14%, transparent);
            color: var(--primary-700);
        }

        .dark .athkar-manager-card__badge--time {
            border-color: color-mix(in srgb, var(--primary-300) 45%, transparent);
            background: color-mix(in srgb, var(--primary-400) 22%, transparent);
            color: var(--primary-100);
        }

        .athkar-manager-card__badge--type {
            border-color: color-mix(in srgb, var(--gray-400) 45%, transparent);
            background: color-mix(in srgb, var(--gray-300) 14%, transparent);
            color: var(--gray-700);
        }

        .dark .athkar-manager-card__badge--type {
            border-color: color-mix(in srgb, var(--gray-600) 65%, transparent);
            background: color-mix(in srgb, var(--gray-700) 28%, transparent);
            color: var(--gray-100);
        }

        .athkar-manager-card__badge--origin {
            border-color: color-mix(in srgb, var(--info-500) 35%, transparent);
            background: color-mix(in srgb, var(--info-500) 13%, transparent);
            color: var(--info-700);
        }

        .dark .athkar-manager-card__badge--origin {
            border-color: color-mix(in srgb, var(--info-300) 45%, transparent);
            background: color-mix(in srgb, var(--info-400) 22%, transparent);
            color: var(--info-100);
        }

        .athkar-manager-card__badge--count {
            border-color: color-mix(in srgb, var(--gray-400) 45%, transparent);
            background: color-mix(in srgb, var(--gray-300) 18%, transparent);
            color: var(--gray-700);
        }

        .dark .athkar-manager-card__badge--count {
            border-color: color-mix(in srgb, var(--gray-600) 65%, transparent);
            background: color-mix(in srgb, var(--gray-700) 28%, transparent);
            color: var(--gray-100);
        }

        .athkar-manager-card__badge--override {
            border-color: color-mix(in srgb, var(--warning-500) 45%, transparent);
            background: color-mix(in srgb, var(--warning-500) 16%, transparent);
            color: var(--warning-700);
        }

        .dark .athkar-manager-card__badge--override {
            border-color: color-mix(in srgb, var(--warning-400) 55%, transparent);
            background: color-mix(in srgb, var(--warning-400) 22%, transparent);
            color: var(--warning-100);
        }

        .athkar-manager-card__drag-handle {
            cursor: grab;
            border-radius: 0.55rem;
            border: 1px solid color-mix(in srgb, var(--gray-400) 40%, transparent);
            background: color-mix(in srgb, var(--gray-300) 16%, transparent);
            padding: 0.3rem 0.45rem;
            color: var(--gray-700);
            touch-action: none;
            -webkit-tap-highlight-color: transparent;
        }

        .athkar-manager-card__drag-handle.athkar-manager-card__delete-button {
            cursor: pointer;
        }

        .dark .athkar-manager-card__drag-handle {
            border-color: color-mix(in srgb, var(--gray-600) 60%, transparent);
            background: color-mix(in srgb, var(--gray-700) 24%, transparent);
            color: var(--gray-100);
        }

        .athkar-manager-card * {
            user-select: none;
            -webkit-user-select: none;
            -webkit-touch-callout: none;
            -webkit-user-drag: none;
        }

        .athkar-manager-cards-grid {
            touch-action: pan-y;
        }

        .athkar-manager-cards-grid .sortable-ghost,
        .athkar-manager-cards-grid .sortable-chosen,
        .athkar-manager-cards-grid .sortable-drag {
            transition: none !important;
        }

        @media (hover: none),
        (pointer: coarse) {
            .athkar-manager-card {
                min-height: 20rem;
                transition: box-shadow 120ms ease, border-color 120ms ease;
            }

            .athkar-manager-card:hover {
                transform: none;
                box-shadow: 0 8px 20px color-mix(in srgb, var(--gray-900) 8%, transparent);
            }

            .athkar-manager-card__click {
                min-height: 20rem;
                padding: 0.875rem;
            }
        }
    </style>
@endassets

<div
    @class(['space-y-4', 'mt-12 sm:mt-0' => $isMobile])
    x-data="athkarAppManager({
        componentId: @js($componentId),
        nativeMobileRuntime: @js((bool) config('nativephp-internal.running', false) && is_platform('mobile')),
        hasActiveSearch: @js($hasActiveSearch),
    })"
    x-bind:class="{ 'athkar-manager--handle-only': shouldRestrictSortHandles() }"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            اسحب بطاقات الأذكار لإعادة ترتيبها، واضغط على أي بطاقة لتعديل محتوياتها.
        </p>

        <div class="flex flex-wrap items-center gap-2">
            <label class="relative min-w-[min(19rem,88vw)] flex-1">
                <x-filament::icon
                    class="inset-s-3 pointer-events-none absolute inset-y-0 my-auto h-4 w-4 text-gray-400"
                    icon="heroicon-o-magnifying-glass"
                />
                <input
                    class="focus:border-primary-500 focus:ring-primary-500/35 w-full rounded-lg border border-gray-300 bg-white/90 py-2 pe-10 ps-9 text-sm text-gray-800 transition dark:border-gray-700 dark:bg-gray-900/80 dark:text-gray-100"
                    type="search"
                    dir="rtl"
                    placeholder="{{ arabic_text('ابحث في نص الذكر...') }}"
                    wire:model.live.debounce.520ms="athkarSearchQuery"
                    x-on:keydown.enter.stop.prevent="$el.blur()"
                    x-on:search.stop.prevent="$el.blur()"
                />
                @if ($hasActiveSearch)
                    <button
                        class="inset-e-2 absolute inset-y-0 my-auto inline-flex h-6 w-6 items-center justify-center rounded-md text-gray-500 hover:text-gray-800 dark:text-gray-400 dark:hover:text-gray-100"
                        type="button"
                        wire:click="clearAthkarSearchQuery"
                    >
                        <x-filament::icon
                            class="h-4 w-4"
                            icon="heroicon-o-x-mark"
                        />
                    </button>
                @endif
            </label>

            <x-filament::button
                color="primary"
                icon="heroicon-s-pencil"
                size="sm"
                wire:click="openCreateAthkar"
                wire:loading.attr="disabled"
                wire:target="openCreateAthkar"
            >
                إضافة ذكر جديد
            </x-filament::button>

            <x-filament::button
                color="warning"
                icon="heroicon-o-arrow-path"
                size="sm"
                wire:click="openResetAthkarOverrides"
                wire:loading.attr="disabled"
                wire:target="openResetAthkarOverrides"
            >
                استعادة الأذكار الافتراضية
            </x-filament::button>
        </div>
    </div>

    <div
        class="athkar-manager-cards-grid flex flex-wrap content-start gap-4"
        dir="ltr"
        wire:key="athkar-manager-cards-grid"
        wire:sort="reorderAthkar"
        wire:sort:config="managerSortConfig()"
        x-cloak
        x-show="$wire.hasHydratedOverrides"
        x-transition.opacity.duration.200ms
    >
        @foreach ($cards as $card)
            <article
                class="athkar-manager-card athkar-manager-card__click flex-none basis-full sm:max-w-[calc((100%-1rem)/2)] sm:basis-[calc((100%-1rem)/2)] xl:max-w-[calc((100%-2rem)/3)] xl:basis-[calc((100%-2rem)/3)]"
                data-athkar-manager-card
                data-athkar-order-index="{{ $loop->index }}"
                data-athkar-card-id="{{ $card['id'] }}"
                style="view-transition-name: athkar-card-{{ $card['id'] }};"
                dir="rtl"
                wire:key="athkar-manager-card-{{ $card['id'] }}"
                wire:loading.class="pointer-events-none"
                wire:target="openEditAthkar({{ $card['id'] }})"
                wire:sort:item="{{ $card['id'] }}"
                x-on:contextmenu.prevent
            >
                <div class="flex items-center justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="athkar-manager-card__badge athkar-manager-card__badge--order"
                            title="ترتيب الذكر"
                        >#{{ $card['order'] }}</span>
                        <span
                            class="athkar-manager-card__badge athkar-manager-card__badge--time">{{ \App\Services\Enums\ThikrTime::labelFor($card['time']) }}</span>
                    </div>

                    <div class="flex items-center gap-2">
                        <button
                            class="athkar-manager-card__drag-handle athkar-manager-card__delete-button"
                            type="button"
                            title="حذف الذكر"
                            wire:sort:ignore
                            wire:click.stop="openDeleteAthkar({{ $card['id'] }})"
                            x-on:pointerdown.stop
                            x-on:click.stop
                        >
                            <x-filament::icon
                                class="text-danger-600 dark:text-danger-400 h-4 w-4"
                                icon="heroicon-o-x-mark"
                            />
                        </button>

                        <span
                            data-athkar-sort-handle
                            title="اسحب لإعادة الترتيب"
                            @class([
                                'opacity-45 pointer-events-none' => $hasActiveSearch,
                                'athkar-manager-card__drag-handle'
                            ])
                            wire:click.stop
                            x-on:click.stop
                        >
                            <x-filament::icon
                                class="h-4 w-4"
                                icon="heroicon-o-bars-3"
                            />
                        </span>
                    </div>
                </div>

                <p class="athkar-manager-card__text font-arabic-serif whitespace-pre-line">
                    {{ $card['text'] }}
                </p>

                <div class="mt-auto flex items-end justify-between gap-2">
                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="athkar-manager-card__badge athkar-manager-card__badge--type">{{ \App\Services\Enums\ThikrType::labelFor($card['type']) }}</span>
                        @if ($card['is_original'])
                            <span class="athkar-manager-card__badge athkar-manager-card__badge--origin">مأثور</span>
                        @endif
                    </div>

                    <div class="flex flex-wrap items-center justify-end gap-2">
                        <span class="athkar-manager-card__badge athkar-manager-card__badge--count">العدد:
                            {{ $card['count'] }}</span>
                        @if ($card['is_overridden'])
                            <span class="athkar-manager-card__badge athkar-manager-card__badge--override">مُعَدّل</span>
                        @endif
                    </div>
                </div>

                <div
                    class="backdrop-blur-xs pointer-events-none absolute inset-0 z-10 grid place-items-center rounded-2xl bg-white/55 opacity-0 transition-opacity duration-150 ease-out dark:bg-gray-900/45"
                    role="status"
                    aria-live="polite"
                    wire:loading.delay.class="opacity-100 pointer-events-auto"
                    wire:loading.delay.class.remove="opacity-0 pointer-events-none"
                    wire:target="openEditAthkar({{ $card['id'] }})"
                >
                    <div
                        class="border-primary-200 border-t-primary-600 dark:border-primary-500/40 dark:border-t-primary-200 h-10 w-10 animate-spin rounded-full border-2">
                    </div>
                    <span class="sr-only">جاري فتح الذكر...</span>
                </div>
            </article>
        @endforeach
    </div>
</div>
