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
        Schema::create('pemarkahan_title_options', function (Blueprint $table) {
            $table->id();
            $table->string('title')->unique();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('pasti_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pasti_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pemarkahan_title_option_id')->constrained('pemarkahan_title_options')->cascadeOnDelete();
            $table->unsignedSmallInteger('year');
            $table->decimal('score', 8, 2);
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['pasti_id', 'pemarkahan_title_option_id', 'year'], 'pasti_scores_unique_per_year');
            $table->index(['year', 'pemarkahan_title_option_id']);
        });

        $now = now();
        DB::table('pemarkahan_title_options')->insert([
            [
                'title' => 'Penaziran Infrastruktur',
                'sort_order' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'title' => 'Penaziran Pengajaran Guru',
                'sort_order' => 2,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pasti_scores');
        Schema::dropIfExists('pemarkahan_title_options');
    }
};
