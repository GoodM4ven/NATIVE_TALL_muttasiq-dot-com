<?php

declare(strict_types=1);

use App\Livewire\AthkarManager;
use App\Models\Thikr;
use App\Models\ThikrOverrideSubmission;
use App\Services\Enums\ThikrTime;
use App\Services\Enums\ThikrType;

use function Pest\Livewire\livewire;

it('loads default athkar cards and keeps origin badge semantics correct', function () {
    $defaultText = 'manager-default-'.uniqid();

    $thikr = Thikr::factory()->create([
        'is_aayah' => false,
        'text' => $defaultText,
        'type' => ThikrType::Repentance,
        'origin' => 'reference',
    ]);

    $cards = livewire(AthkarManager::class)->instance()->defaultAthkarCards();
    $resolvedCard = collect($cards)->firstWhere('id', $thikr->id);

    expect($resolvedCard)->not->toBeNull()
        ->and($resolvedCard['text'])->toBe(arabic_text($defaultText))
        ->and(collect($cards)->contains(fn (array $card): bool => $card['id'] === $thikr->id))->toBeTrue()
        ->and(collect($cards)->contains(fn (array $card): bool => array_key_exists('is_aayah', $card)))->toBeTrue()
        ->and(collect($cards)->contains(fn (array $card): bool => $card['type'] === ThikrType::Repentance->value))->toBeTrue()
        ->and(collect($cards)->contains(fn (array $card): bool => $card['is_original'] === true))->toBeTrue();

    $withOrigin = Thikr::factory()->create([
        'origin' => 'مرجع',
    ]);

    $withoutOrigin = Thikr::factory()->create([
        'origin' => null,
    ]);

    $resolvedCards = collect(livewire(AthkarManager::class)->instance()->resolvedAthkarCards());

    expect($resolvedCards->firstWhere('id', $withOrigin->id)['is_original'])->toBeTrue()
        ->and($resolvedCards->firstWhere('id', $withoutOrigin->id)['is_original'])->toBeFalse();
});

it('searches athkar manager by thikr text only and ignores origin-only matches', function () {
    $textMatchOne = Thikr::factory()->create([
        'text' => 'لَيْسَ كَمِثْلِهِ شَيْءٌ',
        'origin' => 'القرآن الكريم',
    ]);

    $textMatchTwo = Thikr::factory()->create([
        'text' => 'فَلَيْسَ عَلَيْكُمْ جُنَاحٌ',
        'origin' => null,
    ]);

    $originOnlyMatch = Thikr::factory()->create([
        'text' => 'ذكر بلا تطابق مباشر',
        'origin' => 'مرجع فيه كلمة ليس',
    ]);

    $results = collect(
        livewire(AthkarManager::class)
            ->set('athkarSearchQuery', 'ليس')
            ->instance()
            ->filteredAthkarCards(),
    );

    $resultIds = $results->pluck('id')->values();
    $textMatchOneIndex = $resultIds->search($textMatchOne->id);
    $textMatchTwoIndex = $resultIds->search($textMatchTwo->id);
    $originOnlyMatchIndex = $resultIds->search($originOnlyMatch->id);

    expect($textMatchOneIndex)->not->toBeFalse()
        ->and($textMatchTwoIndex)->not->toBeFalse()
        ->and($originOnlyMatchIndex)->toBeFalse();
});

it('opens athkar manager in slide-over mode on desktop and modal mode on mobile', function () {
    livewire(AthkarManager::class)
        ->call('openManageAthkar', false)
        ->assertSet('isManageAthkarMobile', false)
        ->assertSet('mountedActions.0.name', 'manageAthkar');

    livewire(AthkarManager::class)
        ->call('openManageAthkar', true)
        ->assertSet('isManageAthkarMobile', true)
        ->assertSet('mountedActions.0.name', 'manageAthkar');
});

it('passes native mobile runtime flag to the manager card interaction bridge', function () {
    $source = file_get_contents(resource_path('views/components/partials/athkar-app/slideover-content.blade.php'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('nativeMobileRuntime: @js(')
        ->and($source)->toContain("config('nativephp-internal.running', false)")
        ->and($source)->toContain("is_platform('mobile')")
        ->and($source)->toContain('wire:model.live.debounce.520ms="athkarSearchQuery"');
});

it('keeps athkar manager search plan bounded to avoid heavy runtime spikes', function () {
    $source = file_get_contents(app_path('Livewire/AthkarManager.php'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('private array $normalizedAthkarSearchValueCache = [];')
        ->and($source)->toContain('private array $athkarSearchPlanCache = [];')
        ->and($source)->toContain('if (mb_strlen($normalized) > 1200) {')
        ->and($source)->toContain('$terms = [$normalized];')
        ->and($source)->not->toContain('buildComprehensiveSearchPlan');
});

it('configures athkar edit modal submit and delete buttons with explicit icons', function () {
    $source = file_get_contents(app_path('Livewire/AthkarManager.php'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain("->modalSubmitActionLabel('حفظ التعديل')")
        ->and($source)->toContain("->icon('heroicon-o-pencil-square')")
        ->and($source)->toContain("Action::make('deleteAthkarFromEdit')")
        ->and($source)->toContain("->icon('heroicon-o-x-mark')");
});

it('renders explicit sortable config and dedicated drag handle markup for manager cards', function () {
    $source = file_get_contents(resource_path('views/components/partials/athkar-app/slideover-content.blade.php'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('wire:sort:config="managerSortConfig()"')
        ->and($source)->toContain('athkar-manager-cards-grid flex flex-wrap content-start gap-4')
        ->and($source)->toContain('dir="ltr"')
        ->and($source)->toContain('data-athkar-manager-card')
        ->and($source)->toContain('data-athkar-order-index')
        ->and($source)->toContain('dir="rtl"')
        ->and($source)->toContain('data-athkar-sort-handle')
        ->and($source)->toContain('wire:loading.delay.class="opacity-100 pointer-events-auto"')
        ->and($source)->toContain('wire:loading.delay.class.remove="opacity-0 pointer-events-none"')
        ->and($source)->not->toContain('wire:sort:handle')
        ->and($source)->not->toContain('x-bind:data-athkar-touch-drag')
        ->and($source)->not->toContain('wire:click.preserve-scroll="openEditAthkar(');
});

it('mounts edit action and configures manage action presentation by breakpoint', function () {
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
        ->and($mobileAction->getModalWidth()->value)->toBe('5xl');
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
    $component->set('isManageAthkarMobile', true);
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
    $component->set('isManageAthkarMobile', true);
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
    $component->set('isManageAthkarMobile', true);
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

it('creates, edits, validates, and deletes athkar cards through manager actions', function () {
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

it('normalizes enum and legacy payloads when syncing overrides', function () {
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

    $legacyComponent = livewire(AthkarManager::class);

    $legacyComponent->call('syncAthkarOverrides', [
        [
            'thikr_id' => $cardId,
            'text' => Thikr::AAYAH_OPENING_MARK.'نص آية'.Thikr::AAYAH_CLOSING_MARK,
            'is_quran' => true,
        ],
    ]);

    $override = collect($legacyComponent->instance()->athkarOverrides)->firstWhere('thikr_id', $cardId);
    $card = collect($legacyComponent->instance()->resolvedAthkarCards())->firstWhere('id', $cardId);

    expect($override)->not->toBeNull()
        ->and($override['is_aayah'])->toBeTrue()
        ->and($card)->not->toBeNull()
        ->and($card['is_aayah'])->toBeTrue();
});

it('creates a pending override submission for a modified default thikr and skips exact duplicates', function () {
    $thikr = Thikr::factory()->create([
        'time' => ThikrTime::Sabah,
        'type' => ThikrType::Glorification,
        'text' => 'ذكر أصلي للاختبار',
        'origin' => 'مرجع أصلي',
        'count' => 3,
        'is_aayah' => false,
    ]);

    $component = livewire(AthkarManager::class)
        ->set('athkarOverrides', [
            [
                'thikr_id' => $thikr->id,
                'text' => 'ذكر معدل للاعتماد',
                'origin' => 'مرجع معدل',
                'count' => 4,
                'is_aayah' => false,
                'is_deleted' => false,
                'is_custom' => false,
            ],
        ])
        ->instance();

    $submitOverride = Closure::bind(
        static fn (AthkarManager $instance, int $thikrId): bool => $instance->submitOverrideForReviewById($thikrId),
        null,
        AthkarManager::class,
    );

    $firstSubmit = $submitOverride($component, $thikr->id);
    $secondSubmit = $submitOverride($component, $thikr->id);

    $submission = ThikrOverrideSubmission::query()
        ->where('thikr_id', $thikr->id)
        ->where('status', ThikrOverrideSubmission::STATUS_PENDING)
        ->first();

    expect($firstSubmit)->toBeTrue()
        ->and($secondSubmit)->toBeFalse()
        ->and($submission)->not->toBeNull()
        ->and($submission?->status)->toBe(ThikrOverrideSubmission::STATUS_PENDING)
        ->and(collect($submission?->override_payload['changed_keys'] ?? [])->contains('text'))->toBeTrue()
        ->and((string) ($submission?->override_payload['proposed']['text'] ?? ''))->toContain('ذكر معدل للاعتماد');
});

it('updates the existing pending submission payload when override changes', function () {
    $thikr = Thikr::factory()->create([
        'time' => ThikrTime::Masaa,
        'type' => ThikrType::Supplication,
        'text' => 'ذكر أساسي',
        'origin' => null,
        'count' => 1,
        'is_aayah' => false,
    ]);

    $component = livewire(AthkarManager::class)
        ->set('athkarOverrides', [
            [
                'thikr_id' => $thikr->id,
                'text' => 'صياغة أولى',
                'count' => 1,
                'is_deleted' => false,
                'is_custom' => false,
            ],
        ])
        ->instance();

    $submitOverride = Closure::bind(
        static fn (AthkarManager $instance, int $thikrId): bool => $instance->submitOverrideForReviewById($thikrId),
        null,
        AthkarManager::class,
    );

    expect($submitOverride($component, $thikr->id))->toBeTrue();

    $component->athkarOverrides = [
        [
            'thikr_id' => $thikr->id,
            'text' => 'صياغة ثانية',
            'count' => 2,
            'is_deleted' => false,
            'is_custom' => false,
        ],
    ];

    expect($submitOverride($component, $thikr->id))->toBeTrue();

    $pendingSubmissions = ThikrOverrideSubmission::query()
        ->where('thikr_id', $thikr->id)
        ->where('status', ThikrOverrideSubmission::STATUS_PENDING)
        ->get();
    $latestSubmission = $pendingSubmissions->first();

    expect($pendingSubmissions)->toHaveCount(1)
        ->and((string) ($latestSubmission?->override_payload['proposed']['text'] ?? ''))->toContain('صياغة ثانية')
        ->and((int) ($latestSubmission?->override_payload['proposed']['count'] ?? 0))->toBe(2);
});
