<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class MetricDaily extends Model
{
    protected $table = 'metrics_daily';

    // PK komposit (business_id, date): model ini read-only dari sisi Eloquent.
    protected $primaryKey = null;
    public $incrementing = false;

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'date' => 'date:Y-m-d',
            'new_reviews' => 'integer',
            'review_count_cum' => 'integer',
            'rating_avg_cum' => 'decimal:2',
            'rating_avg_day' => 'decimal:2',
        ];
    }

    public function business(): BelongsTo
    {
        return $this->belongsTo(Business::class);
    }

    /** Hitung ulang seluruh metrik harian satu bisnis dari tabel reviews (PostgreSQL). */
    public static function rebuild(int $businessId): void
    {
        DB::transaction(function () use ($businessId) {
            static::where('business_id', $businessId)->delete();

            DB::insert(<<<'SQL'
                INSERT INTO metrics_daily
                    (business_id, date, new_reviews, review_count_cum, rating_avg_cum, rating_avg_day, created_at, updated_at)
                SELECT business_id,
                       review_date_est,
                       COUNT(*),
                       SUM(COUNT(*)) OVER w,
                       ROUND(SUM(SUM(rating)) OVER w / SUM(COUNT(*)) OVER w, 2),
                       ROUND(AVG(rating), 2),
                       NOW(), NOW()
                FROM reviews
                WHERE business_id = ? AND deleted_at IS NULL AND review_date_est IS NOT NULL
                GROUP BY business_id, review_date_est
                WINDOW w AS (ORDER BY review_date_est)
            SQL, [$businessId]);
        });
    }
}