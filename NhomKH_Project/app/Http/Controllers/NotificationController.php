<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Notification;

class NotificationController extends Controller
{
    public function index(Request $request)
    {
        // Lấy thông báo của user đang đăng nhập, xếp mới nhất lên đầu
        $notifications = Notification::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->get();

        $formatted = $notifications->map(function ($notif) {
            return [
                'id' => $notif->id,
                'type' => $notif->type,
                'message' => $notif->message,
                'is_read' => $notif->is_read,
                'idea_id' => $notif->idea_id,
                'created_at' => $notif->created_at->toIso8601String()
            ];
        });

        return response()->json($formatted, 200);
    }
}
