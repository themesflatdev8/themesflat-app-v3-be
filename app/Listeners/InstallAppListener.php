<?php


namespace App\Listeners;

use App\Jobs\Install\RegisterAllShopifyWebHook;
use App\Jobs\Sync\SyncDiscountJob;
use App\Models\ApproveDomainModel;
use App\Models\ResponseModel;
use App\Models\SettingsModel;
use App\Models\ShopModel;
use App\Repository\ShopRepository;
use App\Services\App\ProductService;
use App\Services\App\SearchService;
use App\Services\Shopify\ShopifyApiService;
use Google\Service\Docs\Response;

class InstallAppListener
{
    protected $shopifyApiService;
    protected $settingModel;
    protected $shopRepository;

    public function __construct(
        ShopifyApiService $shopifyApiService,
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
                $this->saveApprovedDomain($data['shopify_domain'], $storeDataApi->email);
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
                dispatch(new SyncDiscountJob($storeDataApi->id, $data['shopify_domain'], $data['access_token'], true, 250, '', true));
                //create endpoint storefront
                $this->createEndpointStoreFront($data);
                // dispatch(new GenerateBundleJob($storeDataApi->id, $data['shopify_domain'], $data['access_token']))->delay(now()->addSeconds(20));
            }
        } catch (\Exception $e) {
            app('sentry')->captureException($e);
        }
    }

    private function saveApprovedDomain($shopifyDomain, $email)
    {
        try {
            $dataSave = [
                'domain_name' => $shopifyDomain,
                'email_domain' => $email,
                'valid_days' => 30,
                'status' => 'approved',
                'created_active' => now(),
            ];
            $result =  ApproveDomainModel::updateOrCreate(['domain_name' => $shopifyDomain], $dataSave);
        } catch (\Exception $e) {
            app('sentry')->captureException($e);
        }
        return true;
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

    private function createEndpointStoreFront($shopInfo)
    {
        try {
            //create endpoint storefront product top view
            /** @var ProductService $productService */
            $productService = app(ProductService::class);
            $limit = 4;
            $paramHash = md5(json_encode(['limit' => $limit]));
            $result = $productService->productTopView($shopInfo, $limit);

            // 3️⃣ Lưu cache
            ResponseModel::updateOrCreate(
                [
                    'shop_domain' => $shopInfo['shop'],
                    'api_name'    => 'productTopView',
                    'param'       => $paramHash,
                ],
                [
                    'response'    => json_encode($result),
                    'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)),
                ]
            );



            // top keyword
            /**  @var \App\Services\App\SearchService $searchService */
            $searchService = app(SearchService::class);
            $range = 1;
            $paramHash = md5(json_encode(['range' => $range]));
            $result = $searchService->topKeywords($shopInfo, $range);
            ResponseModel::updateOrCreate(
                [
                    'shop_domain' => $shopInfo['shop'],
                    'api_name'    => 'topKeywords',
                    'param'       => $paramHash,
                ],
                [
                    'response'    => json_encode($result),
                    'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)),
                ]
            );
        } catch (\Exception $e) {
            app('sentry')->captureException($e);
        }
        return true;
    }
}
