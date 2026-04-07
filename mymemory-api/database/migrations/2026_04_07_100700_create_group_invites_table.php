<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_invites', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_id')->constrained('groups')->cascadeOnDelete();
            $table->string('email');
            $table->enum('role', ['editor', 'viewer'])->default('editor');
            $table->string('token')->unique();
            $table->foreignId('invited_by_user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->timestamps();

            $table->index(['group_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_invites');
    }
};
