<?php

declare(strict_types=1);

use App\Models\Thikr;
use App\Services\Enums\ThikrType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('thikrs', function (Blueprint $table) {
            $table
                ->string('type')
                ->default(ThikrType::Glorification->value)
                ->after('time')
                ->index();
            $table->text('origin')->nullable()->after('text');
        });

        $this->backfillCanonicalThikrs();

        DB::table('thikrs')
            ->whereNull('type')
            ->update(['type' => ThikrType::Glorification->value]);

        Thikr::clearDefaultCache();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thikrs', function (Blueprint $table) {
            $table->dropColumn('origin');
            $table->dropColumn('type');
        });
    }

    private function backfillCanonicalThikrs(): void
    {
        $canonicalThikrs = $this->canonicalThikrs();

        if ($canonicalThikrs === []) {
            return;
        }

        $hasOrderColumn = Schema::hasColumn('thikrs', 'order');
        $hasIsAayahColumn = Schema::hasColumn('thikrs', 'is_aayah');
        $now = now();

        foreach ($canonicalThikrs as $index => $thikr) {
            $time = $thikr['time'] instanceof \BackedEnum
                ? $thikr['time']->value
                : (string) $thikr['time'];

            $type = ($thikr['type'] ?? null) instanceof \BackedEnum
                ? $thikr['type']->value
                : (string) ($thikr['type'] ?? ThikrType::Glorification->value);

            $text = (bool) ($thikr['is_aayah'] ?? false)
                ? Thikr::AAYAH_OPENING_MARK.$thikr['text'].Thikr::AAYAH_CLOSING_MARK
                : $thikr['text'];

            $payload = [
                'time' => $time,
                'type' => $type,
                'text' => $text,
                'origin' => array_key_exists('origin', $thikr) ? (string) $thikr['origin'] : null,
                'count' => $thikr['count'],
                'updated_at' => $now,
            ];

            if ($hasOrderColumn) {
                $payload['order'] = $index + 1;
            }

            if ($hasIsAayahColumn) {
                $payload['is_aayah'] = (bool) ($thikr['is_aayah'] ?? false);
            }

            $query = DB::table('thikrs')
                ->when(
                    $hasOrderColumn,
                    fn ($builder) => $builder->where('order', $index + 1),
                    fn ($builder) => $builder->where('time', $time)->where('text', $text),
                );

            $existingId = $query->value('id');

            if ($existingId === null) {
                DB::table('thikrs')->insert([
                    ...$payload,
                    'created_at' => $now,
                ]);

                continue;
            }

            DB::table('thikrs')
                ->where('id', $existingId)
                ->update($payload);
        }
    }

    /**
     * @return array<int, array{time: mixed, text: string, count: int, is_aayah?: bool, type?: mixed, origin?: string}>
     */
    private function canonicalThikrs(): array
    {
        $seedMigrationPath = database_path('migrations/2026_02_08_121251_seed_default_thikrs.php');

        if (! file_exists($seedMigrationPath)) {
            return [];
        }

        $seedMigration = require $seedMigrationPath;

        if (! is_object($seedMigration)) {
            return [];
        }

        $resolver = \Closure::bind(
            fn (): array => method_exists($this, $functionName = 'thikrData') ? (array) $this->$functionName() : [],
            $seedMigration,
            $seedMigration,
        );

        if (! $resolver instanceof \Closure) {
            return [];
        }

        return (array) $resolver();
    }
};
