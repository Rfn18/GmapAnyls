<?php

namespace App\Http\Controllers\Internal;

use App\Enums\DatePrecision;
use App\Enums\ScrapeJobStatus;
use App\Enums\ScrapeJobType;
use App\Http\Controllers\Controller;
use App\Models\MetricDaily;
use App\Models\Review;
use App\Models\ScrapeJob;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

/**
 * Menerima hasil dari scraper service (bisa dikirim berkali-kali per job, batch ≤ 500 review).
 * Batch terakhir kirim "finished": true.
 */
class ScrapeResultController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $data = $request->validate([
            'job_id' => ['required', 'integer', 'exists:scrape_jobs,id'],
            'finished' => ['sometimes', 'boolean'],
            'error' => ['nullable', 'string'],
            'snapshot' => ['sometimes', 'array'],
            'snapshot.rating_avg' => ['nullable', 'numeric', 'between:0,5'],
            'snapshot.rating_count' => ['nullable', 'integer', 'min:0'],
            'reviews' => ['sometimes', 'array', 'max:500'],
            'reviews.*.external_review_id' => ['required', 'string', 'max:255'],
            'reviews.*.rating' => ['required', 'integer', 'between:1,5'],
            'reviews.*.comment' => ['nullable', 'string'],
            'reviews.*.reviewer_name' => ['nullable', 'string', 'max:255'],
            'reviews.*.review_date_raw' => ['nullable', 'string', 'max:100'],
            'reviews.*.review_date_est' => ['nullable', 'date'],
            'reviews.*.date_precision' => ['nullable', Rule::enum(DatePrecision::class)],
            'reviews.*.owner_reply' => ['nullable', 'string'],
            'reviews.*.has_photos' => ['nullable', 'boolean'],
            'reviews.*.language' => ['nullable', 'string', 'max:10'],
        ]);

        $job = ScrapeJob::with('business')->findOrFail($data['job_id']);

        abort_if(
            in_array($job->status, [ScrapeJobStatus::Done, ScrapeJobStatus::Failed], true),
            409,
            'Job sudah selesai.'
        );

        $business = $job->business;
        $now = now();

        $rows = collect($data['reviews'] ?? [])
            ->unique('external_review_id')
            ->map(fn (array $r) => [
                'business_id' => $business->id,
                'external_review_id' => $r['external_review_id'],
                'rating' => $r['rating'],
                'comment' => $r['comment'] ?? null,
                'reviewer_name' => $r['reviewer_name'] ?? null,
                'review_date_raw' => $r['review_date_raw'] ?? null,
                'review_date_est' => $r['review_date_est'] ?? null,
                'date_precision' => $r['date_precision'] ?? DatePrecision::Month->value,
                'owner_reply' => $r['owner_reply'] ?? null,
                'has_photos' => $r['has_photos'] ?? false,
                'language' => $r['language'] ?? null,
                'first_seen_at' => $now,
                'last_seen_at' => $now,
                'deleted_at' => null,
            ])
            ->values()
            ->all();

        DB::transaction(function () use ($data, $job, $business, $rows, $now) {
            $job->forceFill([
                'status' => ScrapeJobStatus::Running,
                'started_at' => $job->started_at ?? $now,
                'reviews_found' => $job->reviews_found + count($rows),
            ]);

            if ($rows) {
                // review_date_est & date_precision & first_seen_at & sentiment sengaja tidak di-update:
                // estimasi tanggal dari teks relatif ("3 bulan lalu") bergeser tiap scrape, pakai yang pertama.
                Review::upsert(
                    $rows,
                    ['business_id', 'external_review_id'],
                    ['rating', 'comment', 'reviewer_name', 'review_date_raw', 'owner_reply',
                        'has_photos', 'language', 'last_seen_at', 'deleted_at']
                );
            }

            if (isset($data['snapshot'])) {
                $business->snapshots()->updateOrCreate(
                    ['date' => today()->toDateString()],
                    [
                        'rating_avg' => $data['snapshot']['rating_avg'] ?? null,
                        'rating_count' => $data['snapshot']['rating_count'] ?? 0,
                    ]
                );
            }

            if (! empty($data['error'])) {
                $job->forceFill(['status' => ScrapeJobStatus::Failed, 'error' => $data['error'], 'finished_at' => $now]);
            } elseif ($data['finished'] ?? false) {
                // Backfill = scan penuh: review yang tidak terlihat lagi ditandai hilang.
                if ($job->type === ScrapeJobType::Backfill && $job->reviews_found > 0) {
                    $business->reviews()->active()
                        ->where('last_seen_at', '<', $job->started_at)
                        ->update(['deleted_at' => $now]);
                }

                $job->forceFill(['status' => ScrapeJobStatus::Done, 'finished_at' => $now]);
                $business->forceFill(['last_scraped_at' => $now])->save();
                MetricDaily::rebuild($business->id);
            }

            $job->save();
        });

        return response()->json(['status' => $job->status->value, 'reviews_found' => $job->reviews_found]);
    }
}