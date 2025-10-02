<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\GetProductViewRequest;
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

    public function  getProductViews(GetProductViewRequest $request)
    {
        try {
            $domain = $request->input('shopInfo')['shop'];
            // 3. Lấy thêm thông tin trình duyệt và nguồn
            $data = [
                'user_agent' => $request->header('User-Agent', ''),
                'referer' => $request->header('Referer', ''),
                'user_id' =>  $request->get('user_id'),
                'handle' =>  $request->get('handle'),
                'product_id' => $request->input('product_id')
            ];

            $result = $this->productRepository->getProductViews($domain, $data);
            return response()->json([
                'status' => 'success',
                'views' => (int) $result
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
        $result = $this->productService->productTopView($shopInfo, $limit);

        return response()->json([
            'status' => 'success',
            'data' => $result
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
        thứ tự lấy ưu tiên
        1, cùng category
        2, bought
        3, topviewd
        4, lấy sản phẩm mới nhất
        *lưu ý: lấy đủ 10 sản phẩm
        */
        $shopInfo = $request->input('shopInfo');
        $collectionId = $request->input('collection_id');
        $limit = $request->input('limit', 10);
        $result = $this->productService->getProductRelated($shopInfo, $collectionId, $limit);
        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    public function getProductRecent(Request $request)
    {
        $shopInfo = $request->input('shopInfo');
        $limit = $request->input('limit', 10);
        $result = $this->productService->getProductRecent($shopInfo, $limit);
        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }

    public function getOff(Request $request)
    {
        $shopInfo = $request->input('shopInfo');
        $ids = $request->input('ids');
        $result = $this->productService->getOff($shopInfo, $ids);
        return response()->json([
            'status' => 'success',
            'data' => $result
        ]);
    }
}
