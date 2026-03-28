<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Setting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component as SchemaComponent;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\FusedGroup;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class ManageSettings extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?int $navigationSort = 99;

    protected static ?string $title = 'إعدادات التطبيق الافتراضية';

    protected static ?string $slug = 'iedadat-iftiradiyya';

    protected static ?string $navigationLabel = 'الإعدادات الافتراضية';

    protected string $view = 'filament.pages.manage-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public function mount(): void
    {
        $storedSettings = Setting::query()
            ->whereIn('name', array_keys(Setting::defaults()))
            ->pluck('value', 'name')
            ->all();

        $currentSettings = Setting::normalizeSettings(
            array_replace(Setting::defaults(), $storedSettings),
        );
        $currentSettings[Setting::APP_VERSION] = Setting::appVersion();

        $this->form->fill($currentSettings);
    }

    public function form(Schema $schema): Schema
    {
        $generalDefinitions = Setting::definitionsForGroup(Setting::GROUP_GENERAL);
        $athkarDefinitions = Setting::definitionsForGroup(Setting::GROUP_ATHKAR);
        $quranDefinitions = Setting::definitionsForGroup(Setting::GROUP_QURAN);

        return $schema
            ->components([
                Form::make([
                    Section::make('التطبيق')
                        ->schema([
                            Components\TextInput::make(Setting::APP_VERSION)
                                ->label('نسخة التطبيق المعروضة')
                                ->maxLength(32)
                                ->placeholder((string) config('app.custom.app_version')),
                        ]),

                    Section::make('العامة')
                        ->schema(
                            $this->buildFieldsFromDefinitions($generalDefinitions),
                        ),

                    Section::make('الأذكار')
                        ->schema(
                            $this->buildFieldsFromDefinitions($athkarDefinitions),
                        ),

                    Section::make('القرآن')
                        ->schema(
                            $this->buildFieldsFromDefinitions($quranDefinitions),
                        ),
                ])
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('حفظ الإعدادات')
                                ->submit('save'),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $normalized = Setting::normalizeSettings($data);

        if (array_key_exists(Setting::APP_VERSION, $data)) {
            Setting::setAppVersion($data[Setting::APP_VERSION]);
        }

        foreach ($normalized as $name => $value) {
            Setting::query()->updateOrCreate(
                ['name' => $name],
                ['value' => is_bool($value) ? (int) $value : $value],
            );
        }

        Notification::make()
            ->success()
            ->title('تم حفظ الإعدادات بنجاح')
            ->send();
    }

    /**
     * @param  array<string, array{default: bool|int, label: string, group: string, type: 'boolean'|'integer', help?: string, min?: int, max?: int}>  $definitions
     * @return array<int, SchemaComponent>
     */
    private function buildFieldsFromDefinitions(array $definitions): array
    {
        $fields = [];
        $hasWirdFrequency = array_key_exists(Setting::QURAN_WIRD_FREQUENCY_MODE, $definitions);
        $hasWirdKhatmat = array_key_exists(Setting::QURAN_WIRD_KHATMAT_TARGET, $definitions);

        foreach ($definitions as $name => $definition) {
            if (
                $name === Setting::QURAN_WIRD_FREQUENCY_MODE ||
                $name === Setting::QURAN_WIRD_KHATMAT_TARGET
            ) {
                continue;
            }

            if ($definition['type'] === 'boolean') {
                $fields[] = Components\Checkbox::make($name)
                    ->label($definition['label']);
            }

            if ($definition['type'] === 'integer') {
                $field = Components\TextInput::make($name)
                    ->label($definition['label'])
                    ->numeric()
                    ->minValue($definition['min'] ?? 0)
                    ->maxValue($definition['max'] ?? 100);

                $fields[] = $field;
            }
        }

        if ($hasWirdFrequency && $hasWirdKhatmat) {
            $wirdFrequencyDefinition = $definitions[Setting::QURAN_WIRD_FREQUENCY_MODE];
            $wirdKhatmatDefinition = $definitions[Setting::QURAN_WIRD_KHATMAT_TARGET];

            $fields[] = FusedGroup::make([
                Components\Radio::make(Setting::QURAN_WIRD_FREQUENCY_MODE)
                    ->label($wirdFrequencyDefinition['label'])
                    ->options(Setting::quranWirdFrequencyModeOptions())
                    ->inline()
                    ->live()
                    ->afterStateUpdated(function (Set $set, Get $get, mixed $state): void {
                        $maximum = Setting::quranWirdKhatmatMaxForFrequency((int) $state);
                        $current = (int) $get(Setting::QURAN_WIRD_KHATMAT_TARGET);

                        if ($current > $maximum) {
                            $set(Setting::QURAN_WIRD_KHATMAT_TARGET, $maximum);
                        }
                    })
                    ->helperText($wirdFrequencyDefinition['help'] ?? null)
                    ->columnSpan(1),

                Components\Select::make(Setting::QURAN_WIRD_KHATMAT_TARGET)
                    ->label($wirdKhatmatDefinition['label'])
                    ->options(
                        fn (Get $get): array => Setting::quranWirdKhatmatOptionsForFrequency(
                            (int) $get(Setting::QURAN_WIRD_FREQUENCY_MODE),
                        ),
                    )
                    ->native(false)
                    ->live()
                    ->selectablePlaceholder(false)
                    ->helperText(
                        fn (Get $get): string => $this->wirdKhatmatHelperText(
                            (int) $get(Setting::QURAN_WIRD_FREQUENCY_MODE),
                            $wirdKhatmatDefinition['help'] ?? '',
                        ),
                    )
                    ->columnSpan(1),
            ])
                ->label('إعداد الوِرد')
                ->columns(2);
        }

        return $fields;
    }

    private function wirdKhatmatHelperText(int $frequencyMode, string $baseHelp): string
    {
        $maximum = Setting::quranWirdKhatmatMaxForFrequency($frequencyMode);
        $limitSummary = $frequencyMode === Setting::QURAN_WIRD_FREQUENCY_DAILY
            ? 'الحد الأقصى في الوضع اليومي: 4 ختمات.'
            : sprintf('الحد الأقصى في الوضع الشهري لهذا الشهر: %d ختمة.', $maximum);

        $normalizedBaseHelp = trim($baseHelp);

        if ($normalizedBaseHelp === '') {
            return $limitSummary;
        }

        return sprintf('%s %s', $normalizedBaseHelp, $limitSummary);
    }
}
