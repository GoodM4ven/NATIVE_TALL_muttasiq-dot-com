<?php

declare(strict_types=1);

namespace App\Services\Traits;

use App\Models\Setting;
use Filament\Actions\Action;
use Filament\Forms\Components;
use Filament\Schemas\Components\Fieldset;
use Filament\Schemas\Components\Text;
use Filament\Support\Enums\TextSize;

trait HasSettings
{
    /**
     * @var array<string, bool>
     */
    public array $clientSettings = [];

    /**
     * @return array<string, bool>
     */
    public static function settingsDefaults(): array
    {
        return [
            'does_automatically_switch_completed_athkar' => true,
            'does_clicking_switch_athkar_too' => true,
            'does_prevent_switching_athkar_until_completion' => true,
            'does_skip_notice_panels' => false,
        ];
    }

    public function settingsAction(): Action
    {
        $settingsKeysCollection = collect(self::settingsDefaults())->keys();

        return Action::make('settings')
            ->label('الإعدادات')
            ->modalDescription('وبعض التفضيلات في كيفية عمل التطبيق')
            ->modalSubmitActionLabel('حفظ')
            ->fillForm(fn (): array => $this->loadSettings())
            ->schema([
                Fieldset::make('الأذكار')
                    ->columns([
                        'default' => 1,
                        'md' => 2,
                        'xl' => 3,
                    ])
                    ->schema([
                        // * Auto-navigate
                        Components\Checkbox::make($settingsKeysCollection->get(0))
                            ->default(true)
                            ->label('1. الانتقال التلقائي عند اكتمال عدد الذكر.'),

                        // * Click to navigate also, not just swipe
                        Components\Checkbox::make($settingsKeysCollection->get(1))
                            ->default(true)
                            ->label('2. الضغط والنقر يقوم بالانتقال أيضا للذكر التالي، وليس مجرد السحب فحسب.')
                            ->belowContent([
                                Text::make('ولكن إن قمت بالعودة للأذكار التامة، أو كان الخيار الأذكار (1) معطلا، فالضغط يقوم بزيادة العدّ.')->size(TextSize::ExtraSmall),
                            ]),

                        // * Prevent navigating forward to unreached athar or revisting completed modes
                        Components\Checkbox::make($settingsKeysCollection->get(2))
                            ->default(true)
                            ->label('3. المنع من الانتقال بين الأذكار حتى إنهائها أولًا.')
                            ->belowContent([
                                Text::make('وكذلك يقوم بالسماح بإعادة استعراض أذكار الصباح والمساء حتى عند إتمامها.')->size(TextSize::ExtraSmall),
                            ]),

                        // * Skip notice panels
                        Components\Checkbox::make($settingsKeysCollection->get(3))
                            ->default(false)
                            ->label('4. تجاوز رسائل التعريف أو التهنئة وما شابه.'),
                    ]),
            ])
            ->action(function (array $data): void {
                $savedSettings = [];

                foreach (self::settingsDefaults() as $name => $default) {
                    $value = array_key_exists($name, $data) ? (bool) $data[$name] : $default;
                    $savedSettings[$name] = $value;
                }

                $this->clientSettings = $savedSettings;
                $this->dispatch('settings-updated', settings: $savedSettings);

                notify(iconName: 'mdi.content-save-check', title: 'تم حفظ الإعدادات بنجاح');
            });
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    public function syncClientSettings(array $settings): void
    {
        $this->clientSettings = $this->filterSettings($settings);
    }

    /**
     * @return array<string, bool>
     */
    private function loadSettings(): array
    {
        $storedSettings = Setting::query()
            ->whereIn('name', array_keys(self::settingsDefaults()))
            ->pluck('value', 'name')
            ->all();

        $defaults = array_replace(self::settingsDefaults(), $storedSettings);

        return array_replace($defaults, $this->clientSettings);
    }

    /**
     * @param  array<string, mixed>  $settings
     * @return array<string, bool>
     */
    private function filterSettings(array $settings): array
    {
        $filtered = array_intersect_key($settings, self::settingsDefaults());

        foreach ($filtered as $key => $value) {
            $filtered[$key] = (bool) $value;
        }

        return $filtered;
    }
}
