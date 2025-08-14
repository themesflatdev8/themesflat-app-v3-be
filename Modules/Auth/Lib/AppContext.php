<?php

namespace Modules\Auth\Lib;

class AppContext
{
    private static $shop;

    public static function initialize($shop)
    {
        self::$shop = $shop;
    }

    public static function getShop()
    {
        return self::$shop;
    }
}
