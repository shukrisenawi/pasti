<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('kelas_student_counts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kelas_id')->unique();
            $table->unsignedInteger('lelaki_count')->default(0);
            $table->unsignedInteger('perempuan_count')->default(0);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kelas_student_counts');
    }
};
