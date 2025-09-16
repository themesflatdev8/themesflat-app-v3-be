<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class WebhookVerifyHeaders
{
    protected $sentry;

    public function __construct()
    {
        $this->sentry = app('sentry');
    }

    public function handle(Request $request, Closure $next)
    {
        try {
            // by pass header to testing
            // ✅ Bypass chỉ cho local/test
            if ($this->byPassHeader($request)) {
                return $next($request);
            }

            $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
            $data       = $request->getContent();

            if ($hmacHeader && $this->verifyWebhook($data, $hmacHeader)) {
                return $next($request);
            }

            // ❌ Nếu fail verify
            $this->sentry->captureMessage('Webhook verify failed', [
                'shop'  => $request->header('X-Shopify-Shop-Domain'),
                'topic' => $request->header('X-Shopify-Topic'),
            ]);
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
        }

        return response()->json([], 401);
    }

    /**
     * @param $data
     * @param $hmac_header
     *
     * @return bool
     */
    private function verifyWebhook($data, $hmacHeader)
    {
        $calculated = base64_encode(
            hash_hmac('sha256', $data, config('tf_common.shopify_api_secret'), true)
        );

        return hash_equals($calculated, $hmacHeader);
    }

    /**
     * by pass header to testing
     *
     * @param  Request $request
     * @return boolean
     */
    private function byPassHeader(Request $request)
    {
        return $request->get('bypass_header', false);
    }
}
