<?php

namespace App\Http\Controllers;

use App\Services\App\CommentService;
use Dom\Comment;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    //
    protected $commentService;

    public function __construct()
    {
        //
        $this->commentService = app(CommentService::class);
    }

    public function getComment(Request $request)
    {
        $data = $request->all();
        $shopInfo = data_get($data, 'shopInfo', []);
        $comments = $this->commentService->getComments($shopInfo->shop_id);
        return response(['data' => $comments]);
    }

    public function create(Request $request)
    {
        $data = $request->all();
        $shopInfo = data_get($data, 'shopInfo', []);
        $comment = $this->commentService->createComment($shopInfo->shop_id, $data);
        return response(['data' => $comment]);
    }

    public function delete(Request $request)
    {
        $data = $request->all();
        $shopInfo = data_get($data, 'shopInfo', []);
        $this->commentService->deleteComment($shopInfo->shop_id, $data['comment_id']);
        return response(['message' => 'Comment deleted successfully']);
    }

    public function update($commentId, Request $request)
    {
        $data = $request->all();
        $shopInfo = data_get($data, 'shopInfo', []);
        $comment = $this->commentService->updateComment($shopInfo->shop_id, $data);
        return response(['data' => $comment]);
    }
}
