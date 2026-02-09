<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('orders', 'quantity')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->integer('quantity')->default(1);
            });
        }

        if (!Schema::hasColumn('orders', 'unit_price')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('unit_price', 10, 2)->default(0);
            });
        }

        if (!Schema::hasColumn('orders', 'total_price')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->decimal('total_price', 10, 2)->default(0);
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

        if (Schema::hasColumn('orders', 'unit_price')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('unit_price');
            });
        }

        if (Schema::hasColumn('orders', 'total_price')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->dropColumn('total_price');
            });
        }
    }
};
