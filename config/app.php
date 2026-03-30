<?php

declare(strict_types=1);
use Filament\Support\Colors\Color;

return [

    'custom' => [

        'app_description' => 'تطبيق يعين على الإسلام والالتزام باتساق ويسر بإذن الله...',
        'app_keywords' => 'islam, muslim, thikr, athkar',
        'app_version' => env('NATIVEPHP_APP_VERSION'),

        'admin_path' => env('ADMIN_PATH', 'admin'),

        'user' => [
            'name' => env('ADMIN_NAME'),
            'email' => env('ADMIN_EMAIL'),
            'password' => env('ADMIN_PASSWORD'),
        ],

        'native_end_points' => [
            'retries' => 8,
            'athkar' => env('NATIVE_ATHKAR_ENDPOINT', 'https://muttasiq.com/api/athkar'),
            'settings' => env('NATIVE_SETTINGS_ENDPOINT', 'https://muttasiq.com/api/settings'),
            'js_error_reports' => env('NATIVE_JS_ERROR_REPORTS_ENDPOINT', 'https://muttasiq.com/api/js-error-reports'),
        ],

        'security' => [
            'unmatched_routes' => [
                'window_seconds' => (int) env('UNMATCHED_ROUTE_WINDOW_SECONDS', 900),
                'alert_threshold' => (int) env('UNMATCHED_ROUTE_ALERT_THRESHOLD', 25),
                'alert_repeat_every' => (int) env('UNMATCHED_ROUTE_ALERT_REPEAT_EVERY', 25),
            ],
            'web_home_metrics' => [
                'enabled' => (bool) env('WEB_HOME_METRICS_ENABLED', env('APP_ENV') === 'production'),
                'cache_store' => env('WEB_HOME_METRICS_CACHE_STORE', 'database'),
                'chart_days' => (int) env('WEB_HOME_METRICS_CHART_DAYS', 14),
                'retention_days' => (int) env('WEB_HOME_METRICS_RETENTION_DAYS', 35),
            ],
        ],

        'colors' => [
            'gray' => Color::Slate,
            'primary' => [
                50 => '#ebf0f1',
                100 => '#d8e1e4',
                200 => '#a8bcc3',
                300 => '#7696a1',
                400 => '#416e7d',
                500 => '#0a4457',
                600 => '#083745',
                700 => '#062934',
                800 => '#05212b',
                900 => '#041b22',
                950 => '#020f14',
            ],
            'success' => Color::Emerald,
            'info' => Color::Purple,
            'warning' => [
                50 => '#fff9ef',
                100 => '#fef4df',
                200 => '#fee6b8',
                300 => '#fdd88f',
                400 => '#fcc964',
                500 => '#fbb937',
                600 => '#c9932c',
                700 => '#966f21',
                800 => '#7b5b1b',
                900 => '#634916',
                950 => '#392a0d',
            ],
            'danger' => Color::Rose,
        ],

        'filament' => [
            'color_overrides' => [
                'primary' => [
                    'dark' => '#4A6972',
                ],
            ],
            'background_colors' => [
                'shell' => [
                    'light' => '#FFFFFF', // ! Settled
                    'dark' => '#201f25', // ! Settled
                ],
                'surface' => [
                    'light' => '#FFFFFF', // ! Settled
                    'dark' => '#201f25', // ! Settled
                ],
                'surface_raised' => [
                    'light' => '#FFFFFF',
                    'dark' => '#201f25',
                ],
                'surface_muted' => [
                    'light' => '#FFFFFF',
                    'dark' => '#201f25',
                ],
            ],
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application, which will be used when the
    | framework needs to place the application's name in a notification or
    | other UI elements where an application name needs to be displayed.
    |
    */

    'name' => env('APP_NAME', 'Muttasiq'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    'browser_test_fast_mode' => (bool) env('BROWSER_TEST_FAST_MODE', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | the application so that it's available within Artisan commands.
    |
    */

    'url' => env('APP_URL', 'http://127.0.0.1:8000'),

    'asset_url' => env('ASSET_URL'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. The timezone
    | is set to "UTC" by default as it is suitable for most use cases.
    |
    */

    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by Laravel's translation / localization methods. This option can be
    | set to any locale for which you plan to have translation strings.
    |
    */

    'locale' => env('APP_LOCALE', 'en'),

    'fallback_locale' => env('APP_FALLBACK_LOCALE', 'en'),

    'faker_locale' => env('APP_FAKER_LOCALE', 'en_US'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is utilized by Laravel's encryption services and should be set
    | to a random, 32 character string to ensure that all encrypted values
    | are secure. You should do this prior to deploying the application.
    |
    */

    'cipher' => 'AES-256-CBC',

    'key' => env('APP_KEY'),

    'previous_keys' => [
        ...array_filter(
            explode(',', (string) env('APP_PREVIOUS_KEYS', ''))
        ),
    ],

    /*
    |--------------------------------------------------------------------------
    | Maintenance Mode Driver
    |--------------------------------------------------------------------------
    |
    | These configuration options determine the driver used to determine and
    | manage Laravel's "maintenance mode" status. The "cache" driver will
    | allow maintenance mode to be controlled across multiple machines.
    |
    | Supported drivers: "file", "cache"
    |
    */

    'maintenance' => [
        'driver' => env('APP_MAINTENANCE_DRIVER', 'file'),
        'store' => env('APP_MAINTENANCE_STORE', 'database'),
    ],

];
