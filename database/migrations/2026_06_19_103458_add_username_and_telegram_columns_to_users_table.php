<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('username')->nullable()->unique()->after('name');
            $table->string('telegram_username')->nullable()->after('username');
            $table->unsignedBigInteger('telegram_id')->nullable()->index()->after('telegram_username');
            $table->string('email')->nullable()->change();
        });

        $this->backfillUsernames();
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['username', 'telegram_username', 'telegram_id']);
            $table->string('email')->nullable(false)->change();
        });
    }

    /**
     * The app is already live: give existing rows a username so the app can be
     * username-driven. The configured admin keeps a stable username so the
     * username-keyed seeder stays idempotent.
     */
    private function backfillUsernames(): void
    {
        $adminEmail = config('app.custom.user.email');
        $adminUsername = config('app.custom.user.username');

        if (is_string($adminEmail) && $adminEmail !== '' && is_string($adminUsername) && $adminUsername !== '') {
            DB::table('users')
                ->where('email', $adminEmail)
                ->whereNull('username')
                ->update(['username' => $adminUsername]);
        }

        DB::table('users')
            ->whereNull('username')
            ->orderBy('id')
            ->each(function (object $user): void {
                DB::table('users')
                    ->where('id', $user->id)
                    ->update(['username' => 'user_'.Str::lower(Str::random(16))]);
            });
    }
};
