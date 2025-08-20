<?php


namespace App\Repository;

use App\Facade\SystemCache;
use App\Models\DiscountModel;
use App\Models\ShopModel;
use Illuminate\Support\Facades\Cache;

class DiscountRepository extends AbstractRepository
{
    /**
     * @return string
     */
    protected $cacheSupport;


    public function model()
    {
        return DiscountModel::class;
    }


    /**
     * @param int $shopId
     * @return mixed
     */
    public function detail(int $discountId, $force = false)
    {
        $discountInfo = $this->model->where('domain_discount_id', $discountId)->first();

        return $discountInfo;
    }

    /**
     * @param int $shopId
     * @return mixed
     */
    public function getByShopId(int $shopId)
    {
        $discounts = $this->model->where('shop_id', $shopId)->get();

        return $discounts;
    }
}
