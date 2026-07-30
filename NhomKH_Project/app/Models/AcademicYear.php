<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AcademicYear extends Model
{
    use HasFactory;

    // Chỉ định chính xác tên bảng trong Database
    protected $table = 'academic_years';

    // Cho phép điền dữ liệu vào các cột này
    protected $fillable = [
        'year',
        'closure_date',
        'final_closure_date',
    ];

    public function ideas()
    {
        return $this->hasMany(Idea::class, 'academic_year_id', 'id');
    }
}
