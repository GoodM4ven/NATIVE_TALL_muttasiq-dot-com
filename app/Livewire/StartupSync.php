<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class StartupSync extends Component
{
    public function mount(): void
    {
        if (! is_platform('mobile')) {
            return;
        }

        $this->synchronizeSettingsVersion();
    }

    public function render(): View
    {
        return view('livewire.startup-sync');
    }

    public function placeholder(): View
    {
        return view('livewire.startup-sync');
    }

    private function synchronizeSettingsVersion(): void
    {
        // Native app builds are packaged with a fixed release version, so we
        // keep the baked version authoritative even when the settings API is reachable.
        $resolvedVersion = Setting::configuredAppVersion();
        Setting::setAppVersion($resolvedVersion);
        $url = $this->resolveSettingsApiUrl();
        $timeoutInSeconds = $this->resolveTimeoutInSeconds();
        $connectTimeoutInSeconds = min(2, $timeoutInSeconds);

        if ($url !== null) {
            try {
                /** @var Response $response */
                $response = Http::acceptJson()
                    ->connectTimeout($connectTimeoutInSeconds)
                    ->timeout($timeoutInSeconds)
                    ->get($url);

                if (! $response->successful()) {
                    Log::warning('Settings API returned non-success response during startup sync.', [
                        'status' => $response->status(),
                        'url' => $url,
                    ]);
                }
            } catch (\Throwable $exception) {
                Log::warning('Settings API request failed during startup sync.', [
                    'message' => $exception->getMessage(),
                    'url' => $url,
                ]);
            }
        }

        $this->dispatch('app-version-updated', version: $resolvedVersion);
        $this->dispatch('startup-sync-finished');
    }

    private function resolveSettingsApiUrl(): ?string
    {
        $configuredSettingsEndpoint = (string) config('app.custom.native_end_points.settings', 'settings');

        if (str_starts_with($configuredSettingsEndpoint, 'https://') || str_starts_with($configuredSettingsEndpoint, 'http://')) {
            return $configuredSettingsEndpoint;
        }

        $applicationUrl = rtrim((string) config('app.url'), '/');
        $applicationUrlScheme = parse_url($applicationUrl, PHP_URL_SCHEME);

        if (! is_string($applicationUrlScheme) || ! in_array(strtolower($applicationUrlScheme), ['http', 'https'], true)) {
            Log::warning('Skipping startup settings sync because APP_URL does not use an HTTP scheme.', [
                'app_url' => $applicationUrl,
            ]);

            return null;
        }

        $relativeSettingsPath = route('api.settings.index', [], false);

        return $applicationUrl.$relativeSettingsPath;
    }

    private function resolveTimeoutInSeconds(): int
    {
        $configuredTimeout = (int) config('app.custom.native_end_points.retries', 8);

        return max(2, min($configuredTimeout, 8));
    }
}
