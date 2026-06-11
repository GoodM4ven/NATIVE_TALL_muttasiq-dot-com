<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('quran_verses')) {
            return;
        }

        $this->addPrefixIndex(
            table: 'quran_verses',
            column: 'text_searchable_typed',
            indexName: 'quran_verses_text_searchable_typed_prefix_index',
        );

        $this->addPrefixIndex(
            table: 'quran_verses',
            column: 'text_searchable',
            indexName: 'quran_verses_text_searchable_prefix_index',
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql' || ! Schema::hasTable('quran_verses')) {
            return;
        }

        $this->dropIndex(
            table: 'quran_verses',
            indexName: 'quran_verses_text_searchable_typed_prefix_index',
        );

        $this->dropIndex(
            table: 'quran_verses',
            indexName: 'quran_verses_text_searchable_prefix_index',
        );
    }

    private function addPrefixIndex(string $table, string $column, string $indexName, int $length = 191): void
    {
        if ($this->hasIndex($table, $indexName)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` ADD INDEX `%s` (`%s`(%d))',
            $table,
            $indexName,
            $column,
            $length,
        ));
    }

    private function dropIndex(string $table, string $indexName): void
    {
        if (! $this->hasIndex($table, $indexName)) {
            return;
        }

        DB::statement(sprintf(
            'ALTER TABLE `%s` DROP INDEX `%s`',
            $table,
            $indexName,
        ));
    }

    private function hasIndex(string $table, string $indexName): bool
    {
        $result = DB::selectOne(
            <<<'SQL'
                SELECT COUNT(1) AS aggregate
                FROM information_schema.statistics
                WHERE table_schema = DATABASE()
                  AND table_name = ?
                  AND index_name = ?
                SQL,
            [$table, $indexName],
        );

        return (int) ($result->aggregate ?? 0) > 0;
    }
};
