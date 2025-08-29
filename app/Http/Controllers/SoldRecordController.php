<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddSoldRecordRequest;
use App\Http\Requests\BestSellerRequest;
use App\Http\Requests\GetSoldRequest;
use App\Services\App\SoldRecordService;
use Illuminate\Http\Request;

class SoldRecordController extends Controller
{
    //
    protected $soldRecordService;

    public function __construct(SoldRecordService $soldRecordService)
    {
        $this->soldRecordService = $soldRecordService;
    }

    public function addSold32h(AddSoldRecordRequest $request)
    {
        $domain = $request->input('shopInfo')->shop;
        $result = $this->soldRecordService->addSoldRecord($domain, $request->validated());

        return response()->json($result);
    }
    public function getSoldRecord(GetSoldRequest $request)
    {
        $domain = $request->input('shopInfo')->shop;
        $result = $this->soldRecordService->getSoldRecord($domain, $request->validated());

        return response()->json($result);
    }

    public function getBestSeller(BestSellerRequest $request)
    {
        $domain = $request->input('shopInfo')->shop;
        $result = $this->soldRecordService->getBestSeller($domain, $request->validated());

        return response()->json($result);
    }
}
