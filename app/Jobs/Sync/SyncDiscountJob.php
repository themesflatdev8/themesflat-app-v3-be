<?php

namespace App\Jobs\Sync;

use App\Facade\SystemCache;
use App\Models\DiscountModel;
use App\Models\Mongo\Product;
use App\Models\ProductModel;
use App\Models\ProductOptionModel;
use App\Models\ProductVariantModel;
use App\Services\Shopify\ShopifyApiService;
use App\Services\Sync\ProductSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Modules\Auth\Events\SyncSuccessEvent;
use Throwable;

class SyncDiscountJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    private $products;

    private $shopId;
    private $cursor;
    private $shopifyDomain;
    private $accessToken;
    private $limit;
    private $firstPage;

    public function __construct($shopId, $shopifyDomain, $accessToken, $firstPage = false, $limit = 250, $cursor = '')
    {
        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
        $this->cursor = $cursor;
        $this->limit = $limit;
        $this->shopId = $shopId;
        $this->firstPage = $firstPage;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $sentry = app('sentry');
        $key = config('tf_cache.sync.sync_discount') . $this->shopId;
        try {
            //delete old db
            if ($this->firstPage) {
                DiscountModel::where('shop_id', $this->shopId)->delete();
            }
            $response = $this->getDiscountsFromShopify();
            $discounts = $response->data->discountNodes->edges ?? [];
            $dataSave = [];
            foreach ($discounts as $discountEdge) {
                $dataSave[] = $this->prepareDiscountData($discountEdge->node);
            }
            DiscountModel::insert($dataSave);
            $nextPageInfo = $response->data->discountNodes->pageInfo->hasNextPage ?? false;

            if ($nextPageInfo) {
                $lastCursor = end($response->data->discountNodes->edges)->cursor ?? null;
                $this->cursor = $lastCursor;
                if (empty($lastCursor)) {
                    event(new SyncSuccessEvent($this->shopId, config('tf_resource.discount'), 'success'));
                    return;
                }
                dispatch(new self($this->shopId, $this->shopifyDomain, $this->accessToken, false, $this->limit, $this->cursor));
            } else {
                event(new SyncSuccessEvent($this->shopId, config('tf_resource.discount'), 'success'));
            }
        } catch (\Exception $ex) {
            // Handle exception
            $sentry->captureException($ex);
            //remove cache
            SystemCache::removeItemFromSet($key, config('tf_resource.discount'));
            //push event
            event(new SyncSuccessEvent($this->shopId, config('tf_resource.discount'), 'success'));
        }
    }
    private function getDiscountsFromShopify()
    {
        $this->limit = 1;
        $graphqlParam['query'] = '
                query ($first: Int!, $cursor: String) {
                    discountNodes(first: $first, after: $cursor) {
                        pageInfo {
                            hasNextPage
                            hasPreviousPage
                        }
                        edges {
                            cursor
                            node {
                                id
                                discount {
                                    __typename
                                    ... on DiscountCodeFreeShipping {
                                        appliesOnOneTimePurchase
                                        title
                                        summary
                                        status
                                        startsAt
                                        endsAt
                                        codes(first: 2) {
                                            nodes {
                                                code
                                            }
                                        }
                                    }
                                    ... on DiscountAutomaticFreeShipping {
                                        title
                                        summary
                                        status
                                        startsAt
                                        endsAt
                                        minimumRequirement {
                                            ... on DiscountMinimumQuantity {
                                                greaterThanOrEqualToQuantity
                                            }
                                        }
                                        appliesOnOneTimePurchase
                                        appliesOnSubscription
                                        asyncUsageCount
                                        combinesWith {
                                            orderDiscounts
                                            productDiscounts
                                            shippingDiscounts
                                        }
                                        createdAt
                                        destinationSelection {
                                            ... on DiscountCountryAll {
                                            allCountries
                                            }
                                            ... on DiscountCountries {
                                            countries
                                            }
                                        }
                                        hasTimelineComment
                                        maximumShippingPrice {
                                            amount
                                            currencyCode
                                        }
                                        recurringCycleLimit
                                        shortSummary
                                    }
                                    ... on DiscountCodeBasic {
                                        title
                                        summary
                                        status
                                        startsAt
                                        endsAt
                                        codes(first: 1) {
                                            nodes {
                                                code
                                            }
                                        }
                                        customerGets {
                                            value {
                                                ... on DiscountPercentage {
                                                    percentage
                                                }
                                                ... on DiscountAmount {
                                                    amount {
                                                        amount
                                                        currencyCode
                                                    }
                                                }
                                            }
                                            items {
                                                ... on DiscountProducts {
                                                    productVariants(first: 50) {
                                                        nodes {
                                                            product {
                                                                id
                                                                handle
                                                            }
                                                        }
                                                    }
                                                    products(first: 50) {
                                                        nodes {
                                                            id
                                                            handle
                                                        }
                                                    }
                                                }
                                                ... on DiscountCollections {
                                                    collections(first: 100) {
                                                        nodes {
                                                            id
                                                            title
                                                            handle
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        minimumRequirement {
                                            ... on DiscountMinimumSubtotal {
                                                greaterThanOrEqualToSubtotal {
                                                    amount
                                                    currencyCode
                                                }
                                            }
                                            ... on DiscountMinimumQuantity {
                                                greaterThanOrEqualToQuantity
                                            }
                                        }
                                    }
                                    ... on DiscountAutomaticBasic {
                                        title
                                        summary
                                        status
                                        startsAt
                                        endsAt
                                        customerGets {
                                            value {
                                                ... on DiscountPercentage {
                                                    percentage
                                                }
                                                ... on DiscountAmount {
                                                    amount {
                                                        amount
                                                        currencyCode
                                                    }
                                                }
                                            }
                                            items {
                                                ... on DiscountProducts {
                                                    productVariants(first: 50) {
                                                        nodes {
                                                            product {
                                                                handle
                                                            }
                                                        }
                                                    }
                                                    products(first: 50) {
                                                        nodes {
                                                            handle
                                                        }
                                                    }
                                                }
                                                ... on DiscountCollections {
                                                    collections(first: 100) {
                                                        nodes {
                                                            id
                                                            title
                                                            handle
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        minimumRequirement {
                                            ... on DiscountMinimumSubtotal {
                                                greaterThanOrEqualToSubtotal {
                                                    amount
                                                    currencyCode
                                                }
                                            }
                                            ... on DiscountMinimumQuantity {
                                                greaterThanOrEqualToQuantity
                                            }
                                        }
                                    }
                                    ... on DiscountAutomaticBxgy {
                                        title
                                        summary
                                        status
                                        startsAt
                                        endsAt
                                        customerBuys {
                                            items {
                                                ... on DiscountProducts {
                                                    productVariants(first: 50) {
                                                        nodes {
                                                            product {
                                                                handle
                                                            }
                                                        }
                                                    }
                                                    products(first: 50) {
                                                        nodes {
                                                            handle
                                                        }
                                                    }
                                                }
                                                ... on DiscountCollections {
                                                    collections(first: 100) {
                                                        nodes {
                                                            id
                                                            title
                                                            handle
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        customerGets {
                                            items {
                                                ... on DiscountProducts {
                                                    productVariants(first: 100) {
                                                        nodes {
                                                            product {
                                                                handle
                                                            }
                                                        }
                                                    }
                                                    products(first: 100) {
                                                        nodes {
                                                            handle
                                                        }
                                                    }
                                                }
                                                ... on DiscountCollections {
                                                    collections(first: 100) {
                                                        nodes {
                                                            id
                                                            title
                                                            handle
                                                          }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    ... on DiscountCodeBxgy {
                                        title
                                        summary
                                        status
                                        startsAt
                                        endsAt
                                        codes(first: 1) {
                                            nodes {
                                                code
                                            }
                                        }
                                        customerBuys {
                                            items {
                                                ... on DiscountProducts {
                                                    productVariants(first: 50) {
                                                        nodes {
                                                            product {
                                                                handle
                                                            }
                                                        }
                                                    }
                                                    products(first: 50) {
                                                        nodes {
                                                            handle
                                                        }
                                                    }
                                                }
                                                ... on DiscountCollections {
                                                    collections(first: 100) {
                                                        nodes {
                                                            id
                                                            title
                                                            handle
                                                        }
                                                    }
                                                }
                                                ... on DiscountCollections {
                                                    collections(first: 100) {
                                                        nodes {
                                                            id
                                                            title
                                                            handle
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                        customerGets {
                                            items {
                                                ... on DiscountProducts {
                                                    productVariants(first: 50) {
                                                        nodes {
                                                            product {
                                                                handle
                                                            }
                                                        }
                                                    }
                                                    products(first: 50) {
                                                        nodes {
                                                            handle
                                                        }
                                                    }

                                                }
                                            ... on DiscountCollections {
                                                    collections(first: 100) {
                                                        nodes {
                                                            id
                                                            title
                                                            handle
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                    ... on DiscountAutomaticApp
                                    {
                                        title
                                        status
                                        startsAt
                                        endsAt

                                    }

                                }
                            }
                        }
                    }
                }
            ';

        $graphqlParam['variables'] = [
            "first" => $this->limit,
        ];

        if (!empty($this->cursor)) {
            $graphqlParam['variables']['cursor'] = $this->cursor;
        }
        $shopifyApiService = app(ShopifyApiService::class);
        $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);
        $response = $shopifyApiService->post('graphql.json', $graphqlParam);
        return $response;
    }
    private function prepareDiscountData($discountInfo)
    {
        $discount =  $discountInfo->discount;
        $statusMap = DiscountModel::STATUS_MAP;

        $data = [
            'shopify_discount_id' => $discountInfo->id,
            'title' => $discount->title ?? '',
            'summary' => $discount->summary ?? '',
            'status' => $discount->status ?? '',
            'starts_at' => $discount->startsAt ?? null,
            'ends_at' => $discount->endsAt ?? null,
            'domain_name' => $this->shopifyDomain,
            'shop_id' => $this->shopId,
            'type' => $discount->__typename ?? '',
            'codes' => json_encode([$discount->title]) ?? '',
            'discount_value' => $discount->customerGets->value->percentage ?? null,
            'minimum_requirement' => $discount->minimumRequirement->greaterThanOrEqualToQuantity ?? null,
            'minimum_quantity' => null,
            'applies_to' => $discount->appliesTo ?? null,
            'purchase_type' => $discount->purchaseType ?? null,
            'related_handles' => null,
            'buy_handles' => null,
            'get_handles' => null,
            'status' => $statusMap[$discount->status] ?? 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        // Handle other discount types and their specific fields here...

        return $data;
    }
}
