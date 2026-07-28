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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            // user_id là người sẽ NHẬN thông báo
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('type'); // Ví dụ: 'comment', 'like', 'new_idea'
            $table->string('message');
            $table->boolean('is_read')->default(false); // Mặc định là chưa đọc
            $table->unsignedBigInteger('idea_id')->nullable(); // Có thể null nếu thông báo ko gắn với idea nào
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
