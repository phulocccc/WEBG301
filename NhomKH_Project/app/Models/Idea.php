<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Idea extends Model
{
    use HasFactory;


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


    public function user()
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }


    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id', 'id');
    }


    public function categories()
    {
        return $this->belongsToMany(Category::class, 'idea_category', 'idea_id', 'category_id');
    }


    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'idea_id', 'id');
    }


    public function feedbacks()
    {
        return $this->hasMany(Feedback::class, 'idea_id', 'id');
    }


    public function reactions()
    {
        return $this->hasMany(Reaction::class, 'idea_id', 'id');
    }

}
