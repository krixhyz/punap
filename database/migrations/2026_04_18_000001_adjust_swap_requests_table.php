<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('swap_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('swap_requests', 'asking_amount')) {
                $table->decimal('asking_amount', 10, 2)->nullable()->after('offered_amount');
            }
            if (!Schema::hasColumn('swap_requests', 'money_direction')) {
                $table->enum('money_direction', ['none', 'owner_asks_cash', 'requester_offers_cash'])
                      ->default('none')->after('asking_amount');
            }
            if (!Schema::hasColumn('swap_requests', 'order_details_sent_at')) {
                $table->timestamp('order_details_sent_at')->nullable()->after('countered_at');
            }
        });

        // Update status enum to include new states
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE swap_requests MODIFY status ENUM('requested', 'countered', 'awaiting_payment', 'paid', 'confirmation_pending', 'completed', 'rejected', 'cancelled', 'expired') DEFAULT 'requested'"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Reset status enum
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement(
                "ALTER TABLE swap_requests MODIFY status ENUM('requested', 'countered', 'awaiting_payment', 'accepted', 'rejected', 'cancelled') DEFAULT 'requested'"
            );
        }

        Schema::table('swap_requests', function (Blueprint $table) {
            if (Schema::hasColumn('swap_requests', 'asking_amount')) {
                $table->dropColumn('asking_amount');
            }
            if (Schema::hasColumn('swap_requests', 'money_direction')) {
                $table->dropColumn('money_direction');
            }
            if (Schema::hasColumn('swap_requests', 'order_details_sent_at')) {
                $table->dropColumn('order_details_sent_at');
            }
        });
    }
};
