<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->enum('payment_method', ['cash', 'transfer'])->nullable()->after('credit_debit');
        });

        DB::table('financial_transactions')
            ->whereNull('payment_method')
            ->update([
                'payment_method' => 'transfer',
            ]);
    }

    public function down(): void
    {
        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });
    }
};
