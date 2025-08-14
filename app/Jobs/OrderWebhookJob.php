<?php

namespace App\Jobs;

use App\Facade\SystemCache;
use App\Models\LogModel;
use App\Models\StoreModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class OrderWebhookJob implements ShouldQueue
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
            $order = $this->data;

            SystemCache::remove('getBundleStorefront_' . $this->shopifyDomain);
            dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));

            if (!empty($store->billing_id)) {
                $planInfo = config('fa_plans')[$store->app_plan];
                dispatch(new UsageChargeOrder($store->shopify_domain, $store->access_token, $store->billing_id, $planInfo['used_charge']));
            }
            // $log = LogModel::where('store_id', $store->store_id)->first();
            // $totalOrder = empty($log->total_order) ? 1 : ($log->total_order + 1);
            // $priceOrder = !empty($order['total_price']) ? $order['total_price'] : 0;
            // $totalPrice = empty($log->total_revenue) ? $priceOrder : ($log->total_revenue + $priceOrder);

            // LogModel::updateOrCreate(
            //     ['store_id' => $store->store_id],
            //     [
            //         'total_order' => $totalOrder,
            //         'total_revenue' => $totalPrice
            //     ]
            // );
        } catch (\Exception $exception) {
            // dump($exception->getMessage());
            $sentry->captureException($exception);
        }
    }
}
