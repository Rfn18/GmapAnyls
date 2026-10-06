<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('business_id')->constrained()->cascadeOnDelete();
            $table->string('external_review_id');
            $table->unsignedTinyInteger('rating');
            $table->text('comment')->nullable();
            $table->string('reviewer_name')->nullable();
            $table->string('review_date_raw', 100)->nullable(); 
            $table->date('review_date_est')->nullable();
            $table->string('date_precision', 10)->default('month');
            $table->text('owner_reply')->nullable();
            $table->boolean('has_photos')->default(false);
            $table->timestampTz('first_seen_at');
            $table->timestampTz('last_seen_at');
            $table->timestampTz('deleted_at')->nullable();
            $table->string('sentiment', 10)->nullable();        
            $table->string('language', 10)->nullable();
            $table->timestampsTz();

            $table->unique(['business_id', 'external_review_id']);
            $table->index(['business_id', 'review_date_est']);
            $table->index(['business_id', 'rating']);
            $table->index(['business_id', 'sentiment']);
            $table->index(['business_id', 'deleted_at']);
        });

        if (DB::getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE reviews ADD CONSTRAINT reviews_rating_check CHECK (rating BETWEEN 1 AND 5)');
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};