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
            // ✅ Lấy HMAC header do Shopify gửi
            $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');

            // ✅ Dùng php://input để lấy raw body chính xác (không bị Laravel parse)
            $data = file_get_contents('php://input');

            // ✅ Log để debug khi cần
            Log::debug('Shopify Webhook verify check', [
                'path'      => $request->path(),
                'header'    => $hmacHeader,
                'data_size' => strlen($data),
            ]);

            // ✅ Kiểm tra HMAC
            if ($hmacHeader && $this->verifyWebhook($data, $hmacHeader)) {
                Log::info('✅ Shopify Webhook verification success', [
                    'path' => $request->path(),
                ]);
                return $next($request);
            }

            // ❌ Nếu sai thì log chi tiết
            Log::warning('❌ Shopify Webhook verification failed', [
                'path'   => $request->path(),
                'header' => $hmacHeader,
                'calc'   => $this->calculateHmac($data),
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
     */
    private function verifyWebhook(string $data, string $hmacHeader): bool
    {
        $calculated = $this->calculateHmac($data);

        // trim() để tránh lỗi do ký tự '=' cuối base64
        return hash_equals(trim($calculated), trim($hmacHeader));
    }

    /**
     * Hàm phụ để tính HMAC (phục vụ log/debug)
     */
    private function calculateHmac(string $data): string
    {
        return base64_encode(
            hash_hmac('sha256', $data, config('tf_common.shopify_api_secret'), true)
        );
    }
}
