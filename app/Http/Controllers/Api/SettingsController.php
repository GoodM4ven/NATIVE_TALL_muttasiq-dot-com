<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\JsonResponse;

class SettingsController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $settingDefaults = Setting::defaults();
        $storedSettings = Setting::storedValues(array_keys($settingDefaults));

        return response()->json([
            'settings' => Setting::normalizeSettings(
                array_replace($settingDefaults, $storedSettings),
            ),
            'mainTextSizeLimits' => Setting::mainTextSizeLimits(),
            'appVersion' => Setting::appVersion(),
        ]);
    }
}
