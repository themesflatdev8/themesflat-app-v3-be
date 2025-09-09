<?php

use App\Http\Controllers\DiscountsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SoldRecordController;
use Illuminate\Support\Facades\Route;


Route::group([
    'prefix' => 'review',
    'middleware' => ['t.auth']
], function () {
    Route::get('/',  [ReviewController::class, 'getManageReviews']);
    Route::get('/{id}',  [ReviewController::class, 'getReviewById']);
    Route::post('/update/{id}',  [ReviewController::class, 'updateReviewById']);
    Route::post('/delete/{id}',  [ReviewController::class, 'deleteById']);
    Route::post('/bulk-action',  [ReviewController::class, 'bulkAction']);
});
