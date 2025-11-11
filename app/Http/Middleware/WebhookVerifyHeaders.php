<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookVerifyHeaders
{
    public function handle(Request $request, Closure $next)
    {
        try {
            $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');
            $data       = $request->getContent();

            if ($hmacHeader && $this->verifyWebhook($data, $hmacHeader)) {
                Log::warning('Shopify Webhook verification success', [
                    'path'   => $request->path(),
                    'header' => $hmacHeader,
                ]);
                return $next($request);
            }

            // Nếu không hợp lệ, log rồi trả về 401
            Log::warning('Shopify Webhook verification failed', [
                'path'   => $request->path(),
                'header' => $hmacHeader,
            ]);
        } catch (\Exception $exception) {
            Log::error('Shopify Webhook exception: ' . $exception->getMessage(), [
                'trace' => $exception->getTraceAsString(),
            ]);
        }

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    /**
     * Verify webhook với HMAC
     *
     * @param string $data
     * @param string $hmacHeader
     * @return bool
     */
    private function verifyWebhook(string $data, string $hmacHeader): bool
    {
        $calculated = base64_encode(
            hash_hmac('sha256', $data, config('tf_common.shopify_api_secret'), true)
        );

        return hash_equals($calculated, $hmacHeader);
    }
}
