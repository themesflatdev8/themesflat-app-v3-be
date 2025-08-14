<?php

namespace App\Jobs;

use App\Services\Shopify\ShopifyApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddMetafieldCheckOffferJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $accessToken;
    private $shopifyDomain;
    private $storeId;
    private $productIds;
    public $check;

    public function __construct($storeId, $shopifyDomain, $accessToken, $productIds, $check = false)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
        $this->storeId = $storeId;
        $this->productIds = $productIds;
        $this->check = $check; // 
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $shopifyApiService = new ShopifyApiService();
        $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);

        $sentry = app('sentry');

        try {
            $dataCreateMetafield = config("fa_metafield.offer_check");

            foreach ($this->productIds as $productId) {
                if (!empty($productId)) {
                    $dataSetMetaField[] = [
                        "key" => $dataCreateMetafield['key'] . '_' . $productId,
                        "namespace" => $dataCreateMetafield['namespace'],
                        "ownerId" => "gid://shopify/Shop/" . $this->storeId,
                        "type" => $dataCreateMetafield['type'],
                        "value" => $this->check ? "true" : "false"
                    ];
                    // sleep(0.6);
                }
                dump($dataSetMetaField);
                $add = $shopifyApiService->setDataMetafieldStoreFront($dataSetMetaField);
                dump($add);
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
