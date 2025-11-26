<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'quantity')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->integer('quantity')->default(1)->after('transaction_type');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('orders', 'quantity')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
