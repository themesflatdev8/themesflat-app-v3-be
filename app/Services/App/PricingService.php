<?php

namespace App\Services\App;

use App\Jobs\DeactiveDiscountJob;
use App\Models\BundlesModel;
use App\Models\StoreModel;
use App\Models\StoreTestModel;
use App\Services\AbstractService;
use App\Services\Shopify\ShopifyApiService;
use Carbon\Carbon;
use Exception;

class PricingService extends AbstractService
{
    protected $shopifyApiService;
    protected $sentry;

    public function __construct(
        ShopifyApiService $shopifyApiService,
    ) {
        $this->shopifyApiService = $shopifyApiService;
        $this->sentry = app('sentry');
    }

    public function chargeAdd($storeInfo, $plan, $redirectUrl)
    {
        $storeInfo = [
            'id' => $storeInfo->store_id,
            'shopify_domain' => $storeInfo->shopify_domain,
            'access_token' => $storeInfo->access_token,
            'trial_days' => $storeInfo->trial_days,
            'trial_on' => $storeInfo->trial_on
        ];

        $planInfo = config('fa_plans')[$plan];
        $dataCharge = $this->generateDataChargeGraphql($planInfo, $storeInfo, $redirectUrl);
        if (!empty($dataCharge['redirect_url'])) {
            $this->setData(['redirect_url' => $dataCharge['redirect_url']]);

            return $this;
        }
        $this->shopifyApiService->setShopifyHeader($storeInfo['shopify_domain'], $storeInfo['access_token']);
        $addCharge = $this->shopifyApiService->post('graphql.json', $dataCharge);

        $this->setData(['redirect_url' => $addCharge->data->appSubscriptionCreate->confirmationUrl]);

        return $this;
    }

    private function generateDataChargeGraphql($planInfo, $storeInfo, $redirectUrl)
    {
        $interval = 'EVERY_30_DAYS';
        $chargeTitle = config('fa_plans')[$planInfo['name']]['title'];
        $returnUrl = route(
            'charge.callback',
            [
                'store_id' => $storeInfo['id'],
                'shopify_domain' => $storeInfo['shopify_domain'],
                'plan' => $planInfo['name'],
                'redirect_url' => $redirectUrl,
                // 'interval' => $interval
            ]
        );

        // dd($returnUrl);
        $price = $planInfo['price']; // gia duoc ap dung cho user moi

        $test = false;
        $testCheck = StoreTestModel::where('store_id', $storeInfo['id'])->first();
        if (!empty($testCheck)) {
            $test = true;
        }
        $trialDays = getTrialDays($storeInfo);

        switch ($planInfo['name']) {
            case "free":
                // $dataCharge =  [
                //     'query' => 'mutation AppSubscriptionCreate($name: String!, $lineItems: [AppSubscriptionLineItemInput!]!, $returnUrl: URL!, $test: Boolean!, $trialDays: Int!) { appSubscriptionCreate(name: $name, returnUrl: $returnUrl, test: $test, lineItems: $lineItems, trialDays: $trialDays) { userErrors { field message } appSubscription { id } confirmationUrl } }',
                //     'variables' => [
                //         "name" => $chargeTitle,
                //         "returnUrl" => $returnUrl,
                //         "lineItems" => [
                //             "plan" => [
                //                 "appUsagePricingDetails" => [
                //                     "cappedAmount" => [
                //                         "amount" => "3000",
                //                         "currencyCode" => "USD"
                //                     ],
                //                     "terms" => "\$" . $planInfo['used_charge'] . " per order"
                //                 ]
                //             ],

                //         ],
                //         "test" => $test,
                //         "trialDays" => $trialDays
                //     ]
                // ];
                if (!empty($redirectUrl)) {
                    $feRedirectUrl = 'https://' . $storeInfo['shopify_domain'] . '/admin/apps/' . env('EMBEDDED_APP_NAME') . '?' . $redirectUrl;
                } else {
                    $feRedirectUrl = 'https://' . $storeInfo['shopify_domain'] . '/admin/apps/' . env('EMBEDDED_APP_NAME');
                }
                $dataSave = [
                    'billing_id' =>  null,
                    // 'billing_on' => isset($activeCharge->billing_on) ? $activeCharge->billing_on : null,
                    'app_plan' => 'free',
                    'trial_on' => date('Y-m-d H:i:s'),
                    'trial_days' => getTrialDays($storeInfo),
                    // 'show_popup_ungrateful' => 0,
                    // 'pricing_version' => config('fa_common.pricing_version'),
                ];

                $listDiscounts = BundlesModel::where('store_id', $storeInfo['id'])->where('useDiscount', 1)->get();
                BundlesModel::where('store_id', $storeInfo['id'])->where('useDiscount', 1)->update(['useDiscount' => 0]);
                dispatch(new DeactiveDiscountJob($storeInfo['id'], $storeInfo['shopify_domain'], $storeInfo['access_token']));

                StoreModel::where('store_id', $storeInfo['id'])->update($dataSave);
                // return redirect($feRedirectUrl);
                return [
                    'redirect_url' => $feRedirectUrl,
                ];
            case "essential":
                // $dataCharge =  [
                //     'query' => 'mutation AppSubscriptionCreate($name: String!, $lineItems: [AppSubscriptionLineItemInput!]!, $returnUrl: URL!, $test: Boolean!, $trialDays: Int!) { appSubscriptionCreate(name: $name, returnUrl: $returnUrl, test: $test, lineItems: $lineItems, trialDays: $trialDays) { userErrors { field message } appSubscription { id } confirmationUrl } }',
                //     'variables' => [
                //         "name" => $chargeTitle,
                //         "returnUrl" => $returnUrl,
                //         "lineItems" => [
                //             [
                //                 "plan" => [
                //                     "appRecurringPricingDetails" => [
                //                         "price" => [
                //                             "amount" => $price,
                //                             "currencyCode" => "USD"
                //                         ],
                //                         "interval" => $interval
                //                     ]
                //                 ]
                //             ],
                //             [
                //                 "plan" => [
                //                     "appUsagePricingDetails" => [
                //                         "cappedAmount" => [
                //                             "amount" => "3000",
                //                             "currencyCode" => "USD"
                //                         ],
                //                         "terms" => "\$" . $planInfo['used_charge'] . " per order"
                //                     ]
                //                 ]
                //             ]
                //         ],
                //         "test" => $test,
                //         "trialDays" => $trialDays
                //     ]
                // ];
                $dataCharge =  [
                    'query' => 'mutation AppSubscriptionCreate($name: String!, $lineItems: [AppSubscriptionLineItemInput!]!, $returnUrl: URL!, $test: Boolean!, $trialDays: Int!) { appSubscriptionCreate(name: $name, returnUrl: $returnUrl, test: $test, lineItems: $lineItems, trialDays: $trialDays) { userErrors { field message } appSubscription { id } confirmationUrl } }',
                    'variables' => [
                        "name" => $chargeTitle,
                        "returnUrl" => $returnUrl,
                        "lineItems" => [
                            "plan" => [
                                "appRecurringPricingDetails" => [
                                    "price" => [
                                        "amount" => $price,
                                        "currencyCode" => "USD"
                                    ],
                                    "interval" => $interval
                                ]
                            ],

                        ],
                        "test" => $test,
                        "trialDays" => $trialDays
                    ]
                ];
                break;
            case "premium":
                $dataCharge =  [
                    'query' => 'mutation AppSubscriptionCreate($name: String!, $lineItems: [AppSubscriptionLineItemInput!]!, $returnUrl: URL!, $test: Boolean!, $trialDays: Int!) { appSubscriptionCreate(name: $name, returnUrl: $returnUrl, test: $test, lineItems: $lineItems, trialDays: $trialDays) { userErrors { field message } appSubscription { id } confirmationUrl } }',
                    'variables' => [
                        "name" => $chargeTitle,
                        "returnUrl" => $returnUrl,
                        "lineItems" => [
                            "plan" => [
                                "appRecurringPricingDetails" => [
                                    "price" => [
                                        "amount" => $price,
                                        "currencyCode" => "USD"
                                    ],
                                    "interval" => $interval
                                ]
                            ],

                        ],
                        "test" => $test,
                        "trialDays" => $trialDays
                    ]
                ];
                break;
        }


        if (!empty($discount)) {
            $dataCharge['variables']['lineItems']['plan']['appRecurringPricingDetails']['discount'] = $discount;
        }
        // dd($dataCharge);
        return $dataCharge;
    }
}
