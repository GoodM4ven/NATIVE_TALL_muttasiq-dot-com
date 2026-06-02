<?php

declare(strict_types=1);

use App\Models\Setting;
use App\Services\Support\Enums\ViewName;
use Illuminate\Support\Facades\Facade;
use Illuminate\Support\Facades\Schema;

if (! function_exists('arabic_text_runtime_ready')) {
    function arabic_text_runtime_ready(): bool
    {
        if (! class_exists(Facade::class)) {
            return false;
        }

        return Facade::getFacadeApplication() !== null;
    }
} else {
    throw new Exception('The function `arabic_text_runtime_ready` already exists.');
}

if (! function_exists('arabic_text_settings')) {
    /**
     * @return array{preserveHarakat: bool, useWesternNumerals: bool}
     */
    function arabic_text_settings(): array
    {
        static $resolved = null;
        static $resolving = false;
        $normalizeBoolean = static function (mixed $value, bool $fallback): bool {
            if (is_bool($value)) {
                return $value;
            }

            if ($value === 1 || $value === '1') {
                return true;
            }

            if ($value === 0 || $value === '0') {
                return false;
            }

            $normalized = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

            return $normalized ?? $fallback;
        };

        if (is_array($resolved)) {
            return $resolved;
        }

        $preserveHarakatDefault = true;
        $useWesternNumeralsDefault = true;
        $defaultSettings = [
            'preserveHarakat' => $preserveHarakatDefault,
            'useWesternNumerals' => $useWesternNumeralsDefault,
        ];

        try {
            if ($resolving) {
                return $defaultSettings;
            }

            if (! arabic_text_runtime_ready()) {
                $resolved = $defaultSettings;

                return $resolved;
            }

            if (! Schema::hasTable((new Setting)->getTable())) {
                $resolved = $defaultSettings;

                return $resolved;
            }

            $resolving = true;
            $storedValues = Setting::query()
                ->whereIn('name', [
                    Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY,
                    Setting::DOES_USE_WESTERN_NUMERALS,
                ])
                ->pluck('value', 'name')
                ->all();
            $resolved = [
                'preserveHarakat' => $normalizeBoolean(
                    $storedValues[Setting::DOES_PRESERVE_HARAKAT_IN_DISPLAY] ?? null,
                    $preserveHarakatDefault,
                ),
                'useWesternNumerals' => $normalizeBoolean(
                    $storedValues[Setting::DOES_USE_WESTERN_NUMERALS] ?? null,
                    $useWesternNumeralsDefault,
                ),
            ];
        } catch (Throwable) {
            $resolved = $defaultSettings;
        } finally {
            $resolving = false;
        }

        return $resolved;
    }
} else {
    throw new Exception('The function `arabic_text_settings` already exists.');
}

if (! function_exists('arabic_text')) {
    function arabic_text(
        string $text,
        ?bool $preserveHarakat = null,
        ?bool $useWesternNumerals = null,
    ): string {
        if (! arabic_text_runtime_ready()) {
            return $text;
        }

        $processedText = $text;
        $settings = arabic_text_settings();
        $shouldPreserveHarakat = $preserveHarakat ?? $settings['preserveHarakat'];
        $shouldUseWesternNumerals = $useWesternNumerals ?? $settings['useWesternNumerals'];

        if (! $shouldPreserveHarakat) {
            $processedText = camel_dediac_ar($processedText);
        }

        $processedText = strtr($processedText, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);

        if ($shouldUseWesternNumerals) {
            return $processedText;
        }

        return strtr($processedText, [
            '0' => '٠',
            '1' => '١',
            '2' => '٢',
            '3' => '٣',
            '4' => '٤',
            '5' => '٥',
            '6' => '٦',
            '7' => '٧',
            '8' => '٨',
            '9' => '٩',
        ]);
    }
} else {
    throw new Exception('The function `arabic_text` already exists.');
}

if (! function_exists('view_title')) {
    function view_title(ViewName $viewName): string
    {
        $appName = __('custom/general.app_name');
        $title = match ($viewName) {
            ViewName::MainMenu => 'الرئيسية',
            ViewName::AthkarAppGate => 'الأذكار',
            ViewName::AthkarAppSabah => 'أذكار الصباح',
            ViewName::AthkarAppMasaa => 'أذكار المساء',
            ViewName::QuranAppGate => 'الكتاب',
            ViewName::QuranAppTilawa => 'تلاوة الكتاب',
            ViewName::QuranAppHifth => 'حفظ الكتاب',
            ViewName::QuranAppTadabbur => 'تدبّر الكتاب',
        };

        return arabic_text("$appName | $title");
    }
} else {
    throw new Exception('The function `view_title` already exists.');
}
