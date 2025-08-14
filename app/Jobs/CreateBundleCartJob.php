<?php

namespace App\Jobs;

use App\Facade\SystemCache;
use App\Models\BundlesModel;
use App\Models\StoreModel;
use App\Services\MailService;
use Google\Service\AndroidPublisher\Bundle;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class CreateBundleCartJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    private $storeId;
    private $shopifyDomain;
    private $accesstoken;

    public function __construct($storeId, $shopifyDomain, $accesstoken)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

        $this->storeId = $storeId;
        $this->shopifyDomain = $shopifyDomain;
        $this->accesstoken = $accesstoken;
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
            $check  = BundlesModel::where('store_id', $this->storeId)->where('pageType', 'cart')->first();
            if (empty($check)) {
                $data = [
                    'store_id' => $this->storeId,
                    'type' => 'general',
                    'pageType' => 'cart',
                    'mode' =>  'ai',
                    'name' => 'Cart page bundle',
                    'status' => 1,
                ];
                BundlesModel::create($data);

                // echo 11111;

                SystemCache::remove('getBundleStorefront_' . $this->shopifyDomain);
                dispatch(new StoreFrontCacheVersionJob($this->storeId, $this->shopifyDomain, $this->accesstoken, 'bundle'));
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
