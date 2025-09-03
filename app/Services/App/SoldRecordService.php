<?php

namespace App\Services\App;

use App\Models\ProductReviewModel;
use App\Models\SoldRecordModel;
use Illuminate\Support\Facades\DB;
use App\Services\AbstractService;
use Exception;
use Google\Service\AdExchangeBuyerII\Product;
use Google\Service\Merchant\ProductReview;

class SoldRecordService extends AbstractService
{
    protected $sentry;

    public function __construct()
    {
        $this->sentry = app('sentry');
    }

    public function addSoldRecord($domain, $data)
    {
        try {
            $dataSave = [
                'domain_name'   => $domain,
                'product_id'    => $data['product_id'],
                'product_name'  => $data['product_name'],
                'product_price' => $data['product_price'],
                'price_coupon'  => $data['price_coupon'],
                'product_unit'  => $data['product_unit'],
                'total'         => $data['total'],
                'order_id'      => $data['order_id'],
                'order_date'    => $data['order_date'],
            ];
            SoldRecordModel::create($dataSave);
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
        return [
            'status' => 'error',
            'message' => 'Database error',
        ];
    }

    public function getSoldRecord($domain, $data)
    {
        try {
            $totalUnits = SoldRecordModel::where('domain_name', $domain)
                ->where('product_id', $data['product_id'])
                ->where('order_date', '>=', now()->subHours($data['hours']))
                ->sum('product_unit');

            return [
                'status'     => 'success',
                'total_sold' => (int) $totalUnits,
            ];
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
        return [
            'status' => 'error',
            'message' => 'Database error',
        ];
    }

    public function getBestSeller($domain, $data)
    {
        try {
            $subQuery = SoldRecordModel::select('product_id', DB::raw('SUM(product_unit) as total_units'))
                ->where('domain_name', $data['domain'])
                ->where('order_date', '>=', now()->subDays($data['days']))
                ->groupBy('product_id')
                ->orderByDesc('total_units')
                ->limit(10);

            $isBestSeller = SoldRecordModel::fromSub($subQuery, 'top_10')
                ->where('product_id', $data['product_id'])
                ->exists();

            return [
                'status'     => 'success',
                'bestseller' => $isBestSeller ? 1 : 0,
            ];
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
        return [
            'status' => 'error',
            'message' => 'Database error',
        ];
    }
}
