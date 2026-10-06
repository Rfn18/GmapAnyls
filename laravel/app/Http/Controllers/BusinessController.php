<?php

namespace App\Http\Controllers;

use App\Enums\ScrapeJobStatus;
use App\Enums\ScrapeJobType;
use App\Models\Business;
use App\Models\MetricDaily;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class BusinessController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'place_id' => ['required', 'string', 'max:255', 'unique:businesses,place_id'],
            'maps_url' => ['nullable', 'url'],
            'card_installed_at' => ['nullable', 'date'],
        ]);

        $business = DB::transaction(function () use ($data) {
            $business = Business::create($data);
            $business->scrapeJobs()->create(['type' => ScrapeJobType::Backfill]);

            return $business;
        });

        return response()->json($business->refresh(), 201);
    }

    public function overview(Business $business): JsonResponse
    {
        $stats = $business->reviews()->active()
            ->selectRaw('COUNT(*) AS total, ROUND(AVG(rating), 2) AS rating_avg, COUNT(owner_reply) AS replied')
            ->first();

        return response()->json([
            'business' => $business,
            'total_reviews' => (int) $stats->total,
            'rating_avg' => $stats->rating_avg !== null ? (float) $stats->rating_avg : null,
            'reply_rate_pct' => $stats->total ? round($stats->replied / $stats->total * 100, 1) : null,
            'latest_snapshot' => $business->snapshots()->latest('date')->first(),
        ]);
    }

    public function beforeAfter(Request $request, Business $business): JsonResponse
    {
        $request->validate(['window' => ['sometimes', Rule::in([30, 60, 90])]]);
        $window = (int) $request->input('window', 30);

        abort_if(! $business->card_installed_at, 422, 'card_installed_at belum diisi.');

        $cut = $business->card_installed_at->copy()->startOfDay();
        $today = today();

        $beforeFrom = $cut->copy()->subDays($window);
        $beforeTo = $cut->copy()->subDay();
        $afterFrom = $cut->copy();
        $afterTo = $cut->copy()->addDays($window - 1);
        if ($afterTo->gt($today)) {
            $afterTo = $today->copy();
        }
        $afterDays = $afterTo->lt($afterFrom) ? 0 : (int) $afterFrom->diffInDays($afterTo) + 1;

        $before = $this->period($business, $beforeFrom, $beforeTo, $window);
        $after = $this->period($business, $afterFrom, $afterTo, $afterDays);

        $uplift = ($before['velocity_per_week'] > 0 && $after['velocity_per_week'] !== null)
            ? round(($after['velocity_per_week'] - $before['velocity_per_week']) / $before['velocity_per_week'] * 100, 1)
            : null;

        return response()->json([
            'card_installed_at' => $cut->toDateString(),
            'window_days' => $window,
            'after_window_incomplete' => $afterDays < $window,
            'before' => $before,
            'after' => $after,
            'velocity_uplift_pct' => $uplift,
            'rating_delta' => ($before['rating_avg'] !== null && $after['rating_avg'] !== null)
                ? round($after['rating_avg'] - $before['rating_avg'], 2)
                : null,
        ]);
    }

    public function timeseries(Request $request, Business $business): JsonResponse
    {
        $f = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
        ]);

        $from = $f['from'] ?? today()->subDays(180)->toDateString();
        $to = $f['to'] ?? today()->toDateString();

        $rows = MetricDaily::where('business_id', $business->id)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get(['date', 'new_reviews', 'review_count_cum', 'rating_avg_cum', 'rating_avg_day']);

        return response()->json(['card_installed_at' => $business->card_installed_at?->toDateString(), 'data' => $rows]);
    }

    public function rescrape(Business $business): JsonResponse
    {
        $running = $business->scrapeJobs()
            ->whereIn('status', [ScrapeJobStatus::Pending->value, ScrapeJobStatus::Running->value])
            ->exists();

        abort_if($running, 409, 'Masih ada scrape job yang berjalan.');

        $job = $business->scrapeJobs()->create(['type' => ScrapeJobType::Incremental]);

        return response()->json($job->refresh(), 202);
    }

    private function period(Business $business, Carbon $from, Carbon $to, int $days): array
    {
        if ($days <= 0) {
            return [
                'from' => $from->toDateString(), 'to' => $to->toDateString(), 'days' => 0,
                'total' => 0, 'velocity_per_week' => null, 'rating_avg' => null,
                'distribution' => array_fill_keys([1, 2, 3, 4, 5], 0),
                'photo_pct' => null, 'avg_comment_length' => null, 'low_precision_count' => 0,
            ];
        }

        $base = $business->reviews()->active()
            ->whereBetween('review_date_est', [$from->toDateString(), $to->toDateString()]);

        $s = (clone $base)->selectRaw(<<<'SQL'
            COUNT(*) AS total,
            ROUND(AVG(rating), 2) AS rating_avg,
            ROUND(100.0 * AVG(has_photos::int), 1) AS photo_pct,
            ROUND(AVG(LENGTH(comment))) AS avg_len,
            COUNT(*) FILTER (WHERE date_precision IN ('month', 'year')) AS low_precision
        SQL)->first();

        $dist = (clone $base)->selectRaw('rating, COUNT(*) AS n')->groupBy('rating')->pluck('n', 'rating');

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'days' => $days,
            'total' => (int) $s->total,
            'velocity_per_week' => round($s->total / $days * 7, 2),
            'rating_avg' => $s->rating_avg !== null ? (float) $s->rating_avg : null,
            'distribution' => collect([1, 2, 3, 4, 5])->mapWithKeys(fn ($r) => [$r => (int) ($dist[$r] ?? 0)])->all(),
            'photo_pct' => $s->photo_pct !== null ? (float) $s->photo_pct : null,
            'avg_comment_length' => $s->avg_len !== null ? (int) $s->avg_len : null,
            'low_precision_count' => (int) $s->low_precision,
        ];
    }
}