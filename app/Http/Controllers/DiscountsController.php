<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\Sync\SyncDiscountJob;
use App\Repository\DiscountRepository;
use Illuminate\Http\Request;

class DiscountsController extends Controller
{
    //
    public $sentry;
    public $discountRepository;


    public function __construct()
    {
        $this->discountRepository = app(DiscountRepository::class);
        $this->sentry = app('sentry');
    }

    public function sync(Request $request)
    {
        $data = $request->all();
        $shopInfo = data_get($data, 'shopInfo', []);
        $key = config('tf_cache.sync.sync_discount') . $shopInfo->shop_id;
        $checkSync = SystemCache::checkExistItemSet($key, config('tf_resource.discount'));
        if ($checkSync) {
            // return response(['message' => 'Syncing']);
        }
        SystemCache::addItemSet($key, config('tf_resource.discount'), 60 * 60 * 24 * 2);
        dispatch(new SyncDiscountJob($shopInfo->shop_id, $shopInfo->shop, $shopInfo->access_token, true, 250));
        return response(['message' => 'Sync successful']);
    }
}
