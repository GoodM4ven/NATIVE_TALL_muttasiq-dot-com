<?php

declare(strict_types=1);

use App\Models\Thikr;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;

use function Pest\Laravel\getJson;

it('returns cached athkar in order', function () {
    RateLimiter::for('athkar', fn (Request $request): Limit => Limit::none());

    Thikr::factory()->count(3)->create();

    $response = getJson(route('api.athkar.index'));

    $response->assertSuccessful();

    $expected = Thikr::query()
        ->ordered()
        ->get(['id', 'time', 'text', 'count', 'order'])
        ->map(fn (Thikr $thikr): array => $thikr->toAthkarArray())
        ->all();

    expect($response->json('athkar'))->toBe($expected);
});

it('rate limits the athkar endpoint', function () {
    RateLimiter::for('athkar', fn (Request $request): Limit => Limit::perMinute(2)->by('test'));

    Thikr::factory()->create();

    getJson(route('api.athkar.index'))->assertSuccessful();
    getJson(route('api.athkar.index'))->assertSuccessful();
    getJson(route('api.athkar.index'))->assertTooManyRequests();
});

it('refreshes cached athkar after updating and reordering defaults', function () {
    RateLimiter::for('athkar', fn (Request $request): Limit => Limit::none());

    $first = Thikr::factory()->create(['text' => 'First', 'count' => 1, 'is_aayah' => false]);
    $second = Thikr::factory()->create(['text' => 'Second', 'count' => 1, 'is_aayah' => false]);

    getJson(route('api.athkar.index'))->assertSuccessful();

    $first->update(['text' => 'First (edited)']);
    Thikr::setNewOrder([$second->id, $first->id]);

    $response = getJson(route('api.athkar.index'));

    $response->assertSuccessful();

    expect(
        collect($response->json('athkar'))
            ->whereIn('id', [$first->id, $second->id])
            ->pluck('id')
            ->values()
            ->all(),
    )->toBe([$second->id, $first->id]);

    expect(collect($response->json('athkar'))->firstWhere('id', $first->id)['text'])
        ->toBe('First (edited)');
});

it('normalizes aayah text when toggling whether it is an aayah', function () {
    $thikr = Thikr::factory()->create([
        'is_aayah' => true,
        'text' => 'الحمد لله',
    ]);

    expect($thikr->fresh()->text)
        ->toBe(Thikr::AAYAH_OPENING_MARK.'الحمد لله'.Thikr::AAYAH_CLOSING_MARK);

    $thikr->update([
        'is_aayah' => false,
    ]);

    expect($thikr->fresh()->text)->toBe('الحمد لله');
});
