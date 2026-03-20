<?php

declare(strict_types=1);

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

        Schema::create('quran_verse_explanations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('verse_id')->constrained('quran_verses')->cascadeOnDelete();
            $table->unsignedTinyInteger('surah_number');
            $table->unsignedSmallInteger('ayah_number');
            $table->unsignedSmallInteger('ayah_index');
            $table->string('source_key', 80);
            $table->string('source_label', 140);
            $table->string('content_kind', 20)->default('tafsir');
            $table->string('group_ayah_key', 20)->nullable();
            $table->string('from_ayah_key', 20)->nullable();
            $table->string('to_ayah_key', 20)->nullable();
            $table->text('ayah_keys')->nullable();
            $table->longText('content_html')->nullable();
            $table->longText('content_text');
            $table->timestamps();

            $table->unique(['verse_id', 'source_key']);
            $table->index(['surah_number', 'ayah_number']);
            $table->index(['source_key', 'content_kind']);
        });

        Schema::create('quran_word_annotations', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('word_id')->constrained('quran_words')->cascadeOnDelete();
            $table->string('annotation_type', 40);
            $table->string('source_key', 80)->default('manual');
            $table->string('locale', 10)->nullable();
            $table->text('content_text');
            $table->longText('content_html')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['word_id', 'annotation_type']);
            $table->index(['source_key', 'locale']);
        });

        $this->importQuranExplanations();
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_word_annotations');
        Schema::dropIfExists('quran_verse_explanations');
    }

    private function importQuranExplanations(): void
    {
        if (! Schema::hasTable('quran_verses')) {
            return;
        }

        $sourcesDirectory = $this->resolveExplanationsDirectory();

        if ($sourcesDirectory === null) {
            return;
        }

        $sourceMap = [
            'ar-tafsir-al-tabari.db' => ['key' => 'tafsir_tabari', 'label' => 'تفسير الطبري', 'kind' => 'tafsir', 'schema' => 'tafsir'],
            'ar-tafsir-al-baghawi.db' => ['key' => 'tafsir_baghawi', 'label' => 'تفسير البغوي', 'kind' => 'tafsir', 'schema' => 'tafsir'],
            'tafsir-ibn-abi-hatim.db' => ['key' => 'tafsir_ibn_abi_hatim', 'label' => 'تفسير ابن أبي حاتم', 'kind' => 'tafsir', 'schema' => 'tafsir'],
            'tafsir-ibn-abi-zamanin.db' => ['key' => 'tafsir_ibn_abi_zamanin', 'label' => 'تفسير ابن أبي زمنين', 'kind' => 'tafsir', 'schema' => 'tafsir'],
            'tafsir-al-tha-alibi.db' => ['key' => 'tafsir_thaalibi', 'label' => 'تفسير الثعالبي', 'kind' => 'tafsir', 'schema' => 'tafsir'],
            'mawsoo-at-al-tafsir-al-ma-thoor.db' => ['key' => 'tafsir_maathoor', 'label' => 'موسوعة التفسير المأثور', 'kind' => 'tafsir', 'schema' => 'tafsir'],
            'al-i-rab-al-muyassar.db' => ['key' => 'irab_muyassar', 'label' => 'الإعراب الميسر', 'kind' => 'irab', 'schema' => 'tafsir'],
            'tafsir-muqatil-bin-sulayman.db' => ['key' => 'tafsir_muqatil', 'label' => 'تفسير مقاتل بن سليمان', 'kind' => 'tafsir', 'schema' => 'tb_quran'],
            'muqatil_194_expanded.sqlite' => ['key' => 'tafsir_muqatil', 'label' => 'تفسير مقاتل بن سليمان', 'kind' => 'tafsir', 'schema' => 'tb_quran'],
        ];

        $verseMap = [];

        foreach (DB::table('quran_verses')->select(['id', 'surah_number', 'ayah_number', 'ayah_index'])->cursor() as $verse) {
            $verseMap[$verse->surah_number.':'.$verse->ayah_number] = [
                'id' => (int) $verse->id,
                'surah_number' => (int) $verse->surah_number,
                'ayah_number' => (int) $verse->ayah_number,
                'ayah_index' => (int) $verse->ayah_index,
            ];
        }

        if ($verseMap === []) {
            return;
        }

        $now = now();
        $rows = [];

        foreach ($sourceMap as $fileName => $meta) {
            $path = $sourcesDirectory.'/'.$fileName;

            if (! is_file($path)) {
                continue;
            }

            $database = new SQLite3($path, SQLITE3_OPEN_READONLY);
            $schema = (string) ($meta['schema'] ?? 'tafsir');
            $result = null;

            if ($schema === 'tb_quran' && $this->sqliteTableExists($database, 'TB_Quran')) {
                $result = $database->query('SELECT SuraID AS surah_number, AyahID AS ayah_number, AyahText AS text FROM TB_Quran');
            } elseif ($this->sqliteTableExists($database, 'tafsir')) {
                $result = $database->query('SELECT ayah_key, group_ayah_key, from_ayah, to_ayah, ayah_keys, text FROM tafsir');
            }

            if (! $result instanceof SQLite3Result) {
                $database->close();

                continue;
            }

            while (true) {
                $item = $result->fetchArray(SQLITE3_ASSOC);

                if (! is_array($item)) {
                    break;
                }

                $ayahKey = $schema === 'tb_quran'
                    ? ((int) ($item['surah_number'] ?? 0)).':'.((int) ($item['ayah_number'] ?? 0))
                    : trim((string) ($item['ayah_key'] ?? ''));

                if ($ayahKey === '' || ! isset($verseMap[$ayahKey])) {
                    continue;
                }

                $verse = $verseMap[$ayahKey];
                $rawText = (string) ($item['text'] ?? '');
                $html = $schema === 'tb_quran' ? null : $this->nullableValue($rawText);
                $text = $this->toPlainText($rawText);

                if (! $this->hasMeaningfulContent($text)) {
                    continue;
                }

                $rows[] = [
                    'verse_id' => $verse['id'],
                    'surah_number' => $verse['surah_number'],
                    'ayah_number' => $verse['ayah_number'],
                    'ayah_index' => $verse['ayah_index'],
                    'source_key' => $meta['key'],
                    'source_label' => $meta['label'],
                    'content_kind' => $meta['kind'],
                    'group_ayah_key' => $schema === 'tb_quran' ? null : $this->nullableValue($item['group_ayah_key'] ?? null),
                    'from_ayah_key' => $schema === 'tb_quran' ? null : $this->nullableValue($item['from_ayah'] ?? null),
                    'to_ayah_key' => $schema === 'tb_quran' ? null : $this->nullableValue($item['to_ayah'] ?? null),
                    'ayah_keys' => $schema === 'tb_quran' ? null : $this->nullableValue($item['ayah_keys'] ?? null),
                    'content_html' => $html,
                    'content_text' => $text,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (count($rows) >= 800) {
                    DB::table('quran_verse_explanations')->upsert(
                        $rows,
                        ['verse_id', 'source_key'],
                        [
                            'source_label',
                            'content_kind',
                            'group_ayah_key',
                            'from_ayah_key',
                            'to_ayah_key',
                            'ayah_keys',
                            'content_html',
                            'content_text',
                            'updated_at',
                        ],
                    );

                    $rows = [];
                }
            }

            $database->close();
        }

        if ($rows !== []) {
            DB::table('quran_verse_explanations')->upsert(
                $rows,
                ['verse_id', 'source_key'],
                [
                    'source_label',
                    'content_kind',
                    'group_ayah_key',
                    'from_ayah_key',
                    'to_ayah_key',
                    'ayah_keys',
                    'content_html',
                    'content_text',
                    'updated_at',
                ],
            );
        }
    }

    private function resolveExplanationsDirectory(): ?string
    {
        $configured = (string) config('arabicable.data_sources.quran_exegesis_databases_dir', '');

        $candidates = [
            $configured,
            base_path('resources/raw-data/quran/exegesis'),
            base_path('vendor/goodm4ven/arabicable/resources/raw-data/quran/exegesis'),
            dirname(__DIR__, 2).'/resources/raw-data/quran/exegesis',
        ];

        foreach ($candidates as $candidate) {
            if ($candidate === '' || ! is_dir($candidate)) {
                continue;
            }

            $resolved = realpath($candidate);

            if (is_string($resolved) && $resolved !== '') {
                return $resolved;
            }

            return $candidate;
        }

        return null;
    }

    private function sqliteTableExists(SQLite3 $database, string $tableName): bool
    {
        $statement = $database->prepare('SELECT 1 FROM sqlite_master WHERE type = :type AND name = :name LIMIT 1');

        if (! $statement instanceof SQLite3Stmt) {
            return false;
        }

        $statement->bindValue(':type', 'table', SQLITE3_TEXT);
        $statement->bindValue(':name', $tableName, SQLITE3_TEXT);
        $result = $statement->execute();

        if (! $result instanceof SQLite3Result) {
            $statement->close();

            return false;
        }

        $row = $result->fetchArray(SQLITE3_NUM);
        $result->finalize();
        $statement->close();

        return is_array($row);
    }

    private function toPlainText(string $html): string
    {
        $decoded = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $plain = strip_tags($decoded);
        $normalized = preg_replace('/\s+/u', ' ', trim((string) $plain));

        return trim((string) $normalized);
    }

    private function hasMeaningfulContent(string $text): bool
    {
        $normalized = trim($text);

        if ($normalized === '') {
            return false;
        }

        if (in_array($normalized, ['-', '—', '–', 'ـ'], true)) {
            return false;
        }

        return true;
    }

    private function nullableValue(mixed $value): ?string
    {
        $normalized = trim((string) ($value ?? ''));

        return $normalized !== '' ? $normalized : null;
    }
};
