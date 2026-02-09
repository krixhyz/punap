<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSwapRequestsTable extends Migration
{
    public function up()
    {
        Schema::create('swap_requests', function (Blueprint $table) {
            $table->id();

            // target product (the product owner sees and will approve/reject swap)
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // product offered by requester (nullable: user may offer money only)
            $table->foreignId('offered_product_id')->nullable()->constrained('products')->onDelete('set null');

            // user who owns the target product (owner)
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade');

            // user who is requesting the swap (renter/offerer)
            $table->foreignId('requester_id')->constrained('users')->onDelete('cascade');

            // optional money offer on top of the swap (if user wants to pay)
            $table->decimal('offered_amount', 10, 2)->nullable();

            // message from requester
            $table->text('message')->nullable();

            // request status
            $table->enum('status', ['requested','accepted','rejected','cancelled'])->default('requested');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('swap_requests');
    }
}
