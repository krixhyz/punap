<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSwapsTable extends Migration
{
    public function up()
    {
        Schema::create('swaps', function (Blueprint $table) {
            $table->id();

            // reference to swap_request that led to this swap
            $table->foreignId('swap_request_id')->constrained('swap_requests')->onDelete('cascade');

            $table->foreignId('product_a_id')->constrained('products')->onDelete('cascade'); // target product
            $table->foreignId('product_b_id')->constrained('products')->onDelete('cascade'); // offered product

            $table->foreignId('owner_a_id')->constrained('users')->onDelete('cascade'); // owner of product A before swap
            $table->foreignId('owner_b_id')->constrained('users')->onDelete('cascade'); // owner of product B before swap

            // snapshot values
            $table->decimal('offered_amount', 10, 2)->nullable();
            $table->text('notes')->nullable();

            $table->enum('status', ['completed','failed'])->default('completed');

            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('swaps');
    }
}
