<?php
namespace App\Services\Shopify;

use App\Facade\SystemCache;
use App\Services\AbstractService;

class LanguageService extends AbstractService
{
    protected $shopifyApiService;

    protected $sentry;

    public function __construct()
    {
        $this->sentry = app('sentry');
        $this->shopifyApiService = new ShopifyApiService();
    }


    /**
     * Get list locale of store
     */
    public function shopLocales(string $shopifyDomain, string $accessToken)
    {
        try {
            $this->shopifyApiService->setShopifyHeader($shopifyDomain, $accessToken);
            $graphqlParam['query'] = '
                {
                    shopLocales(published: false) {
                        locale
                        name
                        primary
                        published
                    }
                }
            ';
            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);



            if (isset($response->errors)) {
                $this->setStatus(false);
                $this->setMessage($response->errors[0]->message);
            } else if (!empty($response->data->shopLocales)) {
                $response->data->shopLocales = array_values($response->data->shopLocales);
                $this->setStatus(true);
                $this->setData($response);
                $this->setMessage(config('fa_messages.success.common'));
            }
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
            $this->setData([
                'error_code' => $exception->getCode(),
            ]);
            $this->setMessage($exception->getMessage());
        }
        return $this;
    }

    public function getTranslatableResources($domain, $token, $resourceIds, $callback = false){
        try{
            $this->shopifyApiService->setShopifyHeader($domain, $token);
            $resource = implode(',', $resourceIds);
            $graphqlParam['query'] = '
                {
                    translatableResourcesByIds(first:'.count($resourceIds).',resourceIds:  ['.$resource.' ]) {
                        edges {
                            node{
                                resourceId
                                translatableContent {
                                    key
                                    value
                                    digest
                                    locale
                                }
                            }
                        }
                    }
                }
            ';

            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);
            $currentPoint = ($response->extensions->cost->throttleStatus->currentlyAvailable);
            echo  "current point ". $currentPoint. "\n";
            if ($currentPoint < 300){
                if($currentPoint > 200){
                    sleep(2);
                } else {
                    sleep(5);
                }
            }
            $listResource = @$response->data->translatableResourcesByIds->edges;
            if (empty($listResource)){
//                SystemCache::addItemToList("a_debug_result", json_encode($response));
                $requestPoint = ($response->extensions->cost->requestedQueryCost);
                echo  "request point ". $requestPoint. "\n";
                if ($requestPoint > $currentPoint){
                    if ($callback){
                        return false;
                    } else{
                        sleep(15);
                    }
                    return $this->getTranslatableResources($domain, $token, $resourceIds, true);
                }

                return false;
            }
            $result = [];
            $listResource = json_decode(json_encode($listResource), true);
            foreach ($listResource as $line){
                $id = @$line['node']['resourceId'];
                if(empty($id)){
                    continue;
                }
                $result[$id] = $line['node']['translatableContent'];
            }
            return $result;
        } catch (\Exception $ex){
            $this->sentry->captureException($ex);
            return false;
        }

    }

    /**
     * @param $domain
     * @param $token
     * @param $resourceIds
     * @param $locale
     * @return array|false
     */

    public function getTranslateResourceForLocale($domain, $token, $resourceIds, $locale){
        try{
            $this->shopifyApiService->setShopifyHeader($domain, $token);
            $resource = implode(',', $resourceIds);
            $graphqlParam['query'] = '
                {
                    translatableResourcesByIds(first:'.count($resourceIds).',resourceIds:  ['.$resource.' ]) {
                        edges {
                            node{
                                resourceId
                                translatableContent {
                                    key
                                    value
                                    digest
                                    locale
                                }
                                translations(locale: "' . $locale . '") {
                                    value
                                    key
                                    locale
                                }

                            }
                        }
                    }
                }
            ';

            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);
            $listResource = @$response->data->translatableResourcesByIds->edges;
            if (empty($listResource)){
                return [];
            }
            $result = [];
            $listResource = json_decode(json_encode($listResource), true);
            foreach ($listResource as $line){
                $id = @$line['node']['resourceId'];
                if(empty($id)){
                    continue;
                }
                $contents = @$line['node']['translatableContent'];
                $listTranslate = [];
                $translations = @$line['node']['translations'];
                foreach ($translations as  $item){
                    $listTranslate[$item['key']] = $item['value'];
                }
                foreach ($contents as &$content){
                    $content['translation'] = @$listTranslate[$content['key']];
                }
                $result[$id] = $contents;
            }
            return $result;
        } catch (\Exception $ex){
            $this->sentry->captureException($ex);
            return false;
        }

    }

    /**
     *  get translatableResource for single id
     * @param $params
     * @param $convertData
     * @param $titles
     * @param $translationStatus
     * @param $pageType
     * @return
     */
    public function translatableResource($params, $convertData = false, $titles = [], $translationStatus = [], $pageType = "")
    {
        try {
            $this->shopifyApiService->setShopifyHeader($params['shopify_domain'], $params['access_token']);
            ///$params['locale']
            if (!empty($params['local'])) {
                $graphqlParam['query'] = '
                {
                    translatableResource(resourceId: "' . $params["resource_id"] . '") {
                        resourceId
                        translatableContent {
                            key
                            value
                            digest
                            locale
                        }
                        translations(locale: "' . $params["local"] . '") {
                            value
                            key
                            locale
                        }
                    }
                }
                ';
            } else {
                $graphqlParam['query'] = '
                {
                    translatableResource(resourceId:  "' . $params["resource_id"] . '") {
                        resourceId
                        translatableContent {
                            key
                            value
                            digest
                            locale
                        }
                    }
                }
                ';
            }

            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);
            if (!empty($response->data->translatableResource)) {
                $translatableResourceData = $response->data->translatableResource;
                if ($convertData) {
                    // $translatableResourceData = $this->convertDataSingleResource(
                    //     $translatableResourceData,
                    //     $titles,
                    //     $translationStatus,
                    //     $pageType,
                    //     $params['local']
                    // );
                }

                $this->setStatus(true);
                $this->setData($translatableResourceData);
                $this->setMessage(config('fa_messages.success.common'));
            }
        } catch (\Exception $ex){
            $this->setStatus(false);
            $this->setData([]);
            echo $ex->getMessage();
        }


        return $this;
    }

    /**
     * @param array $params
     * @return $this
     */
    public function singleTranslatableResource(array $params)
    {

        $this->shopifyApiService->setShopifyHeader($params['shopify_domain'], $params['access_token']);
        if (!empty($params['locale'])) {
            $graphqlParam['query'] = sprintf('
                {
                    translatableResource(resourceId: "%s") {
                        resourceId
                        translatableContent {
                            key
                            value
                            digest
                            locale
                        }
                        translations(locale: "%s") {
                            value
                            key
                            locale
                        }
                    }
                }
                ',$params["resource_id"],$params["locale"]);
        } else {
            $graphqlParam['query'] = sprintf('
                {
                    translatableResource(resourceId:  "%s") {
                        resourceId
                        translatableContent {
                            key
                            value
                            digest
                            locale
                        }
                    }
                }
                ',$params["resource_id"]);
        }
        $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);
        if (!empty($response->data->translatableResource)) {
            $translatableResourceData = $response->data->translatableResource;

            $this->setStatus(true);
            $this->setData($translatableResourceData);
            $this->setMessage(config('fa_messages.success.common'));
        }
        return $this;
    }


    /**
     * @param array $params
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTranslation(array $params)
    {
        $this->setStatus(false);
        $this->setMessage(config('fa_messages.error.common'));
        try {
            $this->shopifyApiService->setShopifyHeader($params['shopify_domain'], $params['access_token']);
            $graphqlParam['query'] = '
                mutation CreateTranslation($id: ID!, $translations: [TranslationInput!]!) {
                    translationsRegister(resourceId: $id, translations: $translations) {
                        userErrors {
                            message
                            field
                        }
                        translations {
                            locale
                            key
                            value
                        }
                    }
                }
            ';
            $graphqlParam['variables'] = [
                "id" => $params['resource_id'],
                "translations" => $params['translations'],
            ];
            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);

            if (!empty($response->data->translationsRegister->translations)) {
                $this->setStatus(true);
                $this->setData($response);
                $this->setMessage(config('fa_messages.success.common'));
            }
        } catch (\Exception $exception) {
            $this->setMessage($exception->getMessage());
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }
        return $this;
    }


    public function translatableResources(array $params)
    {
        $this->setStatus(false);
        $this->setMessage(config('fa_messages.error.common'));

        try {
            $this->shopifyApiService->setShopifyHeader($params['shopify_domain'], $params['access_token']);

            $graphqlParam['query'] = '
                query getTranslatableResources($first: Int!, $cursor: String, $resourceType: TranslatableResourceType!) {
                    translatableResources(first: $first, after: $cursor, resourceType: $resourceType) {
                        pageInfo {
                            hasNextPage
                            hasPreviousPage
                        }
                        edges {
                            cursor
                            node {
                                resourceId
                                translatableContent {
                                    key
                                    value
                                    digest
                                    locale
                                }
                            }
                        }
                    }
                }
            ';
            $graphqlParam['variables'] = [
                "first" => $params['first'],
                "resourceType" => $params['resourceType'],
            ];
            if (!empty($params['cursor'])) {
                $graphqlParam['variables']['cursor'] = $params['cursor'];
            }
            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);
            if (isset($response->errors)) {
                $this->setStatus(false);
                $this->setMessage($response->errors[0]->message);
            } else {
                $this->setStatus(true);
                $this->setData($response);
                $this->setMessage(config('fa_messages.success.common'));
            }
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
            $this->setStatus(false);
            $this->setMessage($exception->getMessage());
        }
        return $this;
    }

    public function removeTranslation($params)
    {
        $this->setStatus(false);
        $this->setMessage(config('fa_messages.error.common'));

        try {
            $this->shopifyApiService->setShopifyHeader($params['shopify_domain'], $params['access_token']);
            $graphqlParam['query'] = '
                mutation translationsRemove($resourceId: ID!, $translationKeys: [String!]!, $locales: [String!]!) {
                    translationsRemove(resourceId: $resourceId, translationKeys: $translationKeys, locales: $locales) {
                        translations {
                        key
                        locale
                        outdated
                        value
                    }
                    userErrors {
                        code
                        field
                        message
                    }
                }
            }
            ';
            $graphqlParam['variables'] = [
                "resourceId" => $params['id'],
                "translationKeys" => $params['translationKeys'],
                "locales" => $params['locales'],
            ];
            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);

            $this->setStatus(true);
            $this->setData($response);
            $this->setMessage(config('fa_messages.success.common'));
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }

        return $this;
    }

    /**
     * @param array $params
     * @return $this
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function createTranslationWebhook(array $params, $callback = false)
    {
        $response = '';
        try {
            $this->shopifyApiService->setShopifyHeader($params['shopify_domain'], $params['access_token']);
            $graphqlParam['query'] = '
                mutation CreateTranslation($id: ID!, $translations: [TranslationInput!]!) {
                    translationsRegister(resourceId: $id, translations: $translations) {
                        userErrors {
                            message
                            field
                        }
                        translations {
                            locale
                            key
                            value
                        }
                    }
                }
            ';
            $graphqlParam['variables'] = [
                "id" => $params['resource_id'],
                "translations" => $params['translations'],
            ];
            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);

            $response = json_decode(json_encode($response), true);
            $currentPoint = $response['extensions']['cost']['throttleStatus']['currentlyAvailable'];
            if ($currentPoint < 300){
                if($currentPoint > 200){
                    sleep(2);
                } else {
                    sleep(5);
                }
            }
            if (!empty(@$response['data']['translationsRegister']['translations'])) {
                return ['status' => true,'data' => $response['data']['translationsRegister']['translations']];
            }
            $requestPoint = $response['extensions']['cost']['requestedQueryCost'];

            if ($requestPoint > $currentPoint ){
                if ($callback){
                    return false;
                } else{
                    sleep(15);
                }
                return $this->createTranslationWebhook($params, true);
            }

            return ['status'=> false, 'message'=> $response];
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
            $this->sentry->captureMessage(json_encode([$params, $response]));

            return ['status' => false];

        }

    }

    public function enableLocale($shopifyDomain, $accessToken, $locale)
    {
        $this->setStatus(false);
        $this->setMessage(config('fa_messages.error.common'));

        try {
            $this->shopifyApiService->setShopifyHeader($shopifyDomain, $accessToken);
            $graphqlParam['query'] = '
                mutation enableLocale($locale: String!) {
                    shopLocaleEnable(locale: $locale) {
                        userErrors {
                            message
                            field
                        }
                        shopLocale {
                            locale
                            name
                            primary
                            published
                        }
                    }
                }
            ';
            $graphqlParam['variables'] = ["locale" => $locale];

            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);
            $this->setStatus(true);
            $this->setData($response);
            $this->setMessage(config('fa_messages.success.common'));
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }

        return $this;
    }


    public function updateLocale($shopifyDomain, $accessToken, $locale, $published)
    {
        $this->setStatus(false);
        $this->setMessage(config('fa_messages.error.common'));

        try {
            $this->shopifyApiService->setShopifyHeader($shopifyDomain, $accessToken);
            $graphqlParam['query'] = '
                    mutation updateLocale($locale: String!, $published: ShopLocaleInput!) {
                        shopLocaleUpdate(locale: $locale, shopLocale: $published) {
                            userErrors {
                                message
                                field
                            }
                            shopLocale {
                                name
                                locale
                                primary
                                published
                            }
                        }
                    }
                ';
            $graphqlParam['variables'] = [
                "locale" => $locale,
                "published" => [
                    "published" => $published,
                ],
            ];

            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);
            $this->setStatus(true);
            $this->setData($response);
            $this->setMessage(config('fa_messages.success.common'));
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }

        return $this;
    }


    public function deleteLocale($shopifyDomain, $accessToken, $locale)
    {
        $this->setStatus(false);
        $this->setMessage(config('fa_messages.error.common'));

        try {
            $this->shopifyApiService->setShopifyHeader($shopifyDomain, $accessToken);
            $graphqlParam['query'] = '
                    mutation shopLocaleDisable($locale: String!) {
                        shopLocaleDisable(locale: $locale) {
                            locale
                            userErrors {
                              field
                              message
                            }
                        }
                    }
                ';
            $graphqlParam['variables'] = [
                "locale" => $locale,
            ];

            $response = $this->shopifyApiService->post('graphql.json', $graphqlParam);
            $this->setStatus(true);
            $this->setData($response);
            $this->setMessage(config('fa_messages.success.common'));
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            $this->setSentryId($sentryId);
        }

        return $this;
    }
}
