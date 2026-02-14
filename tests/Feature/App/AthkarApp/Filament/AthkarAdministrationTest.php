<?php

declare(strict_types=1);

use App\Filament\Resources\Thikrs\Pages\ListThikrs;
use App\Models\Thikr;
use App\Models\User;
use App\Services\Enums\ThikrType;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

it('allows the configured admin to access athkar resource pages', function () {
    config(['app.custom.user.email' => 'admin@example.test']);

    $admin = User::factory()->create(['email' => 'admin@example.test']);
    $thikr = Thikr::factory()->create();

    actingAs($admin);

    get(route('filament.admin.resources.athkar.index'))->assertSuccessful();
    get(route('filament.admin.resources.athkar.create'))
        ->assertSuccessful()
        ->assertSee('النوع')
        ->assertSee('الأصل');
    get(route('filament.admin.resources.athkar.edit', ['record' => $thikr]))->assertSuccessful();
});

it('forbids non-admin users from athkar resource pages', function () {
    config(['app.custom.user.email' => 'admin@example.test']);

    $user = User::factory()->create(['email' => 'member@example.test']);
    $thikr = Thikr::factory()->create();

    actingAs($user);

    get(route('filament.admin.resources.athkar.index'))->assertForbidden();
    get(route('filament.admin.resources.athkar.create'))->assertForbidden();
    get(route('filament.admin.resources.athkar.edit', ['record' => $thikr]))->assertForbidden();
});

it('reorders athkar inline when updating table order column', function () {
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
});

it('filters athkar admin table by type', function () {
    config(['app.custom.user.email' => 'admin@example.test']);

    $admin = User::factory()->create(['email' => 'admin@example.test']);
    actingAs($admin);
    Filament::setCurrentPanel('admin');

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
