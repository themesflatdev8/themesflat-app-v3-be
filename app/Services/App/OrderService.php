<?php

namespace App\Services\App;

use App\Jobs\DeactiveDiscountJob;
use App\Models\BundlesModel;
use App\Models\ShopModel;
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
            $dataOrderSave = [
                'domain_name' => $domain,
                'shopify_order_id' => $orderData['id'],
                'email'  => @$orderData['email'],
                'total_price' => (float) $orderData['total_price'] ?? 0,
                'subtotal_price' => (float) $orderData['subtotal_price'] ?? 0,
                'total_discounts' => (float) $orderData['total_discounts'] ?? 0,
                'discount_codes' => !empty($orderData['discount_codes']) ? json_encode($orderData['discount_codes']) : null,
                'currency' => $orderData['currency'],
                'financial_status' => $orderData['financial_status'],
                'fulfillment_status' => $orderData['fulfillment_status'],
                'customer_id' => @$orderData['customer']['default_address']['customer_id'],
                'order_data' => json_encode($orderData),
            ];
            $this->orderRepository->createOrder($dataOrderSave);
            $listItem = $orderData['line_items'];
            $dataOrderItem = [];

            foreach ($listItem as $item) {
                $linePrice = (float) $item['price'] * (int) $item['quantity'];
                $finalLinePrice = $linePrice - $item['total_discount'];
                $dataOrderItem[] = [
                    'shop_domain' => $domain,
                    'order_id' => $orderData['id'],
                    'product_id' => @$item['product_id'],
                    'variant_id' => @$item['variant_id'],
                    'title' =>  @$item['title'],
                    'variant_title' => @$item['variant_title'],
                    'sku' => @$item['sku'],
                    'handle' => @$item['handle'],
                    'vendor' => @$item['vendor'],
                    'product_type' => @$item['product_type'],
                    'image_url' => @$item['image_url'],
                    'quantity' => @$item['quantity'] ?? 0,
                    'price' => (float) $item['price'],
                    'total_discount' => $item['total_discount'],
                    'line_price' => $linePrice,
                    'final_line_price' => $finalLinePrice,
                    'line_item_data' => json_encode($item),
                ];
            }

            $dataOrderLog = [
                'shopify_order_id' => $orderData['id'],
                'domain_name' => $domain,
                'action_type' => 'create',
                'log_data' => json_encode($orderData),
            ];
            $dataOrderItem = $this->getVariantIds($domain, $dataOrderItem);
            //save order
            $this->orderRepository->createOrderItem($dataOrderItem);
            $this->orderRepository->createOrderLog($dataOrderLog);
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
            throw $exception; // Re-throw the exception after logging
        }
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
