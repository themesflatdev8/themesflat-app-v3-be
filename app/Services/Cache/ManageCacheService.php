<?php


namespace App\Services\Cache;


use App\Services\AbstractService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ManageCacheService extends AbstractService
{
    /**
     * @param array $attribute
     * @return $this
     */
    public function updateCacheBackend(array $attribute)
    {
        try {
            $storeId = Arr::get($attribute, 'store_id');
            $tags    = env('SWITCH_SYNC_CACHE_BE',false) ? 'redis_be' :  'redis';
            $keyCache = config('fa_cache_key_backend.store.keys.detail_array').$storeId;
            Cache::store($tags)->forget($keyCache);
            $this->setMessage(config('fa_messages.success.common'));
            $this->setStatus(true);
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $this->setStatus(false);
        }
        return $this;
    }

}