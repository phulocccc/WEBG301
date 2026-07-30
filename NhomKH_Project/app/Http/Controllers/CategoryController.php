<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Category;

class CategoryController extends Controller
{
    // =========================================================================
    // API: LẤY DANH SÁCH DANH MỤC (KÈM SỐ LƯỢNG BÀI VIẾT)
    // Route: GET /api/admin/categories
    // =========================================================================
    public function index()
    {
        $categories = Category::withCount('ideas')->get();

        return response()->json([
            'status' => 'success',
            'data' => $categories
        ], 200);
    }

    // =========================================================================
    // API: THÊM DANH MỤC MỚI
    // Route: POST /api/admin/categories
    // =========================================================================
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|unique:categories,name'
        ]);

        $category = Category::create([
            'name' => $request->name
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã thêm danh mục thành công!',
            'data' => $category
        ], 201);
    }

    // =========================================================================
    // API: XÓA DANH MỤC (CHẶN XÓA NẾU ĐANG CÓ BÀI VIẾT)
    // Route: DELETE /api/admin/categories/{id}
    // =========================================================================
    public function destroy($id)
    {
        // 1. Tìm danh mục cần xóa
        $category = Category::findOrFail($id);

        // 2. LOGIC CHẶN CỬA: Đếm xem có Idea nào đang xài Category này không
        if ($category->ideas()->count() > 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Không thể xóa! Danh mục này đang chứa bài viết. Vui lòng xóa hết bài viết trước.'
            ], 400);
        }

        // 3. Nếu đếm ra = 0 (danh mục trống) -> Cho phép xóa
        $category->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Đã xóa danh mục thành công!'
        ], 200);
    }
}
