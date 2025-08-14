<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\Sync\SyncShopifyProductsJobV2;
use App\Models\StoreModel;

class BundleController extends Controller
{
    /**
     * Sync shopify products
     * POST /store/sync-products
     */
    public function syncProducts($id = null)
    {
        $store = StoreModel::where('store_id', $id)->first();
        if ($store) {
            dispatch(new SyncShopifyProductsJobV2($store->store_id, $store->shopify_domain, $store->access_token));
            return redirect()->back()->with('success', 'Sync product success');
        }
        return redirect()->back()->with('error', 'Unable to sync products: Store not found');
    }
}
