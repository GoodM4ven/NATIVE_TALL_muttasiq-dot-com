<?php

declare(strict_types=1);

namespace App\Services\Traits;

use App\Models\Setting;
use Filament\Forms\Components;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Illuminate\Support\HtmlString;

trait HasControlPanelSettingsTab
{
    /**
     * @return array<string, bool|int|string>
     */
    public static function controlPanelDefaults(): array
    {
        $defaults = Setting::defaults();

        unset(
            $defaults[Setting::MINIMUM_MAIN_TEXT_SIZE],
            $defaults[Setting::MAXIMUM_MAIN_TEXT_SIZE],
        );

        return $defaults;
    }

    protected function controlPanelSettingsTab(): Tab
    {
        $athkarDefinitions = Setting::definitionsForGroup(Setting::GROUP_ATHKAR);
        $generalDefinitions = Setting::definitionsForGroup(Setting::GROUP_GENERAL);
        $quranDefinitions = Setting::definitionsForGroup(Setting::GROUP_QURAN);

        return Tab::make('settings')
            ->label(arabic_text('الإعدادات'))
            ->key('settings')
            ->icon('heroicon-s-adjustments-horizontal')
            ->schema([
                Text::make(arabic_text('العامة'))
                    ->color('black')
                    ->weight(FontWeight::Medium),

                Grid::make()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Components\Checkbox::make(Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS)
                            ->default((bool) ($generalDefinitions[Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS]['default'] ?? false))
                            ->extraFieldWrapperAttributes([
                                'class' => 'quran-support-lock-target relative mt-1 sm:mt-3 md:mt-0',
                                'data-support-lock-target' => 'enable-visual-enhancements',
                                'data-support-lock-caption' => arabic_text('هذا الخيار يحتاج تأكيد دعم المشروع'),
                                'x-on:pointerdown.capture' => 'if (!$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                                'x-on:click.capture' => 'if (!$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                                'x-on:keydown.capture' => 'if (![`Enter`, ` `].includes($event.key) || !$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                            ])
                            ->label($generalDefinitions[Setting::DOES_ENABLE_VISUAL_ENHANCEMENTS]['label']),

                        Components\Checkbox::make(Setting::DOES_SKIP_GUIDANCE_PANELS)
                            ->default((bool) ($generalDefinitions[Setting::DOES_SKIP_GUIDANCE_PANELS]['default'] ?? false))
                            ->extraFieldWrapperAttributes(['class' => 'relative mt-3 sm:mt-0'])
                            ->label($generalDefinitions[Setting::DOES_SKIP_GUIDANCE_PANELS]['label']),

                        Components\Checkbox::make(Setting::DOES_USE_WESTERN_NUMERALS)
                            ->default((bool) ($generalDefinitions[Setting::DOES_USE_WESTERN_NUMERALS]['default'] ?? true))
                            ->extraFieldWrapperAttributes(['class' => 'relative mt-3 sm:mt-0'])
                            ->label($generalDefinitions[Setting::DOES_USE_WESTERN_NUMERALS]['label']),

                        Components\Checkbox::make(Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY)
                            ->default((bool) ($generalDefinitions[Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY]['default'] ?? true))
                            ->extraFieldWrapperAttributes(['class' => 'relative mt-0 sm:mt-0'])
                            ->label($generalDefinitions[Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY]['label']),

                        Components\Checkbox::make(Setting::DOES_QURAN_USE_VOLUME_BUTTONS_NAVIGATION)
                            ->default((bool) ($generalDefinitions[Setting::DOES_QURAN_USE_VOLUME_BUTTONS_NAVIGATION]['default'] ?? false))
                            ->visible(fn (): bool => is_platform('native'))
                            ->extraFieldWrapperAttributes([
                                'class' => 'relative mt-3 sm:mt-0 quran-volume-navigation-field',
                            ])
                            ->label($generalDefinitions[Setting::DOES_QURAN_USE_VOLUME_BUTTONS_NAVIGATION]['label'])
                            ->belowContent([
                                Text::make((string) ($generalDefinitions[Setting::DOES_QURAN_USE_VOLUME_BUTTONS_NAVIGATION]['help'] ?? ''))->size(TextSize::ExtraSmall),
                            ]),

                    ]),

                Text::make(new HtmlString('<hr class="border-0 h-px bg-linear-to-r from-transparent via-gray-400 to-transparent mt-5">'))
                    ->extraAttributes(['class' => 'w-full']),

                Text::make(arabic_text('القرآن'))
                    ->color('black')
                    ->weight(FontWeight::Medium),

                Grid::make()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                    ])
                    ->schema([
                        Components\Checkbox::make(Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT)
                            ->default((bool) ($quranDefinitions[Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT]['default'] ?? false))
                            ->label($quranDefinitions[Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT]['label'])
                            ->belowContent([
                                Text::make((string) ($quranDefinitions[Setting::DOES_QURAN_TARGET_WORDS_BY_DEFAULT]['help'] ?? ''))->size(TextSize::ExtraSmall),
                            ]),

                        Components\Checkbox::make(Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY)
                            ->default((bool) ($quranDefinitions[Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY]['default'] ?? true))
                            ->label($quranDefinitions[Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY]['label'])
                            ->belowContent([
                                Text::make((string) ($quranDefinitions[Setting::DOES_QURAN_PRESERVE_HARAKAT_ON_COPY]['help'] ?? ''))->size(TextSize::ExtraSmall),
                            ]),

                        Components\Checkbox::make(Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY)
                            ->default((bool) ($quranDefinitions[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY]['default'] ?? true))
                            ->label($quranDefinitions[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY]['label'])
                            ->belowContent([
                                Text::make((string) ($quranDefinitions[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ON_MULTI_COPY]['help'] ?? ''))->size(TextSize::ExtraSmall),
                            ]),

                        Components\Checkbox::make(Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY)
                            ->default((bool) ($quranDefinitions[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY]['default'] ?? false))
                            ->label($quranDefinitions[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY]['label'])
                            ->belowContent([
                                Text::make((string) ($quranDefinitions[Setting::DOES_QURAN_APPEND_SURAH_AFFIX_ALWAYS_ON_COPY]['help'] ?? ''))->size(TextSize::ExtraSmall),
                            ]),

                        FusedGroup::make([
                            Components\Radio::make(Setting::QURAN_WIRD_FREQUENCY_MODE)
                                ->default((int) ($quranDefinitions[Setting::QURAN_WIRD_FREQUENCY_MODE]['default'] ?? Setting::QURAN_WIRD_FREQUENCY_MONTHLY))
                                ->label($quranDefinitions[Setting::QURAN_WIRD_FREQUENCY_MODE]['label'])
                                ->options(Setting::quranWirdFrequencyModeOptions())
                                ->inline()
                                ->live()
                                ->extraFieldWrapperAttributes([
                                    'class' => 'quran-support-lock-target quran-wird-frequency-field quran-wird-frequency-field--hide-label',
                                    'data-support-lock-target' => 'wird-frequency-mode',
                                    'data-support-lock-caption' => arabic_text('هذا الخيار يحتاج تأكيد دعم المشروع'),
                                    'x-on:pointerdown.capture' => 'if (!$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                                    'x-on:click.capture' => 'if (!$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                                    'x-on:keydown.capture' => 'if (![`Enter`, ` `].includes($event.key) || !$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                                ])
                                ->extraAttributes(['class' => 'quran-wird-frequency-options'])
                                ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                                    $maximum = Setting::quranWirdKhatmatMaxForFrequency((int) $state);
                                    $current = (int) $get(Setting::QURAN_WIRD_KHATMAT_TARGET);

                                    if ($current > $maximum) {
                                        $set(Setting::QURAN_WIRD_KHATMAT_TARGET, $maximum);
                                    }
                                })
                                ->helperText($quranDefinitions[Setting::QURAN_WIRD_FREQUENCY_MODE]['help'] ?? null)
                                ->columnSpan(1),

                            Components\Select::make(Setting::QURAN_WIRD_KHATMAT_TARGET)
                                ->default((int) ($quranDefinitions[Setting::QURAN_WIRD_KHATMAT_TARGET]['default'] ?? 1))
                                ->label($quranDefinitions[Setting::QURAN_WIRD_KHATMAT_TARGET]['label'])
                                ->options(
                                    fn (Get $get): array => Setting::quranWirdKhatmatOptionsForFrequency(
                                        (int) $get(Setting::QURAN_WIRD_FREQUENCY_MODE),
                                    ),
                                )
                                ->native(false)
                                ->live()
                                ->extraFieldWrapperAttributes([
                                    'class' => 'quran-support-lock-target quran-wird-khatmat-field',
                                    'data-support-lock-target' => 'wird-khatmat-target',
                                    'data-support-lock-caption' => arabic_text('هذا الخيار يحتاج تأكيد دعم المشروع'),
                                    'x-on:pointerdown.capture' => 'if (!$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                                    'x-on:click.capture' => 'if (!$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                                    'x-on:keydown.capture' => 'if (![`Enter`, ` `].includes($event.key) || !$el.classList.contains(`quran-support-lock-target--locked`)) { return; } $event.preventDefault(); $event.stopPropagation(); window.dispatchEvent(new CustomEvent(`open-support-unlock-modal`));',
                                ])
                                ->extraAttributes(['class' => 'quran-wird-khatmat-select'])
                                ->selectablePlaceholder(false)
                                ->helperText(
                                    fn (Get $get): string => $this->wirdKhatmatHelperText(
                                        (int) $get(Setting::QURAN_WIRD_FREQUENCY_MODE),
                                        (string) ($quranDefinitions[Setting::QURAN_WIRD_KHATMAT_TARGET]['help'] ?? ''),
                                    ),
                                )
                                ->columnSpan(1),
                        ])
                            ->label($quranDefinitions[Setting::QURAN_WIRD_FREQUENCY_MODE]['label'])
                            ->extraAttributes(['class' => 'quran-wird-group-field'])
                            ->columns(2)
                            ->columnSpanFull(),

                        Components\Checkbox::make(Setting::DOES_QURAN_SHOW_IMMERSIVE_MOBILE_EDGE_CAPTIONS)
                            ->default((bool) ($quranDefinitions[Setting::DOES_QURAN_SHOW_IMMERSIVE_MOBILE_EDGE_CAPTIONS]['default'] ?? true))
                            ->extraFieldWrapperAttributes([
                                'class' => 'relative sm:hidden',
                            ])
                            ->label($quranDefinitions[Setting::DOES_QURAN_SHOW_IMMERSIVE_MOBILE_EDGE_CAPTIONS]['label']),
                    ]),

                Text::make(new HtmlString('<hr class="border-0 h-px bg-linear-to-r from-transparent via-gray-400 to-transparent mt-5">'))
                    ->extraAttributes(['class' => 'w-full']),

                Text::make(arabic_text('الأذكار'))
                    ->color('black')
                    ->weight(FontWeight::Medium),

                Grid::make()
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        Components\Checkbox::make(Setting::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR)
                            ->default((bool) ($athkarDefinitions[Setting::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR]['default'] ?? true))
                            ->label($athkarDefinitions[Setting::DOES_AUTOMATICALLY_SWITCH_COMPLETED_ATHKAR]['label']),

                        Components\Checkbox::make(Setting::DOES_CLICKING_SWITCH_ATHKAR_TOO)
                            ->default((bool) ($athkarDefinitions[Setting::DOES_CLICKING_SWITCH_ATHKAR_TOO]['default'] ?? true))
                            ->label($athkarDefinitions[Setting::DOES_CLICKING_SWITCH_ATHKAR_TOO]['label'])
                            ->belowContent([
                                Text::make((string) ($athkarDefinitions[Setting::DOES_CLICKING_SWITCH_ATHKAR_TOO]['help'] ?? ''))->size(TextSize::ExtraSmall),
                            ]),

                        Components\Checkbox::make(Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION)
                            ->default((bool) ($athkarDefinitions[Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION]['default'] ?? true))
                            ->label($athkarDefinitions[Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION]['label'])
                            ->belowContent([
                                Text::make((string) ($athkarDefinitions[Setting::DOES_PREVENT_SWITCHING_ATHKAR_UNTIL_COMPLETION]['help'] ?? ''))->size(TextSize::ExtraSmall),
                            ]),
                    ]),
            ]);
    }

    private function wirdKhatmatHelperText(int $frequencyMode, string $baseHelp): string
    {
        $maximum = Setting::quranWirdKhatmatMaxForFrequency($frequencyMode);
        $limitSummary = $frequencyMode === Setting::QURAN_WIRD_FREQUENCY_DAILY
            ? arabic_text('الحد الأقصى في الوضع اليومي: 4 ختمات.')
            : arabic_text(sprintf('الحد الأقصى في الوضع الشهري: %d ختمة.', $maximum));

        $normalizedBaseHelp = trim($baseHelp);

        if ($normalizedBaseHelp === '') {
            return $limitSummary;
        }

        return arabic_text(sprintf('%s %s', $normalizedBaseHelp, $limitSummary));
    }
}
