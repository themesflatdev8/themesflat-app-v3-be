<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\TrimStrings as Middleware;

class TrimStrings extends Middleware
{
    /**
     * The names of the attributes that should not be trimmed.
     *
     * @var array<int, string>
     */
    protected $except = [
        'current_password',
        'password',
        'password_confirmation',
        // Không cần thêm gì vào đây vì chúng ta sẽ bypass toàn bộ webhook
    ];

    /**
     * Xử lý request.
     * Thêm logic để bỏ qua (bypass) middleware này cho các request webhook.
     *
     * @param \Illuminate\Http\Request $request
     * @param \Closure $next
     * @return mixed
     */
    public function handle($request, \Closure $next)
    {
        if ($request->is('api/webhook/*')) {
            // Nếu là webhook, chúng ta BỎ QUA middleware TrimStrings
            return $next($request);
        }

        // Nếu không phải webhook, chạy logic TrimStrings mặc định
        return parent::handle($request, $next);
    }
}
