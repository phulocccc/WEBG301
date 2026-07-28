<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    protected $table = 'comments';
    protected $fillable = ['idea_id', 'user_id', 'content', 'is_anonymous'];

    public function idea()
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
