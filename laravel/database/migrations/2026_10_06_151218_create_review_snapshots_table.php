<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('review_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->decimal('rating_avg', 3, 2)->nullable();
            $table->unsignedInteger('rating_count')->default(0);
            $table->timestampsTz();

            $table->unique(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('review_snapshots');
    }
};