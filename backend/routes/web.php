<?php

use App\Http\Controllers\SeoController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return response()->json([
        'name' => 'Limeira News API',
        'docs' => url('/api/v1'),
        'health' => url('/up'),
    ]);
});

Route::get('/robots.txt', [SeoController::class, 'robots']);
Route::get('/sitemap.xml', [SeoController::class, 'sitemap']);
Route::get('/news-sitemap.xml', [SeoController::class, 'newsSitemap']);
Route::get('/feed.xml', [SeoController::class, 'rss']);
