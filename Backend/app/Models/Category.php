<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name']; // Có thể bạn đã có dòng này

    // THÊM HÀM NÀY VÀO ĐỂ KHẮC PHỤC LỖI 500:
    public function ideas()
    {
        // Thay 'idea_category' bằng đúng cái tên bảng trung gian trong DB của bạn.
        // 2 tham số phía sau là tên cột khóa ngoại.
        return $this->belongsToMany(\App\Models\Idea::class, 'idea_category', 'category_id', 'idea_id');
    }
}
