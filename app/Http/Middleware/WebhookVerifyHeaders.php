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
            // by pass header to testing
            if ($this->byPassHeader($request)) {
                return $next($request);
            }
            $res = $request->all();

            if ($headerHmac = $request->server('HTTP_X_SHOPIFY_HMAC_SHA256')) {
                $data     = file_get_contents('php://input');
                $verified = $this->verifyWebhook($data, $headerHmac);
                if ($verified) {
                    return $next($request);
                } else {
                    Log::warning('Webhook not verified', [
                        'topic' => $request->header('X-Shopify-Topic'),
                        'header_hmac' => $headerHmac,
                        'calculated_hmac' => $this->calculateHmac($data),
                        'body_length' => strlen($data),
                        'body_preview' => substr($data, 0, 500),
                    ]);
                }
            } else {
                $this->sentry->captureMessage('Not exists header HTTP_X_SHOPIFY_HMAC_SHA256');
            }
        } catch (\Exception $exception) {
            $this->sentry->captureException($exception);
        }

        return response()->json([], 401);
    }

    private function verifyWebhook(string $data, string $hmacHeader): bool
    {
        $calculated = $this->calculateHmac($data);
        return hash_equals($calculated, $hmacHeader);
    }

    private function calculateHmac(string $data): string
    {
        $secret = config('tf_common.shopify_api_secret');

        if (empty($secret)) {
            Log::error('Shopify API secret not set in config(tf_common.shopify_api_secret)');
        }

        return base64_encode(hash_hmac('sha256', $data, $secret, true));
    }

    private function byPassHeader(Request $request): bool
    {
        return (bool) $request->query('bypass_header', false);
    }
}
