<?php

namespace App\Services\App;

use App\Jobs\DeactiveDiscountJob;
use App\Models\ApproveDomainModel;
use App\Models\BundlesModel;
use App\Models\StoreModel;
use App\Models\StoreTestModel;
use App\Services\AbstractService;
use App\Services\Shopify\ShopifyApiService;
use Carbon\Carbon;
use Exception;

class ShopService extends AbstractService
{
    protected $sentry;

    public function __construct()
    {
        $this->sentry = app('sentry');
    }

    public function requestApprove($shopifyDomain, $data): void
    {
        try {
            ApproveDomainModel::where('domain_name', $shopifyDomain)->update([
                'status' => 'request',
                'email_domain' => $data['email_domain'] ?? '',
                'valid_days' => $data['valid_days'] ?? 0,
            ]);
        } catch (Exception $e) {
            $this->sentry->captureException($e);
        }
    }
}
