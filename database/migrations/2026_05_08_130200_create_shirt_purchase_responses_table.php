<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shirt_purchase_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('shirt_purchase_id')->constrained('shirt_purchases')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->string('size', 10)->nullable();
            $table->text('notes')->nullable();
            $table->unsignedInteger('quantity')->default(1);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->foreignId('paid_marked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shirt_purchase_id', 'guru_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shirt_purchase_responses');
    }
};
