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
        // 🚨 BƯỚC QUAN TRỌNG NHẤT: Kiểm tra xem request có phải là webhook không.
        // Bạn đã cấu hình route webhook là 'webhook/order/create', v.v...
        // vì vậy, tất cả các URL bắt đầu bằng 'webhook/' nên được bỏ qua.

        if ($request->is('webhook/*')) {
            // Nếu là webhook, chúng ta BỎ QUA middleware TrimStrings
            return $next($request);
        }

        // Nếu không phải webhook, chạy logic TrimStrings mặc định
        return parent::handle($request, $next);
    }
}
