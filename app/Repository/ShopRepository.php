<?php


namespace App\Repository;

use App\Facade\SystemCache;
use App\Models\ShopModel;
use App\Models\StoreModel;
use Illuminate\Support\Facades\Cache;

class ShopRepository extends AbstractRepository
{
    /**
     * @return string
     */
    protected $cacheSupport;


    public function model()
    {
        return ShopModel::class;
    }


    /**
     * @param int $shopId
     * @return mixed
     */
    public function detail(int $shopId, $force = false)
    {
        $shopInfo = $this->model()::find($shopId)->toArray();

        return $shopInfo;
    }

    /**
     * @param string $domain
     * @return mixed
     */
    public function detailByShopifyDomain(string $domain)
    {
        $shopInfo = ShopModel::where("shop", $domain)
            ->first();
        $shopInfo = $shopInfo ? $shopInfo->toArray() : [];

        return $shopInfo;
    }


    public function getPlan($shopInfo)
    {
        $planInfo = [];
        $plans = config('fa_plans');
        $appPlan = $shopInfo['app_plan'];
        if (array_key_exists($appPlan, $plans)) {
            $planInfo = $plans[$appPlan];
        }

        return $planInfo;
    }
}
