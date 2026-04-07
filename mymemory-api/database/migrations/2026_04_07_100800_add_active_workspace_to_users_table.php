<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // null = personal workspace; set to a group_id when group context is active
            $table->foreignId('active_workspace_group_id')
                ->nullable()
                ->after('subscription_plan_id')
                ->constrained('groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('active_workspace_group_id');
        });
    }
};
