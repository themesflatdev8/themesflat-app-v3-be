<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\ActiveDiscountCodeJob;
use App\Jobs\AddMetafieldCheckOffferJob;
use App\Jobs\DeactiveDiscountCodeJob;
use App\Jobs\StoreFrontCacheVersionJob;
use App\Models\QuantityOfferModel;
use App\Models\QuantityOfferTierModel;
use App\Models\ProductModel;
use App\Models\QuantityBreakSettingsModel;
use App\Models\StoreModel;
use App\Services\Shopify\ShopifyApiService;
use App\Services\Shopify\ShopifyService;
use Exception;
use Illuminate\Http\Request;

class QuantityBreakController extends Controller
{
    public $storeModel;
    public $productModel;
    public $quantityOfferModel;
    public $quantityOfferTierModel;
    public $sentry;
    public $settingsModel;

    public function __construct(
        StoreModel $storeModel,
        ProductModel $productModel,
        QuantityOfferModel $quantityOfferModel,
        QuantityOfferTierModel $quantityOfferTierModel,
        QuantityBreakSettingsModel $settingsModel
    ) {
        $this->storeModel = $storeModel;
        $this->sentry = app('sentry');
        $this->productModel = $productModel;
        $this->quantityOfferModel = $quantityOfferModel;
        $this->quantityOfferTierModel = $quantityOfferTierModel;
        $this->settingsModel = $settingsModel;
    }

    public function getOffers(Request $request)
    {
        $store = $request->storeInfo;

        $query = $this->quantityOfferModel->where('store_id', $store->store_id);
        if (isset($request->status)) {
            $query = $query->where('status', $request->status);
        }

        if (!empty($request->keyword)) {
            $keyword  = $request->keyword;

            $query = $query->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('id', 'LIKE', '%' . $keyword . '%')
                    ->orWhereHas('product', function ($query) use ($keyword) {
                        return $query->where('title', 'LIKE', '%' . $keyword . '%');
                    });
            });
        }

        // dd($query->with(['product', 'tiers'])
        //     ->orderBy('created_at', 'DESC')->toSql());

        $offers = $query->with(['product', 'tiers'])
            ->orderBy('created_at', 'DESC')->paginate(10);

        return response([
            'message' => 'success',
            'data' => $offers,
            'total_publish' => $this->quantityOfferModel->where('store_id', $store->store_id)->where('status', 1)->count()
        ], 200);
    }

    public function getOfferDetail($id, Request $request)
    {
        $store = $request->storeInfo;

        $offer = $this->quantityOfferModel->where('store_id', $store->store_id)
            ->where('id', $id)->with(['product', 'tiers'])->first();

        if (!empty($offer)) {
            return response([
                'message' => 'success',
                'data' => $offer
            ], 200);
        }

        return response([
            'message' => 'Not found',
            'data' => []
        ], 404);
    }

    public function createOffer(Request $request)
    {
        try {
            $store = $request->storeInfo;
            $shopifyService = new ShopifyService();
            $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);

            $data = [
                'store_id' => $store->store_id,
                'product_id' => $request->product_id,
                'name' => $request->name,
                'status' => true,
                'mostPopularActive' => $request->mostPopularActive,
                'mostPopularPosition' => $request->mostPopularPosition,
                'countdownTimerActive' => $request->countdownTimerActive,
                'countdownTimerValue' => $request->countdownTimerValue,
                'countdownTimerSession' => $request->countdownTimerSession,
                'countdownTimerReaches' => $request->countdownTimerReaches,
            ];

            // handle cross
            $tiers = $request->get('tiers');
            $offer = $this->quantityOfferModel->create($data);
            $dataTiers = [];
            if (!empty($tiers)) {
                foreach ($tiers as $pr) {
                    $pr = !is_array($pr) ? (array) $pr : $pr;
                    $discountCode = 'MSO_' . $offer->id . '_' . genRandomDiscountNumber();
                    // dump($discountCode);
                    $tier = [
                        'offer_id' => $offer->id,
                        'store_id' => $store->store_id,
                        'name' => $pr['name'],
                        'quantity' => $pr['quantity'],
                        'message' => $pr['message'],
                        'useDiscount' => $pr['useDiscount'],
                        'discountType' => $pr['discountType'],
                        'discountCode' => $discountCode,
                        'discountValue' => $pr['discountValue'],
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];

                    $createDiscount = $shopifyService->createDiscountCode(
                        $discountCode,
                        $pr['discountType'],
                        $pr['quantity'],
                        $pr['discountValue'],
                        $request->product_id
                    );
                    $tier['discountId'] = str_replace('gid://shopify/DiscountCodeNode/', '', $createDiscount['codeDiscountNode']['id']);

                    if (empty($pr['useDiscount'])) {
                        $shopifyService->deactiveDiscountCode(
                            $tier['discountId']
                        );
                    }
                    $dataTiers[] = $tier;
                    sleep(0.5);
                }

                $this->quantityOfferTierModel->insert($dataTiers);
            }

            SystemCache::remove('getOffer_' . $store->shopify_domain);
            dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'offer'));
            return response([
                'message' => 'success',
                'data' => $this->quantityOfferModel->where('store_id', $store->store_id)
                    ->where('id', $offer->id)->with(['product', 'tiers'])->first()
            ], 200);
        } catch (Exception $e) {
            // dd($e);
            return response([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function editOfferDetail($id, Request $request)
    {
        try {
            $store = $request->storeInfo;
            $shopifyService = new ShopifyService();
            $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);

            $offer = $this->quantityOfferModel->where('store_id', $store->store_id)->where('id', $id)->first();

            $allDiscountIds = $this->quantityOfferTierModel->where('store_id', $store->store_id)
                ->where('offer_id', $offer->id)->whereNotNull('discountId')->pluck('discountId')->toArray();
            $listDiscountReUse = []; //  những discount vẫn còn sử dụng

            if (!empty($offer)) {
                $data = [
                    'store_id' => $store->store_id,
                    'product_id' => $request->product_id,
                    'name' => $request->name,
                    'mostPopularActive' => $request->mostPopularActive,
                    'mostPopularPosition' => $request->mostPopularPosition,
                    'countdownTimerActive' => $request->countdownTimerActive,
                    'countdownTimerValue' => $request->countdownTimerValue,
                    'countdownTimerSession' => $request->countdownTimerSession,
                    'countdownTimerReaches' => $request->countdownTimerReaches,
                    // 'status' => !empty($request->status) ? $request->status : false,
                ];

                $tiers = $request->get('tiers');
                $dataTiers = [];
                if (!empty($tiers)) {
                    foreach ($tiers as $pr) {
                        $pr = !is_array($pr) ? (array) $pr : $pr;
                        $discountCode = @$pr['discountCode'];
                        if (empty($discountCode)) {
                            $discountCode = 'MSO_' . $offer->id . '_' . genRandomDiscountNumber();
                        }

                        if (!empty($pr['id'])) {
                            $tiersNotDeleted[] = $pr['id'];
                        }
                        $tier = [
                            'offer_id' => $offer->id,
                            'store_id' => $store->store_id,
                            'name' => $pr['name'],
                            'quantity' => $pr['quantity'],
                            'message' => $pr['message'],
                            'useDiscount' => $pr['useDiscount'],
                            'discountType' => $pr['discountType'],
                            'discountValue' => $pr['discountValue'],
                            'discountId' => @$pr['discountId'],
                            'discountCode' => $discountCode,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];

                        if (!empty($pr['discountId'])) {
                            $listDiscountReUse[] = $pr['discountId'];

                            $updateDiscount = $shopifyService->updatesDiscountCode(
                                $pr['discountId'],
                                $discountCode,
                                $pr['discountType'],
                                $pr['quantity'],
                                $pr['discountValue'],
                                $request->product_id
                            );

                            // kiểm tra nếu bị xóa thì tạo lại discount mới
                            if (!empty($updateDiscount['check_deleted'])) {
                                $discountCode = 'MSO_' . $offer->id . '_' . genRandomDiscountNumber();
                                $createDiscount = $shopifyService->createDiscountCode(
                                    $discountCode,
                                    $pr['discountType'],
                                    $pr['quantity'],
                                    $pr['discountValue'],
                                    $request->product_id
                                );

                                $tier['discountCode'] = $discountCode;
                                $tier['discountId'] = str_replace('gid://shopify/DiscountCodeNode/', '', $createDiscount['codeDiscountNode']['id']);
                            }

                            if (empty($pr['useDiscount'])) {
                                $shopifyService->deactiveDiscountCode(
                                    $tier['discountId']
                                );
                            } else {
                                $shopifyService->activeDiscountCode(
                                    $tier['discountId']
                                );
                            }
                        } else {
                            $createDiscount = $shopifyService->createDiscountCode(
                                $discountCode,
                                $pr['discountType'],
                                $pr['quantity'],
                                $pr['discountValue'],
                                $request->product_id
                            );
                            $tier['discountId'] = str_replace('gid://shopify/DiscountCodeNode/', '', $createDiscount['codeDiscountNode']['id']);
                            if (empty($pr['useDiscount'])) {
                                $shopifyService->deactiveDiscountCode(
                                    $tier['discountId']
                                );
                            }
                        }

                        $dataTiers[] = $tier;

                        sleep(0.5);
                    }
                }

                if ($offer->update($data)) {
                    $this->quantityOfferTierModel->where('offer_id', $offer->id)->delete();
                    $this->quantityOfferTierModel->insert($dataTiers);
                }

                // deactive những discount của tier đã xóa
                $discountCodeDeleted = array_diff($allDiscountIds, $listDiscountReUse);
                if (!empty($discountCodeDeleted)) {
                    foreach ($discountCodeDeleted as $ti) {
                        try {
                            $shopifyService->deactiveDiscountCode(
                                $ti
                            );

                            sleep(0.2);
                        } catch (Exception $e) {
                        }
                    }
                }

                SystemCache::remove('getOffer_' . $store->shopify_domain);
                dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'offer'));
                return response([
                    'message' => 'success',
                    'data' => []
                ], 200);
            }

            return response([
                'message' => 'Not found',
                'data' => []
            ], 404);
        } catch (Exception $e) {
            return response([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function deleteOffers(Request $request)
    {
        $store = $request->storeInfo;
        $isAll = $request->is_all;
        if (empty($isAll)) {
            $offerIds = $request->get('ids');
            if (!empty($offerIds)) {
                $discountIds = $this->quantityOfferTierModel->where('store_id', $store->store_id)->whereIn('offer_id', $offerIds)
                    ->whereNotNull('discountId')->pluck('discountId');

                $this->quantityOfferModel->where('store_id', $store->store_id)->whereIn('id', $offerIds)->delete();
                $this->quantityOfferTierModel->where('store_id', $store->store_id)->whereIn('offer_id', $offerIds)->delete();
                dispatch(new DeactiveDiscountCodeJob($store->store_id, $store->shopify_domain, $store->access_token, $discountIds));

                $productIds = $this->quantityOfferModel->where('store_id', $store->store_id)->whereIn('id', $offerIds)->pluck('product_id');
                dispatch(new AddMetafieldCheckOffferJob($store->store_id, $store->shopify_domain, $store->access_token, $productIds, false));
            }
        } else {
            $this->quantityOfferModel->where('store_id', $store->store_id)->delete();
            $this->quantityOfferTierModel->where('store_id', $store->store_id)->delete();
        }

        // TO DO : xóa các discount code

        SystemCache::remove('getOffer_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'offer'));
        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }


    public function publish(Request $request)
    {
        $store = $request->storeInfo;
        $type = $request->type;
        $ids = $request->ids;


        $data = [
            'status' => true,
        ];
        if ($type == 'all') {
            $save = $this->quantityOfferModel->where('store_id', $store->store_id)->update($data);
        } else {
            $save = $this->quantityOfferModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update($data);

            if ($save) {
                dispatch(new ActiveDiscountCodeJob($store->store_id, $store->shopify_domain, $store->access_token, $ids));

                $productIds = $this->quantityOfferModel->where('store_id', $store->store_id)->whereIn('id', $ids)->pluck('product_id');
                dispatch(new AddMetafieldCheckOffferJob($store->store_id, $store->shopify_domain, $store->access_token, $productIds, true));
            }
        }

        SystemCache::remove('getOffer_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'offer'));
        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }

    public function unpublish(Request $request)
    {
        $store = $request->storeInfo;
        $type = $request->type;
        $ids = $request->ids;

        $data = [
            'status' => false,
        ];
        if ($type == 'all') {
            $save = $this->quantityOfferModel->where('store_id', $store->store_id)->update($data);
        } else {
            $save = $this->quantityOfferModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update($data);

            if ($save) {
                $discountIds = $this->quantityOfferTierModel->where('store_id', $store->store_id)->whereIn('offer_id', $ids)
                    ->whereNotNull('discountId')->pluck('discountId');
                dispatch(new DeactiveDiscountCodeJob($store->store_id, $store->shopify_domain, $store->access_token, $discountIds));

                $productIds = $this->quantityOfferModel->where('store_id', $store->store_id)->whereIn('id', $ids)->pluck('product_id');
                dispatch(new AddMetafieldCheckOffferJob($store->store_id, $store->shopify_domain, $store->access_token, $productIds, false));
            }
        }

        SystemCache::remove('getOffer_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'offer'));
        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }

    public function listProducts(Request $request)
    {
        $store = $request->storeInfo;

        $query = $this->productModel->where('store_id', $store->store_id)->where('status', 'active');
        if (!empty($request->get('product_title'))) {
            $query->where('title', 'LIKE', '%' . $request->get('product_title') . '%');
        }

        // TO DO : loại product đã có offer ra

        $products = $query->doesntHave('offers')->with('variants')->paginate(10);

        return response([
            'message' => 'success',
            'data' => $products
        ], 200);
    }


    public function getSettings(Request $request)
    {
        $store = $request->storeInfo;

        $settings  = $this->settingsModel->find($store->store_id);
        $settingsDefault = config('fa_switcher.setting_quantity_default');
        if (empty($settings)) {
            $this->settingsModel->updateOrCreate(
                ['id' => $store->store_id],
                [
                    'settings' => $settingsDefault
                ]
            );

            $settings  = $this->settingsModel->find($store->store_id);
        }

        return response([
            'data' => $settings,
            'default' => $settingsDefault,
        ]);
    }

    public function updateSettings(Request $request)
    {
        $store = $request->storeInfo;
        $save = $this->settingsModel->where('id', $store->store_id)->update([
            'settings' => !empty($request->settings) ? $request->settings : null,
        ]);

        SystemCache::remove('getOffer_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'offer'));

        return response([
            'message' => 'Success'
        ]);
    }

    public function verifyAppBlock(Request $request)
    {

        $store = $request->storeInfo;
        $shopDomain = $store->shopify_domain;
        $accessToken = $store->access_token;
        $theme = getThemeActive($shopDomain, $accessToken);
        $dataThemeAppExtension = getDataAppBlockExtension($store, $theme->id);
        $statusThemeAppExtension = false;

        $idBlock = config('fa_switcher.app_block_quantity.id_block');
        if (!empty($dataThemeAppExtension)) {
            foreach ($dataThemeAppExtension as $dta) {
                if (strpos($dta, config('fa_switcher.app_block_quantity')[$idBlock]) !== false) {
                    $statusThemeAppExtension = true;
                    break;
                }
            }
        }

        return response([
            'verify' => $statusThemeAppExtension,
            'message' => 'Success'
        ]);
    }

    public function bulkActiveTimmer(Request $request)
    {
        $store = $request->storeInfo;
        $type = $request->type;
        $ids = $request->ids;


        $data = [
            'countdownTimerActive' => true,
        ];
        if ($type == 'all') {
            $save = $this->quantityOfferModel->where('store_id', $store->store_id)->update($data);
        } else {
            $save = $this->quantityOfferModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update($data);
        }

        SystemCache::remove('getOffer_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'offer'));
        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }

    public function bulkDeactiveTimmer(Request $request)
    {
        $store = $request->storeInfo;
        $type = $request->type;
        $ids = $request->ids;


        $data = [
            'countdownTimerActive' => false,
        ];
        if ($type == 'all') {
            $save = $this->quantityOfferModel->where('store_id', $store->store_id)->update($data);
        } else {
            $save = $this->quantityOfferModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update($data);
        }

        SystemCache::remove('getOffer_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'offer'));
        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }
}
