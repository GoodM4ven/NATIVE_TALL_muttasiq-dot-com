@php
    $isMobile = (bool) ($isMobile ?? false);
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
            touch-action: pan-y;
        }

        .athkar-manager-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 24px color-mix(in srgb, var(--gray-900) 12%, transparent);
        }

        .athkar-manager-card:active {
            cursor: pointer;
            border-radius: 0 !important;
        }

        .dark .athkar-manager-card {
            border-color: color-mix(in srgb, var(--primary-400) 45%, transparent);
            background: color-mix(in srgb, var(--primary-950) 74%, transparent);
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
            justify-content: center;
            padding-inline: 0.25rem;
            text-align: center;
            font-size: 1.08rem;
            line-height: 1.95;
            color: var(--gray-800);
        }

        @media (max-width: 639px) {
            .athkar-manager-card__text {
                display: flex;
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
        }

        .athkar-manager-card__drag-handle:active {
            cursor: grabbing;
            border-radius: 0 !important;
        }

        .dark .athkar-manager-card__drag-handle {
            border-color: color-mix(in srgb, var(--gray-600) 60%, transparent);
            background: color-mix(in srgb, var(--gray-700) 24%, transparent);
            color: var(--gray-100);
        }

        .athkar-manager-card * {
            user-select: none;
            -webkit-user-select: none;
        }

        .athkar-manager-cards-grid {
            touch-action: pan-y;
        }
    </style>
@endassets

<div
    @class(['space-y-4', 'mt-12 sm:mt-0' => $isMobile])
    x-data="athkarAppManager({ componentId: @js($componentId) })"
>
    <div class="flex flex-wrap items-center justify-between gap-3">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            اسحب بطاقات الأذكار لإعادة ترتيبها، واضغط على أي بطاقة لتعديل محتوياتها.
        </p>

        <div class="flex flex-wrap items-center gap-2">
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
        class="athkar-manager-cards-grid grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3"
        wire:key="athkar-manager-cards-grid"
        wire:sort="reorderAthkar"
        x-cloak
        x-show="$wire.hasHydratedOverrides"
        x-transition.opacity.duration.200ms
    >
        @foreach ($cards as $card)
            <article
                class="athkar-manager-card athkar-manager-card__click"
                data-athkar-manager-card
                style="view-transition-name: athkar-card-{{ $card['id'] }};"
                wire:key="athkar-manager-card-{{ $card['id'] }}"
                wire:sort:item="{{ $card['id'] }}"
                wire:click.preserve-scroll="openEditAthkar({{ $card['id'] }})"
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
                            class="athkar-manager-card__drag-handle"
                            type="button"
                            title="حذف الذكر"
                            wire:sort:ignore
                            wire:click.stop="openDeleteAthkar({{ $card['id'] }})"
                            x-on:pointerdown.stop
                            x-on:click.stop
                        >
                            <x-filament::icon
                                class="text-danger-600 dark:text-danger-400 h-4 w-4"
                                icon="heroicon-o-trash"
                            />
                        </button>

                        <span
                            class="athkar-manager-card__drag-handle"
                            title="اسحب لإعادة الترتيب"
                            wire:click.stop
                            x-on:pointerdown.stop
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
            </article>
        @endforeach
    </div>
</div>
