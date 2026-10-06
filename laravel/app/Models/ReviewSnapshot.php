<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewSnapshot extends Model
{
    protected $fillable = ['business_id', 'date', 'rating_avg', 'rating_count'];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'rating_avg' => 'decimal:2',
            'rating_count' => 'integer',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }
}