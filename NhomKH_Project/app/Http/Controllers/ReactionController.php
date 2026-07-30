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
            'vote_type' => 'required|in:1,-1'
        ]);

        $reaction = Reaction::updateOrCreate(
            ['idea_id' => $ideaId, 'user_id' => $validated['user_id']],
            ['vote_type' => $validated['vote_type']]
        );
        // =========================================================
        // LOGIC BẮN THÔNG BÁO KHI CÓ NGƯỜI REACT BÀI VIẾT
        // =========================================================

        // 1. Sửa $id thành $ideaId cho khớp với tham số trên hàm
        $idea = \App\Models\Idea::findOrFail($ideaId);

        if ($idea->user_id !== $request->user()->id) {

            // 2. Lấy vote_type
            $action = $request->input('vote_type') == 1 ? 'thích' : 'không thích';

            \App\Models\Notification::create([
                'user_id' => $idea->user_id,
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
