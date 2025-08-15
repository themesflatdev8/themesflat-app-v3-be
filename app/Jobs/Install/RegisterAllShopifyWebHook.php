<?php

namespace App\Jobs\Install;

use App\Services\Shopify\ShopifyApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RegisterAllShopifyWebHook implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 2;

    private $accessToken;
    private $shopifyDomain;

    public function __construct($shopifyDomain, $accessToken)
    {
        // $this->onQueue(env('QUEUE_NAME_DEFAULT'));
        $this->shopifyDomain = $shopifyDomain;
        $this->accessToken = $accessToken;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $shopifyApiService = new ShopifyApiService();
        $sentry = app('sentry');

        try {
            $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);

            //Delete all webHook before add Web Hook
            info('start delete all shopify web hook');
            $this->deleteAllWebHook($shopifyApiService);
            info('start register all shopify web hook');
            $listWebHookRegister = $this->listWebhookRegister($shopifyApiService);
            foreach ($listWebHookRegister as $k => $v) {
                info("topic: " . $v['topic']);
                try {
                    $shopifyApiService->post('webhooks.json', [
                        'webhook' => [
                            'address' => $v['address'],
                            'topic' => $v['topic'],
                            'format' => 'json',
                        ],
                    ]);
                } catch (\Exception $exception) {
                    // dump($exception->getMessage());
                    info('Error Register All Shopify WebHook: ' . $exception->getMessage());
                    $sentry->captureException($exception);
                }
            }
        } catch (\Exception $exception) {
            info('Error Register All Shopify WebHook: ' . $exception->getMessage());
            $sentry->captureException($exception);
        }
    }


    /**
     * @return bool
     */
    public function deleteAllWebHook($shopifyApiService)
    {
        $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);
        $webHooks = $this->allWebHook($shopifyApiService);
        if (!empty($webHooks)) {
            foreach ($webHooks as $k => $v) {
                try {
                    $shopifyApiService->drop('webhooks/' . $v->id . '.json');
                } catch (\Exception $exception) {
                    dump($exception->getMessage());
                }
            }
        }
    }

    public function allWebHook($shopifyApiService)
    {
        $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);
        $response = $shopifyApiService->get('webhooks.json');
        if ($response) {
            return $response->webhooks;
        } else {
            return false;
        }
    }

    private function listWebhookRegister()
    {
        $webhooks = [
            [
                'address' => config('tf_common.hook_url') . '/api/webhook/uninstall',
                'topic' => 'app/uninstalled',
            ],
            [
                'address' => config('tf_common.hook_url') . '/api/webhook/shop/update',
                'topic' => 'shop/update',
            ],
            [
                'address' => config('tf_common.hook_url') . '/api/webhook/products/create',
                'topic' => 'products/create',
            ],
            [
                'address' => config('tf_common.hook_url') . '/api/webhook/products/update',
                'topic' => 'products/update',
            ],
            [
                'address' => config('tf_common.hook_url') . '/api/webhook/products/delete',
                'topic' => 'products/delete',
            ],


            [
                'address' => config('tf_common.hook_url') . '/api/webhook/collections/create',
                'topic' => 'collections/create',
            ],
            [
                'address' => config('tf_common.hook_url') . '/api/webhook/collections/update',
                'topic' => 'collections/update',
            ],
            [
                'address' => config('tf_common.hook_url') . '/api/webhook/collections/delete',
                'topic' => 'collections/delete',
            ],

            // [
            //     'address' => config('tf_common.hook_url') . '/api/webhook/themes/publish',
            //     'topic' => 'themes/publish',
            // ],
            // [
            //     'address' => config('tf_common.hook_url') . '/api/webhook/order/create',
            //     'topic' => 'orders/create',
            // ]
        ];

        return $webhooks;
    }
}
