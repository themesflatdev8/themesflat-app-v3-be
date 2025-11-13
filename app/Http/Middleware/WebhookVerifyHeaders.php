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
            if ($this->byPassHeader($request)) {
                Log::info('Bypass webhook verify for local testing');
                return $next($request);
            }

            $headerHmac = $request->header('X-Shopify-Hmac-Sha256');

            if (!$headerHmac) {
                $msg = 'Missing X-Shopify-Hmac-Sha256 header';
                Log::warning($msg, ['path' => $request->path()]);
                $this->sentry->captureMessage($msg);
                return response()->json([], 401);
            }

            // 🏆 FIX CUỐI CÙNG: Lấy body trực tiếp từ input stream PHP.
            // Điều này đảm bảo chúng ta có RAW body 100% gốc,
            // bỏ qua mọi cơ chế can thiệp tiềm ẩn của Laravel/Symfony.
            $data = file_get_contents('php://input');

            Log::debug('Shopify webhook received', [
                'topic' => $request->header('X-Shopify-Topic'),
                'hmac_header_prefix' => substr($headerHmac, 0, 10) . '...',
                'body_length' => strlen($data),
            ]);

            $verified = $this->verifyWebhook($data, $headerHmac);

            if ($verified) {
                // Nếu xác thực thành công, chúng ta cần gán lại body vào Request object
                // để controller (hoặc các middleware tiếp theo) có thể đọc được.
                $request->replace((array) json_decode($data, true));
                return $next($request);
            }

            // ❌ Không verify được → log chi tiết
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

        // ❌ Xóa log debug Secret Key sau khi đã xác nhận nó đúng

        return base64_encode(hash_hmac('sha256', $data, $secret, true));
    }

    private function byPassHeader(Request $request): bool
    {
        return (bool) $request->query('bypass_header', false);
    }
}
