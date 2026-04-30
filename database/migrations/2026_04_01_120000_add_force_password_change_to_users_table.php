<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('force_password_change')->default(false)->after('password');
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("\n                UPDATE users u\n                INNER JOIN model_has_roles mhr\n                    ON mhr.model_id = u.id\n                    AND mhr.model_type = 'App\\\\Models\\\\User'\n                INNER JOIN roles r\n                    ON r.id = mhr.role_id\n                    AND r.name = 'guru'\n                LEFT JOIN gurus g\n                    ON g.user_id = u.id\n                SET u.force_password_change = 1\n                WHERE u.nama_samaran IS NULL\n                    OR u.tarikh_lahir IS NULL\n                    OR u.avatar_path IS NULL\n                    OR g.pasti_id IS NULL\n                    OR g.phone IS NULL\n                    OR g.kad_pengenalan IS NULL\n                    OR g.marital_status IS NULL\n                    OR g.joined_at IS NULL\n            ");
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('force_password_change');
        });
    }
};
