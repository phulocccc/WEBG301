<?php

namespace App\Http\Controllers;

use App\Models\Idea;
use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ReportController extends Controller
{
    // 1. API Thống kê & Báo cáo ngoại lệ
    public function statistics()
    {
        $ideasWithoutComments = Idea::doesntHave('comments')->get();

        return response()->json([
            'status' => 'success',
            'data' => [
                'total_ideas' => Idea::count(),
                'total_comments' => Comment::count(),
                'exception_report' => [
                    'ideas_without_comments_count' => $ideasWithoutComments->count(),
                    'ideas_without_comments_list' => $ideasWithoutComments
                ]
            ]
        ]);
    }

    // 2. Hàm Xuất toàn bộ dữ liệu ra file CSV
    public function exportCsv()
    {
        $ideas = Idea::with('user')->get();
        $fileName = 'Bao_Cao_Y_Tuong.csv';

        $headers = [
            "Content-type"        => "text/csv",
            "Content-Disposition" => "attachment; filename=$fileName",
            "Pragma"              => "no-cache",
            "Cache-Control"       => "must-revalidate, post-check=0, pre-check=0",
            "Expires"             => "0"
        ];

        // Khai báo các cột trong file Excel
        $columns = ['ID', 'Tieu De', 'Noi Dung', 'Tac Gia', 'An Danh', 'Ngay Dang'];

        $callback = function() use($ideas, $columns) {
            $file = fopen('php://output', 'w');
            fputs($file, $bom = (chr(0xEF) . chr(0xBB) . chr(0xBF)));
            fputcsv($file, $columns);

            foreach ($ideas as $idea) {
                fputcsv($file, [
                    $idea->id,
                    $idea->title,
                    $idea->content,
                    $idea->user->name ?? 'N/A',
                    $idea->is_anonymous ? 'Co' : 'Khong',
                    $idea->created_at
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    // 3. Hàm Gom toàn bộ tài liệu đính kèm thành 1 file ZIP
    public function exportZip()
    {
        $zip = new ZipArchive();
        $zipFileName = 'Tai_Lieu_Dinh_Kem.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $files = Storage::disk('public')->files('uploads');

            foreach ($files as $file) {
                $absolutePath = storage_path('app/public/' . $file);
                $relativeNameInZip = basename($file);
                $zip->addFile($absolutePath, $relativeNameInZip);
            }
            $zip->close();
        }

        return response()->download($zipFilePath)->deleteFileAfterSend(true);
    }
}
