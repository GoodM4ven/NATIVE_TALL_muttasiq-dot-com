<?php

use App\Models\Thikr;
use App\Services\Enums\ThikrType;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

use function Pest\Laravel\get;

it('fetches athkar from the remote api on mobile', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
        'app.custom.native_end_points.athkar' => 'athkar',
        'app.custom.native_end_points.retries' => 2,
    ]);

    $payload = [
        [
            'id' => 1,
            'time' => 'sabah',
            'type' => ThikrType::Glorification->value,
            'text' => 'Test athkar',
            'origin' => null,
            'is_aayah' => false,
            'is_original' => false,
            'count' => 1,
            'order' => 1,
        ],
    ];

    Http::fake([
        route('api.athkar.index') => Http::response(['athkar' => $payload]),
    ]);

    $response = get('/');

    $response->assertSuccessful();
    $response->assertViewHas('athkar', $payload);

    Http::assertSent(function (HttpRequest $request): bool {
        return $request->url() === route('api.athkar.index');
    });
});

it('uses local athkar payload on non-mobile requests', function () {
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

    Http::assertNothingSent();
});

it('falls back to local athkar payload on mobile when api request fails', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
        'app.custom.native_end_points.athkar' => 'athkar',
        'app.custom.native_end_points.retries' => 2,
    ]);

    $thikr = Thikr::factory()->create([
        'type' => ThikrType::Protection,
        'origin' => null,
    ]);

    Http::fake([
        route('api.athkar.index') => Http::failedConnection(),
    ]);

    $response = get('/');

    $response->assertSuccessful();
    $response->assertViewHas('athkar', function (array $athkar) use ($thikr): bool {
        return collect($athkar)->contains(function (array $item) use ($thikr): bool {
            return $item['id'] === $thikr->id
                && $item['type'] === ThikrType::Protection->value
                && $item['is_original'] === false
                && $item['origin'] === null;
        });
    });

    Http::assertSent(function (HttpRequest $request): bool {
        return $request->url() === route('api.athkar.index');
    });
});
