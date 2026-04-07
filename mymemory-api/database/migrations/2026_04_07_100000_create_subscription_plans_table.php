<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->enum('type', ['individual', 'group']);
            $table->boolean('is_active')->default(true)->index();

            // Memo and storage limits (null = unlimited)
            $table->unsignedInteger('max_memos')->nullable();
            $table->decimal('storage_gb', 8, 2)->default(0);
            $table->unsignedInteger('api_credits_per_month')->nullable();
            $table->unsignedInteger('downloads_per_month')->nullable();

            // Media capabilities
            $table->boolean('supports_audio_video')->default(false);
            $table->boolean('supports_chunking')->default(false);

            $table->unsignedInteger('price_cents')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_plans');
    }
};
