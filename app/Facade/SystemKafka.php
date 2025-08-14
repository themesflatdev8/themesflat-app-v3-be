<?php
namespace App\Facade;

use App\Services\App\KafkaService;
use Illuminate\Support\Facades\Facade;

class SystemKafka  extends Facade {
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return KafkaService::class;

    }
}
