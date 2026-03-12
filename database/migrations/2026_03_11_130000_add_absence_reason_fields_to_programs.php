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
        Schema::table('programs', function (Blueprint $table) {
            $table->boolean('require_absence_reason')->default(false)->after('description');
        });

        Schema::table('program_teacher', function (Blueprint $table) {
            $table->text('absence_reason')->nullable()->after('program_status_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('program_teacher', function (Blueprint $table) {
            $table->dropColumn('absence_reason');
        });

        Schema::table('programs', function (Blueprint $table) {
            $table->dropColumn('require_absence_reason');
        });
    }
};
