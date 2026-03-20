<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('arabic_stop_words', function (Blueprint $table): void {
            $table->id();
            $table->string('word', 191);
            $table->string('vocalized', 191)->nullable();
            $table->string('lemma', 191)->nullable();
            $table->string('type', 80)->nullable();
            $table->string('category', 120)->nullable();
            $table->string('stem', 191)->nullable();
            $table->string('tags', 255)->nullable();
            $table->string('source', 80)->default('imported');
            $table->timestamps();

            $table->unique(['word', 'source']);
            $table->index('lemma');
            $table->index('stem');
            $table->index('vocalized');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('arabic_stop_words');
    }
};
