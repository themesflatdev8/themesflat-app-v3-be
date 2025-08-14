<?php

namespace App\Jobs;

use App\Models\StoreModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ShopUpdateWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $shopifyDomain;
    private $data;

    public function __construct($shopifyDomain, $data)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));
        $this->shopifyDomain = $shopifyDomain;
        $this->data = $data;
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
            $store = StoreModel::where('shopify_domain', $this->shopifyDomain)->first();

            $hasChange = false;
            $res = $this->data;

            if ($store->domain != $res['domain']) {
                $hasChange = true;
            }

            if ($store->name != $res['name']) {
                $hasChange = true;
            }

            if ($store->shopify_plan != $res['plan_name']) {
                $hasChange = true;
            }

            if ($store->owner != $res['shop_owner']) {
                $hasChange = true;
            }

            if ($store->email != $res['email']) {
                $hasChange = true;
            }

            if ($store->phone != $res['phone']) {
                $hasChange = true;
            }
            if ($store->timezone != $res['iana_timezone']) {
                $hasChange = true;
            }
            if ($store->country != $res['country']) {
                $hasChange = true;
            }
            if ($store->primary_locale != $res['primary_locale']) {
                $hasChange = true;
            }
            if ($store->currency != $res['currency']) {
                $hasChange = true;
            }

            if ($hasChange) {
                echo 'has change';
                $storeData = [
                    'domain' => $res['domain'],
                    'name' => $res['name'],
                    'shopify_plan' => $res['plan_name'],
                    'owner' => $res['shop_owner'],
                    'email' => $res['email'],
                    'phone' => $res['phone'],
                    'timezone' => $res['iana_timezone'],
                    'country' => $res['country'],
                    'primary_locale' => $res['primary_locale'],
                    'currency' => $res['currency'],
                ];

                $store->update($storeData);
            }
        } catch (\Exception $exception) {
            // dump($exception->getMessage());
            $sentry->captureException($exception);
        }
    }
}
