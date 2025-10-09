<?php

namespace App\Http\Controllers;

use App\Facade\SystemCache;
use App\Jobs\Sync\SyncDiscountJob;
use App\Models\ResponseModel;
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
        $filter = [
            'limit' => $request->query('limit', 10),
            'status' => $request->query('status', null),
            'type' => $request->query('type', null),
            'keyword' => $request->query('keyword', null),
        ];
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
        $domain = $request->shopInfo['shop'];
        $country = $request->get('country', null);
        $apiName = 'getFreeShip';
        $paramHash = md5(json_encode(['country' => $country]));
        // Thời gian hết hạn cache (ví dụ: 1 giờ)
        $expireMinutes = 60;

        try {
            // 1️⃣ Kiểm tra cache trong bảng response
            $cached = ResponseModel::where('shop_domain', $domain)
                ->where('api_name', $apiName)
                ->where('param', $paramHash)
                ->where('expire_time', '>', now())
                ->first();
            if ($cached) {
                return response([
                    'status' => 'success',
                    'data' => json_decode($cached->response, true),
                    'cached' => true, // thêm flag để debug
                ]);
            }

            // 2️⃣ Nếu không có cache → gọi repository thật
            $result = $this->discountRepository->getFreeShip($domain, $country);

            if (empty($result)) {
                $responseData = [
                    'status' => 'success',
                    'data' => [],
                    'note' => 'No active free shipping discount codes found.',
                ];

                // Lưu cache rỗng để tránh query lại liên tục
                ResponseModel::updateOrInsert(
                    [
                        'shop_domain' => $domain,
                        'api_name' => $apiName,
                        'param' => $paramHash,
                    ],
                    [
                        'response' => json_encode($responseData['data']),
                        'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)),
                        'updated_at' => now(),
                    ]
                );

                return response($responseData);
            }

            // 3️⃣ Parse kết quả
            $parsed = collect($result)->map(function ($item) {
                $minimumQuantity = $item->minimum_quantity;
                $minimumSubtotal = $item->minimum_subtotal;

                return [
                    'discount_value'    => floatval($item->discount_value),
                    'minimum_subtotal'  => $minimumSubtotal ? intval($minimumSubtotal) : null,
                    'minimum_quantity'  => $minimumQuantity ? intval($minimumQuantity) : null,
                    'codes'             => json_decode($item->codes),
                    'countries'         => json_decode($item->countries),
                ];
            })->values()->toArray();

            // 4️⃣ Lưu vào bảng response
            ResponseModel::updateOrInsert(
                [
                    'shop_domain' => $domain,
                    'api_name' => $apiName,
                    'param' => $paramHash,
                ],
                [
                    'response' => json_encode($parsed),
                    'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)),
                    'updated_at' => now(),
                ]
            );

            return response([
                'status' => 'success',
                'data' => $parsed,
            ]);
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
        }

        return response([
            'status' => 'success',
            'data' => [],
        ]);
    }


    public function checkDiscount(Request $request)
    {
        $domain = $request->shopInfo['shop'];
        $code = $request->get('code');
        $apiName = 'checkDiscount';
        $paramHash = md5(json_encode(['code' => $code]));

        // Thời gian hết hạn (ví dụ 1 giờ)
        $expireMinutes = 60;

        try {
            // 1️⃣ Kiểm tra cache trong DB
            $cached = ResponseModel::where('shop_domain', $domain)
                ->where('api_name', $apiName)
                ->where('param', $paramHash)
                ->where('expire_time', '>', now())
                ->first();

            if ($cached) {
                return response([
                    'status' => 'success',
                    'data' => json_decode($cached->response, true)
                ]);
            }

            // 2️⃣ Nếu không có cache hoặc hết hạn → gọi service thật
            $result = $this->discountRepository->checkDiscount($domain, $code);
            $responseData = !empty($result) ? true : false;

            // 3️⃣ Lưu lại vào bảng response
            ResponseModel::updateOrInsert(
                [
                    'shop_domain' => $domain,
                    'api_name' => $apiName,
                    'param' => $paramHash,
                ],
                [
                    'response' => json_encode($responseData),
                    'expire_time' =>  now()->addHours(config('tf_cache.limit_cache_database', 10)),
                    'updated_at' => now(),
                ]
            );

            return response([
                'status' => 'success',
                'data' => $responseData
            ]);
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
        }

        return response([
            'status' => 'success',
            'data' => false
        ]);
    }
}
