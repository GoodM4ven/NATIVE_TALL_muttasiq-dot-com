<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp;

use Filament\Actions\Action;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Contracts\HasActions;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Concerns\InteractsWithSchemas;
use Filament\Schemas\Contracts\HasSchemas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Contracts\View\View;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Livewire\Component;

class HistoryManagerTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

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
        $this->records = $this->normalizeRecords($records);
        $this->surahNames = $this->normalizeSurahNames($surahNames);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
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
                    ->state(static fn (array $record): string => (string) max(1, (int) ($record['sort_order'] ?? 1)))
                    ->extraAttributes([
                        'lang' => 'en',
                    ])
                    ->sortable(),
                TextColumn::make('page_number')
                    ->label(arabic_text('الصفحة'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('surah_label')
                    ->label(arabic_text('السورة'))
                    ->state(fn (array $record): string => $this->resolveSurahLabel($record)),
                TextColumn::make('source_label')
                    ->label(arabic_text('النوع'))
                    ->badge()
                    ->state(fn (array $record): string => $this->resolveSourceLabel($record)),
                TextColumn::make('note')
                    ->label(arabic_text('الملاحظة'))
                    ->wrap(),
                TextColumn::make('tags_text')
                    ->label(arabic_text('الوسوم'))
                    ->state(fn (array $record): string => $this->resolveTagsText($record))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('source')
                    ->label(arabic_text('نوع السجل'))
                    ->options([
                        'all' => arabic_text('الكل'),
                        'search-result' => arabic_text('بحث'),
                        'surah-directory' => arabic_text('تنقّل سريع'),
                        'bookmark-navigation' => arabic_text('إشارة مرجعية'),
                        'page-jump' => arabic_text('قفزة صفحة'),
                        'page-slider-commit' => arabic_text('شريط الصفحات'),
                    ])
                    ->default('all'),
            ])
            ->searchable()
            ->recordActions([
                Action::make('go')
                    ->label(arabic_text('انتقال'))
                    ->icon('heroicon-s-arrow-up-right')
                    ->action(fn (array $record): mixed => $this->dispatch(
                        'quran-history-manager-go',
                        id: (string) ($record['id'] ?? ''),
                    )),
                Action::make('edit')
                    ->label(arabic_text('تعديل'))
                    ->icon('heroicon-s-pencil-square')
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
                        TextInput::make('note')
                            ->label(arabic_text('الملاحظة'))
                            ->maxLength(300),
                        TagsInput::make('tags')
                            ->label(arabic_text('الوسوم'))
                            ->separator(',')
                            ->nestedRecursiveRules(['min:1', 'max:60']),
                    ])
                    ->action(fn (array $data, array $record): mixed => $this->dispatch(
                        'quran-history-manager-updated',
                        id: (string) ($record['id'] ?? ''),
                        note: (string) ($data['note'] ?? ''),
                        tags: $this->normalizeTagsInput($data['tags'] ?? []),
                    )),
            ])
            ->headerActions([
                Action::make('clearUntagged')
                    ->label(arabic_text('مسح غير المميّز'))
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

                return Str::contains($note, $term) || Str::contains($tagsText, $term);
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
        $descending = trim((string) $sortDirection) === 'desc';

        $resolvedColumn = in_array($column, ['sort_order', 'page_number', 'note', 'tags_text'], true)
            ? $column
            : 'sort_order';

        return $records
            ->sortBy(
                static fn (array $record): mixed => $record[$resolvedColumn] ?? null,
                SORT_NATURAL,
                $descending,
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
            return '-';
        }

        return implode(', ', array_values(array_filter(
            array_map(static fn (mixed $tag): string => trim((string) $tag), $tags),
            static fn (string $tag): bool => $tag !== '',
        )));
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
}
