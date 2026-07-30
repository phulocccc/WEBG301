<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reaction extends Model
{
    protected $table = 'reactions';
    protected $fillable = ['idea_id', 'user_id', 'vote_type'];
}
