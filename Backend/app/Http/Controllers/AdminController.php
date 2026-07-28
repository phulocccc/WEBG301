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
        // doesntHave('feedbacks'): Tuyệt chiêu của Laravel để lọc những bài mồ côi comment
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
        // Gộp 3 bảng lại để đếm số ý tưởng của từng phòng ban
        $chartData = DB::table('ideas')
            ->join('users', 'ideas.user_id', '=', 'users.id')
            ->join('departments', 'users.department_id', '=', 'departments.id')
            ->select('departments.name as department_name', DB::raw('COUNT(ideas.id) as total_ideas'))
            ->groupBy('departments.id', 'departments.name')
            ->get();

        // Tách ra 2 mảng riêng biệt
        $labels = $chartData->pluck('department_name');
        $data = $chartData->pluck('total_ideas');

        // Trả về đúng form JSON Frontend yêu cầu (Không bọc thêm chữ 'data' hay 'status' ở ngoài)
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
        // Kéo danh sách User kèm theo tên phòng ban của họ
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
            'role' => 'required|string', // Lưu ý: Nếu DB bạn dùng 'role_id' thì sửa lại chữ này
            'department_id' => 'required|exists:departments,id' // Bắt buộc phải thuộc về 1 khoa nào đó
        ]);

        // 2. Lưu vào Database
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            // 🔥 Cực kỳ quan trọng: Phải băm mật khẩu ra, nếu không User sẽ không thể login được!
            'password' => Hash::make($request->password),
            'role_id' => $request->role,  // <-- ĐÃ SỬA CHỮ 'role' THÀNH 'role_id' Ở ĐÂY NÈ
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
            // Rule unique: Bỏ qua chính ID của user này để không bị báo trùng email cũ
            'email' => ['required', 'email', Rule::unique('users')->ignore($id)],
            // Password để nullable (không bắt buộc), chỉ bắt nhập nếu muốn đổi
            'password' => 'nullable|string|min:6',
            'role' => 'required',
            'department_id' => 'required|exists:departments,id'
        ]);

        // 2. CHUẨN BỊ DỮ LIỆU ĐỂ CẬP NHẬT
        $updateData = [
            'name' => $request->name,
            'email' => $request->email,
            'role_id' => $request->role, // Map biến 'role' của Frontend vào 'role_id'
            'department_id' => $request->department_id,
        ];

        // 3. XỬ LÝ PASSWORD (Chỉ đổi nếu có nhập mới)
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
        // Tìm User (nếu không thấy sẽ tự văng lỗi 404 chuẩn chỉ)
        $user = User::findOrFail($id);

        // Đã bọc Soft Deletes ở Model nên lệnh delete() này vô cùng an toàn.
        // Nó KHÔNG xóa mất data, mà chỉ điền ngày giờ vào cột 'deleted_at'.
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
    // (Lưu ý: Frontend gửi POST + _method:PUT thì Route này vẫn hoạt động bình thường)
    // =========================================================================
    public function forceUpdateIdea(Request $request, $id)
    {
        $idea = Idea::findOrFail($id);

        // 1. Validate dữ liệu (Mở khóa cho content và file)
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'nullable|string',
            'file' => 'nullable|file|max:10240' // Giới hạn file 10MB
        ]);

        // 2. Cập nhật phần Chữ (Title, Content)
        $idea->update([
            'title' => $request->input('title'),
            'content' => $request->input('content') ?? $idea->content,
        ]);

        // 3. Xử lý File (Đúng chuẩn "Xóa cũ - Lưu mới" Frontend dặn)
        if ($request->hasFile('file')) {
            $newFile = $request->file('file');

            // Laravel lưu file và trả về kiểu 'attachments/file.pdf'
            $path = $newFile->store('attachments', 'public');

            // 🔥 FIX LỖI DÍNH CHÙM: Tự động thêm '/storage/' vào trước đường dẫn
            $finalPath = '/storage/' . $path;

            $oldAttachment = $idea->attachments()->first();

            if ($oldAttachment) {
                // Xóa file cũ
                if (Storage::disk('public')->exists($oldAttachment->file_path)) {
                    Storage::disk('public')->delete($oldAttachment->file_path);
                }

                // Cập nhật đường dẫn chuẩn vào Database
                $oldAttachment->update([
                    'file_name' => $newFile->getClientOriginalName(),
                    'file_path' => $finalPath // <-- ĐÃ SỬA CHỖ NÀY
                ]);
            } else {
                // Tạo mới đường dẫn chuẩn
                $idea->attachments()->create([
                    'file_name' => $newFile->getClientOriginalName(),
                    'file_path' => $finalPath // <-- VÀ CẢ CHỖ NÀY
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Admin đã ép cập nhật ý tưởng và đính kèm thành công!',
            // Trả về data mới nhất kèm thông tin file cho Frontend load lại giao diện
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

        // 🚀 BƯỚC QUAN TRỌNG: XÓA DÂY CHUYỀN (CASCADE) ĐỂ TRÁNH LỖI KHÓA NGOẠI

        // 1. Gỡ liên kết với các Danh mục (Category) trong bảng trung gian
        $idea->categories()->detach();

        // 2. Xóa sạch Bình luận thuộc về bài viết này
        // (Lưu ý: Nếu hàm trong Model Idea của bạn tên là 'comments' thì sửa lại chữ 'feedbacks' thành 'comments')
        $idea->feedbacks()->delete();

        // 3. Cắt đứt các liên kết khác (Nếu DB bạn có làm bảng Reactions hay Attachments thì mở comment ra)
        // $idea->reactions()->delete();
        // $idea->attachments()->delete();

        // 4. Cuối cùng, kết liễu bài viết
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

        // Ghi BOM để Excel đọc tiếng Việt UTF-8 không bị lỗi font
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
            // Lấy toàn bộ file đính kèm trong Database (Nếu bảng của bạn tên khác thì đổi chỗ Attachment)
            $attachments = \App\Models\Attachment::all();
            $hasFiles = false;

            foreach ($attachments as $attachment) {
                // Xử lý cắt bỏ chữ '/storage/' để lấy đường dẫn thật sự trong ổ cứng
                $cleanPath = str_replace('/storage/', '', $attachment->file_path);
                $absolutePath = storage_path('app/public/' . $cleanPath);

                // Nếu file thực sự tồn tại trên ổ cứng thì mới nhét vào ZIP
                if (file_exists($absolutePath) && !is_dir($absolutePath)) {
                    // Nhét file vào zip và lấy tên gốc do người dùng đặt để lưu
                    $zip->addFile($absolutePath, $attachment->file_name);
                    $hasFiles = true;
                }
            }
            $zip->close();

            // Nếu không có file nào thì báo lỗi, xóa file zip rỗng đi
            if (!$hasFiles) {
                if (file_exists($zipPath)) unlink($zipPath);
                return response('Hệ thống hiện tại chưa có file đính kèm nào!', 404);
            }

            // Trả về file ZIP để tải xuống và tự dọn rác
            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return response('Không thể nén file ZIP do lỗi máy chủ', 500);
    }
}
