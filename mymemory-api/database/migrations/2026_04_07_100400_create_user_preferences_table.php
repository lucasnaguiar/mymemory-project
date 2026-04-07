<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();

            // AI level per media type: none | basic | full
            $table->enum('ai_level_text', ['none', 'basic', 'full'])->default('full');
            $table->enum('ai_level_url', ['none', 'basic', 'full'])->default('full');
            $table->enum('ai_level_image', ['none', 'basic', 'full'])->default('full');
            $table->enum('ai_level_audio', ['none', 'basic', 'full'])->default('full');
            $table->enum('ai_level_video', ['none', 'basic', 'full'])->default('full');
            $table->enum('ai_level_document', ['none', 'basic', 'full'])->default('full');

            $table->boolean('confirm_before_processing')->default(true);
            $table->boolean('sound_enabled')->default(true);

            // OCR correction threshold: 1–100 (null = disabled)
            $table->unsignedTinyInteger('ocr_correction_threshold')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_preferences');
    }
};
