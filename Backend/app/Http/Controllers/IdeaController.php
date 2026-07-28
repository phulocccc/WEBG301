<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\AcademicYear;
use App\Models\Attachment;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\File; // Thêm File Facade để dùng cho ZIP
use App\Mail\NewIdeaMail;
use Carbon\Carbon;
use App\Models\Notification;

class IdeaController extends Controller
{
    /**
     * 1. API: Lấy danh sách Ý tưởng cho Dashboard
     * Route: GET /api/ideas
     */
    public function index(Request $request)
    {
        $query = Idea::query()
            ->with([
                'user' => function ($q) {
                    $q->select('id', 'name', 'department_id')->with('department:id,name');
                },
                'categories:id,name'
            ])
            ->withCount([
                'feedbacks as comments_count',
                'reactions as likes_count' => function ($q) {
                    $q->where('vote_type', 1);
                },
                'reactions as dislikes_count' => function ($q) {
                    $q->where('vote_type', -1);
                }
            ]);

        // Mặc định sắp xếp theo bài mới nhất
        $query->orderBy('created_at', 'desc');

        // Frontend tự lo phân trang nên Backend nhả full toàn bộ dữ liệu ra
        $ideas = $query->get();

        return response()->json([
            'status' => 'success',
            'data' => $ideas
        ]);
    }

    /**
     * 2. API: Staff nộp ý tưởng mới
     * Route: POST /api/ideas
     */
    public function store(Request $request)
    {
        // --- A. KIỂM TRA THỜI GIAN (ACADEMIC YEAR) ---
        $now = now();
        $currentYear = AcademicYear::where('final_closure_date', '>=', $now)
                                    ->orderBy('closure_date', 'asc')
                                    ->first();

        if (!$currentYear) {
            return response()->json([
                'status' => 'error',
                'message' => 'Hệ thống hiện không có đợt nộp ý tưởng nào đang mở!'
            ], 403);
        }

        if ($now > $currentYear->closure_date) {
            return response()->json([
                'status' => 'error',
                'message' => 'Đã quá thời hạn nộp bài cho đợt này!'
            ], 403);
        }

        // --- B. KIỂM TRA DỮ LIỆU (VALIDATION) ---
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'category_id' => 'required|exists:categories,id',
            'is_anonymous' => 'nullable',
            'priority_level' => 'nullable|string|in:Low,Medium,High'
        ]);

        // --- C. LƯU Ý TƯỞNG ---
        $idea = Idea::create([
            'title' => $validated['title'],
            'content' => $validated['content'],
            'user_id' => $request->user()->id,
            'academic_year_id' => $currentYear->id,
            'is_anonymous' => $request->boolean('is_anonymous'),
            'priority_level' => $request->input('priority_level', 'Medium'),
            'is_featured' => false,
        ]);

        // --- D. GẮN DANH MỤC (Bảng trung gian) ---
        $idea->categories()->attach([$validated['category_id']]);

        // --- E. XỬ LÝ FILE ĐÍNH KÈM ---
        if ($request->hasFile('files')) {
            foreach ($request->file('files') as $file) {
                $fileName = time() . '_' . $file->getClientOriginalName();
                $filePath = $file->storeAs('uploads', $fileName, 'public');

                Attachment::create([
                    'idea_id' => $idea->id,
                    'file_path' => '/storage/' . $filePath,
                    'file_name' => $fileName
                ]);
            }
        }

        // --- F. GỬI EMAIL THÔNG BÁO ---
        try {
            $qaRole = Role::where('role_name', 'QA Coordinator')->first();
            if ($qaRole) {
                $coordinator = User::where('department_id', $request->user()->department_id)
                                   ->where('role_id', $qaRole->id)
                                   ->first();

                if ($coordinator) {
                    Mail::to($coordinator->email)->send(new NewIdeaMail($idea));
                }
            }
        } catch (\Exception $e) {
            Log::error('Lỗi gửi mail: ' . $e->getMessage());
        }

        // =========================================================
        // LOGIC TỰ ĐỘNG BÁO CHO TẤT CẢ QA COORDINATOR TRONG CÙNG KHOA
        // =========================================================

        // 1. Cài đặt Role ID của chức vụ QA Coordinator (Giả sử trong bảng roles, QA là số 2)
        $qaRoleId = 3;

        // 2. Đi tìm TẤT CẢ những người làm QA Coordinator của Khoa đó
        // - Lọc department_id trùng với khoa của người đăng bài
        // - Lọc role_id là quyền QA Coordinator
        // - Dùng get() để lấy ra toàn bộ danh sách (dù là 1 người, 10 người hay 100 người)
        $qaCoordinators = \App\Models\User::where('department_id', $request->user()->department_id)
                                          ->where('role_id', $qaRoleId)
                                          ->get();

        // 3. Nếu danh sách này có người, ta dùng foreach để gửi thông báo cho TỪNG NGƯỜI một
        if ($qaCoordinators->isNotEmpty()) {
            foreach ($qaCoordinators as $qa) {
                \App\Models\Notification::create([
                    'user_id' => $qa->id, // Gửi chính xác cho ID của ông QA đang được gọi tên trong vòng lặp
                    'type' => 'new_idea',
                    'message' => 'Sinh viên ' . $request->user()->name . ' trong Khoa của bạn vừa nộp một ý tưởng mới.',
                    'idea_id' => $idea->id
                ]);
            }
        }
        // =========================================================
        // LOGIC BÁO CHO TẤT CẢ MỌI NGƯỜI CÙNG NGÀNH/KHOA
        // =========================================================

        // 1. Tìm tất cả "đồng môn" cùng khoa VÀ TRỪ BẢN THÂN NGƯỜI ĐĂNG RA
        $colleagues = \App\Models\User::where('department_id', $request->user()->department_id)
                                      ->where('id', '!=', $request->user()->id)
                                      ->get();

        // 2. Dùng súng liên thanh (foreach) bắn thông báo cho từng người
        if ($colleagues->isNotEmpty()) {
            foreach ($colleagues as $colleague) {
                \App\Models\Notification::create([
                    'user_id' => $colleague->id, // Gửi cho người đồng nghiệp
                    'type' => 'new_idea_department',
                    'message' => 'Thành viên ' . $request->user()->name . ' trong Khoa của bạn vừa chia sẻ một ý tưởng mới. Vào xem ngay!',
                    'idea_id' => $idea->id
                ]);
            }
        }
        // =========================================================

        return response()->json([
            'status' => 'success',
            'message' => 'Thêm Ý tưởng thành công!',
            'data' => $idea->load(['categories', 'attachments'])
        ]);
    }

    /**
     * 3. API: Ghim bài Nổi bật (Dành cho Admin/QA Manager)
     * Route: PATCH/POST /api/ideas/{id}/feature
     */
    public function toggleFeature($id)
    {
        $idea = Idea::find($id);

        if (!$idea) {
            return response()->json(['status' => 'error', 'message' => 'Không tìm thấy ý tưởng!'], 404);
        }

        $idea->is_featured = !$idea->is_featured;
        $idea->save();

        return response()->json([
            'status' => 'success',
            'message' => $idea->is_featured ? 'Đã ghim bài nổi bật!' : 'Đã gỡ ghim!',
            'data' => ['is_featured' => $idea->is_featured]
        ]);
    }

    // =========================================================================
    // API 1: XEM CHI TIẾT BÀI VIẾT (GET /api/ideas/{id})
    // =========================================================================
    public function show($id)
    {
        $idea = Idea::with([
            'user.department',
            'categories',
            'attachments',
            'feedbacks.user'
        ])
        ->withCount([
            'reactions as likes_count' => function ($q) { $q->where('vote_type', 1); },
            'reactions as dislikes_count' => function ($q) { $q->where('vote_type', -1); }
        ])
        ->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $idea
        ], 200);
    }

    // =========================================================================
    // API 2: GỬI BÌNH LUẬN (POST /api/ideas/{id}/comments)
    // =========================================================================
    public function storeComment(Request $request, $id)
    {
        $userId = $request->user() ? $request->user()->id : 1;

        // 1. TẠO VÀ LƯU COMMENT VÀO DB (Code cũ của bạn)
        $comment = \App\Models\Feedback::create([
            'idea_id' => $id,
            'user_id' => $userId,
            'content' => $request->input('content'),
            // 🔥 Dùng hàm boolean() để tự động dịch 'true', '1', 'on' thành số 1 lưu vào DB
            'is_anonymous' => $request->boolean('is_anonymous')
        ]);

        // =========================================================
        // 2. CODE BẮN THÔNG BÁO ĐƯỢC CHÈN VÀO ĐÂY (TRƯỚC KHI RETURN)
        // =========================================================

        // Kéo bài viết ra để biết ai là tác giả (user_id của bài viết)
        $idea = \App\Models\Idea::findOrFail($id);

        // Chỉ gửi thông báo nếu người comment KHÔNG PHẢI là chủ bài viết
        if ($idea->user_id !== $userId) {

            // Lấy tên người comment (Nếu ẩn danh thì hiện chữ 'Một người giấu tên', ngược lại lấy tên thật)
            $commenterName = $request->boolean('is_anonymous') ? 'Một người giấu tên' : ($request->user()->name ?? 'Ai đó');

            \App\Models\Notification::create([
                'user_id' => $idea->user_id, // Gửi cho chủ bài viết
                'type' => 'comment',
                'message' => $commenterName . ' đã bình luận bài viết của bạn.',
                'idea_id' => $idea->id
            ]);
        }
        // =========================================================

        // 3. TRẢ VỀ KẾT QUẢ CHO FRONTEND
        return response()->json([
            'status' => 'success',
            'message' => 'Đã gửi bình luận!',
            'data' => $comment
        ], 201);
    }

    // =========================================================================
    // API 3: THẢ LIKE / DISLIKE (POST /api/ideas/{id}/vote)
    // =========================================================================
    public function vote(Request $request, $id)
    {
        $userId = $request->user() ? $request->user()->id : 1;
        $voteType = $request->input('vote_type') === 'up' ? 1 : -1;

        $reaction = \App\Models\Reaction::updateOrCreate(
            ['idea_id' => $id, 'user_id' => $userId],
            ['vote_type' => $voteType]
        );

        return response()->json([
            'status' => 'success',
            'message' => 'Đã ghi nhận tương tác!',
            'data' => $reaction
        ], 200);
    }

    // =========================================================================
    // API 4: TĂNG LƯỢT XEM (POST /api/ideas/{id}/view)
    // =========================================================================
    public function incrementView($id)
    {
        $idea = Idea::findOrFail($id);

        // 🔥 SỬA CHỮ 'view_count' THÀNH ĐÚNG TÊN CỘT TRONG BẢNG IDEAS CỦA BẠN
        // Ví dụ: Nếu DB của bạn tên cột là 'views', thì sửa thành $idea->increment('views');
        $idea->increment('views_count');

        return response()->json([
            'status' => 'success',
            'message' => 'Đã tăng lượt xem thành công!'
        ], 200);
    }

    // =========================================================================
    // API 5: EXPORT CSV (DÀNH CHO ADMIN)
    // Route: GET /api/export/ideas
    // =========================================================================
    public function exportCSV()
    {
        $ideas = Idea::with('user')->get();
        $filename = "ideas_report_" . date('Ymd') . ".csv";

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        $callback = function() use($ideas) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = ( chr(0xEF) . chr(0xBB) . chr(0xBF) )); // Thêm BOM để không lỗi font tiếng Việt

            fputcsv($file, ['ID', 'Tiêu đề', 'Tác giả', 'Lượt Xem', 'Ngày đăng']);

            foreach ($ideas as $idea) {
                fputcsv($file, [
                    $idea->id,
                    $idea->title,
                    $idea->is_anonymous ? 'Ẩn danh' : ($idea->user ? $idea->user->name : 'Unknown'),
                    $idea->view_count, // Dùng đúng cột view_count của bạn
                    $idea->created_at->format('d/m/Y')
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // =========================================================================
    // API 6: EXPORT ZIP (DÀNH CHO ADMIN)
    // Route: GET /api/export/attachments
    // =========================================================================
    public function exportZIP()
    {
        $zip = new \ZipArchive;
        $fileName = 'all_uploads.zip';
        $zipPath = storage_path('app/public/' . $fileName);

        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            $uploadPath = storage_path('app/public/uploads');

            // Kiểm tra xem thư mục có tồn tại không trước khi nén
            if (File::exists($uploadPath)) {
                $files = File::files($uploadPath);
                foreach ($files as $key => $value) {
                    $relativeNameInZipFile = basename($value);
                    $zip->addFile($value, $relativeNameInZipFile);
                }
            }
            $zip->close();
        }

        // Nếu file zip được tạo thành công, trả về để tải xuống rồi xóa ngay cho nhẹ server
        if (file_exists($zipPath)) {
            return response()->download($zipPath)->deleteFileAfterSend(true);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Không có file đính kèm nào để tải về.'
        ], 404);
    }
}
