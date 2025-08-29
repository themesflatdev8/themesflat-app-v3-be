<?php

namespace App\Http\Controllers;

use App\Services\App\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    //
    public $orderService;
    public function __construct(
        OrderService $orderService,
    ) {

        $this->orderService = $orderService;
    }
    public function alsoBoughts(Request $request)
    {
        $shop = $request->shopInfo;
        $data['shop_domain'] = $shop->shop;
        $data['variant_ids'] = $request->query('variant_ids', '');
        $result = $this->orderService->alsoBoughts($data);
        return response([
            'status' => 'success',
            'data' => $result
        ]);
    }
}
