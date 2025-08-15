<?php

namespace App\Jobs\Sync;

use App\Facade\SystemCache;
use App\Models\Mongo\Product;
use App\Models\ProductModel;
use App\Models\ProductOptionModel;
use App\Models\ProductVariantModel;
use App\Services\Sync\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Throwable;

class SaveSyncProductsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    private $products;

    private $storeId;
    private $resync;
    private $final;

    public function __construct($storeId, $data = [], $resync = false, $final = false)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

        $this->storeId = $storeId;
        $this->products = $data;
        $this->resync = $resync;
        $this->final = $final;
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
            if ($this->resync && !empty($this->products)) {
                $productIds = [];
                foreach ($this->products as $product) {
                    $productId = $product->id;
                    $productIds[] = $productId;
                }
            }
            $this->handleSyncData($this->storeId, $this->products);
        } catch (\Exception $ex) {
            $sentry->captureException($ex);
            // echo $ex->getMessage() . "\n";
            dump($ex);
        }
        if ($this->final) {
            ProductModel::where('store_id', $this->storeId)->where('available', false)->delete();
        }
    }

    public function failed(Throwable $exception)
    {
        $sentry = app('sentry');
        $sentry->captureException($exception);
        // echo $exception->getMessage();
    }

    public function handleSyncData($storeId, $products)
    {
        $products = json_decode(json_encode($products), true);
        $variants = [];
        $options = [];
        $dataSave = [];
        foreach ($products as $product) {
            $stock = null;
            $inventoryManagement = 'shopify';
            foreach ($product['variants'] as $variant) {
                $stock += $variant['inventory_quantity'];
                if (empty($variant['inventory_management'])) {
                    $inventoryManagement = $variant['inventory_management'];
                }
            }

            $updateVariants = $this->filterVariant($storeId, $product['variants'], $product['images']);
            if (!empty($updateVariants)) {
                $variants = array_merge($variants, $updateVariants);
            }

            $updateOptions = $this->filterOption($storeId, $product['options']);
            if (!empty($updateOptions)) {
                $options = array_merge($options, $updateOptions);
            }

            $firstVariant = $product['variants'][0];
            $dataSave[] = [
                'id' => $product['id'],
                'store_id' => $storeId,
                'available' => true,
                'inventory_management' => $inventoryManagement,
                'stock' => $stock,
                'title' => $product['title'],
                'handle' => $product['handle'],
                'price' => $firstVariant['price'],
                'status' => $product['status'],
                'compare_at_price' => $firstVariant['compare_at_price'],
                'image' => $product['image']['src'] ?? null,
                'created_at' => date('Y-m-d H:i:s', strtotime($product['created_at'])),
            ];
        }

        if (!empty($variants)) {
            // ProductVariantModel::upsert($variants, 'id', ['product_id', 'store_id', 'inventory', 'title', 'option1', 'option2', 'option3', 'price', 'compare_at_price']);
            ProductVariantModel::insert($variants);
        }

        // dump($options);
        if (!empty($options)) {
            // ProductOptionModel::upsert($options, 'id', ['product_id', 'store_id', 'name', 'values']);
            ProductOptionModel::insert($options);
        }

        ProductModel::upsert($dataSave, 'id', ['store_id', 'title', 'handle', 'available', 'image', 'compare_at_price', 'price', 'status', 'stock', 'inventory_management', 'created_at']);
    }

    private function filterVariant($storeId, $variants, $images)
    {
        $result = [];
        foreach ($variants as $variant) {
            // if ($variant['title'] == config('tf_common.ignore_variant')) {
            //     continue;
            // }
            $image = null;
            if (!empty($images)) {
                foreach ($images as $img) {
                    if ($variant['image_id'] == $img['id']) {
                        $image = $img['src'];
                        break;
                    }
                }
            }

            $item  = [
                'id' => $variant['id'],
                'product_id' => $variant['product_id'],
                'store_id' => $storeId,
                'title' => $variant['title'],
                'inventory' => $variant['inventory_quantity'],
                'inventory_management' => $variant['inventory_management'],
                'price' => $variant['price'],
                'compare_at_price' => $variant['compare_at_price'],
                'option1' => $variant['option1'],
                'option2' => $variant['option2'],
                'option3' => $variant['option3'],
                'image' => $image,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $result[] = $item;
        }
        return $result;
    }


    private function filterOption($storeId, $options)
    {
        $result = [];
        $duration = 0;
        foreach ($options as $option) {
            $duration = $duration + 1;
            // if ($option['name'] == config('tf_common.ignore_option')) {
            //     continue;
            // }
            $item  = [
                'id' => $option['id'],
                'product_id' => $option['product_id'],
                'store_id' => $storeId,
                'name' => $option['name'],
                'values' => json_encode($option['values']),
                'created_at' => date("Y-m-d H:i:s", strtotime("+$duration sec")),
                'updated_at' => date("Y-m-d H:i:s", strtotime("+$duration sec")),
            ];
            $result[] = $item;
        }
        return $result;
    }
}
