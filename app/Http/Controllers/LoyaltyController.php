<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\AutoAddLoyalty;
use App\Mail\Loyalty;
use App\Models\BundlesModel;
use App\Models\LoyaltyModel;
use App\Models\ProductCommenditionsModel;
use App\Models\StoreModel;
use App\Services\App\PricingService;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class LoyaltyController extends Controller
{
    public $storeModel;
    public $loyaltyModel;
    public $sentry;

    public function __construct(
        StoreModel $storeModel,
        LoyaltyModel $loyaltyModel
    ) {
        $this->storeModel = $storeModel;
        $this->sentry = app('sentry');
        $this->loyaltyModel = $loyaltyModel;
    }

    public function getLoyalty(Request $request)
    {
        $store = $request->storeInfo;
        $check = checkLoyalty($store);

        return response($check);
    }

    public function checkBundle(Request $request)
    {
        $store = $request->storeInfo;
        $check  = BundlesModel::where('store_id', $store->store_id)->where('status', 1)->first();
        if (!empty($check)) {
            return response([
                'check' => true
            ]);
        }
        return response([
            'check' => false
        ]);
    }

    public function setLoyalty(Request $request)
    {
        $store = $request->storeInfo;
        $loyalty = $this->loyaltyModel->where('store_id', $store->store_id)->first();
        $data = [];
        if (isset($request->quest_ext)) {
            $data['quest_ext'] = $request->quest_ext;
        }
        if (isset($request->quest_bundle)) {
            $data['quest_bundle'] = $request->quest_bundle;
        }
        if (isset($request->quest_review)) {
            $data['quest_review'] = $request->quest_review;
        }
        if (isset($request->email)) {
            $data['email'] = $request->email;
        }

        if (!empty($loyalty)) {
            $loyalty->update($data);
        } else {
            $data['store_id'] = $store->store_id;
            $this->loyaltyModel->create($data);
        }

        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);

        return response([
            'message' => 'success'
        ], 200);
    }

    public function applyLoyalty(Request $request)
    {
        $store = $request->storeInfo;
        $loyalty = $this->loyaltyModel->where('store_id', $store->store_id)->first();
        $data = [];

        $data['apply'] = true;
        if (!empty($loyalty)) {
            $loyalty->update($data);
        } else {
            $data['store_id'] = $store->store_id;
            $this->loyaltyModel->create($data);
        }

        AutoAddLoyalty::dispatch($store->store_id)
            ->delay(now()->addHours(24));

        // AutoAddLoyalty::dispatch($store->store_id)
        //     ->delay(now()->addMinutes(10));


        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);

        return response([
            'message' => 'success'
        ], 200);
    }
}
