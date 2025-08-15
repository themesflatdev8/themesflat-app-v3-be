<?php


namespace App\Listeners;

use App\Jobs\CreateBundleCartJob;
use App\Jobs\CreateBundleSearchJob;
use App\Jobs\Install\RegisterAllShopifyWebHook;
use App\Jobs\Sync\SyncCollectionJob;
use App\Jobs\Sync\SyncShopifyProductsJobV2;
use App\Repository\StoreRepository;
use App\Services\Shopify\ShopifyApiService;

class UpdateVersionAppListener
{
    protected $shopifyApiService;
    protected $storeRepository;

    public function __construct(
        ShopifyApiService $shopifyApiService,
        StoreRepository $storeRepository,
    ) {
        $this->shopifyApiService = $shopifyApiService;
        $this->storeRepository = $storeRepository;
    }


    /**
     * @param $event
     */
    public function handle($event)
    {
        $data = $event->store;

        $shop = $data['shop'];
        if ($shop->app_version < 3.2) {
            dispatch(new RegisterAllShopifyWebHook($shop->shopify_domain, $shop->access_token));
            dispatch(new SyncShopifyProductsJobV2($shop->store_id, $shop->shopify_domain, $shop->access_token));
            dispatch(new SyncCollectionJob($shop->store_id, $shop->shopify_domain, $shop->access_token, 'custom_collections'));
            dispatch(new SyncCollectionJob($shop->store_id, $shop->shopify_domain, $shop->access_token, 'smart_collections'));
        }

        if ($shop->app_version < 5.0) {
            dispatch(new CreateBundleCartJob($shop->store_id, $shop->shopify_domain, $shop->access_token));
        }
        if ($shop->app_version < 6.0) {
            dispatch(new CreateBundleSearchJob($shop->store_id, $shop->shopify_domain, $shop->access_token));
        }

        // dispatch(new AddScriptTag($shop->shopify_domain, $shop->access_token));

        // $shop->update([
        //     'app_version' => config('tf_common.app_version'),
        //     'access_token' => $token,
        // ]);
    }

    private function getShopInfoFromShopify($shopifyDomain, $accessToken)
    {
        $this->shopifyApiService->setShopifyHeader($shopifyDomain, $accessToken);
        $response = $this->shopifyApiService->get('shop.json');
        if (!empty($response)) {
            return $response->shop;
        }

        return null;
    }

    private function saveStoreInfo($storeDataApi, string $shopifyDomain, string $accessToken, string $userType)
    {
        $dataSave = [
            'store_id' => $storeDataApi->id,
            'shopify_domain' => $shopifyDomain,
            'access_token' => $accessToken,
            'name' => $storeDataApi->name,
            'domain' => $storeDataApi->domain,
            'shopify_plan' => $storeDataApi->plan_name,
            'owner' => $storeDataApi->shop_owner,
            'email' => $storeDataApi->email,
            'phone' => $storeDataApi->phone,
            'country' => $storeDataApi->country,
            'currency' => $storeDataApi->currency,
            'primary_locale' => $storeDataApi->primary_locale,
            'app_status' => 1,
            'app_plan' => 'free',
            'app_version' => config('tf_common.app_version'),
        ];

        // $store = $this->storeRepository->where('shopify_domain', $shopifyDomain)->first();
        if ($userType == 'new_install') {
            $save = $this->storeRepository->create($dataSave);
        } else {
            $save = $this->storeRepository->where('shopify_domain', $shopifyDomain)->update($dataSave);
        }

        return $save;
    }
}
