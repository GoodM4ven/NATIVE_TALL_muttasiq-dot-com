<?php

declare(strict_types=1);

it('uses the lara-stacker reverb defaults without duplicate environment settings', function (): void {
    $appUrl = parse_url((string) config('app.url'));
    $publicScheme = is_array($appUrl) ? ($appUrl['scheme'] ?? 'http') : 'http';
    $publicHost = is_array($appUrl) ? ($appUrl['host'] ?? 'localhost') : 'localhost';
    $publicPort = is_array($appUrl)
        ? ($appUrl['port'] ?? ($publicScheme === 'https' ? 443 : 80))
        : 80;

    expect(config('broadcasting.connections.reverb.options'))->toMatchArray([
        'host' => '127.0.0.1',
        'port' => 8080,
        'scheme' => 'http',
        'useTLS' => false,
    ]);
    expect(config('broadcasting.connections.reverb.public'))->toMatchArray([
        'host' => $publicHost,
        'port' => $publicPort,
        'scheme' => $publicScheme,
    ]);
    expect(config('reverb.apps.apps.0.allowed_origins'))->toBe([$publicHost]);
    expect(config('reverb.servers.reverb.pulse_ingest_interval'))->toBeInt();
    expect(config('reverb.servers.reverb.telescope_ingest_interval'))->toBeInt();
});
