<?php


namespace App\Services\Shopify;


use GuzzleHttp\Client;

class ShopifyApiService
{
    public $shopifyDomain;
    public $accessToken;
    protected $sentry;

    /**
     * @param string $shopifyDomain
     * @param string $accessToken
     */
    public function setShopifyHeader(string $shopifyDomain, string $accessToken)
    {
        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
        $this->sentry = app('sentry');
    }


    /**
     * @param string $url
     * @param array $data
     * @param string $responseType
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function get(string $url, array $data = [], $responseType = 'content')
    {
        $client = new Client();
        $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, config('tf_shopify.api_version'), $url);
        $response = $client->request(
            'GET',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->accessToken,
                ],
                'query' => $data,
            ]
        );

        if ($responseType == 'content') {
            return json_decode($response->getBody()->getContents());
        } else {
            return $response;
        }
    }


    /**
     * @param string $url
     * @param array $data
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function post(string $url, array $data = [], $apiVersion = null)
    {
        $version = config('tf_shopify.api_version');
        if (!empty($apiVersion)) {
            $version = $apiVersion;
        }
        try {
            $client = new Client();
            $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, $version, $url);
            $response = $client->request(
                'POST',
                $uri,
                [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'X-Shopify-Access-Token' => $this->accessToken,
                    ],
                    'body' => json_encode($data),
                ]
            );
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
            return false;
        }

        return json_decode($response->getBody()->getContents());
    }


    /**
     * @param string $url
     * @param array $data
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function put(string $url, array $data = [])
    {
        $client = new Client();
        $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, config('tf_shopify.api_version'), $url);
        $response = $client->request(
            'PUT',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->accessToken,
                ],
                'body' => json_encode($data),
            ]
        );

        return json_decode($response->getBody()->getContents());
    }


    /**
     * @param string $url
     * @return mixed
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function drop(string $url)
    {
        $client = new Client();
        $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, config('tf_shopify.api_version'), $url);
        $response = $client->request(
            'DELETE',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->accessToken,
                ],
            ]
        );

        return json_decode($response->getBody()->getContents());
    }

    /**
     * Api lấy danh sách products từ shopify : https://help.shopify.com/en/api/reference/products/product
     *
     * @param $shopifyDomain
     * @param $accessToken
     * @param array $field
     * @param array $filters
     * @param int $limit
     *
     * @return $this
     */
    public function shopifyApiGetProducts(
        array $field = [],
        array $filters = [],
        int $limit = 250
    ) {

        try {
            $field = implode(',', $field);
            $data = [
                'limit' => $limit,
            ];
            if (!empty($field)) {
                $data['fields'] = $field;
            }
            if (!empty($filters['page_info'])) {
                $data['page_info'] = urldecode($filters['page_info']);
            }
            if (!empty($filters['title'])) {
                $data['title'] = $filters['title'];
            }
            if (!empty($filters['collection_id'])) {
                $data['collection_id'] = $filters['collection_id'];
            }

            $response = $this->get('products.json', $data, 'all');

            if ($response) {
                $page_info = "";
                $link = $response->getHeader('Link');
                if (!empty($link)) {
                    $link = array_shift($link);
                    $re = '/page_info=(.*?)>;/';

                    preg_match_all($re, $link, $matches, PREG_SET_ORDER, 0);
                    $listCheck = [];
                    if (!empty($matches)) {
                        foreach ($matches as $match) {
                            $page_info = $match[1];
                            $listCheck[] = $match[1];
                        }
                        if (!empty($filters['page_info']) && count($listCheck) == 1) {
                            echo "reset page info" . "\n";
                            $page_info = "";
                        }
                    }
                }

                $products = json_decode($response->getBody()->getContents());

                return  ['products' => $products->products, 'page_info' => $page_info];
            }
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
            return  false;
        }
    }

    public function syncMetaResources($domain, $token, $page)
    {
        try {
            $this->setShopifyHeader($domain, $token);
            if (empty($page)) {
                $graphqlParam['query'] = '
                {
                    products(first: 25, after:null) {
                        edges {
                            cursor
                            node {
                                id
                                seo {
                                    description
                                    title
                                }
                            }
                        }
                    }

                }
            ';
            } else {
                $graphqlParam['query'] = '
                {
                    products(first: 25, after:"' . $page . '") {
                        edges {
                            cursor
                            node {
                                id
                                seo {
                                    description
                                    title
                                }
                            }
                        }
                    }
                }
            ';
            }

            $response = $this->post('graphql.json', $graphqlParam);
            $availablePoint =  @$response->extensions->cost->throttleStatus->currentlyAvailable;
            if (!empty($availablePoint) && $availablePoint < 300) {
                sleep(2);
            }



            $listResource = @$response->data->products->edges;
            if (empty($listResource)) {
                return ['status' => true, 'data' => [], 'cursor' => null];
            }
            $result = [];
            $cursor = null;
            $listResource = json_decode(json_encode($listResource), true);
            foreach ($listResource as $line) {
                $cursor = $line['cursor'];
                $id = @$line['node']['id'];
                if (empty($id)) {
                    continue;
                }

                $seoInfo = @$line['node']['seo'];
                if (empty(array_filter(array_values($seoInfo)))) {
                    continue;
                }
                $id = $this->parseProductId($id);
                $result[$id] = [
                    'meta_tile' => $seoInfo['title'],
                    'meta_description' => $seoInfo['description']
                ];
            }
            return ['status' => true, 'data' => $result, 'cursor' => $cursor];
        } catch (\Exception $ex) {
            $this->sentry->captureException($ex);
            return ['status' => false];
        }
    }

    private function parseProductId($id)
    {
        $list = array_reverse(explode('/', $id));
        return $list[0];
    }

    public  function getThemeActive($shopifyDomain, $accessToken)
    {
        $themeActive = null;
        $this->setShopifyHeader($shopifyDomain, $accessToken);
        $allThemes = $this->get('themes.json');
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

    /**
     * @param string $url
     * @param array $data
     * @param string $responseType
     * @return mixed|\Psr\Http\Message\ResponseInterface
     * @throws \GuzzleHttp\Exception\GuzzleException
     */
    public function getWithPageInfo(string $url, array $data = [],)
    {
        $client = new Client();

        $uri = sprintf("https://%s/admin/api/%s/%s", $this->shopifyDomain, config('tf_shopify.api_version'), $url);
        $response = $client->request(
            'GET',
            $uri,
            [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'X-Shopify-Access-Token' => $this->accessToken,
                ],
                'query' => $data,
            ]
        );
        $link = $response->getHeader('Link');
        $pageInfo = '';
        if (!empty($link)) {
            $link = array_shift($link);
            $re = '/page_info=(.*?)>;/';

            preg_match_all($re, $link, $matches, PREG_SET_ORDER, 0);
            $listCheck = [];
            if (!empty($matches)) {
                foreach ($matches as $match) {
                    $pageInfo = $match[1];
                    $listCheck[] = $match[1];
                }
                if (!empty($data['page_info']) && count($listCheck) == 1) {

                    dump("reset page info" . "\n");
                    $pageInfo = "";
                }
            }
        }
        $dataResult = [
            'data' => json_decode($response->getBody()->getContents(), true),
            "page_info" => $pageInfo
        ];

        return $dataResult;
    }

    public function setDataMetafieldStoreFront(array $params)
    {

        $graphqlParam['query'] = 'mutation metafieldsSet($metafields: [MetafieldsSetInput!]!) {
            metafieldsSet(metafields: $metafields) {
              metafields {
                # Metafield fields
                id
                legacyResourceId
                key
                namespace
                value
                createdAt
                updatedAt
              }
              userErrors {
                code
                field
                message

              }
            }
          }';

        $graphqlParam['variables'] = [
            "metafields" => $params,

        ];
        $response = $this->post('graphql.json', $graphqlParam);
        return $response;
    }
}
