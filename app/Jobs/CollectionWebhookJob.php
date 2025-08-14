<?php

namespace App\Jobs;

use App\Facade\SystemCache;
use App\Models\CollectionModel;
use App\Models\StoreModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class CollectionWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $shopifyDomain;
    private $type;
    private $collection;

    public function __construct($shopifyDomain, $type, $collection)
    {
        $this->onQueue(env('QUEUE_NAME_WEBHOOK'));
        $this->shopifyDomain = $shopifyDomain;
        $this->type = $type;
        $this->collection = $collection;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $collection = $this->collection;

        $sentry = app('sentry');
        try {
            $collectionId = $collection['id'];
            switch ($this->type) {
                case "delete":
                    CollectionModel::where('id', $collectionId)->delete();
                    break;
                case "update":
                    $storeInfo = StoreModel::where('shopify_domain', $this->shopifyDomain)->first();
                    $storeId = $storeInfo->store_id;
                    $hasChange = true;

                    if ($hasChange) {
                        echo " - has change";

                        $dataSave = [
                            'id' => $collectionId,
                            'store_id' => $storeId,
                            'title' => $collection['title'],
                            'handle' => $collection['handle'],
                            // 'products_count' => $collection['products_count'],
                            'type' => $this->type,
                            'image' => !empty($collection['image']) ? $collection['image']['src'] : null,
                        ];

                        CollectionModel::updateOrCreate(
                            ['id' => $collectionId],
                            $dataSave
                        );
                    } else {
                        echo "no change";
                    }
                    break;
                case "create":
                    $storeInfo = StoreModel::where('shopify_domain', $this->shopifyDomain)->first();
                    $storeId = $storeInfo->store_id;


                    $dataSave = [
                        'id' => $collectionId,
                        'store_id' => $storeId,
                        'title' => $collection['title'],
                        'handle' => $collection['handle'],
                        'type' => $this->type,
                        'image' => !empty($collection['image']) ? $collection['image']['src'] : null,
                        // 'products_count' => $collection['products_count'],
                    ];

                    $save = CollectionModel::updateOrCreate(
                        ['id' => $collectionId],
                        $dataSave
                    );

                    break;
            }
        } catch (\Exception $exception) {
            echo ($exception->getMessage());
            // $sentry->captureException($exception);
        }
    }
}
