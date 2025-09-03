<?php

namespace App\Services\App;

use App\Models\ProductReviewModel;
use Illuminate\Support\Facades\DB;
use App\Services\AbstractService;
use Exception;
use Google\Service\AdExchangeBuyerII\Product;
use Google\Service\Merchant\ProductReview;

class ReviewService extends AbstractService
{
    protected $sentry;

    public function __construct()
    {
        $this->sentry = app('sentry');
    }

    public function addReview(string $domain, array $data)
    {
        try {
            ProductReviewModel::create(([
                'user_id'     => $data['user_id'],
                'domain_name' => $domain,
                'product_id'  => $data['product_id'],
                'review_text' => $data['review_text'],
                'rating'      => $data['rating'] ?? null,
                'parent_id'   => $data['parent_id'] ?? null,
                'is_admin'    => $data['is_admin'] ?? false,
                'status'      => 'approved', // mặc định
                'type'        => 'product', // default
            ]));
            return true;
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return false;
    }

    public function editReview(string $domain, array $data)
    {
        try {
            $review = ProductReviewModel::where('id', $data['id'] ?? null)
                ->where('user_id', $data['user_id'] ?? null)
                ->where('domain_name', $domain)
                ->first();

            if (!$review) {
                return false;
            }

            return $review->update([
                'review_text' => $data['review_text'] ?? '',
                'rating'      => $data['rating'] ?? null,
            ]);
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
            return false;
        }
    }
    public function getReviews(string $domain, $data)
    {
        try {
            // ✅ Query đếm tổng reviews'
            $query = ProductReviewModel::where('domain_name', $domain)
                ->where('product_id', $data['product_id'])
                ->where('status', 'approved')
                ->where('type', $data['type']);

            $total = (clone $query)
                ->count();

            // ✅ Nếu type = product → thêm average_rating
            if ($data['type'] === 'product') {
                $averageRating = (clone $query)
                    ->avg('rating');
            }

            // ✅ Nếu type khác product → chỉ trả về tổng
            return [
                'status' => 'success',
                'total'  => $total,
                'average_rating' => !empty($averageRating) ? round((float) $averageRating, 2) : null
            ];
        } catch (Exception $e) {
            dd($e);
            $this->sentry->captureException($e);
        }
        return [
            'status' => 'error',
        ];
    }


    public function getReviewSummary(string $domain, int $productId, string $type = 'product'): array
    {
        try {
            $query = ProductReviewModel::where('product_id', $productId)
                ->where('domain_name', $domain)
                ->whereNull('parent_id')
                ->where('status', 'approved')
                ->where('type', $type);

            $totalReviews = (clone $query)->count();

            if ($type === 'product') {
                $rows = (clone $query)
                    ->select('rating', DB::raw('COUNT(*) as total'))
                    ->groupBy('rating')
                    ->get();

                $summary = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];

                foreach ($rows as $row) {
                    $summary[(int)$row->rating] = (int)$row->total;
                }
            }

            return [
                'status' => 'success',
                'total'  => $totalReviews,
                'breakdown' => $summary ?? null
            ];
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
        }
        return [
            'status' => 'error',
            'message' => 'Database error',
        ];
    }

    public function submitReview(string $domain, array $data)
    {
        try {
            $result = ProductReviewModel::create(([
                'user_id'      => !empty($data['user_id']) ? $data['user_id'] : $data['user_name'],
                'domain_name'  => $domain,
                'product_id'   => $data['product_id'],
                'review_title' => $data['review_title'],
                'review_text'  => $data['review_text'],
                'rating'       => $data['rating'] ?? null,
                'user_name'    => $data['user_name'],
                'status'       => 'approved',
                'type'         => $data['type']
            ]));
            return (bool) $result;
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return false;
    }

    public function getAllReviews(string $domain, int $productId, string $type = 'product'): array
    {
        try {
            $rows = ProductReviewModel::select(
                'id',
                'parent_id',
                'rating',
                'review_title',
                'review_text',
                'user_name',
                'created_at'
            )
                ->where('domain_name', $domain)
                ->where('product_id', $productId)
                ->where('status', 'approved')
                ->where('type', $type)
                ->orderBy('created_at', 'asc')
                ->get()
                ->toArray();

            // Map thành ID => row
            $byId = [];
            foreach ($rows as $row) {
                $row['replies'] = [];
                $byId[$row['id']] = $row;
            }

            // Build tree (gắn reply vào parent)
            $result = [];
            foreach ($byId as $id => &$row) {
                if (!empty($row['parent_id']) && isset($byId[$row['parent_id']])) {
                    $byId[$row['parent_id']]['replies'][] = &$row;
                } else {
                    $result[] = &$row;
                }
            }

            return [
                'status' => 'success',
                'reviews' => $result
            ];
        } catch (Exception $exception) {
            dd($exception);
            $this->sentry->captureException($exception);
        }
        return [
            'status' => 'error',
            'message' => 'Database error',
        ];
    }

    public function getComments(array $params): array
    {
        $domain   = $params['domain'];
        $statuses = $params['status'] ?? [];
        $perPage  = max(1, (int) ($params['per_page'] ?? 10));
        $type     = 'article';

        // Trường hợp chỉ lọc 1 status => flat list
        if (count($statuses) === 1) {
            $paginator = ProductReviewModel::select(
                'id',
                'parent_id',
                'review_title',
                'review_text',
                'user_name',
                'created_at',
                'status'
            )
                ->where('domain_name', $domain)
                ->where('type', $type)
                ->when($statuses, fn($q) => $q->whereIn('status', $statuses))
                ->orderBy('created_at')
                ->paginate($perPage);

            return $this->formatResponse($paginator);
        }

        // Mặc định => phân trang theo cha, kèm replies
        $paginator = ProductReviewModel::where('domain_name', $domain)
            ->where('type', $type)
            ->whereNull('parent_id')
            ->when($statuses, fn($q) => $q->whereIn('status', $statuses))
            ->with(['replies' => fn($q) => $q->orderBy('created_at')])
            ->orderBy('created_at')
            ->paginate($perPage);

        return $this->formatResponse($paginator);
    }

    private function formatResponse($paginator): array
    {
        return [
            'status' => 'success',
            'comments' => $paginator->items(),
            'pagination' => [
                'total'       => $paginator->total(),
                'page'        => $paginator->currentPage(),
                'per_page'    => $paginator->perPage(),
                'total_pages' => $paginator->lastPage(),
            ],
        ];
    }

    public function updateComment($domain, $data)
    {
        try {
            $update = [];
            $update = array_filter([
                'status'       => $data['status']       ?? null,
                'review_title' => $data['review_title'] ?? null,
                'review_text'  => $data['review_text']  ?? null,
            ], fn($v) => !is_null($v));

            if (empty($update)) {
                return [
                    'status'  => 'error',
                    'message' => 'Nothing to update',
                ];
            }

            $updated = DB::table('product_reviews')
                ->where('id', $data['id'])
                ->where('domain_name', $data['domain'])
                ->where('type', 'article')
                ->update($update);

            if (!$updated) {
                return [
                    'status'  => 'error',
                    'message' => 'Update failed',
                ];
            }

            return [
                'status'         => 'success',
                'id'             => $data['id'],
                'updated_fields' => array_keys($update),
            ];
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return [
            'status'         => 'error',
            'message'             => 'Database error',
        ];
    }

    public function countComment(string $domain, $data): array
    {
        try {

            $query = DB::table('product_reviews')
                ->where('domain_name', $domain)
                ->where('product_id', $data['product_id'])
                ->where('status', 'approved')
                ->where('type', $data['type']);

            $total = (clone $query)->count();

            // Nếu type = product thì tính thêm điểm trung bình
            if ($data['type'] === 'product') {
                $averageRating = (clone $query)->avg('rating');

                return [
                    'status'         => 'success',
                    'total'          => $total,
                ];
            }

            return [
                'status' => 'success',
                'total'  => $total,
                'average_rating' => isset($averageRating) ? round((float) $averageRating, 2) : null,

            ];
        } catch (Exception $exception) {
            $this->sentry->captureException($exception);
        }
        return [
            'status' => 'error',
            'message' => 'Database error',
        ];
    }
}
