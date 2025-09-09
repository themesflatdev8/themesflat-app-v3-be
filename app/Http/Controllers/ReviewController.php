<?php

namespace App\Http\Controllers;

use App\Http\Requests\AddReviewRequest;
use App\Http\Requests\CountCommentRequest;
use App\Http\Requests\EditReviewRequest;
use App\Http\Requests\GetAllReviewRequest;
use App\Http\Requests\GetCommentsRequest;
use App\Http\Requests\GetReviewRequest;
use App\Http\Requests\GetReviewSummaryRequest;
use App\Http\Requests\SubmitReviewRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Services\App\ReviewService;
use Google\Service\AndroidPublisher\Review;
use Google\Service\Datastore\Sum;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    //\
    public $reviewService;
    public function __construct(ReviewService $reviewService)
    {
        $this->reviewService = $reviewService;
    }

    public function addReview(AddReviewRequest $request)
    {
        $data = $request->validated();
        $domain = $request->input('shopInfo')['shop'];

        $result = $this->reviewService->addReview($domain, $data);
        if ($result) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Review added successfully',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Database error',
        ], 500);
    }

    public function editReview(EditReviewRequest $request)
    {
        $data = $request->validated();
        $domain = $request->input('shopInfo')['shop'];


        $result = $this->reviewService->editReview($domain, $data);
        if ($result) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Review updated successfully',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Database error',
        ]);
    }

    public function getReviews(GetReviewRequest $request)
    {
        $data = $request->validated();
        $domain = $request->input('shopInfo')['shop'];
        $data['type'] = $data['type'] ?? 'product';
        $result = $this->reviewService->getReviews($domain, $data);
        return response()->json($result);
    }

    public function getReviewSummary(GetReviewSummaryRequest $request)
    {
        $data = $request->validated();
        $domain = $request->input('shopInfo')['shop'];
        $data['type'] = $data['type'] ?? 'product';

        $result = $this->reviewService->getReviewSummary($domain, $data['product_id'], $data['type']);
        return response()->json($result);
    }




    public function submitReview(SubmitReviewRequest $request)
    {
        $data = $request->validated();
        $domain = $request->input('shopInfo')['shop'];
        $data['type'] = $data['type'] ?? 'product';

        $result = $this->reviewService->submitReview($domain, $data);
        if ($result) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Review submitted successfully',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Database error',
        ]);
    }

    public function getAllReviews(GetAllReviewRequest $request)
    {
        $data = $request->all();
        $domain = $request->input('shopInfo')['shop'];
        $data['type'] = $data['type'] ?? 'product';

        $result = $this->reviewService->getAllReviews($domain, $data['product_id'], $data['type'] ?? 'product');
        return response()->json($result);
    }

    public function getComments(GetCommentsRequest $request)
    {
        $data = $request->validated();
        $domain = $request->input('shopInfo')['shop'];

        $result = $this->reviewService->getComments($domain, $data);
        return response()->json($result);
    }
    public function updateComment(UpdateCommentRequest $request)
    {
        $data = $request->validated();
        $domain = $request->input('shopInfo')['shop'];


        $result = $this->reviewService->updateComment($domain, $data);
        return response()->json($result);
    }

    public function countComment(CountCommentRequest $request)
    {
        $data = $request->validated();
        $domain = $request->input('shopInfo')['shop'];


        $result = $this->reviewService->countComment($domain, $data);
        return response()->json($result);
    }
    public function getManageReviews(Request $request)
    {
        $data = $request->all();
        $domain = $request->input('shopInfo')['shop'];

        $result = $this->reviewService->getManageReviews($domain, $data);
        return response()->json($result);
    }

    public function getReviewById(Request $request, $id)
    {
        $domain = $request->input('shopInfo')['shop'];
        $filter = $request->all();

        $result = $this->reviewService->getReviewById($domain, $id, $filter);
        return response()->json($result);
    }

    public function updateReviewById($id, Request $request)
    {
        $data = $request->all();
        $domain = $request->input('shopInfo')['shop'];


        $result = $this->reviewService->updateReviewById($domain, $id, $data);
        if ($result) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Review updated successfully',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Database error',
        ]);
    }

    public function deleteById($id, Request $request)
    {
        $domain = $request->input('shopInfo')['shop'];

        $result = $this->reviewService->deleteById($domain, $id);
        if ($result) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Review deleted successfully',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Database error',
        ]);
    }

    public function bulkAction(Request $request)
    {
        $data = $request->all();
        $domain = $request->input('shopInfo')['shop'];

        $result = $this->reviewService->bulkAction($domain, $data);
        if ($result) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Bulk action completed successfully',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'Database error',
        ]);
    }
}
