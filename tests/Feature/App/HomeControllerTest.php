<?php

use App\Models\Setting;
use App\Models\Thikr;
use App\Services\Enums\ThikrType;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\get;

function copyrightVersionShellClasses(string $content): string
{
    $matched = preg_match(
        '/<div\s+class="([^"]*)"\s+data-testid="copyright-version-shell"/',
        $content,
        $matches,
    );

    expect($matched)->toBe(1);

    return $matches[1];
}

function homeMainClasses(string $content): string
{
    $matched = preg_match(
        '/<main\s+class="([^"]*)">/',
        $content,
        $matches,
    );

    expect($matched)->toBe(1);

    return $matches[1];
}

function buttonsStackClasses(string $content): string
{
    $matched = preg_match(
        '/<div\b[^>]*x-bind:data-respecting-stack=[^>]*>/',
        $content,
        $matches,
    );

    expect($matched)->toBe(1);

    $classMatched = preg_match(
        '/\sclass="([^"]*)"/',
        $matches[0],
        $classMatches,
    );

    if ($classMatched !== 1) {
        return '';
    }

    return $classMatches[1];
}

function buttonsStackCount(string $content): int
{
    $matched = preg_match_all(
        '/<div\b[^>]*x-bind:data-respecting-stack=[^>]*>/',
        $content,
    );

    expect($matched)->not->toBeFalse();

    return (int) $matched;
}

it('uses local athkar payload and runtime-specific shell/layout classes without remote sync', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
        'app.custom.native_end_points.athkar' => 'athkar',
        'app.custom.native_end_points.settings' => 'settings',
    ]);

    Http::fake();

    $thikr = Thikr::factory()->create([
        'type' => ThikrType::Supplication,
        'origin' => 'مرجع',
    ]);

    $response = get('/');

    $response->assertSuccessful();
    $response->assertViewHas('athkar', function (array $athkar) use ($thikr): bool {
        return collect($athkar)->contains(function (array $item) use ($thikr): bool {
            return $item['id'] === $thikr->id
                && $item['type'] === ThikrType::Supplication->value
                && $item['is_original'] === true
                && $item['origin'] === 'مرجع';
        });
    });

    $content = $response->getContent();
    $shellClasses = copyrightVersionShellClasses($content);

    expect($shellClasses)
        ->toContain('bottom-7')
        ->not->toContain('bottom-3')
        ->not->toContain('mb-7');

    expect($content)->toContain('data-testid="startup-sync-shield"')
        ->toContain('data-testid="startup-sync-component"')
        ->toContain('startup-sync-resolved');

    Http::assertNothingSent();
    $response->assertDontSee('data-testid="native-startup-loader"', false);

    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'desktop',
    ]);

    Http::fake();

    $thikr = Thikr::factory()->create([
        'type' => ThikrType::Supplication,
        'origin' => 'مرجع',
    ]);

    $response = get('/');

    $response->assertSuccessful();
    $response->assertViewHas('athkar', function (array $athkar) use ($thikr): bool {
        return collect($athkar)->contains(function (array $item) use ($thikr): bool {
            return $item['id'] === $thikr->id
                && $item['type'] === ThikrType::Supplication->value
                && $item['is_original'] === true
                && $item['origin'] === 'مرجع';
        });
    });

    $shellClasses = copyrightVersionShellClasses($response->getContent());

    expect($shellClasses)
        ->toContain('bottom-4')
        ->not->toContain('bottom-3')
        ->not->toContain('bottom-7');

    Http::assertNothingSent();
    config([
        'nativephp-internal.platform' => 'ios',
    ]);

    $iosResponse = get('/');
    $iosResponse->assertSuccessful();

    expect(homeMainClasses($iosResponse->getContent()))
        ->toContain('mt-22')
        ->not->toContain('mt-16');
    expect(buttonsStackClasses($iosResponse->getContent()))->toContain('mt-8');
    expect(buttonsStackCount($iosResponse->getContent()))->toBe(1);

    config([
        'nativephp-internal.platform' => 'android',
    ]);

    $androidResponse = get('/');
    $androidResponse->assertSuccessful();

    expect(homeMainClasses($androidResponse->getContent()))
        ->toContain('mt-15')
        ->not->toContain('mt-22');
    expect(buttonsStackClasses($androidResponse->getContent()))->not->toContain('mt-8');
    expect(buttonsStackCount($androidResponse->getContent()))->toBe(1);
});

it('renders expected icon and markup contracts while resetting app version to configured runtime value', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
        'app.custom.app_version' => '3.1.4',
    ]);

    Setting::setAppVersion('0.9.0');

    $response = get('/');

    $response->assertSuccessful()
        ->assertSee('v3.1.4', false)
        ->assertSee('athkar-origin-indicator__icon', false);

    expect(Setting::appVersion())->toBe('3.1.4');

    $content = $response->getContent();

    expect(substr_count($content, 'athkar-origin-indicator__icon'))->toBeGreaterThanOrEqual(2)
        ->and($content)->not->toContain('-left-px -top-px')
        ->and($content)->toContain('relative grid h-8 w-8 rotate-45 place-items-center overflow-hidden')
        ->and($content)->toContain('absolute top-1/2 left-1/2 h-6 w-6 -translate-x-1/2 -translate-y-1/2 -rotate-45 shrink-0')
        ->and($content)->toContain('data-testid="main-menu-insights-trigger"')
        ->and($content)->toContain('data-testid="main-menu-insights-panel"')
        ->and($content)->toContain('data-quran-app-reader-root');
});
