<?php

namespace App\Jobs;

use App\Services\Shopify\ShopifyApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class StoreFrontCacheVersionJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $accessToken;
    private $shopifyDomain;
    private $storeId;
    public $type;

    public function __construct($storeId, $shopifyDomain, $accessToken, $type)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
        $this->storeId = $storeId;
        $this->type = $type;
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
            $dataCreateMetafield = config("fa_metafield." . $this->type);

            $dataSetMetaField[0] = [
                "key" => $dataCreateMetafield['key'],
                "namespace" => $dataCreateMetafield['namespace'],
                "ownerId" => "gid://shopify/Shop/" . $this->storeId,
                "type" => $dataCreateMetafield['type'],
                "value" => (string) time()
            ];
            $add = $shopifyApiService->setDataMetafieldStoreFront($dataSetMetaField);
            // dump($add);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
