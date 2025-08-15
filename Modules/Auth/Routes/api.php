<?php

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


Route::get('/test-session', function () {
    session(['foo' => 'bar']);
    return response()->json(['foo' => session('foo')]);
});
Route::post('/', 'AuthController@index');
Route::get('/callback', 'AuthController@handleCallback');

Route::get('/debug', function () {
    return response()->json(['success']);
})->middleware('t.auth');
