<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

it('skips importing quran explanations while running in tests', function () {
    $migration = require database_path('migrations/2026_03_20_180146_create_quran_explanations_tables.php');

    expect(Schema::hasTable('quran_verses'))->toBeTrue()
        ->and(Schema::hasTable('quran_words'))->toBeTrue();

    $verse = DB::table('quran_verses')
        ->select(['surah_number', 'ayah_number'])
        ->orderBy('id')
        ->first();

    expect($verse)->not->toBeNull();

    $temporaryDirectory = storage_path('framework/testing/'.Str::uuid()->toString());
    $sqlitePath = $temporaryDirectory.'/ar-tafsir-al-tabari.db';

    if (! is_dir($temporaryDirectory)) {
        mkdir($temporaryDirectory, 0777, true);
    }

    $sqlite = new SQLite3($sqlitePath);
    $sqlite->exec(
        'CREATE TABLE tafsir (
            ayah_key TEXT,
            group_ayah_key TEXT,
            from_ayah TEXT,
            to_ayah TEXT,
            ayah_keys TEXT,
            text TEXT
        )',
    );

    $statement = $sqlite->prepare(
        'INSERT INTO tafsir (ayah_key, group_ayah_key, from_ayah, to_ayah, ayah_keys, text)
         VALUES (:ayah_key, :group_ayah_key, :from_ayah, :to_ayah, :ayah_keys, :text)',
    );

    expect($statement)->not->toBeFalse();

    $statement->bindValue(':ayah_key', $verse->surah_number.':'.$verse->ayah_number, SQLITE3_TEXT);
    $statement->bindValue(':group_ayah_key', null, SQLITE3_NULL);
    $statement->bindValue(':from_ayah', null, SQLITE3_NULL);
    $statement->bindValue(':to_ayah', null, SQLITE3_NULL);
    $statement->bindValue(':ayah_keys', null, SQLITE3_NULL);
    $statement->bindValue(':text', '<p>Imported explanation that should be skipped during tests.</p>', SQLITE3_TEXT);
    $statement->execute();
    $statement->close();
    $sqlite->close();

    config()->set('arabicable.features.quran', true);
    config()->set('arabicable.data_sources.quran_exegesis_databases_dir', $temporaryDirectory);

    $migration->down();
    $migration->up();
    $migration->up();

    expect(Schema::hasTable('quran_verse_explanations'))->toBeTrue()
        ->and(Schema::hasTable('quran_word_annotations'))->toBeTrue()
        ->and(DB::table('quran_verse_explanations')->count())->toBe(0);

    unlink($sqlitePath);
    rmdir($temporaryDirectory);
});
