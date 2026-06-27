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

        // Surfaced here, after the data-override blinker reload, so the
        // notification renders on the fresh page rather than flashing away
        // during the fade (the live notifications component would otherwise
        // pull it from the session before the reload).
        $overrideNotice = session()->pull('data-branch-override-notice');
        $nativeAuthRestart = (bool) session()->pull('auth.native_restart', false);
        $nativeAuthRestoreToken = session()->pull('auth.native_restore_token');

        if (is_string($overrideNotice) && $overrideNotice !== '') {
            notify('heroicon-o-circle-stack', $overrideNotice);
        }

        $settingsPayload = $this->resolveLocalSettingsPayload();

        return view('home', [
            'athkar' => Thikr::cachedDefaults(),
            'athkarSettings' => $settingsPayload['settings'],
            'athkarMainTextSizeLimits' => $settingsPayload['mainTextSizeLimits'],
            'currentAppVersion' => Setting::appVersion(),
            'nativeAuthRestart' => $nativeAuthRestart,
            'nativeAuthRestoreToken' => is_string($nativeAuthRestoreToken) ? $nativeAuthRestoreToken : null,
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
