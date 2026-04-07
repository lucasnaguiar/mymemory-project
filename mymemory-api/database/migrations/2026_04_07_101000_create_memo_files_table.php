<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memo_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('memo_id')->unique()->constrained('memos')->cascadeOnDelete();
            $table->string('disk')->default('local'); // local | s3
            $table->string('storage_key');            // path on disk or S3 object key
            $table->string('original_filename');
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memo_files');
    }
};
