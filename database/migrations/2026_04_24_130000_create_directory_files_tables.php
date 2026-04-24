<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('directory_files', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('original_name');
            $table->string('file_path');
            $table->enum('target_type', ['all', 'selected'])->default('all');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('directory_file_guru', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('directory_file_id')->constrained('directory_files')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['directory_file_id', 'guru_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('directory_file_guru');
        Schema::dropIfExists('directory_files');
    }
};

