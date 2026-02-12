<?php

use App\Models\Thikr;
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
            $table->boolean('is_aayah')->default(false)->index();
        });

        DB::table('thikrs')
            ->where(
                'text',
                'like',
                Thikr::AAYAH_OPENING_MARK.'%'.Thikr::AAYAH_CLOSING_MARK,
            )
            ->update(['is_aayah' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('thikrs', function (Blueprint $table) {
            $table->dropColumn('is_aayah');
        });
    }
};
