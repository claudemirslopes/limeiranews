<?php

use App\Http\Controllers\Api\V1\Admin;
use App\Http\Controllers\Api\V1\AdController;
use App\Http\Controllers\Api\V1\ArticleController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\BreakingNewsController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\HomepageController;
use App\Http\Controllers\Api\V1\MenuController;
use App\Http\Controllers\Api\V1\NewsletterController;
use App\Http\Controllers\Api\V1\PageController;
use App\Http\Controllers\Api\V1\SearchController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    // ---------------------------------------------------------------
    // Public
    // ---------------------------------------------------------------
    Route::middleware('throttle:api')->group(function () {
        Route::get('articles', [ArticleController::class, 'index']);
        Route::get('articles/{slug}', [ArticleController::class, 'show']);

        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{slug}', [CategoryController::class, 'show']);

        Route::get('homepage', [HomepageController::class, 'index']);
        Route::get('search', [SearchController::class, 'index']);
        Route::get('menus/{location}', [MenuController::class, 'show']);
        Route::get('pages/{slug}', [PageController::class, 'show']);
        Route::get('breaking-news', [BreakingNewsController::class, 'current']);
        Route::get('ads', [AdController::class, 'index']);
    });

    Route::post('newsletter/subscribe', [NewsletterController::class, 'subscribe'])->middleware('throttle:newsletter');
    Route::get('newsletter/confirm/{token}', [NewsletterController::class, 'confirm'])->middleware('throttle:newsletter');

    // ---------------------------------------------------------------
    // Auth
    // ---------------------------------------------------------------
    Route::post('auth/login', [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // -----------------------------------------------------------
        // Admin
        // -----------------------------------------------------------
        Route::prefix('admin')->group(function () {
            Route::get('dashboard', [Admin\DashboardController::class, 'index']);

            Route::apiResource('articles', Admin\ArticleController::class);
            Route::prefix('articles/{article}')->group(function () {
                Route::post('publish', [Admin\ArticleController::class, 'publish']);
                Route::post('schedule', [Admin\ArticleController::class, 'schedule']);
                Route::post('submit-review', [Admin\ArticleController::class, 'submitReview']);
                Route::post('approve', [Admin\ArticleController::class, 'approve']);
                Route::post('request-revision', [Admin\ArticleController::class, 'requestRevision']);
                Route::post('archive', [Admin\ArticleController::class, 'archive']);
                Route::post('duplicate', [Admin\ArticleController::class, 'duplicate']);
            });

            Route::apiResource('categories', Admin\CategoryController::class);

            Route::get('media', [Admin\MediaController::class, 'index']);
            Route::post('media', [Admin\MediaController::class, 'store']);
            Route::delete('media/{media}', [Admin\MediaController::class, 'destroy']);

            Route::get('homepage-sections', [Admin\HomepageSectionController::class, 'index']);
            Route::match(['put', 'patch'], 'homepage-sections/{homepage_section}', [Admin\HomepageSectionController::class, 'update']);
        });
    });
});
