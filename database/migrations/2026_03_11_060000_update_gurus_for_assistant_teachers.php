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
        Schema::table('gurus', function (Blueprint $table) {
            $table->string('name')->nullable()->after('pasti_id');
            $table->string('email')->nullable()->after('name');
            $table->boolean('is_assistant')->default(false)->after('email');
        });

        if (DB::getDriverName() === 'sqlite') {
            DB::table('gurus')
                ->select(['id', 'user_id'])
                ->orderBy('id')
                ->get()
                ->each(function (object $guru): void {
                    $user = DB::table('users')->where('id', $guru->user_id)->first(['name', 'email']);

                    if (! $user) {
                        return;
                    }

                    DB::table('gurus')
                        ->where('id', $guru->id)
                        ->update([
                            'name' => $user->name,
                            'email' => $user->email,
                        ]);
                });
        } else {
            DB::statement('
                UPDATE gurus
                INNER JOIN users ON users.id = gurus.user_id
                SET gurus.name = users.name, gurus.email = users.email
            ');
        }

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('gurus', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('gurus')->whereNull('user_id')->delete();

        Schema::table('gurus', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
        });

        Schema::table('gurus', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->dropColumn(['name', 'email', 'is_assistant']);
        });
    }
};
