<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\StoreFrontCacheVersionJob;
use App\Models\BundlesModel;
use App\Models\CartSettingsModel;
use App\Models\SearchSettingsModel;
use App\Models\SettingsModel;
use App\Models\StoreModel;
use App\Models\TrustSettingsModel;
use Illuminate\Http\Request;
use PgSql\Lob;

class TrustSettingController extends Controller
{
    public $storeModel;
    public $settingsModel;
    public $sentry;

    public function __construct(
        StoreModel $storeModel,
        TrustSettingsModel $settingsModel,
    ) {
        $this->storeModel = $storeModel;
        $this->sentry = app('sentry');
        $this->settingsModel = $settingsModel;
    }

    public function getSettings(Request $request)
    {
        $store = $request->storeInfo;

        $type = $request->type;
        $keydefault = 'fa_trust.default_' . $type;
        $default = config($keydefault);
        $settings  = $this->settingsModel->where('store_id', $store->store_id)->where('type', $type)->first();

        return response([
            'data' => !empty($settings->settings) ? $settings->settings : $default,
            'default' => $default
        ]);
    }

    public function update(Request $request)
    {
        $type = $request->type;
        $store = $request->storeInfo;

        $settings  = $this->settingsModel->where('store_id', $store->store_id)->where('type', $type)->first();
        if (empty($settings)) {
            $dataSave = [
                'settings' => !empty($request->settings) ? $request->settings : null,
                'store_id' => $store->store_id,
                'type' => $type,
                // 'version' => config('fa_switcher.template_version')
            ];
            $save = $this->settingsModel->create($dataSave);
        } else {
            $save = $this->settingsModel->where('store_id', $store->store_id)->where('type', $type)
                ->update([
                    'settings' => !empty($request->settings) ? $request->settings : null,
                    // 'version' => config('fa_switcher.template_version')
                ]);
        }



        // SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
        // dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));

        return response([
            'message' => 'Success'
        ]);
    }

    public function resetDefault(Request $request)
    {
        $store = $request->storeInfo;
        $pageType = $request->pageType;
        // if ($pageType == "cart") {
        //     $save = $this->cartSettingsModel->where('id', $store->store_id)->update([
        //         'settings' => config('fa_switcher.cart_setting_default'),
        //     ]);
        // } else {
        //     $save = $this->settingsModel->where('id', $store->store_id)->update([
        //         'settings' => config('fa_switcher.setting_default'),
        //     ]);
        // }


        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));

        return response([
            'message' => 'Success'
        ]);
    }


    public function verifyAppBlock(Request $request)
    {

        $store = $request->storeInfo;
        $shopDomain = $store->shopify_domain;
        $accessToken = $store->access_token;
        $theme = getThemeActive($shopDomain, $accessToken);

        $statusThemeAppExtension = false;
        $idBlock = config('fa_switcher.app_block.id_block');
        $pageType = $request->pageType;
        $dataThemeAppExtension = getDataAppBlockExtension($store, $theme->id, $pageType);
        if (!empty($dataThemeAppExtension)) {
            foreach ($dataThemeAppExtension as $dta) {
                if (strpos($dta, config('fa_switcher.app_block')[$idBlock]) !== false) {
                    $statusThemeAppExtension = true;
                    break;
                }
            }
        }

        // if ($dataThemeAppExtension['current'] == 'Default') {
        //     $statusThemeAppExtension = false;
        // } else {
        // $statusThemeAppExtension = !$dataThemeAppExtension['current']['blocks'][$idBlock]['disabled'];
        // }

        if ($shopDomain == "0834c0.myshopify.com") {
            $statusThemeAppExtension = true;
        }

        return response([
            'verify' => $statusThemeAppExtension,
            'message' => 'Success'
        ]);
    }

    public function verifyAppEmbed(Request $request)
    {
        $store = $request->storeInfo;
        $shopDomain = $store->shopify_domain;
        $accessToken = $store->access_token;
        $theme = getThemeActive($shopDomain, $accessToken);
        $dataThemeAppExtension = getDataThemeAppExtension($store, $theme->id);
        $dataThemeAppExtension = json_decode(json_encode($dataThemeAppExtension), true);

        $idBlock = config('fa_switcher.app_embed.id_block');

        if ($dataThemeAppExtension['current'] == 'Default') {
            $statusThemeAppExtension = false;
        } else {
            $statusThemeAppExtension = !$dataThemeAppExtension['current']['blocks'][$idBlock]['disabled'];
        }

        return response([
            'verify' => $statusThemeAppExtension,
            'message' => 'Success'
        ]);
    }

    public function saveSetupGuide(Request $request)
    {
        $store = $request->storeInfo;
        $data = $request->setup_guide;
        $save = $this->storeModel->where('store_id', $store->store_id)->update([
            'setup_guide' => $data,
        ]);

        return response([
            'message' => 'Success'
        ]);
    }
}
