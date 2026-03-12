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
        Schema::create('admin_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->boolean('sent_to_all')->default(false);
            $table->timestamps();
        });

        Schema::create('admin_message_recipients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_message_id')->constrained('admin_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->unique(['admin_message_id', 'user_id']);
        });

        Schema::create('admin_message_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('admin_message_id')->constrained('admin_messages')->cascadeOnDelete();
            $table->foreignId('sender_id')->constrained('users')->cascadeOnDelete();
            $table->text('body');
            $table->string('image_path')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('admin_message_replies');
        Schema::dropIfExists('admin_message_recipients');
        Schema::dropIfExists('admin_messages');
    }
};
