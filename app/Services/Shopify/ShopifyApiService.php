<?php


namespace App\Services\Shopify;

use App\Models\ResponseModel;
use Google\Service\Docs\Response;
use Illuminate\Support\Facades\Http;
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
        return [];

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
    protected function clean($val)
    {
        return ($val !== null && $val !== 'null' && $val !== '') ? $val : null;
    }
    protected function buildQueryUrl($base, $params = [])
    {
        $params = array_filter($params, fn($v) => $this->clean($v) !== null);
        return $base . '?' . http_build_query($params);
    }

    public function getApiProduct($domain, $accessToken, $data)
    {
        try {
            $page = intval($data['page'] ?? 1);
            $limit = 12;

            $apiVersion = config('tf_shopify.api_version');
            $apiUrl = "https://{$domain}/admin/api/{$apiVersion}/graphql.json";

            $afterCursor = $data['after'] ?? null;
            $querySearch = [];

            if (!empty($data['vendor'])) {
                $querySearch[] = 'vendor:' . $data['vendor'];
            }
            if (!empty($data['query'])) {
                $querySearch[] = $data['query'];
            }
            if (!empty($data['collection'])) {
                $querySearch[] = 'collection_id:' . $data['collection'];
            }

            $graphqlQuery = <<<'GQL'
                query getProducts($first: Int!, $after: String, $query: String) {
                products(first: $first, after: $after, query: $query) {
                    edges {
                    cursor
                    node {
                        id
                        handle
                        title
                        vendor
                        descriptionHtml
                        totalInventory
                        variants(first: 10) {
                        edges {
                            node {
                            id
                            title
                            availableForSale
                            inventoryQuantity


                            selectedOptions {
                                name
                                value
                            }
                            }
                        }
                        }
                    }
                    }
                    pageInfo {
                    hasNextPage
                    hasPreviousPage
                    }
                }
                }
        GQL;

            $variables = [
                'first' => $limit,
                'after' => $afterCursor,
                'query' => !empty($querySearch) ? implode(" ", $querySearch) : null,
            ];

            $res = Http::withHeaders([
                'X-Shopify-Access-Token' => $accessToken,
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                'query' => $graphqlQuery,
                'variables' => $variables,
            ]);

            if ($res->failed()) {
                return response()->json(['error' => 'GraphQL API error'], $res->status());
            }

            $result = $res->json();
            $edges = $result['data']['products']['edges'] ?? [];
            $pageInfo = $result['data']['products']['pageInfo'] ?? [];

            $products = collect($edges)->map(function ($edge) {
                $p = $edge['node'];
                return [
                    'handle' => $p['handle'],
                    'title'  => $p['title'],
                    'vendor' => $p['vendor'],
                    'variants' => collect($p['variants']['edges'])->map(fn($v) => $v['node'])->toArray(),
                    'cursor' => $edge['cursor'],
                ];
            })->toArray();

            return [
                'products' => $products,
                'pagination' => [
                    'hasNextPage' => $pageInfo['hasNextPage'] ?? false,
                    'hasPrevPage' => $pageInfo['hasPreviousPage'] ?? false,
                    'nextPageUrl' => ($pageInfo['hasNextPage'] ?? false)
                        ? $this->buildQueryUrl('/api-products', array_merge($data, [
                            'page' => $page + 1,
                            'after' => end($edges)['cursor'] ?? null,
                        ]))
                        : null,
                    'prevPageUrl' => $page > 1
                        ? $this->buildQueryUrl('/api-products', array_merge($data, [
                            'page' => $page - 1,
                            'after' => null, // Shopify không cho prev cursor dễ, thường phải tự lưu
                        ]))
                        : null,
                ]
            ];
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }
        return null;
    }


    protected function getProductSales($domain, $accessToken, $productId)
    {
        $apiVersion = config('tf_shopify.api_version');
        $url = "https://{$domain}/admin/api/{$apiVersion}/orders.json?product_id={$productId}&status=any";

        $res = Http::withHeaders([
            'X-Shopify-Access-Token' => $accessToken,
            'Content-Type' => 'application/json',
        ])->get($url);

        if ($res->failed()) {
            return 0;
        }

        $data = $res->json();
        $totalSales = 0;
        foreach ($data['orders'] ?? [] as $order) {
            foreach ($order['line_items'] ?? [] as $item) {
                if ($item['product_id'] == $productId) {
                    $totalSales += $item['quantity'];
                }
            }
        }
        return $totalSales;
    }

    public function getProductNewest()
    {
        $shopDomain = $this->shopifyDomain;
        $apiName = 'getProductNewest';
        $param = md5('default'); // có thể thêm tham số khác nếu cần

        // 🔹 1. Kiểm tra cache
        $cache = ResponseModel::where('shop_domain', $shopDomain)
            ->where('api_name', $apiName)
            ->where('param', $param)
            ->where('expire_time', '>', now())
            ->first();

        if ($cache) {
            // Trả về cache đã có
            return json_decode($cache->response, true);
        }

        // 🔹 2. Gọi Shopify API
        $apiVersion = config('tf_shopify.api_version');
        $url = "https://{$this->shopifyDomain}/admin/api/{$apiVersion}/graphql.json";
        $query = <<<GQL
            {
            products(first: 10, sortKey: CREATED_AT, reverse: true) {
                edges {
                node {
                    id
                    title
                    createdAt
                    status
                    handle
                    onlineStoreUrl
                }
                }
            }
            }
            GQL;

        $response = \Illuminate\Support\Facades\Http::withHeaders([
            'X-Shopify-Access-Token' => $this->accessToken,
            'Content-Type' => 'application/json',
        ])->post($url, [
            'query' => $query,
        ]);

        $edges = $response->json('data.products.edges') ?? [];

        // 🔹 3. Lưu vào DB cache
        ResponseModel::updateOrCreate(
            [
                'shop_domain' => $shopDomain,
                'api_name' => $apiName,
                'param' => $param,
            ],
            [
                'response' => json_encode($edges),
                'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)), // cấu hình trong .env
            ]
        );

        return $edges;
    }


    public function getProductByCategory($collectionId)
    {
        try {
            $apiVersion = config('tf_shopify.api_version');
            $url = "https://{$this->shopifyDomain}/admin/api/{$apiVersion}/graphql.json";
            $query = <<<GQL
            {
            collection(id: "gid://shopify/Collection/{$collectionId}") {
                title
                products(first: 10, reverse: true) {
                        edges {
                            node {
                            id
                            title
                            handle
                            createdAt
                            }
                        }
                        }
                    }
                }
            GQL;
            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($url, ['query' => $query]);
            return $response->json('data.collection.products.edges');
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return [];
    }

    public function getProductsInfo($productIds)
    {
        try {
            $apiVersion = config('tf_shopify.api_version');

            $ids = array_map(function ($id) {
                return "gid://shopify/Product/{$id}";
            }, $productIds);

            $query = <<<'GRAPHQL'
                query($ids: [ID!]!) {
                    nodes(ids: $ids) {
                        ... on Product {
                        id
                        handle
                        title
                        }
                    }
                }
            GRAPHQL;

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post("https://{$this->shopifyDomain}/admin/api/{$apiVersion}/graphql.json", [
                'query' => $query,
                'variables' => [
                    'ids' => $ids,
                ],
            ]);

            if ($response->failed()) {
                throw new \Exception("Shopify API error: " . $response->body());
            }

            $data = $response->json();

            return collect($data['data']['nodes'])
                ->filter() // bỏ null
                ->map(function ($node) {
                    return [
                        'id'     => str_replace('gid://shopify/Product/', '', $node['id']),
                        'handle' => $node['handle'],
                        'title'  => $node['title'],
                    ];
                })
                ->values()
                ->all();
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }
        return  [];
    }

    public function getProductVariantsByIds($variantIds)
    {
        try {
            $apiVersion = config('tf_shopify.api_version');

            if (empty($variantIds)) {
                return [];
            }

            $query = <<<'GRAPHQL'
                query($ids: [ID!]!) {
                    nodes(ids: $ids) {
                        ... on ProductVariant {
                            id
                            title
                            sku
                            inventoryQuantity
                            image {
                                id
                                originalSrc
                                altText
                            }
                            product {
                                id
                                handle
                                title
                                onlineStoreUrl
                                featuredImage  {
                                    id
                                    originalSrc
                                    altText
                                }

                            }
                        }
                    }
                }
            GRAPHQL;
            $data = [];
            try {
                $response = Http::withHeaders([
                    'X-Shopify-Access-Token' => $this->accessToken,
                    'Content-Type' => 'application/json',
                ])->post("https://{$this->shopifyDomain}/admin/api/{$apiVersion}/graphql.json", [
                    'query' => $query,
                    'variables' => [
                        'ids' => $variantIds,
                    ],
                ]);

                if ($response->failed()) {
                    throw new \Exception("Shopify API error: " . $response->body());
                }

                $data = $response->json();
            } catch (\Exception $ex) {
                $this->sentry->captureException($ex);
                return [];
            }

            if (empty($data['data']['nodes'])) {
                return [];
            }

            return collect($data['data']['nodes'])
                ->filter() // bỏ null
                ->map(function ($node) {
                    return [
                        'id'                => str_replace('gid://shopify/ProductVariant/', '', $node['id']),
                        'title'             => $node['title'],
                        'sku'               => $node['sku'],
                        'inventoryQuantity' => $node['inventoryQuantity'],
                        'image'             => $node['image'] ? [
                            'id'          => str_replace('gid://shopify/ProductImage/', '', $node['image']['id']),
                            'originalSrc' => $node['image']['originalSrc'],
                            'altText'     => $node['image']['altText'],
                        ] : null,
                        'product'           => $node['product'] ? [
                            'id'             => str_replace('gid://shopify/Product/', '', $node['product']['id']),
                            'handle'         => $node['product']['handle'],
                            'title'          => $node['product']['title'],
                            'onlineStoreUrl' => $node['product']['onlineStoreUrl'],
                            'featuredImage'  => $node['product']['featuredImage'] ? [
                                'id'          => str_replace('gid://shopify/ProductImage/', '', $node['product']['featuredImage']['id']),
                                'originalSrc' => $node['product']['featuredImage']['originalSrc'],
                                'altText'     => $node['product']['featuredImage']['altText'],
                            ] : null,
                        ] : null,
                    ];
                })
                ->values()
                ->all();
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }
        return  [];
    }
    public function getVariantsByIds(array $variantIds): ?array
    {
        try {
            if (empty($variantIds)) {
                return [];
            }

            $apiVersion = config('tf_shopify.api_version');
            $apiUrl = "https://{$this->shopifyDomain}/admin/api/{$apiVersion}/graphql.json";

            $graphqlQuery = <<<'GQL'
                query getVariants($ids: [ID!]!) {
                    nodes(ids: $ids) {
                        ... on ProductVariant {
                            id
                            title
                            sku
                            availableForSale
                            inventoryQuantity
                            price
                            compareAtPrice
                            product {
                                id
                                title
                                handle
                            }
                            selectedOptions {
                                name
                                value
                            }
                        }
                    }
                }
            GQL;

            $variables = [
                'ids' => $variantIds, // phải truyền đúng định dạng "gid://shopify/ProductVariant/123456789"
            ];

            $response = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                'query' => $graphqlQuery,
                'variables' => $variables,
            ]);

            if ($response->failed()) {
                return [];
            }

            $data = $response->json();
            return $data['data']['nodes'] ?? [];
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }
        return [];
    }

    public function getProducts($param)
    {
        try {
            $limit = (int) $param['limit'] ?? 10;
            $keyword = $param['keyword'] ?? null; // 👉 keyword cần search
            $afterCursor = $param['cursor'] ?? null;

            $apiVersion = config('tf_shopify.api_version');
            $apiUrl = "https://{$this->shopifyDomain}/admin/api/{$apiVersion}/graphql.json";

            // 👉 Tạo query search
            $querySearch = [];

            if (!empty($keyword)) {
                // search theo title, vendor, tag, sku (tùy bạn chọn)
                $keyword = trim($keyword);
                $querySearch[] = "(title:*{$keyword}* OR vendor:*{$keyword}* OR tag:*{$keyword}* OR sku:*{$keyword}*)";
            }

            $graphqlQuery = <<<'GQL'
            query getProducts($first: Int!, $after: String, $query: String) {
                products(first: $first, after: $after, query: $query) {
                    edges {
                        cursor
                        node {
                            id
                            handle
                            title
                            vendor
                            descriptionHtml
                            totalInventory
                            images(first: 5) {
                                edges {
                                    node {
                                        id
                                        originalSrc
                                        altText
                                    }
                                }
                            }

                        }
                    }
                    pageInfo {
                        hasNextPage
                        hasPreviousPage
                    }
                }
            }
        GQL;

            $variables = [
                'first' => $limit,
                'after' => $afterCursor,
                'query' => !empty($querySearch) ? implode(' ', $querySearch) : null,
            ];

            $res = Http::withHeaders([
                'X-Shopify-Access-Token' => $this->accessToken,
                'Content-Type' => 'application/json',
            ])->post($apiUrl, [
                'query' => $graphqlQuery,
                'variables' => $variables,
            ]);

            if ($res->failed()) {
                return response()->json([
                    'error' => 'GraphQL API error',
                    'message' => $res->body(),
                ], $res->status());
            }
            $result = $res->json();
            $edges = @$result['data']['products'] ?: [];
            return $edges;
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
