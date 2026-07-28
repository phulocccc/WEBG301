<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('academic_years', function (Blueprint $table) {
            $table->id();
            $table->string('year'); // Lưu tên năm học, ví dụ: "2025-2026"
            $table->dateTime('closure_date'); // Hạn chót nộp bài mới
            $table->dateTime('final_closure_date'); // Hạn chót đóng toàn bộ tương tác
            $table->timestamps(); // Tự động sinh ra 2 cột created_at và updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_years');
    }
};
