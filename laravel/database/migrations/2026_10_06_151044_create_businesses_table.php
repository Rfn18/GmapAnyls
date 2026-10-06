<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('businesses', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('place_id')->unique();
            $table->text('maps_url')->nullable();
            $table->date('card_installed_at')->nullable();
            $table->timestampTz('last_scraped_at')->nullable();
            $table->string('status', 20)->default('active'); 
            $table->timestampsTz();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('businesses');
    }
};