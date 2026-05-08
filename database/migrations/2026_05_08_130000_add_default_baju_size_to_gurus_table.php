<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->string('default_baju_size', 10)->nullable()->after('kursus_guru');
        });
    }

    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->dropColumn('default_baju_size');
        });
    }
};
