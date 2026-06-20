<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;
use Livewire\Blaze\Blaze;
use Livewire\Livewire;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Telegram\Provider as TelegramProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->disableViteHotFileWhenUnavailable();

        Model::unguard();

        Livewire::useScriptTagAttributes(['defer' => true]);

        $this->configureNativeIosUrlGeneration();

        Blaze::optimize()->in(resource_path('views/components'));

        Event::listen(function (SocialiteWasCalled $event): void {
            $event->extendSocialite('telegram', TelegramProvider::class);
        });
    }

    private function disableViteHotFileWhenUnavailable(): void
    {
        if (! config('nativephp-internal.running')) {
            return;
        }

        $hotFile = Vite::hotFile();

        if (! is_file($hotFile)) {
            return;
        }

        $hotUrl = trim((string) file_get_contents($hotFile));

        if ($hotUrl === '') {
            return;
        }

        $host = parse_url($hotUrl, PHP_URL_HOST);

        if (! is_string($host) || $host === '') {
            return;
        }

        $scheme = parse_url($hotUrl, PHP_URL_SCHEME) ?: 'http';
        $port = parse_url($hotUrl, PHP_URL_PORT) ?: ($scheme === 'https' ? 443 : 80);

        $timeoutSeconds = 0.2;
        $connection = @fsockopen($host, (int) $port, $errno, $error, $timeoutSeconds);

        if (is_resource($connection)) {
            fclose($connection);

            return;
        }

        Vite::useHotFile(storage_path('framework/vite.hot.disabled'));
    }

    private function configureNativeIosUrlGeneration(): void
    {
        if (! config('nativephp-internal.running')) {
            return;
        }

        $platform = strtolower((string) config('nativephp-internal.platform', ''));
        if ($platform !== 'ios') {
            return;
        }

        URL::forceRootUrl('php://127.0.0.1');
        URL::forceScheme('php');
    }
}
