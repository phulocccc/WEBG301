<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\DB;
use App\Models\Idea;
use App\Models\Feedback;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    // 1. LẤY THỐNG KÊ TỔNG QUAN
    public function statistics()
    {
        $totalIdeas = Idea::count();
        $totalComments = Feedback::count();
        // Đếm số người (user_id) đã từng đăng ít nhất 1 bài
        $totalContributors = Idea::distinct('user_id')->count('user_id');

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_ideas' => $totalIdeas,
                'total_comments' => $totalComments,
                'total_contributors' => $totalContributors
            ]
        ], 200);
    }

    // 2. BÁO CÁO: BÀI VIẾT KHÔNG CÓ BÌNH LUẬN
    public function noComments()
    {
        $ideas = Idea::doesntHave('feedbacks')
                     ->with(['user.department', 'categories'])
                     ->get();

        return response()->json(['status' => 'success', 'data' => $ideas], 200);
    }

    // 3. BÁO CÁO: BÀI VIẾT ẨN DANH
    public function anonymousIdeas()
    {
        $ideas = Idea::where('is_anonymous', 1)
                     ->with(['user.department', 'categories'])
                     ->get();

        return response()->json(['status' => 'success', 'data' => $ideas], 200);
    }

    // 4. API: DỮ LIỆU BIỂU ĐỒ PIE CHART
    public function chartData()
    {
        $chartData = DB::table('ideas')
            ->join('users', 'ideas.user_id', '=', 'users.id')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->select('departments.name as department_name', DB::raw('COUNT(ideas.id) as total_ideas'))
            ->groupBy('departments.id', 'departments.name')
            ->get();

        // Tách ra 2 mảng riêng biệt
        $labels = $chartData->pluck('department_name');
        $data = $chartData->pluck('total_ideas');

        return response()->json([
            'labels' => $labels,
            'data' => $data
        ], 200);
    }

    // =========================================================================
    // 5. API: LẤY DANH SÁCH USER (Kèm thông tin Phòng ban)
    // Route: GET /api/admin/users
    // =========================================================================
    public function indexUsers()
    {
        $users = User::with('department')->orderBy('created_at', 'desc')->get();

        return response()->json([
            'status' => 'success',
            'data' => $users
        ], 200);
    }

    // =========================================================================
    // 6. API: ADMIN TẠO TÀI KHOẢN MỚI
    // Route: POST /api/admin/users
    // =========================================================================
    public function storeUser(Request $request)
    {
        // 1. Rào soát dữ liệu Frontend gửi lên
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'role' => 'required|string',
            'department_id' => 'required|exists:departments,id'
        ]);

        // 2. Lưu vào Database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role_id' => $request->role,
            'department_id' => $request->department_id
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Đã cấp tài khoản mới thành công!',
            'data' => $user
        ], 201);
    }

    // =========================================================================
    // 7. API: ADMIN CẬP NHẬT THÔNG TIN USER
    // Route: PUT /api/admin/users/{id}
    // =========================================================================
    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        // 1. VALIDATION THÔNG MINH
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($id)],
            'password' => 'nullable|string|min:6',
            'role' => 'required',
            'department_id' => 'required|exists:departments,id'
        ]);

        // 2. CHUẨN BỊ DỮ LIỆU ĐỂ CẬP NHẬT
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role,
            'department_id' => $request->department_id,
        ];

        // 3. XỬ LÝ PASSWORD
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        return response()->json([
            'status' => 'success',
            'message' => 'Cập nhật tài khoản thành công!',
            'data' => $user->load('department', 'role')
        ], 200);
    }

    // =========================================================================
    // 8. API: ADMIN XÓA USER (SOFT DELETE - GIỮ LẠI BÀI VIẾT)
    // Route: DELETE /api/admin/users/{id}
    // =========================================================================
    public function destroyUser($id)
    {
        $user = User::findOrFail($id);
        $user->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Đã khóa/xóa tài khoản thành công (Dữ liệu bài viết vẫn được giữ nguyên)!'
        ], 200);
    }
    // =========================================================================
    // =========================================================================
    // 9. API: ADMIN ÉP SỬA Ý TƯỞNG BẤT KỲ (HỖ TRỢ ĐỔI CẢ FILE)
    // Route: PUT /api/admin/ideas/{id}
    // =========================================================================
    public function forceUpdateIdea(Request $request, $id)
    {
        $idea = Idea::findOrFail($id);

        // 1. Validate dữ liệu
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240' // Giới hạn file 10MB
        ]);

        // 2. Cập nhật phần Chữ
        $idea->update([
            'title' => $request->input('title'),
            'content' => $request->input('content') ?? $idea->content,
        ]);

        // 3. Xử lý File
        if ($request->hasFile('file')) {
            $newFile = $request->file('file');

            $path = $newFile->store('attachments', 'public');

            $finalPath = '/storage/' . $path;

            $oldAttachment = $idea->attachments()->first();

            if ($oldAttachment) {
                // Xóa file cũ
                if (Storage::disk('public')->exists($oldAttachment->file_path)) {
                    Storage::disk('public')->delete($oldAttachment->file_path);
                }

                $oldAttachment->update([
                    'file_name' => $newFile->getClientOriginalName(),
                    'file_path' => $finalPath
                ]);
            } else {
                // Tạo mới đường dẫn chuẩn
                $idea->attachments()->create([
                    'file_name' => $newFile->getClientOriginalName(),
                    'file_path' => $finalPath
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Admin đã ép cập nhật ý tưởng và đính kèm thành công!',
            'data' => $idea->load('attachments')
        ], 200);
    }

    // =========================================================================
    // 10. API: ADMIN ÉP XÓA TẬN GỐC Ý TƯỞNG BẤT KỲ (CASCADE DELETE)
    // Route: DELETE /api/admin/ideas/{id}
    // =========================================================================
    public function forceDeleteIdea($id)
    {
        $idea = Idea::findOrFail($id);


        // 1. Gỡ liên kết với các Danh mục (Category) trong bảng trung gian
        $idea->categories()->detach();

        // 2. Xóa sạch Bình luận thuộc về bài viết này
        $idea->feedbacks()->delete();

        // 3. Cắt đứt các liên kết khác

        $idea->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Admin đã xóa tận gốc bài viết và toàn bộ dữ liệu liên quan!'
        ], 200);
    }
    // =========================================================================
    // 11. API: ADMIN TẢI XUỐNG FILE CSV (TỔNG HỢP IDEAS)
    // Route: GET /api/admin/export/csv?token={token}
    // =========================================================================
    public function exportCsv(Request $request)
    {
        // 1. Check quyền Admin thủ công từ Token trên URL
        $token = $request->query('token');
        $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;

        if (!$user) {
            return response('Không có quyền truy cập (Unauthorized)', 401);
        }

        // 2. Kéo dữ liệu Ideas kèm theo Tác giả và Khoa
        $ideas = Idea::with(['user.department'])->get();

        // 3. Tạo file CSV
        $filename = "Export_Ideas_" . date('Ymd_His') . ".csv";
        $filePath = storage_path('app/public/' . $filename);
        $handle = fopen($filePath, 'w');

        fputs($handle, $bom =( chr(0xEF) . chr(0xBB) . chr(0xBF) ));

        // Ghi dòng tiêu đề (Header)
        fputcsv($handle, ['ID', 'Tiêu đề', 'Tác giả', 'Khoa', 'Lượt xem', 'Ẩn danh', 'Ngày đăng']);

        // Ghi từng dòng dữ liệu
        foreach ($ideas as $idea) {
            fputcsv($handle, [
                $idea->id,
                $idea->title,
                $idea->is_anonymous ? 'Ẩn danh' : ($idea->user->name ?? 'Không rõ'),
                $idea->user->department->name ?? 'Không rõ',
                $idea->view_count ?? 0,
                $idea->is_anonymous ? 'Có' : 'Không',
                $idea->created_at->format('d/m/Y H:i:s')
            ]);
        }
        fclose($handle);

        // 4. Trả file về cho trình duyệt tải xuống, sau đó tự động xóa file rác trong server
        return response()->download($filePath)->deleteFileAfterSend(true);
    }

    // =========================================================================
    // 12. API: ADMIN TẢI XUỐNG FILE ZIP (TOÀN BỘ ĐÍNH KÈM)
    // Route: GET /api/admin/export/zip?token={token}
    // =========================================================================
    public function exportZip(Request $request)
    {
        // 1. Check quyền Admin thủ công
        $token = $request->query('token');
        $user = \Laravel\Sanctum\PersonalAccessToken::findToken($token)?->tokenable;

        if (!$user) {
            return response('Không có quyền truy cập (Unauthorized)', 401);
        }

        // 2. Tạo file ZIP mới
        $zip = new \ZipArchive;
        $fileName = 'All_Attachments_' . date('Ymd_His') . '.zip';
        $zipPath = storage_path('app/public/' . $fileName);

        if ($zip->open($zipPath, \ZipArchive::CREATE) === TRUE) {
            $attachments = \App\Models\Attachment::all();
            $hasFiles = false;

            foreach ($attachments as $attachment) {
                $cleanPath = str_replace('/storage/', '', $attachment->file_path);
                $absolutePath = storage_path('app/public/' . $cleanPath);

                if (file_exists($absolutePath) && !is_dir($absolutePath)) {
                    $zip->addFile($absolutePath, $attachment->file_name);
                    $hasFiles = true;
                }
            }
            $zip->close();

            if (!$hasFiles) {
                if (file_exists($zipPath)) unlink($zipPath);
                return response('Hệ thống hiện tại chưa có file đính kèm nào!', 404);
            }

            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return response('Không thể nén file ZIP do lỗi máy chủ', 500);
    }
}
