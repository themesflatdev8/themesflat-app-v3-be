<?php


namespace App\Listeners;

use App\Jobs\CreateBundleCartJob;
use App\Jobs\CreateBundleSearchJob;
use App\Jobs\Install\RegisterAllShopifyWebHook;
use App\Jobs\Sync\SyncCollectionJob;
use App\Jobs\Sync\SyncShopifyProductsJobV2;
use App\Models\SettingsModel;
use App\Repository\StoreRepository;
use App\Services\Shopify\ShopifyApiService;

class InstallAppListener
{
    protected $shopifyApiService;
    protected $storeRepository;
    protected $settingModel;

    public function __construct(
        ShopifyApiService $shopifyApiService,
        StoreRepository $storeRepository,
        SettingsModel $settingModel,
    ) {
        $this->shopifyApiService = $shopifyApiService;
        $this->storeRepository = $storeRepository;
        $this->settingModel = $settingModel;
    }


    /**
     * @param $event
     */
    public function handle($event)
    {
        $data = $event->store;
        info('listener event install');
        //get and save store info from shopify
        $storeDataApi = $this->getShopInfoFromShopify($data['shopify_domain'], $data['access_token']);
        if (!empty($storeDataApi)) {
            $this->saveStoreInfo($storeDataApi, $data['shopify_domain'], $data['access_token'], $data['userType']);
            // dispatch(new SyncShopifyProductsJobV2($storeDataApi->id, $data['shopify_domain'], $data['access_token']));
            // dispatch(new SyncCollectionJob($storeDataApi->id, $data['shopify_domain'], $data['access_token'], 'custom_collections'));
            // dispatch(new SyncCollectionJob($storeDataApi->id, $data['shopify_domain'], $data['access_token'], 'smart_collections'));
            // dispatch(new CreateBundleCartJob($storeDataApi->id, $data['shopify_domain'], $data['access_token']));

            // add settings default
            $settings = config('fa_switcher.setting_default');
            // $this->settingModel->updateOrCreate(
            //     ['id' => $storeDataApi->id],
            //     [
            //         'settings' => $settings,
            //         'template_version' => config('fa_switcher.template_version')
            //     ]
            // );
            info('dispatch register all shopify web hook');
            dispatch(new RegisterAllShopifyWebHook($data['shopify_domain'], $data['access_token']));

            // dispatch(new GenerateBundleJob($storeDataApi->id, $data['shopify_domain'], $data['access_token']))->delay(now()->addSeconds(20));
        }
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
            'money_format' => $storeDataApi->money_format,
            'primary_locale' => $storeDataApi->primary_locale,
            'timezone' => $storeDataApi->iana_timezone,
            'app_status' => 1,
            'app_plan' => 'free',
            'app_version' => config('fa_common.app_version'),
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
