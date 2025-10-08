<?php


namespace App\Repository;

use Illuminate\Support\Facades\DB;
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

    public function deleteOrder($id)
    {
        return $this->model->where('shopify_order_id', $id)->delete();
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

    public function deleteOrderItem($id)
    {
        $orderItemModel = app(OrderItemModel::class);
        return $orderItemModel->where('order_id', $id)->delete();
    }

    public function alsoBoughts($data)
    {
        $shopDomain = $data['shop_domain'];
        $variantIds = $data['variant_ids'] ?? [];
        $groupBy = $data['group_by'] ?? 'variant_id';

        // Danh sách cột select (sử dụng ANY_VALUE để tránh lỗi khi group)
        $select = [
            $groupBy,
            DB::raw('ANY_VALUE(title) AS title'),
            DB::raw('ANY_VALUE(variant_title) AS variant_title'),
            DB::raw('ANY_VALUE(sku) AS sku'),
            DB::raw('ANY_VALUE(vendor) AS vendor'),
            DB::raw('ANY_VALUE(product_type) AS product_type'),
            DB::raw('ANY_VALUE(handle) AS handle'),
            DB::raw('ANY_VALUE(image_url) AS image_url'),
            DB::raw('ANY_VALUE(price) AS price'),
            DB::raw('SUM(quantity) AS total_sold'),
        ];

        // Query chính
        $query = DB::table('domain_order_items')
            ->select($select)
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

        // Loại bỏ chính các variant đang xét
        if (!empty($variantIds)) {
            $query->whereNotIn('variant_id', $variantIds);
        }

        // Gom nhóm & sắp xếp
        return $query
            ->groupBy($groupBy)
            ->orderByDesc('total_sold')
            ->limit(15)
            ->get();
    }
}
