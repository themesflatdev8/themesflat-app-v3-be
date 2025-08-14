<?php

namespace App\Services\App;

use App\Services\Cache\BaseCache;
use Illuminate\Support\Facades\Redis;

class SystemCacheService extends BaseCache
{

    public function __construct()
    {
        $this->redis = Redis::connection('default');
    }


    public function mixCachePaginate($keySet, $keyHash, $values)
    {
        foreach ($values as  $key => $val) {
            if (is_array($val)) {
                $val = json_encode($val);
            }
            $this->addItemSortedSet($keySet, [$key => strtotime("now")]);
            $this->addItemToHash($keyHash, $key, $val, true);
        }
    }
}
