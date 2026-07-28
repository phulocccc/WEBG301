<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminRole
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Lấy tên quyền của User hiện tại (Đã xử lý an toàn tránh bị null)
        $roleName = $user->role ? $user->role->name : '';

        // Kiểm tra xem quyền đó có nằm trong danh sách VIP không
        if (in_array($roleName, ['Admin', 'QA Manager'])) {
            // Có quyền -> Mở barie cho đi tiếp vào Controller
            return $next($request);
        }

        // Không có quyền -> Đuổi cổ, nhả lỗi 403 Forbidden
        return response()->json([
            'status' => 'error',
            'message' => 'Lỗi 403: Bạn không có quyền truy cập tính năng Quản trị!'
        ], 403);
    }
}
