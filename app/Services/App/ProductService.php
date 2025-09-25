<?php

namespace App\Services\App;

use App\Models\ProductReviewModel;
use App\Repository\OrderRepository;
use App\Repository\ProductRepository;
use App\Repository\ProductReviewRepository;
use Illuminate\Support\Facades\DB;
use App\Services\AbstractService;
use App\Services\Shopify\ShopifyApiService;
use Exception;
use Google\Service\AdExchangeBuyerII\Product;
use Google\Service\Merchant\ProductReview;

class ProductService extends AbstractService
{
    protected $sentry;
    protected $productRepository;
    protected $shopifyApiService;

    public function __construct(ProductRepository $productRepository, ShopifyApiService $shopifyApiService)
    {
        $this->sentry = app('sentry');
        $this->productRepository = $productRepository;
        $this->shopifyApiService = $shopifyApiService;
    }

    public function productTopView($shopInfo, $limit = 10)
    {
        try {
            $domain = $shopInfo['shop'];
            $result = $this->productRepository->getTop10ProductView($domain, $limit);
            if (empty($result)) {
                /** @var ShopifyApiService $shopifyApiService */
                $shopifyApiService = app(ShopifyApiService::class);
                $accessToken = $shopInfo['access_token'];

                $shopifyApiService->setShopifyHeader($domain, $accessToken);

                $result = $shopifyApiService->getProductNewest($domain, $accessToken);
                $result = collect($result)->map(function ($item) {
                    return [
                        'id'     => filter_var($item['node']['id'], FILTER_SANITIZE_NUMBER_INT),
                        'handle' => $item['node']['handle'],
                    ];
                })->all();
            }
            return $result;
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }
        return [];
    }
    public function getProductRelated($shopInfo, $idCategory)
    {
        try {
            $this->shopifyApiService->setShopifyHeader($shopInfo['shop'], $shopInfo['access_token']);

            $result = [];

            // helper: thêm product vào result cho đủ số lượng cần
            $addProducts = function (array $items, int $limit = 10) use (&$result) {
                $needed = $limit - count($result);
                if ($needed <= 0) return;
                $result = array_merge($result, array_slice($items, 0, $needed));
            };

            // 1️⃣ product by category
            $products = $this->shopifyApiService->getProductByCategory($idCategory);
            $mapped = collect($products)->map(fn($item) => [
                'id'     => filter_var($item['node']['id'], FILTER_SANITIZE_NUMBER_INT),
                'handle' => $item['node']['handle'],
            ])->all();
            $addProducts($mapped);

            // 2️⃣ product by top sell
            if (count($result) < 10) {
                /** @var OrderRepository $orderRepo */

                $orderRepo = app(OrderRepository::class);
                $data = $orderRepo->alsoBoughts([
                    'shop_domain' => $shopInfo['shop'],
                    'variants_id' => [],
                    'group_by'    => 'product_id',
                ])->toArray();

                $productIdsNeedGet = array_column(array_slice($data, 0, 10 - count($result)), 'product_id');
                $productInfo = $this->shopifyApiService->getProductsInfo($productIdsNeedGet);
                $addProducts($productInfo);
            }

            // 3️⃣ product by top view
            if (count($result) < 10) {
                $topView = $this->productTopView($shopInfo);
                $addProducts($topView);
            }

            // 4️⃣ newest product
            if (count($result) < 10) {
                $productNewest = $this->shopifyApiService->getProductNewest();
                $mappedNewest = collect($productNewest)->map(fn($item) => [
                    'id'     => filter_var($item['node']['id'], FILTER_SANITIZE_NUMBER_INT),
                    'handle' => $item['node']['handle'],
                ])->all();
                $addProducts($mappedNewest);
            }

            return $result;
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
        return [];
    }

    public function getProductRecent($shopInfo)
    {
        try {
            $this->shopifyApiService->setShopifyHeader($shopInfo['shop'], $shopInfo['access_token']);

            $result = [];
            // helper: thêm product vào result cho đủ số lượng cần
            $addProducts = function (array $items, int $limit = 10) use (&$result) {
                $needed = $limit - count($result);
                if ($needed <= 0) return;
                $result = array_merge($result, array_slice($items, 0, $needed));
            };

            // 1️⃣ product by category
            $topView = $this->productTopView($shopInfo);
            $addProducts($topView);

            if (count($result) < 10) {
                $productNewest = $this->shopifyApiService->getProductNewest();
                $mappedNewest = collect($productNewest)->map(fn($item) => [
                    'id'     => filter_var($item['node']['id'], FILTER_SANITIZE_NUMBER_INT),
                    'handle' => $item['node']['handle'],
                ])->all();
                $addProducts($mappedNewest);
            }

            return $result;
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
        return [];
    }

    public function getOff($shopInfo, $ids)
    {
        try {
            $this->shopifyApiService->setShopifyHeader($shopInfo['shop'], $shopInfo['access_token']);
            $variantIds = array_values(array_map(
                fn($id) => "gid://shopify/ProductVariant/" . trim($id),
                array_filter(explode(',', $ids))
            ));
            // Lấy thông tin biến thể sản phẩm từ Shopify
            $variants = $this->shopifyApiService->getVariantsByIds($variantIds);
            $ids = explode(',', $ids);
            // lọc theo đúng ids user request
            $filtered = array_filter($variants, function ($variant) use ($ids) {
                return in_array((string)$variant['id'], $ids, true);
            });

            $totalDiscount = 0;
            $resultData = [];
            foreach ($filtered as $variant) {
                $price          = (float)($variant['price'] ?? 0);
                $compareAtPrice = (float)($variant['compare_at_price'] ?? 0);

                $discountPercent = 0;
                if ($compareAtPrice > 0 && $compareAtPrice > $price) {
                    $discountPercent = round((($compareAtPrice - $price) / $compareAtPrice) * 100);
                }

                $totalDiscount += $discountPercent;

                $resultData[] = [
                    'variant_id'       => $variant['id'],
                    'title'            => $variant['title'],
                    'price'            => $variant['price'],
                    'total_sold'       => rand(1, 10), // giả lập
                    'discount_percent' => $discountPercent,
                ];
            }

            $averageTotalDiscountPercent = count($resultData) > 0
                ? round($totalDiscount / count($resultData))
                : 0;

            $result = [
                'products'                   => $resultData,
                'averageTotalDiscountPercent' => $averageTotalDiscountPercent,
            ];



            return $result;
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
        return [];
    }
}
