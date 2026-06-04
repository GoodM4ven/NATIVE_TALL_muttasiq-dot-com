@php
    $resolveFilamentBackground = static fn($value, string $fallback): string => is_string($value) && $value !== ''
        ? $value
        : $fallback;

    $fiShellBackgroundLight = $resolveFilamentBackground(
        config('app.custom.filament.background_colors.shell.light'),
        theme_color('background', '#fef7ff'),
    );
    $fiShellBackgroundDark = $resolveFilamentBackground(
        config('app.custom.filament.background_colors.shell.dark'),
        theme_color('background-dark', '#201f25'),
    );

    $fiSurfaceBackgroundLight = $resolveFilamentBackground(
        config('app.custom.filament.background_colors.surface.light'),
        theme_color('gray-50', '#f9fafb'),
    );
    $fiSurfaceBackgroundDark = $resolveFilamentBackground(
        config('app.custom.filament.background_colors.surface.dark'),
        theme_color('gray-900', '#111827'),
    );

    $fiSurfaceRaisedBackgroundLight = $resolveFilamentBackground(
        config('app.custom.filament.background_colors.surface_raised.light'),
        $fiSurfaceBackgroundLight,
    );
    $fiSurfaceRaisedBackgroundDark = $resolveFilamentBackground(
        config('app.custom.filament.background_colors.surface_raised.dark'),
        $fiSurfaceBackgroundDark,
    );

    $fiSurfaceMutedBackgroundLight = $resolveFilamentBackground(
        config('app.custom.filament.background_colors.surface_muted.light'),
        $fiSurfaceBackgroundLight,
    );
    $fiSurfaceMutedBackgroundDark = $resolveFilamentBackground(
        config('app.custom.filament.background_colors.surface_muted.dark'),
        $fiSurfaceBackgroundDark,
    );
@endphp

<style>
    :root {
        --fi-shell-bg-light: {{ $fiShellBackgroundLight }};
        --fi-shell-bg-dark: {{ $fiShellBackgroundDark }};
        --fi-surface-bg-light: {{ $fiSurfaceBackgroundLight }};
        --fi-surface-bg-dark: {{ $fiSurfaceBackgroundDark }};
        --fi-surface-raised-bg-light: {{ $fiSurfaceRaisedBackgroundLight }};
        --fi-surface-raised-bg-dark: {{ $fiSurfaceRaisedBackgroundDark }};
        --fi-surface-muted-bg-light: {{ $fiSurfaceMutedBackgroundLight }};
        --fi-surface-muted-bg-dark: {{ $fiSurfaceMutedBackgroundDark }};
    }

    .nativephp-ios.mobile-platform .fi-modal-window {
        margin-top: 0 !important;
    }
</style>
