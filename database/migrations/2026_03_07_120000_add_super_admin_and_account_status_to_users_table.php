<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin','super_admin') NOT NULL DEFAULT 'user'");
        }

        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'account_status')) {
                $table->enum('account_status', ['active', 'suspended', 'banned'])->default('active')->after('role');
            }

            if (! Schema::hasColumn('users', 'status_notes')) {
                $table->text('status_notes')->nullable()->after('account_status');
            }
        });
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'status_notes')) {
                $table->dropColumn('status_notes');
            }

            if (Schema::hasColumn('users', 'account_status')) {
                $table->dropColumn('account_status');
            }
        });

        if ($driver === 'mysql') {
            DB::statement("ALTER TABLE users MODIFY COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user'");
        }
    }
};
