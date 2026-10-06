<?php

namespace App\Models;

use App\Enums\DatePrecision;
use App\Enums\Sentiment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Review extends Model
{
    protected $fillable = [
        'business_id', 'external_review_id', 'rating', 'comment', 'reviewer_name',
        'review_date_raw', 'review_date_est', 'date_precision', 'owner_reply',
        'has_photos', 'first_seen_at', 'last_seen_at', 'deleted_at', 'sentiment', 'language',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'review_date_est' => 'date:Y-m-d',
            'date_precision' => DatePrecision::class,
            'has_photos' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'deleted_at' => 'datetime',
            'sentiment' => Sentiment::class,
        ];
    }

    /** Review yang masih terlihat di Google (belum ditandai hilang). */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereNull('deleted_at');
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(ReviewKeyword::class);
    }
}