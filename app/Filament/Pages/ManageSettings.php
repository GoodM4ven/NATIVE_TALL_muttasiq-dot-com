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
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
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
     * @return array<int, Components\Checkbox|Components\TextInput>
     */
    private function buildFieldsFromDefinitions(array $definitions): array
    {
        $fields = [];

        foreach ($definitions as $name => $definition) {
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

        return $fields;
    }
}
