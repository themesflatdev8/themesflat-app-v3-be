<?php


namespace App\Repository;

use App\Facade\SystemCache;
use App\Models\ProductReviewModel;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ProductReviewRepository extends AbstractRepository
{
    /**
     * @return string
     */
    protected $cacheSupport;


    public function model()
    {
        return ProductReviewModel::class;
    }

    public function getReviewByProduct($domain, $filter)
    {
        return $this->model->select(
            'product_id',
            DB::raw('MAX(title) as title'),
            DB::raw('MAX(handle) as handle'),
            DB::raw('COUNT(*) as total_reviews'),
            DB::raw('AVG(rating)::numeric(10,2) as avg_rating'),
            DB::raw('MAX(created_at) as created_at')
            // DB::raw("COUNT(*) FILTER (WHERE status = 'pending') as pending_count"),
            // DB::raw("COUNT(*) FILTER (WHERE status = 'approved') as approved_count")
        )->where('domain_name', $domain)
            ->orderByDesc('created_at')
            ->groupBy('product_id')
            ->get();
    }

    public function getReviewById($domain, $id, $filter)
    {
        $query = $this->model->where('domain_name', $domain)
            ->where('product_id', $id);

        // lọc theo status
        if (isset($filter['status']) && $filter['status'] !== 'all') {
            $query->where('status', $filter['status']);
        }

        // setup phân trang
        $page = isset($filter['page']) ? (int) $filter['page'] : 1;
        $perPage = isset($filter['per_page']) ? (int) $filter['per_page'] : 10;

        return $query
            ->orderByDesc('created_at')
            ->paginate($perPage, ['*'], 'page', $page);
    }

    public function deleteReviews($domain, $data)
    {
        $query =  $this->model->where('domain_name', $domain);
        // lọc theo status
        if ($data['select_all'] && !empty($data['unselected'])) {
            $query = $query->whereNotIn('id', $data['unselected']);
        }
        if (!empty($data['selected'])) {
            $query = $query->whereIn('id', $data['selected']);
        }

        return $query->delete();
    }

    public function updateStatusReview($domain, $data)
    {
        $query =  $this->model->where('domain_name', $domain);
        // lọc theo status
        if ($data['select_all'] && !empty($data['unselected'])) {
            $query = $query->whereNotIn('id', $data['unselected']);
        }
        if (!empty($data['selected'])) {
            $query = $query->whereIn('id', $data['selected']);
        }

        return $query->update(['status' => $data['action']]);
    }
}
