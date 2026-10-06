<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('metric_daily', function (Blueprint $table) {
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->unsignedInteger('new_reviews')->default(0);
            $table->unsignedInteger('review_count_cum')->default(0);
            $table->decimal('rating_avg_cum', 3, 2)->nullable();
            $table->decimal('rating_avg_day', 3, 2)->nullable();
            $table->timestampsTz();

            $table->primary(['business_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('metric_daily');
    }
};