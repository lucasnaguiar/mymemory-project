<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('subscription_plan_id')
                ->nullable()
                ->after('id')
                ->constrained('subscription_plans')
                ->nullOnDelete();

            $table->enum('role', ['user', 'admin'])
                ->default('user')
                ->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('subscription_plan_id');
            $table->dropColumn('role');
        });
    }
};
