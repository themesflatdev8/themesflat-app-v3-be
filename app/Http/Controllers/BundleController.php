<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\ActiveDiscountJob;
use App\Jobs\DeactiveDiscountV2Job;
use App\Jobs\GenerateBundleDefaultJob;
use App\Jobs\GenerateBundleJob;
use App\Jobs\StoreFrontCacheVersionJob;
use App\Jobs\Sync\SyncShopifyProductsJobV2;
use App\Models\BundleMainIdsModel;
use App\Models\BundlesModel;
use App\Models\CollectionModel;
use App\Models\ProductCommenditionsModel;
use App\Models\ProductModel;
use App\Models\ProductVariantModel;
use App\Models\StoreModel;
use App\Services\Shopify\ShopifyService;
use Exception;
use Illuminate\Http\Request;

class BundleController extends Controller
{
    public $storeModel;
    public $productModel;
    public $collectionModel;
    public $bundleModel;
    public $productCommendationModel;
    public $mainIdsModel;
    public $sentry;

    public function __construct(
        StoreModel $storeModel,
        ProductModel $productModel,
        CollectionModel $collectionModel,
        BundlesModel $bundleModel,
        ProductCommenditionsModel $productCommendationModel,
        BundleMainIdsModel $mainIdsModel,
    ) {
        $this->storeModel = $storeModel;
        $this->sentry = app('sentry');
        $this->productModel = $productModel;
        $this->bundleModel = $bundleModel;
        $this->collectionModel = $collectionModel;
        $this->productCommendationModel = $productCommendationModel;
        $this->mainIdsModel = $mainIdsModel;
    }

    public function getBundles(Request $request)
    {
        $store = $request->storeInfo;

        $query = $this->bundleModel->where('store_id', $store->store_id);

        if (!empty($request->type)) {
            $query = $query->whereIn('type', $request->type);
        }
        if (!empty($request->pageType)) {
            $query = $query->where('pageType', $request->pageType);
        }
        if (isset($request->mode)) {
            $query = $query->where('mode', $request->mode);
        }
        // if (!empty($request->cross_option)) {
        //     $query = $query->where('cross_option', $request->cross_option);
        // }
        if (isset($request->status)) {
            $query = $query->where('status', $request->status);
        }

        if (!empty($request->keyword)) {
            $keyword  = $request->keyword;

            $query = $query->where(function ($query) use ($keyword) {
                $query->where('name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('type', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('id', 'LIKE', '%' . $keyword . '%')
                    ->orWhereHas('listDefaultProducts', function ($query) use ($keyword) {
                        return $query->where('title', 'LIKE', '%' . $keyword . '%');
                    })
                    ->orWhereHas('listDefaultCollections', function ($query) use ($keyword) {
                        return $query->where('title', 'LIKE', '%' . $keyword . '%');
                    });
            });
        }

        $bundles = $query->with(['listDefaultIds', 'listDefaultProducts', 'listDefaultCollections', 'listCommendations'])
            ->orderBy('created_at', 'DESC')->paginate(10)->toArray();

        if (!empty($bundles['data'])) {
            foreach ($bundles['data'] as &$bundle) {
                $bundle = $this->convertBundle50($bundle);
            }
        }


        return response([
            'message' => 'success',
            'data' => $bundles,
            'total_publish' => $this->bundleModel->where('store_id', $store->store_id)->where('status', 1)->count()
        ], 200);
    }

    public function getFirstBundlePublish(Request $request)
    {
        $store = $request->storeInfo;

        $bundle = $this->bundleModel->where('store_id', $store->store_id)->where('status', 1)
            ->whereHas('product', function ($query) {
                return $query->where('status', 'active');
            })->with(['product'])->first();

        return response([
            'message' => 'success',
            'data' => $bundle,
        ], 200);
    }

    public function getBundleDetail($id, Request $request)
    {
        $store = $request->storeInfo;
        $pageType = $request->pageType;


        if ($pageType == "cart") {
            $bundle = $this->bundleModel->where('store_id', $store->store_id)
                ->where('pageType', 'cart')->with(['listDefaultIds', 'listDefaultProducts', 'listDefaultCollections', 'listCommendations'])->first();
        } else {
            $bundle = $this->bundleModel->where('store_id', $store->store_id)
                ->where('id', $id)->with(['listDefaultIds', 'listDefaultProducts', 'listDefaultCollections', 'listCommendations'])->first();
        }

        if (!empty($bundle->productCommendations)) {
            $productCommendationsWithVariants = [];
            foreach ($bundle->productCommendations as $productCommendations) {
                $variants  = ProductVariantModel::where('product_id', $productCommendations->id)->get();
                $productCommendations->variants = $variants;
                $productCommendationsWithVariants[] = $productCommendations;
            }

            $bundle->list_commendations = $productCommendationsWithVariants;
        }

        $giftProduct = null;
        if (!empty($bundle->gift_id)) {
            $giftProduct = ProductModel::where('id', $bundle->gift_id)->first();
        }
        $bundle->gift_product = $giftProduct;

        // dd($bundle->productCommendations);
        if (!empty($bundle)) {
            $bundle = $bundle->toArray();
            $bundle = $this->convertBundle50($bundle);

            $discountUse = null;
            if (!empty($bundle['useDiscount']) && $bundle['mode'] == "manual") {
                if ($bundle['promotionType'] == BundlesModel::PROMOTION_TYPE_DISCOUNT && !empty($bundle['discountId'])) {
                    $discountUse = $bundle['discountId'];
                }

                if ($bundle['promotionType'] == BundlesModel::PROMOTION_TYPE_FRESHIP && !empty($bundle['discontFreeshipId'])) {
                    $discountUse = $bundle['discontFreeshipId'];
                }
            }

            if (!empty($discountUse)) {
                $shopifyService = new ShopifyService();
                $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);
                $checkDiscount = $shopifyService->checkDiscountV2($discountUse);

                $bundle['check_discount'] = $checkDiscount;
                if (empty($checkDiscount)) {
                    $bundle['useDiscount'] = false;
                }
            }


            return response([
                'message' => 'success',
                'data' => $bundle
            ], 200);
        }

        return response([
            'message' => 'Not found',
            'data' => []
        ], 404);
    }

    /**
     * convert data bundle bắt đầu  từ version 5.0
     */
    private function convertBundle50(array $bundle)
    {
        if (!empty($bundle['list_default_ids'])) {
            foreach ($bundle['list_default_ids'] as &$id) {
                $id = $id['main_id'];
            }
        }

        return $bundle;
    }

    public function createBundle(Request $request)
    {
        try {
            $store = $request->storeInfo;
            $shopifyService = new ShopifyService();
            $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);

            $data = [
                'store_id' => $store->store_id,
                // 'product_id' => $request->product_id,
                'type' => $request->type,
                'pageType' => $request->pageType ? $request->pageType : 'product',
                'mode' => !empty($request->mode) ? $request->mode : 'manual',
                // 'cross_option' => $request->cross_option,
                'status' => true,
                'name' => @$request->name,
                'useDiscount' => $request->get('useDiscount'),
                'minimumAmount' => $request->get('minimumAmount'),
                // new
                'promotionType' => $request->get('promotionType'),

                'discountType' => $request->get('discountType'),
                'discountValue' => $request->get('discountValue'),
                'discountContent' => $request->get('discountContent'),

                'discountFreeshipType' => $request->get('discountFreeshipType'),
                'discountFreeshipValue' => $request->get('discountFreeshipValue'),
                'discountFreeshipContent' => $request->get('discountFreeshipContent'),

                'selectable' => $request->get('selectable'),
                'discountOncePer' => $request->get('discountOncePer'),

                'maxProduct' => $request->get('maxProduct'),
                'countdownTimerActive' => $request->get('countdownTimerActive'),
                'countdownTimerValue' => $request->get('countdownTimerValue'),
                'countdownTimerSession' => $request->get('countdownTimerSession'),
                'countdownTimerReaches' => $request->get('countdownTimerReaches'),
                'templateDesktop' => $request->get('templateDesktop'),
                'templateMobile' => $request->get('templateMobile'),
            ];

            // handle cross
            $productCommendations = $request->get('list_commendations');
            // if ($request->cross_option == "cross") {
            //     $productCommendationsId = [];
            //     if (!empty($productCommendations)) {
            //         foreach ($productCommendations as $pr) {
            //             $pr = !is_array($pr) ? (array) $pr : $pr;
            //             $productCommendationsId[] = $pr['id'];
            //         }

            //         $data['cross_ids'] = $productCommendationsId;
            //     }
            // } else {
            //     $data['cross_ids'] = null;
            // }

            // insert to bundle table
            $bundle = $this->bundleModel->create($data);

            // handle list_default_ids
            $mainIds = $request->list_default_ids;
            if (!empty($mainIds)) {
                foreach ($mainIds as $mainId) {
                    $dataMainId[] = [
                        'bundle_id' => $bundle->id,
                        'store_id' => $store->store_id,
                        'main_id' => $mainId,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s')
                    ];
                }
                $this->mainIdsModel->insert($dataMainId);
            }

            $dataCommendations = [];
            if (!empty($productCommendations)) {
                $duration = 0;
                foreach ($productCommendations as $pr) {
                    $duration = $duration + 1;
                    $pr = !is_array($pr) ? (array) $pr : $pr;
                    $dataCommendations[] = [
                        'bundle_id' => $bundle->id,
                        'store_id' => $store->store_id,
                        'product_id' => $pr['id'],
                        // 'type' => $pr['type'],
                        'created_at' => date("Y-m-d H:i:s", strtotime("+$duration sec")),
                        'updated_at' => date("Y-m-d H:i:s", strtotime("+$duration sec")),
                    ];
                }
                $this->productCommendationModel->insert($dataCommendations);
            }

            if (!empty($request->get('useDiscount')) && $request->mode == "manual") {
                $promotionType = $request->get('promotionType');
                $data = $this->handleDiscountV2(
                    $bundle,
                    $promotionType,
                    $shopifyService,
                    $data,
                    $productCommendations,
                    $request->list_default_ids,
                    $request->get('discountOncePer'),
                    'create'
                );
                $bundle->update($data);
            }

            SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
            dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));

            $bundleResponse = $this->bundleModel->where('store_id', $store->store_id)
                ->where('id', $bundle->id)->with(['listDefaultIds', 'listDefaultProducts', 'listDefaultCollections', 'listCommendations'])->first()->toArray();

            return response([
                'message' => 'success',
                'data' => $this->convertBundle50($bundleResponse)
            ], 200);
        } catch (Exception $e) {
            // dd($e);
            return response([
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function editBundleDetail($id, Request $request)
    {
        try {
            $store = $request->storeInfo;
            $shopifyService = new ShopifyService();
            $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);

            $pageType = $request->pageType;


            if ($pageType == "cart") {
                $bundle = $this->bundleModel->where('store_id', $store->store_id)->where('pageType', 'cart')->first();
            } else {
                $bundle = $this->bundleModel->where('store_id', $store->store_id)->where('id', $id)->first();
            }
            if (!empty($bundle)) {
                $data = [
                    'product_id' => null,
                    'type' => $request->type,
                    'mode' => !empty($request->mode) ? $request->mode : 'manual',
                    // 'cross_option' => $request->cross_option,
                    'name' => @$request->name,
                    'useDiscount' => $request->get('useDiscount'),
                    'minimumAmount' => $request->get('minimumAmount'),
                    'promotionType' => $request->get('promotionType'),

                    'discountType' => $request->get('discountType'),
                    'discountValue' => $request->get('discountValue'),
                    'discountContent' => $request->get('discountContent'),

                    'discountFreeshipType' => $request->get('discountFreeshipType'),
                    'discountFreeshipValue' => $request->get('discountFreeshipValue'),
                    'discountFreeshipContent' => $request->get('discountFreeshipContent'),

                    'selectable' => $request->get('selectable'),
                    'discountOncePer' => $request->get('discountOncePer'),

                    'maxProduct' => $request->get('maxProduct'),
                    'countdownTimerActive' => $request->get('countdownTimerActive'),
                    'countdownTimerValue' => $request->get('countdownTimerValue'),
                    'countdownTimerSession' => $request->get('countdownTimerSession'),
                    'countdownTimerReaches' => $request->get('countdownTimerReaches'),
                    'templateDesktop' => $request->get('templateDesktop'),
                    'templateMobile' => $request->get('templateMobile'),
                ];



                $productCommendations = $request->get('list_commendations');
                $dataCommendations = [];
                $productCommendationsId = [];
                if (!empty($productCommendations)) {
                    $duration = 0;
                    foreach ($productCommendations as $pr) {
                        $duration = $duration + 1;
                        $pr = !is_array($pr) ? (array) $pr : $pr;
                        $dataCommendations[] = [
                            'bundle_id' => $bundle->id,
                            'store_id' => $store->store_id,
                            'product_id' => $pr['id'],
                            // 'type' => $pr['type'],
                            'created_at' => date("Y-m-d H:i:s", strtotime("+$duration sec")),
                            'updated_at' => date("Y-m-d H:i:s", strtotime("+$duration sec")),
                        ];
                        $productCommendationsId[] = $pr['id'];
                    }
                }

                // handle cross
                // if ($request->cross_option == "cross") {
                //     $data['cross_ids'] = $productCommendationsId;
                // } else {
                //     $data['cross_ids'] = null;
                // }

                $deactiveDiscount = false;

                // check nếu bundle đang có discount  + edit mới useDiscount = false  -> deactiveDiscount = true
                if (!empty($bundle->discountId) && empty($request->get('useDiscount'))) {
                    $deactiveDiscount = true;
                }

                // check nếu user chuyển promotionType thì cũng deactive discount
                if ($request->get('promotionType') != $bundle->promotionType) {
                    $deactiveDiscount = true;
                }

                if ($deactiveDiscount) {
                    if (!empty($bundle->discountId)) {
                        $shopifyService->deactiveDiscountCode(
                            $bundle->discountId
                        );
                    }


                    if (!empty($bundle->discontFreeshipId)) {
                        $shopifyService->deactiveDiscountCode(
                            $bundle->discontFreeshipId
                        );
                    }
                }

                if (!empty($request->get('useDiscount'))  && $request->mode == "manual") {
                    $promotionType = $request->get('promotionType');
                    $data = $this->handleDiscountV2(
                        $bundle,
                        $promotionType,
                        $shopifyService,
                        $data,
                        $productCommendations,
                        $request->list_default_ids,
                        $request->get('discountOncePer'),
                        'update'
                    );
                }

                if ($bundle->update($data)) {
                    $this->productCommendationModel->where('bundle_id', $bundle->id)->delete();
                    $this->productCommendationModel->insert($dataCommendations);


                    // handle list_default_ids
                    $this->mainIdsModel->where('bundle_id', $bundle->id)->delete();
                    $mainIds = $request->list_default_ids;
                    if (!empty($mainIds)) {
                        foreach ($mainIds as $mainId) {
                            $dataMainId[] = [
                                'bundle_id' => $bundle->id,
                                'store_id' => $store->store_id,
                                'main_id' => $mainId,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                        }
                        $this->mainIdsModel->insert($dataMainId);
                    }
                }

                SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
                dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));
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

    // move sang dùng discount code
    private function handleDiscountV2(
        $bundle,
        $promotionType,
        $shopifyService,
        $data,
        $productCommendations,
        $mainIds,
        $appliesOnEachItem,
        $actionType = 'create'
    ) {
        if (!empty($mainIds)) {
            foreach ($mainIds as $mainId) {
                array_push($productCommendations, ['id' => $mainId]);
            }
        }

        switch ($promotionType) {
            case BundlesModel::PROMOTION_TYPE_DISCOUNT:
                $code = 'MSB_' . $bundle->id . '_Amount_' . genRandomDiscountNumber();
                if (empty($bundle->discountId)) {
                    $createDiscount = $shopifyService->createDiscountV2(
                        $bundle->id,
                        $code,
                        $data['discountType'],
                        $data['minimumAmount'],
                        $data['discountValue'],
                        $productCommendations,
                        $bundle->type == BundlesModel::BUNDLE_TYPE_COLLECTION ? "collection" : "product",
                        @$data['combine_discount'],
                        $appliesOnEachItem
                    );

                    $data['discountCode'] = $code;
                    $data['discountId'] = str_replace('gid://shopify/DiscountCodeNode/', '', $createDiscount['codeDiscountNode']['id']);
                    if ($actionType == "create") {
                        $shopifyService->activeDiscountCode($data['discountId']);
                    }
                } else {
                    $discountId = $bundle->discountId;
                    $productDiscountsCurrent = []; // danh sách products trên discount admin hiện tại

                    try {
                        $discount = $shopifyService->getDiscountV2($discountId);
                        if (!empty($discount->codeDiscount->customerGets->items->products->edges)) {
                            foreach ($discount->codeDiscount->customerGets->items->products->edges as $prNode) {
                                $productDiscountsCurrent[] = $prNode->node->id;
                            }
                        }
                    } catch (Exception $e) {
                    }

                    // dump($productDiscountsCurrent);
                    $update = $shopifyService->updateDiscountV2(
                        $bundle->id,
                        $discountId,
                        $data['discountType'],
                        $data['minimumAmount'],
                        $data['discountValue'],
                        $this->productCommendationModel->where('bundle_id', $bundle->id)->pluck('product_id'),
                        $productCommendations,
                        $bundle->type == BundlesModel::BUNDLE_TYPE_COLLECTION ? "collection" : "product",
                        @$data['combine_discount'],
                        $appliesOnEachItem,
                        $productDiscountsCurrent
                    );

                    // kiểm tra nếu bị xóa thì tạo lại discount mới
                    if (!empty($update['check_deleted'])) {
                        $createDiscount = $shopifyService->createDiscountV2(
                            $bundle->id,
                            $code,
                            $data['discountType'],
                            $data['minimumAmount'],
                            $data['discountValue'],
                            $productCommendations,
                            $bundle->type == BundlesModel::BUNDLE_TYPE_COLLECTION ? "collection" : "product",
                            @$data['combine_discount'],
                            $appliesOnEachItem
                        );
                        $data['discountCode'] = $code;
                        $discountId = $data['discountId'] = str_replace('gid://shopify/DiscountCodeNode/', '', $createDiscount['codeDiscountNode']['id']);
                    }

                    $shopifyService->activeDiscountCode($discountId);
                }
                break;
            case BundlesModel::PROMOTION_TYPE_FRESHIP:
                $code = 'MSB_' . $bundle->id . '_Freeship_' . genRandomDiscountNumber();
                if (empty($bundle->discontFreeshipId)) {
                    $createDiscount = $shopifyService->createFreeshipV2(
                        $bundle->id,
                        $code,
                        $data['minimumAmount'],
                    );

                    $data['discountFreeshipCode'] = $code;
                    $data['discontFreeshipId'] = str_replace('gid://shopify/DiscountCodeNode/', '', $createDiscount['codeDiscountNode']['id']);
                    if ($actionType == "create") {
                        $shopifyService->activeDiscountCode($data['discontFreeshipId']);
                    }
                } else {
                    $discountId = $bundle->discontFreeshipId;
                    $update = $shopifyService->updateFreeshipV2(
                        $bundle->id,
                        $discountId,
                        $data['minimumAmount'],
                    );

                    // kiểm tra nếu bị xóa thì tạo lại discount mới
                    if (!empty($update['check_deleted'])) {
                        $createDiscount = $shopifyService->createFreeshipV2(
                            $bundle->id,
                            $code,
                            $data['minimumAmount'],
                        );
                        $data['discountFreeshipCode'] = $code;
                        $discountId = $data['discontFreeshipId'] = str_replace('gid://shopify/DiscountCodeNode/', '', $createDiscount['codeDiscountNode']['id']);
                    }

                    $shopifyService->activeDiscountCode($discountId);
                }
                break;
            case BundlesModel::PROMOTION_TYPE_GIFT:
                break;
        }

        return $data;
    }

    public function deleteBundles(Request $request)
    {
        $store = $request->storeInfo;
        $isAll = $request->is_all;
        if (empty($isAll)) {
            $bundleIds = $request->get('ids');
            if (!empty($bundleIds)) {
                $discountIds = $this->bundleModel->where('store_id', $store->store_id)
                    ->whereIn('id', $bundleIds)
                    ->whereNotNull('discountId')->pluck('discountId')->toArray();

                // dump($discountIds);
                $discountIds2 = $this->bundleModel->where('store_id', $store->store_id)
                    ->whereIn('id', $bundleIds)
                    ->whereNotNull('discontFreeshipId')->pluck('discontFreeshipId')->toArray();
                // dump($discountIds2);

                $discountIds = array_merge($discountIds, $discountIds2);

                // dd($discountIds);

                $this->bundleModel->where('store_id', $store->store_id)->whereIn('id', $bundleIds)->delete();
                $this->productCommendationModel->where('store_id', $store->store_id)->whereIn('bundle_id', $bundleIds)->delete();
                $this->mainIdsModel->where('store_id', $store->store_id)->whereIn('bundle_id', $bundleIds)->delete();

                dispatch(new DeactiveDiscountV2Job($store->store_id, $store->shopify_domain, $store->access_token, $discountIds));
            }
        } else {
            $this->bundleModel->where('store_id', $store->store_id)->delete();

            $this->productCommendationModel->where('store_id', $store->store_id)->delete();
            $this->mainIdsModel->where('store_id', $store->store_id)->delete();
        }

        // TO DO : xóa các discount code

        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));
        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }

    public function generateBundleDefault(Request $request)
    {
        $store = $request->storeInfo;
        dispatch(new GenerateBundleDefaultJob($store->store_id, $store->shopify_domain, $store->access_token));

        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }

    public function generateAI(Request $request)
    {
        $store = $request->storeInfo;
        $product_id = $request->product_id;
        $products = $this->productModel->where('store_id', $store->store_id)->where('status', 'active')->get();
        $random = 5;
        $totalProduct = collect($products)->whereNotIn('id', $product_id)->count();
        if ($totalProduct < 5) {
            $random = $totalProduct;
        }
        $productCommendations = collect($products)->whereNotIn('id', $product_id)->random($random);

        return response([
            'message' => 'success',
            'data' => $productCommendations
        ], 200);
    }

    public function generateBundle(Request $request)
    {
        $store = $request->storeInfo;
        // $type = $request->type;
        $ids = $request->ids;

        $bundles = $this->bundleModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update([
            'status' => true
        ]);
        $bundles = $this->bundleModel->where('store_id', $store->store_id)->whereIn('id', $ids)->get();
        if (!empty($bundles)) {
            $dataCommendations = [];
            $products = $this->productModel->where('store_id', $store->store_id)->where('status', 'active')->get();

            foreach ($bundles as $bundle) {
                $random = 5;
                $totalProduct = collect($products)->whereNotIn('id', $bundle->product_id)->count();
                if ($totalProduct < 5) {
                    $random = $totalProduct;
                }
                $this->productCommendationModel->where('bundle_id', $bundle->id)->delete();
                $productCommendations = collect($products)->whereNotIn('id', $bundle->product_id)->random($random);
                if (!empty($productCommendations)) {
                    foreach ($productCommendations as $pr) {
                        $dataCommendations[] = [
                            'bundle_id' => $bundle->id,
                            'store_id' => $store->store_id,
                            'product_id' => $pr->id,
                            'created_at' => date('Y-m-d H:i:s'),
                            'updated_at' => date('Y-m-d H:i:s')
                        ];
                    }
                }
            }
            $this->productCommendationModel->insert($dataCommendations);

            SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
            dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));
            return response([
                'message' => 'success',
            ], 200);
        }

        return response([
            'message' => 'Not found',
            'data' => []
        ], 404);
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
            $save = $this->bundleModel->where('store_id', $store->store_id)->update($data);
        } else {
            $save = $this->bundleModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update($data);

            dispatch(new ActiveDiscountJob($store->store_id, $store->shopify_domain, $store->access_token, $ids));
        }

        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));
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
            $save = $this->bundleModel->where('store_id', $store->store_id)->update($data);
        } else {
            $save = $this->bundleModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update($data);

            $discountIds = $this->bundleModel->where('store_id', $store->store_id)
                ->whereIn('id', $ids)
                ->whereNotNull('discountId')->pluck('discountId')->toArray();

            $discountIds2 = $this->bundleModel->where('store_id', $store->store_id)
                ->whereIn('id', $ids)
                ->whereNotNull('discontFreeshipId')->pluck('discontFreeshipId')->toArray();

            $discountIds = array_merge($discountIds, $discountIds2);
            dispatch(new DeactiveDiscountV2Job($store->store_id, $store->shopify_domain, $store->access_token, $discountIds));
        }

        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));
        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }

    public function generateBundles(Request $request)
    {
        $store = $request->storeInfo;
        dispatch(new GenerateBundleJob($store->store_id, $store->shopify_domain, $store->access_token));

        return response([
            'message' => 'success',
            'data' => [
                'second' => rand(7, 20)
            ]
        ], 200);
    }

    public function syncProducts(Request $request)
    {
        $store = $request->storeInfo;

        dispatch(new SyncShopifyProductsJobV2($store->store_id, $store->shopify_domain, $store->access_token));

        return response([
            'message' => 'success'
        ], 200);
    }


    public function listProducts(Request $request)
    {
        $store = $request->storeInfo;

        $query = $this->productModel->where('store_id', $store->store_id)->where('status', 'active')
            ->where('requires_selling_plan', "!=", 1);
        if (!empty($request->get('product_title'))) {
            $query->where('title', 'LIKE', '%' . $request->get('product_title') . '%');
        }
        if (!empty($request->get('product_bundle_id'))) {
            $query->whereNotIn('id', [$request->get('product_bundle_id')]);
        }

        $products = $query->with('variants')->paginate(10);

        return response([
            'message' => 'success',
            'data' => $products
        ], 200);
    }

    public function listCollections(Request $request)
    {
        $store = $request->storeInfo;

        $query = $this->collectionModel->where('store_id', $store->store_id);
        if (!empty($request->get('title'))) {
            $query->where('title', 'LIKE', '%' . $request->get('title') . '%');
        }
        // if (!empty($request->get('product_bundle_id'))) {
        //     $query->whereNotIn('id', [$request->get('product_bundle_id')]);
        // }

        $collections = $query->paginate(10);

        return response([
            'message' => 'success',
            'data' => $collections
        ], 200);
    }

    public function checkDiscountLimit(Request $request)
    {
        $store = $request->storeInfo;
        $shopifyService = new ShopifyService();
        $shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);
        $limitDiscountTotal = 25;

        $discounts = $shopifyService->getDiscounts();
        $listDiscountIds = [];
        $limit = false;
        $countDiscount = 0;
        if (!empty($discounts) && count($discounts) >= $limitDiscountTotal) {
            foreach ($discounts as $discount) {
                $listDiscountIds[] = str_replace('gid://shopify/DiscountAutomaticNode/', '', $discount['node']['id']);
                if ($discount['node']['automaticDiscount']['status'] == "ACTIVE" || $discount['node']['automaticDiscount']['status'] == "SCHEDULED") {
                    $countDiscount += 1;
                }
            }
            if ($countDiscount >= $limitDiscountTotal) {
                $limit = true;

                // dump($listDiscountIds);
                // $bundle  = BundlesModel::find($request->bundle_id);
            }
        }
        return response([
            'message' => 'success',
            'data' => [
                'limit' => $limit,
                'countDiscount' => $countDiscount
            ]
        ], 200);
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
            $save = $this->bundleModel->where('store_id', $store->store_id)->update($data);
        } else {
            $save = $this->bundleModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update($data);
        }

        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));
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
            $save = $this->bundleModel->where('store_id', $store->store_id)->update($data);
        } else {
            $save = $this->bundleModel->where('store_id', $store->store_id)->whereIn('id', $ids)->update($data);
        }

        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));
        return response([
            'message' => 'success',
            'data' => []
        ], 200);
    }
}
