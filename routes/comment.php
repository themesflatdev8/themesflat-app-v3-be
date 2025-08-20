<?php

use App\Http\Controllers\CommentController;
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
    'prefix' => 'comment',
    'middleware' => ['t.auth']
], function () {
    Route::get('/',  [CommentController::class, 'getComment']);
    Route::post('/create',  [CommentController::class, 'create']);
    Route::post('/delete',  [CommentController::class, 'delete']);
    Route::post('/update',  [CommentController::class, 'update']);
});
