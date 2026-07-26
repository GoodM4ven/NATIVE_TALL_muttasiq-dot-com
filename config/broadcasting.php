<?php

declare(strict_types=1);

$appUrl = parse_url((string) env('APP_URL', 'http://localhost'));
$publicScheme = is_array($appUrl) ? ($appUrl['scheme'] ?? 'http') : 'http';
$publicHost = is_array($appUrl) ? ($appUrl['host'] ?? 'localhost') : 'localhost';
$publicPort = is_array($appUrl)
    ? ($appUrl['port'] ?? ($publicScheme === 'https' ? 443 : 80))
    : 80;
$reverbScheme = (string) env('REVERB_SCHEME', 'http');

return [

    'default' => env('BROADCAST_CONNECTION', 'reverb'),

    'connections' => [

        'reverb' => [
            'driver' => 'reverb',
            'key' => env('REVERB_APP_KEY'),
            'secret' => env('REVERB_APP_SECRET'),
            'app_id' => env('REVERB_APP_ID'),
            'options' => [
                'host' => env('REVERB_HOST', '127.0.0.1'),
                'port' => env('REVERB_PORT', 8080),
                'scheme' => $reverbScheme,
                'useTLS' => $reverbScheme === 'https',
            ],
            'client_options' => [],

            // Public connection details the browser uses to reach Reverb. Injected
            // into the page at runtime (see home.blade) instead of being baked at
            // build time, so the SAME built assets connect to whatever host the
            // dev tunnel exposes (e.g. a Tailscale Funnel) — defaults to APP_URL
            // when the public_* overrides are unset.
            'public' => [
                'key' => env('REVERB_APP_KEY'),
                'host' => env('REVERB_PUBLIC_HOST', $publicHost),
                'port' => (int) env('REVERB_PUBLIC_PORT', $publicPort),
                'scheme' => env('REVERB_PUBLIC_SCHEME', $publicScheme),
            ],
        ],

        'log' => [
            'driver' => 'log',
        ],

        'null' => [
            'driver' => 'null',
        ],

    ],

];
