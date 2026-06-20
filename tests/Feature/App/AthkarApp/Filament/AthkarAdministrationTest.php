<?php

declare(strict_types=1);

use App\Filament\Resources\Thikrs\Pages\CreateThikr;
use App\Filament\Resources\Thikrs\Pages\EditThikr;
use App\Filament\Resources\Thikrs\Pages\ListThikrs;
use App\Models\Thikr;
use App\Models\User;
use App\Services\Enums\ThikrTime;
use App\Services\Enums\ThikrType;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

it('enforces athkar admin route access for admin users, non-admin users, and native runtime', function () {
    config(['app.custom.user.username' => 'admin']);

    $admin = User::factory()->create(['username' => 'admin']);
    $thikr = Thikr::factory()->create();

    actingAs($admin);

    get(route('filament.admin.resources.athkar.index'))->assertSuccessful();
    get(route('filament.admin.resources.athkar.create'))
        ->assertSuccessful()
        ->assertSee('الترتيب')
        ->assertSee('النوع')
        ->assertSee('الأصل');
    get(route('filament.admin.resources.athkar.edit', ['record' => $thikr]))->assertSuccessful();
    $user = User::factory()->create(['username' => 'member']);

    actingAs($user);

    get(route('filament.admin.resources.athkar.index'))->assertForbidden();
    get(route('filament.admin.resources.athkar.create'))->assertForbidden();
    get(route('filament.admin.resources.athkar.edit', ['record' => $thikr]))->assertForbidden();

    config([
        'app.custom.user.username' => 'admin',
        'nativephp-internal.running' => true,
    ]);

    actingAs($admin);

    get(route('filament.admin.auth.login'))->assertNotFound();
    get(route('filament.admin.resources.athkar.index'))->assertNotFound();
});

it('applies create/edit/reorder semantics for athkar ordering through model and Filament forms', function () {
    Thikr::query()->delete();

    $first = Thikr::factory()->create();
    $second = Thikr::factory()->create();
    $third = Thikr::factory()->create();

    Thikr::setNewOrder([$first->id, $second->id, $third->id]);

    $third->moveToOrder(1);

    expect(
        Thikr::query()
            ->ordered()
            ->limit(3)
            ->pluck('id')
            ->all(),
    )->toBe([$third->id, $first->id, $second->id]);

    expect($third->fresh()->order)->toBe(1)
        ->and($first->fresh()->order)->toBe(2)
        ->and($second->fresh()->order)->toBe(3);

    config(['app.custom.user.username' => 'admin']);

    $admin = User::factory()->create(['username' => 'admin']);
    actingAs($admin);
    Filament::setCurrentPanel('admin');

    Thikr::query()->delete();

    $first = Thikr::factory()->create();
    $second = Thikr::factory()->create();
    Thikr::setNewOrder([$first->id, $second->id]);

    livewire(CreateThikr::class)
        ->fillForm([
            'order' => 1,
            'time' => ThikrTime::Shared->value,
            'type' => ThikrType::Supplication->value,
            'text' => 'ذكر جديد بترتيب مخصص',
            'origin' => null,
            'count' => 1,
            'is_aayah' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $created = Thikr::query()
        ->where('text', 'ذكر جديد بترتيب مخصص')
        ->firstOrFail();

    expect($created->fresh()->order)->toBe(1)
        ->and(
            Thikr::query()
                ->ordered()
                ->limit(3)
                ->pluck('id')
                ->all(),
        )->toBe([$created->id, $first->id, $second->id]);

    Thikr::query()->delete();

    $first = Thikr::factory()->create();
    $second = Thikr::factory()->create();
    $third = Thikr::factory()->create();

    Thikr::setNewOrder([$first->id, $second->id, $third->id]);

    livewire(EditThikr::class, ['record' => $third->getKey()])
        ->fillForm([
            'order' => 1,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($third->fresh()->order)->toBe(1)
        ->and($first->fresh()->order)->toBe(2)
        ->and($second->fresh()->order)->toBe(3);
});

it('validates thikr form constraints and filters the admin table by type', function () {
    config(['app.custom.user.username' => 'admin']);

    $admin = User::factory()->create(['username' => 'admin']);
    actingAs($admin);
    Filament::setCurrentPanel('admin');

    livewire(CreateThikr::class)
        ->fillForm([
            'order' => 0,
            'time' => ThikrTime::Shared->value,
            'type' => ThikrType::Supplication->value,
            'text' => 'ذكر للتحقق من التحقق',
            'origin' => null,
            'count' => 0,
            'is_aayah' => false,
        ])
        ->call('create')
        ->assertHasFormErrors([
            'order' => 'min',
            'count' => 'min',
        ]);

    Thikr::query()->delete();

    $repentance = Thikr::factory()->create([
        'type' => ThikrType::Repentance,
        'text' => 'ذكر التوبة',
    ]);
    $supplication = Thikr::factory()->create([
        'type' => ThikrType::Supplication,
        'text' => 'ذكر الدعاء',
    ]);

    livewire(ListThikrs::class)
        ->assertCanSeeTableRecords([$repentance, $supplication])
        ->filterTable('type', ThikrType::Repentance->value)
        ->assertCanSeeTableRecords([$repentance])
        ->assertCanNotSeeTableRecords([$supplication]);
});
