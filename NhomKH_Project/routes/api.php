<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\IdeaController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\NotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// --- 1. CÁC API KHÔNG CẦN ĐĂNG NHẬP (PUBLIC) ---

Route::get('/login', function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Lỗi 401: Token không hợp lệ hoặc Frontend quên truyền Header "Accept: application/json"!'
    ], 401);
})->name('login');

// API Đăng nhập
Route::post('/login', [AuthController::class, 'login']);

// API Đăng ký (Nếu bạn có làm)
Route::post('/register', [AuthController::class, 'register']);


// --- 2. CÁC API BẮT BUỘC PHẢI CÓ TOKEN (PRIVATE) ---

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/notifications', [NotificationController::class, 'index']);


    Route::get('/user', function (Request $request) {
        $user = $request->user()->load('department', 'role');

        return response()->json([
            'status' => 'success',
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role ? $user->role->name : 'Staff',
                'department_id' => $user->department_id,
                'department_name' => $user->department ? $user->department->name : ''
            ]
        ]);
    });

    // --------------------------------------------------------
    // MODULE: Ý TƯỞNG (IDEAS)
    // --------------------------------------------------------

    // CỬA SỐ 1: Lấy danh sách ý tưởng đổ ra Dashboard
    Route::get('/ideas', [IdeaController::class, 'index']);

    // CỬA SỐ 2: Nộp ý tưởng mới
    Route::post('/ideas', [IdeaController::class, 'store']);

    // CỬA SỐ 3: Ghim / Gỡ ghim bài nổi bật (Dành cho Sếp)
    Route::post('/ideas/{id}/feature', [IdeaController::class, 'toggleFeature']);

    // CỬA SỐ 4: Xem chi tiết 1 Idea
    Route::get('/ideas/{id}', [IdeaController::class, 'show']);

    // CỬA SỐ 5: Gửi Comment vào Idea
    Route::post('/ideas/{id}/comments', [IdeaController::class, 'storeComment']);

    // CỬA SỐ 6: Thả Like / Dislike
    Route::post('/ideas/{id}/vote', [IdeaController::class, 'vote']);

    // CỬA SỐ 7: Tăng lượt xem (View)
    Route::post('/ideas/{id}/view', [IdeaController::class, 'incrementView']);
    Route::get('/categories', [\App\Http\Controllers\CategoryController::class, 'index']);


    // --------------------------------------------------------
    // KHU VỰC DÀNH CHO ADMIN / TÍNH NĂNG NÂNG CAO
    // --------------------------------------------------------

    Route::prefix('admin')->middleware(['auth:sanctum', \App\Http\Middleware\CheckAdminRole::class])->group(function () {

        // NHÓM 1: THỐNG KÊ & CATEGORY
        Route::get('/statistics', [\App\Http\Controllers\AdminController::class, 'statistics']);


        Route::post('/categories', [\App\Http\Controllers\CategoryController::class, 'store']);
        Route::delete('/categories/{id}', [\App\Http\Controllers\CategoryController::class, 'destroy']);
        // API Dữ liệu Biểu đồ
        Route::get('/chart-data', [\App\Http\Controllers\AdminController::class, 'chartData']);
        // API Quản lý Tài khoản
        Route::get('/users', [\App\Http\Controllers\AdminController::class, 'indexUsers']);
        Route::post('/users', [\App\Http\Controllers\AdminController::class, 'storeUser']);

        // NHÓM 2: BÁO CÁO NGOẠI LỆ
        Route::get('/exceptions/no-comments', [\App\Http\Controllers\AdminController::class, 'noComments']);
        Route::get('/exceptions/anonymous', [\App\Http\Controllers\AdminController::class, 'anonymousIdeas']);

        // API Update và Delete User
        Route::put('/users/{id}', [\App\Http\Controllers\AdminController::class, 'updateUser']);
        Route::delete('/users/{id}', [\App\Http\Controllers\AdminController::class, 'destroyUser']);
        // API Admin "sinh sát" Ý tưởng
        Route::put('/ideas/{id}', [\App\Http\Controllers\AdminController::class, 'forceUpdateIdea']);
        Route::delete('/ideas/{id}', [\App\Http\Controllers\AdminController::class, 'forceDeleteIdea']);
        // API EXPORT FILE DÀNH CHO ADMIN


    });


    // --------------------------------------------------------
    // ĐĂNG XUẤT
    // --------------------------------------------------------
    Route::post('/logout', [AuthController::class, 'logout']);
});
// =========================================================================
// API EXPORT FILE DÀNH CHO ADMIN
// =========================================================================
Route::get('/admin/export/csv', [\App\Http\Controllers\AdminController::class, 'exportCsv']);
Route::get('/admin/export/zip', [\App\Http\Controllers\AdminController::class, 'exportZip']);
