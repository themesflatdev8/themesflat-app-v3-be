<?php

namespace App\Services\App;

use Illuminate\Support\Facades\DB;
use App\Models\BlacklistKeywordModel;
use App\Models\KeywordSummaryModel;
use App\Models\SearchKeywordModel;
use App\Services\AbstractService;
use Carbon\Carbon;
use Exception;

class SearchService extends AbstractService
{
    protected $sentry;

    public function __construct()
    {
        $this->sentry = app('sentry');
    }

    public function search($domain, $data)
    {
        // Implement search logic here
        try {
            $isBlacklisted = BlacklistKeywordModel::where('keyword', strtolower($data['keyword']))->exists();

            if ($isBlacklisted) {
                return response()->json([
                    'error'   => 'blacklisted',
                    'message' => 'Keyword blocked'
                ], 403);
            }
            SearchKeywordModel::create([
                'shop_domain' => $domain,
                'keyword'     => $data['keyword'],
                'searched_at' => Carbon::now(),
                'user_ip'     => request()->ip(),
                'user_agent'  => $data['user_agent'],
                'referer'     => $data['referer'],
            ]);
            KeywordSummaryModel::updateOrInsert(
                [
                    'shop_domain' => $domain,
                    'keyword'     => $data['keyword'],
                    'date'        => Carbon::today()->toDateString(),
                ],
                [
                    'count' => DB::raw('count + 1'),
                ]
            );
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
    }

    public function topKeywords($domain, $range)
    {
        try {
            // Implement logic to retrieve top keywords
            $startDate = Carbon::today()->subDays($range - 1)->toDateString();
            $result = KeywordSummaryModel::query()
                ->select('keyword')
                ->where('shop_domain', $domain)
                ->where('date', '>=', $startDate)
                ->groupBy('keyword')
                ->orderByRaw('SUM(count) DESC')
                ->limit(10)
                ->pluck('keyword')
                ->toArray();
            if (empty($result)) {
                $results = KeywordSummaryModel::query()
                    ->select('keyword')
                    ->groupBy('keyword')
                    ->orderByRaw('SUM(count) DESC')
                    ->limit(10)
                    ->pluck('keyword')
                    ->toArray();
            }
            return $result;
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
        return [];
    }
}
