<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Lệnh này sẽ tự động sinh ra cột 'deleted_at' kiểu TIMESTAMP, cho phép NULL
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Lệnh này dùng để xóa cột đi nếu bạn hối hận muốn quay xe (Rollback)
            $table->dropSoftDeletes();
        });
    }
};
