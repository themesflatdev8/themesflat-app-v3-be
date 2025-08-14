<?php

namespace App\Facade;

use App\Services\App\SystemCacheService;
use Illuminate\Support\Facades\Facade;

class SystemCache  extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return SystemCacheService::class;
    }
}
