<?php

namespace App\Http\Controllers;


use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use App\Mail\NewCommentMail;
use App\Models\Idea;

class CommentController extends Controller
{
    public function store(Request $request, $ideaId)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'content' => 'required|string',
            'is_anonymous' => 'boolean'
        ]);

        $comment = Comment::create([
            'idea_id' => $ideaId,
            'user_id' => $validated['user_id'],
            'content' => $validated['content'],
            'is_anonymous' => $validated['is_anonymous'] ?? false,
        ]);
        // --- GỬI EMAIL CHO TÁC GIẢ Ý TƯỞNG ---
        $idea = Idea::with('user')->find($ideaId);

        if ($idea->user->id !== $comment->user_id) {
            Mail::to($idea->user->email)->send(new NewCommentMail($comment));
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Thêm bình luận thành công!',
            'data' => $comment
        ]);
    }
}
