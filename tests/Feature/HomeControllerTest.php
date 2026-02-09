<?php

use App\Models\Thikr;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Support\Facades\Http;

it('fetches athkar from the remote api on mobile', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'android',
        'services.athkar.base_url' => 'https://muttasiq.com',
        'services.athkar.timeout' => 2,
    ]);

    $payload = [
        [
            'id' => 1,
            'time' => 'sabah',
            'text' => 'Test athkar',
            'count' => 1,
            'order' => 1,
        ],
    ];

    Http::fake([
        'https://muttasiq.com/api/athkar' => Http::response(['athkar' => $payload]),
    ]);

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewHas('athkar', $payload);

    Http::assertSent(function (HttpRequest $request): bool {
        return $request->url() === 'https://muttasiq.com/api/athkar';
    });
});

it('uses local athkar payload on non-mobile requests', function () {
    config([
        'nativephp-internal.running' => true,
        'nativephp-internal.platform' => 'desktop',
    ]);

    Http::fake();

    $thikr = Thikr::factory()->create();

    $response = $this->get('/');

    $response->assertSuccessful();
    $response->assertViewHas('athkar', function (array $athkar) use ($thikr): bool {
        return collect($athkar)->contains(
            fn (array $item): bool => $item['id'] === $thikr->id
        );
    });

    Http::assertNothingSent();
});
