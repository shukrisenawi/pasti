<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transaction_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->foreignId('financial_transaction_type_id')->nullable()->after('pasti_id')->constrained('financial_transaction_types')->nullOnDelete();
            $table->enum('credit_debit', ['credit', 'debit'])->nullable()->after('transaction_date');
        });

        $defaultTypeId = DB::table('financial_transaction_types')->insertGetId([
            'name' => 'Lain-lain',
            'is_active' => true,
            'created_by' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('financial_transactions')
            ->whereNull('financial_transaction_type_id')
            ->update([
                'financial_transaction_type_id' => $defaultTypeId,
            ]);

        DB::table('financial_transactions')
            ->whereNull('credit_debit')
            ->update([
                'credit_debit' => DB::raw("CASE WHEN transaction_type = 'masuk' THEN 'credit' ELSE 'debit' END"),
            ]);

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE financial_transactions MODIFY pasti_id BIGINT UNSIGNED NULL');
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE financial_transactions MODIFY pasti_id BIGINT UNSIGNED NOT NULL');
        }

        Schema::table('financial_transactions', function (Blueprint $table) {
            $table->dropForeign(['financial_transaction_type_id']);
            $table->dropColumn(['financial_transaction_type_id', 'credit_debit']);
        });

        Schema::dropIfExists('financial_transaction_types');
    }
};
