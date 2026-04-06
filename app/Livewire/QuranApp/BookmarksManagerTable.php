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

class BookmarksManagerTable extends Component implements HasActions, HasSchemas, HasTable
{
    use InteractsWithActions;
    use InteractsWithSchemas;
    use InteractsWithTable;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $records = [];

    #[On('quran-bookmarks-manager-sync')]
    public function syncFromClient(array $records = []): void
    {
        $this->records = $this->normalizeRecords($records);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->afterReordering(fn (array $order): mixed => $this->dispatch(
                'quran-bookmarks-manager-reordered',
                order: $order,
            ))
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
                    ->numeric()
                    ->sortable(),
                TextColumn::make('page_number')
                    ->label(arabic_text('الصفحة'))
                    ->badge()
                    ->sortable(),
                TextColumn::make('note')
                    ->label(arabic_text('الملاحظة'))
                    ->wrap(),
                TextColumn::make('tags_text')
                    ->label(arabic_text('الوسوم'))
                    ->state(fn (array $record): string => $this->resolveTagsText($record))
                    ->wrap(),
            ])
            ->filters([
                SelectFilter::make('tag_state')
                    ->label(arabic_text('الوسوم'))
                    ->options([
                        'all' => arabic_text('الكل'),
                        'tagged' => arabic_text('بوسوم'),
                        'untagged' => arabic_text('بدون وسوم'),
                    ])
                    ->default('all'),
            ])
            ->searchable()
            ->recordActions([
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
                        'quran-bookmarks-manager-updated',
                        id: (string) ($record['id'] ?? ''),
                        note: (string) ($data['note'] ?? ''),
                        tags: array_values($data['tags'] ?? []),
                    )),
                Action::make('replacePage')
                    ->label(arabic_text('استبدال الصفحة'))
                    ->icon('heroicon-s-arrow-path')
                    ->action(fn (array $record): mixed => $this->dispatch(
                        'quran-bookmarks-manager-replaced',
                        id: (string) ($record['id'] ?? ''),
                    )),
                Action::make('remove')
                    ->label(arabic_text('حذف'))
                    ->color('danger')
                    ->requiresConfirmation()
                    ->icon('heroicon-s-trash')
                    ->action(fn (array $record): mixed => $this->dispatch(
                        'quran-bookmarks-manager-removed',
                        id: (string) ($record['id'] ?? ''),
                    )),
            ])
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
                'sort_order' => max(1, (int) ($record['sort_order'] ?? $index + 1)),
                'page_number' => max(1, (int) ($record['page_number'] ?? 1)),
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
}
