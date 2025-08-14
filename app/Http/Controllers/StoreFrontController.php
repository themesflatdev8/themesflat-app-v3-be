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
        StoreModel $storeModel,
        SettingsModel $settingModel,
        BundlesModel $bundleModel,
        QuantityOfferModel $offerModel,
        QuantityBreakSettingsModel $settingOfferModel,
        ProductModel $productModel,
        CartSettingsModel $settingCartModel,
        SearchSettingsModel $settingSearchModel
    ) {
        $this->storeModel = $storeModel;
        $this->bundleModel = $bundleModel;
        $this->settingModel = $settingModel;
        $this->productModel = $productModel;
        $this->offerModel = $offerModel;
        $this->settingOfferModel = $settingOfferModel;
        $this->settingCartModel = $settingCartModel;
        $this->settingSearchModel = $settingSearchModel;
        $this->sentry = app('sentry');
    }

    public function getBundleStorefront(Request $request)
    {
        $message = "Not found";
        try {
            $shopifyDomain = $request->shopify_domain;

            $primaryKey = 'getBundleStorefront_' . $shopifyDomain;
            $productId = $request->product_id;
            $themeName = $request->theme_name;
            $pageType = $request->pageType;
            if (is_array($productId) && $pageType != "search") {
                // $productsCheckData = $this->bundleModel->select('product_id')->whereIn('product_id', $productId)->where('status', 1)->inRandomOrder()->first();;
                // if (!empty($productsCheckData)) {
                //     $productId = $productsCheckData->product_id;
                // } else {
                $productId = $productId[0];
                // }
            }

            $subKey = 'getBundleStorefront_' . $productId . '_' . $pageType . '_' . $shopifyDomain;
            if (!empty($themeName)) {
                $subKey = 'getBundleStorefront_' . $productId . '_' . $pageType . '_' . $shopifyDomain . '_' . md5($themeName);
            }

            // if ($shopifyDomain != "b-luxx-boutique.myshopify.com") {
            $resultCache = SystemCache::getItemsFromHash($primaryKey, $subKey)[0] ?? [];
            if (!empty($resultCache)) {
                $result = json_decode($resultCache);
                $dataResponse = (array) $result;
                $dataResponse['is_cache'] = true;
                $dataResponse['product_ids'] = $request->product_id;

                return $dataResponse;
            }
            // }

            $store = $this->storeModel->where('shopify_domain', $shopifyDomain)->first();
            $bundle = null;
            $loyaltyCheck = true;
            $checkCross = false; // check xem bundle này product là cross hay k

            if (!empty($store)) {
                switch ($pageType) {
                    case "product":
                        $settingsDefault = config('fa_switcher.setting_default');
                        $settings = $this->settingModel->find($store->store_id);
                        // get bundle với type là specific trước
                        if (!empty($productId)) {
                            // dump($productId);
                            // dump($store->store_id);
                            $bundle = $this->bundleModel->where('pageType', 'product')
                                ->where('store_id', $store->store_id)
                                ->whereHas('listDefaultIds', function ($query) use ($productId) {
                                    // $productId = (int) $productId;
                                    return $query->where('main_id', $productId);
                                })
                                ->where('type', BundlesModel::BUNDLE_TYPE_SPECIFIC)
                                ->where('status', 1)->orderBy('created_at', 'DESC')->first(); //->toSql();
                            // $sql = $bundle->toSql();
                            // $bindings = $bundle->getBindings();

                            // foreach ($bindings as $binding) {
                            //     $sql = preg_replace('/\?/', "'$binding'", $sql, 1);
                            // }

                            // dd($sql);
                        }

                        // nếu k có lấy qua bundle collection
                        if (empty($bundle) && !empty($request->collection_ids)) {
                            if (!empty($request->collection_ids)) {
                                $collectionIds = is_array($request->collection_ids) ? $request->collection_ids : (array) $request->collection_ids;
                                $bundle = $this->bundleModel->where('pageType', 'product')
                                    ->where('store_id', $store->store_id)
                                    ->where(function ($query) use ($collectionIds) {
                                        return //$query->whereIn('product_id', $collectionIds)
                                            $query->whereHas('listDefaultIds', function ($query) use ($collectionIds) {
                                                return $query->whereIn('main_id', $collectionIds);
                                            });
                                    })
                                    ->with('listDefaultIds')
                                    ->where('status', 1)
                                    ->orderBy('created_at', 'DESC')->first();
                            }
                        }

                        // nếu vẫn k có sẽ lấy bundle general
                        if (empty($bundle)) {
                            $bundle = $this->bundleModel->where('store_id', $store->store_id)->where('pageType', 'product')
                                ->where('type', BundlesModel::BUNDLE_TYPE_GENERAL)->where('status', 1)->orderBy('created_at', 'DESC')->first();
                        }
                        break;

                    case "cart":
                        $settingsDefault = config('fa_switcher.cart_setting_default');
                        $settings = $this->settingCartModel->find($store->store_id);
                        $bundle = $this->bundleModel->where('store_id', $store->store_id)->where('pageType', 'cart')
                            ->where('type', BundlesModel::BUNDLE_TYPE_GENERAL)->orderBy('created_at', 'DESC')->first();
                        break;

                    case "search":
                        $settingsDefault = config('fa_switcher.search_setting_default');
                        $settings = $this->settingSearchModel->find($store->store_id);
                        $bundle = $this->bundleModel->where('store_id', $store->store_id)->where('pageType', 'search')
                            ->where('type', BundlesModel::BUNDLE_TYPE_GENERAL)->orderBy('created_at', 'DESC')->first();
                        break;
                }

                if (!empty($settings->template_version) && $settings->template_version == 1) {
                    $oldSettings = $settings->settings;
                    $newSettings = migrateBundleSettings1vs2($oldSettings, $settingsDefault);
                    $settings->settings  = $newSettings;
                }
                if (empty($settings)) {
                    $settings = (object) ['settings' => $settingsDefault];
                }

                // dd($bundle);

                if (!empty($bundle)) {
                    $mainIds = $bundle->listDefaultIds->pluck('main_id')->toArray();

                    if (!in_array($productId, $mainIds)) {
                        $checkCross = true;
                    }
                    $bundle->check_cross = $checkCross;

                    $product = $this->productModel->where('store_id', $store->store_id)->with(['variants', 'options'])->where('id', $productId)->first();
                    $bundle->product = $product;

                    $maxProduct = $bundle->maxProduct;

                    $daysAgo = null;

                    // dd($bundle);

                    switch ($bundle->mode) {
                        case  "same_collection":
                            $shopifyService = new ShopifyApiService();
                            $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);
                            $collectionId = $mainIds[0];

                            $data = $shopifyService->get('collections/' . $collectionId . '/products.json', ['limit' => $maxProduct]);
                            if (!empty($data->products)) {
                                foreach ($data->products as $pr) {
                                    if ($pr->id != $productId) {
                                        $commendationIds[] = $pr->id;
                                    }
                                }
                            }
                            break;
                        case "manual":
                            if ($bundle->type == BundlesModel::BUNDLE_TYPE_COLLECTION) {
                                $commendationIds = ProductCommenditionsModel::where('store_id', $store->store_id)
                                    ->where('bundle_id', $bundle->id)
                                    ->orderBy('created_at', 'ASC')->pluck('product_id')->toArray();
                            } else {
                                $commendationIds = ProductCommenditionsModel::where('store_id', $store->store_id)
                                    ->where('bundle_id', $bundle->id);
                                if (!empty($productId)) {
                                    $commendationIds = $commendationIds->whereNotIn('product_id', [$productId]);
                                }
                                $commendationIds = $commendationIds->orderBy('created_at', 'ASC')->limit($maxProduct)->pluck('product_id')->toArray();

                                if ($checkCross && !empty($mainIds)) {
                                    if (!empty($commendationIds)) {
                                        $commendationIds = array_merge($commendationIds, $mainIds);
                                    } else {
                                        $commendationIds = $mainIds;
                                    }
                                }
                            }

                            break;
                        case "bought_together":
                            $commendationIds = [];
                            $shopifyService = new ShopifyService();
                            $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);
                            $orders = $shopifyService->getOrder();
                            if (!empty($orders)) {
                                foreach ($orders as $order) {
                                    if (!empty($order->node->lineItems->edges)) {
                                        // dump($order);
                                        $checkExistProductInOrder = false;
                                        $commendationIdsDraft = [];
                                        foreach ($order->node->lineItems->edges as $product) {
                                            $productOrderId = str_replace('gid://shopify/Product/', '', $product->node->product->id);

                                            if (!empty($product->node->product) && $product->node->product->status == "ACTIVE") {
                                                if (!empty($productId)) {
                                                    if ($productOrderId != $productId) {
                                                        $commendationIdsDraft[] = $productOrderId;
                                                    }
                                                } else {
                                                    $commendationIdsDraft[] = $productOrderId;
                                                }
                                            }


                                            if (!empty($productId)) {
                                                if ($productOrderId == $productId) {
                                                    $checkExistProductInOrder  = true;
                                                }
                                            } else {
                                                $checkExistProductInOrder = true;
                                            }
                                        }

                                        if ($checkExistProductInOrder) {
                                            $commendationIds = array_merge($commendationIds, $commendationIdsDraft);
                                        }
                                    }
                                }
                            }

                            if (!empty($commendationIds)) {
                                $counted_values = array_count_values($commendationIds);
                                arsort($counted_values);
                                $commendationIds = array_keys($counted_values);
                            }
                            break;
                        case "most_popular":
                            $commendationIds = [];
                            $shopifyService = new ShopifyService();
                            $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);
                            $orders = $shopifyService->getOrder();
                            if (!empty($orders)) {
                                foreach ($orders as $order) {
                                    if (!empty($order->node->lineItems->edges)) {
                                        foreach ($order->node->lineItems->edges as $product) {
                                            if (
                                                !empty($product->node->product)
                                                && $product->node->product->status == "ACTIVE"
                                            ) {
                                                $productOrderId = str_replace('gid://shopify/Product/', '', $product->node->product->id);
                                                if ($productOrderId != $productId) {
                                                    $commendationIds[] = $productOrderId;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                            if (!empty($commendationIds)) {
                                $counted_values = array_count_values($commendationIds);
                                arsort($counted_values);
                                $commendationIds = array_keys($counted_values);
                            }
                            break;
                        case "new_arrival":
                            $commendationIds = ProductModel::where('store_id', $store->store_id);
                            if (!empty($productId)) {
                                $commendationIds = $commendationIds->whereNotIn('id', [$productId]);
                            }
                            // ->whereNotIn('id', [$productId])
                            $commendationIds = $commendationIds->where('status', 'active');
                            if (!empty($daysAgo)) {
                                $commendationIds = $commendationIds->where('created_at', '>=', $daysAgo);
                            }
                            $commendationIds = $commendationIds->orderBy('created_at', 'DESC')
                                ->limit($maxProduct)
                                ->pluck('id')->toArray();
                            break;
                    }

                    if (!empty($commendationIds)) {
                        $idsOrdered = [];
                        foreach ($commendationIds as $a) {
                            $idsOrdered[] = $a;
                        }
                        $idsOrdered = implode(',', $idsOrdered);
                        $productCommendations = $this->productModel->whereIn('id', $commendationIds);
                        if (!empty($daysAgo)) {
                            $productCommendations = $productCommendations->where('created_at', '>=', $daysAgo)->where('status', 'active');
                        }
                        $productCommendations = $productCommendations->with(['variants', 'options'])
                            ->orderByRaw("FIELD(id, $idsOrdered)")
                            ->get();
                        $bundle->list_commendations = $productCommendations;
                    }


                    $giftProduct = null;
                    if (!empty($bundle->gift_id)) {
                        $giftProduct = ProductModel::where('id', $bundle->gift_id)->with('variants')->first();
                    }
                    $bundle->gift_product = $giftProduct;

                    // if($bundle->discountFreeshipValue == null){
                    //     $bundle->discountFreeshipValue = "";
                    // }

                    $loyaltyCheck = true;
                    // $loyalty = checkLoyalty($store);
                    // if (!empty($loyalty) && !empty($loyalty['loyalty'])) {
                    //     $loyaltyCheck = true;
                    // }

                    $message = "Success";
                }
            }

            $themeSelector = null;
            if (!empty($themeName)) {
                $themeSelectorDB = ThemeSelectorModel::where('name', $themeName)->first();
                if (!empty($themeSelectorDB)) {
                    $themeSelector = $themeSelectorDB->toArray();
                }
            }
        } catch (Exception $e) {
            if (env('APP_ENV') != "production") {
                dd($e);
            }
        }


        $data = [
            'loyalty' => @$loyaltyCheck,
            'app_plan' => !empty($store) ? $store->app_plan : null,
            'settings' => !empty($settings) ? $settings->settings : null,
            'bundle' => @$bundle,
            'currency' => !empty($store) ? $store->currency : null,
            'money_format' => !empty($store) ? $store->money_format : null,
            'theme_selector' => @$themeSelector,
        ];
        SystemCache::addItemToHash($primaryKey, $subKey, ['message' => $message, 'data' => $data], true, 1 * 24 * 60 * 60);

        return response([
            'message' => $message,
            'data' => $data
        ], 200);
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


    public function getSettings(Request $request)
    {
        $shopifyDomain = $request->shopify_domain;
        $store = $this->storeModel->where('shopify_domain', $shopifyDomain)->first();
        $type = $request->type;

        if (!empty($store)) {
            $type = $request->type;
            $settings  = TrustSettingsModel::where('store_id', $store->store_id)->where('type', $type)->first();
            $keydefault = 'fa_trust.default_' . $type;
            $default = config($keydefault);

            return response([
                'data' => !empty($settings->settings) ? $settings->settings : $default,
            ]);
        }

        return response(['message' => 'Not found', 'data' => null], 200);
    }


    public function createDiscountCode(Request $request)
    {
        $shopifyDomain = $request->shopify_domain;
        $store = $this->storeModel->where('shopify_domain', $shopifyDomain)->first();
        $shopifyService = new ShopifyService();
        $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);

        $productIds = $request->product_ids; // id của list recommendation
        $bundleId = $request->bundle_id;
        $bundle = $this->bundleModel->find($bundleId);

        if (
            !empty($bundle)
            && $bundle->useDiscount
            && in_array($bundle->mode, ['ai', 'bought_together', 'most_popular', 'new_arrival'])
        ) {
            $productCommendationsArray = [];
            if (!empty($productIds)) {
                foreach ($productIds as $value) {
                    $productCommendationsArray[] = ['id' => $value];
                }
            }

            switch ($bundle->discountType) {
                case BundlesModel::PROMOTION_TYPE_DISCOUNT:
                    $code = 'MSB_' . $bundle->id . '_Amount_' . genRandomDiscountNumber();
                    $createDiscount = $shopifyService->createDiscountV2(
                        $bundle->id,
                        $code,
                        $bundle->discountType,
                        $bundle->minimun,
                        $bundle->discountValue,
                        $productCommendationsArray,
                        $bundle->type == BundlesModel::BUNDLE_TYPE_COLLECTION ? "collection" : "product",
                        null,
                        $bundle->discountOncePer
                    );

                    $discountId = $createDiscount['codeDiscountNode']['id'];
                    break;

                case BundlesModel::PROMOTION_TYPE_FRESHIP:
                    $code = 'MSB_' . $bundle->id . '_Freeship_' . genRandomDiscountNumber();
                    $createDiscount = $shopifyService->createFreeshipV2(
                        $bundle->id,
                        $code,
                        $bundle->minimun,
                    );
                    $discountId = $createDiscount['codeDiscountNode']['id'];
                    break;
            }

            return response([
                'data' => [
                    'code' => $code
                ],
            ]);
        }

        return response(['message' => 'Error', 'data' => null], 200);
    }
}
