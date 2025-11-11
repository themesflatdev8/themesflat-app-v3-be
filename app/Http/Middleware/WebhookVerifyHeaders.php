<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookVerifyHeaders
{
    public function handle(Request $request, Closure $next)
    {
        // Lấy HMAC header
        $hmacHeader = $request->header('X-Shopify-Hmac-Sha256');

        // ✅ Đọc raw body từ php://input TRƯỚC KHI Laravel xử lý gì khác
        $rawData = file_get_contents('php://input');

        Log::debug('Shopify Webhook verify check', [
            'path'      => $request->path(),
            'header'    => $hmacHeader,
            'data_size' => strlen($rawData),
        ]);

        if ($hmacHeader && $this->verifyWebhook($rawData, $hmacHeader)) {
            Log::info('✅ Shopify Webhook verification success', [
                'path' => $request->path(),
            ]);
            return $next($request);
        }

        Log::warning('❌ Shopify Webhook verification failed', [
            'path'   => $request->path(),
            'header' => $hmacHeader,
            'calc'   => $this->calculateHmac($rawData),
        ]);

        return response()->json(['error' => 'Unauthorized'], 401);
    }

    private function verifyWebhook(string $data, string $hmacHeader): bool
    {
        $calculated = $this->calculateHmac($data);
        return hash_equals(trim($calculated), trim($hmacHeader));
    }

    private function calculateHmac(string $data): string
    {
        return base64_encode(
            hash_hmac('sha256', $data, config('tf_common.shopify_api_secret'), true)
        );
    }
}
