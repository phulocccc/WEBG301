<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name'];

    public function ideas()
    {

        return $this->belongsToMany(\App\Models\Idea::class, 'idea_category', 'category_id', 'idea_id');
    }
}
