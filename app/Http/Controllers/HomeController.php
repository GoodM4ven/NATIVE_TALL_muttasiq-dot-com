<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Setting;
use App\Models\Thikr;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class HomeController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): View
    {
        if (is_platform('mobile')) {
            Setting::setAppVersion(Setting::configuredAppVersion());
        }

        $settingsPayload = $this->resolveLocalSettingsPayload();

        return view('home', [
            'athkar' => Thikr::defaultsPayload(),
            'athkarSettings' => $settingsPayload['settings'],
            'athkarMainTextSizeLimits' => $settingsPayload['mainTextSizeLimits'],
        ]);
    }

    /**
     * @return array{settings: array<string, bool|int|string>, mainTextSizeLimits: array<string, array{min: int, max: int, default: int}>}
     */
    private function resolveLocalSettingsPayload(): array
    {
        $settingDefaults = Setting::defaults();
        $storedSettings = Setting::storedValues(array_keys($settingDefaults));

        return [
            'settings' => Setting::normalizeSettings(
                array_replace($settingDefaults, $storedSettings),
            ),
            'mainTextSizeLimits' => Setting::mainTextSizeLimits(),
        ];
    }
}
