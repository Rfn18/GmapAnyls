<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_keywords', function (Blueprint $table) {
            $table->id();
            $table->foreignId('review_id')->constrained()->cascadeOnDelete();
            $table->string('keyword', 100);
            $table->string('sentiment', 10)->nullable();

            $table->unique(['review_id', 'keyword']);
            $table->index('keyword');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_keywords');
    }
};