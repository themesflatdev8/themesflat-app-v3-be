<?php

use App\Http\Controllers\DiscountsController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ReviewController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SoldRecordController;
use Illuminate\Support\Facades\Route;



Route::middleware('storefront')->group(function () {
    Route::get('/also-boughts', [OrderController::class, 'alsoBoughts']); //varriant_id
    Route::get('/freeship', [DiscountsController::class, 'getFreeShip']);  //country
    Route::get('/count-shipping', [DiscountsController::class, 'countShipping']); //variant_id
    Route::get('/check-coupon', [DiscountsController::class, 'checkDiscount']); //code
    Route::get('/recent', [ProductController::class, 'getRecentViews']); //user_id
    Route::get('/product-views', [ProductController::class, 'getProductViews']); //user_id
    Route::get('/product-top-view', [ProductController::class, 'productTopView']);
    Route::get('/api-products', [ProductController::class, 'getApiProduct']);
    Route::get('/products-recent', [ProductController::class, 'getProductRecent']);
    Route::get('/products-related', [ProductController::class, 'getProductRelated']);
    Route::get('/get-off', [ProductController::class, 'getOff']);

    Route::post('/search', [SearchController::class, 'search']);
    Route::get('/top-keywords', [SearchController::class, 'topKeywords']);

    Route::post('/add-review', [ReviewController::class, 'addReview']);
    Route::post('/edit-review', [ReviewController::class, 'editReview']);
    Route::get('/get-reviews', [ReviewController::class, 'getReviews']);
    Route::get('/get-review-summary', [ReviewController::class, 'getReviewSummary']);
    Route::post('/submit-review', [ReviewController::class, 'submitReview']);
    Route::get('/get-reviews-full', [ReviewController::class, 'getAllReviews']);
    Route::get('/get-comments', [ReviewController::class, 'getComments']);
    Route::post('/update-comments', [ReviewController::class, 'updateComment']);
    Route::get('/count-comments', [ReviewController::class, 'countComment']);

    Route::post('/add-sol32h', [SoldRecordController::class, 'addSold32h']);
    Route::get('/sold-32h', [SoldRecordController::class, 'getSoldRecord']);
    Route::get('/bestseller', [SoldRecordController::class, 'getBestSeller']);
});
