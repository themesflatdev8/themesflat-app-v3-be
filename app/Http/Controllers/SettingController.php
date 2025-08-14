<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\StoreFrontCacheVersionJob;
use App\Models\BundlesModel;
use App\Models\CartSettingsModel;
use App\Models\SearchSettingsModel;
use App\Models\SettingsModel;
use App\Models\StoreModel;
use Illuminate\Http\Request;
use PgSql\Lob;

class SettingController extends Controller
{
    public $storeModel;
    public $settingsModel;
    public $cartSettingsModel;
    public $searchSettingsModel;
    public $sentry;

    public function __construct(
        StoreModel $storeModel,
        SettingsModel $settingsModel,
        CartSettingsModel $cartSettingsModel,
        SearchSettingsModel $searchSettingsModel
    ) {
        $this->storeModel = $storeModel;
        $this->sentry = app('sentry');
        $this->settingsModel = $settingsModel;
        $this->cartSettingsModel = $cartSettingsModel;
        $this->searchSettingsModel = $searchSettingsModel;
    }

    public function getSettings(Request $request)
    {
        $store = $request->storeInfo;
        $settingsDefault = config('fa_switcher.setting_default');
        $settings  = $this->settingsModel->find($store->store_id);
        if (empty($settings)) {
            $this->settingsModel->updateOrCreate(
                ['id' => $store->store_id],
                [
                    'settings' => $settingsDefault,
                    'template_version' => config('fa_switcher.template_version')
                ]
            );

            $settings  = $this->settingsModel->find($store->store_id);
        }

        // if ($settings->template_version == 1) {
        //     $oldSettings = $settings->settings;
        //     $newSettings = migrateBundleSettings1vs2($oldSettings, $settingsDefault);
        //     $settings->settings  = $newSettings;
        // }

        $totalBundlePublish = BundlesModel::where('store_id', $store->store_id)->where('status', 1)->count();
        $settings->total_bundle_publish = $totalBundlePublish;

        return response([
            'data' => $settings,
            'default' => $settingsDefault
        ]);
    }

    public function update(Request $request)
    {
        $pageType = $request->pageType;
        $store = $request->storeInfo;

        $save = $this->settingsModel->where('id', $store->store_id)->update([
            'settings' => !empty($request->settings) ? $request->settings : null,
            'template_version' => config('fa_switcher.template_version')
        ]);

        SystemCache::remove('getBundleStorefront_' . $store->shopify_domain);
        dispatch(new StoreFrontCacheVersionJob($store->store_id, $store->shopify_domain, $store->access_token, 'bundle'));

        return response([
            'message' => 'Success'
        ]);
    }

    public function resetDefault(Request $request)
    {
        $store = $request->storeInfo;
        $pageType = $request->pageType;
        $save = $this->settingsModel->where('id', $store->store_id)->update([
            'settings' => config('fa_switcher.setting_default'),
        ]);

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
        $type = $request->type;
        $pageType = 'product';
        $idBlock = config('fa_switcher.app_block.id_block');
        $stringCheck = config('fa_switcher.app_block')[$idBlock];

        switch ($type) {
            case "product_bundles":
                $pageType = 'product';
                $stringCheck = 'product-bundles\/' . $stringCheck;
                break;
            case "trust_badges":
                $pageType = 'product';
                $stringCheck = 'trust-badge\/' . $stringCheck;
                break;
            case "payment_badges":
                $pageType = 'product';
                $stringCheck = 'payment-badge\/' . $stringCheck;
                break;
        }

        $theme = getThemeActive($shopDomain, $accessToken);

        $statusThemeAppExtension = false;
        $dataThemeAppExtension = getDataAppBlockExtension($store, $theme->id, $pageType);
        if (!empty($dataThemeAppExtension)) {
            foreach ($dataThemeAppExtension as $dta) {
                if (strpos($dta, $stringCheck) !== false) {
                    $statusThemeAppExtension = true;
                    break;
                }
            }
        }


        return response([
            'verify' => $statusThemeAppExtension,
            'message' => 'Success'
        ]);
    }

    public function verifyAppEmbed(Request $request)
    {
        $statusThemeAppExtension = false;
        try {
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
                $statusThemeAppExtension = !@$dataThemeAppExtension['current']['blocks'][$idBlock]['disabled'];
            }
        } catch (\Exception $e) {
            $statusThemeAppExtension = false;
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
