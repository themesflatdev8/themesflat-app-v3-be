<?php

use App\Http\Controllers\GoogleApiController;
use App\Http\Controllers\Admin\StoreController;
use App\Http\Controllers\Admin\BundleController;
use App\Http\Controllers\Admin\BlackListController;
use App\Http\Controllers\Admin\AffiliateController;
use App\Http\Controllers\Admin\ThemeController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});


// Dashboard
Route::group([
    'namespace' => 'dashboard',
    'prefix' => 'dashboard',
    'middleware' => ['auth', 'verified']
], function () {
    Route::get('/', [StoreController::class, 'index'])->name('dashboard');
    Route::get('/mail/{id}', [StoreController::class, 'mail'])->name('mail');
    Route::get('/store-info', [StoreController::class, 'getStoreInfo'])->name('store_info');
});
// Store for admin
Route::group([
    'namespace' => 'store',
    'prefix' => 'store',
    'middleware' => ['auth', 'verified']
], function () {
    Route::get('/edit/{id}', [StoreController::class, 'edit'])->name('store_edit');
    Route::post('/edit/{id}', [StoreController::class, 'update'])->name('store_update');

    //Sync product
    Route::post('/sync-products/{id}', [BundleController::class, 'syncProducts'])->name('sync-products');
    // Sync collection
    Route::post('/sync-collections/{id}', [StoreController::class, 'syncCollections'])->name('sync-collections');
});


// Blacklist for admin
Route::group([
    'namespace' => 'blackList',
    'prefix' => 'blacklist',
    'middleware' => ['auth', 'verified']
], function () {
    Route::get('/', [BlackListController::class, 'index'])->name('blackList');

    Route::get('/add', [BlackListController::class, 'add'])->name('add_blackList');
    Route::post('/add', [BlackListController::class, 'post'])->name('post_blackList');

    Route::get('/edit/{id}', [BlackListController::class, 'edit'])->name('blackList_edit');
    Route::post('/edit/{id}', [BlackListController::class, 'update'])->name('blackList_update');
});

// Affiliate for admin
Route::group([
    'namespace' => 'affiliate',
    'prefix' => 'affiliate',
    'middleware' => ['auth', 'verified']
], function () {
    Route::get('/', [AffiliateController::class, 'index'])->name('affiliate');

    Route::get('/add', [AffiliateController::class, 'add'])->name('add_affiliate');
    Route::post('/add', [AffiliateController::class, 'post'])->name('post_affiliate');

    Route::get('/edit/{id}', [AffiliateController::class, 'edit'])->name('affiliate_edit');
    Route::post('/edit/{id}', [AffiliateController::class, 'update'])->name('affiliate_update');

    Route::delete('/delete/{id}', [AffiliateController::class, 'delete'])->name('affiliate_delete');

    Route::post('/toggle-affiliate-status', [AffiliateController::class, 'toggleAffiliate'])->name('toggle_affiliate');

    Route::get('/affiliate/sample-csv', [AffiliateController::class, 'downloadSampleCsv'])->name('download_sample_csv'); //CSV template file

    Route::post('/', [AffiliateController::class, 'chunking'])->name('chunking'); //split file
    Route::get('/upload-csv-file', [AffiliateController::class, 'uploadCsv'])->name('upload_csv'); // insert data into db from csv file
});

// Theme for admin
Route::group([
    'namespace' => 'theme',
    'prefix' => 'theme',
    'middleware' => ['auth', 'verified'],
], function(){
    Route::get('/', [ThemeController::class, 'index'])->name('theme');

    Route::get('/add', [ThemeController::class, 'add'])->name('add_theme');
    Route::post('/add', [ThemeController::class, 'post'])->name('post_theme');

    Route::get('/edit/{id}', [ThemeController::class, 'edit'])->name('theme_edit');
    Route::post('/edit/{id}', [ThemeController::class, 'update'])->name('theme_update');

    Route::delete('/delete/{id}', [ThemeController::class, 'delete'])->name('theme_delete');
});




require __DIR__ . '/auth.php';


Route::group(['prefix' => 'googleapi'], function () {
    Route::get('oauth', [GoogleApiController::class, 'analyticsOauth2']);
    Route::get('oauth-callback', [GoogleApiController::class, 'analyticsOauth2Callback'])->name('google.auth.callback');
    Route::get('hook', [GoogleApiController::class, 'hook']);
    Route::post('hook', [GoogleApiController::class, 'hook']);
});


// Route::get('/blacklist/add', function (Request $request) {
//     $config = config('fa_blacklist');
//     $competitor = $config['competitor'];
//     $shopify = $config['shopify'];

//     $dataSave = [];
//     foreach ($competitor as $type => $values) {
//         if (!empty($values)) {
//             foreach ($values as $v) {
//                 $dataSave[] = [
//                     'type' => $type,
//                     'value' => $v,
//                     'category' => BlackListModel::CATEGORY_COMPETITOR,
//                     'created_at' => now(), 'updated_at' => now()
//                 ];
//             }
//         }
//     }

//     foreach ($shopify as $type => $values) {
//         if (!empty($values)) {
//             foreach ($values as $v) {
//                 $dataSave[] = [
//                     'type' => $type,
//                     'value' => $v,
//                     'category' => BlackListModel::CATEGORY_SHOPIFY,
//                     'created_at' => now(), 'updated_at' => now()
//                 ];
//             }
//         }
//     }
//     dump($dataSave);

//     $save = BlackListModel::upsert($dataSave, ['type', 'value'], ['category', 'updated_at']);
//     dd($save);
// });
