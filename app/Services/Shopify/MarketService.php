<?php

namespace App\Services\Shopify;

use App\Facade\SystemCache;
use App\Services\AbstractService;
use Illuminate\Support\Facades\Cache;
use GuzzleHttp\Client;

class MarketService extends AbstractService
{
    protected $shopifyApiService;
    protected $sentry;

    public function __construct()
    {
        $this->sentry = app('sentry');
        $this->storeRepository = app('repoFactory')->store();
    }

    /**
     * get primary market
     */
    public function getPrimaryMarket($storeId)
    {
        $this->setStatus(false);
        $this->setMessage(config('fa_messages.error.common'));
        try {
            $storeInfo = $this->storeRepository->detail($storeId);
            $graphqlParam['query'] = '{
                primaryMarket{
                            id,
                            name,
                            currencySettings 
                            {
                                baseCurrency
                                {
                                    currencyCode,
                                    currencyName,
                                    enabled,
                                    rateUpdatedAt
                                },
                                localCurrencies
                            },
                            enabled,
                            priceList {
                                contextRule {
                                    countries
                                },
                                currency,
                                id,
                                name,
                                parent {
                                    adjustment {
                                        type,
                                        value
                                    }
                                }
                            },
                            primary,
                            webPresence{
                                alternateLocales,
                                defaultLocale,
                                domain {
                                    host,
                                    id,
                                    localization{
                                        alternateLocales,
                                        country,
                                        defaultLocale
                                    },
                                    sslEnabled,
                                    url
                                },
                                id,
                                # market,
                                rootUrls{
                                    locale,
                                    url
                                },
                                subfolderSuffix
                            }
                        
                    }
                
            }';
            $result =  $this->postGraphQL($storeInfo, $graphqlParam);

            $data = [];
            if (!empty($result->data->primaryMarket)) {
                $data = $result->data->primaryMarket;
                $this->setStatus(true);
                $this->setData($data);
                $this->setMessage(config('fa_messages.success.common'));
            }
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }
        return $this;
    }


    /**
     * get market by country code
     */
    public function getMarketByLocation($storeId, $countryCode)
    {
        $this->setStatus(false);
        $this->setMessage(config('fa_messages.error.common'));
        try {
            $keyTag = config('fa_cache_keys.market.tags.paginate') . '_' . $storeId;
            $cacheKey = config('fa_cache_keys.market.keys.market_by_country_code') . $storeId . "_" . $countryCode;
            $cacheResult = Cache::tags($keyTag)->get($cacheKey);
            if (!empty($cacheResult)) {
                $this->setStatus(true);
                $this->setData($cacheResult);
                $this->setMessage(config('fa_messages.success.common'));
                return $this;
            }

            $storeInfo = $this->storeRepository->detail($storeId);
            $graphqlParam['query'] = 'query ($country: CountryCode!){
                marketByGeography(countryCode: $country){
                            id,
                            name,
                            currencySettings 
                            {
                                baseCurrency
                                {
                                    currencyCode,
                                    currencyName,
                                    enabled,
                                    rateUpdatedAt
                                },
                                localCurrencies
                            },
                            enabled,
                            priceList {
                                contextRule {
                                    countries
                                },
                                currency,
                                id,
                                name,
                                parent {
                                    adjustment {
                                        type,
                                        value
                                    }
                                }
                            },
                            primary,
                            webPresence{
                                alternateLocales,
                                defaultLocale,
                                domain {
                                    host,
                                    id,
                                    localization{
                                        alternateLocales,
                                        country,
                                        defaultLocale
                                    },
                                    sslEnabled,
                                    url
                                },
                                id,
                                # market,
                                rootUrls{
                                    locale,
                                    url
                                },
                                subfolderSuffix
                            }
                    }
            }';

            $graphqlParam['variables']['country'] = $countryCode;

            $result =  $this->postGraphQL($storeInfo, $graphqlParam);
            $data = [];
            if (!empty($result->data->marketByGeography)) {
                $data = $result->data->marketByGeography;
            }
            Cache::tags($keyTag)->put($cacheKey, $data, config('fa_cache_keys.default_cache_time'));
            $this->setStatus(true);
            $this->setData($data);
            $this->setMessage(config('fa_messages.success.common'));
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }
        return $this;
    }


    private function postGraphQL($storeInfo, $data)
    {
        $client = new Client();
        $url = "https://$storeInfo->shopify_domain/admin/api/" . config('fa_shopify.api_market_version') . "/graphql.json";
        $response = $client->request(
            'POST',
            $url,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $storeInfo->access_token,
                ],
                'body' => json_encode($data),
            ]
        );
        $result =  json_decode($response->getBody()->getContents());
        return $result;
    }

    public function getMarketFromShopify($params)
    {
        try {
            $storeInfo = $this->storeRepository->detail($params['store_id']);
            $graphqlParam['query'] = '
                query ($first: Int!, $cursor: String){
                    markets(first: $first, after: $cursor ){
                        pageInfo {
                            hasNextPage
                            hasPreviousPage
                        }
                        edges{
                            cursor,
                            node{
                                id,
                                name,
                                currencySettings 
                                {
                                    baseCurrency
                                    {
                                        currencyCode,
                                        currencyName,
                                        enabled,
                                        rateUpdatedAt
                                    },
                                    localCurrencies
                                },
                                enabled,
                                priceList {
                                    contextRule {
                                        countries
                                    },
                                    currency,
                                    id,
                                    name,
                                    parent {
                                        adjustment {
                                            type,
                                            value
                                        }
                                    }
                                },
                                primary,
                                webPresence{
                                    alternateLocales,
                                    defaultLocale,
                                    domain {
                                        host,
                                        id,
                                        localization{
                                            alternateLocales,
                                            country,
                                            defaultLocale
                                        },
                                        sslEnabled,
                                        url
                                    },
                                    id,
                                    # market,
                                    rootUrls{
                                        locale,
                                        url
                                    },
                                    subfolderSuffix
                                }
                            }
                        }
                    }
                }
            ';
            $graphqlParam['variables'] = [
                "first" => $params['first'],
            ];
            if (!empty($params['cursor'])) {
                $graphqlParam['variables']['cursor'] = $params['cursor'];
            }
            $response =  $this->postGraphQL($storeInfo, $graphqlParam);
            if (!empty($response->data->markets->edges)) {
                $data = $this->formatDataMarket($storeInfo->id, $response->data->markets->edges);
                $result = ['status' => true, 'data' => $data];
                if ($response->data->markets->pageInfo->hasNextPage) {
                    $result['cursor'] = end($response->data->markets->edges)->cursor;
                }
            } else {
                $result = ['status' => false, 'message' => $response->errors[0]->message];
            }
            return $result;
        } catch (\Exception $e) {
            $sentry = app('sentry');
            $sentry->captureException($e);
            return null;
        }
    }

    public function formatDataMarket($storeId, $data)
    {
        $market = [];
        $urlResource = 'gid://shopify/Market/';
        foreach ($data as $item) {
            $id = str_replace($urlResource, '', $item->node->id);
            $market[$id]['id'] = $id;
            $market[$id]['title'] = $item->node->name;
            $market[$id]['store_id'] = $storeId;
            $market[$id]['enabled'] = (bool)$item->node->enabled;
            $market[$id]['primary'] = (bool)$item->node->primary;
            $market[$id]['sub_forder'] = '';
            $market[$id]['domain'] = '';
            $market[$id]['adjustment_type'] = '';
            $market[$id]['adjustment_value'] = 0;
            $arrDetail = [];
            if (!empty($item->node->webPresence)) {
                $market[$id]['sub_forder'] = $item->node->webPresence->subfolderSuffix;
                $market[$id]['domain'] = !empty($item->node->webPresence->domain->host) ? $item->node->webPresence->domain->host : null;
                foreach ($item->node->webPresence->rootUrls as $detail) {
                    $value['store_id'] = $storeId;
                    $value['market_id'] = $id;
                    $value['url'] = $detail->url;
                    $value['locale'] = $detail->locale;
                    $convertLocale = config('fa_convert_language_translate.market_locale');
                    if (array_key_exists($detail->locale, $convertLocale)) {
                        $value['locale'] = $convertLocale[$detail->locale];
                    }
                    array_push($arrDetail, $value);
                }
                $market[$id]['detail'] = $arrDetail;
            }
            $market[$id]['locale_default'] = !empty($item->node->webPresence) ? $item->node->webPresence->defaultLocale : null;
            $market[$id]['locale_default'] = $market[$id]['locale_default'] == "fr-FR" ? 'fr' : $market[$id]['locale_default'];

            $market[$id]['currency_default'] = $item->node->currencySettings->baseCurrency->currencyCode;
            if (!empty($item->node->priceList)) {
                $market[$id]['adjustment_type'] = !empty($item->node->priceList->parent->adjustment->type) ? $item->node->priceList->parent->adjustment->type : 0;
                $market[$id]['adjustment_value'] = !empty($item->node->priceList->parent->adjustment->value) ? $item->node->priceList->parent->adjustment->value : 0;
            }
        }
        return $market;
    }
}
