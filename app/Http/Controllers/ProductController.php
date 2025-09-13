<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\GetProductViewRequest;
use App\Repository\DiscountRepository;
use App\Repository\ProductRepository;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    //
    protected $productRepository;
    protected $sentry;

    public function __construct(ProductRepository $productRepository)
    {
        $this->productRepository = $productRepository;
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
                'data' => $result
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
        $domain = $request->input('shopInfo')['shop'];
        /** @var DiscountRepository $discountRepository */
        $result = $this->productRepository->getTop10ProductView($domain);
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
}
