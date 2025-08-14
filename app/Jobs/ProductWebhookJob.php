<?php

namespace App\Jobs;

use App\Facade\SystemCache;
use App\Models\BundlesModel;
use App\Models\ProductCommenditionsModel;
use App\Models\ProductModel;
use App\Models\ProductOptionModel;
use App\Models\ProductVariantModel;
use App\Models\StoreModel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class ProductWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $shopifyDomain;
    private $type;
    private $product;

    public function __construct($shopifyDomain, $type, $product)
    {
        $this->onQueue(env('QUEUE_NAME_WEBHOOK'));
        $this->shopifyDomain = $shopifyDomain;
        $this->type = $type;
        $this->product = $product;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $product = $this->product;
        // dump($product);
        // $sentry = app('sentry');
        try {
            switch ($this->type) {
                case "delete":
                    $productId = $product['id'];
                    ProductModel::where('id', $productId)->delete();
                    BundlesModel::where('product_id', $productId)->delete();
                    ProductCommenditionsModel::where('product_id', $productId)->delete();
                    ProductVariantModel::where('product_id', $productId)->delete();
                    ProductOptionModel::where('product_id', $productId)->delete();

                    SystemCache::remove('getBundleStorefront_' . $this->shopifyDomain);
                    break;
                case "update":
                    $productId = $product['id'];
                    $storeInfo = StoreModel::where('shopify_domain', $this->shopifyDomain)->first();
                    $storeId = $storeInfo->store_id;
                    if (empty($storeInfo->app_plan) || $storeInfo->app_version != config('fa_common.app_version')) {
                        return false;
                    }

                    $stock = null;
                    $inventoryManagement = 'shopify';
                    foreach ($product['variants'] as $variant) {
                        $stock += $variant['inventory_quantity'];
                        if (empty($variant['inventory_management'])) {
                            $inventoryManagement = $variant['inventory_management'];
                        }
                    }

                    $firstVariant = $product['variants'][0];
                    $productImage = $product['image']['src'] ?? null;

                    $productDB = ProductModel::where('id', $productId)->first();

                    $hasChange = false; // cheat
                    if (in_array($storeInfo->shopify_plan, ['affiliate', 'partner_test', 'development', 'plus_partner_sandbox'])) {
                        $hasChange = true; // cheat
                    }


                    if ($product['title'] != $productDB->title) {
                        echo " -  change title";
                        $hasChange = true;
                    }

                    if ($product['handle'] != $productDB->handle) {
                        echo " -  change handle";
                        $hasChange = true;
                    }

                    if ($product['status'] != $productDB->status) {
                        echo " -  change status";
                        $hasChange = true;
                    }
                    if ((!empty($productImage) && $productDB->image != $productImage)) {
                        echo " -  change image";
                        $hasChange = true;
                    }
                    if ((!empty($firstVariant['price']) && floatval($productDB->price) != floatval($firstVariant['price']))) {
                        echo " -  change price";
                        $hasChange = true;
                    }
                    if ((!empty($firstVariant['compare_at_price']) && floatval($productDB->compare_at_price) != floatval($firstVariant['price']))) {
                        echo " -  change compare_at_price";
                        $hasChange = true;
                    }

                    if ($productDB->stock != $stock) {
                        echo " -  change stock";
                        $hasChange = true;
                    }
                    if ($productDB->inventory_management != $inventoryManagement) {
                        echo " -  change inventory_management";
                        $hasChange = true;
                    }
                    if ($hasChange) {
                        echo " - has change";

                        ProductVariantModel::where('product_id', $product['id'])->delete();
                        $variants = $this->filterVariant($storeId, $product['variants'], @$product['images']);

                        if ($productId != "8687038038318") {
                            ProductOptionModel::where('product_id', $product['id'])->delete();
                            $options = $this->filterOption($storeId, $product['options']);
                        }


                        $dataSave = [
                            'id' => $product['id'],
                            'store_id' => $storeInfo->store_id,
                            'available' => true,
                            'inventory_management' => $inventoryManagement,
                            'stock' => $stock,
                            'title' => $product['title'],
                            'handle' => $product['handle'],
                            'price' => $firstVariant['price'],
                            'status' => $product['status'],
                            'compare_at_price' => $firstVariant['compare_at_price'],
                            'image' => $productImage,
                        ];

                        // Log::info(print_r($dataSave, true));

                        // Log::info(print_r($productDB, true));

                        ProductModel::updateOrCreate(
                            ['id' => $product['id']],
                            $dataSave
                        );

                        SystemCache::remove('getBundleStorefront_' . $this->shopifyDomain);
                        dispatch(new StoreFrontCacheVersionJob($storeInfo->store_id, $storeInfo->shopify_domain, $storeInfo->access_token, 'bundle'));

                        if (!empty($variants)) {
                            ProductVariantModel::insert($variants);
                        }

                        if ($productId != "8687038038318") {
                            if (!empty($options)) {
                                ProductOptionModel::insert($options);
                            }
                        }
                    } else {
                        echo "no change";
                    }
                    break;
                case "create":
                    $storeInfo = StoreModel::where('shopify_domain', $this->shopifyDomain)->first();
                    $storeId = $storeInfo->store_id;

                    if (empty($storeInfo->app_plan) || $storeInfo->app_version != config('fa_common.app_version')) {
                        return false;
                    }

                    $variants = $this->filterVariant($storeId, $product['variants'], @$product['images']);
                    $options = $this->filterOption($storeId, $product['options']);
                    $firstVariant = $product['variants'][0];

                    $stock = null;
                    $inventoryManagement = 'shopify';
                    foreach ($product['variants'] as $variant) {
                        $stock += $variant['inventory_quantity'];
                        if (empty($variant['inventory_management'])) {
                            $inventoryManagement = $variant['inventory_management'];
                        }
                    }

                    $dataSave = [
                        'id' => $product['id'],
                        'store_id' => $storeId,
                        'available' => true,
                        'inventory_management' => $inventoryManagement,
                        'stock' => $stock,
                        'title' => $product['title'],
                        'handle' => $product['handle'],
                        'price' => $firstVariant['price'],
                        'compare_at_price' => $firstVariant['compare_at_price'],
                        'image' => $product['image']['src'] ?? null,
                        'status' => $product['status']
                    ];

                    $save = ProductModel::updateOrCreate(
                        ['id' => $product['id']],
                        $dataSave
                    );

                    if (!empty($variants)) {
                        ProductVariantModel::insert($variants);
                    }

                    if (!empty($options)) {
                        ProductOptionModel::insert($options);
                    }

                    SystemCache::remove('getBundleStorefront_' . $this->shopifyDomain);
                    dispatch(new StoreFrontCacheVersionJob($storeInfo->store_id, $storeInfo->shopify_domain, $storeInfo->access_token, 'bundle'));

                    break;
            }
        } catch (\Exception $exception) {
            // dump($exception->getMessage());
            // $sentry->captureException($exception);
        }
    }


    private function filterVariant($storeId, $variants, $images)
    {
        $result = [];
        foreach ($variants as $variant) {
            // if ($variant['title'] == config('fa_common.ignore_variant')) {
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
            // if ($option['name'] == config('fa_common.ignore_option')) {
            //     continue;
            // }
            $duration = $duration + 1;

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
