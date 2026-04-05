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
        Schema::create('thikr_override_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('thikr_id')->constrained('thikrs')->cascadeOnDelete();
            $table->json('override_payload');
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('submitted_from_ip', 45)->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignId('reviewed_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('reviewed_note')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('thikr_override_submissions');
    }
};
