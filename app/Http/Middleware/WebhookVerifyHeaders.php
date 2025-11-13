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

            if ($headerHmac = $request->server('HTTP_X_SHOPIFY_HMAC_SHA256')) {
                $data     = file_get_contents('php://input');
                $verified = $this->verifyWebhook($data, $headerHmac);
                if ($verified) {
                    return $next($request);
                } else {
                    Log::warning('Webhook uninstall not verify', ['header_hmac' => $headerHmac, 'data' => $data]);
                    $this->sentry->captureMessage('Webhook uninstall not verify');
                }
            } else {
                $this->sentry->captureMessage('Not exists header HTTP_X_SHOPIFY_HMAC_SHA256');
            }
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
    private function verifyWebhook($data, $hmac_header)
    {
        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, config('tf_common.shopify_api_secret'), true));

        return ($hmac_header == $calculated_hmac);
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
