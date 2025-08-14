<?php

namespace Modules\Auth\Http\Middleware;

use App\Facade\SystemCache;
use App\Models\StoreModel;
use Firebase\JWT\JWT;
use Closure;
use Exception;
use Modules\Auth\Lib\AppContext;
use Modules\Auth\Services\TAuthService;
use Shopify\Context;


class TAuthen
{
    public function handle($request, Closure $next)
    {
        $headers = $request->header();
        $authService = app(TAuthService::class);
        if (!isset($headers['authorization'])) {
            throw new Exception('Unauthenticated 1.', 403);
        }
        $auth = $headers['authorization'];
        preg_match('/^Bearer (.+)$/', $auth[0], $matches);
        if (!$matches) {
            throw new Exception('Unauthenticated.', 403);
        }

        $session = $headers['session'][0];
        //verify token
        $jwtPayload = JWT::decode($matches[1], Context::$API_SECRET_KEY, array('HS256'));

        $shop = preg_replace('/^https:\/\//', '', $jwtPayload->dest);
        $shopVerify = $this->authenticateSession($session);
        //session invalid
        if (empty($shopVerify) || $shopVerify != $shop) {
            return response()->json(['data' => $authService->getUrlAuthorize(['shop' => $shop]), 'check' => 1], 401);
        }
        $store = StoreModel::where("shopify_domain", $shop)->first();

        // store invalid
        if (empty($store) || empty($store->app_status)) {
            sleep(2);
            $store = StoreModel::where("shopify_domain", $shop)->first();
            if (empty($store) || empty($store->app_status)) {
                return response()->json([
                    'data' => $authService->getUrlAuthorize(['shop' => $shop]),
                    'message' => "Unauthenticated 2",
                    // 'shop' => $shop,
                    // 'shop_info' => $store
                ], 401);
            }
        }

        if ($store->app_version != config('fa_common.app_version')) {
            return response()->json(['data' => $authService->getUrlAuthorize(['shop' => $shop]), 'message' => "Unauthenticated 3"], 401);
        }

        AppContext::initialize($store->toArray());
        $request->merge([
            'storeInfo' => $store,
        ]);

        return $next($request);
    }

    private function authenticateSession($sessionId)
    {
        $validPoint = strtotime('now - 1days');
        $listInvalidData = SystemCache::getItemFromSortedSetByScore(config('fa_setting.cache.list_valid_sessions'), 0, $validPoint);
        if (!empty($listInvalidData)) {
            SystemCache::removeItemFromSortedSetByScore(config('fa_setting.cache.list_valid_sessions'), 0, $validPoint);
            SystemCache::removeItemsFromHash(config('fa_setting.cache.list_store_session'), $listInvalidData);
        }
        $shop = SystemCache::getItemFromHash(config('fa_setting.cache.list_store_session'), $sessionId);
        return $shop;
    }
}
