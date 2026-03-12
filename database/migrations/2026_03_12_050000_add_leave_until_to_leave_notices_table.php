<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('leave_notices', function (Blueprint $table) {
            $table->date('leave_until')->nullable()->after('leave_date');
        });

        DB::table('leave_notices')->update([
            'leave_until' => DB::raw('leave_date'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('leave_notices', function (Blueprint $table) {
            $table->dropColumn('leave_until');
        });
    }
};
