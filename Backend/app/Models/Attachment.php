<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Attachment extends Model
{
    protected $table = 'attachments';
    protected $fillable = ['idea_id', 'file_path', 'file_name'];

    public function idea()
    {
        return $this->belongsTo(Idea::class, 'idea_id');
    }
}
