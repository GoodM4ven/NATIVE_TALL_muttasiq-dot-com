<?php

use App\Providers\AppServiceProvider;
use App\Providers\AthkarAppServiceProvider;
use App\Providers\FilamentServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\InereshServiceProvider;
use App\Providers\LazyCssServiceProvider;
use App\Providers\NativeServiceProvider;
use App\Providers\RateLimitServiceProvider;
use Goodm4ven\NativePatches\NativePatchesServiceProvider;
use SocialiteProviders\Manager\ServiceProvider as SocialiteManagerServiceProvider;

return [
    AppServiceProvider::class,
    AthkarAppServiceProvider::class,
    FilamentServiceProvider::class,
    FortifyServiceProvider::class,
    NativePatchesServiceProvider::class,
    InereshServiceProvider::class,
    LazyCssServiceProvider::class,
    NativeServiceProvider::class,
    RateLimitServiceProvider::class,
    SocialiteManagerServiceProvider::class,
];
