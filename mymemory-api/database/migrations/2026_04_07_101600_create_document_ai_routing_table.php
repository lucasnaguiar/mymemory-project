<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Singleton config table: one row (id = 1), managed exclusively by admins
        Schema::create('document_ai_routing', function (Blueprint $table) {
            $table->id();
            $table->json('config'); // Routing rules: mime types → AI pipeline mapping
            $table->foreignId('updated_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('document_ai_routing');
    }
};
