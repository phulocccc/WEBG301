<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id', 'type', 'message', 'is_read', 'idea_id'
    ];

    protected $casts = [
        'is_read' => 'boolean', // Tự động ép kiểu về true/false cho giống Frontend
    ];
}
