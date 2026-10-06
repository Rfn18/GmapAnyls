<?php

namespace App\Http\Controllers;

use App\Enums\Sentiment;
use App\Models\Business;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ReviewController extends Controller
{
    public function index(Request $request, Business $business): JsonResponse
    {
        $f = $request->validate([
            'rating' => ['nullable', 'integer', 'between:1,5'],
            'sentiment' => ['nullable', Rule::enum(Sentiment::class)],
            'q' => ['nullable', 'string', 'max:100'],
            'per_page' => ['nullable', 'integer', 'between:1,100'],
        ]);

        $reviews = $business->reviews()->active()
            ->when($f['rating'] ?? null, fn ($q, $v) => $q->where('rating', $v))
            ->when($f['sentiment'] ?? null, fn ($q, $v) => $q->where('sentiment', $v))
            ->when($f['q'] ?? null, fn ($q, $v) => $q->where('comment', 'ilike', '%'.addcslashes($v, '%_\\').'%'))
            ->orderByRaw('review_date_est DESC NULLS LAST')
            ->orderByDesc('id')
            ->paginate($f['per_page'] ?? 20);

        return response()->json($reviews);
    }
}