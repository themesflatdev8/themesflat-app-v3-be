<?php


namespace App\Services\Shopify;

use Error;
use GuzzleHttp\Client;

class ShopifyService extends  BaseShopifyService
{

    public function __construct($shopifyDomain = '', $accessToken = '')
    {
        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
        $this->sentry = app('sentry');
    }
    /**
     * Get list locale of store
     */
    public function shopLocales($shopifyDomain = '', $accessToken = '')
    {
        if ($shopifyDomain && $accessToken) {
            $this->setShopifyHeader($shopifyDomain, $accessToken);
        }

        try {
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
            $response = $this->post('graphql.json', $graphqlParam);


            if (isset($response->errors)) {
                $result = ['status' => false, 'message' => $response->errors[0]->message];
            } else if (!empty($response->data->shopLocales)) {
                $data = (array)array_values($response->data->shopLocales);
                $result = ['status' => true, 'data' => $data, 'message' => config('fa_messages.success.common')];
            }
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
            $result = ['status' => false, 'message' => @$response->errors[0]->message];
        }
        return $result;
    }

    public function removeProductTranslation($resourceId, $elements, $locales)
    {

        try {
            $listLocale = [];
            foreach ($locales as $locale) {
                $listLocale[] = '"' . $locale . '"';
            }
            $listLocale = implode(',', $listLocale);
            $listEle = [];
            foreach ($elements as $item) {
                $listEle[] = '"' . $item . '"';
            }

            $listEle = implode(',', $listEle);

            $query = '
            mutation translationsRemove {
                translationsRemove(resourceId: "' . $resourceId . '", translationKeys: [' . $listEle . '], locales: [' . $listLocale . ']) {
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
            }';
            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            $currentPoint = ($response->extensions->cost->throttleStatus->currentlyAvailable);
            echo  "current point request delete " . $currentPoint . "\n";
            if ($currentPoint < 300) {
                if ($currentPoint > 200) {
                    sleep(2);
                } else {
                    sleep(5);
                }
            }
            $resultRemove = $response->data->translationsRemove->translations;
            if ($resultRemove) {
                $resultRemove = json_decode(json_encode($resultRemove), true);
            }
            return $resultRemove;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
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


    /**
     * https://shopify.dev/docs/api/admin-graphql/2024-01/mutations/discountAutomaticBasicCreate
     *
     */
    public function createDiscount(
        $bundleId,
        $type,
        $minimun,
        $value,
        $products = [],
        $typeItem = "product",
        $checkCombine = 1,
        $discountOncePerOrder = false
    ) {
        $title = "FTR_" . $bundleId;
        $start = date('Y-m-d');


        if ($type == "percent") {
            $value =  (float)$value / 100;
            $discountType = '
        value: {
            percentage : ' . $value . '
          }
        ';
        } else {
            $appliesOnEachItemText = 'appliesOnEachItem : true';
            if (!empty($discountOncePerOrder)) {
                $appliesOnEachItemText = 'appliesOnEachItem : false';
            }
            $discountType = '
            value: {
            discountAmount : {
                amount : ' . $value . ',
                ' . $appliesOnEachItemText . '
              }
            }
            ';
        }

        $productsToAdd = "";
        $textAdd = 'Product';
        if ($typeItem == "collection") {
            $textAdd = "Collection";
        }
        $totalProducts = count($products);
        foreach ($products as $k => $p) {
            if ($totalProducts < 2) {
                $productsToAdd = '"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
            } else {
                if ($k == 0) {
                    $productsToAdd .= '"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
                } else {
                    $productsToAdd .= ',"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
                }
            }
        }

        $combine = "combinesWith: {
                        orderDiscounts: true,
                        productDiscounts: true,
                        shippingDiscounts: true
                      },";

        if (empty($checkCombine)) {
            $combine = '';
        }

        // dd($typeItem);
        try {
            $query = '
                mutation {
                    discountAutomaticBasicCreate(automaticBasicDiscount: {
                      title: "' . $title . '",
                      startsAt: "' . $start . '",
                      minimumRequirement:{
                        subtotal :{
                          greaterThanOrEqualToSubtotal : ' . $minimun . '
                        }
                      },
                      ' . $combine;
            if ($typeItem == "collection") {
                $query .= 'customerGets: {
                                ' . $discountType . '

                                items: {
                                    collections: {
                                     add: [' . $productsToAdd . ']
                                   }
                                 }

                               }}) {';
            } else {
                $query .= 'customerGets: {
                        ' . $discountType . '

                         items: {
                           products: {
                             productsToAdd: [' . $productsToAdd . ']
                           }
                         }

                       }}) {';
            }

            $query .= 'userErrors {
                        field
                        message
                        code
                      }
                      automaticDiscountNode {
                        id
                      }
                    }
                  }';

            // dd($productsToAdd);

            $graphqlParam['query'] = $query;
            // dd($graphqlParam);
            $response = $this->post('graphql.json', $graphqlParam);
            $result = $response->data->discountAutomaticBasicCreate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    dump($errors);
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function updateDiscount(
        $bundleId,
        $discountId,
        $type,
        $minimun,
        $value,
        $oldProducts,
        $products = [],
        $typeItem = "products",
        $checkCombine = 1,
        $discountOncePerOrder = false
    ) {
        $title = "FTR_" . $bundleId;
        $start = date('Y-m-d');

        if ($type == "percent") {
            $value =  (float)$value / 100;
            $discountType = '
        value: {
            percentage : ' . $value . '
          }
        ';
        } else {
            $appliesOnEachItemText = 'appliesOnEachItem : true';
            if (!empty($discountOncePerOrder)) {
                $appliesOnEachItemText = 'appliesOnEachItem : false';
            }
            $discountType = '
            value: {
            discountAmount : {
                amount : ' . $value . ',
                ' . $appliesOnEachItemText . '
              }
            }
            ';
        }

        $productsToAdd = "";
        $textAdd = 'Product';
        if ($typeItem == "collection") {
            $textAdd = "Collection";
        }
        $totalProducts = count($products);
        $productsToAddArr = [];
        foreach ($products as $k => $p) {
            $productsToAddArr[] = $p['id'];
            if ($totalProducts < 2) {
                $productsToAdd = '"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
            } else {
                if ($k == 0) {
                    $productsToAdd .= '"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
                } else {
                    $productsToAdd .= ',"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
                }
            }
        }
        $oldProductsRemove = [];
        foreach ($oldProducts as $op) {
            if (!in_array($op, $productsToAddArr)) {
                $oldProductsRemove[] = '"gid://shopify/' . $textAdd . '/' . $op . '"';
            }
        }
        $productsToRemove = implode(',', $oldProductsRemove);
        // dump($productsToRemove);

        $combine = "combinesWith: {
      orderDiscounts: true,
      productDiscounts: true,
      shippingDiscounts: true
    },";

        if (empty($checkCombine)) {
            $combine = '';
        }

        try {
            $query = '
            mutation {
            discountAutomaticBasicUpdate(id: "gid://shopify/DiscountAutomaticNode/' . $discountId . '"
                automaticBasicDiscount: {
                  title: "' . $title . '",
                  minimumRequirement:{
                    subtotal :{
                      greaterThanOrEqualToSubtotal : ' . $minimun . '
                    }
                  },
                  ' . $combine . '
                  customerGets: {
                    ' . $discountType . '
                    ';
            if ($typeItem == "collection") {
                $query .= 'items: {
                    collections: {
                      add: [' . $productsToAdd . ']
                      remove: [' . $productsToRemove . ']
                    }
                  }';
            } else {
                $query .= 'items: {
                            products: {
                              productsToAdd: [' . $productsToAdd . ']
                              productsToRemove: [' . $productsToRemove . ']
                            }
                          }';
            }

            $query .= '}}) {
                  userErrors {
                    field
                    message
                    code
                  }
                  automaticDiscountNode {
                    id
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            $result = $response->data->discountAutomaticBasicUpdate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    $checkDeleted = false;
                    if (!empty($errors)) {
                        foreach ($errors as $err) {
                            if (str_contains($err['message'], 'does not exist')) {
                                $checkDeleted = true;
                            }
                        }
                    }

                    if ($checkDeleted) {
                        return ['check_deleted' => true];
                    } else {
                        // dd($errors);
                    }
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function checkDiscount($id)
    {
        try {
            $query = '
            query {
    automaticDiscountNode(id: "gid://shopify/DiscountAutomaticNode/' . $id . '") {
      ... on DiscountAutomaticNode {
      id
      }
    }
  }';
            $graphqlParam['query'] = $query;

            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->automaticDiscountNode;
            if (!empty($result)) {
                return true;
            }
            return false;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function getDiscount($id)
    {
        try {
            $query = '
            query {
    automaticDiscountNode(id: "gid://shopify/DiscountAutomaticNode/' . $id . '") {
      ... on DiscountAutomaticNode {
      id
      }
    }
  }';
            $graphqlParam['query'] = $query;

            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->automaticDiscountNode;
            if (!empty($result)) {
                return true;
            }
            return false;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function deactiveDiscount($id)
    {
        try {
            $query = '
            mutation discountAutomaticDeactivate($id: ID!) {
                discountAutomaticDeactivate(id: $id) {
                  automaticDiscountNode {
                    automaticDiscount {
                      ... on DiscountAutomaticBxgy {
                        status
                        startsAt
                        endsAt
                      }
                    }
                  }
                  userErrors {
                    field
                    message
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $graphqlParam['variables'] = [
                "id" => 'gid://shopify/DiscountAutomaticNode/' . $id,
            ];
            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->discountAutomaticDeactivate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    // dump($errors);
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function activeDiscount($id)
    {
        try {
            $query = '
            mutation discountAutomaticActivate($id: ID!) {
                discountAutomaticActivate(id: $id) {
                  automaticDiscountNode {
                    automaticDiscount {
                      ... on DiscountAutomaticBxgy {
                        status
                        startsAt
                        endsAt
                      }
                    }
                  }
                  userErrors {
                    field
                    message
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $graphqlParam['variables'] = [
                "id" => 'gid://shopify/DiscountAutomaticNode/' . $id,
            ];
            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->discountAutomaticActivate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    // dump($errors);
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function createFreeship($bundleId, $minimun)
    {
        $title = "FTR_" . $bundleId . "_FREESHIP";
        $start = date('Y-m-d');

        try {
            $query = '
            mutation {
                discountAutomaticFreeShippingCreate(freeShippingAutomaticDiscount: {
                  title: "' . $title . '",
                  startsAt: "' . $start . '",
                  minimumRequirement:{
                    subtotal :{
                      greaterThanOrEqualToSubtotal : ' . $minimun . '
                    }
                  },
                  appliesOnOneTimePurchase : true,
                  appliesOnSubscription : true,
                  recurringCycleLimit : 1,
                  destination : {
                    all : true,
                  }
                }) {
                  userErrors {
                    field
                    message
                    code
                  }
                  automaticDiscountNode {
                    id
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            $result = $response->data->discountAutomaticFreeShippingCreate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    dump($errors);
                }
            }
            return $result;
        } catch (\Exception $exception) {
            // dd($exception);
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function updateFreeship($bundleId, $discountId, $minimun)
    {
        $title = "FTR_" . $bundleId . "_FREESHIP";
        $start = date('Y-m-d');

        try {
            $query = '
            mutation {
                discountAutomaticFreeShippingUpdate( id: "gid://shopify/DiscountAutomaticNode/' . $discountId . '"
                  freeShippingAutomaticDiscount: {
                  title: "' . $title . '",
                  minimumRequirement:{
                    subtotal :{
                      greaterThanOrEqualToSubtotal : ' . $minimun . '
                    }
                  },

                  appliesOnOneTimePurchase : true,
                  appliesOnSubscription : true,
                  recurringCycleLimit : 1,
                  destination : {
                    all : true,
                  }
                }) {
                  userErrors {
                    field
                    message
                    code
                  }
                  automaticDiscountNode {
                    id
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);

            $result = $response->data->discountAutomaticFreeShippingUpdate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    $checkDeleted = false;
                    if (!empty($errors)) {
                        foreach ($errors as $err) {
                            if (str_contains($err['message'], 'does not exist')) {
                                $checkDeleted = true;
                            }
                        }
                    }

                    if ($checkDeleted) {
                        return ['check_deleted' => true];
                    } else {
                        // dd($errors);
                    }
                }
            }
            return $result;
        } catch (\Exception $exception) {
            dd($exception);
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function getDiscounts()
    {

        try {
            $query = '
            {
                automaticDiscountNodes(first: 200) {
                edges {
                  node {
                    id
                    automaticDiscount {
                      ... on DiscountAutomaticBasic {
                       status
                      }
                      ... on DiscountAutomaticFreeShipping {
                        status
                      }
                      ... on DiscountAutomaticBxgy {
                        status
                      }
                    }
                  }
                }
              }
            }';
            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            $result = $response->data->automaticDiscountNodes->edges;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    dump($errors);
                }
            }
            return $result;
        } catch (\Exception $exception) {
            // dd($exception);
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function getOrderCount()
    {
        try {
            $response = $this->get('orders/count.json');

            if ($response) {
                return $response->count;
            }
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
            return false;
        }
    }










    /**
     * https://shopify.dev/docs/api/admin-graphql/2024-01/mutations/discountAutomaticBasicCreate
     * move từ discount auto sang discount code cho bundle
     */
    public function createDiscountV2(
        $bundleId,
        $code,
        $type,
        $minimun,
        $value,
        $products = [],
        $typeItem = "product",
        $checkCombine = 1,
        $discountOncePerOrder = false,
        $checkCombineOrderError = false,
    ) {
        $start = date('Y-m-d');


        if ($type == "percent") {
            $value =  (float)$value / 100;
            $discountType = '
        value: {
            percentage : ' . $value . '
          }
        ';
        } else {
            $appliesOnEachItemText = 'appliesOnEachItem : true';
            if (!empty($discountOncePerOrder)) {
                $appliesOnEachItemText = 'appliesOnEachItem : false';
            }
            $discountType = '
            value: {
            discountAmount : {
                amount : ' . $value . ',
                ' . $appliesOnEachItemText . '
              }
            }
            ';
        }

        $productsToAdd = "";
        $textAdd = 'Product';
        if ($typeItem == "collection") {
            $textAdd = "Collection";
        }
        $totalProducts = count($products);
        foreach ($products as $k => $p) {
            if ($totalProducts < 2) {
                $productsToAdd = '"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
            } else {
                if ($k == 0) {
                    $productsToAdd .= '"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
                } else {
                    $productsToAdd .= ',"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
                }
            }
        }

        $combine = "combinesWith: {
                        orderDiscounts: true,
                        productDiscounts: true,
                        shippingDiscounts: true
                      },";

        // if (empty($checkCombine)) {
        //   $combine = '';
        // }
        if ($checkCombineOrderError) {
            $combine = "combinesWith: {
        productDiscounts: true,
        shippingDiscounts: true
      },";
        }

        // dd($typeItem);
        try {
            $query = '
                mutation {
                    discountCodeBasicCreate(basicCodeDiscount: {
                      title: "' . $code . '",
                      code : "' . $code . '",
                      startsAt: "' . $start . '",
                      minimumRequirement:{
                        subtotal :{
                          greaterThanOrEqualToSubtotal : ' . $minimun . '
                        }
                      },
                       customerSelection : {
                        all : true
                      },
                      ' . $combine;
            if ($typeItem == "collection") {
                $query .= 'customerGets: {
                                ' . $discountType . '

                                items: {
                                    collections: {
                                     add: [' . $productsToAdd . ']
                                   }
                                 }

                               }}) {';
            } else {
                $query .= 'customerGets: {
                        ' . $discountType . '

                         items: {
                           products: {
                             productsToAdd: [' . $productsToAdd . ']
                           }
                         }

                       }}) {';
            }

            $query .= 'userErrors {
        field
        message
        code
      }
      codeDiscountNode {
        id
      }
    }
    }';

            // dd($productsToAdd);

            $graphqlParam['query'] = $query;
            // dump($graphqlParam);
            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->discountCodeBasicCreate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    $checkCombineOrderError = false;
                    if (!empty($errors)) {
                        foreach ($errors as $err) {
                            if ($err['code'] == "INVALID_COMBINES_WITH_FOR_DISCOUNT_CLASS") {
                                $checkCombineOrderError = true;
                            }
                        }
                    }

                    if ($checkCombineOrderError) {
                        return $this->createDiscountV2(
                            $bundleId,
                            $code,
                            $type,
                            $minimun,
                            $value,
                            $products,
                            $typeItem,
                            $checkCombine,
                            $discountOncePerOrder,
                            $checkCombineOrderError
                        );
                    } else {
                        dd($errors);
                    }
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function updateDiscountV2(
        $bundleId,
        $discountId,
        $type,
        $minimun,
        $value,
        $oldProducts,
        $products = [],
        $typeItem = "products",
        $checkCombine = 1,
        $discountOncePerOrder = false,
        $productDiscountsCurrent = []
    ) {

        if ($type == "percent") {
            $value =  (float)$value / 100;
            $discountType = '
        value: {
            percentage : ' . $value . '
          }
        ';
        } else {
            $appliesOnEachItemText = 'appliesOnEachItem : true';
            if (!empty($discountOncePerOrder)) {
                $appliesOnEachItemText = 'appliesOnEachItem : false';
            }
            $discountType = '
            value: {
            discountAmount : {
                amount : ' . $value . ',
                ' . $appliesOnEachItemText . '
              }
            }
            ';
        }

        $productsToAdd = "";
        $textAdd = 'Product';
        if ($typeItem == "collection") {
            $textAdd = "Collection";
        }
        $totalProducts = count($products);
        $productsToAddArr = [];
        foreach ($products as $k => $p) {
            $productsToAddArr[] = $p['id'];
            if ($totalProducts < 2) {
                $productsToAdd = '"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
            } else {
                if ($k == 0) {
                    $productsToAdd .= '"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
                } else {
                    $productsToAdd .= ',"gid://shopify/' . $textAdd . '/' . $p['id'] . '"';
                }
            }
        }
        $oldProductsRemove = [];
        foreach ($oldProducts as $op) {
            if (!in_array($op, $productsToAddArr)) {
                $oldProductsRemove[] = '"gid://shopify/' . $textAdd . '/' . $op . '"';
            }
        }
        // dump($productsToRemove);

        if (!empty($productDiscountsCurrent)) {
            foreach ($productDiscountsCurrent as $p) {
                $pF = str_replace('gid://shopify/Product/', '', $p);
                if (!in_array($pF, $productsToAddArr) && !in_array($p, $oldProductsRemove)) {
                    // dump($p);
                    $oldProductsRemove[] = '"gid://shopify/' . $textAdd . '/' . $pF . '"';
                }
            }
            // dump($productsToAddArr);
        }

        $productsToRemove = implode(',', $oldProductsRemove);

        // dump($productsToAdd);
        // dump($productsToRemove);

        $combine = "combinesWith: {
      orderDiscounts: true,
      productDiscounts: true,
      shippingDiscounts: true
    },";

        // if (empty($checkCombine)) {
        //   $combine = '';
        // }

        try {
            $query = '
            mutation {
            discountCodeBasicUpdate(id: "gid://shopify/DiscountCodeNode/' . $discountId . '"
                basicCodeDiscount: {
                  minimumRequirement:{
                    subtotal :{
                      greaterThanOrEqualToSubtotal : ' . $minimun . '
                    }
                  },
                  ' . $combine . '
                  customerGets: {
                    ' . $discountType . '
                    ';
            if ($typeItem == "collection") {
                $query .= 'items: {
                    collections: {
                      add: [' . $productsToAdd . ']
                      remove: [' . $productsToRemove . ']
                    }
                  }';
            } else {
                $query .= 'items: {
                            products: {
                              productsToAdd: [' . $productsToAdd . ']
                              productsToRemove: [' . $productsToRemove . ']
                            }
                          }';
            }

            $query .= '}}) {
                  userErrors {
                    field
                    message
                    code
                  }
                  codeDiscountNode {
                    id
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->discountCodeBasicUpdate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    $checkDeleted = false;
                    if (!empty($errors)) {
                        foreach ($errors as $err) {
                            if (str_contains($err['message'], 'does not exist')) {
                                $checkDeleted = true;
                            }
                        }
                    }

                    if ($checkDeleted) {
                        return ['check_deleted' => true];
                    } else {
                        // dd($errors);
                    }
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function createFreeshipV2($bundleId, $code, $minimun)
    {
        $start = date('Y-m-d');

        try {
            $query = '
            mutation {
                discountCodeFreeShippingCreate(freeShippingCodeDiscount: {
                  title: "' . $code . '",
                  code: "' . $code . '",
                  startsAt: "' . $start . '",
                  minimumRequirement:{
                    subtotal :{
                      greaterThanOrEqualToSubtotal : ' . $minimun . '
                    }
                  },
                  customerSelection : {
                    all : true
                  },
                  destination : {
                    all : true,
                  }
                }) {
                  userErrors {
                    field
                    message
                    code
                  }
                  codeDiscountNode {
                    id
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            $result = $response->data->discountCodeFreeShippingCreate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    dump($errors);
                }
            }
            return $result;
        } catch (\Exception $exception) {
            // dd($exception);
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function updateFreeshipV2($bundleId, $discountId, $minimun)
    {
        try {
            $query = '
            mutation {
                discountCodeFreeShippingUpdate( id: "gid://shopify/DiscountCodeNode/' . $discountId . '"
                  freeShippingCodeDiscount: {
                  minimumRequirement:{
                    subtotal :{
                      greaterThanOrEqualToSubtotal : ' . $minimun . '
                    }
                  },
                  destination : {
                    all : true,
                  }
                }) {
                  userErrors {
                    field
                    message
                    code
                  }
                  codeDiscountNode {
                    id
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);

            $result = $response->data->discountCodeFreeShippingUpdate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    $checkDeleted = false;
                    if (!empty($errors)) {
                        foreach ($errors as $err) {
                            if (str_contains($err['message'], 'does not exist')) {
                                $checkDeleted = true;
                            }
                        }
                    }

                    if ($checkDeleted) {
                        return ['check_deleted' => true];
                    } else {
                        // dd($errors);
                    }
                }
            }
            return $result;
        } catch (\Exception $exception) {
            dd($exception);
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function checkDiscountV2($id)
    {
        try {
            $query = '
            query {
    codeDiscountNode(id: "gid://shopify/DiscountCodeNode/' . $id . '") {
      ... on DiscountCodeNode {
      id
      }
    }
  }';
            $graphqlParam['query'] = $query;

            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->codeDiscountNode;
            if (!empty($result)) {
                return true;
            }
            return false;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function getDiscountV2($id)
    {
        try {
            $query = '
  query {
    codeDiscountNode(id: "gid://shopify/DiscountCodeNode/' . $id . '") {
      ... on DiscountCodeNode {
        id,
       codeDiscount {
        ... on DiscountCodeBasic{
            customerGets{
                items{
                    ... on DiscountProducts {
                        products(first : 250){
                            edges{
                                node{
                                    id
                                }
                            }
                        }

                    }
                }
            }
        }
       }
      }
    }
  }
  ';
            $graphqlParam['query'] = $query;

            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->codeDiscountNode;
            if (!empty($result)) {
                return $result;
            }
            return null;
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
            return null;
        }
    }



    /**
     * https://shopify.dev/docs/api/admin-graphql/2024-01/mutations/discountCodeBasicCreate
     *
     */
    public function createDiscountCode(
        $code,
        $type,
        $minimun,
        $value,
        $productId,
        $noCombieOrder = false
    ) {
        $start = date('Y-m-d');


        if ($type == "percent") {
            $value =  (float)$value / 100;
            $discountType = '
        value: {
            percentage : ' . $value . '
          }
        ';
        } else {
            $discountType = '
            value: {
            discountAmount : {
                amount : ' . $value . ',
                appliesOnEachItem : false
              }
            }
            ';
        }

        $productsToAdd = '"gid://shopify/Product/' . $productId . '"';

        $combinesWith = "combinesWith: {
                        orderDiscounts: true,
                        productDiscounts: true,
                        shippingDiscounts: true
                      }";
        if ($noCombieOrder) {
            $combinesWith = "combinesWith: {
                          productDiscounts: true,
                          shippingDiscounts: true
                        }";
        }
        try {
            $query = '
                mutation {
                    discountCodeBasicCreate(basicCodeDiscount: {
                      title: "' . $code . '",
                      startsAt: "' . $start . '",
                      appliesOncePerCustomer : true,
                      code : "' . $code . '",
                      minimumRequirement:{
                        quantity :{
                          greaterThanOrEqualToQuantity : "' . $minimun . '"
                        }
                      },
                      ' . $combinesWith . '
                      ,
                      customerSelection : {
                        all : true
                      },';
            $query .= 'customerGets: {
                        ' . $discountType . '

                         items: {
                           products: {
                             productsToAdd: [' . $productsToAdd . ']
                           }
                         }

                       }}) {';

            $query .= 'userErrors {
                        field
                        message
                        code
                      }
                      codeDiscountNode {
                        id
                      }
                    }
                  }';

            // dd($productsToAdd);

            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            $result = $response->data->discountCodeBasicCreate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    $checkCombineOrderError = false;
                    if (!empty($errors)) {
                        foreach ($errors as $err) {
                            if ($err['code'] == "INVALID_COMBINES_WITH_FOR_DISCOUNT_CLASS") {
                                $checkCombineOrderError = true;
                            }
                        }
                    }

                    if ($checkCombineOrderError) {
                        return $this->createDiscountCode($code, $type, $minimun, $value, $productId, true);
                    } else {
                        dd($errors);
                    }
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            // dd($exception);
            return false;
        }
    }

    public function updatesDiscountCode($discountId, $code, $type, $minimun, $value, $productId, $noCombieOrder = false)
    {
        if ($type == "percent") {
            $value =  (float)$value / 100;
            $discountType = '
        value: {
            percentage : ' . $value . '
          }
        ';
        } else {
            $discountType = '
            value: {
            discountAmount : {
                amount : ' . $value . ',
                appliesOnEachItem : false
              }
            }
            ';
        }

        $productsToAdd = '"gid://shopify/Product/' . $productId . '"';

        $combinesWith = "combinesWith: {
      orderDiscounts: true,
      productDiscounts: true,
      shippingDiscounts: true
    }";
        if ($noCombieOrder) {
            $combinesWith = "combinesWith: {
        productDiscounts: true,
        shippingDiscounts: true
      }";
        }

        try {
            $query = '
                mutation {
                    discountCodeBasicUpdate( id: "gid://shopify/DiscountCodeNode/' . $discountId . '"
                    basicCodeDiscount: {
                      title: "' . $code . '",
                      appliesOncePerCustomer : true,
                      code : "' . $code . '",
                      minimumRequirement:{
                        quantity :{
                          greaterThanOrEqualToQuantity : "' . $minimun . '"
                        }
                      },
                       ' . $combinesWith . '
                      customerSelection : {
                        all : true
                      },';
            $query .= 'customerGets: {
                        ' . $discountType . '

                         items: {
                           products: {
                             productsToAdd: [' . $productsToAdd . ']
                           }
                         }

                       }}) {';

            $query .= 'userErrors {
                        field
                        message
                        code
                      }
                      codeDiscountNode {
                        id
                      }
                    }
                  }';

            $graphqlParam['query'] = $query;
            $response = $this->post('graphql.json', $graphqlParam);
            $result = $response->data->discountCodeBasicUpdate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    //check compine
                    $checkCombineOrderError = false;
                    if (!empty($errors)) {
                        foreach ($errors as $err) {
                            if ($err['code'] == "INVALID_COMBINES_WITH_FOR_DISCOUNT_CLASS") {
                                $checkCombineOrderError = true;
                            }
                        }
                    }

                    if ($checkCombineOrderError) {
                        return $this->updatesDiscountCode($discountId, $code, $type, $minimun, $value, $productId, true);
                    } else {
                        // check discount deleted
                        $checkDeleted = false;
                        if (!empty($errors)) {
                            foreach ($errors as $err) {
                                if (str_contains($err['message'], 'does not exist')) {
                                    $checkDeleted = true;
                                }
                            }
                        }

                        if ($checkDeleted) {
                            return ['check_deleted' => true];
                        } else {
                            // dd($errors);
                        }
                    }
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            // dd($exception);
            return false;
        }
    }

    public function deactiveDiscountCode($id)
    {
        try {
            $query = '
            mutation discountCodeDeactivate($id: ID!) {
                discountCodeDeactivate(id: $id) {
                  codeDiscountNode {
                    codeDiscount {
                      ... on DiscountCodeBasic {
                        status
                      }
                    }
                  }
                  userErrors {
                    field
                    message
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $graphqlParam['variables'] = [
                "id" => 'gid://shopify/DiscountCodeNode/' . $id,
            ];
            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->discountCodeDeactivate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    dump($errors);
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }

    public function activeDiscountCode($id)
    {
        try {
            $query = '
            mutation discountCodeActivate($id: ID!) {
                discountCodeActivate(id: $id) {
                  codeDiscountNode {
                    codeDiscount {
                      ... on DiscountCodeBasic {
                        status
                      }
                    }
                  }
                  userErrors {
                    field
                    message
                  }
                }
              }';
            $graphqlParam['query'] = $query;
            $graphqlParam['variables'] = [
                "id" => 'gid://shopify/DiscountCodeNode/' . $id,
            ];
            $response = $this->post('graphql.json', $graphqlParam);
            // dd($response);
            $result = $response->data->discountCodeActivate;
            if ($result) {
                $result = json_decode(json_encode($result), true);
                if (!empty($result['userErrors'])) {
                    $errors = $result['userErrors'];
                    dump($errors);
                }
            }
            return $result;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }


    public function getOrder($limit = 50)
    {
        try {
            $query = '
      query {
  orders(first: ' . $limit . ') {
    edges {
      node {
        id,
        lineItems(first : 10){
            edges{
                node{
                    product{
                        status
                        id
                    }
                }
            }
        }
      }
    }
  }
}
      ';
            $graphqlParam['query'] = $query;

            $response = $this->post('graphql.json', $graphqlParam);
            $result = $response->data->orders;
            if (!empty($result)) {
                return $result->edges;
            }
            return false;
        } catch (\Exception $exception) {
            $sentryId = $this->sentry->captureException($exception);
            return false;
        }
    }
}
