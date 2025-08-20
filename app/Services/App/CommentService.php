<?php

namespace App\Services\App;

use App\Jobs\DeactiveDiscountJob;
use App\Models\BundlesModel;
use App\Models\StoreModel;
use App\Models\StoreTestModel;
use App\Services\AbstractService;
use App\Services\Shopify\ShopifyApiService;
use Carbon\Carbon;
use Exception;

class CommentService extends AbstractService
{
    protected $sentry;

    public function __construct()
    {
        $this->sentry = app('sentry');
    }

    public function getComments(int $shopId)
    {
        return $this->model->where('shop_id', $shopId)->get();
    }
}
