<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Idea extends Model
{
    use HasFactory;

    // Nếu bảng trong DB của bạn tên khác (VD: 'staff_ideas'), hãy bỏ comment và sửa chữ 'ideas' ở dòng dưới:
    // protected $table = 'ideas';

    // Khai báo các cột được phép nhận dữ liệu từ Frontend (Đã xóa description)
    protected $fillable = [
        'title',
        'content',
        'user_id',
        'academic_year_id',
        'is_anonymous',
        'priority_level',
        'is_featured',
    ];

    // =====================================================================
    // KHU VỰC KHAI BÁO MỐI QUAN HỆ (RELATIONSHIPS) - "BẢN ĐỒ" CHO DATABASE
    // =====================================================================

    /**
     * 1. Quan hệ với Tác giả (User): N Nhiều Ý tưởng thuộc về 1 User
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    /**
     * 2. Quan hệ với Năm học (AcademicYear): Nhiều Ý tưởng thuộc về 1 Năm học
     */
    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'id');
    }

    /**
     * 3. Quan hệ với Danh mục (Category): N Nhiều Ý tưởng có Nhiều Danh mục (N-N)
     * Lưu ý: Thay 'idea_tags' bằng tên bảng trung gian thực tế trong Database của bạn nếu khác
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'idea_category', 'idea_id', 'category_id');
    }

    /**
     * 4. Quan hệ với File đính kèm (Attachment): 1 Ý tưởng có Nhiều File
     */
    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'idea_id', 'id');
    }

    /**
     * 5. Quan hệ với Bình luận (Feedback): 1 Ý tưởng có Nhiều Bình luận
     * (Hàm này giúp giải quyết triệt để lỗi 500 của hàm withCount)
     */
    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'idea_id', 'id');
    }

    /**
     * 6. Quan hệ với Cảm xúc (Reaction): 1 Ý tưởng có Nhiều Lượt Like/Dislike
     * (Hàm này giúp giải quyết triệt để lỗi 500 của hàm withCount)
     */
    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'idea_id', 'id');
    }

}
