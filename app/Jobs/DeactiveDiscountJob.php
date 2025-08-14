<?php

namespace App\Jobs;

use App\Models\BundlesModel;
use App\Models\ProductCommenditionsModel;
use App\Models\ProductModel;
use App\Services\Shopify\ShopifyApiService;
use App\Services\Shopify\ShopifyService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class DeactiveDiscountJob implements ShouldQueue
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
        $shopifyApiService = new ShopifyService();
        $sentry = app('sentry');
        // echo $this->shopifyDomain;
        // echo $this->accessToken;

        try {
            $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);
            $bundleModel = new BundlesModel();

            $discountIds = $bundleModel->where('store_id', $this->storeId)
                ->whereNotNull('discountId')->pluck('discountId');

            if (!empty($discountIds)) {
                foreach ($discountIds as $id) {
                    $deactive = $shopifyApiService->deactiveDiscount($id);
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
