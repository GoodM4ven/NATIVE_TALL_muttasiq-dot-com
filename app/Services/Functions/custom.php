<?php

declare(strict_types=1);

use App\Services\Enums\ViewName;

if (! function_exists('view_title')) {
    function view_title(ViewName $viewName): string
    {
        $appName = __('custom/general.app_name');
        $title = match ($viewName) {
            ViewName::MainMenu => 'الرئيسية',
            ViewName::AthkarAppGate => 'الأذكار',
            ViewName::AthkarAppSabah => 'أذكار الصباح',
            ViewName::AthkarAppMasaa => 'أذكار المساء',
        };

        return "$appName | $title";
    }
} else {
    throw new Exception('The function `view_title` already exists.');
}
