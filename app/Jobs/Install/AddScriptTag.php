<?php

namespace App\Jobs\Install;

use App\Services\Shopify\ShopifyApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AddScriptTag implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 1;

    private $accessToken;
    private $shopifyDomain;

    public function __construct($shopifyDomain, $accessToken)
    {
        $this->onQueue(env('QUEUE_NAME_DEFAULT'));

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
            // echo $this->shopifyDomain;
            $shopifyApiService->setShopifyHeader($this->shopifyDomain, $this->accessToken);
            $scriptTags = $shopifyApiService->get('script_tags.json');
            dump($scriptTags);
            if (!empty($scriptTags->script_tags)) {
                foreach ($scriptTags->script_tags as $tag) {
                    $shopifyApiService->drop('script_tags/' . $tag->id . '.json');
                    sleep(0.5);
                }
            }

            $scriptTag = $shopifyApiService->post('script_tags.json', [
                'script_tag' => [
                    'event' => 'onload',
                    'display_scope' => 'online_store',
                    'src' => env('FE_URL') . '/shopify/assets/storefront/features.js',
                ]
            ]);
            dump($scriptTag);
        } catch (\Exception $exception) {
            echo $exception->getMessage();
            $sentry->captureException($exception);
        }
    }
}
