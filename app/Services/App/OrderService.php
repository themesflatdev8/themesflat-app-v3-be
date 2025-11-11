<?php

namespace App\Services\App;

use App\Jobs\DeactiveDiscountJob;
use App\Models\BundlesModel;
use App\Models\ShopModel;
use App\Models\SoldRecordModel;
use App\Models\StoreModel;
use App\Models\StoreTestModel;
use App\Repository\OrderRepository;
use App\Repository\ShopRepository;
use App\Services\Shopify\ShopifyApiService;
use App\Services\AbstractService;
use Carbon\Carbon;
use Exception;

class OrderService extends AbstractService
{
    protected $sentry;
    protected $orderRepository;

    public function __construct(OrderRepository $orderRepository)
    {
        $this->sentry = app('sentry');
        $this->orderRepository = $orderRepository;
    }

    public function createOrder(string $domain, array $orderData)
    {
        try {
            // Chuẩn bị dữ liệu order
            $dataOrderSave = [
                'domain_name' => $domain,
                'shopify_order_id' => $orderData['id'],
                'email' => $orderData['email'] ?? null,
                'total_price' => (float) ($orderData['total_price'] ?? 0),
                'subtotal_price' => (float) ($orderData['subtotal_price'] ?? 0),
                'total_discounts' => (float) ($orderData['total_discounts'] ?? 0),
                'discount_codes' => !empty($orderData['discount_codes']) ? json_encode($orderData['discount_codes']) : null,
                'currency' => $orderData['currency'] ?? null,
                'financial_status' => $orderData['financial_status'] ?? null,
                'fulfillment_status' => $orderData['fulfillment_status'] ?? null,
                'customer_id' => $orderData['customer']['default_address']['customer_id'] ?? null,
                'order_data' => json_encode($orderData),
            ];
            $this->orderRepository->createOrder($dataOrderSave);

            $dataOrderItem = [];
            $dataSold = [];
            $productIds = [];
            $now = now();

            foreach ($orderData['line_items'] as $item) {
                if (empty($item['product_id'])) {
                    continue;
                }
                $quantity = (int) ($item['quantity'] ?? 0);
                $price = (float) ($item['price'] ?? 0);
                $totalDiscount = (float) ($item['total_discount'] ?? 0);
                $linePrice = $price * $quantity;
                $finalLinePrice = $linePrice - $totalDiscount;

                $dataOrderItem[] = [
                    'shop_domain' => $domain,
                    'order_id' => $orderData['id'],
                    'product_id' => $item['product_id'] ?? null,
                    'variant_id' => $item['variant_id'] ?? null,
                    'title' => $item['title'] ?? null,
                    'variant_title' => $item['variant_title'] ?? null,
                    'sku' => $item['sku'] ?? null,
                    'handle' => $item['handle'] ?? null,
                    'vendor' => $item['vendor'] ?? null,
                    'product_type' => $item['product_type'] ?? null,
                    'image_url' => $item['image_url'] ?? null,
                    'quantity' => $quantity,
                    'price' => $price,
                    'total_discount' => $totalDiscount,
                    'line_price' => $linePrice,
                    'final_line_price' => $finalLinePrice,
                    'line_item_data' => json_encode($item),
                ];

                $dataSold[] = [
                    'domain_name' => $domain,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => '', // update sau
                    'product_price' => $price,
                    'price_coupon' => $totalDiscount,
                    'product_unit' => $quantity,
                    'total' => $linePrice - $totalDiscount,
                    'order_id' => $orderData['id'],
                    'order_date' => Carbon::parse($orderData['created_at'])->toDateTimeString(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];

                if (isset($item['product_id'])) {
                    $productIds[] = $item['product_id'];
                }
            }

            $dataOrderLog = [
                'shopify_order_id' => $orderData['id'],
                'domain_name' => $domain,
                'action_type' => 'create',
                'log_data' => json_encode($orderData),
            ];

            // Cập nhật variant IDs
            $dataOrderItem = $this->getVariantIds($domain, $dataOrderItem);

            // Lấy thông tin sản phẩm
            $productInfo = $this->getProductInfo($domain, $productIds);
            foreach ($productInfo as $product) {
                foreach ($dataSold as $key => $sold) {
                    if ($sold['product_id'] == $product['id']) {
                        $dataSold[$key]['product_name'] = $product['title'] ?? 'text';
                    }
                }
            }
            // Lưu dữ liệu
            $this->orderRepository->createOrderItem($dataOrderItem);
            $this->orderRepository->createOrderLog($dataOrderLog);
            if (!empty($dataSold)) {
                SoldRecordModel::insert($dataSold);
            }
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
            throw $exception;
        }
    }


    public function getProductInfo(string $domain, array $productIds): array
    {
        $shopInfo = ShopModel::where("shop", $domain)->first();
        /** @var ShopifyApiService $shopifyApiService */
        $shopifyApiService = app(ShopifyApiService::class);

        $shopifyApiService->setShopifyHeader($domain, $shopInfo['access_token']);
        $productInfo = $shopifyApiService->getProductsInfo($productIds);
        return $productInfo;
    }

    public function deleteOrder($orderId)
    {
        try {
            $this->orderRepository->deleteOrder($orderId);
            $this->orderRepository->deleteOrderItem($orderId);
            $dataOrderLog = [
                'shopify_order_id' => $orderId,
                'action_type' => 'delete',
                'log_data' => json_encode(['order_id' => $orderId]),
            ];
            $this->orderRepository->createOrderLog($dataOrderLog);
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
            throw $exception; // Re-throw the exception after logging
        }
        return true;
    }
    public function alsoBoughts($data)
    {
        try {
            $variantIdsRaw = $data['variant_ids'] ?? [];
            if (!is_array($variantIdsRaw)) {
                $data['variant_ids'] = array_filter(array_map('intval', explode(',', $variantIdsRaw)));
            } else {
                $data['variant_ids'] = array_filter(array_map('intval', $variantIdsRaw));
            }

            $result = $this->orderRepository->alsoBoughts($data);
            return $result;
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return [];
    }

    private function getVariantIds(string $domain, array $data): array
    {
        $variantIds = array_map(fn($id) => "gid://shopify/ProductVariant/{$id}", array_column($data, 'variant_id'));

        $shopInfo = ShopModel::where("shop", $domain)->first();
        /** @var ShopifyApiService $shopifyApiService */
        $shopifyApiService = app(ShopifyApiService::class);

        $shopifyApiService->setShopifyHeader($domain, $shopInfo['access_token']);
        $variantInfo = $shopifyApiService->getProductVariantsByIds($variantIds);
        if (empty($variantInfo)) {
            return [];
        }
        $variantMap = collect($variantInfo)
            ->mapWithKeys(function ($v) {
                $id = (int) str_replace('gid://shopify/ProductVariant/', '', $v['id']);
                return [$id => [
                    'handle' => $v['product']['handle'] ?? null,
                    'image_url'  => $v['image']['featuredImage'] ?? $v['product']['featuredImage']['originalSrc'] ?? null,
                ]];
            });
        // Cập nhật $data
        foreach ($data as $key => $item) {
            if (isset($variantMap[$item['variant_id']])) {
                $data[$key] = array_merge($item, $variantMap[$item['variant_id']]);
            }
        }
        return $data;
    }
}
