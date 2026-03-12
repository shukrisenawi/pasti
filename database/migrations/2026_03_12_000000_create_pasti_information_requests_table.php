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
        Schema::create('pasti_information_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasti_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('requested_at');
            $table->foreignId('completed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();

            $table->unsignedInteger('jumlah_guru')->nullable();
            $table->unsignedInteger('jumlah_pembantu_guru')->nullable();
            $table->unsignedInteger('murid_lelaki_4_tahun')->nullable();
            $table->unsignedInteger('murid_perempuan_4_tahun')->nullable();
            $table->unsignedInteger('murid_lelaki_5_tahun')->nullable();
            $table->unsignedInteger('murid_perempuan_5_tahun')->nullable();
            $table->unsignedInteger('murid_lelaki_6_tahun')->nullable();
            $table->unsignedInteger('murid_perempuan_6_tahun')->nullable();

            $table->timestamps();

            $table->index(['pasti_id', 'completed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasti_information_requests');
    }
};
