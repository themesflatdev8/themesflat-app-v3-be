<?php

use App\Http\Controllers\CommentController;
use App\Http\Controllers\ShopController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'prefix' => 'shop',
    'middleware' => ['t.auth']
], function () {
    Route::get('/request',  [ShopController::class, 'requestApprove']);
});
