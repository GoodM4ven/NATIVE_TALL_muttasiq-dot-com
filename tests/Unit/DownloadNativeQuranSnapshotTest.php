<?php

use App\Jobs\DownloadNativeQuranSnapshot;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

uses(TestCase::class);

/**
 * @param  list<string>  $statements
 */
function runSqliteStatements(string $databasePath, array $statements): void
{
    $pdo = new PDO('sqlite:'.$databasePath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    foreach ($statements as $statement) {
        $pdo->exec($statement);
    }
}

function makeTemporarySqlitePath(string $prefix): string
{
    $path = tempnam(sys_get_temp_dir(), $prefix);

    if ($path === false) {
        throw new RuntimeException('Unable to create temporary sqlite file.');
    }

    return $path;
}

test('it imports quran snapshot tables without dropping attached source tables', function () {
    $runtimeDatabasePath = makeTemporarySqlitePath('native-runtime-');
    $snapshotDatabasePath = makeTemporarySqlitePath('native-snapshot-');

    try {
        runSqliteStatements($snapshotDatabasePath, [
            'CREATE TABLE common_arabic_texts (id INTEGER PRIMARY KEY, type TEXT NOT NULL, content TEXT NOT NULL, created_at TEXT NULL, updated_at TEXT NULL)',
            'CREATE TABLE arabic_stop_words (id INTEGER PRIMARY KEY, word TEXT NOT NULL, vocalized TEXT NULL, lemma TEXT NULL, type TEXT NULL, category TEXT NULL, stem TEXT NULL, tags TEXT NULL, source TEXT NOT NULL, created_at TEXT NULL, updated_at TEXT NULL)',
            'CREATE TABLE quran_verses (id INTEGER PRIMARY KEY, surah_number INTEGER NOT NULL, ayah_number INTEGER NOT NULL, ayah_index INTEGER NOT NULL, mushaf_page INTEGER NULL, mushaf_line INTEGER NULL, text_uthmani TEXT NOT NULL, text_searchable TEXT NOT NULL, text_searchable_typed TEXT NOT NULL, created_at TEXT NULL, updated_at TEXT NULL)',
            'CREATE TABLE quran_words (id INTEGER PRIMARY KEY, verse_id INTEGER NOT NULL, surah_number INTEGER NOT NULL, ayah_number INTEGER NOT NULL, ayah_index INTEGER NOT NULL, word_position INTEGER NOT NULL, global_word_index INTEGER NOT NULL, token_uthmani TEXT NOT NULL, token_searchable TEXT NOT NULL, token_searchable_typed TEXT NOT NULL, token_stem TEXT NULL, token_root TEXT NULL, token_lemma TEXT NULL)',
            'CREATE TABLE quran_mushaf_lines (id INTEGER PRIMARY KEY, layout_key TEXT NOT NULL, page_number INTEGER NOT NULL, line_number INTEGER NOT NULL, line_type TEXT NOT NULL, is_centered INTEGER NOT NULL DEFAULT 0, first_word_index INTEGER NULL, last_word_index INTEGER NULL, surah_number INTEGER NULL, created_at TEXT NULL, updated_at TEXT NULL)',
            'CREATE UNIQUE INDEX quran_verses_ayah_index_unique ON quran_verses (ayah_index)',
            "INSERT INTO common_arabic_texts (id, type, content, created_at, updated_at) VALUES (1, 'basmallah', 'source-common', '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
            "INSERT INTO arabic_stop_words (id, word, source) VALUES (1, 'stop-word', 'snapshot')",
            "INSERT INTO quran_verses (id, surah_number, ayah_number, ayah_index, mushaf_page, mushaf_line, text_uthmani, text_searchable, text_searchable_typed, created_at, updated_at) VALUES (1, 1, 1, 1, 1, 1, 'verse-source', 'verse-search', 'verse-typed', '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
            "INSERT INTO quran_words (id, verse_id, surah_number, ayah_number, ayah_index, word_position, global_word_index, token_uthmani, token_searchable, token_searchable_typed) VALUES (1, 1, 1, 1, 1, 1, 1, 'word-source', 'word-search', 'word-typed')",
            "INSERT INTO quran_mushaf_lines (id, layout_key, page_number, line_number, line_type, is_centered, first_word_index, last_word_index, surah_number, created_at, updated_at) VALUES (1, 'qpc-v2', 1, 1, 'ayah', 0, 1, 1, 1, '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
        ]);

        runSqliteStatements($runtimeDatabasePath, [
            'CREATE TABLE common_arabic_texts (id INTEGER PRIMARY KEY, type TEXT NOT NULL, content TEXT NOT NULL, created_at TEXT NULL, updated_at TEXT NULL)',
            "INSERT INTO common_arabic_texts (id, type, content, created_at, updated_at) VALUES (1, 'old', 'runtime-stale', '2026-01-01 00:00:00', '2026-01-01 00:00:00')",
        ]);

        config()->set('database.default', 'sqlite');
        config()->set('database.connections.sqlite.driver', 'sqlite');
        config()->set('database.connections.sqlite.database', $runtimeDatabasePath);

        DB::purge('sqlite');
        DB::reconnect('sqlite');

        $job = app(DownloadNativeQuranSnapshot::class);
        $method = new ReflectionMethod($job, 'replaceQuranTablesFromSnapshot');
        $method->setAccessible(true);
        $method->invoke($job, $snapshotDatabasePath);

        $runtimePdo = new PDO('sqlite:'.$runtimeDatabasePath);
        $runtimePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $runtimeContent = $runtimePdo->query('SELECT content FROM common_arabic_texts LIMIT 1')->fetchColumn();
        $runtimeVerseCount = (int) $runtimePdo->query('SELECT COUNT(*) FROM quran_verses')->fetchColumn();

        $sourcePdo = new PDO('sqlite:'.$snapshotDatabasePath);
        $sourcePdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $sourceContent = $sourcePdo->query('SELECT content FROM common_arabic_texts LIMIT 1')->fetchColumn();

        expect($runtimeContent)->toBe('source-common');
        expect($runtimeVerseCount)->toBe(1);
        expect($sourceContent)->toBe('source-common');
    } finally {
        DB::purge('sqlite');
        File::delete($runtimeDatabasePath);
        File::delete($snapshotDatabasePath);
    }
});
