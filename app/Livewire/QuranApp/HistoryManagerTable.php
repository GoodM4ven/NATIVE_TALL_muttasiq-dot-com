<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Enums\RecordActionsPosition;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithoutUrlPagination;

class HistoryManagerTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;
    use WithoutUrlPagination;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $records = [];

    /**
     * @var array<int, string>
     */
    public array $surahNames = [];

    public function mount(): void
    {
        $this->dispatch('quran-history-manager-request-sync');
    }

    #[On('quran-history-manager-sync')]
    public function syncFromClient(array $records = [], array $surahNames = []): void
    {
        $this->surahNames = $this->normalizeSurahNames($surahNames);
        $this->records = $this->normalizeRecords($records);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSortOptionLabel('-')
            ->selectable(false)
            ->stackedOnMobile()
            ->defaultPaginationPageOption(100)
            ->paginationPageOptions([100])
            ->reorderable('sort_order')
            ->records(function (
                ?string $sortColumn,
                ?string $sortDirection,
                ?string $search,
                array $filters,
                int $page,
                int $recordsPerPage,
            ): LengthAwarePaginator {
                $records = collect($this->records);
                $records = $this->applySourceFilter($records, $filters);
                $records = $this->applySearchFilter($records, $search);
                $records = $this->applySorting($records, $sortColumn, $sortDirection);

                $total = $records->count();
                $resolvedPage = max(1, $page);
                $resolvedPerPage = max(1, $recordsPerPage);
                $items = $records
                    ->slice(($resolvedPage - 1) * $resolvedPerPage, $resolvedPerPage)
                    ->mapWithKeys(fn (array $record): array => [(string) $record['id'] => $record])
                    ->all();

                return new LengthAwarePaginator(
                    items: $items,
                    total: $total,
                    perPage: $resolvedPerPage,
                    currentPage: $resolvedPage,
                );
            })
            ->columns([
                TextColumn::make('sort_order')
                    ->label(arabic_text('الترتيب'))
                    ->state(fn (mixed $record): string => (string) max(1, (int) ($this->resolveTableRecord($record)['sort_order'] ?? 1)))
                    ->extraAttributes([
                        'lang' => 'en',
                    ])
                    ->sortable(),
                TextColumn::make('page_number')
                    ->label(arabic_text('الموضع'))
                    ->state(fn (mixed $record): string => $this->resolveHistoryLocationDescription($this->resolveTableRecord($record)))
                    ->description(fn (mixed $record): string => arabic_text(sprintf('صفحة %d', max(1, (int) ($this->resolveTableRecord($record)['page_number'] ?? 1)))))
                    ->sortable(),
                TextColumn::make('tags_text')
                    ->label(arabic_text('الوسوم والملاحظة'))
                    ->state(fn (mixed $record): string => $this->resolveHistoryTagsSummary($this->resolveTableRecord($record)))
                    ->description(fn (mixed $record): string => $this->resolveHistoryNoteSummary($this->resolveTableRecord($record)))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label(arabic_text('نوع السجل'))
                    ->placeholder(arabic_text('الكل'))
                    ->options([
                        'search-result' => arabic_text('بحث'),
                        'surah-directory' => arabic_text('تنقّل سريع'),
                        'bookmark-navigation' => arabic_text('إشارة مرجعية'),
                        'page-jump' => arabic_text('قفزة صفحة'),
                        'page-slider-commit' => arabic_text('شريط الصفحات'),
                    ]),
            ])
            ->searchable()
            ->recordActions([
                ActionGroup::make([
                    Action::make('go')
                        ->label(arabic_text('انتقال'))
                        ->icon('heroicon-s-arrow-up-right')
                        ->action(fn (mixed $record): mixed => $this->dispatch(
                            'quran-history-manager-go',
                            id: (string) ($this->resolveTableRecord($record)['id'] ?? ''),
                        )),
                    Action::make('edit')
                        ->label(arabic_text('تعديل'))
                        ->icon('heroicon-s-pencil-square')
                        ->modalSubmitActionLabel(arabic_text('تعديل'))
                        ->modalSubmitAction(fn (Action $action): Action => $action->icon('heroicon-o-pencil-square'))
                        ->fillForm(fn (mixed $record): array => [
                            'note' => (string) ($this->resolveTableRecord($record)['note'] ?? ''),
                            'tags' => array_values(array_filter(
                                array_map(
                                    static fn (mixed $tag): string => trim((string) $tag),
                                    is_array($this->resolveTableRecord($record)['tags'] ?? null) ? $this->resolveTableRecord($record)['tags'] : [],
                                ),
                                static fn (string $tag): bool => $tag !== '',
                            )),
                        ])
                        ->schema([
                            TextInput::make('note')
                                ->label(arabic_text('الملاحظة'))
                                ->maxLength(300),
                            TagsInput::make('tags')
                                ->label(arabic_text('الوسوم'))
                                ->separator(',')
                                ->nestedRecursiveRules(['min:1', 'max:60']),
                        ])
                        ->action(function (array $data, mixed $record): void {
                            $resolvedRecord = $this->resolveTableRecord($record);
                            $recordId = trim((string) ($resolvedRecord['id'] ?? ''));
                            $note = trim((string) ($data['note'] ?? ''));
                            $tags = $this->normalizeTagsInput($data['tags'] ?? []);

                            if ($recordId !== '') {
                                $this->optimisticallyUpdateRecordMetadata(
                                    recordId: $recordId,
                                    note: $note,
                                    tags: $tags,
                                );
                            }

                            $this->resetTable();
                            $this->dispatch(
                                'quran-history-manager-updated',
                                id: $recordId,
                                note: $note,
                                tags: $tags,
                            );
                        }),
                    Action::make('remove')
                        ->label(arabic_text('حذف'))
                        ->icon('heroicon-o-x-mark')
                        ->color('danger')
                        ->requiresConfirmation()
                        ->action(function (mixed $record): void {
                            $recordId = trim((string) ($this->resolveTableRecord($record)['id'] ?? ''));

                            if ($recordId !== '') {
                                $this->optimisticallyRemoveRecord($recordId);
                            }

                            $this->resetTable();
                            $this->dispatch('quran-history-manager-removed', id: $recordId);
                        }),
                ])
                    ->label(arabic_text('إجراءات'))
                    ->iconButton(),
            ], position: RecordActionsPosition::BeforeColumns)
            ->headerActions([
                Action::make('clearUntagged')
                    ->label(arabic_text('مسح غير المميّز'))
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->action(fn (): mixed => $this->dispatch('quran-history-manager-clear-untagged')),
            ])
            ->emptyStateHeading(arabic_text('لا توجد عناصر بعد.'));
    }

    public function render(): View
    {
        return view('livewire.quran-app.history-manager-table');
    }

    /**
     * @param  array<int, array<string, mixed>>  $records
     * @return array<int, array<string, mixed>>
     */
    private function normalizeRecords(array $records): array
    {
        $normalizedRecords = [];

        foreach ($records as $index => $record) {
            $id = trim((string) ($record['id'] ?? ''));

            if ($id === '') {
                continue;
            }

            $tags = array_values(array_filter(
                array_map(
                    static fn (mixed $tag): string => trim((string) $tag),
                    is_array($record['tags'] ?? null) ? $record['tags'] : [],
                ),
                static fn (string $tag): bool => $tag !== '',
            ));

            $normalizedRecords[] = [
                'id' => $id,
                'sort_order' => $this->resolveSortOrderValue($record['sort_order'] ?? null, $index),
                'page_number' => max(1, (int) ($record['page_number'] ?? 1)),
                'surah_number' => max(0, (int) ($record['surah_number'] ?? 0)),
                'source' => trim((string) ($record['source'] ?? 'search-result')),
                'note' => trim((string) ($record['note'] ?? '')),
                'tags' => $tags,
                'tags_text' => implode(', ', $tags),
                'created_at' => $this->resolveTimestampValue($record['created_at'] ?? null),
                'surah_label' => $this->resolveSurahLabel($record),
                'source_label' => $this->resolveSourceLabel($record),
            ];
        }

        return collect($normalizedRecords)
            ->sortBy('sort_order', SORT_NATURAL)
            ->values()
            ->all();
    }

    /**
     * @param  array<int|string, int|string>  $order
     */
    public function reorderTable(array $order, int|string|null $draggedRecordKey = null): void
    {
        if (! $this->getTable()->isReorderable()) {
            return;
        }

        $normalizedOrder = array_values(array_filter(
            array_map(static fn (mixed $recordKey): string => trim((string) $recordKey), $order),
            static fn (string $recordKey): bool => $recordKey !== '',
        ));

        if ($normalizedOrder === []) {
            return;
        }

        $this->getTable()->callBeforeReordering($normalizedOrder);
        $this->dispatch('quran-history-manager-reordered', order: $normalizedOrder);
        $this->getTable()->callAfterReordering($normalizedOrder);
    }

    /**
     * @param  array<int, string>  $surahNames
     * @return array<int, string>
     */
    private function normalizeSurahNames(array $surahNames): array
    {
        $normalized = [];

        foreach ($surahNames as $surahNumber => $name) {
            $normalizedSurahNumber = (int) $surahNumber;

            if ($normalizedSurahNumber < 1) {
                continue;
            }

            $normalized[$normalizedSurahNumber] = trim((string) $name);
        }

        return $normalized;
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @param  array<string, mixed>  $filters
     * @return Collection<int, array<string, mixed>>
     */
    private function applySourceFilter(Collection $records, array $filters): Collection
    {
        $source = trim((string) ($filters['source']['value'] ?? 'all'));

        if ($source === '' || $source === 'all') {
            return $records;
        }

        return $records->filter(
            static fn (array $record): bool => (string) ($record['source'] ?? '') === $source,
        )->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    private function applySearchFilter(Collection $records, ?string $search): Collection
    {
        $term = Str::lower(trim((string) $search));

        if ($term === '') {
            return $records;
        }

        return $records
            ->filter(static function (array $record) use ($term): bool {
                $note = Str::lower(trim((string) ($record['note'] ?? '')));
                $tagsText = Str::lower(trim((string) ($record['tags_text'] ?? '')));
                $surahLabel = Str::lower(trim((string) ($record['surah_label'] ?? '')));
                $sourceLabel = Str::lower(trim((string) ($record['source_label'] ?? '')));

                return Str::contains($note, $term)
                    || Str::contains($tagsText, $term)
                    || Str::contains($surahLabel, $term)
                    || Str::contains($sourceLabel, $term);
            })
            ->values();
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $records
     * @return Collection<int, array<string, mixed>>
     */
    private function applySorting(Collection $records, ?string $sortColumn, ?string $sortDirection): Collection
    {
        $column = trim((string) $sortColumn);
        $direction = trim((string) $sortDirection);
        $descending = $direction === 'desc';

        $resolvedColumn = in_array(
            $column,
            ['sort_order', 'page_number', 'surah_label', 'source_label', 'note', 'tags_text'],
            true,
        )
            ? $column
            : null;

        /** @var Collection<int, array<string, mixed>> $savedRecords */
        $savedRecords = $records
            ->filter(fn (array $record): bool => $this->recordHasPersistenceMeta($record))
            ->values();

        /** @var Collection<int, array<string, mixed>> $unsavedRecords */
        $unsavedRecords = $records
            ->reject(fn (array $record): bool => $this->recordHasPersistenceMeta($record))
            ->values();

        if ($resolvedColumn === null) {
            return $savedRecords
                ->sortBy(
                    fn (array $record): int => $this->resolveTimestampValue($record['created_at'] ?? null),
                    SORT_NUMERIC,
                    true,
                )
                ->values()
                ->concat(
                    $unsavedRecords
                        ->sortBy(
                            fn (array $record): int => $this->resolveTimestampValue(
                                $record['created_at'] ?? null,
                            ),
                            SORT_NUMERIC,
                            true,
                        )
                        ->values(),
                )
                ->values();
        }

        if ($resolvedColumn === 'sort_order') {
            return $savedRecords
                ->sortBy(
                    fn (array $record): int => max(1, (int) ($record['sort_order'] ?? 1)),
                    SORT_NUMERIC,
                    $descending,
                )
                ->values()
                ->concat(
                    $unsavedRecords
                        ->sortBy(
                            fn (array $record): int => max(1, (int) ($record['sort_order'] ?? 1)),
                            SORT_NUMERIC,
                            $descending,
                        )
                        ->values(),
                )
                ->values();
        }

        return $savedRecords
            ->sortBy(
                static fn (array $record): mixed => $record[$resolvedColumn] ?? null,
                SORT_NATURAL,
                $descending,
            )
            ->values()
            ->concat(
                $unsavedRecords
                    ->sortBy(
                        fn (array $record): int => $this->resolveTimestampValue(
                            $record['created_at'] ?? null,
                        ),
                        SORT_NUMERIC,
                        true,
                    )
                    ->values(),
            )
            ->values();
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveSurahLabel(array $record): string
    {
        $surahNumber = max(0, (int) ($record['surah_number'] ?? 0));

        if ($surahNumber < 1) {
            return '-';
        }

        $surahName = trim((string) ($this->surahNames[$surahNumber] ?? ''));

        return $surahName !== '' ? $surahName : arabic_text(sprintf('سورة %d', $surahNumber));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveSourceLabel(array $record): string
    {
        return match ((string) ($record['source'] ?? '')) {
            'surah-directory' => arabic_text('تنقّل سريع'),
            'bookmark-navigation' => arabic_text('إشارة مرجعية'),
            'page-jump' => arabic_text('قفزة صفحة'),
            'page-slider-commit' => arabic_text('شريط الصفحات'),
            default => arabic_text('بحث'),
        };
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveTagsText(array $record): string
    {
        $tags = is_array($record['tags'] ?? null) ? $record['tags'] : [];

        if ($tags === []) {
            return '';
        }

        return implode(', ', array_values(array_filter(
            array_map(static fn (mixed $tag): string => trim((string) $tag), $tags),
            static fn (string $tag): bool => $tag !== '',
        )));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveHistoryLocationDescription(array $record): string
    {
        $surah = (string) ($record['surah_label'] ?? '-');
        $source = $this->resolveSourceLabel($record);
        $sortOrder = max(1, (int) ($record['sort_order'] ?? 1));

        return arabic_text(sprintf('%d • %s • %s', $sortOrder, $surah, $source));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveHistoryTagsSummary(array $record): string
    {
        $tags = $this->resolveTagsText($record);

        if ($tags !== '') {
            return $tags;
        }

        return arabic_text('بدون وسوم');
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveHistoryNoteSummary(array $record): string
    {
        $note = trim((string) ($record['note'] ?? ''));

        if ($note !== '') {
            return $note;
        }

        return arabic_text('بدون ملاحظة');
    }

    /**
     * @return array<int, string>
     */
    private function normalizeTagsInput(mixed $value): array
    {
        $source = is_array($value) ? $value : explode(',', (string) $value);

        return array_values(array_filter(
            array_map(static fn (mixed $tag): string => trim((string) $tag), $source),
            static fn (string $tag): bool => $tag !== '',
        ));
    }

    /**
     * @return array<string, mixed>
     */
    private function resolveTableRecord(mixed $record): array
    {
        return is_array($record) ? $record : [];
    }

    private function resolveSortOrderValue(mixed $value, int $index): int
    {
        $parsed = (int) $value;

        if ($parsed > 0) {
            return $parsed;
        }

        return $index + 1;
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function recordHasPersistenceMeta(array $record): bool
    {
        $tags = is_array($record['tags'] ?? null) ? $record['tags'] : [];
        $note = trim((string) ($record['note'] ?? ''));

        return $tags !== [] || $note !== '';
    }

    private function resolveTimestampValue(mixed $value): int
    {
        $parsed = (int) $value;

        if ($parsed > 0) {
            return $parsed;
        }

        return 0;
    }

    /**
     * @param  array<int, string>  $tags
     */
    private function optimisticallyUpdateRecordMetadata(string $recordId, string $note, array $tags): void
    {
        $this->records = collect($this->records)
            ->map(function (array $record) use ($recordId, $note, $tags): array {
                if ((string) ($record['id'] ?? '') !== $recordId) {
                    return $record;
                }

                return [
                    ...$record,
                    'note' => $note,
                    'tags' => $tags,
                    'tags_text' => implode(', ', $tags),
                ];
            })
            ->values()
            ->all();
    }

    private function optimisticallyRemoveRecord(string $recordId): void
    {
        $this->records = collect($this->records)
            ->reject(fn (array $record): bool => (string) ($record['id'] ?? '') === $recordId)
            ->values()
            ->all();
    }
}
