<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->decimal('elaun', 10, 2)->nullable()->after('kad_pengenalan');
            $table->decimal('elaun_transit', 10, 2)->nullable()->after('elaun');
            $table->decimal('elaun_lain', 10, 2)->nullable()->after('elaun_transit');
        });
    }

    public function down(): void
    {
        Schema::table('gurus', function (Blueprint $table): void {
            $table->dropColumn(['elaun', 'elaun_transit', 'elaun_lain']);
        });
    }
};
