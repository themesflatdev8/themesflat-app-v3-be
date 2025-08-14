<?php

namespace App\Jobs;

use App\Models\BundlesModel;
use App\Models\ProductCommenditionsModel;
use App\Models\ProductModel;
use App\Models\QuantityOfferTierModel;
use App\Services\Shopify\ShopifyApiService;
use App\Services\Shopify\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeactiveDiscountCodeJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $storeId;
    private $accessToken;
    private $shopifyDomain;
    private $discountIds;

    public function __construct($storeId, $shopifyDomain, $accessToken, $discountIds = [])
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));
        $this->storeId = $storeId;
        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
        $this->discountIds = $discountIds;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $shopifyApiService = new ShopifyService();
        $sentry = app('sentry');
        // echo $this->shopifyDomain;

        try {
            $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);
            // $offerModel = new QuantityOfferTierModel();

            // $discountIds = $offerModel->where('store_id', $this->storeId)
            //     ->whereIn('offer_id', $this->ids)
            //     ->whereNotNull('discountId')->pluck('discountId');

            if (!empty($this->discountIds)) {
                foreach ($this->discountIds as $id) {
                    $deactive = $shopifyApiService->deactiveDiscountCode($id);
                    // dump($id);
                    // dump($deactive);
                    sleep(0.7);
                }
            }
        } catch (\Exception $exception) {
            // dump($exception->getMessage());
            $sentry->captureException($exception);
        }
    }
}
