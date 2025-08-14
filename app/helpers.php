<?php

use App\Mail\Loyalty;
use App\Models\BlackListModel;
use App\Models\LoyaltyModel;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Support\Facades\Mail;

use function Clue\StreamFilter\fun;

function getTrialDays($storeInfo)
{
    if (is_array($storeInfo)) {
        $trialOn = $storeInfo['trial_on'];
        $trialDays = !empty($storeInfo['trial_days']) ? $storeInfo['trial_days'] : config('fa_common.trial_days');
    } else {
        $trialOn = $storeInfo->trial_on;
        $trialDays = !empty($storeInfo->trial_days) ? $storeInfo->trial_days : config('fa_common.trial_days');
    }

    if (!empty($trialOn)) {
        $today = date('Y-m-d');
        $trialOn  = date('Y-m-d', strtotime($trialOn));
        $diff = date_diff(date_create($today), date_create($trialOn));
        $daysDiff = $diff->days;
        if ($daysDiff > 0) {
            $trialDays = $trialDays - $daysDiff;
        }
    }
    if ($trialDays < 0) {
        $trialDays = 0;
    }

    return $trialDays;
}

function getThemeActive($shopifyDomain, $accessToken)
{
    $themeActive = null;
    $shopifyApiService = new ShopifyApiService();
    $shopifyApiService->setShopifyHeader($shopifyDomain, $accessToken);
    $allThemes = $shopifyApiService->get('themes.json');
    if ($allThemes) {
        $allThemes = $allThemes->themes;
        $theme = array_filter($allThemes, function ($item) {
            return $item->role === 'main';
        });

        if (!empty($theme)) {
            $themeActive = array_values($theme)[0];
        }
    }

    return $themeActive;
}

function getDataThemeAppExtension($storeInfo, $themeId)
{
    $shopifyApiService = new ShopifyApiService();
    $shopifyApiService->setShopifyHeader($storeInfo->shopify_domain, $storeInfo->access_token);
    $result = $shopifyApiService->get('themes/' . $themeId . '/assets.json', [
        'asset' => [
            'key' => config('fa_switcher.app_embed.path'),
        ],
    ]);

    if (!empty($result->asset)) {
        $assetValue = $result->asset;
        $value = json_decode($assetValue->value);

        return $value;
    }

    return [];
}

function getDataAppBlockExtension($storeInfo, $themeId, $pageType = "product")
{
    $values = [];
    $shopifyApiService = new ShopifyApiService();
    $shopifyApiService->setShopifyHeader($storeInfo->shopify_domain, $storeInfo->access_token);
    $resultAll = $shopifyApiService->get('themes/' . $themeId . '/assets.json');
    foreach ($resultAll->assets as $rs) {
        if (str_contains($rs->key, 'templates/' . $pageType)) {
            $result = $shopifyApiService->get('themes/' . $themeId . '/assets.json', [
                'asset' => [
                    'key' => $rs->key,
                ],
            ]);


            if (!empty($result->asset)) {
                $assetValue = $result->asset;
                $value = ($assetValue->value);
                $values[] = $value;
            }
        }
    }

    return $values;
}


function checkBlacklist(
    $shopify_domain,
    $shopify_plan,
    $email,
    $store_name = null,
    $domain = null
) {
    $checkBlacklist = null;
    $storeBlacklist = BlackListModel::get();

    $checkEmail = $storeBlacklist->where('type', BlackListModel::TYPE_EMAIL)->where('value', $email)->first();
    if (!empty($checkEmail)) {
        $checkBlacklist = $checkEmail;
    }

    if (empty($checkBlacklist)) {
        $checkDomain = $storeBlacklist->where('type', BlackListModel::TYPE_DOMAIN)
            ->whereIN('value', [$shopify_domain, $domain])->first();
        if (!empty($checkDomain)) {
            $checkBlacklist = $checkDomain;
        }
    }

    if (empty($checkBlacklist)) {
        $listPlans = $storeBlacklist->where('type', BlackListModel::TYPE_PLAN);
        if (!empty($listPlans)) {
            foreach ($listPlans as $item) {
                if (strpos($shopify_plan, $item->value) !== false) {
                    $checkBlacklist = $item;
                    break;
                }
            }
        }
    }

    if (empty($checkBlacklist)) {
        $lisKeywordEmail = $storeBlacklist->where('type', BlackListModel::TYPE_KEYWORD_EMAIL);
        if (!empty($lisKeywordEmail)) {
            foreach ($lisKeywordEmail as $item) {
                if (
                    strpos($email, $item->value) !== false
                ) {
                    $checkBlacklist = $item;
                    break;
                }
            }
        }
    }

    if (empty($checkBlacklist)) {
        $lisKeywordDomain = $storeBlacklist->where('type', BlackListModel::TYPE_KEYWORD_DOMAIN);
        if (!empty($lisKeywordDomain)) {
            foreach ($lisKeywordDomain as $item) {
                if (
                    strpos($shopify_domain, $item->value) !== false
                ) {
                    $checkBlacklist = $item;
                    break;
                }
            }
        }
    }

    if (empty($checkBlacklist)) {
        $lisKeywordName = $storeBlacklist->where('type', BlackListModel::TYPE_KEYWORD_NAME);
        if (!empty($lisKeywordName)) {
            foreach ($lisKeywordName as $item) {
                if (
                    strpos($store_name, $item->value) !== false
                ) {
                    $checkBlacklist = $item;
                    break;
                }
            }
        }
    }

    if (!empty($checkBlacklist)) {
        return [
            'category' => $checkBlacklist->category,
            'type' => $checkBlacklist->type,
            'value' => $checkBlacklist->value,
        ];
    }
    return [];


    // $checkDomain = $storeBlacklist->where('type', 'shopify_domain')->where('value', $shopify_domain);
    // if (!empty($checkDomain->count())) {
    //     $checkBlacklist = true;
    // } else {
    //     $checkPlan = $storeBlacklist->where('type', 'shopify_plan')->where('value', $shopify_plan);
    //     if (!empty($checkPlan->count())) {
    //         $checkBlacklist = true;
    //     } else {
    //         $checkEmail = $storeBlacklist->where('type', 'email')->where('value', $email);
    //         if (!empty($checkEmail->count())) {
    //             $checkBlacklist = true;
    //         } else {
    //             $emailArgs = explode('@', $email);
    //             if (!empty($emailArgs[1])) {
    //                 $domainEmail = '@' . $emailArgs[1];
    //             }
    //             $checkMailDomain = $storeBlacklist->where('type', 'email_domain')->where('value', $domainEmail);
    //             if (!empty($checkMailDomain->count())) {
    //                 $checkBlacklist = true;
    //             }
    //         }
    //     }
    // }

    // return $checkBlacklist;
}

function checkLoyalty($store)
{
    $loyalty = LoyaltyModel::where('store_id', $store->store_id)->first();
    $email = $store->email;
    if (!empty($loyalty->email)) {
        $email = $loyalty->email;
    }

    // nếu paid plan hoặc nằm trong blacklist thì tự động cho loyalty lun
    $checkBlacklist = checkBlacklist(
        $store->shopify_domain,
        $store->shopify_plan,
        $store->email,
        $store->name,
        $store->domain
    );
    if (!empty($checkBlacklist) || (!empty($store->app_plan) && $store->app_plan != "free")) {
        return [
            'quest_ext' => true,
            'quest_bundle' => true,
            'quest_review' => 5,
            'apply' => false,
            'email' => $email,
            'congratulations_status' => true,
            'loyalty' => true
        ];
    }

    if (!empty($loyalty)) {
        $loyaltyCheck = false;
        $criteriaStatus = false;
        if (!empty($loyalty->apply)) {
            $loyaltyCheck = true;
        }
        if (!empty($loyalty->force_loyalty)) {
            $loyaltyCheck = true;
            $criteriaStatus = true;
        }
        if (!empty($loyalty->quest_ext) && !empty($loyalty->quest_bundle) && !empty($loyalty->quest_review) && $loyalty->quest_review >= 3) {
            $loyaltyCheck = true;
            $criteriaStatus = true;
        }

        return [
            'quest_ext' => !empty($loyalty->quest_ext) ? true : false,
            'quest_bundle' => !empty($loyalty->quest_bundle) ? true : false,
            'quest_review' => $loyalty->quest_review,
            'apply' => !empty($loyalty->apply) ? true : false,
            'email' => $email,
            'congratulations_status' => $criteriaStatus,
            'loyalty' => $loyaltyCheck
        ];
    }


    return [
        'quest_ext' => false,
        'quest_bundle' => false,
        'quest_review' => false,
        'apply' => false,
        'email' => $email,
        'loyalty' => false,
        'congratulations_status' => false,
    ];
}


function checkTrialTool($store)
{
    $ti = false;
    // if ($store->shopify_plan == "trial" && empty($store->phone) && ($store->timezone == "Asia/Bangkok" || $store->timezone == "Asia/Ho_Chi_Minh")) {
    //     $ti = true;
    // }

    if ($store->shopify_plan == "trial" && empty($store->phone) && $store->country == "VN") {
        $ti = true;
    }
    return $ti;
}


function genRandomDiscountNumber()
{
    return  time() . substr(md5(microtime()), rand(0, 26), 2);
}


function migrateBundleSettings1vs2(array $oldSettings, array $settingsDefault)
{
    $newSettings  = array_merge($settingsDefault, $oldSettings);
    unset($newSettings['is_show_section']);
    unset($newSettings['is_show_modal']);
    unset($newSettings['is_show_section_cart']);
    unset($newSettings['number_of_recommended']);
    unset($newSettings['total_price_content']);
    unset($newSettings['gift_badge_content']);
    unset($newSettings['view_more_button_content']);
    unset($newSettings['variant_content']);
    unset($newSettings['style']);
    unset($newSettings['button_add_to_cart_text_color']);
    unset($newSettings['button_add_to_cart_bg_color']);
    unset($newSettings['button_buy_now_text_color']);
    unset($newSettings['button_buy_now_bg_color']);
    unset($newSettings['timer_content']);
    unset($newSettings['this_item_content']);

    return $newSettings;
}
