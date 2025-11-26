<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'quantity')) {
            Schema::table('products', function (Blueprint $table) {
                $table->integer('quantity')->default(1)->after('price');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'quantity')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('quantity');
            });
        }
    }
};
