<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
use App\Http\Controllers\IdeaController;

Route::get('/ideas', [IdeaController::class, 'index']);
Route::post('/ideas', [IdeaController::class, 'store']);
Route::middleware('auth:sanctum')->post('/api/ideas', [IdeaController::class, 'store']);
use App\Http\Controllers\CommentController;

Route::post('/ideas/{ideaId}/comments', [CommentController::class, 'store']);
use App\Http\Controllers\ReactionController;

Route::post('/ideas/{ideaId}/react', [ReactionController::class, 'store']);
use App\Http\Controllers\ReportController;

// Thống kê & Xuất file
Route::get('/reports/statistics', [ReportController::class, 'statistics']);
Route::get('/reports/export-csv', [ReportController::class, 'exportCsv']);
Route::get('/reports/export-zip', [ReportController::class, 'exportZip']);
use App\Http\Controllers\AuthController;

Route::get('/login', function () {
    return response()->json([
        'status' => 'error',
        'message' => 'Lỗi 401: Token không hợp lệ hoặc thiếu Accept JSON!'
    ], 401);
})->name('login');

Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->post('/ideas', [IdeaController::class, 'store']);
