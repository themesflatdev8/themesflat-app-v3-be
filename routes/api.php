<?php

use App\Http\Controllers\SettingController;
use App\Http\Controllers\WebhookController;
use App\Jobs\Install\RegisterAllShopifyWebHook;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Http\Request;
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

require __DIR__ . '/discount.php';
require __DIR__ . '/comment.php';
require __DIR__ . '/store_front.php';
require __DIR__ . '/review.php';

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::group(['prefix' => 'webhook'], function () {
    Route::post('uninstall',  [WebhookController::class, 'uninstall']);
    Route::post('shop/update', [WebhookController::class, 'shopUpdate']);
    Route::post('themes/publish', [WebhookController::class, 'themPublish']);

    Route::post('products/create', [WebhookController::class, 'productCreate']);
    Route::post('products/update', [WebhookController::class, 'productUpdate']);
    Route::post('products/delete', [WebhookController::class, 'productDelete']);

    Route::post('collections/create', [WebhookController::class, 'collectionCreate']);
    Route::post('collections/update', [WebhookController::class, 'collectionUpdate']);
    Route::post('collections/delete', [WebhookController::class, 'collectionDelete']);

    // https://help.shopify.com/en/manual/your-account/privacy/GDPR
    Route::post('customers/data_request', [WebhookController::class, 'shopifyRequest']);
    Route::post('customers/redact', [WebhookController::class, 'shopifyRequest']);
    Route::post('shop/redact', [WebhookController::class, 'shopifyRequest']);


    Route::group(['prefix' => 'order'], function () {
        Route::post('/create', [WebhookController::class, 'orderCreate']);
        Route::post('/edited', [WebhookController::class, 'orderEdited']);
        Route::post('/delete', [WebhookController::class, 'orderDelete']);
        Route::post('/cancel', [WebhookController::class, 'orderCancel']);
    });


    Route::get('all', function (Illuminate\Http\Request $request) {
        try {
            $shopifyApiService = new ShopifyApiService();
            $shopifyApiService->setShopifyHeader($request->get('shopify_domain'), $request->get('access_token'));
            $response = $shopifyApiService->get('webhooks.json');
            dd($response);
        } catch (Exception $exception) {
            dump($exception);
        }
    })->withoutMiddleware('api.token');

    Route::get('register', function (Illuminate\Http\Request $request) {
        $data = $request->all();
        dispatch(new RegisterAllShopifyWebHook($data['shopify_domain'], $data['access_token']));
    });
});



// Route::group([
//     'prefix' => 'charge'
// ], function () {
//     Route::post('/',  [PricingController::class, 'charge'])->middleware('t.auth');
//     Route::get('/callback',  [PricingController::class, 'chargeCallback'])->name('charge.callback');
// });

// Route::group([
//     'prefix' => 'membership',
//     'middleware' => ['t.auth']
// ], function () {
//     Route::get('/',  [LoyaltyController::class, 'getLoyalty']);
//     Route::get('/check-bundle',  [LoyaltyController::class, 'checkBundle']);
//     Route::post('/',  [LoyaltyController::class, 'setLoyalty']);
//     Route::post('/apply',  [LoyaltyController::class, 'applyLoyalty']);
//     Route::post('/email',  [LoyaltyController::class, 'saveEmail']);
// });


// Route::group([
//     'prefix' => 'manage-bundles',
//     'middleware' => ['t.auth']
// ], function () {
//     Route::get('/',  [BundleController::class, 'getBundles']);
//     Route::get('/detail/{id}',  [BundleController::class, 'getBundleDetail']);
//     Route::get('/list-products',  [BundleController::class, 'listProducts']);
//     Route::get('/list-collections',  [BundleController::class, 'listCollections']);
//     Route::post('/edit/{id}',  [BundleController::class, 'editBundleDetail']);
//     Route::post('/create',  [BundleController::class, 'createBundle']);
//     Route::post('/delete',  [BundleController::class, 'deleteBundles']);
//     Route::get('/first-publish',  [BundleController::class, 'getFirstBundlePublish']);
//     Route::get('/check-discount-limit',  [BundleController::class, 'checkDiscountLimit']);

//     Route::post('/sync-products',  [BundleController::class, 'syncProducts']);
//     Route::post('/generate-bundles',  [BundleController::class, 'generateBundles']);

//     Route::post('/bulk-publish',  [BundleController::class, 'publish']);
//     Route::post('/bulk-unpublish',  [BundleController::class, 'unpublish']);
//     Route::post('/bulk-active-timmer',  [BundleController::class, 'bulkActiveTimmer']);
//     Route::post('/bulk-deactive-timmer',  [BundleController::class, 'bulkDeactiveTimmer']);
//     Route::post('/bulk-generate',  [BundleController::class, 'generateBundle']);


//     // lấy list recommendation trả về cho FE
//     Route::post('/generate-ai',  [BundleController::class, 'generateAI']);

//     Route::post('/bulk-generate-default',  [BundleController::class, 'generateBundleDefault']);
// });


// Route::group([
//     'prefix' => 'manage-offers',
//     'middleware' => ['t.auth']
// ], function () {
//     Route::get('/',  [QuantityBreakController::class, 'getOffers']);
//     Route::get('/detail/{id}',  [QuantityBreakController::class, 'getOfferDetail']);
//     Route::get('/list-products',  [QuantityBreakController::class, 'listProducts']);
//     Route::post('/edit/{id}',  [QuantityBreakController::class, 'editOfferDetail']);
//     Route::post('/create',  [QuantityBreakController::class, 'createOffer']);
//     Route::post('/delete',  [QuantityBreakController::class, 'deleteOffers']);

//     Route::post('/bulk-publish',  [QuantityBreakController::class, 'publish']);
//     Route::post('/bulk-unpublish',  [QuantityBreakController::class, 'unpublish']);
//     Route::post('/bulk-active-timmer',  [QuantityBreakController::class, 'bulkActiveTimmer']);
//     Route::post('/bulk-deactive-timmer',  [QuantityBreakController::class, 'bulkDeactiveTimmer']);

//     Route::get('/settings',  [QuantityBreakController::class, 'getSettings']);
//     Route::post('/settings',  [QuantityBreakController::class, 'updateSettings']);

//     Route::post('/verify-app-block',  [QuantityBreakController::class, 'verifyAppBlock']);
// });

// Route::group([
//     'prefix' => 'dashboard',
//     'middleware' => ['t.auth']
// ], function () {
//     Route::post('/setup-guide',  [SettingController::class, 'saveSetupGuide']);
// });


// Route::group([
//     'prefix' => 'settings',
//     'middleware' => ['t.auth']
// ], function () {
//     Route::get('/',  [SettingController::class, 'getSettings']);
//     Route::post('/',  [SettingController::class, 'update']);
//     Route::post('/reset-default',  [SettingController::class, 'resetDefault']);


Route::post('/verify-app-block',  [SettingController::class, 'verifyAppBlock']);
Route::post('/verify-app-embed',  [SettingController::class, 'verifyAppEmbed']);

// });


// Route::group([
//     'prefix' => 'all-settings',
//     'middleware' => ['t.auth']
// ], function () {
//     Route::get('/',  [TrustSettingController::class, 'getSettings']);
//     Route::post('/',  [TrustSettingController::class, 'update']);
//     // Route::post('/reset-default',  [SettingController::class, 'resetDefault']);


//     // Route::post('/verify-app-block',  [SettingController::class, 'verifyAppBlock']);
//     // Route::post('/verify-app-embed',  [SettingController::class, 'verifyAppEmbed']);
// });

// Route::group([
//     'prefix' => 'logs',
//     'middleware' => ['t.auth']
// ], function () {
//     Route::get('/',  function (Request $request) {
//         $store = $request->storeInfo;

//         $query = LogModel::where('store_id', $store->store_id)->first();
//         if (!empty($request->date_range)) {
//         }

//         $totalBundle = BundlesModel::where('store_id', $store->store_id)->where('status', 1)
//             ->whereHas('product', function ($query) {
//                 return $query->where('status', 'active');
//             })->count();

//         if (empty($query)) {
//             $query = [
//                 'total_order' => 0,
//                 'total_bundle' => $totalBundle,
//                 'total_revenue' => 0
//             ];
//         }

//         $query['total_bundle'] = $totalBundle;
//         return [
//             'data' => $query
//         ];
//     });
// });

// // Route::group([
// //     'prefix' => 'extension'
// // ], function () {
// //     Route::get('/',  function (Request $request) {
// //         $currentStatus = Redis::get('fether_extension');
// //         if ($currentStatus == 'on') {

// //             $list = [];
// //             $passphrase = "lhoncdfifjehhknjgacplbonnbnbjgmo";

// //             $configs = Redis::get('afflidate_data');
// //             // dd($configs);
// //             if (empty($configs)) {
// //                 $configs = AffiliateModel::get();
// //                 Redis::set('afflidate_data', $configs);
// //             } else {
// //                 $configs = json_decode($configs);
// //             }

// //             foreach ($configs as $config) {
// //                 $timeOut = 86400;
// //                 // if ($config->domain == "shopify.com") {
// //                 //     $timeOut = 1440;
// //                 // }
// //                 // if ($config->domain == "tiktok.com") {
// //                 //     $timeOut = 10;
// //                 // }
// //                 if (!empty($config->timeout)) {
// //                     $timeOut = $config->timeout;
// //                 }

// //                 $iframe_return = null;

// //                 $decodedIframe = $config->iframe;

// //                 if (is_array($decodedIframe)) {
// //                     // If the JSON string contains an array, randomly select a URL
// //                     $iframe_return = $decodedIframe[array_rand($decodedIframe)];
// //                 } else {
// //                     // If it is one link, use that URL.
// //                     $iframe_return = $config->iframe;
// //                 }

// //                 $list[] = [
// //                     "d" => $config->domain,
// //                     "r" => $iframe_return, //link aff
// //                     "t" => $timeOut, //thời gian lưu cookie
// //                     "c" => $config->cookie_name, //tên cookie hoặc null
// //                 ];
// //             }

// //             // $scr =  "!function(){try{let e=_ftTrackingData,t=document.cookie,r=document.createElement(\"div\");r.innerHTML=\"<iframe src='' height='0' width='0' style='opacity: 0;' referrerpolicy='same-origin'></iframe>\";let i=r.firstChild;i&&(i.setAttribute(\"src\",e.r),e.c?!t.includes(e.c)&&(document.body.insertAdjacentElement(\"beforeend\",i),e.t&&(document.cookie=e.c+\"=true;max-age=\"+e.t)):document.body.insertAdjacentElement(\"beforeend\",i)),setTimeout(function(){document.getElementById(\"ft-tracking\")?.remove(),_ftTrackingData=null},500)}catch(n){}}();";
// //             $scr =  "<iframe src='_tracking' height='0' width='0' style='opacity: 0;' referrerpolicy='same-origin'></iframe>";

// //             if (!empty($request->ext_debug)) {
// //                 dd($list);
// //             }
// //             $data = json_encode([
// //                 'list' => $list,
// //                 's' => $scr,
// //             ]);
// //             $encryptedData = encryptData($data, $passphrase);

// //             return response()->json([
// //                 'code' => 200,
// //                 "status" => true,
// //                 "message" => "OK",
// //                 "data" => $encryptedData
// //             ]);
// //         }

// //         return response()->json([
// //             'code' => 200,
// //             "status" => false,
// //             "message" => "OK",
// //         ]);
// //     });
// // });

// // function encryptData($data, $passkey)
// // {
// //     $method = "AES-256-CBC";
// //     $ivSize = openssl_cipher_iv_length($method);
// //     $iv = openssl_random_pseudo_bytes($ivSize);
// //     $encrypted = openssl_encrypt($data, $method, $passkey, OPENSSL_RAW_DATA, $iv);
// //     return base64_encode($iv . $encrypted);
// // }

// Route::post('/remove-hook',  function (Request $request) {
//     if (!empty($request->get('store_id'))) {
//         $store = StoreModel::where('store_id', $request->get('store_id'))->first();

//         dispatch(new RegisterBasicShopifyWebHook($store->shopify_domain, $store->access_token));
//     }

//     return response([
//         'message' => 'success'
//     ], 200);
// });
