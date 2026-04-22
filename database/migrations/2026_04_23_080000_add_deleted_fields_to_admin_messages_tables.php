<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('admin_messages', function (Blueprint $table): void {
            $table->foreignId('deleted_by_id')->nullable()->after('sent_to_all')->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable()->after('deleted_by_id');
        });

        Schema::table('admin_message_replies', function (Blueprint $table): void {
            $table->foreignId('deleted_by_id')->nullable()->after('image_path')->constrained('users')->nullOnDelete();
            $table->timestamp('deleted_at')->nullable()->after('deleted_by_id');
        });
    }

    public function down(): void
    {
        Schema::table('admin_message_replies', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deleted_by_id');
            $table->dropColumn('deleted_at');
        });

        Schema::table('admin_messages', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('deleted_by_id');
            $table->dropColumn('deleted_at');
        });
    }
};
