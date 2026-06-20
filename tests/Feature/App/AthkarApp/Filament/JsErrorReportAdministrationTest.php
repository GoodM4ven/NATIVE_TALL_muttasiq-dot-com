<?php

declare(strict_types=1);

use App\Filament\Resources\JsErrorReports\Pages\ListJsErrorReports;
use App\Models\JsErrorReport;
use App\Models\User;
use Filament\Facades\Filament;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

it('enforces js error reports page access for admin and non-admin users', function () {
    config(['app.custom.user.username' => 'admin']);

    $admin = User::factory()->create(['username' => 'admin']);
    actingAs($admin);

    get(route('filament.admin.resources.balaghat-akhtaa.index'))->assertSuccessful();

    $user = User::factory()->create(['username' => 'member']);
    actingAs($user);

    get(route('filament.admin.resources.balaghat-akhtaa.index'))->assertForbidden();
});

it('separates unresolved/resolved reports by tabs and toggles resolution state from table actions', function () {
    config(['app.custom.user.username' => 'admin']);

    $admin = User::factory()->create(['username' => 'admin']);
    actingAs($admin);
    Filament::setCurrentPanel('admin');

    $unresolved = JsErrorReport::factory()->create([
        'user_note' => 'بلاغ غير معالج',
        'resolved_at' => null,
    ]);
    $resolved = JsErrorReport::factory()->create([
        'user_note' => 'بلاغ معالج',
        'resolved_at' => now(),
    ]);

    livewire(ListJsErrorReports::class)
        ->assertCanSeeTableRecords([$unresolved])
        ->assertCanNotSeeTableRecords([$resolved])
        ->set('activeTab', 'resolved')
        ->assertCanSeeTableRecords([$resolved])
        ->assertCanNotSeeTableRecords([$unresolved]);

    $report = JsErrorReport::factory()->create([
        'resolved_at' => null,
    ]);

    livewire(ListJsErrorReports::class)
        ->callTableAction('markResolved', $report);

    expect($report->fresh()->resolved_at)->not->toBeNull();
    $resolvedReport = JsErrorReport::factory()->create([
        'resolved_at' => now(),
    ]);

    livewire(ListJsErrorReports::class)
        ->set('activeTab', 'resolved')
        ->callTableAction('markUnresolved', $resolvedReport);

    expect($resolvedReport->fresh()->resolved_at)->toBeNull();
});

it('deletes nonsense reports from the table action', function () {
    config(['app.custom.user.username' => 'admin']);

    $admin = User::factory()->create(['username' => 'admin']);
    actingAs($admin);
    Filament::setCurrentPanel('admin');

    $report = JsErrorReport::factory()->create();

    livewire(ListJsErrorReports::class)
        ->callTableAction('delete', $report);

    expect(JsErrorReport::query()->find($report->id))->toBeNull();
});
