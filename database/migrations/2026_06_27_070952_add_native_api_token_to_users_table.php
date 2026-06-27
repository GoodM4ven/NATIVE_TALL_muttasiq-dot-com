<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // The Sanctum Bearer the native device stores (mirrored from the server
            // at login) and presents when pushing changes back to the authoritative
            // server. Server-side this column is unused; it's the device's copy.
            $table->string('native_api_token')->nullable()->after('synced_data');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('native_api_token');
        });
    }
};
