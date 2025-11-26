<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void  
    {
        Schema::create('rental_requests', function (Blueprint $table) {
            $table->id();

            // Relations
            $table->foreignId('rental_id')->constrained('rentals')->onDelete('cascade')->nullable(); // which rental item
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('owner_id')->constrained('users')->onDelete('cascade'); // owner of the product
            $table->foreignId('renter_id')->constrained('users')->onDelete('cascade'); // person requesting

            // Request details
            $table->dateTime('start_date');
            $table->dateTime('end_date');
            $table->integer('duration')->nullable(); // total hours/days
            $table->decimal('total_amount', 10, 2)->nullable();
            $table->decimal('rent_deposit', 10, 2)->nullable();

            // Status of the request
            $table->enum('status', [
                'requested', // renter sent request
                'approved',  // owner approved
                'rejected',  // owner declined
                'cancelled'
            ])->default('requested');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rental_requests');
    }
};
