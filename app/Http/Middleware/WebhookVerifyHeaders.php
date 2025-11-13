<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
            // bypass header param for local testing ?bypass_header=1
            if ($this->byPassHeader($request)) {
                return $next($request);
            }

            // lấy header theo cách Laravel (an toàn hơn)
            $headerHmac = $request->header('X-Shopify-Hmac-Sha256');

            if (!$headerHmac) {
                $this->sentry->captureMessage('Not exists header X-Shopify-Hmac-Sha256');
                Log::warning('Missing X-Shopify-Hmac-Sha256 header', [
                    'path' => $request->path(),
                    'server_keys' => array_keys($request->server()),
                ]);
                return response()->json([], 401);
            }

            // dùng getContent() để lấy raw body (an toàn hơn php://input)
            $data = $request->getContent();

            // debug logs (dev only) - KHÔNG log secret
            Log::debug('Shopify webhook received', [
                'topic' => $request->header('X-Shopify-Topic'),
                'hmac_header' => substr($headerHmac, 0, 10) . '...', // rút gọn
                'body_length' => strlen($data),
            ]);

            $verified = $this->verifyWebhook($data, $headerHmac);

            if ($verified) {
                return $next($request);
            }

            // nếu không verify
            Log::warning('Webhook not verified', [
                'header_hmac' => $headerHmac,
                'calculated_hmac' => $this->calculateHmac($data), // rút gọn tiện debug
                'topic' => $request->header('X-Shopify-Topic'),
                'body_snippet' => substr($data, 0, 300),
            ]);
            $this->sentry->captureMessage('Webhook not verified: HMAC mismatch');
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
            Log::error('Webhook verify exception', ['message' => $exception->getMessage()]);
        }

        return response()->json([], 401);
    }

    private function verifyWebhook($data, $hmac_header)
    {
        // dùng hash_equals để so sánh an toàn
        $calculated_hmac = $this->calculateHmac($data);
        return hash_equals($calculated_hmac, $hmac_header);
    }

    private function calculateHmac($data)
    {
        $secret = config('tf_common.shopify_api_secret');
        // trả về base64 string (Shopify yêu cầu)
        return base64_encode(hash_hmac('sha256', $data, $secret, true));
    }

    private function byPassHeader(Request $request)
    {
        return $request->boolean('bypass_header', false);
    }
}
