<?php

namespace App\Models;

use App\Enums\ScrapeJobStatus;
use App\Enums\ScrapeJobType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ScrapeJob extends Model
{
    protected $fillable = [
        'business_id', 'type', 'status', 'attempts', 'reviews_found',
        'started_at', 'finished_at', 'error',
    ];

    protected function casts(): array
    {
        return [
            'type' => ScrapeJobType::class,
            'status' => ScrapeJobStatus::class,
            'started_at' => 'datetime',
            'finished_at' => 'datetime',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}