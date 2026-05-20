<?php

declare(strict_types=1);

namespace App\Livewire\QuranApp\Concerns;

use App\Services\Quran\QuranReaderDataService;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\Width;

trait InteractsWithQuranSearchAction
{
    public function searchQuranAction(): Action
    {
        return Action::make('searchQuran')
            ->modalHeading(arabic_text('البحث الشامل للقرآن الكريم'))
            ->modalDescription(arabic_text('ابحث عن آية، أو اختر سورة للانتقال المباشر.'))
            ->modalAutofocus(false)
            ->modalWidth(Width::FiveExtraLarge)
            ->modalSubmitActionLabel(arabic_text('انتقال'))
            ->modalCancelActionLabel(arabic_text('إغلاق'))
            ->extraModalWindowAttributes([
                'id' => 'quran-reader-search-modal',
                'class' => 'quran-reader-search-modal-window',
            ])
            ->fillForm(fn (): array => [
                'surah_number' => $this->defaultSearchSurahNumber(),
                'query' => '',
            ])
            ->schema([
                Select::make('surah_number')
                    ->label(arabic_text('السورة'))
                    ->options($this->searchSurahOptions())
                    ->searchable()
                    ->preload()
                    ->native(false)
                    ->placeholder(arabic_text('اختر سورة'))
                    ->helperText(arabic_text('الاختيار اختياري إذا كنت تبحث بالنص.')),
                TextInput::make('query')
                    ->label(arabic_text('بحث نصّي'))
                    ->placeholder(arabic_text('جزء من آية أو اسم سورة...'))
                    ->helperText(arabic_text('إن تُرك فارغاً سيتم الانتقال إلى السورة المختارة.'))
                    ->autocomplete('off')
                    ->maxLength(120)
                    ->columnSpanFull(),
            ])
            ->action(function (array $data, QuranReaderDataService $readerDataService): void {
                $target = $this->resolveSearchNavigationPayload($data, $readerDataService);

                if ($target === null) {
                    notify(
                        iconName: 'heroicon-o-exclamation-triangle',
                        title: arabic_text('لم يتم تحديد وجهة انتقال'),
                        body: arabic_text('اختر سورة أو أدخل عبارة بحث صحيحة.'),
                    );

                    return;
                }

                $this->dispatch(
                    'quran-go-page',
                    page: $target['page_number'],
                    source: 'search-modal',
                    activeAyahIndex: $target['highlight_ayah_index'],
                    searchHighlightAyahIndex: $target['highlight_ayah_index'],
                    surahNumber: $target['surah_number'],
                    ayahNumber: $target['ayah_number'],
                    query: $target['query'],
                );
            });
    }

    /**
     * @return array<int, string>
     */
    private function searchSurahOptions(): array
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $surahNames = $readerDataService->surahNames();
        $surahDirectory = $readerDataService->surahDirectory();
        $options = [];

        foreach ($surahDirectory as $entry) {
            $surahNumber = max(1, (int) $entry['surah_number']);
            $surahName = trim((string) ($surahNames[$surahNumber] ?? ''));
            $surahLabel = sprintf('(%d) - %s', $surahNumber, $surahName !== '' ? $surahName : '---');
            $options[$surahNumber] = arabic_text($surahLabel);
        }

        ksort($options);

        return $options;
    }

    private function defaultSearchSurahNumber(): int
    {
        /** @var QuranReaderDataService $readerDataService */
        $readerDataService = app(QuranReaderDataService::class);
        $surahDirectory = $readerDataService->surahDirectory();
        $currentPage = max(1, (int) $this->pageNumber);
        $resolvedSurahNumber = 1;

        foreach ($surahDirectory as $entry) {
            $surahNumber = max(1, (int) $entry['surah_number']);
            $surahStartPage = max(1, (int) $entry['page_number']);

            if ($surahStartPage > $currentPage) {
                break;
            }

            $resolvedSurahNumber = $surahNumber;
        }

        return $resolvedSurahNumber;
    }

    /**
     * @param  array{query?: string, surah_number?: int|string|null}  $data
     * @return array{
     *     page_number: int,
     *     surah_number: int,
     *     ayah_number: int,
     *     highlight_ayah_index: int,
     *     query: string|null
     * }|null
     */
    private function resolveSearchNavigationPayload(
        array $data,
        QuranReaderDataService $readerDataService,
    ): ?array {
        $query = trim((string) ($data['query'] ?? ''));
        $surahNumber = max(0, (int) ($data['surah_number'] ?? 0));

        if ($query !== '') {
            $firstMatch = $readerDataService->search($query, 1)[0] ?? null;

            if (is_array($firstMatch)) {
                $target = $readerDataService->resolveSearchNavigationTarget(
                    verseId: max(0, (int) $firstMatch['id']) ?: null,
                    fallbackPageNumber: max(1, (int) $firstMatch['page_number']),
                    fallbackAyahIndex: max(0, (int) $firstMatch['ayah_index']),
                    fallbackSurahNumber: max(1, (int) $firstMatch['surah_number']),
                    fallbackAyahNumber: max(0, (int) $firstMatch['ayah_number']),
                );

                return [
                    'page_number' => max(1, (int) $target['page_number']),
                    'surah_number' => max(1, (int) $target['surah_number']),
                    'ayah_number' => max(0, (int) $target['ayah_number']),
                    'highlight_ayah_index' => max(0, (int) $target['highlight_ayah_index']),
                    'query' => $query,
                ];
            }
        }

        if ($surahNumber < 1) {
            return null;
        }

        $surahStartPage = collect($readerDataService->surahDirectory())
            ->first(
                static fn (array $entry): bool => max(0, (int) $entry['surah_number']) ===
                    $surahNumber,
            );

        return [
            'page_number' => max(1, (int) ($surahStartPage['page_number'] ?? 1)),
            'surah_number' => $surahNumber,
            'ayah_number' => 0,
            'highlight_ayah_index' => 0,
            'query' => $query !== '' ? $query : null,
        ];
    }
}
