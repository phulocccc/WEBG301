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

        // Lấy tên quyền của User hiện tại
        $roleName = $user->role ? $user->role->name : '';

        if (in_array($roleName, ['Admin', 'QA Manager'])) {
            return $next($request);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Lỗi 403: Bạn không có quyền truy cập tính năng Quản trị!'
        ], 403);
    }
}
