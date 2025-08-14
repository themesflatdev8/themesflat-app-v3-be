<?php

namespace App\Jobs;

use App\Facade\SystemCache;
use App\Models\BundlesModel;
use App\Models\ProductCommenditionsModel;
use App\Models\ProductModel;
use App\Models\StoreModel;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateBundleDefaultJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $storeId;
    private $accessToken;
    private $shopifyDomain;

    public function __construct($storeId, $shopifyDomain, $accessToken)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));
        $this->storeId = $storeId;
        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        return  true;
        
        $sentry = app('sentry');
        echo $this->storeId;

        try {
            $productModel = new ProductModel();
            $bundleModel = new BundlesModel();

            $products = $productModel->where('store_id', $this->storeId)->where('status', 'active')->get();

            $bundleData = [];
            foreach ($products as $product) {
                // create bundles
                $bundleData[] = [
                    'product_id' => $product->id,
                    'store_id' => $this->storeId,
                ];
            }

            $bundleModel->upsert($bundleData, 'product_id', ['store_id', 'product_id']);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
