<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('groups', function (Blueprint $table) {
            $table->id();
            $table->string('name');

            $table->foreignId('owner_user_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('subscription_plan_id')
                ->constrained('subscription_plans')
                ->restrictOnDelete();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('groups');
    }
};
