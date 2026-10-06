<?php

namespace App\Models;

use App\Enums\Sentiment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReviewKeyword extends Model
{
    public $timestamps = false;

    protected $fillable = ['review_id', 'keyword', 'sentiment'];

    protected function casts(): array
    {
        return ['sentiment' => Sentiment::class];
    }

    public function review(): BelongsTo
    {
        return $this->belongsTo(Review::class);
    }
}