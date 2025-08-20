<?php

use App\Http\Controllers\BundleController;
use App\Http\Controllers\CartSettingController;
use App\Http\Controllers\DiscountsController;
use App\Http\Controllers\LanguageController;
use App\Http\Controllers\LoyaltyController;
use App\Http\Controllers\PricingController;
use App\Http\Controllers\QuantityBreakController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StoreFrontController;
use App\Http\Controllers\SwitcherSettingController;
use App\Http\Controllers\TrustSettingController;
use App\Http\Controllers\WebhookController;
use App\Jobs\GenerateBundleJob;
use App\Jobs\Install\AddScriptTag;
use App\Jobs\Install\RegisterAllShopifyWebHook;
use App\Jobs\Install\RegisterBasicShopifyWebHook;
use App\Jobs\Sync\SyncCollectionJob;
use App\Mail\Loyalty;
use App\Models\AffiliateModel;
use App\Models\BundlesModel;
use App\Models\LogModel;
use App\Models\StoreModel;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Redis;

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
    'prefix' => 'discount',
    'middleware' => ['t.auth']
], function () {
    Route::get('/',  [DiscountsController::class, 'getDiscount']);
    Route::post('/sync',  [DiscountsController::class, 'sync']);
});
