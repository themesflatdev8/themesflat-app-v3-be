<?php

namespace App\Jobs\Sync;

use App\Facade\SystemCache;
use App\Models\ProductModel;
use App\Models\ProductOptionModel;
use App\Models\ProductVariantModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Shopify\ShopifyApiService;

class SyncShopifyProductsJob //implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;
    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 1200;
    private $storeId;
    private $accessToken;
    private $shopifyDomain;
    private $isFirstPage;
    /**
     * @var
     */
    private $limitApi = 50;
    /**
     * api 2019-07 : https://help.shopify.com/en/api/guides/paginated-rest-results
     */
    private $pageInfo;
    private $resync;

    public function __construct($storeId, $shopifyDomain, $accessToken, $pageInfo = '',  $resync = false)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));
        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
        $this->storeId = $storeId;
        $this->pageInfo = $pageInfo;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sentry = app('sentry');
        try {
            $pageInfo = $this->pageInfo;
            $domain = $this->shopifyDomain;
            $token = $this->accessToken;
            $storeId = $this->storeId;
            if (empty($pageInfo)) {
                $product = ProductModel::where('store_id', $storeId)->first();
                if (!empty($product)) {
                    $this->resync = true;
                }

                ProductVariantModel::where('store_id', $storeId)->delete();
                ProductOptionModel::where('store_id', $storeId)->delete();
            }

            echo $storeId . ' - ';

            $i = 0;
            while (true) {
                $i++;
                $productsResult = $this->getProductsApi($domain, $token, $pageInfo);
                if ($productsResult === false) {
                    echo "get product error" . "\n";
                    return;
                }
                $data = $productsResult['products'];
                if (empty($data)) {
                    echo "sync done, empty resuslt";
                    break;
                }
                if (empty($productsResult['page_info'])) {
                    echo "sync done";
                    // SyncShopifyMetaProductsJob::dispatch($domain);
                    // SystemCache::remove("store_product_paginate_" . $storeId);
                    // SystemCache::remove("store_product_paginate_data_" . $storeId);
                    SaveSyncProductsJob::dispatch($storeId, $data, $this->resync, true);
                    break;
                } else {
                    SaveSyncProductsJob::dispatch($storeId, $data, $this->resync);
                }
                $pageInfo = $productsResult['page_info'];
                echo $pageInfo;

                if ($i >= 20) {
                    SyncShopifyProductsJob::dispatch($storeId, $domain, $token, $pageInfo, $this->resync);
                    break;
                }
                sleep(0.5);
            }

            SystemCache::remove('getBundleStorefront_' . $this->shopifyDomain);
        } catch (\Exception $exception) {
            $sentry->captureException($exception);
            echo ($exception->getMessage());
        }
    }

    private function getProductsApi($domain, $token, $pageInfo)
    {
        $shopifyService = app(ShopifyApiService::class);
        $shopifyService->setShopifyHeader($domain, $token);
        $fields = [
            'id',
            'title',
            'image',
            'status',
            'handle',
            'options',
            'variants',
            'images',
            'created_at'
        ];
        $products = $shopifyService->shopifyApiGetProducts(
            $fields,
            ['page_info' => $pageInfo],
            $this->limitApi
        );
        return $products;
    }
}
