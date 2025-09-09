<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\Sync\SyncDiscountJob;
use App\Repository\DiscountRepository;
use Illuminate\Http\Request;

class DiscountsController extends Controller
{
    //
    public $sentry;
    public $discountRepository;

    public function __construct(DiscountRepository $discountRepository)
    {
        $this->discountRepository = $discountRepository;
        $this->sentry = app('sentry');
    }

    public function sync(Request $request)
    {
        $data = $request->all();
        $shopInfo = data_get($data, 'shopInfo', []);
        $key = config('tf_cache.sync.sync_discount') . $shopInfo->shop_id;
        $checkSync = SystemCache::checkExistItemSet($key, config('tf_resource.discount'));
        if ($checkSync) {
            // return response(['message' => 'Syncing']);
        }
        SystemCache::addItemSet($key, config('tf_resource.discount'), 60 * 60 * 24 * 2);
        dispatch(new SyncDiscountJob($shopInfo->shop_id, $shopInfo->shop, $shopInfo->access_token, true, 250));
        return response(['message' => 'Sync successful']);
    }

    public function getDiscounts(Request $request)
    {
        $data = $request->all();
        $shopInfo = data_get($data, 'shopInfo', []);
        $filter = data_get($data, 'filter', []);
        $discounts = $this->discountRepository->getDiscounts($shopInfo->shop_id, $filter);
        return response(['data' => $discounts]);
    }

    public function getFreeShippingDiscounts(Request $request)
    {
        try {
            $data = $request->all();
            $shopInfo = data_get($data, 'shopInfo', []);
            $results = $this->discountRepository->getFreeShippingDiscounts($shopInfo->shop);
            // Bước 3: Nếu không có kết quả
            if (empty($results)) {
                return response()->json([
                    'status' => 'success',
                    'data'   => [],
                    'note'   => 'No active free shipping discount codes found.'
                ], 200);
            }

            // Bước 4: Xử lý logic minimum_value
            $parsed = $results->map(function ($item) {
                $minimumQuantity = $item->minimum_quantity;
                $minimumRequirement = $item->minimum_requirement;

                $minimumValue = null;
                if (!is_null($minimumQuantity)) {
                    $minimumValue = intval($minimumQuantity);
                } elseif (!is_null($minimumRequirement)) {
                    $minimumValue = floatval($minimumRequirement);
                }

                return [
                    'discount_value'      => floatval($item->discount_value),
                    'minimum_requirement' => $minimumRequirement ? floatval($minimumRequirement) : null,
                    'minimum_quantity'    => $minimumQuantity ? intval($minimumQuantity) : null,
                    'minimum_value'       => $minimumValue,
                ];
            });

            return response()->json([
                'status' => 'success',
                'data'   => $parsed
            ], 200);
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return response()->json([
            'status' => 'failed',
            'data'   => [],
        ], 200);
    }

    public function countShipping(Request $request)
    {
        $shop = $request->shopInfo;
        $data['shop_domain'] = $shop->shop;
        $data['variant_ids'] = $request->query('variant_ids', '');
        $result = $this->discountRepository->getFreeShippingDiscounts($data);
        return response([
            'status' => 'success',
            'data' => $result
        ]);
    }

    public function getFreeShip(Request $request)
    {
        try {
            $domain = $request->shopInfo['shop'];
            $result = $this->discountRepository->getFreeShip($domain);

            if (empty($result)) {
                return [
                    'status' => 'success',
                    'data' => [],
                    'note' => 'No active free shipping discount codes found.',
                ];
            }

            $parsed = collect($result)->map(function ($item) {
                $minimum_quantity = $item->minimum_quantity;
                $minimum_requirement = $item->minimum_requirement;

                $minimum_value = null;
                if (!is_null($minimum_quantity)) {
                    $minimum_value = intval($minimum_quantity);
                } elseif (!is_null($minimum_requirement)) {
                    $minimum_value = floatval($minimum_requirement);
                }

                return [
                    'discount_value'      => floatval($item->discount_value),
                    'minimum_requirement' => $minimum_requirement ?? null,
                    'minimum_quantity'    => $minimum_quantity ?? null,
                    'minimum_value'       => $minimum_value,
                    'codes'               => $item->codes,
                ];
            })->values()->toArray();


            return response([
                'status' => 'success',
                'data' => $parsed
            ]);
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return response([
            'status' => 'success',
            'data' => []
        ]);
    }
}
