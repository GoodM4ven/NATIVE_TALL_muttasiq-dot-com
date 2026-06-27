<?php

declare(strict_types=1);

use Native\Mobile\Facades\System;

if (! function_exists('is_platform')) {
    function is_platform(string $platform): bool
    {
        $platform = strtolower($platform);

        $nativeRuntime = (bool) config('nativephp-internal.running', false);
        $nativePlatform = strtolower((string) config('nativephp-internal.platform', ''));

        if ($nativeRuntime && $nativePlatform === '') {
            if (System::isAndroid()) {
                $nativePlatform = 'android';
            } elseif (System::isIos()) {
                $nativePlatform = 'ios';
            }
        }

        $isAndroid = $nativePlatform === 'android';
        $isIos = $nativePlatform === 'ios';
        $isMobile = $isAndroid || $isIos;
        $isNative = $nativeRuntime || $isMobile;
        $isWeb = ! $isNative;
        $isDesktop = $isNative && ! $isMobile;

        return match ($platform) {
            'android' => $isAndroid,
            'ios' => $isIos,
            'mobile' => $isMobile,
            'native' => $isNative,
            'web' => $isWeb,
            'desktop' => $isDesktop,
            default => throw new InvalidArgumentException('Unrecognized platform.'),
        };
    }
} else {
    throw new Exception('The function `is_platform` already exists.');
}

if (! function_exists('is_native_bootstrap_runtime')) {
    function is_native_bootstrap_runtime(): bool
    {
        $nativeRunning = strtolower((string) getenv('NATIVEPHP_RUNNING'));
        $nativePlatform = strtolower((string) getenv('NATIVEPHP_PLATFORM'));

        if (in_array($nativeRunning, ['1', 'true', 'yes'], true)) {
            return true;
        }

        return in_array($nativePlatform, ['android', 'ios'], true);
    }
} else {
    throw new Exception('The function `is_native_bootstrap_runtime` already exists.');
}

if (! function_exists('native_server_base')) {
    /**
     * Resolve the public server's scheme://host[:port] that the native runtime
     * should talk to (where Telegram auth + account sync live), derived from the
     * configured native endpoints. Returns null if none are usable.
     */
    function native_server_base(): ?string
    {
        foreach ([
            config('app.custom.native_end_points.settings'),
            config('app.custom.native_end_points.telegram_auth'),
            config('app.url'),
        ] as $endpoint) {
            $endpoint = trim((string) $endpoint);

            if ($endpoint === '') {
                continue;
            }

            $scheme = parse_url($endpoint, PHP_URL_SCHEME);
            $host = parse_url($endpoint, PHP_URL_HOST);

            if (is_string($scheme) && is_string($host) && $scheme !== '' && $host !== '') {
                $port = parse_url($endpoint, PHP_URL_PORT);

                return $scheme.'://'.$host.($port !== null ? ':'.$port : '');
            }
        }

        return null;
    }
} else {
    throw new Exception('The function `native_server_base` already exists.');
}

if (! function_exists('open_link_native_aware')) {
    function open_link_native_aware(string $url): string
    {
        if (is_platform('mobile')) {
            return "(window.browser?.open ? await window.browser.open(`{$url}`) : window.open(`{$url}`, `_blank`, `noopener`))";
        }

        return "window.open(`{$url}`, `_blank`, `noopener`)";
    }
} else {
    throw new Exception('The function `open_link_native_aware` already exists.');
}
