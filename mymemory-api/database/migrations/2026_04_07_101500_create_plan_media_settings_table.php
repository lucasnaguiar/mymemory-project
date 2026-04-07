<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('plan_media_settings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->cascadeOnDelete();

            $table->enum('media_type', ['image', 'audio', 'video', 'document']);
            $table->unsignedInteger('max_file_size_mb');

            // For audio/video chunking: max chunk duration in minutes (null = no chunking)
            $table->unsignedSmallInteger('max_chunk_minutes')->nullable();

            // OCR vision correction threshold default for this plan (1–100, null = disabled)
            $table->unsignedTinyInteger('ocr_correction_threshold_default')->nullable();

            $table->timestamps();

            $table->unique(['subscription_plan_id', 'media_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_media_settings');
    }
};
