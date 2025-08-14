<?php

namespace App\Jobs;

use App\Mail\Loyalty;
use App\Models\LoyaltyModel;
use App\Models\StoreModel;
use App\Services\MailService;
use App\Services\Shopify\ShopifyApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class AutoAddLoyalty implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    private $storeId;

    public function __construct($storeId)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

        $this->storeId = $storeId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle(MailService $mailService)
    {
        $sentry = app('sentry');

        try {
            $store = StoreModel::where('store_id', $this->storeId)->first();
            if (!empty($store) && !empty($store->app_status)) {
                $loyalty = LoyaltyModel::where('store_id', $this->storeId)->first();
                if (!empty($loyalty)) {
                    if (empty($loyalty->quest_review) && !empty($loyalty->apply)) {
                        $email = $store->email;
                        if (!empty($loyalty->email)) {
                            $email = $loyalty->email;
                        }

                        if (empty($loyalty->sent_mail)) {
                            $loyaltyMail = new Loyalty($store);
                            $mailService->sendLoyaltyEmail($email, $loyaltyMail);
                            
                            $loyalty->update(['force_loyalty' => true, 'sent_mail' => true]);
                        }
                    }
                }
            }
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
