<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();

            // null = personal memo; set for group memos
            $table->foreignId('group_id')->nullable()->constrained('groups')->nullOnDelete();

            $table->enum('type', ['text', 'url', 'image', 'audio', 'video', 'document']);

            // draft = created by process step, awaiting confirmation
            // confirmed = saved (by confirm or direct creation)
            $table->enum('status', ['draft', 'confirmed'])->default('confirmed')->index();

            $table->string('title')->nullable();
            $table->text('content')->nullable();       // original/transcribed content
            $table->text('summary')->nullable();       // AI-generated summary
            $table->json('keywords')->nullable();      // string[]
            $table->string('source_url')->nullable();  // for url type

            $table->enum('ai_level', ['none', 'basic', 'full'])->default('full');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['user_id', 'status', 'created_at']);
            $table->index(['group_id', 'status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memos');
    }
};
