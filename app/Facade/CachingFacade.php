<?php


namespace App\Facade;


use Illuminate\Support\Facades\Facade;

class CachingFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'cachingSupport';
    }
}