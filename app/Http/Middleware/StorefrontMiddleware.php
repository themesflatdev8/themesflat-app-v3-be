<?php

namespace App\Http\Middleware;

use App\Repository\ShopRepository;
use Closure;
use Illuminate\Http\Request;

class StorefrontMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        $shopifyDomain = $request->get('domain_name') ?? $request->get('domain');
        $shopRepository = app(ShopRepository::class);

        $shopInfo = $shopRepository->detailByShopifyDomain($shopifyDomain);
        if (empty($shopInfo) || !$shopInfo['is_active']) {
            return [
                'status' => false,
                'message' =>  'Domain invalid',
            ];
        }
        $request->merge([
            'shopInfo' => $shopInfo,
        ]);
        return $next($request);
    }
}
