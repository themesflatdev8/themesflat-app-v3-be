<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\StoreModel;
use App\Services\Shopify\ShopifyService;
use App\Models\StoreTestModel;
use App\Models\LoyaltyModel;
use App\Jobs\Sync\SyncCollectionJob;
use App\Mail\Loyalty;
use App\Jobs\SendLoyaltyEmailJob;
use Exception;
class StoreController extends Controller
{
    protected $shopifyService;
    public function __construct(ShopifyService $shopifyService)
    {
        $this->shopifyService = $shopifyService;
    }
    /**
     * Display and search store
     * GET /dashboard
     */
    public function index(Request $request)
    {
        $query = StoreModel::query();

        // Search
        $query = $this->applySearch($query, $request);

        // Apply sorting
        if ($request->has('sort')) {
            $sortField = $request->get('sort');
            $sortOrder = $request->get('order');
            $stores = $query->orderBy($sortField, $sortOrder);
        }

        // Apply filters
        $this->applyFilters($query, $request);

        // Execute query and paginate the results
        $stores = $query->orderBy('created_at', 'desc')->paginate(10);

        // Loyalty status filter
        $stores = $this->applyLoyaltyStatusFilter($stores, $request);

        // Process data for each store
        foreach ($stores as $store) {
            $loyaltyInfo = checkLoyalty($store);
            $store->loyalty = [
                'loyalty' => $loyaltyInfo['loyalty'],
                'quest_review' => $loyaltyInfo['quest_review']
            ];
            $store->test = $this->checkTestInfo($store);
            $store->blackList = $this->checkBlacklistStore($store);
        }

        // Get shopify_plan from the store table
        $shopifyPlans = StoreModel::distinct()->pluck('shopify_plan')->toArray();

        return view('dashboard', compact('stores', 'shopifyPlans'));
    }

    /**
     * Apply filters to the query.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @param \Illuminate\Http\Request $request
     * @return void
     */
    private function applyFilters($query, $request)
    {
        $data = $request->all();

        // App plan filter
        if (isset($data['app_plan'])) {
            $this->applyAppPlanFilter($query, $data);
        }

        // App status filter
        if (isset($data['app_status'])) {
            $this->applyAppStatusFilter($query, $data);
        }

        // Shopify plan filter
        if (isset($data['shopify_plan'])) {
            $this->applyShopifyPlanFilter($query, $data);
        }

        // Quest review filter
        if (isset($data['quest_review'])) {
            $this->applyQuestReviewFilter($query, $data);
        }

        // Quest bundle filter
        if (isset($data['quest_bundle'])) {
            $this->applyQuestBundleFilter($query, $data);
        }

        // Quest ext filter
        if (isset($data['quest_ext'])) {
            $this->applyQuestExtFilter($query, $data);
        }
    }

    /**
     * Apply loyalty status filter to the stores.
     *
     * @param \Illuminate\Pagination\LengthAwarePaginator $stores
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    private function applyLoyaltyStatusFilter($stores, $request)
    {
        $data = $request->all();

        if (!empty($data['loyalty_status'])) {
            // Lấy giá trị của loyalty_status từ request
            $loyaltyStatus = $data['loyalty_status'];
            if ($loyaltyStatus == 'yes') {
                $loyalStoreIds = [];
                foreach ($stores as $store) {
                    $storeInfo = checkLoyalty($store);
                    if ($storeInfo['loyalty'] == true) {
                        $loyalStoreIds[] = $store->id;
                    }
                }
                $stores = StoreModel::whereIn('id', $loyalStoreIds)->paginate(10);
            } elseif ($loyaltyStatus == 'no') {
                $nonLoyalStoreIds = [];
                foreach ($stores as $store) {
                    $storeInfo = checkLoyalty($store);
                    if ($storeInfo['loyalty'] == false) {
                        $nonLoyalStoreIds[] = $store->id;
                    }
                }
                $stores = StoreModel::whereIn('id', $nonLoyalStoreIds)->paginate(10);
            }
        }

        return $stores;
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request)
    {
        $store = StoreModel::where('store_id', $id)->first();
        $loyalty = checkLoyalty($store);
        $store->loyalty = $loyalty;
        $test_exists = StoreTestModel::where('store_id', $id)->exists();

        $allPlans = config('fa_plans');

        return view('store.edit', compact('store', 'test_exists', 'allPlans'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update($id, Request $request)
    {
        $store = StoreModel::where('store_id', $id)->first();

        if ($store) {
            // Update or create new loyalty
            $loyaltyData = [
                'quest_review' => $request->input('review'),
                // 'quest_bundle' => $request->has('bundle') ? 1 : 0,
                // 'quest_ext' => $request->has('ext') ? 1 : 0,
            ];
            if (!empty($request->get('loyalty'))) {
                $loyaltyData['force_loyalty'] = true;
                $loyaltyData['apply'] = true;
            } else {
                $loyaltyData['force_loyalty'] = false;
                $loyaltyData['apply'] = false;
            }

            $loyalty = LoyaltyModel::where('store_id', $store->store_id)->first();
            if ($loyalty) {
                $loyalty->update($loyaltyData);
            } else {
                $loyaltyData['store_id'] = $store->store_id;
                // $loyaltyData['sent_mail'] = 1;
                LoyaltyModel::create($loyaltyData);
            }
            // Update store
            StoreModel::where('store_id', $store->store_id)->update([
                'app_status' => $request->has('status') ? 1 : 0,
                'app_plan' => $request->get('app_plan'),
            ]);

            // Check checkbox test_store
            $test_store = StoreTestModel::where('store_id', $store->store_id)->first();

            if ($request->has('test_stores') && !$test_store) {
                StoreTestModel::create([
                    'store_id' => $store->store_id
                ]);
            } elseif (!$request->has('test_stores') && $test_store) {
                $test_store->delete();
            }
        }

        return redirect()->back()->with('success', 'Update success');
    }

    /**
     * Send loyalty email to a specific store.
     *
     * @param  int  $id
     * @return \Illuminate\Http\RedirectResponse
     */
    public function mail($id)
    {
        $store = StoreModel::where('store_id', $id)->first();

        if (!$store) {
            return redirect()->back()->with('error', 'Store not found');
        }

        try {
            $email = $store->email;
            $loyalty = LoyaltyModel::where('store_id', $id)->first();
            if (!empty($loyalty) && !empty($loyalty->email)) {
                $email = $loyalty->email;
            }

            $loyaltyMail = new Loyalty($store);
            // Dispatch job to queue
            dispatch(new SendLoyaltyEmailJob($email, $loyaltyMail));

            return redirect()->back()->with('success', 'Email sent successfully');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Failed to queue email: ' . $e->getMessage());
        }
    }
    /**
     * Render the email content.
     *
     * @param  \Illuminate\Mail\Mailable  $mailable  The mailable object to render.
     * @return string  The rendered email content.
     */
    private function renderEmail($mailable)
    {
        return $mailable->render();
    }


    /**
     * Process information about the store's Loyalty, Theme Extension and App Block.
     *
     * @param \App\Models\StoreModel $store Information of the store to process.
     * @return array An array containing information about Loyalty, Theme Extension and App Block.
     */
    private function processStoreInfo($store)
    {
        $result = [
            'loyalty' => checkLoyalty($store), // check Loyalty
            'theme_ext' => false,
            'app_block' => false,
        ];

        $shopDomain = $store->shopify_domain;
        $accessToken = $store->access_token;

        if (!empty($accessToken)) {
            try {
                // Lấy thông tin về theme đang hoạt động
                $theme = getThemeActive($shopDomain, $accessToken);

                // Xử lý thông tin về App Block Extension
                $idBlock = config('fa_switcher.app_block.id_block');
                if (!empty($dataThemeAppExtension)) {
                    foreach ($dataThemeAppExtension as $dta) {
                        if (strpos($dta, config('fa_switcher.app_block')[$idBlock]) !== false) {
                            // echo "\"bar\" exists in the haystack variable";
                            $result['app_block'] = true;
                            break;
                        }
                    }
                }

                // Xử lý thông tin về Theme Extension
                $dataThemeAppExtension = getDataThemeAppExtension($store, $theme->id);
                $dataThemeAppExtension = json_decode(json_encode($dataThemeAppExtension), true);
                $idBlock = config('fa_switcher.app_embed.id_block');
                if ($dataThemeAppExtension['current'] != 'Default') {
                    $result['theme_ext'] = !$dataThemeAppExtension['current']['blocks'][$idBlock]['disabled'];
                }
            } catch (Exception $e) {
            }
        }
        return $result;
    }

    /**
     * Apply searches to queries.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Database\Query\Builder
     */
    private function applySearch($query, $request)
    {
        if (!empty($request->get('keyword'))) {
            $keyword = $request->get('keyword');

            $query = $query->where(function ($query) use ($keyword) {
                $query->where('email', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('shopify_domain', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('domain', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('name', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('owner', 'LIKE', '%' . $keyword . '%')
                    ->orWhere('store_id', 'LIKE', '%' . $keyword . '%');
            });
        }

        return $query;
    }

    /**
     * Check if a store is on the blacklist or not.
     *
     * @param \App\Models\StoreModel $store Information of the store to check.
     * @return bool Returns true if the store is in the blacklist, otherwise returns false.
     */
    private function checkBlacklistStore($store)
    {
        $checkBlacklist = checkBlacklist(
            $store->shopify_domain,
            $store->shopify_plan,
            $store->email,
            $store->name,
            $store->domain,
        );

        return !empty($checkBlacklist);
    }

    /**
     * Check if a store has Test information.
     *
     * @param \App\Models\StoreModel $store Information of the store to check.
     * @return bool Returns true if the store has Test information, otherwise returns false.
     */
    private function checkTestInfo($store)
    {
        // Check to see if any data row in the StoreTestModel table has a corresponding store_id
        return StoreTestModel::where('store_id', $store->store_id)->exists();
    }

    /**
     * Apply app plan filter.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $data
     * @return void
     */
    private function applyAppPlanFilter($query, $data)
    {
        $query->where('app_plan', $data['app_plan'] == 'free' ? 'free' : 'essential');
    }
    /**
     * Apply app status filter.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $data
     * @return void
     */
    private function applyAppStatusFilter($query, $data)
    {
        $query->where('app_status', $data['app_status'] == '1' ? '1' : '0');
    }

    /**
     * Apply shopify plan filter.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $data
     * @return void
     */
    private function applyShopifyPlanFilter($query, $data)
    {
        $query->where('shopify_plan', $data['shopify_plan']);
    }

    /**
     * Apply quest review filter.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $data
     * @return void
     */
    private function applyQuestReviewFilter($query, $data)
    {
        $storeIDs = LoyaltyModel::where('quest_review', $data['quest_review'])->pluck('store_id');
        $query->whereIn('store_id', $storeIDs);
    }

    /**
     * Apply quest bundel filter.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $data
     * @return void
     */
    private function applyQuestBundleFilter($query, $data)
    {
        if ($data['quest_bundle'] === '1') {
            $bundleStoreIDs = LoyaltyModel::where('quest_bundle', 1)->pluck('store_id');
            $query->whereIn('store_id', $bundleStoreIDs);
        } else {
            $bundleStoreIDs = LoyaltyModel::where('quest_bundle', 0)->pluck('store_id');
            $query->whereIn('store_id', $bundleStoreIDs);
        }
    }

    /**
     * Apply quest ext filter.
     *
     * @param  \Illuminate\Database\Query\Builder  $query
     * @param  array  $data
     * @return void
     */
    private function applyQuestExtFilter($query, $data)
    {
        if ($data['quest_ext'] === '1') {
            $extStoreIDs = LoyaltyModel::where('quest_ext', 1)->pluck('store_id');
            // Sử dụng phép OR để kết hợp các kết quả từ cả hai điều kiện
            $query->orWhereIn('store_id', $extStoreIDs);
        } else {
            $extStoreIDs = LoyaltyModel::where('quest_ext', 0)->pluck('store_id');
            $query->whereIn('store_id', $extStoreIDs);
        }
    }

    /**
     * Sync shopify collection
     * POST /store/sync-collections
     */
    public function syncCollections($id = null)
    {

        $store = StoreModel::where('store_id', $id)->first();
        if ($store) {
            dispatch(new SyncCollectionJob($store->store_id, $store->shopify_domain, $store->access_token, 'custom_collections'));
            return redirect()->back()->with('success', 'Sync collections success');
        }
        return redirect()->back()->with('error', 'Unable to sync collections: Store not found');
    }

    /**
     * Retrieve the embedded app and app block information from Shopify.
     * 
     * Endpoint: GET /dashboard/store-info/{id}
     * 
     * @param Request $request - The incoming request containing the store ID.
     * @return \Illuminate\Http\JsonResponse
     */
    public function getStoreInfo(Request $request)
    {
        $storeId = $request->input('store_id');
        $store = StoreModel::where('store_id', $storeId)->first();
        $orders_count = null;

        if ($store) {
            if (!empty($store->access_token)) {
                $this->shopifyService->setShopifyHeader($store->shopify_domain, $store->access_token);
                $orders_count = $this->shopifyService->getOrderCount();
            }

            $storeInfo = $this->processStoreInfo($store);
            return response()->json([
                'theme_ext' => $storeInfo['theme_ext'],
                'app_block' => $storeInfo['app_block'],
                'orders_count' => $orders_count,
            ]);
        }

        return response()->json(['error' => 'Store not found'], 404);
    }
}
