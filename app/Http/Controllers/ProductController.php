<?php

namespace App\Http\Controllers;

use App\Http\Requests\Admin\GetProductViewRequest;
use App\Repository\DiscountRepository;
use App\Repository\ProductRepository;
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
            $shopInfo = $request->input('shopInfo');
            $domain = $shopInfo->shop;

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
            $productId = $request->input('product_id');;
            $shopInfo = $request->input('shopInfo');
            $domain = $shopInfo->shop;
            // 3. Lấy thêm thông tin trình duyệt và nguồn
            $data = [
                'user_agent' => $request->header('User-Agent', ''),
                'referer' => $request->header('Referer', ''),
                'product_id' =>  $request->get('user_id'),
                'handle' =>  $request->get('handle'),
            ];

            $result = $this->productRepository->getProductViews($productId, $domain, $data);
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
        /** @var DiscountRepository $discountRepository */
        $domainName = $request->input('shopInfo')->shop;
        $discountRepository = app(DiscountRepository::class);
        $result = $discountRepository->getFreeShippingDiscounts($domainName);
        if (!empty($result)) {
            return response()->json([
                'status' => 'success',
                'data'   => [],
                'note'   => 'No active free shipping discount codes found.'
            ], 200);
        }
        $parsed = $result->map(function ($item) {
            $minimum_quantity    = $item->minimum_quantity;
            $minimum_requirement = $item->minimum_requirement;

            $minimum_value = null;
            if (!is_null($minimum_quantity)) {
                $minimum_value = (int) $minimum_quantity;
            } elseif (!is_null($minimum_requirement)) {
                $minimum_value = (float) $minimum_requirement;
            }

            return [
                'discount_value'      => (float) $item->discount_value,
                'minimum_requirement' => $minimum_requirement ?? null,
                'minimum_quantity'    => $minimum_quantity ?? null,
                'minimum_value'       => $minimum_value,
                'codes'               => $item->codes,
            ];
        });

        return response()->json([
            'status' => 'success',
            'data' => $parsed
        ]);
    }
}
