<?php

namespace App\Models;

use App\Enums\BusinessStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Business extends Model
{
    protected $fillable = [
        'name', 'place_id', 'maps_url', 'card_installed_at', 'last_scraped_at', 'status',
    ];

    protected function casts(): array
    {
        return [
            'card_installed_at' => 'date:Y-m-d',
            'last_scraped_at' => 'datetime',
            'status' => BusinessStatus::class,
        ];
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    public function scrapeJobs(): HasMany
    {
        return $this->hasMany(ScrapeJob::class);
    }

    public function snapshots(): HasMany
    {
        return $this->hasMany(ReviewSnapshot::class);
    }

    public function metricsDaily(): HasMany
    {
        return $this->hasMany(MetricDaily::class);
    }
}