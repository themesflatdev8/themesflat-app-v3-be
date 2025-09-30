<?php


namespace App\Repository;

use App\Facade\SystemCache;
use App\Models\DiscountModel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
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

    public function getDiscounts(int $shopId, array $filter = [])
    {

        $query = $this->model->where('shop_id', $shopId);
        $limit = data_get($filter, 'limit', 10);
        if (!empty($filter['status'])) {
            $query->where('status', $filter['status']);
        }

        if (!empty($filter['type'])) {
            $query->where('type', $filter['type']);
        }
        if (!empty($filter['keyword'])) {
            $keyword = $filter['keyword'];
            $query->where(function ($q) use ($keyword) {
                $q->where('title', 'ILIKE', "%{$keyword}%")
                    ->orWhereRaw("codes::text ILIKE ?", ["%{$keyword}%"]);
            });
        }
        $discounts = $query->paginate($limit);

        return $discounts;
    }
    public function getFreeShippingDiscounts($domainName)
    {
        $now = Carbon::now();
        return $this->model->select('discount_value', 'minimum_requirement', 'minimum_quantity', 'codes')
            ->where('domain_name', $domainName)
            ->where('type', 'DiscountCodeFreeShipping')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->get();
    }

    public function getFreeShip($domain, $country = null)
    {
        $now = Carbon::now();
        $result = DB::table('domain_discounts')
            ->select('discount_value', 'minimum_subtotal', 'minimum_quantity', 'codes', 'countries')
            ->where('domain_name', $domain)
            ->where('type', 'DiscountCodeFreeShipping')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now);
        if (!is_null($country)) {
            $result = $result->where(function ($q) use ($country) {
                $q->whereJsonContains('countries', $country) // nếu countries là mảng JSON
                    ->orWhereJsonContains('countries', 'all'); // nếu countries = "all"
            });
        }

        $result = $result->get();
        $result = $result ? $result->toArray() : [];
        return $result;
    }

    public function checkDiscount($domain, $code)
    {
        $now = Carbon::now();
        return DB::table('domain_discounts')
            ->select('discount_value', 'minimum_requirement', 'minimum_quantity', 'codes')
            ->whereJsonContains('codes', $code)
            ->where('domain_name', $domain)
            ->where('type', 'DiscountCodeFreeShipping')
            ->where('starts_at', '<=', $now)
            ->where('ends_at', '>=', $now)
            ->first();
    }
}
