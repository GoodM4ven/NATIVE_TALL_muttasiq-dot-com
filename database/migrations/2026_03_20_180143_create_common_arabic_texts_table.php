<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('common_arabic_texts', function (Blueprint $table): void {
            $table->id();
            $table->string('type', 30);
            $table->arabicString('content', length: 40, isUnique: true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('common_arabic_texts');
    }
};
