<?php

// Tempel ke routes/api.php

use App\Http\Controllers\BusinessController;
use App\Http\Controllers\Internal\ScrapeResultController;
use App\Http\Controllers\ReviewController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->prefix('businesses')->group(function () {
    Route::post('/', [BusinessController::class, 'store']);
    Route::get('{business}/overview', [BusinessController::class, 'overview']);
    Route::get('{business}/before-after', [BusinessController::class, 'beforeAfter']);
    Route::get('{business}/timeseries', [BusinessController::class, 'timeseries']);
    Route::post('{business}/rescrape', [BusinessController::class, 'rescrape']);
    Route::get('{business}/reviews', [ReviewController::class, 'index']);
});

Route::post('internal/scrape-results', ScrapeResultController::class)->middleware('scraper');