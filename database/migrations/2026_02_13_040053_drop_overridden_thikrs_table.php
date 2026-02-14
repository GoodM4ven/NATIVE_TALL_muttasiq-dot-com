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
        Schema::dropIfExists('overridden_thikrs');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::create('overridden_thikrs', function (Blueprint $table) {
            $table->id();

            $table->foreignId('thikr_id')->nullable()->constrained('thikrs')->nullOnDelete();

            $table->unsignedInteger('order')->nullable();
            $table->json('overrides')->nullable();
            $table->string('time')->nullable();
            $table->text('text')->nullable();
            $table->unsignedSmallInteger('count')->nullable();
            $table->boolean('is_deleted')->default(false);

            $table->timestamps();

            $table->unique(['thikr_id']);
            $table->index(['order']);
        });
    }
};
