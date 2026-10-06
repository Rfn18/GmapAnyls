<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scrape_jobs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20);                      // backfill|incremental
            $table->string('status', 20)->default('pending'); // pending|running|done|failed
            $table->unsignedSmallInteger('attempts')->default(0);
            $table->unsignedInteger('reviews_found')->default(0);
            $table->timestampTz('started_at')->nullable();
            $table->timestampTz('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->timestampsTz();

            $table->index(['business_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scrape_jobs');
    }
};