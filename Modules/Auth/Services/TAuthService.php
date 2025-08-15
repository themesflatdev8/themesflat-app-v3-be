<?php

namespace Modules\Auth\Services;


use App\Facade\SystemCache;
use App\Models\ShopModel;
use App\Models\StoreModel;
use App\Models\StoreTestModel;
use App\Repository\StoreRepository;
use Exception;
use Modules\Auth\Events\InstallCompleteEvent;
use Modules\Auth\Events\UpdateVersionAppEvent;
use Modules\Auth\Lib\TAuth;
use Shopify\Context;
use Shopify\Utils;

class TAuthService extends AbstractAuthService
{
    protected $tAuth;

    public function __construct()
    {
        parent::__construct();
        $this->tAuth = app(TAuth::class);
    }

    public  function verifyRequest($data, $force = false)
    {
        $hmac = @$data['hmac'];
        unset($data['hmac']);
        $queryString = http_build_query($data);
        $secret = config('tf_common.shopify_api_secret');
        $calculated = hash_hmac('sha256', $queryString, $secret);
        if ($hmac !== $calculated || empty($data['session'])) {
            return ['status' => false, 'message' => 'error hashmac', 'data' => $this->getUrlAuthorize($data)];
        }

        $shop = ShopModel::where("shop", $data["shop"])->first();
        $isWelcome = false;
        if (empty($shop) || empty($shop->is_active)) {
            try {
                $token = app(TAuth::class)->getExchangeToken($data['id_token'], $data['shop']);
                $userType = $this->getUserType($shop);
                event(new InstallCompleteEvent([
                    'shopify_domain' => $data['shop'],
                    'access_token' => $token,
                    'userType' => $userType
                ]));

                $isWelcome = true;
                $shop = ShopModel::where("shop", $data["shop"])->first();
            } catch (Exception $e) {
                return ['status' => false, 'message' => 'empty store', 'data' => $this->getUrlAuthorize($data)];
            }
        }
        info(2);
        SystemCache::mixCachePaginate(config('fa_setting.cache.list_valid_sessions'), config('fa_setting.cache.list_store_session'), [$data['session'] => $data["shop"]]);
        SystemCache::addItemToHash(config('fa_setting.cache.list_store_key'), $shop->shop_id, $shop->shop);
        SystemCache::addItemToHash(config('fa_setting.cache.store_detail_key'), $shop->shop, $shop->toArray());
        $data = $shop->toArray();

        $planInfo = app(StoreRepository::class)->getPlan($data);
        $data = array_merge($data, [
            'plan_info' => $planInfo,
        ]);

        info(3);
        $checkBlacklist = false;
        $test = false;
        $trialDays = 0;
        $ti = checkTrialTool($shop);
        if (!$ti) {
            // $checkBlacklist = checkBlacklist(
            //     $data['shopify_domain'],
            //     $data['shopify_plan'],
            //     $data['email'],
            //     $data['name'],
            //     $data['domain']
            // );
            // try {
            //     if (!empty($checkBlacklist)) {
            //         unset($checkBlacklist['type']);
            //         unset($checkBlacklist['value']);
            //     }
            // } catch (Exception $e) {
            // }

            // $testCheck = StoreTestModel::where('store_id', $data['store_id'])->first();
            // if (!empty($testCheck)) {
            //     $test = true;
            // }

            $trialDays = getTrialDays($data);

            if ($shop->app_version != config('tf_common.app_version')) {
                event(new UpdateVersionAppEvent([
                    'shop' => $shop,
                    'token' => $shop->access_token
                ]));

                $shop->update([
                    'app_version' => config('tf_common.app_version'),
                    // 'access_token' => $token,
                ]);
            }
        }


        $data['ti'] = $ti;
        $data['bl'] = $checkBlacklist;
        $data['test'] = false;
        $data['trial_days'] = $trialDays;
        $data['is_welcome'] = $isWelcome;

        unset($data['access_token']);


        return ['status' => true, 'data' => $data];
    }

    public function verifyCallback($data)
    {
        $sessionId = TAuth::getOfflineSessionId($data['shop']);
        $dataAuth = SystemCache::getItemFromHash('session_datas', $sessionId);
        $dataAuth = json_decode($dataAuth, true);
        $state = !empty($data['state']) ? $data['state'] : '';
        if (!empty($dataAuth['state']) && $dataAuth['state'] != $state) {
            return $this->getUrlAuthorize($data);
        }

        SystemCache::removeItemsFromHash('session_datas', $sessionId);

        $token = app(TAuth::class)->getToken($data['code'], $data['shop']);
        $shop = ShopModel::where('shop', $data['shop'])->orWhere('domain', $data['shop'])->first();
        $userType = $this->getUserType($shop);
        $sanitizedShop = Utils::sanitizeShopDomain($data['shop']);

        if ($userType != 'active') {
            event(new InstallCompleteEvent([
                'shopify_domain' => $data['shop'],
                'access_token' => $token,
                'userType' => $userType
            ]));
            return "https://{$sanitizedShop}/admin/apps/" . Context::$API_KEY . '?is_welcome=true';
        } else {
            if ($shop->app_version != config('tf_common.app_version')) {
                event(new UpdateVersionAppEvent([
                    'shop' => $shop,
                    'token' => $token
                ]));

                $shop->update([
                    'app_version' => config('tf_common.app_version'),
                    'access_token' => $token,
                ]);
            }
        }

        return "https://{$sanitizedShop}/admin/apps/" . Context::$API_KEY;
    }

    private function getUserType($shop)
    {
        $userType = 'new_install';
        if (!empty($shop)) {
            if (!empty($shop->is_active)) {
                $userType = "active";
            } else {
                $userType = "re_install";
            }
        }

        return $userType;
    }



    public function getUrlAuthorize($data)
    {
        $installUrl = TAuth::begin(
            $data['shop'],
            '/api/auth/callback',
            false

        );
        return $installUrl;
    }
}
