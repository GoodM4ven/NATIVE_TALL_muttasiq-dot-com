<?php

declare(strict_types=1);

use GoodMaven\Arabicable\Facades\ArabicFilter;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! (bool) config('arabicable.features.quran', true)) {
            return;
        }

        Schema::create('quran_verses', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('surah_number');
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedSmallInteger('ayah_index');
            $table->unsignedSmallInteger('mushaf_page')->nullable();
            $table->unsignedTinyInteger('mushaf_line')->nullable();
            $table->text('text_uthmani');
            $table->text('text_searchable');
            $table->text('text_searchable_typed');
            $table->timestamps();
        });

        Schema::create('quran_words', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->unsignedTinyInteger('surah_number');
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedSmallInteger('ayah_index');
            $table->unsignedSmallInteger('word_position');
            $table->unsignedInteger('global_word_index');
            $table->string('token_uthmani', 191);
            $table->string('token_searchable', 191);
            $table->string('token_searchable_typed', 191);
            $table->string('token_stem', 191)->nullable();
            $table->string('token_root', 191)->nullable();
            $table->string('token_lemma', 191)->nullable();
        });

        Schema::create('quran_mushaf_lines', function (Blueprint $table): void {
            $table->id();
            $table->string('layout_key', 64);
            $table->unsignedSmallInteger('page_number');
            $table->unsignedTinyInteger('line_number');
            $table->string('line_type', 24)->default('ayah');
            $table->boolean('is_centered')->default(false);
            $table->unsignedInteger('first_word_index')->nullable();
            $table->unsignedInteger('last_word_index')->nullable();
            $table->unsignedTinyInteger('surah_number')->nullable();
            $table->timestamps();
        });

        $this->importQuranIndexData();
        $this->importMushafLayoutData();
        $this->createQuranReaderIndexes();
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_mushaf_lines');
        Schema::dropIfExists('quran_words');
        Schema::dropIfExists('quran_verses');
    }

    private function createQuranReaderIndexes(): void
    {
        Schema::table('quran_verses', function (Blueprint $table): void {
            $table->unique(['surah_number', 'ayah_number']);
            $table->unique('ayah_index');
        });

        Schema::table('quran_words', function (Blueprint $table): void {
            $table->unique(['surah_number', 'ayah_number', 'word_position']);
            $table->unique('global_word_index');
            $table->index('token_searchable');
            $table->index('token_searchable_typed');
            $table->index('token_stem');
            $table->index('token_root');
            $table->index('token_lemma');
        });

        Schema::table('quran_mushaf_lines', function (Blueprint $table): void {
            $table->unique(['layout_key', 'page_number', 'line_number']);
            $table->index(['page_number', 'line_number']);
            $table->index(['first_word_index', 'last_word_index']);
        });
    }

    private function importQuranIndexData(): void
    {
        $quranDirectory = $this->resolveQuranDirectory();

        if ($quranDirectory === null) {
            throw new RuntimeException('Quran source files were not found. Expected source-othmani-surah-###.txt files.');
        }

        $verses = $this->collectVerses($quranDirectory);

        if ($verses === []) {
            throw new RuntimeException('No Quran verses were imported from Othmani source files.');
        }

        $timestamp = now();
        $verseRows = [];

        foreach ($verses as $verse) {
            $verseRows[] = [
                'id' => $verse['ayah_index'],
                'surah_number' => $verse['surah_number'],
                'ayah_number' => $verse['ayah_number'],
                'ayah_index' => $verse['ayah_index'],
                'mushaf_page' => null,
                'mushaf_line' => null,
                'text_uthmani' => $verse['text_uthmani'],
                'text_searchable' => $verse['text_searchable'],
                'text_searchable_typed' => $verse['text_searchable_typed'],
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];

            if (count($verseRows) >= 500) {
                DB::table('quran_verses')->insert($verseRows);
                $verseRows = [];
            }
        }

        if ($verseRows !== []) {
            DB::table('quran_verses')->insert($verseRows);
        }

        $verseIdMap = [];

        foreach ($verses as $verse) {
            $verseIdMap[$verse['surah_number'].':'.$verse['ayah_number']] = $verse['ayah_index'];
        }

        $lexicalLookupByLocation = $this->loadQuranWordLookupByLocation($quranDirectory);
        $wordRows = [];
        $globalWordIndex = 0;

        foreach ($verses as $verse) {
            $key = $verse['surah_number'].':'.$verse['ayah_number'];
            $verseId = $verseIdMap[$key] ?? null;

            if ($verseId === null) {
                continue;
            }

            $tokens = $this->tokenizeAyah($verse['text_uthmani']);
            $searchableTokens = array_map(static fn (string $token): string => ArabicFilter::forSearch($token), $tokens);
            $typedSearchableTokens = array_map(fn (string $token): string => $this->normalizeQuranForTypedSearch($token), $tokens);

            $wordPosition = 0;

            foreach ($tokens as $index => $token) {
                $searchableToken = $searchableTokens[$index] ?? '';
                $typedSearchableToken = $typedSearchableTokens[$index] ?? '';

                if ($searchableToken === '' && $typedSearchableToken === '') {
                    continue;
                }

                $wordPosition++;
                $globalWordIndex++;

                $wordLocation = $verse['surah_number'].':'.$verse['ayah_number'].':'.$wordPosition;
                $lexical = $lexicalLookupByLocation[$wordLocation] ?? null;

                $wordRows[] = [
                    'verse_id' => $verseId,
                    'surah_number' => $verse['surah_number'],
                    'ayah_number' => $verse['ayah_number'],
                    'ayah_index' => $verse['ayah_index'],
                    'word_position' => $wordPosition,
                    'global_word_index' => $globalWordIndex,
                    'token_uthmani' => $token,
                    'token_searchable' => $searchableToken !== '' ? $searchableToken : $typedSearchableToken,
                    'token_searchable_typed' => $typedSearchableToken !== '' ? $typedSearchableToken : $searchableToken,
                    'token_stem' => $lexical['stem'] ?? null,
                    'token_root' => $lexical['root'] ?? null,
                    'token_lemma' => $lexical['lemma'] ?? null,
                ];

                if (count($wordRows) >= 1200) {
                    DB::table('quran_words')->insert($wordRows);
                    $wordRows = [];
                }
            }
        }

        if ($wordRows !== []) {
            DB::table('quran_words')->insert($wordRows);
        }
    }

    private function importMushafLayoutData(): void
    {
        $layout = $this->resolveMushafLayoutDatabase();

        if ($layout === null) {
            return;
        }

        $database = new SQLite3($layout['path'], SQLITE3_OPEN_READONLY);
        $result = $database->query('SELECT page_number, line_number, line_type, is_centered, first_word_id, last_word_id, surah_number FROM pages ORDER BY page_number, line_number');

        if (! $result instanceof SQLite3Result) {
            $database->close();

            return;
        }

        $now = now();
        $rows = [];

        while (true) {
            $item = $result->fetchArray(SQLITE3_ASSOC);

            if (! is_array($item)) {
                break;
            }

            $pageNumber = (int) ($item['page_number'] ?? 0);
            $lineNumber = (int) ($item['line_number'] ?? 0);

            if ($pageNumber < 1 || $lineNumber < 1) {
                continue;
            }

            $rows[] = [
                'layout_key' => $layout['key'],
                'page_number' => $pageNumber,
                'line_number' => $lineNumber,
                'line_type' => trim((string) ($item['line_type'] ?? 'ayah')),
                'is_centered' => ((int) ($item['is_centered'] ?? 0)) === 1,
                'first_word_index' => $this->nullablePositiveInteger($item['first_word_id'] ?? null),
                'last_word_index' => $this->nullablePositiveInteger($item['last_word_id'] ?? null),
                'surah_number' => $this->nullableTinyInteger($item['surah_number'] ?? null),
                'created_at' => $now,
                'updated_at' => $now,
            ];

            if (count($rows) >= 500) {
                DB::table('quran_mushaf_lines')->insert($rows);

                $rows = [];
            }
        }

        if ($rows !== []) {
            DB::table('quran_mushaf_lines')->insert($rows);
        }

        $database->close();

        $this->assignVerseMushafLocations($layout['key']);
    }

    private function assignVerseMushafLocations(string $layoutKey): void
    {
        $lineRows = DB::table('quran_mushaf_lines')
            ->select(['page_number', 'line_number', 'first_word_index', 'last_word_index'])
            ->where('layout_key', $layoutKey)
            ->whereNotNull('first_word_index')
            ->whereNotNull('last_word_index')
            ->orderBy('first_word_index')
            ->get()
            ->all();

        if ($lineRows === []) {
            return;
        }

        $lineCursor = 0;
        $lineCount = count($lineRows);

        $verseRows = DB::table('quran_words')
            ->selectRaw('verse_id, MIN(global_word_index) AS first_word_index')
            ->groupBy('verse_id')
            ->orderBy('first_word_index')
            ->cursor();

        foreach ($verseRows as $verseRow) {
            $firstWordIndex = (int) $verseRow->first_word_index;

            while ($lineCursor < $lineCount && (int) $lineRows[$lineCursor]->last_word_index < $firstWordIndex) {
                $lineCursor++;
            }

            $mushafPage = null;
            $mushafLine = null;

            if ($lineCursor < $lineCount) {
                $line = $lineRows[$lineCursor];
                $lineFirstWordIndex = (int) $line->first_word_index;
                $lineLastWordIndex = (int) $line->last_word_index;

                if ($firstWordIndex >= $lineFirstWordIndex && $firstWordIndex <= $lineLastWordIndex) {
                    $mushafPage = (int) $line->page_number;
                    $mushafLine = (int) $line->line_number;
                }
            }

            DB::table('quran_verses')
                ->where('id', (int) $verseRow->verse_id)
                ->update([
                    'mushaf_page' => $mushafPage,
                    'mushaf_line' => $mushafLine,
                ]);
        }
    }

    /**
     * @return array<int, array{surah_number: int, ayah_number: int, ayah_index: int, text_uthmani: string, text_searchable: string, text_searchable_typed: string}>
     */
    private function collectVerses(string $quranDirectory): array
    {
        $verses = [];
        $ayahIndex = 0;

        for ($surah = 1; $surah <= 114; $surah++) {
            $path = sprintf('%s/source-othmani-surah-%03d.txt', $quranDirectory, $surah);

            if (! is_file($path)) {
                throw new RuntimeException('Missing Quran source file: '.$path);
            }

            $file = new SplFileObject($path, 'r');

            while (! $file->eof()) {
                $line = trim((string) $file->fgets());

                if ($line === '') {
                    continue;
                }

                $parsed = $this->parseAyahLine($line);

                if ($parsed === null) {
                    continue;
                }

                $ayahIndex++;
                $ayahText = $this->normalizeQuranTextSpacing($parsed['ayah_text']);

                $verses[] = [
                    'surah_number' => $surah,
                    'ayah_number' => $parsed['ayah_number'],
                    'ayah_index' => $ayahIndex,
                    'text_uthmani' => $ayahText,
                    'text_searchable' => ArabicFilter::forSearch($ayahText),
                    'text_searchable_typed' => $this->normalizeQuranForTypedSearch($ayahText),
                ];
            }
        }

        return $verses;
    }

    /**
     * @return array{ayah_number: int, ayah_text: string}|null
     */
    private function parseAyahLine(string $line): ?array
    {
        if (preg_match('/^(?<text>.+?)\s*۝\s*(?<ayah>[٠-٩]+)\s*$/u', $line, $matches) !== 1) {
            return null;
        }

        $ayahNumber = (int) $this->toAsciiDigits((string) $matches['ayah']);

        if ($ayahNumber < 1) {
            return null;
        }

        $ayahText = trim((string) $matches['text']);

        if ($ayahText === '') {
            return null;
        }

        return [
            'ayah_number' => $ayahNumber,
            'ayah_text' => $ayahText,
        ];
    }

    /**
     * @return array<int, string>
     */
    private function tokenizeAyah(string $ayahText): array
    {
        $normalizedAyah = $this->normalizeQuranTextSpacing($ayahText);
        $parts = preg_split('/\s+/u', trim($normalizedAyah));

        if (! is_array($parts)) {
            return [];
        }

        $tokens = [];

        foreach ($parts as $part) {
            $token = preg_replace('/[ۖۗۘۙۚۛۜ۞]/u', '', $part);
            $token = trim((string) $token);

            if ($token === '') {
                continue;
            }

            $tokens[] = $token;
        }

        return $tokens;
    }

    private function normalizeQuranTextSpacing(string $text): string
    {
        $normalized = strtr($text, [
            "\u{00A0}" => ' ',
            "\u{2007}" => ' ',
            "\u{202F}" => ' ',
        ]);

        $normalized = preg_replace('/\s+([\x{064B}-\x{065F}\x{0670}\x{06D6}-\x{06ED}])/u', '$1', $normalized) ?? $normalized;
        $normalized = preg_replace('/\h+/u', ' ', $normalized) ?? $normalized;

        return trim($normalized);
    }

    private function resolveQuranDirectory(): ?string
    {
        $configured = (string) config('arabicable.data_sources.quran_othmani_surahs_dir', '');

        $candidates = [
            $configured,
            base_path('resources/raw-data/quran'),
            basename(base_path()) === 'workbench'
                ? dirname(base_path()).'/resources/raw-data/quran'
                : '',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran'),
            dirname(__DIR__, 2).'/resources/raw-data/quran',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate !== '' && is_dir($candidate)) {
                $resolved = realpath($candidate);

                if ($resolved !== false) {
                    return $resolved;
                }

                return $candidate;
            }
        }

        return null;
    }

    /**
     * @return array{path: string, key: string}|null
     */
    private function resolveMushafLayoutDatabase(): ?array
    {
        $configured = (string) config('arabicable.data_sources.quran_layout_databases_dir', '');

        $directories = [
            $configured,
            base_path('resources/raw-data/quran/layouts'),
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/layouts'),
            dirname(__DIR__, 2).'/resources/raw-data/quran/layouts',
        ];

        $files = [
            'digital-khatt-15-lines.db',
            'qpc-v2-15-lines.db',
            'taj-indopak-16-lines.db',
        ];

        foreach ($directories as $directory) {
            if ($directory === '' || ! is_dir($directory)) {
                continue;
            }

            foreach ($files as $fileName) {
                $path = $directory.'/'.$fileName;

                if (! is_file($path)) {
                    continue;
                }

                return [
                    'path' => $path,
                    'key' => pathinfo($fileName, PATHINFO_FILENAME),
                ];
            }
        }

        return null;
    }

    /**
     * @return array<string, array{lemma: string|null, stem: string|null, root: string|null}>
     */
    private function loadQuranWordLookupByLocation(string $quranDirectory): array
    {
        $lookup = [];
        $databases = $this->resolveQuranLexiconDatabases($quranDirectory);

        if ($databases['lemma'] !== null) {
            $database = new SQLite3($databases['lemma'], SQLITE3_OPEN_READONLY);
            $result = $database->query(
                'SELECT lw.word_location AS word_location, l.text_clean AS lemma_text
                 FROM lemma_words lw
                 INNER JOIN lemmas l ON l.id = lw.lemma_id',
            );

            if ($result instanceof SQLite3Result) {
                while (true) {
                    $row = $result->fetchArray(SQLITE3_ASSOC);

                    if (! is_array($row)) {
                        break;
                    }

                    $location = trim((string) ($row['word_location'] ?? ''));

                    if ($location === '') {
                        continue;
                    }

                    $lemma = $this->normalizeQuranForTypedSearch((string) ($row['lemma_text'] ?? ''));

                    if ($lemma === '') {
                        continue;
                    }

                    $lookup[$location] ??= ['lemma' => null, 'stem' => null, 'root' => null];
                    $lookup[$location]['lemma'] = $lemma;
                }
            }

            $database->close();
        }

        if ($databases['stem'] !== null) {
            $database = new SQLite3($databases['stem'], SQLITE3_OPEN_READONLY);
            $result = $database->query(
                'SELECT sw.word_location AS word_location, s.text_clean AS stem_text
                 FROM stem_words sw
                 INNER JOIN stems s ON s.id = sw.stem_id',
            );

            if ($result instanceof SQLite3Result) {
                while (true) {
                    $row = $result->fetchArray(SQLITE3_ASSOC);

                    if (! is_array($row)) {
                        break;
                    }

                    $location = trim((string) ($row['word_location'] ?? ''));

                    if ($location === '') {
                        continue;
                    }

                    $stem = $this->normalizeQuranForTypedSearch((string) ($row['stem_text'] ?? ''));

                    if ($stem === '') {
                        continue;
                    }

                    $lookup[$location] ??= ['lemma' => null, 'stem' => null, 'root' => null];
                    $lookup[$location]['stem'] = $stem;
                }
            }

            $database->close();
        }

        if ($databases['root'] !== null) {
            $database = new SQLite3($databases['root'], SQLITE3_OPEN_READONLY);
            $result = $database->query(
                'SELECT rw.word_location AS word_location, r.arabic_trilateral AS root_text
                 FROM root_words rw
                 INNER JOIN roots r ON r.id = rw.root_id',
            );

            if ($result instanceof SQLite3Result) {
                while (true) {
                    $row = $result->fetchArray(SQLITE3_ASSOC);

                    if (! is_array($row)) {
                        break;
                    }

                    $location = trim((string) ($row['word_location'] ?? ''));

                    if ($location === '') {
                        continue;
                    }

                    $root = $this->normalizeQuranRoot((string) ($row['root_text'] ?? ''));

                    if ($root === '') {
                        continue;
                    }

                    $lookup[$location] ??= ['lemma' => null, 'stem' => null, 'root' => null];
                    $lookup[$location]['root'] = $root;
                }
            }

            $database->close();
        }

        return $lookup;
    }

    /**
     * @return array{lemma: string|null, stem: string|null, root: string|null}
     */
    private function resolveQuranLexiconDatabases(string $quranDirectory): array
    {
        $configuredDirectory = (string) config('arabicable.data_sources.quran_lexicon_databases_dir', '');

        $directories = [
            $configuredDirectory,
            base_path('resources/raw-data/quran/lexicon'),
            basename(base_path()) === 'workbench'
                ? dirname(base_path()).'/resources/raw-data/quran/lexicon'
                : '',
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/lexicon'),
            dirname(__DIR__, 2).'/resources/raw-data/quran/lexicon',
            $quranDirectory,
        ];

        $resolved = [
            'lemma' => null,
            'stem' => null,
            'root' => null,
        ];

        foreach ($directories as $directory) {
            if ($directory === '' || ! is_dir($directory)) {
                continue;
            }

            if ($resolved['lemma'] === null) {
                foreach (['word-lemma.db'] as $fileName) {
                    $path = $directory.'/'.$fileName;

                    if (is_file($path)) {
                        $resolved['lemma'] = $path;
                        break;
                    }
                }
            }

            if ($resolved['stem'] === null) {
                $path = $directory.'/word-stem.db';

                if (is_file($path)) {
                    $resolved['stem'] = $path;
                }
            }

            if ($resolved['root'] === null) {
                $path = $directory.'/word-root.db';

                if (is_file($path)) {
                    $resolved['root'] = $path;
                }
            }

            if ($resolved['lemma'] !== null && $resolved['stem'] !== null && $resolved['root'] !== null) {
                break;
            }
        }

        return $resolved;
    }

    private function normalizeQuranRoot(string $text): string
    {
        $normalized = preg_replace('/\s+/u', '', $text) ?? $text;
        $normalized = ArabicFilter::forSearch($normalized);

        return trim($normalized);
    }

    private function normalizeQuranForTypedSearch(string $text): string
    {
        $prepared = strtr($text, [
            'ٱ' => 'ا',
            'ٲ' => 'ا',
            'ٳ' => 'ا',
            'ٵ' => 'ا',
            'ی' => 'ي',
            'ى' => 'ي',
            'ے' => 'ي',
            'ۍ' => 'ي',
            'ې' => 'ي',
            'ۑ' => 'ي',
            'ک' => 'ك',
        ]);

        $prepared = preg_replace('/([\p{Arabic}])\x{0670}/u', '$1ا', $prepared) ?? $prepared;
        $prepared = preg_replace('/\x{0670}/u', 'ا', $prepared) ?? $prepared;

        $normalized = ArabicFilter::forSearch($prepared);

        return strtr($normalized, $this->quranOrthographyMap());
    }

    /**
     * @return array<string, string>
     */
    private function quranOrthographyMap(): array
    {
        return [
            'الرحمان' => 'الرحمن',
            'رحمان' => 'رحمن',
            'الصلوة' => 'الصلاة',
            'صلوة' => 'صلاة',
            'الزكوة' => 'الزكاة',
            'زكوة' => 'زكاة',
            'الحيوة' => 'الحياة',
            'حيوة' => 'حياة',
            'الربوا' => 'الربا',
            'ربوا' => 'ربا',
        ];
    }

    private function nullablePositiveInteger(mixed $value): ?int
    {
        $integer = (int) ($value ?? 0);

        return $integer > 0 ? $integer : null;
    }

    private function nullableTinyInteger(mixed $value): ?int
    {
        $integer = (int) ($value ?? 0);

        if ($integer < 1 || $integer > 114) {
            return null;
        }

        return $integer;
    }

    private function toAsciiDigits(string $value): string
    {
        return strtr($value, [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);
    }
};
