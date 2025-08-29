<?php


namespace App\Repository;

use App\Facade\SystemCache;
use App\Models\ShopifyRecentViewModel;
use App\Models\ShopModel;
use App\Models\StoreModel;
use App\Models\ViewLogModel;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProductRepository extends AbstractRepository
{
    /**
     * @return string
     */
    protected $cacheSupport;


    public function model()
    {
        return ShopModel::class;
    }

    public function shopifyRecentViews($userId, $domain)
    {
        $shopifyRecentViewsModel = app(ShopifyRecentViewModel::class);
        $result = $shopifyRecentViewsModel->where('user_id', $userId)
            ->where('domain_name', $domain)
            ->orderByDesc('viewed_at')
            ->limit(10)
            ->get();
        return $result;
    }

    public function getProductViews($productId, $domain, $data)
    {
        $now = Carbon::now();
        ShopifyRecentViewModel::updateOrCreate(
            [
                'product_id'  => $productId,
                'user_id'     => $data['user_id'],
                'domain_name' => $domain,
            ],
            [
                'handle'    => $data['handle'],
                'viewed_at' => $now,
            ]
        );

        // 6. Lưu bảng view logs (insert mới)
        ViewLogModel::create([
            'product_id'  => $productId,
            'handle'      => $data['handle'],
            'user_id'     => $data['user_id'],
            'domain_name' => $domain,
            'viewed_at'   => $now,
            'user_agent'  => $data['user_agent'],
            'referer'     => $data['referer'],
        ]);
        // 7. Đếm tổng lượt xem theo domain
        $count = ViewLogModel::where('product_id', $productId)
            ->where('domain_name', $domain)
            ->count();
        return $count;
    }
}
