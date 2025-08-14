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

class SyncShopifyProductsJobV2 implements ShouldQueue
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
                if (empty($productsResult)) {
                    echo "sync done, empty resuslt";
                    break;
                }
                if (empty($productsResult->pageInfo) or empty($productsResult->pageInfo->hasNextPage)) {
                    echo "sync done";
                    SaveSyncProductsJobV2::dispatch($storeId, $productsResult->edges, $this->resync, true);
                    break;
                } else {
                    SaveSyncProductsJobV2::dispatch($storeId, $productsResult->edges, $this->resync);
                }
                $pageInfo = $productsResult->pageInfo;
                // dump($pageInfo);

                if ($i >= 20) {
                    SyncShopifyProductsJobV2::dispatch($storeId, $domain, $token, $pageInfo, $this->resync);
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
        $after = '';
        if (!empty($pageInfo)) {
            // return null;
            $after = ', after : "' . $pageInfo->endCursor . '"';
        }
        // dump($after);
        $shopifyService = app(ShopifyApiService::class);
        $shopifyService->setShopifyHeader($domain, $token);
        $graphqlParam['query'] = '
                {
                    products(first: 5 ' . $after . ') {
    edges {
      node {
        id
        title
        handle
        tracksInventory
        requiresSellingPlan
        featuredMedia{
            # id
            preview{
                image{
                    url
                }
            }
        }
        
        status
        createdAt
        variants(first:50){
            edges{
                node{
                    id
                    title
                    createdAt
                    price
                    compareAtPrice
                    inventoryQuantity
                    selectedOptions{
                        name
                        value
                    }
                    image {
                        url
                    }
                }
            }
        }
        options(first:20){
            id
            name
            values
        }
      }
      cursor
    }
    pageInfo {
        endCursor
      hasNextPage
    }
  }
                }
            ';

        $response = $shopifyService->post('graphql.json', $graphqlParam);
        // dump($response);
        $availablePoint =  @$response->extensions->cost->throttleStatus->currentlyAvailable;
        if (!empty($availablePoint) && $availablePoint < 300) {
            sleep(1);
        }
        $listResource = @$response->data->products;
        if (!empty($listResource)) {
            return $listResource;
        }
        return null;
    }
}
