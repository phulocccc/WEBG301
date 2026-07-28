<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use App\Http\Controllers\IdeaController;

// Khi người dùng vào đường dẫn /ideas, chạy hàm index trong IdeaController
Route::get('/ideas', [IdeaController::class, 'index']);
// Khi người dùng gửi dữ liệu POST lên /ideas, chạy hàm store
Route::post('/ideas', [IdeaController::class, 'store']);
Route::middleware('auth:sanctum')->post('/api/ideas', [IdeaController::class, 'store']);
use App\Http\Controllers\CommentController;

// Khi POST vào link có dạng /ideas/1/comments thì sẽ thêm comment cho Idea số 1
Route::post('/ideas/{ideaId}/comments', [CommentController::class, 'store']);
use App\Http\Controllers\ReactionController;

// URL để thả react: /ideas/1/react
Route::post('/ideas/{ideaId}/react', [ReactionController::class, 'store']);
use App\Http\Controllers\ReportController;

// Thống kê & Xuất file
Route::get('/reports/statistics', [ReportController::class, 'statistics']);
Route::get('/reports/export-csv', [ReportController::class, 'exportCsv']);
Route::get('/reports/export-zip', [ReportController::class, 'exportZip']);
use App\Http\Controllers\AuthController;

// 1. CÁI BẪY LỖI (Dùng GET) - Code mình đưa lúc nãy
Route::get('/login', function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Lỗi 401: Token không hợp lệ hoặc thiếu Accept JSON!'
    ], 401);
})->name('login');

// 2. API ĐĂNG NHẬP CHÍNH THỨC (Dùng POST) - Code xử lý login của bạn
Route::post('/login', [AuthController::class, 'login']);
// Route bắt buộc phải kẹp middleware này vào để nó biết đường đọc Token từ Frontend gửi lên
Route::middleware('auth:sanctum')->post('/ideas', [IdeaController::class, 'store']);
