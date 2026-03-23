<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('financial_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasti_id')->constrained('pastis')->cascadeOnDelete();
            $table->date('transaction_date');
            $table->enum('transaction_type', ['masuk', 'keluar']);
            $table->decimal('amount', 12, 2);
            $table->string('amount_remark')->nullable();
            $table->text('transaction_remark')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_transactions');
    }
};
