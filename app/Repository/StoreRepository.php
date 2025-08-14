<?php


namespace App\Repository;

use App\Facade\SystemCache;
use App\Models\StoreModel;
use Illuminate\Support\Facades\Cache;

class StoreRepository extends AbstractRepository
{
    /**
     * @return string
     */
    protected $cacheSupport;


    public function model()
    {
        return StoreModel::class;
    }


    /**
     * @param int $storeId
     * @return mixed
     */
    public function detail(int $storeId, $force = false)
    {
        $storeInfo = $this->model()::find($storeId)->toArray();

        return $storeInfo;
    }

    /**
     * @param string $domain
     * @return mixed
     */
    public function detailByShopifyDomain(string $domain)
    {
        $storeInfo = StoreModel::where("shopify_domain", $domain)
            ->first()
            ->toArray();

        return $storeInfo;
    }


    public function getPlan($storeInfo)
    {
        $planInfo = [];
        $plans = config('fa_plans');
        $appPlan = $storeInfo['app_plan'];
        if (array_key_exists($appPlan, $plans)) {
            $planInfo = $plans[$appPlan];
        }

        return $planInfo;
    }
}
