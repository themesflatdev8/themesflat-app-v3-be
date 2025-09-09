<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Models\BundlesModel;
use App\Models\CartSettingsModel;
use App\Models\ProductCommenditionsModel;
use App\Models\ProductModel;
use App\Models\QuantityBreakSettingsModel;
use App\Models\QuantityOfferModel;
use App\Models\SearchSettingsModel;
use App\Models\SettingsModel;
use App\Models\StoreModel;
use App\Models\ThemeSelectorModel;
use App\Models\TrustSettingsModel;
use App\Services\Shopify\ShopifyApiService;
use App\Services\Shopify\ShopifyService;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;

class StoreFrontController extends Controller
{
    public $settingModel;
    public $settingOfferModel;
    public $settingCartModel;
    public $settingSearchModel;
    public $storeModel;
    public $bundleModel;
    public $offerModel;
    public $productModel;
    public $sentry;

    public function __construct(
        // StoreModel $storeModel,
        // SettingsModel $settingModel,
        // BundlesModel $bundleModel,
        // QuantityOfferModel $offerModel,
        // QuantityBreakSettingsModel $settingOfferModel,
        ProductModel $productModel,
        // CartSettingsModel $settingCartModel,
        // SearchSettingsModel $settingSearchModel
    ) {
        // $this->storeModel = $storeModel;
        // $this->bundleModel = $bundleModel;
        // $this->settingModel = $settingModel;
        $this->productModel = $productModel;
        // $this->offerModel = $offerModel;
        // $this->settingOfferModel = $settingOfferModel;
        // $this->settingCartModel = $settingCartModel;
        // $this->settingSearchModel = $settingSearchModel;
        $this->sentry = app('sentry');
    }




    public function getOffer(Request $request)
    {
        // return response(['message' => 'Not found',], 404);
        try {
            $shopifyDomain = $request->shopify_domain;

            $primaryKey = 'getOffer_' . $shopifyDomain;
            $productId = $request->product_id;
            $loyaltyCheck = true;

            $subKey = 'getOffer_' . $productId . '_' . $shopifyDomain;

            $resultCache = SystemCache::getItemsFromHash($primaryKey, $subKey)[0] ?? [];
            if (!empty($resultCache)) {
                $result = json_decode($resultCache);
                $dataResponse = (array) $result;
                $dataResponse['is_cache'] = true;

                return $dataResponse;
            }

            $store = $this->storeModel->where('shopify_domain', $shopifyDomain)->first();

            if (!empty($store)) {
                $settings = $this->settingOfferModel->find($store->store_id);

                $offer = $this->offerModel
                    ->where('store_id', $store->store_id)
                    ->where('product_id', $productId)
                    ->where('status', 1)->with('tiers')->orderBy('created_at', 'DESC')->first();

                if (!empty($offer)) {
                    $product = $this->productModel->where('store_id', $store->store_id)->with(['variants', 'options'])->where('id', $productId)->orderBy('created_at', 'desc')->first();
                    $offer->product = $product;


                    $response = [
                        'message' => 'Success',
                        'data' => [
                            'loyalty' => $loyaltyCheck,
                            'app_plan' => $store->app_plan,
                            'settings' => $settings->settings,
                            'offer' => $offer,
                            'currency' => $store->currency,
                            'money_format' => $store->money_format,
                        ]
                    ];

                    SystemCache::addItemToHash($primaryKey, $subKey, $response, true, 1 * 24 * 60 * 60);
                    return response($response);
                }
            }
        } catch (Exception $e) {
            if (env('APP_ENV') != "production") {
                dd($e);
            }
        }

        SystemCache::addItemToHash($primaryKey, $subKey, ['message' => 'Not found', 'data' => null], true, 3 * 24 * 60 * 60);
        return response(['message' => 'Not found', 'data' => null], 200);
    }
}
