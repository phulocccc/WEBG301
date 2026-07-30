<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use App\Models\Department;
use App\Models\Category;
use App\Models\AcademicYear;
use Illuminate\Support\Facades\Hash;
use Carbon\Carbon;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. TẠO PHÒNG BAN (Departments)
        $itDept = Department::create(['name' => 'IT Department']);
        $hrDept = Department::create(['name' => 'Human Resources']);
        $bizDept = Department::create(['name' => 'Business Analysis']);

        // 2. TẠO VAI TRÒ (Roles)
        $adminRole = Role::create(['name' => 'Admin']);
        $staffRole = Role::create(['name' => 'Staff']);
        $qaCoordRole = Role::create(['name' => 'QA Coordinator']);
        $qaManagerRole = Role::create(['name' => 'QA Manager']);

        // 3. TẠO TÀI KHOẢN MẪU (Users/Accounts)
        // Tài khoản Admin để quản trị
        User::create([
            'name' => 'System Admin',
            'email' => 'admin@univ.edu',
            'password' => Hash::make('123456'),
            'role_id' => $adminRole->id,
            'department_id' => $itDept->id,
        ]);

        // Tài khoản Staff để test nộp Ý tưởng
        User::create([
            'name' => 'Nguyen Van Staff',
            'email' => 'staff@univ.edu',
            'password' => Hash::make('123456'),
            'role_id' => $staffRole->id,
            'department_id' => $bizDept->id,
        ]);

        // Tài khoản QA Coordinator của phòng Business (Để test gửi Email)
        User::create([
            'name' => 'QA Business',
            'email' => 'qa_biz@univ.edu',
            'password' => Hash::make('123456'),
            'role_id' => $qaCoordRole->id,
            'department_id' => $bizDept->id,
        ]);

        // 4. TẠO DANH MỤC (Categories)
        Category::create(['name' => 'Facility Management']);
        Category::create(['name' => 'IT Services']);
        Category::create(['name' => 'Education Quality']);

        // 5. TẠO NĂM HỌC (Academic Years)
        // Năm học đang MỞ (Còn hạn nộp bài)
        AcademicYear::create([
            'year' => 'Academic Year 2025-2026',
            'closure_date' => Carbon::now()->addDays(15),
            'final_closure_date' => Carbon::now()->addDays(30),
        ]);

        // Năm học đã ĐÓNG (Để test lỗi 403 khi nộp bài quá hạn)
        AcademicYear::create([
            'year' => 'Past Year 2024',
            'closure_date' => Carbon::now()->subDays(10),
            'final_closure_date' => Carbon::now()->subDays(5),
        ]);

        echo "--------------------------------------------------\n";
        echo "✅ ĐÃ BƠM DỮ LIỆU THÀNH CÔNG!\n";
        echo "Tài khoản test: staff@univ.edu / 123456\n";
        echo "--------------------------------------------------\n";
    }
}
