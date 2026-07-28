<?php

namespace App\Http\Controllers;

use App\Models\Reaction;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function store(Request $request, $ideaId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'vote_type' => 'required|in:1,-1' // 1: Thumbs Up, -1: Thumbs Down
        ]);

        // Hàm updateOrCreate giúp giữ luật: 1 User chỉ có 1 Reaction cho 1 Idea [cite: 22, 612]
        $reaction = Reaction::updateOrCreate(
            ['idea_id' => $ideaId, 'user_id' => $validated['user_id']], // Điều kiện tìm kiếm
            ['vote_type' => $validated['vote_type']] // Dữ liệu cập nhật hoặc tạo mới
        );
        // =========================================================
        // LOGIC BẮN THÔNG BÁO KHI CÓ NGƯỜI REACT BÀI VIẾT
        // =========================================================

        // 1. Sửa $id thành $ideaId cho khớp với tham số trên hàm
        $idea = \App\Models\Idea::findOrFail($ideaId);

        // Quy tắc số 1: Không tự kỷ bắn thông báo cho chính mình (Tự like tự xem)
        if ($idea->user_id !== $request->user()->id) {

            // 2. Lấy vote_type (1 hoặc -1) để dịch ra tiếng Việt cho chuẩn
            $action = $request->input('vote_type') == 1 ? 'thích' : 'không thích';

            \App\Models\Notification::create([
                'user_id' => $idea->user_id, // Gửi đích danh cho chủ bài viết
                'type' => 'reaction',
                'message' => $request->user()->name . ' đã ' . $action . ' bài viết của bạn.',
                'idea_id' => $idea->id
            ]);
        }
        // =========================================================

        return response()->json([
            'status' => 'success',
            'message' => 'Đã ghi nhận tương tác của bạn!',
            'data' => $reaction
        ]);
    }
}
