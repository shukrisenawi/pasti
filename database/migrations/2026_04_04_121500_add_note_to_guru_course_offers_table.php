<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guru_course_offers', function (Blueprint $table): void {
            $table->text('note')->nullable()->after('registration_deadline');
        });
    }

    public function down(): void
    {
        Schema::table('guru_course_offers', function (Blueprint $table): void {
            $table->dropColumn('note');
        });
    }
};
