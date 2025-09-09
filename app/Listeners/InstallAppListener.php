<?php


namespace App\Listeners;

use App\Jobs\CreateBundleCartJob;
use App\Jobs\CreateBundleSearchJob;
use App\Jobs\Install\RegisterAllShopifyWebHook;
use App\Jobs\Sync\SyncCollectionJob;
use App\Jobs\Sync\SyncShopifyProductsJobV2;
use App\Models\SettingsModel;
use App\Models\ShopModel;
use App\Repository\ShopRepository;
use App\Services\Shopify\ShopifyApiService;

class InstallAppListener
{
    protected $shopifyApiService;
    protected $settingModel;
    protected $shopRepository;

    public function __construct(
        ShopifyApiService $shopifyApiService,
        SettingsModel $settingModel,
        ShopRepository $shopRepository
    ) {
        $this->shopifyApiService = $shopifyApiService;
        $this->shopRepository = $shopRepository;
    }


    /**
     * @param $event
     */
    public function handle($event)
    {
        try {
            $data = $event->store;
            //get and save store info from shopify
            $storeDataApi = $this->getShopInfoFromShopify($data['shopify_domain'], $data['access_token']);
            if (!empty($storeDataApi)) {
                $this->saveStoreInfo($storeDataApi, $data['shopify_domain'], $data['access_token'], $data['userType']);
                // dispatch(new SyncShopifyProductsJobV2($storeDataApi->id, $data['shopify_domain'], $data['access_token']));
                // dispatch(new SyncCollectionJob($storeDataApi->id, $data['shopify_domain'], $data['access_token'], 'custom_collections'));
                // dispatch(new SyncCollectionJob($storeDataApi->id, $data['shopify_domain'], $data['access_token'], 'smart_collections'));
                // dispatch(new CreateBundleCartJob($storeDataApi->id, $data['shopify_domain'], $data['access_token']));

                // add settings default
                // $settings = config('fa_switcher.setting_default');
                // $this->settingModel->updateOrCreate(
                //     ['id' => $storeDataApi->id],
                //     [
                //         'settings' => $settings,
                //         'template_version' => config('fa_switcher.template_version')
                //     ]
                // );
                dispatch(new RegisterAllShopifyWebHook($data['shopify_domain'], $data['access_token']));

                // dispatch(new GenerateBundleJob($storeDataApi->id, $data['shopify_domain'], $data['access_token']))->delay(now()->addSeconds(20));
            }
        } catch (\Exception $e) {
            app('sentry')->captureException($e);
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
            'shop_id' => $storeDataApi->id,
            'shop' => $shopifyDomain,
            'access_token' => $accessToken,
            'domain' => $storeDataApi->domain,
            'shopify_plan' => $storeDataApi->plan_name,
            'email' => $storeDataApi->email,
            'phone' => $storeDataApi->phone,
            'is_active' => 1,
            'app_plan' => 'free',
            'app_version' => config('tf_common.app_version'),
            'installed_at' => now()
        ];

        if ($userType == 'new_install') {
            $save = ShopModel::create($dataSave);
        } else {
            $save = ShopModel::where('shop', $shopifyDomain)->update($dataSave);
        }

        return $save;
    }
}
