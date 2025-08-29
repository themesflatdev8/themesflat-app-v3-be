<?php


namespace App\Repository;

use App\Facade\SystemCache;
use App\Models\OrderItemModel;
use App\Models\OrderLogModel;
use App\Models\OrderModel;
use Carbon\Carbon;

class OrderRepository extends AbstractRepository
{
    public function model()
    {
        return OrderModel::class;
    }
    /**
     * Create a new order.
     *
     * @param array $orderData
     * @return OrderModel
     */
    public function createOrder(array $orderData): OrderModel
    {
        return $this->model->create($orderData);
    }

    public function createOrderItem(array $data)
    {
        $orderItemModel = app(OrderItemModel::class);
        $now = Carbon::now();
        $prepareData = array_map(function ($value) use ($now) {
            $value['created_at'] = $value['updated_at'] = $now;

            return $value;
        }, $data);

        $orderItemModel->insert($prepareData);
        return true;
    }

    public function createOrderLog(array $data): OrderLogModel
    {
        $orderLogModel = app(OrderLogModel::class);

        return $orderLogModel->create($data);
    }

    public function alsoBoughts($data)
    {
        $shopDomain = $data['shop_domain'];
        $variantIds = $data['variant_ids'];
        $query = DB::table('domain_order_items')
            ->select(
                'variant_id',
                'title',
                'variant_title',
                'sku',
                'vendor',
                'product_type',
                'handle',
                'image_url',
                'price',
                'tags',
                DB::raw('SUM(quantity) AS total_sold')
            )
            ->where('shop_domain', $shopDomain)
            ->whereIn('order_id', function ($q) use ($shopDomain, $variantIds) {
                $q->select('order_id')
                    ->distinct()
                    ->from('domain_order_items')
                    ->where('shop_domain', $shopDomain);

                if (!empty($variantIds)) {
                    $q->whereIn('variant_id', $variantIds);
                }
            });

        if (!empty($variantIds)) {
            $query->whereNotIn('variant_id', $variantIds);
        }

        $results = $query
            ->groupBy(
                'variant_id',
                'title',
                'variant_title',
                'sku',
                'vendor',
                'product_type',
                'handle',
                'image_url',
                'price',
                'tags'
            )
            ->orderByDesc('total_sold')
            ->limit(15)
            ->get();
        return $results;
    }
}
