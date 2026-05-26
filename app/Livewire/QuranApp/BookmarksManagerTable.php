<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp;

use App\Services\Quran\QuranReaderDataService;
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

class BookmarksManagerTable extends Component implements HasActions, HasSchemas, HasTable
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

    /**
     * @var array<int, array{surah_number: int, page_number: int}>
     */
    public array $surahDirectory = [];

    public function mount(): void
    {
        $this->initializeSurahMetadata();
        $this->dispatch('quran-bookmarks-manager-request-sync');
    }

    #[On('quran-bookmarks-manager-sync')]
    public function syncFromClient(
        array $records = [],
        array $surahNames = [],
        array $surahDirectory = [],
    ): void {
        if ($surahNames !== []) {
            $this->surahNames = $this->normalizeSurahNames($surahNames);
        }

        if ($surahDirectory !== []) {
            $this->surahDirectory = $this->normalizeSurahDirectory($surahDirectory);
        }

        if ($this->surahNames === [] || $this->surahDirectory === []) {
            $this->initializeSurahMetadata();
        }

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
                $records = $this->applyTagsFilter($records, $filters);
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
                    ->state(static fn (array $record): string => (string) max(1, (int) ($record['sort_order'] ?? 1)))
                    ->extraAttributes([
                        'lang' => 'en',
                    ])
                    ->sortable(),
                TextColumn::make('page_number')
                    ->label(arabic_text('الموضع'))
                    ->state(fn (array $record): string => $this->resolveBookmarkLocationDescription($record))
                    ->description(fn (array $record): string => arabic_text(sprintf('صفحة %d', max(1, (int) ($record['page_number'] ?? 1)))))
                    ->sortable(),
                TextColumn::make('tags_text')
                    ->label(arabic_text('الوسوم والملاحظة'))
                    ->state(fn (array $record): string => $this->resolveBookmarkTagsSummary($record))
                    ->description(fn (array $record): string => $this->resolveBookmarkNoteSummary($record))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('tag_state')
                    ->label(arabic_text('الوسوم'))
                    ->placeholder(arabic_text('الكل'))
                    ->options([
                        'tagged' => arabic_text('بوسوم'),
                        'untagged' => arabic_text('بدون وسوم'),
                    ]),
            ])
            ->searchable()
            ->recordActions([
                ActionGroup::make([
                    Action::make('go')
                        ->label(arabic_text('انتقال'))
                        ->icon('heroicon-s-arrow-up-right')
                        ->action(fn (array $record): mixed => $this->dispatch(
                            'quran-bookmarks-manager-go',
                            id: (string) ($record['id'] ?? ''),
                        )),
                    Action::make('edit')
                        ->label(arabic_text('تعديل'))
                        ->icon('heroicon-s-pencil-square')
                        ->modalSubmitActionLabel(arabic_text('تعديل'))
                        ->modalSubmitAction(fn (Action $action): Action => $action->icon('heroicon-o-pencil-square'))
                        ->fillForm(fn (array $record): array => [
                            'note' => (string) ($record['note'] ?? ''),
                            'tags' => array_values(array_filter(
                                array_map(
                                    static fn (mixed $tag): string => trim((string) $tag),
                                    is_array($record['tags'] ?? null) ? $record['tags'] : [],
                                ),
                                static fn (string $tag): bool => $tag !== '',
                            )),
                        ])
                        ->schema([
                            TagsInput::make('tags')
                                ->label(arabic_text('الوسوم'))
                                ->separator(',')
                                ->nestedRecursiveRules(['min:1', 'max:60']),
                            TextInput::make('note')
                                ->label(arabic_text('الملاحظة'))
                                ->maxLength(300),
                        ])
                        ->action(fn (array $data, array $record): mixed => $this->dispatch(
                            'quran-bookmarks-manager-updated',
                            id: (string) ($record['id'] ?? ''),
                            note: (string) ($data['note'] ?? ''),
                            tags: $this->normalizeTagsInput($data['tags'] ?? []),
                        )),
                    Action::make('replacePage')
                        ->label(arabic_text('استبدال الصفحة'))
                        ->icon('heroicon-s-arrow-path')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading(arabic_text('تأكيد استبدال الصفحة'))
                        ->modalDescription(arabic_text('سيتم استبدال الصفحة المحفوظة لهذه العلامة بالصفحة الحالية.'))
                        ->modalSubmitActionLabel(arabic_text('استبدال الصفحة'))
                        ->action(fn (array $record): mixed => $this->dispatch(
                            'quran-bookmarks-manager-replaced',
                            id: (string) ($record['id'] ?? ''),
                        )),
                    Action::make('remove')
                        ->label(arabic_text('حذف'))
                        ->color('danger')
                        ->requiresConfirmation()
                        ->icon('heroicon-o-x-mark')
                        ->action(fn (array $record): mixed => $this->dispatch(
                            'quran-bookmarks-manager-removed',
                            id: (string) ($record['id'] ?? ''),
                        )),
                ])
                    ->label(arabic_text('إجراءات'))
                    ->iconButton(),
            ], position: RecordActionsPosition::BeforeColumns)
            ->emptyStateHeading(arabic_text('لا توجد علامات محفوظة.'));
    }

    public function render(): View
    {
        return view('livewire.quran-app.bookmarks-manager-table');
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
                'note' => trim((string) ($record['note'] ?? '')),
                'tags' => $tags,
                'tags_text' => implode(', ', $tags),
                'created_at' => $this->resolveTimestampValue($record['created_at'] ?? null),
                'updated_at' => $this->resolveTimestampValue($record['updated_at'] ?? null),
                'surah_label' => $this->resolveBookmarkSurahLabel(
                    max(1, (int) ($record['page_number'] ?? 1)),
                ),
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
        $this->dispatch('quran-bookmarks-manager-reordered', order: $normalizedOrder);
        $this->getTable()->callAfterReordering($normalizedOrder);
    }

    private function applyTagsFilter(Collection $records, array $filters): Collection
    {
        $tagState = trim((string) ($filters['tag_state']['value'] ?? 'all'));

        if ($tagState === '' || $tagState === 'all') {
            return $records;
        }

        if ($tagState === 'tagged') {
            return $records->filter(
                static fn (array $record): bool => is_array($record['tags'] ?? null) && $record['tags'] !== [],
            )->map(static fn (array $record): array => $record)->values();
        }

        if ($tagState === 'untagged') {
            return $records->filter(
                static fn (array $record): bool => ! is_array($record['tags'] ?? null) || $record['tags'] === [],
            )->map(static fn (array $record): array => $record)->values();
        }

        return $records;
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

                return Str::contains($note, $term)
                    || Str::contains($tagsText, $term)
                    || Str::contains($surahLabel, $term);
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
            ['sort_order', 'page_number', 'surah_label', 'note', 'tags_text'],
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

        if ($resolvedColumn === null || $resolvedColumn === 'sort_order') {
            $sortSavedDescending = $resolvedColumn === null ? true : $descending;

            return $savedRecords
                ->sortBy(
                    fn (array $record): int => max(1, (int) ($record['sort_order'] ?? 1)),
                    SORT_NUMERIC,
                    $sortSavedDescending,
                )
                ->values()
                ->concat(
                    $unsavedRecords
                        ->sortBy(
                            fn (array $record): int => $this->resolveBookmarkRecency($record),
                            SORT_NUMERIC,
                            true,
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
                        fn (array $record): int => $this->resolveBookmarkRecency($record),
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
    private function resolveBookmarkLocationDescription(array $record): string
    {
        $sortOrder = max(1, (int) ($record['sort_order'] ?? 1));
        $surah = trim((string) ($record['surah_label'] ?? '-'));

        return arabic_text(sprintf('%d • %s', $sortOrder, $surah));
    }

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveBookmarkTagsSummary(array $record): string
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
    private function resolveBookmarkNoteSummary(array $record): string
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

    private function resolveSortOrderValue(mixed $value, int $index): int
    {
        $parsed = (int) $value;

        if ($parsed > 0) {
            return $parsed;
        }

        return $index + 1;
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
     * @param  array<int, array<string, mixed>>  $surahDirectory
     * @return array<int, array{surah_number: int, page_number: int}>
     */
    private function normalizeSurahDirectory(array $surahDirectory): array
    {
        $normalized = [];

        foreach ($surahDirectory as $entry) {
            $surahNumber = (int) ($entry['surah_number'] ?? 0);
            $pageNumber = (int) ($entry['page_number'] ?? 0);

            if ($surahNumber < 1 || $pageNumber < 1) {
                continue;
            }

            $normalized[] = [
                'surah_number' => $surahNumber,
                'page_number' => $pageNumber,
            ];
        }

        return collect($normalized)
            ->sortBy('page_number', SORT_NUMERIC)
            ->values()
            ->all();
    }

    private function resolveBookmarkSurahLabel(int $pageNumber): string
    {
        if ($this->surahDirectory === [] || $this->surahNames === []) {
            $this->initializeSurahMetadata();
        }

        $resolvedSurahNumber = 0;

        foreach ($this->surahDirectory as $entry) {
            $entryPage = max(1, (int) $entry['page_number']);

            if ($entryPage > $pageNumber) {
                break;
            }

            $resolvedSurahNumber = max(1, (int) $entry['surah_number']);
        }

        if ($resolvedSurahNumber < 1) {
            $resolvedSurahNumber = 1;
        }

        $surahName = trim((string) ($this->surahNames[$resolvedSurahNumber] ?? ''));

        return $surahName !== '' ? $surahName : arabic_text(sprintf('سورة %d', $resolvedSurahNumber));
    }

    private function initializeSurahMetadata(): void
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);

        if ($this->surahNames === []) {
            $this->surahNames = $this->normalizeSurahNames($readerDataService->surahNames());
        }

        if ($this->surahDirectory === []) {
            $this->surahDirectory = $this->normalizeSurahDirectory($readerDataService->surahDirectory());
        }
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

    /**
     * @param  array<string, mixed>  $record
     */
    private function resolveBookmarkRecency(array $record): int
    {
        $updatedAt = $this->resolveTimestampValue($record['updated_at'] ?? null);

        if ($updatedAt > 0) {
            return $updatedAt;
        }

        return $this->resolveTimestampValue($record['created_at'] ?? null);
    }

    private function resolveTimestampValue(mixed $value): int
    {
        $parsed = (int) $value;

        if ($parsed > 0) {
            return $parsed;
        }

        return 0;
    }
}
