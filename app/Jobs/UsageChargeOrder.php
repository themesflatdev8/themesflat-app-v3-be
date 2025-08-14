<?php

namespace App\Jobs;

use App\Services\Shopify\ShopifyApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class UsageChargeOrder implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $accessToken;
    private $shopifyDomain;
    public $billingId;
    public $price;

    public function __construct($shopifyDomain, $accessToken, $billingId, $price = 0.1)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
        $this->billingId = $billingId;
        $this->price = $price;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $shopifyApiService = new ShopifyApiService();
        $sentry = app('sentry');
        if (empty($this->price)) {
            return true;
        }

        try {
            // echo $this->shopifyDomain;
            if (!empty($this->billingId)) {
                $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);

                $charge = $shopifyApiService->post('recurring_application_charges/' . $this->billingId . '/usage_charges.json', [
                    'usage_charge' => [
                        'description' => 'Fether usage charge for 1 order',
                        'price' => $this->price
                    ]
                ]);
                // dump([
                //     'billing_id' => $this->billingId,
                //     'store' => $this->shopifyDomain
                // ]);
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
