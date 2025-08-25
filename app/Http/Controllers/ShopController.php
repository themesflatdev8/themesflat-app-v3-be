<?php

namespace App\Http\Controllers;

use App\Services\App\ShopService;
use Illuminate\Http\Request;

class ShopController extends Controller
{
    //
    protected $shopService;
    protected $sentry;

    public function __construct(
        ShopService $shopService,
    ) {
        $this->shopService = $shopService;
        $this->sentry = app('sentry');
    }

    public function index(Request $request)
    {
        $shops = []; //$this->shopService->listShop($request->all());
        return view('shop.index', compact('shops'));
    }

    public function requestApprove(Request $request)
    {
        $data = $request->all();
        $shopifyDomain = $data['shopInfo']->shop ?? '';
        if (empty($shopifyDomain)) {
            return response(['status' => false, 'message' => 'Missing shopify_domain'], 400);
        }
        $this->shopService->requestApprove($shopifyDomain, $data['data']);
        return response(['status' => true]);
    }
}
