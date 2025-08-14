<?php

namespace App\Jobs;

use App\Models\CollectionModel;
use App\Models\ProductCommenditionsModel;
use App\Models\ProductModel;
use App\Models\ProductOptionModel;
use App\Models\ProductVariantModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeleteProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $storeId;

    public function __construct($storeId)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));
        $this->storeId = $storeId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // $sentry = app('sentry');
        $storeId =  $this->storeId;
        try {
            ProductModel::where('store_id', $storeId)->delete();
            ProductVariantModel::where('store_id', $storeId)->delete();
            ProductOptionModel::where('store_id', $storeId)->delete();

            CollectionModel::where('store_id', $storeId)->delete();
        } catch (\Exception $exception) {
            dump($exception->getMessage());
            // $sentry->captureException($exception);
        }
    }
}
