<?php

namespace App\Http\Controllers;

use App\Services\App\SearchService;
use Google\Service\CustomSearchAPI\Search;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    //
    public $searchService;
    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
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
        $range  = intval($request->input('range', 1));
        $shopInfo = $request->input('shopInfo');

        $result = $this->searchService->topKeywords($shopInfo, $range);
        return response()->json([
            'status' => 'success',
            'data' => $result

        ]);
    }
}
