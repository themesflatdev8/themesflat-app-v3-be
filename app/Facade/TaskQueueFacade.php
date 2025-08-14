<?php


namespace App\Facade;


use Illuminate\Support\Facades\Facade;

class TaskQueueFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'taskQueue';
    }
}