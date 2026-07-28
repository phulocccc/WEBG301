<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    // QUAN TRỌNG NHẤT LÀ DÒNG NÀY:
    // Ép Model Feedback phải đi moi móc dữ liệu từ bảng 'comments'
    protected $table = 'comments';

    protected $fillable = [
        'idea_id',
        'user_id',
        'content',
        'is_anonymous',
        // ... các cột khác trong bảng comments của bạn
    ];
    // Thêm hàm này vào trong class Feedback
    public function user()
    {
        // Khai báo Feedback này thuộc về User nào
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
