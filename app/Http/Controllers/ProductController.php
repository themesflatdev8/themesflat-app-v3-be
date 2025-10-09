<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\GetProductViewRequest;
use App\Models\ResponseModel;
use App\Repository\DiscountRepository;
use App\Repository\ProductRepository;
use App\Services\App\ProductService;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //
    protected $productRepository;
    protected $sentry;
    protected $productService;

    public function __construct(ProductRepository $productRepository, ProductService $productService)
    {
        $this->productRepository = $productRepository;
        $this->productService = $productService;
        $this->sentry = app('sentry');
    }
    public function getRecentViews(Request $request)
    {
        // Lấy param từ request
        try {
            $userId = $request->input('user_id');
            $domain = $request->input('shopInfo')['shop'];

            // Query DB (giả sử bảng trong Laravel migration là `shopify_recent_views`)
            $result = $this->productRepository->shopifyRecentViews($userId, $domain);
            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }
        return response()->json([
            'status' => 'error',
        ]);
    }

    public function getProductViews(GetProductViewRequest $request)
    {
        try {
            $shopDomain = $request->input('shopInfo')['shop'];
            $data = [
                'user_agent' => $request->header('User-Agent', ''),
                'referer'    => $request->header('Referer', ''),
                'user_id'    => $request->get('user_id'),
                'handle'     => $request->get('handle'),
                'product_id' => $request->input('product_id'),
            ];

            $apiName = 'getProductViews';
            $paramHash = md5(json_encode([
                'product_id' => $data['product_id'],
                'user_id'    => $data['user_id'],
                'handle'     => $data['handle'],
            ]));

            // 1️⃣ Check cache
            $cached = ResponseModel::where('shop_domain', $shopDomain)
                ->where('api_name', $apiName)
                ->where('param', $paramHash)
                ->where('expire_time', '>', now())
                ->first();

            if ($cached) {
                return response()->json([
                    'status' => 'success',
                    'views' => (int) json_decode($cached->response, true)
                ]);
            }

            // 2️⃣ Gọi service thật
            $result = $this->productRepository->getProductViews($shopDomain, $data);

            // 3️⃣ Lưu cache
            ResponseModel::updateOrCreate(
                [
                    'shop_domain' => $shopDomain,
                    'api_name'    => $apiName,
                    'param'       => $paramHash,
                ],
                [
                    'response'    => json_encode((int) $result),
                    'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)),
                ]
            );

            return response()->json([
                'status' => 'success',
                'views'  => (int) $result
            ]);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }

        return response()->json([
            'status' => 'error',
        ]);
    }


    public function productTopView(Request $request)
    {
        $shopInfo = $request->input('shopInfo');
        $limit = $request->input('limit', 10);
        $shopDomain = $shopInfo['shop'] ?? null;

        $apiName = 'productTopView';
        $paramHash = md5(json_encode(['limit' => $limit]));
        $expireMinutes = 60;

        try {
            // 1️⃣ Check cache
            $cached = ResponseModel::where('shop_domain', $shopDomain)
                ->where('api_name', $apiName)
                ->where('param', $paramHash)
                ->where('expire_time', '>', now())
                ->first();

            if ($cached) {
                return response()->json([
                    'status' => 'success',
                    'data' => json_decode($cached->response, true)
                ]);
            }

            // 2️⃣ Gọi service thật
            $result = $this->productService->productTopView($shopInfo, $limit);

            // 3️⃣ Lưu cache
            ResponseModel::updateOrCreate(
                [
                    'shop_domain' => $shopDomain,
                    'api_name'    => $apiName,
                    'param'       => $paramHash,
                ],
                [
                    'response'    => json_encode($result),
                    'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)),
                ]
            );

            return response()->json([
                'status' => 'success',
                'data'   => $result
            ]);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }

        return response()->json([
            'status' => 'success',
            'data' => []
        ]);
    }


    public function getApiProduct(Request $request)
    {
        try {
            $domain = $request->input('shopInfo')['shop'];
            $accessToken = $request->input('shopInfo')['access_token'];
            $data = $request->all();
            /** @var ShopifyApiService $shopifyApiService */
            $shopifyApiService = app(ShopifyApiService::class);
            $shopifyApiService->setShopifyHeader($domain, $accessToken);
            $result = $shopifyApiService->getApiProduct($domain, $accessToken, $data);
            return response()->json($result);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }
        return response()->json([
            'status' => 'error',
        ]);
    }

    public function getProductRelated(Request $request)
    {
        /*
    Thứ tự ưu tiên:
    1. Cùng category
    2. Bought
    3. Top viewed
    4. Sản phẩm mới nhất
    (Lưu ý: lấy đủ 10 sản phẩm)
    */

        try {
            $shopInfo = $request->input('shopInfo');
            $shopDomain = $shopInfo['shop'] ?? null;
            $collectionId = $request->input('collection_id');
            $limit = (int) $request->input('limit', 10);

            $apiName = 'getProductRelated';
            $paramHash = md5(json_encode([
                'collection_id' => $collectionId,
                'limit' => $limit,
            ]));

            // 1️⃣ Check cache
            $cached = ResponseModel::where('shop_domain', $shopDomain)
                ->where('api_name', $apiName)
                ->where('param', $paramHash)
                ->where('expire_time', '>', now())
                ->first();

            if ($cached) {
                return response()->json([
                    'status' => 'success',
                    'data' => json_decode($cached->response, true),
                    'cached' => true, // optional: giúp debug dễ hơn
                ]);
            }

            // 2️⃣ Gọi service thật
            $result = $this->productService->getProductRelated($shopInfo, $collectionId, $limit);

            // 3️⃣ Lưu cache
            ResponseModel::updateOrCreate(
                [
                    'shop_domain' => $shopDomain,
                    'api_name' => $apiName,
                    'param' => $paramHash,
                ],
                [
                    'response' => json_encode($result),
                    'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)), // config trong tf_cache.php
                ]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
                'cached' => false,
            ]);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }

        return response()->json([
            'status' => 'error',
            'data' => [],
        ]);
    }


    public function getProductRecent(Request $request)
    {
        $shopInfo = $request->input('shopInfo');
        $limit = $request->input('limit', 10);
        $domain = $shopInfo['shop'] ?? null;

        $apiName = 'getProductRecent';
        $paramHash = md5(json_encode(['limit' => $limit]));

        try {
            // 1️⃣ Kiểm tra cache trong DB
            $cached = ResponseModel::query()
                ->where('shop_domain', $domain)
                ->where('api_name', $apiName)
                ->where('param', $paramHash)
                ->where('expire_time', '>', now())
                ->first();

            if ($cached) {
                return response()->json([
                    'status' => 'success',
                    'data' => json_decode($cached->response, true)
                ]);
            }

            // 2️⃣ Gọi service thật nếu chưa có cache
            $result = $this->productService->getProductRecent($shopInfo, $limit);

            // 3️⃣ Lưu lại vào bảng responses
            ResponseModel::updateOrCreate(
                [
                    'shop_domain' => $domain,
                    'api_name' => $apiName,
                    'param' => $paramHash,
                ],
                [
                    'response' => json_encode($result),
                    'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)),
                ]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }

        return response()->json([
            'status' => 'success',
            'data' => []
        ]);
    }


    public function getOff(Request $request)
    {
        $shopInfo = $request->input('shopInfo');
        $ids = $request->input('ids');
        $shopDomain = $shopInfo['shop'] ?? null;

        $apiName = 'getOff';
        $paramHash = md5(json_encode(['ids' => $ids]));
        $expireMinutes = 60; // cache 1 giờ

        try {
            // 1️⃣ Kiểm tra cache trong bảng responses
            $cached = \App\Models\ResponseModel::query()
                ->where('shop_domain', $shopDomain)
                ->where('api_name', $apiName)
                ->where('param', $paramHash)
                ->where('expire_time', '>', now())
                ->first();

            if ($cached) {
                return response()->json([
                    'status' => 'success',
                    'data' => json_decode($cached->response, true)
                ]);
            }

            // 2️⃣ Nếu không có cache → gọi service thật
            $result = $this->productService->getOff($shopInfo, $ids);

            // 3️⃣ Lưu lại vào bảng responses
            \App\Models\ResponseModel::updateOrCreate(
                [
                    'shop_domain' => $shopDomain,
                    'api_name' => $apiName,
                    'param' => $paramHash,
                ],
                [
                    'response' => json_encode($result),
                    'expire_time' => now()->addMinutes($expireMinutes),
                ]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result
            ]);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }

        return response()->json([
            'status' => 'success',
            'data' => []
        ]);
    }
}
