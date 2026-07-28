<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    // =========================================================================
    // API: ĐĂNG NHẬP HỆ THỐNG
    // Route: POST /api/login
    // =========================================================================
    public function login(Request $request)
    {
        // 1. Kiểm tra dữ liệu đầu vào
        $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        // 2. Xác thực thông tin
        if (!Auth::attempt($request->only('email', 'password'))) {
            return response()->json([
                'status' => 'error',
                'message' => 'Email hoặc mật khẩu không chính xác!'
            ], 401);
        }

        // 3. Lấy thông tin user (Đọc thần chú ép kiểu để VS Code không gạch đỏ)
        /** @var \App\Models\User $user */
        $user = Auth::user();

        // Load thêm quan hệ role và department (Nếu bảng users của bạn có nối khóa ngoại)
        // Nếu DB của bạn không nối mà lưu chữ cứng vào cột role thì có thể bỏ dòng load() này đi cũng không sao.
        $user->load(['role', 'department']);

        // 4. Tạo token (Sanctum)
        $token = $user->createToken('auth_token')->plainTextToken;

        // 5. Trả về cục JSON kèm ROLE cho Frontend
        return response()->json([
            'status' => 'success',
            'message' => 'Đăng nhập thành công',
            'token' => $token,
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                // 🔥 ĐÂY LÀ CHỖ FRONTEND CẦN ĐỂ PHÂN QUYỀN NÈ:
                // Nếu bảng users nối với bảng roles, lấy $user->role->role_name
                // Nếu bảng users lưu thẳng chữ 'Admin', 'Staff' thì chỉ cần $user->role
                'role' => $user->role,
                'department_id' => $user->department_id
            ]
        ], 200);
    }

    // =========================================================================
    // API: ĐĂNG XUẤT
    // Route: POST /api/logout
    // =========================================================================
    public function logout(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = $request->user();

        // Tách ra một biến và "đọc thần chú" cho VS Code hết hoảng sợ
        /** @var \Laravel\Sanctum\PersonalAccessToken $token */
        $token = $user->currentAccessToken();

        // Hủy token hiện tại
        $token->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Đăng xuất thành công!'
        ], 200);
    }

    // =========================================================================
    // API: ĐĂNG KÝ (Dùng để seed data hoặc test nhanh nếu cần)
    // Route: POST /api/register
    // =========================================================================
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            // Default role hoặc department_id nếu cần có thể set cứng ở đây lúc test
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Đăng ký thành công',
            'token' => $token,
            'data' => $user
        ], 201);
    }
}
