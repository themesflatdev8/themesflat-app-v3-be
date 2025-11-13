<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;


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
            $hmacHeader = strval($request->header('X-Shopify-Hmac-SHA256'));
            $data = strval(file_get_contents('php://input'));

            if (! $this->verifyWebhook($data, $hmacHeader)) {
                Log::warning('Invalid HMAC header for Shopify webhook', [
                    'headers' => $request->headers->all(),
                    'body' => $data,
                ]);
                return response([
                    'error' => 'Invalid HMAC header',
                ], SymfonyResponse::HTTP_UNAUTHORIZED);
            }


            return $next($request);
        } catch (\Exception $e) {
            $this->sentry->captureException($e);
            return response()->json([], 401);
        }
    }

    private function verifyWebhook(string $data, string $hmacHeader): bool
    {
        $calculated_hmac = base64_encode(hash_hmac('sha256', $data, config('shopify-webhook.signature'), true));

        return hash_equals($calculated_hmac, $hmacHeader);
    }
}
