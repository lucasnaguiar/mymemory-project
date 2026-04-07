<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('monthly_usage', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            // null = personal usage; set for group-scoped usage tracking
            $table->foreignId('group_id')->nullable()->constrained('groups')->cascadeOnDelete();

            $table->unsignedSmallInteger('year');
            $table->unsignedTinyInteger('month'); // 1–12

            $table->unsignedInteger('api_credits_used')->default(0);
            $table->unsignedInteger('downloads_count')->default(0);
            $table->unsignedInteger('memos_created_count')->default(0);

            $table->timestamps();

            $table->unique(['user_id', 'group_id', 'year', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monthly_usage');
    }
};
