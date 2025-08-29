<?php

namespace App\Http\Middleware;

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
        $shopRepository = app('App\Repositories\ShopRepository');
        $shopInfo = $shopRepository->detailByShopifyDomain($shopifyDomain);
        if (empty($shopInfo) || $shopInfo['status'] != 1) {
            return [
                'status' => false,
                'message' =>  'Domain invalid',
            ];
        }
        return $next($request);
    }
}
