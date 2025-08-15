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

use function Ramsey\Uuid\v1;

class SaveSyncProductsJobV2 implements ShouldQueue
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
        foreach ($products as $productNode) {
            // dump($productNode);
            $product = $productNode['node'];
            $productId =  str_replace('gid://shopify/Product/', '', $product['id']);
            $featureMedia = null;
            if (!empty($product['featuredMedia'])) {
                $featureMedia = @$product['featuredMedia']['preview']['image']['url'];
            }

            $stock = null;
            $inventoryManagement = 'shopify';
            if (empty($product['tracksInventory'])) {
                $inventoryManagement = null;
            }
            foreach ($product['variants']['edges'] as $variantNode) {
                $variant = $variantNode['node'];
                $stock += $variant['inventoryQuantity'];
                // if (empty($variant['inventory_management'])) {
                //     $inventoryManagemenst = $variant['inventory_management'];
                // }
            }

            $updateVariants = $this->filterVariant($storeId, $productId, $product['variants']['edges'], $inventoryManagement);
            if (!empty($updateVariants)) {
                $variants = array_merge($variants, $updateVariants);
            }

            $updateOptions = $this->filterOption($storeId, $productId, $product['options']);
            if (!empty($updateOptions)) {
                $options = array_merge($options, $updateOptions);
            }



            $firstVariant = $product['variants']['edges'][0]['node'];
            $dataSave[] = [
                'id' => $productId,
                'store_id' => $storeId,
                'available' => true,
                'inventory_management' => $inventoryManagement,
                'stock' => $stock,
                'title' => $product['title'],
                'handle' => $product['handle'],
                'price' => $firstVariant['price'],
                'status' => strtolower($product['status']),
                'requires_selling_plan' => $product['requiresSellingPlan'],
                'compare_at_price' => $firstVariant['compareAtPrice'],
                'image' => $featureMedia,
                'created_at' => date('Y-m-d H:i:s', strtotime($product['createdAt'])),
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

        ProductModel::upsert($dataSave, 'id', ['store_id', 'title', 'handle', 'available', 'image', 'compare_at_price', 'price', 'status', 'stock', 'inventory_management', 'created_at', 'requires_selling_plan']);
    }

    private function filterVariant($storeId, $productId, $variants, $inventoryManagement)
    {
        $result = [];
        foreach ($variants as $variant) {
            $variant = $variant['node'];
            $option1 = @$variant['selectedOptions'][0]['value'];
            $option2 = @$variant['selectedOptions'][1]['value'];
            $option3 = @$variant['selectedOptions'][2]['value'];

            $item  = [
                'id' => str_replace('gid://shopify/ProductVariant/', '', $variant['id']),
                'product_id' => $productId,
                'store_id' => $storeId,
                'title' => $variant['title'],
                'inventory' => $variant['inventoryQuantity'],
                'inventory_management' => $inventoryManagement,
                'price' => $variant['price'],
                'compare_at_price' => $variant['compareAtPrice'],
                'option1' => $option1,
                'option2' => $option2,
                'option3' => $option3,
                'image' => @$variant['image']['url'],
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ];
            $result[] = $item;
        }
        return $result;
    }


    private function filterOption($storeId, $productId, $options)
    {
        $result = [];
        $duration = 0;
        foreach ($options as $option) {
            $duration = $duration + 1;
            // if ($option['name'] == config('tf_common.ignore_option')) {
            //     continue;
            // }
            $item  = [
                'id' => str_replace('gid://shopify/ProductOption/', '', $option['id']),
                'product_id' => $productId,
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
