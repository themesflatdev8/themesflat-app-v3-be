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

class GenerateBundleJob implements ShouldQueue
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
        
        $shopifyApiService = new ShopifyApiService();
        $sentry = app('sentry');
        echo $this->storeId;

        try {
            $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);
            $productModel = new ProductModel();
            $bundleModel = new BundlesModel();
            $commendationModel = new ProductCommenditionsModel();
            $store = StoreModel::where('store_id', $this->storeId)->first();
            $checkLoyalty = checkLoyalty($store);

            $products = $productModel->where('store_id', $this->storeId)->where('status', 'active')->get();
            // $bundleModel->where('store_id', $this->storeId)->delete();
            // $commendationModel->where('store_id', $this->storeId)->delete();

            $bundleData = [];
            $commendationData = [];
            foreach ($products as $product) {
                // create bundles
                $bundleData[] = [
                    'product_id' => $product->id,
                    'store_id' => $this->storeId,
                    'status' => 0,
                    // 'created_at' => date('Y-m-d H:i:s'),
                    // 'updated_at' => date('Y-m-d H:i:s')
                ];
            }

            $bundleModel->upsert($bundleData, 'product_id', ['store_id', 'product_id']);

            // dump($checkLoyalty);
            // nếu loyalty thì mới cho phép AI gen
            if ($checkLoyalty['loyalty']) {
                $bundles = $bundleModel->where('store_id', $this->storeId)->get();
                // dump($products);
                foreach ($bundles as $bundle) {
                    // dump('product : ' . $bundle->product_id . ' - ');
                    $random = 5;
                    $totalProduct = collect($products)->whereNotIn('id', $bundle->product_id)->count();
                    if ($totalProduct < 5) {
                        $random = $totalProduct;
                    }

                    $productCommendations = collect($products)->whereNotIn('id', $bundle->product_id)->random($random);
                    if (!empty($productCommendations)) {
                        foreach ($productCommendations as $re) {
                            $commendationData[] = [
                                'product_id' => $re->id,
                                'bundle_id' => $bundle->id,
                                'store_id' => $this->storeId,
                                'created_at' => date('Y-m-d H:i:s'),
                                'updated_at' => date('Y-m-d H:i:s')
                            ];
                        }
                    }
                    // dump($productCommendations);
                }

                $commendationModel->upsert($commendationData, ['product_id', 'bundle_id', 'store_id'], ['updated_at']);
            }

            SystemCache::remove('getBundleStorefront_' . $this->shopifyDomain);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
