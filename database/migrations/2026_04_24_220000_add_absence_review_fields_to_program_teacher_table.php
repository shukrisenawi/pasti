<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('program_teacher', function (Blueprint $table): void {
            $table->string('absence_reason_status')->nullable()->after('absence_reason');
            $table->foreignId('absence_reason_reviewed_by')->nullable()->after('absence_reason_status')->constrained('users')->nullOnDelete();
            $table->timestamp('absence_reason_reviewed_at')->nullable()->after('absence_reason_reviewed_by');
        });
    }

    public function down(): void
    {
        Schema::table('program_teacher', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('absence_reason_reviewed_by');
            $table->dropColumn(['absence_reason_status', 'absence_reason_reviewed_at']);
        });
    }
};
