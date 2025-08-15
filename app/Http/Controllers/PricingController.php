<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\DeactiveDiscountJob;
use App\Models\BundlesModel;
use App\Models\StoreModel;
use App\Services\App\PricingService;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Http\Request;

class PricingController extends Controller
{
    public $pricingService;
    public $storeModel;
    public $shopifyService;
    public $sentry;

    public function __construct(
        PricingService $pricingService,
        ShopifyApiService $shopifyService,
        StoreModel $storeModel,
    ) {
        $this->pricingService = $pricingService;
        $this->storeModel = $storeModel;
        $this->shopifyService = $shopifyService;
        $this->sentry = app('sentry');
    }

    public function charge(Request $request)
    {
        $store = $request->storeInfo;

        $data =  $this->pricingService->chargeAdd($store, $request->get('plan'), $request->get('redirect_url'));
        return response([
            'data' => $data->getData()
        ]);
    }

    public function chargeCallback(Request $request)
    {
        $data = $request->all();
        $idCharged = $data['charge_id'];
        $shopifyDomain = $data['shopify_domain'];
        if (!empty($data['redirect_url'])) {
            $feRedirectUrl = 'https://' . $data['shopify_domain'] . '/admin/apps/' . env('EMBEDDED_APP_NAME') . '?' . $data['redirect_url'];
        } else {
            $feRedirectUrl = 'https://' . $data['shopify_domain'] . '/admin/apps/' . env('EMBEDDED_APP_NAME');
        }
        $newPlan = $data['plan'];
        $store = $this->storeModel->where('shopify_domain', $shopifyDomain)->first();

        $this->shopifyService->setShopifyHeader($shopifyDomain, $store->access_token);
        $detailCharged = $this->shopifyService->get('recurring_application_charges/' . $idCharged . '.json');

        $activeCharge = $detailCharged->recurring_application_charge;


        $dataSave = [
            'billing_id' => isset($activeCharge->id) ? $activeCharge->id : null,
            'billing_on' => isset($activeCharge->billing_on) ? $activeCharge->billing_on : null,
            'app_plan' => $newPlan,
            'trial_on' => date('Y-m-d H:i:s'),
            'trial_days' => getTrialDays($store),
            // 'pricing_version' => config('tf_common.pricing_version'),
        ];

        $store->update($dataSave);
        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);

        return redirect($feRedirectUrl);
    }
}
