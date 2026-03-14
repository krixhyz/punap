<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_requests', 'stock_reserved')) {
                $table->boolean('stock_reserved')->default(false)->after('rent_deposit');
            }

            if (!Schema::hasColumn('rental_requests', 'reserved_until')) {
                $table->dateTime('reserved_until')->nullable()->after('stock_reserved');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_requests', function (Blueprint $table) {
            if (Schema::hasColumn('rental_requests', 'reserved_until')) {
                $table->dropColumn('reserved_until');
            }

            if (Schema::hasColumn('rental_requests', 'stock_reserved')) {
                $table->dropColumn('stock_reserved');
            }
        });
    }
};
