<?php

declare(strict_types=1);

use App\Livewire\AthkarManager;
use App\Models\Thikr;
use App\Services\Enums\ThikrTime;
use App\Services\Enums\ThikrType;

use function Pest\Livewire\livewire;

it('loads default athkar cards for the manager', function () {
    $defaultText = 'manager-default-'.uniqid();

    $thikr = Thikr::factory()->create([
        'is_aayah' => false,
        'text' => $defaultText,
        'type' => ThikrType::Repentance,
        'origin' => 'reference',
    ]);

    $cards = livewire(AthkarManager::class)->instance()->defaultAthkarCards();

    expect(collect($cards)->contains(fn (array $card): bool => $card['text'] === $defaultText))->toBeTrue()
        ->and(collect($cards)->contains(fn (array $card): bool => $card['id'] === $thikr->id))->toBeTrue()
        ->and(collect($cards)->contains(fn (array $card): bool => array_key_exists('is_aayah', $card)))->toBeTrue()
        ->and(collect($cards)->contains(fn (array $card): bool => $card['type'] === ThikrType::Repentance->value))->toBeTrue()
        ->and(collect($cards)->contains(fn (array $card): bool => $card['is_original'] === true))->toBeTrue();
});

it('opens athkar manager in slide-over mode on non-mobile breakpoints', function () {
    livewire(AthkarManager::class)
        ->call('openManageAthkar', false)
        ->assertSet('isManageAthkarMobile', false)
        ->assertSet('mountedActions.0.name', 'manageAthkar');
});

it('opens athkar manager in modal mode on mobile breakpoints', function () {
    livewire(AthkarManager::class)
        ->call('openManageAthkar', true)
        ->assertSet('isManageAthkarMobile', true)
        ->assertSet('mountedActions.0.name', 'manageAthkar');
});

it('mounts the edit action when opening a card from the manager', function () {
    $component = livewire(AthkarManager::class)
        ->call('openManageAthkar', false);

    $cardId = (int) collect($component->instance()->resolvedAthkarCards())
        ->pluck('id')
        ->first();

    expect($cardId)->toBeGreaterThan(0);

    $component->call('openEditAthkar', $cardId);

    $mountedActionNames = collect($component->get('mountedActions'))
        ->pluck('name')
        ->filter()
        ->values()
        ->all();

    expect($mountedActionNames)->toContain('editAthkar');
});

it('configures manage action as slide-over on desktop and fullscreen modal on mobile', function () {
    $desktopComponent = livewire(AthkarManager::class)
        ->set('isManageAthkarMobile', false)
        ->instance();
    $desktopAction = $desktopComponent->manageAthkarAction();

    $mobileComponent = livewire(AthkarManager::class)
        ->set('isManageAthkarMobile', true)
        ->instance();
    $mobileAction = $mobileComponent->manageAthkarAction();

    expect($desktopAction->isModalSlideOver())->toBeTrue()
        ->and($desktopAction->getModalWidth()->value)->toBe('7xl')
        ->and($mobileAction->isModalSlideOver())->toBeFalse()
        ->and($mobileAction->getModalWidth()->value)->toBe('screen');
});

it('resolves defaults with local overrides and marks overridden cards', function () {
    $first = Thikr::factory()->create([
        'time' => ThikrTime::Sabah,
        'type' => ThikrType::Glorification,
        'text' => 'ذكر افتراضي أول',
        'origin' => null,
        'count' => 2,
        'is_aayah' => false,
    ]);

    $second = Thikr::factory()->create([
        'time' => ThikrTime::Masaa,
        'type' => ThikrType::Supplication,
        'text' => 'ذكر افتراضي ثان',
        'origin' => 'مصدر',
        'count' => 3,
        'is_aayah' => false,
    ]);

    Thikr::setNewOrder([$first->id, $second->id]);

    $component = livewire(AthkarManager::class)
        ->set('athkarOverrides', [
            [
                'thikr_id' => $first->id,
                'order' => 5,
                'time' => ThikrTime::Masaa->value,
                'type' => ThikrType::Repentance->value,
                'text' => 'ذكر مخصص أول',
                'origin' => 'مرجع مخصص',
                'count' => 8,
                'is_deleted' => false,
            ],
        ])
        ->instance();

    $cards = $component->resolvedAthkarCards();

    $firstCard = collect($cards)->firstWhere('id', $first->id);
    $secondCard = collect($cards)->firstWhere('id', $second->id);

    expect($firstCard)->not->toBeNull()
        ->and($firstCard['order'])->toBe(5)
        ->and($firstCard['time'])->toBe(ThikrTime::Masaa->value)
        ->and($firstCard['type'])->toBe(ThikrType::Repentance->value)
        ->and($firstCard['text'])->toBe('ذكر مخصص أول')
        ->and($firstCard['origin'])->toBe('مرجع مخصص')
        ->and($firstCard['count'])->toBe(8)
        ->and($firstCard['is_original'])->toBeTrue()
        ->and($firstCard['is_overridden'])->toBeTrue();

    expect($secondCard)->not->toBeNull()
        ->and($secondCard['time'])->toBe(ThikrTime::Masaa->value)
        ->and($secondCard['type'])->toBe(ThikrType::Supplication->value)
        ->and($secondCard['origin'])->toBe('مصدر')
        ->and($secondCard['is_original'])->toBeTrue()
        ->and($secondCard['is_overridden'])->toBeTrue();
});

it('reorders athkar cards locally without mutating database defaults', function () {
    $component = livewire(AthkarManager::class);
    $cards = collect($component->instance()->resolvedAthkarCards())->values();

    expect($cards->count())->toBeGreaterThan(1);

    $movedId = (int) $cards[0]['id'];
    $swappedId = (int) $cards[1]['id'];

    $movedDbOrder = Thikr::query()->findOrFail($movedId)->order;
    $swappedDbOrder = Thikr::query()->findOrFail($swappedId)->order;

    $component
        ->call('reorderAthkar', $movedId, 1)
        ->assertDispatched('athkar-manager-overrides-persisted')
        ->instance();

    $overridesById = collect($component->instance()->athkarOverrides)->keyBy('thikr_id');

    expect($overridesById->get($movedId)['order'])->toBe(2)
        ->and($overridesById->get($swappedId)['order'])->toBe(1)
        ->and(Thikr::query()->findOrFail($movedId)->order)->toBe($movedDbOrder)
        ->and(Thikr::query()->findOrFail($swappedId)->order)->toBe($swappedDbOrder);
});

it('marks only the actually moved default cards as modified after reordering', function () {
    $component = livewire(AthkarManager::class);
    $beforeDefaultIds = collect($component->instance()->resolvedAthkarCards())
        ->reject(fn (array $card): bool => (bool) ($card['is_custom'] ?? false))
        ->pluck('id')
        ->values();

    expect($beforeDefaultIds->count())->toBeGreaterThan(3);

    $component->call('reorderAthkar', (int) $beforeDefaultIds[0], 2);

    $afterDefaultCards = collect($component->instance()->resolvedAthkarCards())
        ->reject(fn (array $card): bool => (bool) ($card['is_custom'] ?? false))
        ->values();
    $afterDefaultIds = $afterDefaultCards->pluck('id')->values();

    $expectedChangedIds = collect(range(0, $beforeDefaultIds->count() - 1))
        ->flatMap(function (int $index) use ($afterDefaultIds, $beforeDefaultIds): array {
            if (($beforeDefaultIds[$index] ?? null) === ($afterDefaultIds[$index] ?? null)) {
                return [];
            }

            return [
                (int) ($beforeDefaultIds[$index] ?? 0),
                (int) ($afterDefaultIds[$index] ?? 0),
            ];
        })
        ->filter(fn (int $id): bool => $id > 0)
        ->unique()
        ->values()
        ->all();
    $actualChangedIds = $afterDefaultCards
        ->filter(fn (array $card): bool => (bool) ($card['is_overridden'] ?? false))
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    expect($actualChangedIds)->toBe(collect($expectedChangedIds)->sort()->values()->all());
});

it('keeps override badges scoped to actually moved defaults when custom and deleted overrides exist', function () {
    $component = livewire(AthkarManager::class);
    $defaults = collect($component->instance()->defaultAthkarCards())->values();

    expect($defaults->count())->toBeGreaterThan(6);

    $deletedDefaultId = (int) $defaults[4]['id'];
    $customId = ((int) $defaults->pluck('id')->max()) + 1000;

    $component->set('athkarOverrides', [
        [
            'thikr_id' => $deletedDefaultId,
            'is_deleted' => true,
        ],
        [
            'thikr_id' => $customId,
            'order' => 1,
            'time' => ThikrTime::Shared->value,
            'type' => ThikrType::Supplication->value,
            'text' => 'ذكر مخصص تجريبي',
            'origin' => null,
            'count' => 1,
            'is_aayah' => false,
            'is_deleted' => false,
            'is_custom' => true,
        ],
    ]);

    $beforeDefaultIds = collect($component->instance()->resolvedAthkarCards())
        ->reject(fn (array $card): bool => (bool) ($card['is_custom'] ?? false))
        ->pluck('id')
        ->values();

    expect($beforeDefaultIds->count())->toBeGreaterThan(3);

    $component->call('reorderAthkar', (int) $beforeDefaultIds[0], 2);

    $afterDefaultCards = collect($component->instance()->resolvedAthkarCards())
        ->reject(fn (array $card): bool => (bool) ($card['is_custom'] ?? false))
        ->values();
    $afterDefaultIds = $afterDefaultCards->pluck('id')->values();

    $expectedChangedIds = collect(range(0, $beforeDefaultIds->count() - 1))
        ->flatMap(function (int $index) use ($afterDefaultIds, $beforeDefaultIds): array {
            if (($beforeDefaultIds[$index] ?? null) === ($afterDefaultIds[$index] ?? null)) {
                return [];
            }

            return [
                (int) ($beforeDefaultIds[$index] ?? 0),
                (int) ($afterDefaultIds[$index] ?? 0),
            ];
        })
        ->filter(fn (int $id): bool => $id > 0)
        ->unique()
        ->sort()
        ->values()
        ->all();

    $actualChangedIds = $afterDefaultCards
        ->filter(fn (array $card): bool => (bool) ($card['is_overridden'] ?? false))
        ->pluck('id')
        ->sort()
        ->values()
        ->all();

    expect($actualChangedIds)->toBe($expectedChangedIds)
        ->and(count($actualChangedIds))->toBeLessThan($afterDefaultCards->count());
});

it('syncs athkar overrides only once from the client bridge', function () {
    $component = livewire(AthkarManager::class)
        ->call('syncAthkarOverrides', [
            [
                'thikr_id' => 999,
                'order' => 2,
                'time' => ThikrTime::Sabah->value,
                'type' => ThikrType::Glorification->value,
                'text' => 'ذكر',
                'origin' => null,
                'count' => 1,
                'is_deleted' => false,
            ],
        ])
        ->assertSet('hasHydratedOverrides', true)
        ->instance();

    expect($component->athkarOverrides)->toHaveCount(1);

    livewire($component::class)
        ->set('athkarOverrides', $component->athkarOverrides)
        ->set('hasHydratedOverrides', true)
        ->call('syncAthkarOverrides', [])
        ->assertSet('athkarOverrides', $component->athkarOverrides);
});

it('creates a browser-only custom thikr via manager create action', function () {
    $component = livewire(AthkarManager::class)
        ->call('openManageAthkar', false)
        ->call('mountAction', 'createAthkar')
        ->set('mountedActions.1.data', [
            'order' => 1,
            'time' => ThikrTime::Masaa->value,
            'type' => ThikrType::Supplication->value,
            'text' => 'ذكر محلي جديد',
            'origin' => 'مرجع محلي',
            'count' => 3,
            'is_aayah' => false,
        ])
        ->call('callMountedAction')
        ->assertDispatched('athkar-manager-overrides-persisted');

    $customCard = collect($component->instance()->resolvedAthkarCards())
        ->first(fn (array $card): bool => ($card['is_custom'] ?? false) === true);

    expect($customCard)->not->toBeNull()
        ->and($customCard['text'])->toBe('ذكر محلي جديد')
        ->and($customCard['type'])->toBe(ThikrType::Supplication->value)
        ->and($customCard['count'])->toBe(3)
        ->and($customCard['order'])->toBe(1);
});

it('reorders a card when order is changed from manager edit form', function () {
    $component = livewire(AthkarManager::class)
        ->call('openManageAthkar', false);

    $cards = collect($component->instance()->resolvedAthkarCards())->values();
    expect($cards->count())->toBeGreaterThan(1);

    $movedId = (int) $cards[0]['id'];
    $expectedSecondId = (int) $cards[1]['id'];

    $component
        ->call('openEditAthkar', $movedId)
        ->set('mountedActions.1.data.order', 2)
        ->call('callMountedAction')
        ->assertDispatched('athkar-manager-overrides-persisted');

    $afterCards = collect($component->instance()->resolvedAthkarCards())->values();

    expect((int) $afterCards[0]['id'])->toBe($expectedSecondId)
        ->and((int) $afterCards[1]['id'])->toBe($movedId);
});

it('validates order and count in manager create action form', function () {
    $component = livewire(AthkarManager::class)
        ->call('openManageAthkar', false)
        ->call('mountAction', 'createAthkar')
        ->set('mountedActions.1.data', [
            'order' => 0,
            'time' => ThikrTime::Masaa->value,
            'type' => ThikrType::Supplication->value,
            'text' => 'ذكر غير صالح',
            'origin' => null,
            'count' => 0,
            'is_aayah' => false,
        ])
        ->call('callMountedAction')
        ->assertHasErrors();

    $errorKeys = $component->errors()->keys();
    $hasOrderError = collect($errorKeys)->contains(
        fn (string $key): bool => str_contains($key, 'order'),
    );
    $hasCountError = collect($errorKeys)->contains(
        fn (string $key): bool => str_contains($key, 'count'),
    );

    expect($hasOrderError)->toBeTrue()
        ->and($hasCountError)->toBeTrue();
});

it('validates order and count in manager edit action form', function () {
    $component = livewire(AthkarManager::class)
        ->call('openManageAthkar', false);

    $cardId = (int) collect($component->instance()->resolvedAthkarCards())
        ->pluck('id')
        ->first();

    expect($cardId)->toBeGreaterThan(0);

    $component
        ->call('openEditAthkar', $cardId)
        ->set('mountedActions.1.data.order', 0)
        ->set('mountedActions.1.data.count', 0)
        ->call('callMountedAction')
        ->assertHasErrors();

    $errorKeys = $component->errors()->keys();
    $hasOrderError = collect($errorKeys)->contains(
        fn (string $key): bool => str_contains($key, 'order'),
    );
    $hasCountError = collect($errorKeys)->contains(
        fn (string $key): bool => str_contains($key, 'count'),
    );

    expect($hasOrderError)->toBeTrue()
        ->and($hasCountError)->toBeTrue();
});

it('deletes a thikr via confirmation action override', function () {
    $component = livewire(AthkarManager::class)
        ->call('openManageAthkar', false);

    $cardId = (int) collect($component->instance()->resolvedAthkarCards())
        ->pluck('id')
        ->first();

    expect($cardId)->toBeGreaterThan(0);

    $component
        ->call('openDeleteAthkar', $cardId)
        ->call('callMountedAction')
        ->assertDispatched('athkar-manager-overrides-persisted');

    $deletedOverride = collect($component->instance()->athkarOverrides)
        ->firstWhere('thikr_id', $cardId);

    expect($deletedOverride)->not->toBeNull()
        ->and($deletedOverride['is_deleted'])->toBeTrue();
});

it('normalizes enum objects when syncing overrides', function () {
    $component = livewire(AthkarManager::class);
    $cardId = (int) collect($component->instance()->resolvedAthkarCards())
        ->pluck('id')
        ->first();

    expect($cardId)->toBeGreaterThan(0);

    $component
        ->call('syncAthkarOverrides', [
            [
                'thikr_id' => $cardId,
                'order' => 2,
                'time' => ThikrTime::Sabah,
                'type' => ThikrType::Repentance,
                'text' => 'ذكر',
                'origin' => null,
                'count' => 1,
                'is_deleted' => false,
            ],
        ])
        ->assertSet('hasHydratedOverrides', true);

    $override = collect($component->instance()->athkarOverrides)->firstWhere('thikr_id', $cardId);

    expect($override)->not->toBeNull()
        ->and($override['time'])->toBe(ThikrTime::Sabah->value)
        ->and($override['type'])->toBe(ThikrType::Repentance->value);
});

it('normalizes legacy is_quran override payloads to is_aayah state', function () {
    $component = livewire(AthkarManager::class);
    $cardId = (int) collect($component->instance()->resolvedAthkarCards())
        ->pluck('id')
        ->first();

    expect($cardId)->toBeGreaterThan(0);

    $component->call('syncAthkarOverrides', [
        [
            'thikr_id' => $cardId,
            'text' => Thikr::AAYAH_OPENING_MARK.'نص آية'.Thikr::AAYAH_CLOSING_MARK,
            'is_quran' => true,
        ],
    ]);

    $override = collect($component->instance()->athkarOverrides)->firstWhere('thikr_id', $cardId);
    $card = collect($component->instance()->resolvedAthkarCards())->firstWhere('id', $cardId);

    expect($override)->not->toBeNull()
        ->and($override['is_aayah'])->toBeTrue()
        ->and($card)->not->toBeNull()
        ->and($card['is_aayah'])->toBeTrue();
});

it('shows the origin badge only when origin is available', function () {
    $withOrigin = Thikr::factory()->create([
        'origin' => 'مرجع',
    ]);

    $withoutOrigin = Thikr::factory()->create([
        'origin' => null,
    ]);

    $component = livewire(AthkarManager::class)->instance();
    $cards = collect($component->resolvedAthkarCards());

    expect($cards->firstWhere('id', $withOrigin->id)['is_original'])->toBeTrue()
        ->and($cards->firstWhere('id', $withoutOrigin->id)['is_original'])->toBeFalse();
});
