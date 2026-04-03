<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guru_course_offers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedTinyInteger('target_semester');
            $table->date('registration_deadline');
            $table->foreignId('sent_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('sent_at');
            $table->timestamps();

            $table->index('target_semester');
        });

        Schema::create('guru_course_offer_responses', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('guru_course_offer_id')->constrained('guru_course_offers')->cascadeOnDelete();
            $table->foreignId('guru_id')->constrained('gurus')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('decision', 20)->nullable();
            $table->text('stop_reason')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->timestamps();

            $table->unique(['guru_course_offer_id', 'guru_id'], 'guru_offer_guru_unique');
            $table->index(['guru_id', 'responded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guru_course_offer_responses');
        Schema::dropIfExists('guru_course_offers');
    }
};
