<?php

namespace App\Http\Controllers;

use App\Models\ResponseModel;
use App\Services\App\SearchService;
use Google\Service\CustomSearchAPI\Search;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    //
    public $searchService;
    public $sentry;
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
        $this->sentry = app('sentry');
    }

    public function search(Request $request)
    {
        $data = [
            'keyword' => $request->input('keyword'),
            'user_ip' => request()->ip(),
            'user_agent'  => $request->header('User-Agent'),
            'referer'     => $request->header('Referer'),

        ];
        $domain = $request->input('shopInfo')['shop'];

        $this->searchService->search($domain, $data);

        return response()->json([
            'success' => true,
        ]);
    }

    public function topKeywords(Request $request)
    {
        try {
            $shopInfo = $request->input('shopInfo');
            $shopDomain = $shopInfo['shop'] ?? null;
            $range = intval($request->input('range', 1));

            $apiName = 'topKeywords';
            $paramHash = md5(json_encode(['range' => $range]));
            $expireMinutes = 60 * 6; // cache 6 tiếng

            // 1️⃣ Check cache DB
            $cached = ResponseModel::where('shop_domain', $shopDomain)
                ->where('api_name', $apiName)
                ->where('param', $paramHash)
                ->where('expire_time', '>', now())
                ->first();

            if ($cached) {
                return response()->json([
                    'status' => 'success',
                    'data' => json_decode($cached->response, true),
                    'cached' => true, // optional: để debug
                ]);
            }

            // 2️⃣ Gọi service 
            $result = $this->searchService->topKeywords($shopInfo, $range);

            // 3️⃣ Lưu cache vào DB
            ResponseModel::updateOrCreate(
                [
                    'shop_domain' => $shopDomain,
                    'api_name' => $apiName,
                    'param' => $paramHash,
                ],
                [
                    'response' => json_encode($result),
                    'expire_time' => now()->addHours(config('tf_cache.limit_cache_database', 10)), // cấu hình trong .env
                ]
            );

            return response()->json([
                'status' => 'success',
                'data' => $result,
                'cached' => false,
            ]);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }

        return response()->json([
            'status' => 'error',
            'data' => [],
        ]);
    }
}
