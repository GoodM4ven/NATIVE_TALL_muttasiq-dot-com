<?php

declare(strict_types=1);

namespace App\Livewire;

use App\Filament\Resources\Thikrs\Schemas\ThikrForm;
use App\Models\Thikr;
use App\Services\Enums\ThikrTime;
use App\Services\Enums\ThikrType;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AthkarManager extends Component implements HasActions, HasSchemas
{
    use InteractsWithActions;
    use InteractsWithSchemas;

    public bool $isManageAthkarMobile = false;

    /**
     * @var array<int, array{
     *     thikr_id: int,
     *     order: int|null,
     *     time: string|null,
     *     type: string|null,
     *     text: string|null,
     *     origin: string|null,
     *     count: int|null,
     *     is_aayah: bool|null,
     *     is_deleted: bool,
     *     is_custom: bool
     * }>
     */
    public array $athkarOverrides = [];

    public bool $hasHydratedOverrides = false;

    public function openManageAthkar(bool $isMobile = false): void
    {
        $this->isManageAthkarMobile = $isMobile;

        $this->mountAction('manageAthkar');
    }

    public function manageAthkarAction(): Action
    {
        return Action::make('manageAthkar')
            ->modalHeading('إدارة أذكار الصباح والمساء')
            ->modalDescription('يمكنك تخصيص الأذكار كما ترغب، مع إمكانية استعادة الأذكار الافتراضية عبر زر استعادة.')
            ->modalAutofocus(false)
            ->slideOver(! $this->isManageAthkarMobile)
            ->modalWidth($this->isManageAthkarMobile ? Width::FiveExtraLarge : Width::SevenExtraLarge)
            ->registerModalActions([
                $this->editAthkarAction(),
                $this->createAthkarAction(),
                $this->deleteAthkarAction(),
                $this->resetAthkarOverridesAction(),
            ])
            ->modalSubmitAction(false)
            ->modalCancelActionLabel('إغلاق')
            ->modalContent(fn (): View => view('livewire.athkar-manager.slideover-content', [
                'componentId' => $this->getId(),
                'cards' => $this->resolvedAthkarCards(),
                'isMobile' => $this->isManageAthkarMobile,
            ]))
            ->action(static fn (): null => null);
    }

    public function editAthkarAction(): Action
    {
        return Action::make('editAthkar')
            ->overlayParentActions()
            ->modalHeading('تعديل الذكر')
            ->modalAutofocus(false)
            ->modalSubmitActionLabel('حفظ التعديل')
            ->modalFooterActionsAlignment(Alignment::End)
            ->extraModalFooterActions([
                Action::make('deleteAthkarFromEdit')
                    ->label('حذف الذكر')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalAutofocus(false)
                    ->action(function (array $mountedActions): void {
                        $editAthkarAction = collect($mountedActions)
                            ->first(fn (Action $mountedAction): bool => $mountedAction->getName() === 'editAthkar');
                        $thikrId = (int) ($editAthkarAction?->getArguments()['thikrId'] ?? 0);

                        if ($thikrId < 1) {
                            return;
                        }

                        if ($this->deleteAthkarById($thikrId)) {
                            notify('heroicon-o-trash', 'تم حذف الذكر', 'يمكنك استعادته من زر استعادة الكل.');
                        }
                    })
                    ->cancelParentActions('editAthkar'),
            ])
            ->fillForm(function (array $arguments): array {
                return $this->editFormDefaultsById((int) ($arguments['thikrId'] ?? 0));
            })
            ->schema($this->thikrFormSchema())
            ->action(function (array $data, array $arguments): void {
                $thikrId = (int) ($arguments['thikrId'] ?? 0);

                if ($thikrId < 1) {
                    return;
                }

                $didSave = $this->saveOverrideById($thikrId, $data);
                $didReorder = $this->applyRequestedOrderToCard($thikrId, $data);

                if ($didSave || $didReorder) {
                    notify('heroicon-o-check-circle', 'تم حفظ التعديل');
                }
            });
    }

    public function createAthkarAction(): Action
    {
        return Action::make('createAthkar')
            ->overlayParentActions()
            ->modalHeading('إضافة ذكر جديد')
            ->modalAutofocus(false)
            ->modalSubmitActionLabel('إضافة')
            ->fillForm(fn (): array => [
                'order' => max(1, $this->maxResolvedOrder() + 1),
                'time' => ThikrTime::Shared->value,
                'type' => ThikrType::Glorification->value,
                'text' => '',
                'origin' => null,
                'count' => 1,
                'is_aayah' => false,
            ])
            ->schema($this->thikrFormSchema())
            ->action(function (array $data): void {
                $beforeIds = collect($this->resolvedAthkarCards())
                    ->pluck('id')
                    ->map(fn (mixed $id): int => max(0, (int) $id))
                    ->filter(fn (int $id): bool => $id > 0)
                    ->values();

                if ($this->createCustomAthkar($data)) {
                    $afterIds = collect($this->resolvedAthkarCards())
                        ->pluck('id')
                        ->map(fn (mixed $id): int => max(0, (int) $id))
                        ->filter(fn (int $id): bool => $id > 0)
                        ->values();
                    $newThikrId = (int) ($afterIds->diff($beforeIds)->first() ?? 0);

                    if ($newThikrId > 0) {
                        $this->applyRequestedOrderToCard($newThikrId, $data);
                    }

                    notify('heroicon-o-plus-circle', 'تمت إضافة الذكر');
                }
            });
    }

    public function deleteAthkarAction(): Action
    {
        return Action::make('deleteAthkar')
            ->overlayParentActions()
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('حذف الذكر')
            ->modalAutofocus(false)
            ->modalDescription('سيتم إخفاء الذكر محليًا ويمكن استعادته عبر زر استعادة الكل.')
            ->action(function (array $arguments): void {
                $thikrId = (int) ($arguments['thikrId'] ?? 0);

                if ($thikrId < 1) {
                    return;
                }

                if ($this->deleteAthkarById($thikrId)) {
                    notify('heroicon-o-trash', 'تم حذف الذكر', 'يمكنك استعادته من زر استعادة الكل.');
                }
            });
    }

    public function resetAthkarOverridesAction(): Action
    {
        return Action::make('resetAthkarOverrides')
            ->color('danger')
            ->requiresConfirmation()
            ->modalHeading('استعادة الإعدادات الافتراضية؟')
            ->modalAutofocus(false)
            ->modalDescription('سيتم حذف كل التعديلات المحلية، بما فيها الأذكار المضافة.')
            ->modalSubmitActionLabel('نعم، استعادة الكل')
            ->action(function (): void {
                if ($this->resetAllAthkarOverrides()) {
                    notify('heroicon-o-arrow-path', 'تمت الاستعادة', 'أُعيدت جميع الأذكار للحالة الافتراضية.');
                }
            });
    }

    public function openEditAthkar(int $thikrId): void
    {
        if (! $this->resolvedCardById($thikrId)) {
            return;
        }

        $this->mountAction('editAthkar', ['thikrId' => $thikrId]);
    }

    public function openCreateAthkar(): void
    {
        $this->mountAction('createAthkar');
    }

    public function openDeleteAthkar(int $thikrId): void
    {
        if (! $this->resolvedCardById($thikrId)) {
            return;
        }

        $this->mountAction('deleteAthkar', ['thikrId' => $thikrId]);
    }

    public function openResetAthkarOverrides(): void
    {
        $this->mountAction('resetAthkarOverrides');
    }

    public function resetAllAthkarOverrides(): bool
    {
        $previousOverrides = $this->normalizeOverrides($this->athkarOverrides);

        if ($previousOverrides === []) {
            return false;
        }

        $this->athkarOverrides = [];
        $this->dispatchOverridesPersisted(hasOrderChange: true);

        return true;
    }

    public function reorderAthkar(int|string $thikrId, int|string $position): void
    {
        $thikrId = max(0, (int) $thikrId);

        if ($thikrId < 1) {
            return;
        }

        $orderedIds = collect($this->resolvedAthkarCards())
            ->pluck('id')
            ->map(fn (mixed $id): int => max(0, (int) $id))
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($orderedIds->count() < 2) {
            return;
        }

        $fromIndex = $orderedIds->search($thikrId);

        if ($fromIndex === false) {
            return;
        }

        $previousOrderedIds = $orderedIds->values()->all();
        $targetIndex = max(0, min((int) $position, $orderedIds->count() - 1));

        if ($fromIndex === $targetIndex) {
            return;
        }

        $orderedIds->forget($fromIndex);
        $orderedIds = $orderedIds->values();
        $orderedIds->splice($targetIndex, 0, [$thikrId]);

        $this->persistOrderFromResolvedIds($previousOrderedIds, $orderedIds->values()->all());
    }

    /**
     * @param  array{order?: mixed, time?: mixed, type?: mixed, text?: mixed, origin?: mixed, count?: mixed, is_aayah?: mixed}  $data
     */
    private function applyRequestedOrderToCard(int $thikrId, array $data): bool
    {
        $requestedOrder = $this->normalizePositiveInteger($data['order'] ?? null);

        if ($requestedOrder === null) {
            return false;
        }

        return $this->moveResolvedCardToOrder($thikrId, $requestedOrder);
    }

    private function moveResolvedCardToOrder(int $thikrId, int $targetOrder): bool
    {
        $thikrId = max(0, $thikrId);

        if ($thikrId < 1) {
            return false;
        }

        $orderedIds = collect($this->resolvedAthkarCards())
            ->pluck('id')
            ->map(fn (mixed $id): int => max(0, (int) $id))
            ->filter(fn (int $id): bool => $id > 0)
            ->values();

        if ($orderedIds->count() < 2) {
            return false;
        }

        $fromIndex = $orderedIds->search($thikrId);

        if ($fromIndex === false) {
            return false;
        }

        $targetIndex = max(0, min($targetOrder - 1, $orderedIds->count() - 1));

        if ($fromIndex === $targetIndex) {
            return false;
        }

        $previousOrderedIds = $orderedIds->all();
        $orderedIds->forget($fromIndex);
        $orderedIds = $orderedIds->values();
        $orderedIds->splice($targetIndex, 0, [$thikrId]);

        $this->persistOrderFromResolvedIds(
            $previousOrderedIds,
            $orderedIds->values()->all(),
            shouldNotify: false,
        );

        return true;
    }

    /**
     * @param  array<int, mixed>  $overrides
     */
    public function syncAthkarOverrides(array $overrides): void
    {
        if ($this->hasHydratedOverrides) {
            return;
        }

        $this->athkarOverrides = $this->normalizeOverrides($overrides);
        $this->hasHydratedOverrides = true;
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     time: string,
     *     type: string,
     *     text: string,
     *     origin: string|null,
     *     is_aayah: bool,
     *     is_original: bool,
     *     count: int,
     *     order: int,
     *     is_overridden: bool,
     *     is_custom: bool
     * }>
     */
    public function resolvedAthkarCards(): array
    {
        $defaults = $this->defaultAthkarCards();
        $defaultOrderMap = $this->defaultOrderMap();
        $defaultsById = collect($defaults)->keyBy('id');
        $normalizedOverrides = $this->normalizeOverrides($this->athkarOverrides);
        $overridesById = collect($normalizedOverrides)
            ->keyBy('thikr_id');

        $defaultCards = [];

        foreach ($defaults as $defaultCard) {
            /** @var array{
             *     thikr_id: int,
             *     order: int|null,
             *     time: string|null,
             *     type: string|null,
             *     text: string|null,
             *     origin: string|null,
             *     count: int|null,
             *     is_aayah: bool|null,
             *     is_deleted: bool,
             *     is_custom: bool
             * }|null $override */
            $override = $overridesById->get($defaultCard['id']);

            if ($override && $override['is_deleted']) {
                continue;
            }

            $mergedText = $override['text'] ?? $defaultCard['text'];
            $mergedOrigin = $override['origin'] ?? $defaultCard['origin'];

            $mergedCard = [
                ...$defaultCard,
                'order' => $override['order'] ?? ($defaultOrderMap[$defaultCard['id']] ?? $defaultCard['order']),
                'time' => $override['time'] ?? $defaultCard['time'],
                'type' => $override['type'] ?? $defaultCard['type'],
                'text' => $mergedText,
                'origin' => $mergedOrigin,
                'count' => $override['count'] ?? $defaultCard['count'],
                'is_aayah' => $this->resolveAayahState(
                    $override['is_aayah'] ?? null,
                    $mergedText,
                    fallback: (bool) $defaultCard['is_aayah'],
                ),
            ];

            $mergedCard['is_original'] = $this->hasOrigin($mergedOrigin);
            $mergedCard['is_custom'] = false;

            $defaultCards[] = $mergedCard;
        }

        $customCards = [];

        foreach ($normalizedOverrides as $override) {
            if (! $override['is_custom']) {
                continue;
            }
            if ($defaultsById->has($override['thikr_id'])) {
                continue;
            }
            if ($override['is_deleted']) {
                continue;
            }

            $origin = $this->normalizeNullableText($override['origin']);
            $text = $override['text'] ?? '';

            $customCards[] = [
                'id' => $override['thikr_id'],
                'time' => $override['time'] ?? ThikrTime::Shared->value,
                'type' => $override['type'] ?? ThikrType::Glorification->value,
                'text' => $text,
                'origin' => $origin,
                'is_aayah' => $this->resolveAayahState(
                    $override['is_aayah'],
                    $text,
                ),
                'is_original' => $this->hasOrigin($origin),
                'count' => max(1, (int) ($override['count'] ?? 1)),
                'order' => max(1, (int) ($override['order'] ?? 1)),
                'is_custom' => true,
            ];
        }

        $cards = [...$defaultCards, ...$customCards];
        usort($cards, static function (array $left, array $right): int {
            $orderComparison = $left['order'] <=> $right['order'];

            if ($orderComparison !== 0) {
                return $orderComparison;
            }

            return $left['id'] <=> $right['id'];
        });

        return $this->attachOverrideFlags($cards, $normalizedOverrides);
    }

    /**
     * @return array<int, array{id: int, time: string, type: string, text: string, origin: string|null, is_aayah: bool, is_original: bool, count: int, order: int}>
     */
    public function defaultAthkarCards(): array
    {
        return Thikr::defaultsPayload();
    }

    private function thikrFormSchema(): array
    {
        return ThikrForm::components(fromManager: true);
    }

    /**
     * @return array{order: int, time: string, type: string, text: string, origin: string|null, count: int, is_aayah: bool}
     */
    private function editFormDefaultsById(int $thikrId): array
    {
        $card = collect($this->resolvedAthkarCards())
            ->first(fn (array $resolvedCard): bool => $resolvedCard['id'] === $thikrId)
            ?? $this->defaultCardById($thikrId);

        if (! $card) {
            return [
                'order' => 1,
                'time' => ThikrTime::Shared->value,
                'type' => ThikrType::Glorification->value,
                'text' => '',
                'origin' => null,
                'count' => 1,
                'is_aayah' => false,
            ];
        }

        $text = $card['text'];
        $origin = $this->normalizeNullableText($card['origin'] ?? null);

        return [
            'order' => max(1, (int) $card['order']),
            'time' => $card['time'],
            'type' => $card['type'],
            'text' => Thikr::stripAayahWrapper($text),
            'origin' => $origin,
            'count' => max(1, $card['count']),
            'is_aayah' => $this->resolveAayahState(
                $card['is_aayah'],
                $text,
            ),
        ];
    }

    /**
     * @param  array{order?: mixed, time?: mixed, type?: mixed, text?: mixed, origin?: mixed, count?: mixed, is_aayah?: mixed}  $data
     */
    private function saveOverrideById(int $thikrId, array $data): bool
    {
        $defaultCard = $this->defaultCardById($thikrId);
        $defaultOrderMap = $this->defaultOrderMap();
        $defaultOrder = $defaultOrderMap[$thikrId] ?? (int) ($defaultCard['order'] ?? 1);
        $previousOverrides = $this->normalizeOverrides($this->athkarOverrides);
        $existingOverride = collect($previousOverrides)->firstWhere('thikr_id', $thikrId);

        if ($defaultCard) {
            $normalizedText = Thikr::normalizeAayahText(
                (string) ($data['text'] ?? ''),
                (bool) ($data['is_aayah'] ?? false),
            );

            $nextOverride = $this->normalizeOverride([
                'thikr_id' => $thikrId,
                'order' => $existingOverride['order'] ?? null,
                'time' => $data['time'] ?? $defaultCard['time'],
                'type' => $data['type'] ?? $defaultCard['type'],
                'text' => $normalizedText,
                'origin' => $data['origin'] ?? null,
                'count' => $data['count'] ?? $defaultCard['count'],
                'is_aayah' => $data['is_aayah'] ?? $defaultCard['is_aayah'],
                'is_deleted' => false,
                'is_custom' => false,
            ]);

            if (! $nextOverride) {
                return false;
            }

            $defaultOrigin = $this->normalizeNullableText($defaultCard['origin']);

            $nextOverride['order'] = $nextOverride['order'] !== $defaultOrder ? $nextOverride['order'] : null;
            $nextOverride['time'] = $nextOverride['time'] !== $defaultCard['time'] ? $nextOverride['time'] : null;
            $nextOverride['type'] = $nextOverride['type'] !== $defaultCard['type'] ? $nextOverride['type'] : null;
            $nextOverride['text'] = $nextOverride['text'] !== $defaultCard['text'] ? $nextOverride['text'] : null;
            $nextOverride['origin'] = $nextOverride['origin'] !== $defaultOrigin ? $nextOverride['origin'] : null;
            $nextOverride['count'] = $nextOverride['count'] !== $defaultCard['count'] ? $nextOverride['count'] : null;
            $nextOverride['is_aayah'] = $nextOverride['is_aayah'] !== (bool) $defaultCard['is_aayah'] ? $nextOverride['is_aayah'] : null;

            $remainingOverrides = collect($previousOverrides)
                ->reject(fn (array $override): bool => $override['thikr_id'] === $thikrId);

            if ($this->hasMeaningfulOverride($nextOverride)) {
                $remainingOverrides->push($nextOverride);
            }

            $nextOverrides = $remainingOverrides->values()->all();

            if ($nextOverrides === $previousOverrides) {
                return false;
            }

            $this->athkarOverrides = $nextOverrides;
            $this->dispatchOverridesPersisted(
                changedThikrId: $thikrId,
                hasOrderChange: $this->overridesOrderMap($previousOverrides) !== $this->overridesOrderMap($nextOverrides),
            );

            return true;
        }

        return $this->saveCustomOverrideById($thikrId, $data);
    }

    /**
     * @param  array{order?: mixed, time?: mixed, type?: mixed, text?: mixed, origin?: mixed, count?: mixed, is_aayah?: mixed}  $data
     */
    private function saveCustomOverrideById(int $thikrId, array $data): bool
    {
        $previousOverrides = $this->normalizeOverrides($this->athkarOverrides);
        $currentCustomOverride = collect($previousOverrides)
            ->first(fn (array $override): bool => $override['thikr_id'] === $thikrId && $override['is_custom']);

        if (! $currentCustomOverride) {
            return false;
        }

        $normalizedText = Thikr::normalizeAayahText(
            (string) ($data['text'] ?? ''),
            (bool) ($data['is_aayah'] ?? false),
        );

        $nextOverride = $this->normalizeOverride([
            'thikr_id' => $thikrId,
            'order' => $currentCustomOverride['order'] ?? max(1, $this->maxResolvedOrder()),
            'time' => $data['time'] ?? $currentCustomOverride['time'] ?? ThikrTime::Shared->value,
            'type' => $data['type'] ?? $currentCustomOverride['type'] ?? ThikrType::Glorification->value,
            'text' => $normalizedText,
            'origin' => $data['origin'] ?? null,
            'count' => $data['count'] ?? $currentCustomOverride['count'] ?? 1,
            'is_aayah' => (bool) ($data['is_aayah'] ?? $currentCustomOverride['is_aayah'] ?? false),
            'is_deleted' => $currentCustomOverride['is_deleted'],
            'is_custom' => true,
        ]);

        if (! $nextOverride) {
            return false;
        }

        $remainingOverrides = collect($previousOverrides)
            ->reject(fn (array $override): bool => $override['thikr_id'] === $thikrId);

        if ($this->hasMeaningfulOverride($nextOverride)) {
            $remainingOverrides->push($nextOverride);
        }

        $nextOverrides = $remainingOverrides->values()->all();

        if ($nextOverrides === $previousOverrides) {
            return false;
        }

        $this->athkarOverrides = $nextOverrides;
        $this->dispatchOverridesPersisted(
            changedThikrId: $thikrId,
            hasOrderChange: $this->overridesOrderMap($previousOverrides) !== $this->overridesOrderMap($nextOverrides),
        );

        return true;
    }

    /**
     * @param  array<int, int>  $previousOrderedIds
     * @param  array<int, int>  $nextOrderedIds
     */
    private function persistOrderFromResolvedIds(
        array $previousOrderedIds,
        array $nextOrderedIds,
        bool $shouldNotify = true
    ): void {
        $previousOrderedIds = collect($previousOrderedIds)
            ->map(fn (int $id): int => max(0, $id))
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();
        $nextOrderedIds = collect($nextOrderedIds)
            ->map(fn (int $id): int => max(0, $id))
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();

        if ($previousOrderedIds === [] || $nextOrderedIds === [] || $previousOrderedIds === $nextOrderedIds) {
            return;
        }

        $previousOverrides = collect($this->normalizeOverrides($this->athkarOverrides))
            ->sortBy('thikr_id')
            ->values()
            ->all();

        $defaultsById = collect($this->defaultAthkarCards())->keyBy('id');
        $defaultOrderMap = $this->defaultOrderMap();
        $nextOverridesById = collect($previousOverrides)->keyBy('thikr_id');
        $nextPositions = collect($nextOrderedIds)
            ->values()
            ->mapWithKeys(fn (int $id, int $index): array => [$id => $index + 1])
            ->all();
        $touchedIds = array_keys($this->changedOrderIdsMap($previousOrderedIds, $nextOrderedIds));

        foreach ($touchedIds as $recordId) {
            $defaultCard = $defaultsById->get($recordId);
            $existingOverride = $nextOverridesById->get($recordId);
            $nextOrder = $nextPositions[$recordId] ?? null;

            if (($nextOrder === null) || (! $defaultCard && ! (bool) ($existingOverride['is_custom'] ?? false))) {
                continue;
            }

            $nextOverride = $existingOverride ?? [
                'thikr_id' => $recordId,
                'order' => null,
                'time' => null,
                'type' => null,
                'text' => null,
                'origin' => null,
                'count' => null,
                'is_aayah' => null,
                'is_deleted' => false,
                'is_custom' => false,
            ];

            if ($defaultCard) {
                $defaultOrder = $defaultOrderMap[$recordId] ?? $defaultCard['order'];
                $nextOverride['order'] = $nextOrder !== $defaultOrder ? $nextOrder : null;
            } else {
                $nextOverride['order'] = $nextOrder;
                $nextOverride['is_custom'] = true;
            }

            $normalizedOverride = $this->normalizeOverride($nextOverride);

            if ((! $normalizedOverride) || (! $this->hasMeaningfulOverride($normalizedOverride))) {
                $nextOverridesById->forget($recordId);

                continue;
            }

            $nextOverridesById->put($recordId, $normalizedOverride);
        }

        $nextOverrides = $nextOverridesById
            ->sortBy('thikr_id')
            ->values()
            ->all();

        if ($nextOverrides === $previousOverrides) {
            return;
        }

        $this->athkarOverrides = $nextOverrides;
        $this->dispatchOverridesPersisted(hasOrderChange: true);

        if ($shouldNotify) {
            notify('heroicon-o-bars-3', 'تم تحديث ترتيب الأذكار');
        }
    }

    private function dispatchOverridesPersisted(?int $changedThikrId = null, bool $hasOrderChange = false): void
    {
        $this->dispatch(
            'athkar-manager-overrides-persisted',
            componentId: $this->getId(),
            overrides: $this->athkarOverrides,
            changedThikrId: $changedThikrId,
            hasOrderChange: $hasOrderChange,
        );
    }

    /**
     * @param  array<int, array{
     *     thikr_id: int,
     *     order: int|null,
     *     time: string|null,
     *     type: string|null,
     *     text: string|null,
     *     origin: string|null,
     *     count: int|null,
     *     is_aayah: bool|null,
     *     is_deleted: bool,
     *     is_custom: bool
     * }>  $overrides
     * @return array<int, int|null>
     */
    private function overridesOrderMap(array $overrides): array
    {
        return collect($overrides)
            ->sortBy('thikr_id')
            ->pluck('order', 'thikr_id')
            ->all();
    }

    /**
     * @param  array<int, mixed>  $overrides
     * @return array<int, array{
     *     thikr_id: int,
     *     order: int|null,
     *     time: string|null,
     *     type: string|null,
     *     text: string|null,
     *     origin: string|null,
     *     count: int|null,
     *     is_aayah: bool|null,
     *     is_deleted: bool,
     *     is_custom: bool
     * }>
     */
    private function normalizeOverrides(array $overrides): array
    {
        return collect($overrides)
            ->map(function (mixed $override): ?array {
                if (! is_array($override)) {
                    return null;
                }

                return $this->normalizeOverride($override);
            })
            ->filter()
            ->keyBy('thikr_id')
            ->values()
            ->filter(fn (array $override): bool => $this->hasMeaningfulOverride($override))
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $override
     * @return array{
     *     thikr_id: int,
     *     order: int|null,
     *     time: string|null,
     *     type: string|null,
     *     text: string|null,
     *     origin: string|null,
     *     count: int|null,
     *     is_aayah: bool|null,
     *     is_deleted: bool,
     *     is_custom: bool
     * }|null
     */
    private function normalizeOverride(array $override): ?array
    {
        $thikrId = max(0, (int) ($override['thikr_id'] ?? 0));

        if ($thikrId < 1) {
            return null;
        }

        $isAayah = array_key_exists('is_aayah', $override)
            ? $override['is_aayah']
            : (array_key_exists('is_quran', $override) ? $override['is_quran'] : null);

        return [
            'thikr_id' => $thikrId,
            'order' => $this->normalizePositiveInteger($override['order'] ?? null),
            'time' => $this->normalizeTimeValue($override['time'] ?? null),
            'type' => $this->normalizeTypeValue($override['type'] ?? null),
            'text' => $this->normalizeNullableText($override['text'] ?? null),
            'origin' => $this->normalizeNullableText($override['origin'] ?? null),
            'count' => $this->normalizePositiveInteger($override['count'] ?? null),
            'is_aayah' => $isAayah === null ? null : (bool) $isAayah,
            'is_deleted' => (bool) ($override['is_deleted'] ?? false),
            'is_custom' => (bool) ($override['is_custom'] ?? false),
        ];
    }

    /**
     * @param  array{
     *     thikr_id: int,
     *     order: int|null,
     *     time: string|null,
     *     type: string|null,
     *     text: string|null,
     *     origin: string|null,
     *     count: int|null,
     *     is_aayah: bool|null,
     *     is_deleted: bool,
     *     is_custom: bool
     * }  $override
     */
    private function hasMeaningfulOverride(array $override): bool
    {
        return (bool) (
            $override['is_custom'] ||
            $override['is_deleted'] ||
            $override['order'] !== null ||
            $override['time'] !== null ||
            $override['type'] !== null ||
            $override['text'] !== null ||
            $override['origin'] !== null ||
            $override['count'] !== null ||
            $override['is_aayah'] !== null
        );
    }

    /**
     * @param  array{
     *     thikr_id: int,
     *     order: int|null,
     *     time: string|null,
     *     type: string|null,
     *     text: string|null,
     *     origin: string|null,
     *     count: int|null,
     *     is_aayah: bool|null,
     *     is_deleted: bool,
     *     is_custom: bool
     * }  $override
     */
    private function hasNonOrderOverride(array $override): bool
    {
        return (bool) (
            $override['is_custom'] ||
            $override['is_deleted'] ||
            $override['time'] !== null ||
            $override['type'] !== null ||
            $override['text'] !== null ||
            $override['origin'] !== null ||
            $override['count'] !== null ||
            $override['is_aayah'] !== null
        );
    }

    /**
     * @param  array<int, array{
     *     id: int,
     *     time: string,
     *     type: string,
     *     text: string,
     *     origin: string|null,
     *     is_aayah: bool,
     *     is_original: bool,
     *     count: int,
     *     order: int,
     *     is_custom: bool
     * }>  $cards
     * @param  array<int, array{
     *     thikr_id: int,
     *     order: int|null,
     *     time: string|null,
     *     type: string|null,
     *     text: string|null,
     *     origin: string|null,
     *     count: int|null,
     *     is_aayah: bool|null,
     *     is_deleted: bool,
     *     is_custom: bool
     * }>  $normalizedOverrides
     * @return array<int, array{
     *     id: int,
     *     time: string,
     *     type: string,
     *     text: string,
     *     origin: string|null,
     *     is_aayah: bool,
     *     is_original: bool,
     *     count: int,
     *     order: int,
     *     is_overridden: bool,
     *     is_custom: bool
     * }>
     */
    private function attachOverrideFlags(array $cards, array $normalizedOverrides): array
    {
        $overridesById = collect($normalizedOverrides)->keyBy('thikr_id');
        $currentDefaultIds = collect($cards)
            ->reject(fn (array $card): bool => $card['is_custom'])
            ->pluck('id')
            ->map(fn (mixed $id): int => max(0, (int) $id))
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();
        $deletedDefaultIds = collect($normalizedOverrides)
            ->filter(fn (array $override): bool => $override['is_deleted'])
            ->pluck('thikr_id')
            ->map(fn (mixed $id): int => max(0, (int) $id))
            ->filter(fn (int $id): bool => $id > 0)
            ->values()
            ->all();
        $baseDefaultIds = collect($this->defaultAthkarCards())
            ->pluck('id')
            ->map(fn (mixed $id): int => max(0, (int) $id))
            ->reject(fn (int $id): bool => in_array($id, $deletedDefaultIds, true))
            ->values()
            ->all();
        $changedOrderIds = $this->changedOrderIdsMap($baseDefaultIds, $currentDefaultIds);

        return collect($cards)
            ->map(function (array $card) use ($changedOrderIds, $overridesById): array {
                $override = $overridesById->get($card['id']);
                $isDefaultCard = ! $card['is_custom'];
                $isOrderOverrideMeaningful = $isDefaultCard
                    ? (bool) ($changedOrderIds[(int) $card['id']] ?? false)
                    : (bool) ($override['order'] ?? null);

                if (! $override) {
                    $card['is_overridden'] = $isOrderOverrideMeaningful;

                    return $card;
                }

                $card['is_overridden'] = $this->hasNonOrderOverride($override) || $isOrderOverrideMeaningful;

                return $card;
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<int, int>  $beforeOrderedIds
     * @param  array<int, int>  $afterOrderedIds
     * @return array<int, true>
     */
    private function changedOrderIdsMap(array $beforeOrderedIds, array $afterOrderedIds): array
    {
        $maxIndex = max(count($beforeOrderedIds), count($afterOrderedIds));

        if ($maxIndex < 1) {
            return [];
        }

        $changedIds = collect(range(0, $maxIndex - 1))
            ->flatMap(function (int $index) use ($beforeOrderedIds, $afterOrderedIds): array {
                if (($beforeOrderedIds[$index] ?? null) === ($afterOrderedIds[$index] ?? null)) {
                    return [];
                }

                return [
                    max(0, (int) ($beforeOrderedIds[$index] ?? 0)),
                    max(0, (int) ($afterOrderedIds[$index] ?? 0)),
                ];
            })
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($changedIds === []) {
            return [];
        }

        $changedIdsMap = [];

        foreach ($changedIds as $changedId) {
            $changedIdsMap[$changedId] = true;
        }

        return $changedIdsMap;
    }

    private function resolveAayahState(?bool $state, string $text, bool $fallback = false): bool
    {
        if ($state === true) {
            return true;
        }
        if (Thikr::isWrappedAsAayah(trim($text))) {
            return true;
        }
        if ($state === false) {
            return false;
        }

        return $fallback;
    }

    private function normalizePositiveInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $normalized = max(0, (int) $value);

        return $normalized > 0 ? $normalized : null;
    }

    private function normalizeTimeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof ThikrTime) {
            return $value->value;
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return ThikrTime::tryFrom((string) $value)?->value;
    }

    private function normalizeTypeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof ThikrType) {
            return $value->value;
        }

        if ($value instanceof BackedEnum) {
            $value = $value->value;
        }

        return ThikrType::tryFrom((string) $value)?->value;
    }

    private function normalizeNullableText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized !== '' ? $normalized : null;
    }

    private function hasOrigin(mixed $origin): bool
    {
        return $this->normalizeNullableText($origin) !== null;
    }

    /**
     * @param  array{order?: mixed, time?: mixed, type?: mixed, text?: mixed, origin?: mixed, count?: mixed, is_aayah?: mixed}  $data
     */
    private function createCustomAthkar(array $data): bool
    {
        $previousOverrides = $this->normalizeOverrides($this->athkarOverrides);
        $newThikrId = $this->nextCustomThikrId();
        $nextOrder = $this->maxResolvedOrder() + 1;

        $normalizedText = Thikr::normalizeAayahText(
            (string) ($data['text'] ?? ''),
            (bool) ($data['is_aayah'] ?? false),
        );

        $nextOverride = $this->normalizeOverride([
            'thikr_id' => $newThikrId,
            'order' => $nextOrder,
            'time' => $data['time'] ?? ThikrTime::Shared->value,
            'type' => $data['type'] ?? ThikrType::Glorification->value,
            'text' => $normalizedText,
            'origin' => $data['origin'] ?? null,
            'count' => $data['count'] ?? 1,
            'is_aayah' => (bool) ($data['is_aayah'] ?? false),
            'is_deleted' => false,
            'is_custom' => true,
        ]);

        if (! $nextOverride) {
            return false;
        }

        $nextOverrides = collect($previousOverrides)
            ->reject(fn (array $override): bool => $override['thikr_id'] === $newThikrId)
            ->push($nextOverride)
            ->values()
            ->all();

        if ($nextOverrides === $previousOverrides) {
            return false;
        }

        $this->athkarOverrides = $nextOverrides;
        $this->dispatchOverridesPersisted(changedThikrId: $newThikrId, hasOrderChange: true);

        return true;
    }

    private function deleteAthkarById(int $thikrId): bool
    {
        $defaultCard = $this->defaultCardById($thikrId);
        $previousOverrides = $this->normalizeOverrides($this->athkarOverrides);
        $existingOverride = collect($previousOverrides)->firstWhere('thikr_id', $thikrId);

        if (! $defaultCard && ! $existingOverride) {
            return false;
        }

        $deleteOverride = $this->normalizeOverride([
            'thikr_id' => $thikrId,
            'order' => $existingOverride['order'] ?? null,
            'time' => $existingOverride['time'] ?? null,
            'type' => $existingOverride['type'] ?? null,
            'text' => $existingOverride['text'] ?? null,
            'origin' => $existingOverride['origin'] ?? null,
            'count' => $existingOverride['count'] ?? null,
            'is_aayah' => $existingOverride['is_aayah'] ?? null,
            'is_deleted' => true,
            'is_custom' => (bool) ($existingOverride['is_custom'] ?? ! $defaultCard),
        ]);

        if (! $deleteOverride) {
            return false;
        }

        $nextOverrides = collect($previousOverrides)
            ->reject(fn (array $override): bool => $override['thikr_id'] === $thikrId)
            ->push($deleteOverride)
            ->values()
            ->all();

        if ($nextOverrides === $previousOverrides) {
            return false;
        }

        $this->athkarOverrides = $nextOverrides;
        $this->dispatchOverridesPersisted(changedThikrId: $thikrId, hasOrderChange: true);

        return true;
    }

    private function nextCustomThikrId(): int
    {
        $maxDefaultId = collect($this->defaultAthkarCards())
            ->pluck('id')
            ->max() ?? 0;
        $maxOverrideId = collect($this->normalizeOverrides($this->athkarOverrides))
            ->pluck('thikr_id')
            ->max() ?? 0;

        return max(1, (int) max($maxDefaultId, $maxOverrideId) + 1);
    }

    private function maxResolvedOrder(): int
    {
        return (int) (collect($this->resolvedAthkarCards())->pluck('order')->max() ?? 0);
    }

    /**
     * @return array<int, int>
     */
    private function defaultOrderMap(): array
    {
        return collect($this->defaultAthkarCards())
            ->sortBy([
                ['order', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->mapWithKeys(fn (array $card, int $index): array => [(int) $card['id'] => $index + 1])
            ->all();
    }

    /**
     * @return array{id: int, time: string, type: string, text: string, origin: string|null, is_aayah: bool, is_original: bool, count: int, order: int, is_overridden: bool, is_custom: bool}|null
     */
    private function resolvedCardById(int $id): ?array
    {
        return collect($this->resolvedAthkarCards())
            ->first(fn (array $card): bool => $card['id'] === $id);
    }

    /**
     * @return array{id: int, time: string, type: string, text: string, origin: string|null, is_aayah: bool, is_original: bool, count: int, order: int}|null
     */
    private function defaultCardById(int $id): ?array
    {
        return collect($this->defaultAthkarCards())
            ->first(fn (array $card): bool => $card['id'] === $id);
    }

    public function render(): View
    {
        return view('livewire.athkar-manager');
    }
}
