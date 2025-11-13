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
            // ✅ Cho phép bypass header khi test local: ?bypass_header=1
            if ($this->byPassHeader($request)) {
                Log::info('Bypass webhook verify for local testing');
                return $next($request);
            }

            // ✅ Lấy header đúng key, Shopify luôn gửi kiểu "X-Shopify-Hmac-Sha256"
            $headerHmac = $request->header('X-Shopify-Hmac-Sha256');

            if (!$headerHmac) {
                $msg = 'Missing X-Shopify-Hmac-Sha256 header';
                Log::warning($msg, ['path' => $request->path()]);
                $this->sentry->captureMessage($msg);
                return response()->json([], 401);
            }

            // ✅ Dùng getContent() để lấy raw body gốc, không decode
            $data = $request->getContent();

            // ✅ Trim CRLF nếu có (ngăn lỗi khi webhook orders/create thêm ký tự cuối)
            $data = rtrim($data, "\r\n");

            // 🧩 Debug log cơ bản
            Log::debug('Shopify webhook received', [
                'topic' => $request->header('X-Shopify-Topic'),
                'hmac_header_prefix' => substr($headerHmac, 0, 10) . '...',
                'body_length' => strlen($data),
            ]);

            // ✅ Verify
            $verified = $this->verifyWebhook($data, $headerHmac);

            if ($verified) {
                return $next($request);
            }

            // ❌ Không verify được → log chi tiết (ẩn bớt dữ liệu nhạy cảm)
            Log::warning('Webhook not verified', [
                'topic' => $request->header('X-Shopify-Topic'),
                'header_hmac' => $headerHmac,
                'calculated_hmac' => $this->calculateHmac($data),
                'body_length' => strlen($data),
                'body_preview' => substr($data, 0, 500),
            ]);

            $this->sentry->captureMessage('Shopify Webhook HMAC mismatch');
        } catch (\Throwable $e) {
            Log::error('Webhook verify exception', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            $this->sentry->captureException($e);
        }

        return response()->json([], 401);
    }

    /**
     * Xác thực HMAC
     */
    private function verifyWebhook(string $data, string $hmacHeader): bool
    {
        $calculated = $this->calculateHmac($data);
        // Dùng hash_equals để chống timing attack
        return hash_equals($calculated, $hmacHeader);
    }

    /**
     * Tính HMAC base64 theo chuẩn Shopify
     */
    private function calculateHmac(string $data): string
    {
        $secret = config('tf_common.shopify_api_secret');

        if (empty($secret)) {
            Log::error('Shopify API secret not set in config(tf_common.shopify_api_secret)');
        }

        return base64_encode(hash_hmac('sha256', $data, $secret, true));
    }

    /**
     * Cho phép bypass khi test local
     */
    private function byPassHeader(Request $request): bool
    {
        return (bool) $request->query('bypass_header', false);
    }
}
