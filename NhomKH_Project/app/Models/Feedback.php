<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feedback extends Model
{
    use HasFactory;

    protected $table = 'comments';

    protected $fillable = [
        'idea_id',
        'user_id',
        'content',
        'is_anonymous',
    ];

    public function user()
    {
        // Khai báo Feedback này thuộc về User nào
        return $this->belongsTo(\App\Models\User::class, 'user_id');
    }
}
