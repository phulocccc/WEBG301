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

        // Map dữ liệu để format đúng y xì đúc requirement của Frontend
        $formatted = $notifications->map(function ($notif) {
            return [
                'id' => $notif->id,
                'type' => $notif->type,
                'message' => $notif->message,
                'is_read' => $notif->is_read,
                'idea_id' => $notif->idea_id,
                // Format ngày giờ ra chuẩn ISO 8601 giống ảnh (có chữ T và Z)
                'created_at' => $notif->created_at->toIso8601String()
            ];
        });

        // Frontend yêu cầu trả về một mảng [] trực tiếp, không bọc trong data hay status
        return response()->json($formatted, 200);
    }
}
