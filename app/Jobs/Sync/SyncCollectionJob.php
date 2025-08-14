<?php

namespace App\Jobs\Sync;

use App\Models\CollectionModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Services\Shopify\ShopifyApiService;

class SyncCollectionJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

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
    private $pageInfo;
    private $type;

    public function __construct($storeId, $shopifyDomain, $accessToken, $type, $pageInfo = '')
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

        $this->storeId = $storeId;
        $this->accessToken = $accessToken;
        $this->shopifyDomain = $shopifyDomain;
        $this->type = $type;
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
            //delete
            CollectionModel::where('store_id', $this->storeId)->where('type', $this->type)->delete();

            $shopifyApiService = app(ShopifyApiService::class);
            $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);
            $dataJson = [
                'limit' => 250,
            ];
            if (!empty($this->pageInfo)) {
                $dataJson['page_info'] = $this->pageInfo;
            }
            $data = $shopifyApiService->getWithPageInfo($this->type . '.json', $dataJson);
            $pageInfo = $data['page_info'];
            $listData = $data['data'][$this->type];
            if (!empty($listData)) {
                $dataSave = [];
                foreach ($listData as $dt) {
                    if (!is_array($dt)) {
                        $dt = (array) $dt;
                    }
                    $c = [
                        'id' => $dt['id'],
                        'store_id' => $this->storeId,
                        'title' => $dt['title'],
                        'handle' => $dt['handle'],
                        'type' => $this->type,
                        // 'products_count' => $dt['products_count'],
                        'image' => !empty($dt['image']) ? $dt['image']['src'] : null,
                        'created_at' => date('Y-m-d H:i:s'),
                        'updated_at' => date('Y-m-d H:i:s'),
                    ];
                    $dataSave[] = $c;
                    // $c['available'] = true;
                }

                // dump($dataSave);
                CollectionModel::upsert($dataSave, 'id', ['store_id', 'title', 'handle', 'type', 'image']);

                if (!empty($pageInfo)) {
                    // sleep(0.5);
                    // SyncCollectionJob::dispatch(
                    //     $this->storeId,
                    //     $this->shopifyDomain,
                    //     $this->accessToken,
                    //     $this->type,
                    //     $pageInfo
                    // );
                } else {
                    // if ($this->type == "smart_collections") {
                    //     afterHandleSync($this->storeId, 'COLLECTION');
                    // }
                }
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
